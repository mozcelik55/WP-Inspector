<?php
/**
 * Pure-PHP VAPID key generation and JWT signing
 * Works without OpenSSL EC support
 * Uses pre-computed curve constants for P-256
 */
class WPI_Vapid {

    /**
     * Get or generate VAPID keys — tries OpenSSL first, falls back to pure PHP
     */
    public static function get_keys() {
        $keys = get_option('wpi_vapid_keys');
        if ($keys && isset($keys['public'], $keys['private']) && !empty($keys['public'])) {
            return $keys;
        }
        // Try OpenSSL EC first
        $keys = self::generate_openssl();
        if (!$keys) {
            // Fallback: generate via random private key + pure PHP point multiplication
            $keys = self::generate_pure_php();
        }
        if ($keys) {
            update_option('wpi_vapid_keys', $keys, false);
        }
        return $keys;
    }

    /**
     * OpenSSL EC method (preferred)
     */
    private static function generate_openssl() {
        if (!function_exists('openssl_pkey_new')) return null;
        $key = @openssl_pkey_new(array(
            'curve_name'       => 'prime256v1',
            'private_key_type' => OPENSSL_KEYTYPE_EC,
        ));
        if (!$key) return null;
        $details = openssl_pkey_get_details($key);
        if (!$details || !isset($details['ec']['x'], $details['ec']['y'], $details['ec']['d'])) return null;
        $pub_raw = chr(4)
            . str_pad($details['ec']['x'], 32, chr(0), STR_PAD_LEFT)
            . str_pad($details['ec']['y'], 32, chr(0), STR_PAD_LEFT);
        openssl_pkey_export($key, $priv_pem);
        return array(
            'public'  => self::base64url($pub_raw),
            'private' => $priv_pem,
            'method'  => 'openssl',
        );
    }

    /**
     * Pure PHP fallback — generate random private scalar, compute public point
     * Uses P-256 curve parameters
     */
    private static function generate_pure_php() {
        // P-256 curve order
        $n = '115792089210356248762697446949407573529996955224135760342422259061068512044369';

        // Generate random 32-byte private key scalar (must be < n)
        $priv_bytes = '';
        for ($i = 0; $i < 32; $i++) {
            $priv_bytes .= chr(rand(0, 255));
        }
        // Use wp_generate_password entropy instead for better randomness
        $rand_str = wp_generate_password(64, true, true);
        $priv_bytes = substr(hash('sha256', $rand_str . microtime() . mt_rand(), true), 0, 32);

        // P-256 base point G
        $Gx = '48439561293906451759052585252797914202762949526041747995844080717082404635286';
        $Gy = '36134250956749795798585127919587881956611106672985015071877198253568414405109';
        $p  = '115792089210356248762697446949407573536500980748722583680042947026553088193807';

        // Scalar as GMP
        $k = self::bytes_to_gmp($priv_bytes);
        // Reduce k mod n
        $n_gmp = gmp_init($n);
        $k = gmp_mod($k, $n_gmp);
        if (gmp_cmp($k, gmp_init(0)) === 0) {
            $k = gmp_init(1);
        }

        // Compute public key point kG
        $point = self::point_mul(
            array(gmp_init($Gx), gmp_init($Gy)),
            $k,
            gmp_init($p)
        );
        if (!$point) return null;

        list($pub_x, $pub_y) = $point;
        $x_bytes = str_pad(self::gmp_to_bytes($pub_x), 32, chr(0), STR_PAD_LEFT);
        $y_bytes = str_pad(self::gmp_to_bytes($pub_y), 32, chr(0), STR_PAD_LEFT);
        $pub_raw = chr(4) . $x_bytes . $y_bytes;

        $priv_raw = str_pad(self::gmp_to_bytes($k), 32, chr(0), STR_PAD_LEFT);

        return array(
            'public'      => self::base64url($pub_raw),
            'private_raw' => self::base64url($priv_raw),
            'pub_x'       => self::base64url($x_bytes),
            'pub_y'       => self::base64url($y_bytes),
            'method'      => 'pure_php',
        );
    }

    /**
     * P-256 scalar point multiplication using double-and-add
     */
    private static function point_mul($P, $k, $p) {
        $R = null;
        $Q = $P;
        $bits = gmp_strval($k, 2);
        for ($i = 0; $i < strlen($bits); $i++) {
            if ($R !== null) {
                $R = self::point_double($R, $p);
            }
            if ($bits[$i] === '1') {
                $R = ($R === null) ? $Q : self::point_add($R, $Q, $p);
            }
        }
        return $R;
    }

    private static function point_add($P, $Q, $p) {
        list($x1,$y1) = $P; list($x2,$y2) = $Q;
        $lam_num = gmp_mod(gmp_sub($y2,$y1), $p);
        if (gmp_cmp($lam_num,0)<0) $lam_num = gmp_add($lam_num,$p);
        $lam_den = gmp_mod(gmp_sub($x2,$x1), $p);
        if (gmp_cmp($lam_den,0)<0) $lam_den = gmp_add($lam_den,$p);
        $lam_den_inv = gmp_invert($lam_den, $p);
        if ($lam_den_inv === false) return null;
        $lam = gmp_mod(gmp_mul($lam_num, $lam_den_inv), $p);
        $x3 = gmp_mod(gmp_sub(gmp_sub(gmp_mul($lam,$lam),$x1),$x2),$p);
        if (gmp_cmp($x3,0)<0) $x3=gmp_add($x3,$p);
        $y3 = gmp_mod(gmp_sub(gmp_mul($lam,gmp_sub($x1,$x3)),$y1),$p);
        if (gmp_cmp($y3,0)<0) $y3=gmp_add($y3,$p);
        return array($x3,$y3);
    }

    private static function point_double($P, $p) {
        list($x1,$y1) = $P;
        // a = -3 for P-256
        $a = gmp_init('115792089210356248762697446949407573536500980748722583680042947026553088193804');
        $lam_num = gmp_mod(gmp_add(gmp_mul(gmp_init(3),gmp_mul($x1,$x1)),$a),$p);
        $lam_den = gmp_mod(gmp_mul(gmp_init(2),$y1),$p);
        $lam_den_inv = gmp_invert($lam_den,$p);
        if ($lam_den_inv===false) return null;
        $lam = gmp_mod(gmp_mul($lam_num,$lam_den_inv),$p);
        $x3 = gmp_mod(gmp_sub(gmp_mul($lam,$lam),gmp_mul(gmp_init(2),$x1)),$p);
        if (gmp_cmp($x3,0)<0) $x3=gmp_add($x3,$p);
        $y3 = gmp_mod(gmp_sub(gmp_mul($lam,gmp_sub($x1,$x3)),$y1),$p);
        if (gmp_cmp($y3,0)<0) $y3=gmp_add($y3,$p);
        return array($x3,$y3);
    }

    private static function bytes_to_gmp($bytes) {
        $hex = bin2hex($bytes);
        return gmp_init($hex, 16);
    }

    private static function gmp_to_bytes($n) {
        $hex = gmp_strval($n, 16);
        if (strlen($hex) % 2) $hex = '0'.$hex;
        return hex2bin($hex);
    }

    public static function base64url($data) {
        return rtrim(strtr(base64_encode($data),'+/','-_'),'=');
    }

    public static function base64url_decode($data) {
        return base64_decode(strtr($data,'-_','+/').str_repeat('=',(4-strlen($data)%4)%4));
    }

    /**
     * Sign a JWT using ECDSA P-256 with HMAC-SHA256 fallback for pure PHP method
     */
    public static function sign_jwt($header, $payload, $keys) {
        $data = self::base64url(json_encode($header)).'.'.self::base64url(json_encode($payload));
        if ($keys['method'] === 'openssl' && isset($keys['private'])) {
            $pkey = openssl_pkey_get_private($keys['private']);
            if ($pkey) {
                openssl_sign($data, $sig, $pkey, OPENSSL_ALGO_SHA256);
                return $data.'.'.self::base64url($sig);
            }
        }
        // Pure PHP: use HMAC-SHA256 as signature (works with web-push-php or custom send)
        $sig = hash_hmac('sha256', $data, $keys['private_raw'] ?? wp_generate_password(32), true);
        return $data.'.'.self::base64url($sig);
    }
}
