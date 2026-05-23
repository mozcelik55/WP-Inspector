<?php
/**
 * Plugin Name: WP Inspector
 * Plugin URI:  https://github.com/your-repo/wp-inspector
 * Description: A powerful inspection & audit management system for WordPress, similar to iAuditor.
 * Version:     1.4.45.103
 * Author:      Your Name
 * License:     GPL-2.0+
 * Text Domain: wp-inspector
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'WPI_VERSION',     '1.4.45.103' );
define( 'WPI_PLUGIN_DIR',  plugin_dir_path( __FILE__ ) );
define( 'WPI_PLUGIN_URL',  plugin_dir_url( __FILE__ ) );
define( 'WPI_PLUGIN_FILE', __FILE__ );

require_once WPI_PLUGIN_DIR . 'includes/class-activator.php';
require_once WPI_PLUGIN_DIR . 'includes/class-deactivator.php';
require_once WPI_PLUGIN_DIR . 'includes/class-ajax.php';
require_once WPI_PLUGIN_DIR . 'includes/class-admin.php';
require_once WPI_PLUGIN_DIR . 'includes/class-pdf.php';
require_once WPI_PLUGIN_DIR . 'includes/class-pdf-email.php';
require_once WPI_PLUGIN_DIR . 'includes/class-access.php';


// ── Device Login Registration Helpers (PWA/Web/iOS/Android) ─────────────
if ( ! function_exists( 'wpi_device_ensure_columns' ) ) {
function wpi_device_ensure_columns() {
    global $wpdb;
    $table = $wpdb->prefix . 'wpi_sessions';
    if ( $wpdb->get_var( "SHOW TABLES LIKE '$table'" ) !== $table ) return;
    $cols = $wpdb->get_col( "SHOW COLUMNS FROM `$table`", 0 );
    if ( ! is_array( $cols ) ) return;
    $wpdb->hide_errors();
    if ( ! in_array( 'status', $cols, true ) ) {
        $wpdb->query( "ALTER TABLE `$table` ADD status VARCHAR(20) NOT NULL DEFAULT 'active' AFTER ip_address" );
        $wpdb->query( "ALTER TABLE `$table` ADD INDEX status (status)" );
    }
    if ( ! in_array( 'removed_at', $cols, true ) ) $wpdb->query( "ALTER TABLE `$table` ADD removed_at DATETIME NULL AFTER created_at" );
    if ( ! in_array( 'blocked_at', $cols, true ) ) $wpdb->query( "ALTER TABLE `$table` ADD blocked_at DATETIME NULL AFTER removed_at" );
    if ( ! in_array( 'expired_at', $cols, true ) ) $wpdb->query( "ALTER TABLE `$table` ADD expired_at DATETIME NULL AFTER blocked_at" );
    $wpdb->show_errors();
}}

if ( ! function_exists( 'wpi_get_request_device_id' ) ) {
function wpi_get_request_device_id() {
    $raw = $_POST['wpi_device_id'] ?? $_POST['device_id'] ?? $_COOKIE['wpi_device_id'] ?? '';
    $raw = sanitize_text_field( wp_unslash( $raw ) );
    if ( $raw && strlen( $raw ) >= 8 && strlen( $raw ) <= 128 ) return $raw;
    return '';
}}

if ( ! function_exists( 'wpi_get_request_device_info' ) ) {
function wpi_get_request_device_info( $device_id = '' ) {
    $ua  = substr( sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] ?? '' ), 0, 250 );
    $raw = $_POST['wpi_device_info'] ?? $_POST['device_info'] ?? $_COOKIE['wpi_device_info'] ?? '';
    $client = array();
    if ( $raw ) {
        $decoded = json_decode( stripslashes( (string) wp_unslash( $raw ) ), true );
        if ( is_array( $decoded ) ) {
            $client = array(
                'ua'             => isset($decoded['ua']) ? substr( sanitize_text_field($decoded['ua']), 0, 250 ) : '',
                'platform'       => isset($decoded['platform']) ? substr( sanitize_text_field($decoded['platform']), 0, 80 ) : '',
                'os'             => isset($decoded['os']) ? substr( sanitize_text_field($decoded['os']), 0, 40 ) : '',
                'browser'        => isset($decoded['browser']) ? substr( sanitize_text_field($decoded['browser']), 0, 40 ) : '',
                'maxTouchPoints' => isset($decoded['maxTouchPoints']) ? absint($decoded['maxTouchPoints']) : 0,
                'standalone'     => ! empty($decoded['standalone']),
                'displayMode'    => isset($decoded['displayMode']) ? substr( sanitize_text_field($decoded['displayMode']), 0, 30 ) : '',
            );
        }
    }
    return substr( wp_json_encode( array_merge( array( 'server_ua' => $ua ), $client, array( 'did' => $device_id ) ), JSON_UNESCAPED_SLASHES ), 0, 700 );
}}

if ( ! function_exists( 'wpi_get_user_org_id_for_device' ) ) {
function wpi_get_user_org_id_for_device( $uid ) {
    global $wpdb;
    return (int) $wpdb->get_var( $wpdb->prepare( "SELECT org_id FROM {$wpdb->prefix}wpi_org_users WHERE user_id=%d LIMIT 1", (int)$uid ) );
}}

if ( ! function_exists( 'wpi_get_device_limits_for_user' ) ) {
function wpi_get_device_limits_for_user( $uid, $org_id ) {
    global $wpdb;
    $user_max = get_user_meta( (int)$uid, 'wpi_user_max_devices', true );
    if ( $user_max === '' || $user_max === null ) $user_max = (int) get_option( 'wpi_default_user_devices', 1 );
    else $user_max = (int) $user_max;

    $org_max = 0;
    if ( $org_id ) {
        $org_val = $wpdb->get_var( $wpdb->prepare( "SELECT max_sessions FROM {$wpdb->prefix}wpi_organisations WHERE id=%d", (int)$org_id ) );
        if ( $org_val !== null && $org_val !== '' ) $org_max = (int) $org_val;
    }
    if ( ! $org_max ) $org_max = (int) get_option( 'wpi_max_sessions', 5 );
    return array( max(0,$user_max), max(0,$org_max) );
}}

if ( ! function_exists( 'wpi_get_login_session_key' ) ) {
function wpi_get_login_session_key( $uid, $device_id = '' ) {
    if ( $device_id ) return 'dev_' . (int)$uid . '_' . $device_id;
    $ua_hash     = md5( $_SERVER['HTTP_USER_AGENT'] ?? 'unknown' );
    $cookie_hash = md5( $_COOKIE[COOKIEHASH] ?? $_COOKIE[LOGGED_IN_COOKIE] ?? 'login' );
    return 'fallback_' . (int)$uid . '_' . md5( $cookie_hash . $ua_hash );
}}

if ( ! function_exists( 'wpi_device_allowed_for_login' ) ) {
function wpi_device_allowed_for_login( $uid, $org_id, $session_key ) {
    global $wpdb;
    wpi_device_ensure_columns();
    $table = $wpdb->prefix . 'wpi_sessions';
    if ( $wpdb->get_var( "SHOW TABLES LIKE '$table'" ) !== $table ) return true;

    $existing = $wpdb->get_row( $wpdb->prepare( "SELECT status FROM `$table` WHERE session_key=%s LIMIT 1", $session_key ) );
    if ( $existing && $existing->status === 'blocked' ) return false;
    if ( $existing && $existing->status === 'active' ) return true; // same PWA/device returning

    list( $user_max, $org_max ) = wpi_get_device_limits_for_user( $uid, $org_id );
    $cutoff = date( 'Y-m-d H:i:s', time() - 15 * 60 );
    if ( $user_max > 0 ) {
        $user_active = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT session_key) FROM `$table` WHERE user_id=%d AND status='active' AND last_active >= %s", (int)$uid, $cutoff ) );
        if ( $user_active >= $user_max ) return false;
    }
    if ( $org_id && $org_max > 0 ) {
        $org_active = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT session_key) FROM `$table` WHERE org_id=%d AND status='active' AND last_active >= %s", (int)$org_id, $cutoff ) );
        if ( $org_active >= $org_max ) return false;
    }
    return true;
}}

if ( ! function_exists( 'wpi_register_login_device' ) ) {
function wpi_register_login_device( $uid ) {
    global $wpdb;
    wpi_device_ensure_columns();
    $table = $wpdb->prefix . 'wpi_sessions';
    if ( $wpdb->get_var( "SHOW TABLES LIKE '$table'" ) !== $table ) return;
    $org_id     = wpi_get_user_org_id_for_device( $uid );
    $device_id  = wpi_get_request_device_id();
    $session_key= wpi_get_login_session_key( $uid, $device_id );
    if ( ! wpi_device_allowed_for_login( $uid, $org_id, $session_key ) ) return;
    $device     = wpi_get_request_device_info( $device_id );
    $ip         = sanitize_text_field( $_SERVER['REMOTE_ADDR'] ?? '' );
    $now        = current_time( 'mysql' );
    $wpdb->query( $wpdb->prepare(
        "INSERT INTO `$table` (user_id, org_id, session_key, device_info, ip_address, status, last_active, created_at)
         VALUES (%d,%d,%s,%s,%s,'active',%s,%s)
         ON DUPLICATE KEY UPDATE status='active', removed_at=NULL, expired_at=NULL, last_active=%s, device_info=%s, ip_address=%s",
        (int)$uid, (int)$org_id, $session_key, $device, $ip, $now, $now, $now, $device, $ip
    ) );
}}

register_activation_hook( __FILE__, array( 'WPI_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'WPI_Deactivator', 'deactivate' ) );


// ── Audit4me Access ──────────────────────────────────────────────────────
add_action( 'wp_login', function( $user_login, $user ) {
    WPI_Access::check_login_access( $user );
}, 10, 2 );

add_action( 'wpi_access_expiry_cron', function() {
    WPI_Access::run_expiry_cron();
} );

add_action( 'init', function() {
    if ( ! wp_next_scheduled( 'wpi_access_expiry_cron' ) ) {
        $tz   = wp_timezone();
        $next = new DateTime( 'today 00:01', $tz );
        if ( $next->getTimestamp() < time() ) $next->modify( '+1 day' );
        wp_schedule_event( $next->getTimestamp(), 'wpi_daily', 'wpi_access_expiry_cron' );
    }
    global $wpdb;
    $table = $wpdb->prefix . 'wpi_licences';
    if ( $wpdb->get_var("SHOW TABLES LIKE '$table'") !== $table ) {
        require_once WPI_PLUGIN_DIR . 'includes/class-activator.php';
        WPI_Activator::activate();
    } else {
        $wpdb->hide_errors();
        $wpdb->query("ALTER TABLE `{$table}` ADD COLUMN `user_id` BIGINT(20) UNSIGNED NOT NULL DEFAULT 0");
        $wpdb->query("ALTER TABLE `{$table}` ADD COLUMN `assigned_to` VARCHAR(10) NOT NULL DEFAULT 'none'");
        $wpdb->show_errors();
    }
    // Ensure seats table exists
    $seats_table = $wpdb->prefix . 'wpi_licence_seats';
    if ( $wpdb->get_var("SHOW TABLES LIKE '$seats_table'") !== $seats_table ) {
        require_once WPI_PLUGIN_DIR . 'includes/class-activator.php';
        WPI_Activator::activate();
    }
    // Performance migration: add indexes used by template list, builder saves and PDF/report lookups.
    // Runs once only. This avoids slow ORDER BY updated_at / template_id scans after the recent section-logic updates.
    if ( get_option( 'wpi_perf_indexes_141' ) !== 'done' ) {
        $wpdb->hide_errors();
        $wpi_add_index = function( $table, $index_name, $definition ) use ( $wpdb ) {
            $exists = $wpdb->get_var( $wpdb->prepare(
                "SHOW INDEX FROM `$table` WHERE Key_name = %s LIMIT 1",
                $index_name
            ) );
            if ( ! $exists ) {
                $wpdb->query( "ALTER TABLE `$table` ADD INDEX `$index_name` ($definition)" );
            }
        };
        $wpi_add_index( $wpdb->prefix . 'wpi_templates', 'idx_status_updated', 'status, updated_at' );
        $wpi_add_index( $wpdb->prefix . 'wpi_templates', 'idx_org_status_updated', 'org_id, status, updated_at' );
        $wpi_add_index( $wpdb->prefix . 'wpi_questions', 'idx_template_sort', 'template_id, sort_order' );
        $wpi_add_index( $wpdb->prefix . 'wpi_responses', 'idx_inspection_question', 'inspection_id, question_id' );
        $wpi_add_index( $wpdb->prefix . 'wpi_inspections', 'idx_template_conducted', 'template_id, conducted_at' );
        $wpi_add_index( $wpdb->prefix . 'wpi_template_shares', 'idx_share_lookup', 'template_id, shared_with_type, shared_with_id' );
        update_option( 'wpi_perf_indexes_141', 'done', false );
        $wpdb->show_errors();
    }
}, 1 );

// ── Block deactivated users from logging in ───────────────────────────
add_filter( 'wp_authenticate_user', function( $user, $password ) {
    if ( is_wp_error( $user ) ) return $user;

    // Brute force protection: lock account after 10 failed attempts per IP per 15 minutes
    $ip       = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $lock_key = 'wpi_login_fail_' . md5( $ip );
    $fails    = (int) get_transient( $lock_key );
    if ( $fails >= 10 ) {
        return new WP_Error( 'wpi_locked', 'Too many failed login attempts. Please try again in 15 minutes.' );
    }

    if ( get_user_meta( $user->ID, 'wpi_deactivated', true ) ) {
        return new WP_Error( 'wpi_deactivated', 'This account has been deactivated. Please contact your administrator.' );
    }

    // Enforce System Admin device limits during login, including PWA logins.
    if ( function_exists( 'wpi_get_login_session_key' ) && function_exists( 'wpi_device_allowed_for_login' ) ) {
        $device_id   = wpi_get_request_device_id();
        $org_id      = wpi_get_user_org_id_for_device( $user->ID );
        $session_key = wpi_get_login_session_key( $user->ID, $device_id );
        if ( ! wpi_device_allowed_for_login( $user->ID, $org_id, $session_key ) ) {
            return new WP_Error( 'wpi_device_limit', 'Device limit reached. Your account is not blocked. Please use desktop web to remove an old device or contact the System Owner.' );
        }
    }
    return $user;
}, 10, 2 );

// Increment fail counter on bad login
add_action( 'wp_login_failed', function( $username ) {
    $ip       = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $lock_key = 'wpi_login_fail_' . md5( $ip );
    $fails    = (int) get_transient( $lock_key );
    set_transient( $lock_key, $fails + 1, 15 * MINUTE_IN_SECONDS );
} );

// Clear fail counter on successful login
add_action( 'wp_login', function( $user_login, $user ) {
    $ip       = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $lock_key = 'wpi_login_fail_' . md5( $ip );
    delete_transient( $lock_key );
    // Force 30-day auth cookie so PWA stays logged in
    wp_set_auth_cookie( $user->ID, true );

    // Register this Web/PWA/mobile device immediately on successful login.
    // This fixes PWA logins not appearing until a later background ping.
    if ( function_exists( 'wpi_register_login_device' ) ) {
        wpi_register_login_device( $user->ID );
    }

    // Record login time in wpi_user_roles.last_seen so Roles/Users pages
    // show accurate activity immediately (not just after first ping)
    global $wpdb;
    $now = current_time( 'mysql' );
    $wpdb->query( $wpdb->prepare(
        "INSERT INTO `{$wpdb->prefix}wpi_user_roles` (user_id, role, set_by, last_seen)
          VALUES (%d, 'standard', 0, %s)
          ON DUPLICATE KEY UPDATE last_seen = %s",
        $user->ID, $now, $now
    ) );
}, 10, 2 );

add_action( 'plugins_loaded', 'wpi_init' );

/**
 * WPI early login handler - fires at priority 1, before Loginizer (priority 10).
 * Processes /?wpi=1&wpi_action=auth POST requests and exits before any
 * security plugin middleware can intercept the response.
 */
add_action( 'init', function() {
    if (
        empty( $_GET['wpi'] ) ||
        ( $_GET['wpi_action'] ?? '' ) !== 'login' ||
        $_SERVER['REQUEST_METHOD'] !== 'POST'
    ) return;

    // Clean any output Loginizer or other plugins may have buffered
    while ( ob_get_level() > 0 ) { ob_end_clean(); }
    ob_start();
    if ( ! headers_sent() ) {
        header( 'Content-Type: application/json; charset=utf-8' );
        header( 'Cache-Control: no-store, no-cache, must-revalidate' );
        header( 'X-Content-Type-Options: nosniff' );
        header( 'X-Frame-Options: DENY' );
    }

    // CORS - same origin only
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    $home   = home_url();
    if ( $origin && strpos( $home, $origin ) !== 0 && strpos( $origin, $home ) !== 0 ) {
        http_response_code( 403 );
        ob_end_clean();
        echo json_encode( [ 'success' => false, 'data' => [ 'message' => 'Origin not allowed.' ] ] );
        exit;
    }

    // Nonce - wp_verify_nonce is available after plugins_loaded
    $nonce = sanitize_text_field( $_POST['nonce'] ?? '' );
    if ( ! $nonce || ! wp_verify_nonce( $nonce, 'wpi_login' ) ) {
        http_response_code( 403 );
        ob_end_clean();
        echo json_encode( [ 'success' => false, 'data' => [ 'message' => 'Security check failed. Please refresh the page.' ] ] );
        exit;
    }

    $log = sanitize_text_field( wp_unslash( $_POST['wpi_u'] ?? '' ) );
    $pwd = wp_unslash( $_POST['wpi_k'] ?? '' );

    if ( ! $log || ! $pwd ) {
        ob_end_clean();
        echo json_encode( [ 'success' => false, 'data' => [ 'message' => 'Please enter your username and password.' ] ] );
        exit;
    }

    // Dual rate limit: per IP and per username
    $ip           = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $rate_key_ip  = 'wpi_login_ip_'  . md5( $ip );
    $rate_key_usr = 'wpi_login_usr_' . md5( strtolower( $log ) );
    if ( (int) get_transient( $rate_key_ip ) >= 10 || (int) get_transient( $rate_key_usr ) >= 20 ) {
        http_response_code( 429 );
        ob_end_clean();
        echo json_encode( [ 'success' => false, 'data' => [ 'message' => 'Too many login attempts. Please wait a few minutes.' ] ] );
        exit;
    }
    set_transient( $rate_key_ip,  (int) get_transient( $rate_key_ip )  + 1, 5 * MINUTE_IN_SECONDS );
    set_transient( $rate_key_usr, (int) get_transient( $rate_key_usr ) + 1, 5 * MINUTE_IN_SECONDS );

    // Resolve email to username
    $username = $log;
    if ( is_email( $log ) ) {
        $u = get_user_by( 'email', $log );
        if ( $u ) $username = $u->user_login;
    }

    // Use wp_signon() - sets auth cookies properly
    $user = wp_signon( [
        'user_login'    => $username,
        'user_password' => $pwd,
        'remember'      => ! empty( $_POST['rememberme'] ),
    ], is_ssl() );

    if ( is_wp_error( $user ) ) {
        // Generic message - never reveal whether username or password was wrong
        ob_end_clean();
        echo json_encode( [ 'success' => false, 'data' => [ 'message' => 'Incorrect username or password.' ] ] );
        exit;
    }

    delete_transient( $rate_key_ip );
    delete_transient( $rate_key_usr );
    ob_end_clean();
    echo json_encode( [ 'success' => true, 'data' => [ 'redirect' => home_url( '/?wpi=1' ) ] ] );
    exit;

}, 1 ); // Priority 1 on init = before most plugins but after WP core loads


// ── Trial expiry cron ─────────────────────────────────────────────
add_action( 'wpi_check_trial_expiry', 'wpi_expire_trials' );
function wpi_expire_trials() {
    global $wpdb;
    require_once WPI_PLUGIN_DIR . 'includes/class-billing.php';

    // Find trialing subscriptions that have expired
    $expired = $wpdb->get_results(
        "SELECT * FROM {$wpdb->prefix}wpi_subscriptions
         WHERE status = 'trialing' AND is_trial = 1
         AND trial_ends_at IS NOT NULL AND trial_ends_at < NOW()"
    );

    foreach ( $expired as $sub ) {
        $wpdb->update(
            $wpdb->prefix . 'wpi_subscriptions',
            array( 'status' => 'canceled', 'updated_at' => current_time('mysql') ),
            array( 'id' => $sub->id )
        );
        if ( $sub->org_id ) {
            WPI_Billing::lock_org_team( (int)$sub->org_id );
        }
    }
}

// Schedule cron if not already scheduled
if ( ! wp_next_scheduled( 'wpi_check_trial_expiry' ) ) {
    wp_schedule_event( time(), 'hourly', 'wpi_check_trial_expiry' );
}

// Stripe webhook endpoint: POST /?wpi_stripe_webhook=1
add_action( 'init', function() {
    if ( ! empty( $_GET['wpi_stripe_webhook'] ) && $_SERVER['REQUEST_METHOD'] === 'POST' ) {
        require_once WPI_PLUGIN_DIR . 'includes/class-billing.php';
        WPI_Billing::handle_webhook();
    }
    // Invitation accept link: /?wpi=1&wpi_invite=TOKEN
    // Store token in a transient keyed to session so the SPA can pick it up
    if ( ! empty( $_GET['wpi_invite'] ) && ! empty( $_GET['wpi'] ) ) {
        $token = sanitize_text_field( $_GET['wpi_invite'] );
        if ( is_user_logged_in() ) {
            set_transient( 'wpi_pending_invite_' . get_current_user_id(), $token, 600 );
        } else {
            // Store in cookie for post-login pickup
            setcookie( 'wpi_pending_invite', $token, time() + 600, '/', '', is_ssl(), true );
        }
    }
} );

// Ensure sw.js is served with correct headers so browsers and crawlers
// recognise it as a valid service worker with root scope.
add_action( 'send_headers', function() {
    if ( isset( $_SERVER['REQUEST_URI'] ) ) {
        $uri = $_SERVER['REQUEST_URI'];
        if ( strpos( $uri, 'wp-inspector/sw.js' ) !== false ) {
            header( 'Service-Worker-Allowed: /' );
            header( 'Cache-Control: no-cache, no-store, must-revalidate' );
            header( 'Content-Type: application/javascript; charset=UTF-8' );
        }
    }
} );

// Add /sw.js rewrite to root .htaccess for iOS PWA push notification scope
add_action( 'init', function() {
    $htaccess = ABSPATH . '.htaccess';
    if ( ! file_exists( $htaccess ) || ! is_writable( $htaccess ) ) return;
    $content = file_get_contents( $htaccess );
    $marker_start = '# BEGIN WPI SW';
    $marker_end   = '# END WPI SW';
    $sw_path = str_replace( ABSPATH, '/', WPI_PLUGIN_DIR . 'sw.js' );
    $rule = "\n$marker_start\n<IfModule mod_rewrite.c>\nRewriteEngine On\nRewriteRule ^sw\\.js$ {$sw_path} [L]\n</IfModule>\n<IfModule mod_headers.c>\n<FilesMatch \"sw\\.js$\">\nHeader set Service-Worker-Allowed \"/\"\nHeader set Cache-Control \"no-cache, no-store, must-revalidate\"\nHeader set Content-Type \"application/javascript; charset=UTF-8\"\n</FilesMatch>\n</IfModule>\n$marker_end\n";
    // Remove old block if exists
    $content = preg_replace( '/' . preg_quote($marker_start,'/').'.*?'.preg_quote($marker_end,'/').'[\r\n]*/s', '', $content );
    // Add new block before # BEGIN WordPress
    $content = str_replace( '# BEGIN WordPress', $rule . '# BEGIN WordPress', $content );
    file_put_contents( $htaccess, $content );
} );

// Extend session to 30 days — keeps PWA users logged in longer
add_filter( 'auth_cookie_expiration', function( $expiration, $user_id, $remember ) {
    return 30 * DAY_IN_SECONDS; // 30 days regardless of remember me
}, 10, 3 );

function wpi_init() {
    $ajax  = new WPI_Ajax();
    $admin = new WPI_Admin();
    $ajax->init();
    $admin->init();

    // ── Boot REST API + API-key auth ──────────────────────────
    require_once WPI_PLUGIN_DIR . 'includes/class-api.php';
    require_once WPI_PLUGIN_DIR . 'includes/class-billing.php';
    $api = new WPI_API();
    $api->register_routes();

    // Authenticate REST requests using X-Api-Key header or ?api_key= param
    add_filter( 'rest_authentication_errors', function( $result ) {
        if ( ! empty( $result ) ) return $result; // already authed or errored
        $request_uri = $_SERVER['REQUEST_URI'] ?? '';
        if ( strpos( $request_uri, '/wp_inspector/v1/' ) === false &&
             strpos( $request_uri, '/wp-inspector/v1/' ) === false ) return $result;

        $raw = '';
        if ( ! empty( $_SERVER['HTTP_X_API_KEY'] ) ) {
            $raw = sanitize_text_field( $_SERVER['HTTP_X_API_KEY'] );
        } elseif ( ! empty( $_GET['api_key'] ) ) {
            $raw = sanitize_text_field( $_GET['api_key'] );
        }
        if ( ! $raw ) return $result;

        // Must start with wpi_
        if ( strpos( $raw, 'wpi_' ) !== 0 ) return $result;

        global $wpdb;
        $hash = hash( 'sha256', $raw );
        $key_row = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wpi_api_keys WHERE key_hash = %s", $hash
        ) );
        if ( ! $key_row ) {
            return new WP_Error( 'rest_forbidden', 'Invalid API key.', array( 'status' => 401 ) );
        }
        if ( ! (int)$key_row->is_active ) {
            return new WP_Error( 'rest_forbidden', 'This API key has been disabled.', array( 'status' => 403 ) );
        }
        // Log last used
        $wpdb->update( $wpdb->prefix . 'wpi_api_keys',
            array( 'last_used_at' => current_time('mysql') ),
            array( 'id' => $key_row->id )
        );
        // Set current user to the key owner
        wp_set_current_user( (int)$key_row->created_by );
        // Store org_id for REST handlers to scope queries
        if ( $key_row->org_id ) {
            global $_wpi_api_key_org_id;
            $_wpi_api_key_org_id = (int)$key_row->org_id;
        }
        return true;
    }, 20 );
    wpi_maybe_migrate();
    wpi_ensure_org_licence_columns();
    wpi_ensure_critical_tables();

    // Show admin notice if wpi_actions table still missing after creation attempt
    add_action( 'admin_notices', function() {
        global $wpdb;
        if ( ! current_user_can('manage_options') ) return;
        if ( $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}wpi_actions'" ) ) return;
        echo '<div class="notice notice-error"><p><strong>Audit4me:</strong> The <code>wpi_actions</code> table could not be created. DB error: ' . esc_html( $wpdb->last_error ?: 'unknown' ) . '</p></div>';
    } );
}

/**
 * Ensure critical tables exist — runs on every plugin load.
 * Uses a daily transient for performance but always checks on first load.
 */
function wpi_ensure_critical_tables() {
    global $wpdb;
    $key = 'wpi_tables_' . md5( WPI_VERSION . '_r6' ); // bump suffix when schema changes
    if ( get_transient( $key ) ) return;

    $charset = 'DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci';

    // Add last_seen column to wpi_user_roles if missing
    $cols = $wpdb->get_col( "SHOW COLUMNS FROM `{$wpdb->prefix}wpi_user_roles`", 0 );
    if ( $cols && ! in_array( 'last_seen', $cols ) ) {
        $wpdb->query( "ALTER TABLE `{$wpdb->prefix}wpi_user_roles` ADD COLUMN `last_seen` DATETIME DEFAULT NULL" );
    }

    // Add notes column to wpi_responses if missing
    $resp_cols = $wpdb->get_col( "SHOW COLUMNS FROM `{$wpdb->prefix}wpi_responses`", 0 );
    if ( $resp_cols && ! in_array( 'notes', $resp_cols ) ) {
        $wpdb->query( "ALTER TABLE `{$wpdb->prefix}wpi_responses` ADD COLUMN `notes` TEXT DEFAULT NULL" );
    }

    // Add max_sessions to organisations if missing
    $org_cols = $wpdb->get_col( "SHOW COLUMNS FROM `{$wpdb->prefix}wpi_organisations`", 0 );
    if ( $org_cols && ! in_array( 'max_sessions', $org_cols ) ) {
        $wpdb->query( "ALTER TABLE `{$wpdb->prefix}wpi_organisations` ADD COLUMN `max_sessions` INT(11) DEFAULT NULL COMMENT 'NULL = use system default'" );
    }

    // Add photos and notified columns to wpi_actions if missing
    // Add columns to wpi_actions — suppress duplicate column errors so it's safe to run every boot
    $wpdb->hide_errors();
    foreach ( array(
        "ALTER TABLE `{$wpdb->prefix}wpi_actions` ADD COLUMN `photos`           LONGTEXT        DEFAULT NULL",
        "ALTER TABLE `{$wpdb->prefix}wpi_actions` ADD COLUMN `notified`         TINYINT(1)      NOT NULL DEFAULT 0",
        "ALTER TABLE `{$wpdb->prefix}wpi_actions` ADD COLUMN `question_answer`  VARCHAR(500)    NOT NULL DEFAULT ''",
        "ALTER TABLE `{$wpdb->prefix}wpi_actions` ADD COLUMN `question_note`    TEXT            DEFAULT NULL",
        "ALTER TABLE `{$wpdb->prefix}wpi_actions` ADD COLUMN `question_section` VARCHAR(255)    NOT NULL DEFAULT ''",
        "ALTER TABLE `{$wpdb->prefix}wpi_template_shares` ADD COLUMN `inspection_visibility` VARCHAR(20) NOT NULL DEFAULT 'all'",
        "ALTER TABLE `{$wpdb->prefix}wpi_template_shares` ADD COLUMN `shared_at` DATETIME DEFAULT NULL",
    ) as $alter_sql ) {
        $wpdb->query( $alter_sql ); // silently fails if column already exists
    }
    $wpdb->show_errors();

    // ── One-time migration: back-fill question_answer/note/section on old action rows ──
    // Runs only once, tracked via wp_option. Safe to leave in forever.
    if ( ! get_option('wpi_actions_backfill_v1') ) {
        $old_actions = $wpdb->get_results(
            "SELECT a.id, a.inspection_id, a.question_id
             FROM `{$wpdb->prefix}wpi_actions` a
             WHERE a.question_id != ''
               AND (a.question_answer = '' OR a.question_answer IS NULL
                    OR a.question_note IS NULL
                    OR a.question_section = '' OR a.question_section IS NULL)
             LIMIT 500"
        );
        foreach ( $old_actions as $a ) {
            $update = array();
            // Back-fill answer + note from wpi_responses
            $resp = $wpdb->get_row( $wpdb->prepare(
                "SELECT value, notes FROM `{$wpdb->prefix}wpi_responses`
                 WHERE inspection_id=%d AND question_id=%s LIMIT 1",
                (int)$a->inspection_id, $a->question_id
            ) );
            if ( $resp ) {
                if ( $resp->value ) $update['question_answer'] = $resp->value;
                if ( $resp->notes ) $update['question_note']   = $resp->notes;
            }
            // Back-fill section from wpi_questions
            if ( is_numeric($a->question_id) ) {
                $sec = $wpdb->get_var( $wpdb->prepare(
                    "SELECT section FROM `{$wpdb->prefix}wpi_questions` WHERE id=%d LIMIT 1",
                    (int)$a->question_id
                ) );
                if ( $sec ) $update['question_section'] = $sec;
            }
            if ( ! empty($update) ) {
                $wpdb->update( $wpdb->prefix . 'wpi_actions', $update, array( 'id' => $a->id ) );
            }
        }
        update_option( 'wpi_actions_backfill_v1', 1, false );
    }

    // Create wpi_sessions table for device/session tracking
    $wpdb->query( "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}wpi_sessions` (
        `id`          BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        `user_id`     BIGINT(20) UNSIGNED NOT NULL,
        `org_id`      BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
        `session_key` VARCHAR(64) NOT NULL,
        `device_info` VARCHAR(255) DEFAULT '',
        `ip_address`  VARCHAR(45) DEFAULT '',
        `last_active` DATETIME NOT NULL,
        `created_at`  DATETIME NOT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `session_key` (`session_key`),
        KEY `user_id` (`user_id`),
        KEY `org_id` (`org_id`),
        KEY `last_active` (`last_active`)
    ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" );

    // ── wpi_plans — subscription plan definitions ────────────
    $wpdb->query( "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}wpi_plans` (
        `id`                     INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
        `name`                   VARCHAR(100) NOT NULL DEFAULT '',
        `slug`                   VARCHAR(50)  NOT NULL DEFAULT '',
        `description`            TEXT,
        `is_active`              TINYINT(1) NOT NULL DEFAULT 1,
        `is_free`                TINYINT(1) NOT NULL DEFAULT 0,
        `sort_order`             INT(11) NOT NULL DEFAULT 0,
        `monthly_price_cents`    INT(11) NOT NULL DEFAULT 0,
        `annual_price_cents`     INT(11) NOT NULL DEFAULT 0,
        `currency`               VARCHAR(3)  NOT NULL DEFAULT 'AUD',
        `stripe_monthly_price_id` VARCHAR(100) NOT NULL DEFAULT '',
        `stripe_annual_price_id`  VARCHAR(100) NOT NULL DEFAULT '',
        `stripe_trial_price_id`   VARCHAR(100) NOT NULL DEFAULT '',
        `is_trial`               TINYINT(1) NOT NULL DEFAULT 0,
        `trial_days`             INT(11) NOT NULL DEFAULT 7,
        `limits`                 TEXT COMMENT 'JSON: max_users, max_templates, max_inspections, max_sites, api_access, scheduler, custom_branding',
        `features`               TEXT COMMENT 'JSON array of feature strings for display',
        `created_at`             DATETIME NOT NULL,
        `updated_at`             DATETIME NOT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `slug` (`slug`)
    ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" );

    // Self-heal: add trial columns if missing (existing installs)
    $plan_cols = $wpdb->get_col( "SHOW COLUMNS FROM `{$wpdb->prefix}wpi_plans`", 0 );
    if ( $plan_cols && ! in_array( 'is_trial', $plan_cols ) ) {
        $wpdb->query( "ALTER TABLE `{$wpdb->prefix}wpi_plans`
            ADD COLUMN `stripe_trial_price_id` VARCHAR(100) NOT NULL DEFAULT '' AFTER `stripe_annual_price_id`,
            ADD COLUMN `is_trial` TINYINT(1) NOT NULL DEFAULT 0 AFTER `stripe_trial_price_id`,
            ADD COLUMN `trial_days` INT(11) NOT NULL DEFAULT 7 AFTER `is_trial`" );
    }

    // ── wpi_subscriptions — org subscription records ──────────
    $wpdb->query( "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}wpi_subscriptions` (
        `id`                     INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
        `org_id`                 BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
        `plan_id`                INT(11) UNSIGNED DEFAULT NULL,
        `status`                 VARCHAR(30) NOT NULL DEFAULT 'free',
        `stripe_customer_id`     VARCHAR(100) NOT NULL DEFAULT '',
        `stripe_subscription_id` VARCHAR(100) NOT NULL DEFAULT '',
        `current_period_end`     DATETIME DEFAULT NULL,
        `billing_cycle`          VARCHAR(20) NOT NULL DEFAULT '',
        `cancel_at_period_end`   TINYINT(1) NOT NULL DEFAULT 0,
        `trial_ends_at`          DATETIME DEFAULT NULL,
        `is_trial`               TINYINT(1) NOT NULL DEFAULT 0,
        `created_at`             DATETIME NOT NULL,
        `updated_at`             DATETIME NOT NULL,
        PRIMARY KEY (`id`),
        KEY `org_id` (`org_id`),
        KEY `stripe_customer_id` (`stripe_customer_id`),
        KEY `stripe_subscription_id` (`stripe_subscription_id`)
    ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" );

    // Seed default plans if none exist
    $plan_count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$wpdb->prefix}wpi_plans`" );
    if ( $plan_count === 0 ) {
        $now = current_time('mysql');
        $default_plans = array(
            array(
                'name'                => 'Free',
                'slug'                => 'free',
                'description'         => 'Get started with the basics',
                'is_active'           => 1,
                'is_free'             => 1,
                'sort_order'          => 0,
                'monthly_price_cents' => 0,
                'annual_price_cents'  => 0,
                'currency'            => 'AUD',
                'stripe_monthly_price_id' => '',
                'stripe_annual_price_id'  => '',
                'limits'              => json_encode( array(
                    'max_users' => 1, 'max_templates' => 3,
                    'max_inspections' => 10, 'max_sites' => 1,
                    'api_access' => false, 'scheduler' => false, 'custom_branding' => false,
                ) ),
                'features' => json_encode( array( '1 user', '3 templates', '10 inspections/month', '1 site' ) ),
                'created_at' => $now, 'updated_at' => $now,
            ),
            array(
                'name'                => 'Starter',
                'slug'                => 'starter',
                'description'         => 'Perfect for small teams',
                'is_active'           => 1,
                'is_free'             => 0,
                'sort_order'          => 1,
                'monthly_price_cents' => 2900,
                'annual_price_cents'  => 29000,
                'currency'            => 'AUD',
                'stripe_monthly_price_id' => '',
                'stripe_annual_price_id'  => '',
                'limits'              => json_encode( array(
                    'max_users' => 5, 'max_templates' => -1,
                    'max_inspections' => -1, 'max_sites' => 5,
                    'api_access' => false, 'scheduler' => true, 'custom_branding' => false,
                ) ),
                'features' => json_encode( array( '5 users', 'Unlimited templates', 'Unlimited inspections', '5 sites', 'Scheduled inspections' ) ),
                'created_at' => $now, 'updated_at' => $now,
            ),
            array(
                'name'                => 'Pro',
                'slug'                => 'pro',
                'description'         => 'For growing organisations',
                'is_active'           => 1,
                'is_free'             => 0,
                'sort_order'          => 2,
                'monthly_price_cents' => 7900,
                'annual_price_cents'  => 79000,
                'currency'            => 'AUD',
                'stripe_monthly_price_id' => '',
                'stripe_annual_price_id'  => '',
                'limits'              => json_encode( array(
                    'max_users' => 20, 'max_templates' => -1,
                    'max_inspections' => -1, 'max_sites' => -1,
                    'api_access' => true, 'scheduler' => true, 'custom_branding' => true,
                ) ),
                'features' => json_encode( array( '20 users', 'Unlimited everything', 'API access', 'Scheduled inspections', 'Custom branding', 'Priority support' ) ),
                'created_at' => $now, 'updated_at' => $now,
            ),
            array(
                'name'                => 'Enterprise',
                'slug'                => 'enterprise',
                'description'         => 'Unlimited scale for large teams',
                'is_active'           => 1,
                'is_free'             => 0,
                'sort_order'          => 3,
                'monthly_price_cents' => 19900,
                'annual_price_cents'  => 199000,
                'currency'            => 'AUD',
                'stripe_monthly_price_id' => '',
                'stripe_annual_price_id'  => '',
                'limits'              => json_encode( array(
                    'max_users' => -1, 'max_templates' => -1,
                    'max_inspections' => -1, 'max_sites' => -1,
                    'api_access' => true, 'scheduler' => true, 'custom_branding' => true,
                ) ),
                'features' => json_encode( array( 'Unlimited users', 'Unlimited everything', 'API access', 'Multiple organisations', 'Custom branding', 'Dedicated support' ) ),
                'created_at' => $now, 'updated_at' => $now,
            ),
        );
        foreach ( $default_plans as $plan ) {
            $wpdb->insert( $wpdb->prefix . 'wpi_plans', $plan );
        }
    }

    // Self-heal: add is_trial to wpi_subscriptions if missing
    if ( $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}wpi_subscriptions'" ) ) {
        $sub_cols2 = $wpdb->get_col( "SHOW COLUMNS FROM `{$wpdb->prefix}wpi_subscriptions`", 0 );
        if ( $sub_cols2 && ! in_array( 'is_trial', $sub_cols2 ) ) {
            $wpdb->query( "ALTER TABLE `{$wpdb->prefix}wpi_subscriptions` ADD COLUMN `is_trial` TINYINT(1) NOT NULL DEFAULT 0 AFTER `trial_ends_at`" );
        }
    }

    // ── wpi_org_seats — purchased user seats ─────────────────
    $wpdb->query( "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}wpi_org_seats` (
        `id`                     INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
        `org_id`                 BIGINT(20) UNSIGNED NOT NULL,
        `purchased_by`           BIGINT(20) UNSIGNED NOT NULL,
        `seats_total`            INT(11) NOT NULL DEFAULT 0,
        `pending_seats_total`    INT(11) DEFAULT NULL,
        `seats_used`             INT(11) NOT NULL DEFAULT 0,
        `price_per_seat_cents`   INT(11) NOT NULL DEFAULT 0,
        `currency`               VARCHAR(3) NOT NULL DEFAULT 'AUD',
        `stripe_payment_id`      VARCHAR(100) NOT NULL DEFAULT '',
        `stripe_subscription_id` VARCHAR(100) NOT NULL DEFAULT '',
        `stripe_customer_id`     VARCHAR(100) NOT NULL DEFAULT '',
        `status`                 VARCHAR(20) NOT NULL DEFAULT 'active',
        `billing_cycle`          VARCHAR(20) NOT NULL DEFAULT 'monthly',
        `renewal_date`           DATETIME DEFAULT NULL,
        `notes`                  TEXT,
        `created_at`             DATETIME NOT NULL,
        `updated_at`             DATETIME NOT NULL,
        PRIMARY KEY (`id`),
        KEY `org_id` (`org_id`)
    ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" );

    // ── wpi_seat_assignments — which user is on which seat ────
    $wpdb->query( "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}wpi_seat_assignments` (
        `id`          INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
        `org_id`      BIGINT(20) UNSIGNED NOT NULL,
        `seat_id`     INT(11) UNSIGNED NOT NULL,
        `user_id`     BIGINT(20) UNSIGNED DEFAULT NULL,
        `email`       VARCHAR(200) NOT NULL DEFAULT '',
        `assigned_by` BIGINT(20) UNSIGNED NOT NULL,
        `status`      VARCHAR(20) NOT NULL DEFAULT 'assigned',
        `created_at`  DATETIME NOT NULL,
        PRIMARY KEY (`id`),
        KEY `org_id` (`org_id`),
        KEY `seat_id` (`seat_id`),
        KEY `user_id` (`user_id`)
    ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" );

    // ── wpi_invitations — org membership invitations ─────────
    $wpdb->query( "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}wpi_invitations` (
        `id`          INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
        `org_id`      BIGINT(20) UNSIGNED NOT NULL,
        `invited_by`  BIGINT(20) UNSIGNED NOT NULL,
        `email`       VARCHAR(200) NOT NULL DEFAULT '',
        `role`        VARCHAR(50)  NOT NULL DEFAULT 'standard',
        `token`       VARCHAR(64)  NOT NULL DEFAULT '',
        `status`      VARCHAR(20)  NOT NULL DEFAULT 'pending',
        `message`     TEXT,
        `expires_at`  DATETIME NOT NULL,
        `accepted_at` DATETIME DEFAULT NULL,
        `created_at`  DATETIME NOT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `token` (`token`),
        KEY `email` (`email`),
        KEY `org_id` (`org_id`)
    ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" );

    // ── wpi_api_keys — org-scoped REST API keys ──────────────
    $wpdb->query( "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}wpi_api_keys` (
        `id`           BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        `org_id`       BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
        `created_by`   BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
        `label`        VARCHAR(100) NOT NULL DEFAULT '',
        `description`  VARCHAR(255) NOT NULL DEFAULT '',
        `key_prefix`   VARCHAR(12)  NOT NULL DEFAULT '',
        `key_hash`     VARCHAR(64)  NOT NULL DEFAULT '',
        `scopes`       VARCHAR(255) NOT NULL DEFAULT 'read',
        `is_active`    TINYINT(1)   NOT NULL DEFAULT 1,
        `last_used_at` DATETIME DEFAULT NULL,
        `created_at`   DATETIME NOT NULL,
        PRIMARY KEY (`id`),
        KEY `org_id` (`org_id`),
        KEY `key_hash` (`key_hash`)
    ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" );

    // Add parent_id to wpi_sites for hierarchical sub-sites
    $site_cols = $wpdb->get_results( "SHOW COLUMNS FROM `{$wpdb->prefix}wpi_sites`" );
    if ( $site_cols ) {
        $site_col_names = wp_list_pluck( $site_cols, 'Field' );
        if ( ! in_array( 'parent_id', $site_col_names, true ) ) {
            $wpdb->query( "ALTER TABLE `{$wpdb->prefix}wpi_sites` ADD COLUMN `parent_id` BIGINT(20) UNSIGNED DEFAULT NULL AFTER `org_id`" );
            $wpdb->query( "ALTER TABLE `{$wpdb->prefix}wpi_sites` ADD KEY `parent_id` (`parent_id`)" );
        }
    }

    // Add description + is_active columns to existing installs (compatible with MySQL 5.7 + MariaDB)
    $api_cols = $wpdb->get_results( "SHOW COLUMNS FROM `{$wpdb->prefix}wpi_api_keys`" );
    if ( $api_cols ) {
        $existing_cols = wp_list_pluck( $api_cols, 'Field' );
        if ( ! in_array( 'description', $existing_cols, true ) ) {
            $wpdb->query( "ALTER TABLE `{$wpdb->prefix}wpi_api_keys` ADD COLUMN `description` VARCHAR(255) NOT NULL DEFAULT '' AFTER `label`" );
        }
        if ( ! in_array( 'is_active', $existing_cols, true ) ) {
            $wpdb->query( "ALTER TABLE `{$wpdb->prefix}wpi_api_keys` ADD COLUMN `is_active` TINYINT(1) NOT NULL DEFAULT 1 AFTER `scopes`" );
        }
    }

    // wpi_activity_log
    $wpdb->query( "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}wpi_activity_log` (
        `id`            BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        `object_type`   VARCHAR(20) NOT NULL DEFAULT '',
        `object_id`     BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
        `action`        VARCHAR(50) NOT NULL DEFAULT '',
        `detail`        TEXT,
        `user_id`       BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
        `user_name`     VARCHAR(255) NOT NULL DEFAULT '',
        `org_id`        BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
        `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `object` (`object_type`, `object_id`),
        KEY `user_id` (`user_id`),
        KEY `created_at` (`created_at`)
    ) $charset;" );

    if ( $wpdb->last_error ) {
        error_log( 'WPI wpi_activity_log create error: ' . $wpdb->last_error );
    }

    // wpi_actions
    $wpdb->query( "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}wpi_actions` (
        `id`              BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        `inspection_id`   BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
        `question_id`     VARCHAR(100) NOT NULL DEFAULT '',
        `question_label`  VARCHAR(500) NOT NULL DEFAULT '',
        `note`            TEXT,
        `assigned_to`     BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
        `assigned_name`   VARCHAR(255) NOT NULL DEFAULT '',
        `assigned_email`  VARCHAR(255) NOT NULL DEFAULT '',
        `due_date`        DATE DEFAULT NULL,
        `priority`        VARCHAR(10) NOT NULL DEFAULT 'medium',
        `status`          VARCHAR(20) NOT NULL DEFAULT 'open',
        `resolved_note`   TEXT,
        `resolved_at`     DATETIME DEFAULT NULL,
        `resolved_by`     BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
        `created_by`      BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
        `org_id`          BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
        `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `inspection_id` (`inspection_id`),
        KEY `assigned_to` (`assigned_to`),
        KEY `status` (`status`),
        KEY `org_id` (`org_id`),
        KEY `due_date` (`due_date`)
    ) $charset;" );

    if ( $wpdb->last_error ) {
        error_log( 'WPI wpi_actions create error: ' . $wpdb->last_error );
    } else {
        set_transient( $key, 1, DAY_IN_SECONDS );
    }
}

/**
 * Clean PDF endpoint — bypasses admin-ajax.php so iOS Safari
 * never sees the WordPress admin title in the tab.
 * URL: /?wpi_pdf=1&id=X&nonce=Y[&standalone=1]
 */
add_action( 'template_redirect', 'wpi_pdf_endpoint', 1 );

// Lightweight column-check: runs once per day via transient
add_action( 'init', function() {
    // Always check for critical tables — fast SHOW TABLES is negligible overhead
    global $wpdb;
    if ( ! $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}wpi_actions'" ) ) {
        $wpdb->query( "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wpi_actions (
            id              BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            inspection_id   BIGINT(20) UNSIGNED NOT NULL,
            question_id     VARCHAR(100) DEFAULT '',
            question_label  VARCHAR(500) DEFAULT '',
            note            TEXT DEFAULT NULL,
            assigned_to     BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            assigned_name   VARCHAR(255) DEFAULT '',
            assigned_email  VARCHAR(255) DEFAULT '',
            due_date        DATE DEFAULT NULL,
            priority        VARCHAR(10) DEFAULT 'medium',
            status          VARCHAR(20) DEFAULT 'open',
            resolved_note   TEXT DEFAULT NULL,
            resolved_at     DATETIME DEFAULT NULL,
            resolved_by     BIGINT(20) UNSIGNED DEFAULT 0,
            created_by      BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            org_id          BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY inspection_id (inspection_id),
            KEY assigned_to (assigned_to),
            KEY status (status),
            KEY org_id (org_id),
            KEY due_date (due_date)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" );
    }
    if ( ! $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}wpi_activity_log'" ) ) {
        $wpdb->query( "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wpi_activity_log (
            id            BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            object_type   VARCHAR(20) NOT NULL,
            object_id     BIGINT(20) UNSIGNED NOT NULL,
            action        VARCHAR(50) NOT NULL,
            detail        TEXT DEFAULT NULL,
            user_id       BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            user_name     VARCHAR(255) DEFAULT '',
            org_id        BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            created_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY object (object_type, object_id),
            KEY user_id (user_id),
            KEY created_at (created_at)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" );
    }

    $schema_key = 'wpi_schema_' . md5( WPI_VERSION );
    if ( get_transient( $schema_key ) ) return;
    $cols = $wpdb->get_col( "SHOW COLUMNS FROM {$wpdb->prefix}wpi_questions", 0 );
    if ( $cols && ! in_array( 'yes_no_colors', $cols ) ) {
        $wpdb->query( "ALTER TABLE {$wpdb->prefix}wpi_questions ADD COLUMN yes_no_colors TEXT DEFAULT NULL" );
    }
    // Create scheduler table if missing
    require_once WPI_PLUGIN_DIR . 'includes/class-scheduler.php';
    WPI_Scheduler::create_table();

    // Create activity log table if missing
    if ( ! $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}wpi_activity_log'" ) ) {
        $wpdb->query( "CREATE TABLE {$wpdb->prefix}wpi_activity_log (
            id            BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            object_type   VARCHAR(20) NOT NULL,
            object_id     BIGINT(20) UNSIGNED NOT NULL,
            action        VARCHAR(50) NOT NULL,
            detail        TEXT DEFAULT NULL,
            user_id       BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            user_name     VARCHAR(255) DEFAULT '',
            org_id        BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            created_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY object (object_type, object_id),
            KEY user_id (user_id),
            KEY created_at (created_at)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" );
    }

    // Create corrective actions table if missing
    if ( ! $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}wpi_actions'" ) ) {
        $wpdb->query( "CREATE TABLE {$wpdb->prefix}wpi_actions (
            id              BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            inspection_id   BIGINT(20) UNSIGNED NOT NULL,
            question_id     VARCHAR(100) DEFAULT '',
            question_label  VARCHAR(500) DEFAULT '',
            note            TEXT DEFAULT NULL,
            assigned_to     BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            assigned_name   VARCHAR(255) DEFAULT '',
            assigned_email  VARCHAR(255) DEFAULT '',
            due_date        DATE DEFAULT NULL,
            priority        VARCHAR(10) DEFAULT 'medium',
            status          VARCHAR(20) DEFAULT 'open',
            resolved_note   TEXT DEFAULT NULL,
            resolved_at     DATETIME DEFAULT NULL,
            resolved_by     BIGINT(20) UNSIGNED DEFAULT 0,
            created_by      BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            org_id          BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY inspection_id (inspection_id),
            KEY assigned_to (assigned_to),
            KEY status (status),
            KEY org_id (org_id),
            KEY due_date (due_date)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" );
    }

    set_transient( $schema_key, 1, DAY_IN_SECONDS );
}, 1 );

// Scheduler cron hook — called by your server cron or wp-cron
add_action( 'wpi_run_scheduler', function() {
    require_once WPI_PLUGIN_DIR . 'includes/class-scheduler.php';
    WPI_Scheduler::run();
} );
// Register WP cron schedule as fallback (every 15 min)
add_filter( 'cron_schedules', function( $schedules ) {
    if ( ! isset( $schedules['wpi_15min'] ) ) {
        $schedules['wpi_15min'] = array( 'interval' => 900, 'display' => 'Every 15 Minutes (WPI)' );
    }
    return $schedules;
} );
if ( ! wp_next_scheduled( 'wpi_run_scheduler' ) ) {
    wp_schedule_event( time(), 'wpi_15min', 'wpi_run_scheduler' );
}

// ── Daily cron for overdue action reminder emails ──────────────────
add_filter( 'cron_schedules', function( $schedules ) {
    if ( ! isset( $schedules['wpi_daily'] ) ) {
        $schedules['wpi_daily'] = array( 'interval' => 86400, 'display' => 'Once Daily (WPI)' );
    }
    return $schedules;
} );
add_action( 'wpi_action_overdue_reminders', function() {
    require_once WPI_PLUGIN_DIR . 'includes/class-scheduler.php';
    WPI_Scheduler::send_action_overdue_reminders();
} );
if ( ! wp_next_scheduled( 'wpi_action_overdue_reminders' ) ) {
    // Schedule for 8 AM local time daily
    $tz   = wp_timezone();
    $next = new DateTime( 'today 08:00', $tz );
    if ( $next->getTimestamp() < time() ) $next->modify( '+1 day' );
    wp_schedule_event( $next->getTimestamp(), 'wpi_daily', 'wpi_action_overdue_reminders' );
}
add_action( 'template_redirect', 'wpi_share_token_endpoint', 1 );

/* ═══════════════════════════════════════════════════════════════════
/* ═══════════════════════════════════════════════════════════════════
 * BRANDED FRONT-END ENTRY POINT
 * Works with ALL permalink structures including Plain (?p=123).
 * URL: audit4me.net/?wpi=1  (shareable, bookmark-friendly)
 * Logged-out  → branded login page (no WP chrome)
 * Logged-in   → full Audit4me app (no WP chrome)
 * ═══════════════════════════════════════════════════════════════════ */

// Serve dynamic manifest for /?wpi=1 with correct start_url
add_action( 'template_redirect', function() {
    if ( ! isset( $_GET['wpi_manifest'] ) ) return;
    $icon_base = WPI_PLUGIN_URL . 'assets/icons/';
    $site_url  = home_url('/');
    $manifest  = array(
        'id'               => home_url('/?wpi=1'),
        'name'             => get_bloginfo('name') ?: 'Audit4me',
        'short_name'       => 'Audit4me',
        'description'      => 'Professional inspection and audit management platform.',
        'start_url'        => home_url( '/?wpi=1' ),
        'scope'            => '/',
        'display'          => 'standalone',
        'display_override' => array('window-controls-overlay','standalone','minimal-ui'),
        'orientation'      => 'portrait-primary',
        'background_color' => '#1a3a5c',
        'theme_color'      => '#1a3a5c',
        'lang'             => 'en-AU',
        'dir'              => 'ltr',
        'categories'       => array('business','productivity','utilities'),
        'icons'            => array(
            array('src'=>$icon_base.'app-icon-180.png',  'sizes'=>'180x180',  'type'=>'image/png', 'purpose'=>'any'),
            array('src'=>$icon_base.'icon-192x192.png',  'sizes'=>'192x192',  'type'=>'image/png', 'purpose'=>'any'),
            array('src'=>$icon_base.'icon-192x192.png',  'sizes'=>'192x192',  'type'=>'image/png', 'purpose'=>'maskable'),
            array('src'=>$icon_base.'icon-512x512.png',  'sizes'=>'512x512',  'type'=>'image/png', 'purpose'=>'any'),
            array('src'=>$icon_base.'icon-512x512.png',  'sizes'=>'512x512',  'type'=>'image/png', 'purpose'=>'maskable'),
        ),
        'shortcuts' => array(
            array(
                'name'        => 'New Inspection',
                'short_name'  => 'Inspect',
                'description' => 'Start a new inspection or audit',
                'url'         => home_url('/?wpi=1#inspections'),
                'icons'       => array(array('src'=>$icon_base.'icon-192x192.png','sizes'=>'192x192','type'=>'image/png')),
            ),
            array(
                'name'        => 'My Actions',
                'short_name'  => 'Actions',
                'description' => 'View and manage your assigned actions',
                'url'         => home_url('/?wpi=1#actions'),
                'icons'       => array(array('src'=>$icon_base.'icon-192x192.png','sizes'=>'192x192','type'=>'image/png')),
            ),
            array(
                'name'        => 'Templates',
                'short_name'  => 'Templates',
                'description' => 'Browse and manage inspection templates',
                'url'         => home_url('/?wpi=1#templates'),
                'icons'       => array(array('src'=>$icon_base.'icon-192x192.png','sizes'=>'192x192','type'=>'image/png')),
            ),
        ),
        'screenshots' => array(
            array(
                'src'         => $icon_base.'screenshot-mobile.png',
                'sizes'       => '390x844',
                'type'        => 'image/png',
                'form_factor' => 'narrow',
                'label'       => 'Audit4me — Inspection dashboard on mobile',
            ),
        ),
        'serviceworker'               => array(
            'src'   => home_url('/sw.js'),
            'scope' => '/',
            'update_via_cache' => 'none',
        ),
        'launch_handler'              => array(
            'client_mode' => array('navigate-existing', 'auto'),
        ),
        'share_target'                => array(
            'action' => home_url('/?wpi=1&wpi_share=1'),
            'method' => 'POST',
            'enctype'=> 'multipart/form-data',
            'params' => array(
                'title' => 'title',
                'text'  => 'text',
                'url'   => 'url',
                'files' => array(
                    array(
                        'name'   => 'photos',
                        'accept' => array('image/jpeg','image/png','image/webp'),
                    ),
                ),
            ),
        ),
        'iarc_rating_id'              => 'e84b072d-71b3-4d3e-86ae-31a8ce4e53b7',
        'prefer_related_applications' => false,
        'related_applications'        => array(),
        'protocol_handlers'           => array(),
    );
    header( 'Content-Type: application/manifest+json; charset=UTF-8' );
    header( 'Cache-Control: public, max-age=3600' );
    header( 'Service-Worker-Allowed: /' );
    echo json_encode( $manifest, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT );
    exit;
}, 1 );

add_action( 'init', 'wpi_register_app_endpoint' );
function wpi_register_app_endpoint() {
    add_rewrite_rule( '^audit4me/?$', 'index.php?wpi=1', 'top' );
}

add_filter( 'query_vars', function( $vars ) {
    $vars[] = 'wpi';
    return $vars;
} );

// Flush rewrite rules on activation
register_activation_hook( __FILE__, function() {
    wpi_register_app_endpoint();
    flush_rewrite_rules();
} );

add_action( 'template_redirect', 'wpi_app_endpoint', 1 );
function wpi_app_endpoint() {
    // Trigger on ?wpi=1 (plain permalinks) OR /audit4me/ (pretty permalinks)
    $is_wpi = get_query_var( 'wpi' ) || ( isset( $_GET['wpi'] ) );
    if ( ! $is_wpi ) return;

    // Handle AJAX login via WPI endpoint (bypasses WAF that blocks admin-ajax.php)
    if ( isset( $_GET['wpi_action'] ) && $_GET['wpi_action'] === 'auth' && $_SERVER['REQUEST_METHOD'] === 'POST' ) {
        header( 'Content-Type: application/json; charset=utf-8' );
        header( 'Cache-Control: no-store, no-cache, must-revalidate' );
        header( 'X-Content-Type-Options: nosniff' );
        header( 'X-Frame-Options: DENY' );
        // Restrict to same origin only
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        $home   = home_url();
        if ( $origin && strpos( $home, $origin ) !== 0 && strpos( $origin, $home ) !== 0 ) {
            http_response_code( 403 );
            echo json_encode( array( 'success' => false, 'data' => array( 'message' => 'Origin not allowed.' ) ) );
            exit;
        }

        // Nonce check
        $nonce = sanitize_text_field( $_POST['nonce'] ?? '' );
        if ( ! $nonce || ! wp_verify_nonce( $nonce, 'wpi_login' ) ) {
            http_response_code( 403 );
            echo json_encode( array( 'success' => false, 'data' => array( 'message' => 'Security check failed. Please refresh the page and try again.' ) ) );
            exit;
        }

        $log = sanitize_text_field( wp_unslash( $_POST['wpi_u'] ?? '' ) );
        $pwd = wp_unslash( $_POST['wpi_k'] ?? '' );

        if ( ! $log || ! $pwd ) {
            echo json_encode( array( 'success' => false, 'data' => array( 'message' => 'Please enter your username and password.' ) ) );
            exit;
        }

        // Rate limit by IP (use REMOTE_ADDR directly - ignore X-Forwarded-For to prevent spoofing)
        // Also rate limit per username to catch distributed attacks on a single account
        $ip           = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $rate_key_ip  = 'wpi_login_ip_'  . md5( $ip );
        $rate_key_usr = 'wpi_login_usr_' . md5( strtolower( $log ) );
        $attempts_ip  = (int) get_transient( $rate_key_ip );
        $attempts_usr = (int) get_transient( $rate_key_usr );
        if ( $attempts_ip >= 10 || $attempts_usr >= 20 ) {
            http_response_code( 429 );
            echo json_encode( array( 'success' => false, 'data' => array( 'message' => 'Too many login attempts. Please wait a few minutes and try again.' ) ) );
            exit;
        }
        // Increment BEFORE attempting auth (prevent timing attacks)
        set_transient( $rate_key_ip,  $attempts_ip  + 1, 5 * MINUTE_IN_SECONDS );
        set_transient( $rate_key_usr, $attempts_usr + 1, 5 * MINUTE_IN_SECONDS );

        // Resolve email to username
        $username = $log;
        if ( is_email( $log ) ) {
            $u = get_user_by( 'email', $log );
            if ( $u ) $username = $u->user_login;
        }

        $user = wp_signon( array(
            'user_login'    => $username,
            'user_password' => $pwd,
            'remember'      => ! empty( $_POST['rememberme'] ),
        ), is_ssl() );

        if ( is_wp_error( $user ) ) {
            // Generic message to prevent username enumeration
            // (never reveal whether username or password was wrong)
            echo json_encode( array( 'success' => false, 'data' => array( 'message' => 'Incorrect username or password.' ) ) );
            exit;
        }

        // Success - clear rate limits
        delete_transient( $rate_key_ip );
        delete_transient( $rate_key_usr );
        echo json_encode( array( 'success' => true, 'data' => array( 'redirect' => home_url( '/?wpi=1' ) ) ) );
        exit;
    }

    // Handle logout
    if ( isset( $_GET['wpi_logout'] ) ) {
        if ( wp_verify_nonce( $_GET['_wpnonce'] ?? '', 'wpi_logout' ) ) {
            // Remove WP's own logout redirect before calling wp_logout()
            remove_action( 'wp_logout', 'wp_redirect_admin_locations' );
            add_action( 'wp_logout', function() {
                wp_redirect( home_url( '/?wpi=1' ) );
                exit;
            }, 1 );
            wp_logout();
        }
        wp_redirect( home_url( '/?wpi=1' ) );
        exit;
    }

    // Handle forgot username — email the username to the address provided
    if ( isset( $_GET['wpi_forgot_user'] ) ) {
        wpi_render_forgot_username_page();
        exit;
    }

    if ( ! is_user_logged_in() ) {
        $action = $_GET['action'] ?? '';
        if ( $action === 'rp' || $action === 'resetpass' ) {
            wpi_render_reset_password_page();
            exit;
        }
        if ( $action === 'register' ) {
            if ( ! get_option( 'wpi_registration_enabled', 0 ) ) {
                wp_redirect( home_url( '/?wpi=1&login=registration_disabled' ) );
                exit;
            }
            wpi_render_registration_page( sanitize_text_field( $_GET['wpi_invite'] ?? '' ) );
            exit;
        }
        if ( $action === 'install' ) {
            wpi_render_install_page();
            exit;
        }
        wpi_render_login_page( sanitize_text_field( $_GET['wpi_invite'] ?? '' ) );
        exit;
    }
    wpi_render_app_page();
    exit;
}

// Redirect password reset emails back to our branded URL
add_filter( 'lostpassword_redirect', function() {
    return home_url( '/?wpi=1&login=checkemail' );
} );

// Override WP password reset email with branded template
add_filter( 'retrieve_password_message', function( $message, $key, $user_login, $user_data ) {
    $site      = get_bloginfo('name') ?: get_option('blogname') ?: 'Audit4me';
    $reset_url = home_url( '/?wpi=1&action=rp&key=' . rawurlencode($key) . '&login=' . rawurlencode($user_login) );
    $fname     = $user_data->first_name ?: $user_login;
    $today     = (new DateTime('now', wp_timezone()))->format('d M Y');

    $body_html = '
        <p style="margin:0 0 16px;color:#374151;font-size:14px;line-height:1.6;">Hi ' . esc_html($fname) . ',</p>
        <p style="margin:0 0 20px;color:#374151;font-size:14px;line-height:1.6;">
            We received a request to reset the password for your <strong>' . esc_html($site) . '</strong> account.
            Click the button below to set a new password. This link expires in 24 hours.
        </p>
        <p style="margin:0 0 20px;color:#374151;font-size:13px;">
            If you did not request a password reset, you can safely ignore this email — your password will not change.
        </p>';

    require_once plugin_dir_path( __FILE__ ) . 'includes/class-scheduler.php';
    $subject = 'Reset your password — ' . $site;
    WPI_Scheduler::send_branded_email(
        $user_data->user_email,
        $subject,
        $body_html,
        '#1a3a5c',
        '🔐 PASSWORD RESET',
        $reset_url,
        'Reset Password →'
    );

    // Return empty to prevent WP sending its own plain-text email
    return false;
}, 10, 4 );

add_filter( 'retrieve_password_title', function() {
    return 'Reset your password — ' . (get_bloginfo('name') ?: 'Audit4me');
} );

// Intercept WP lost password page — render in branded style
add_action( 'login_init', function() {
    $action = $_REQUEST['action'] ?? '';
    if ( $action === 'lostpassword' && ! isset($_POST['user_login']) ) {
        wpi_render_lostpassword_page();
        exit;
    }
    if ( $action === 'rp' || $action === 'resetpass' ) {
        // Render branded set-password form
        wpi_render_reset_password_page();
        exit;
    }
}, 1 );

// After successful password reset, go back to branded login
add_filter( 'wp_login_url', function( $url ) {
    if ( isset($_GET['wpi']) || isset($_GET['wpi_forgot_user']) ) {
        return home_url( '/?wpi=1' );
    }
    return $url;
} );

// Always redirect logout back to the custom login page
add_filter( 'logout_redirect', function( $redirect_to, $requested_redirect_to, $user ) {
    return home_url( '/?wpi=1' );
}, 10, 3 );

function wpi_render_lostpassword_page() {
    $icon_url     = WPI_PLUGIN_URL . 'assets/icons/app-icon-180.png';
    $manifest_url = home_url( '/?wpi_manifest=1' );
    $action_url   = site_url( 'wp-login.php?action=lostpassword', 'login_post' );
    $error = '';

    nocache_headers();
    header( 'Content-Type: text/html; charset=UTF-8' );
    ?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>Audit4me — Reset Password</title>
    <link rel="manifest" href="<?php echo esc_url($manifest_url); ?>">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="Audit4me">
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo esc_url($icon_url); ?>">
    <meta name="theme-color" content="#1a3a5c">
    <style>
        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
        html{height:100dvh;overflow:hidden;background:linear-gradient(160deg,#0f2440 0%,#1a3a5c 50%,#1e4976 100%);}
        body{height:100dvh;overflow-y:auto;-webkit-overflow-scrolling:touch;
            background:linear-gradient(160deg,#0f2440 0%,#1a3a5c 50%,#1e4976 100%);
            font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;
            display:flex;align-items:center;justify-content:center;
            padding:20px;padding-top:max(20px,env(safe-area-inset-top));
            padding-bottom:max(20px,env(safe-area-inset-bottom));}
        .card{background:#fff;border-radius:20px;padding:36px 28px;width:100%;max-width:400px;
            box-shadow:0 24px 80px rgba(0,0,0,.35);flex-shrink:0;}
        .brand{display:flex;align-items:center;gap:12px;margin-bottom:28px;}
        .brand img{width:44px;height:44px;border-radius:10px;}
        .brand-name{font-size:20px;font-weight:800;color:#0f2440;}
        .brand-tag{font-size:11px;color:#64748b;}
        h1{font-size:22px;font-weight:800;color:#0f172a;margin-bottom:6px;}
        .sub{font-size:14px;color:#64748b;margin-bottom:24px;line-height:1.5;}
        label{display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:6px;}
        input[type=text],input[type=email]{width:100%;padding:13px 16px;border-radius:10px;
            border:1.5px solid #e2e8f0;font-size:15px;color:#0f172a;outline:none;
            margin-bottom:16px;-webkit-appearance:none;background:#f8fafc;}
        input:focus{border-color:#1a3a5c;background:#fff;box-shadow:0 0 0 3px rgba(26,58,92,.1);}
        .btn{width:100%;padding:14px;border-radius:10px;border:none;background:#1a3a5c;color:#fff;
            font-size:15px;font-weight:700;cursor:pointer;-webkit-appearance:none;display:block;text-align:center;}
        .btn:active{background:#0d1f36;}
        .back{text-align:center;margin-top:18px;font-size:13px;}
        .back a{color:#1a3a5c;text-decoration:none;font-weight:600;}
        .footer{text-align:center;margin-top:24px;font-size:12px;color:#94a3b8;}
    </style>
</head>
<body>
<div class="card">
    <div class="brand">
        <img src="<?php echo esc_url($icon_url); ?>" alt="Audit4me">
        <div><div class="brand-name">Audit4me</div><div class="brand-tag">Inspection &amp; Audit Platform</div></div>
    </div>
    <h1>Reset your password</h1>
    <p class="sub">Enter your username or email address and we'll send you a link to reset your password.</p>

    <form method="post" action="<?php echo esc_url($action_url); ?>">
        <?php wp_nonce_field( 'retrieve_password' ); ?>
        <input type="hidden" name="redirect_to" value="<?php echo esc_attr(home_url('/?wpi=1')); ?>">
        <label for="user_login">Username or Email</label>
        <input type="text" id="user_login" name="user_login" autocomplete="username" required>
        <input type="submit" class="btn" value="Send Reset Link →">
    </form>
    <div class="back"><a href="<?php echo esc_url(home_url('/?wpi=1')); ?>">← Back to Sign In</a></div>
    <div class="footer">© <?php echo esc_html(date('Y')); ?> Audit4me · All rights reserved</div>
</div>

    <script>
      // wpi:logout-click-handler
      document.addEventListener('click', function(e){
        var a = e.target && e.target.closest ? e.target.closest('a') : null;
        if (!a || !a.href) return;
        if (a.href.indexOf('wpi_logout=1') !== -1 && window.wpi_wtn_logout_cleanup_then_go) {
          e.preventDefault();
          window.wpi_wtn_logout_cleanup_then_go(a.href);
        }
      }, true);
    </script>


    <script>
      // wpi-force-register-device-panel
      function wpiForceRegisterThisDevice(showAlert) {
        function done(msg) {
          console.log('[Audit4me FCM Force Register]', msg);
          var el = document.getElementById('wpi-force-register-status');
          if (el) el.textContent = msg;
          if (showAlert) alert(msg);
        }
        try {
          if (!window.wpInspector || !wpInspector.user || !wpInspector.user.id) {
            done('No logged-in Audit4me user found.');
            return;
          }
          if (!(window.WTN && window.WTN.Firebase && window.WTN.Firebase.Messaging)) {
            done('WTN Firebase bridge not available. Open inside the WebToNative Android app.');
            return;
          }
          window.WTN.Firebase.Messaging.getFCMToken({
            callback: function(data) {
              var token = data && data.token ? data.token : '';
              if (!token) {
                done('No FCM token returned from WebToNative.');
                return;
              }
              var fd = new FormData();
              fd.append('action', 'wpi_wtn_register');
              fd.append('nonce', wpInspector.nonce);
              fd.append('body', JSON.stringify({
                fcm_token: token,
                user_id: wpInspector.user.id,
                force: 1
              }));
              fetch((wpInspector.ajaxUrl || wpInspector.ajax), {method:'POST', credentials:'include', body:fd})
                .then(function(r){ return r.json(); })
                .then(function(j){
                  var d = j && (j.data || j);
                  if (j && j.success === false) throw new Error((d && d.message) || 'Registration failed');
                  done('Device registered to user ID ' + (d.uid || wpInspector.user.id) + ' successfully.');
                })
                .catch(function(e){ done('Register failed: ' + e.message); });
            }
          });
        } catch(e) {
          done('Register error: ' + e.message);
        }
      }

      function wpiAddForceRegisterButton() {
        if (document.getElementById('wpi-force-register-device-panel')) return;
        var panel = document.createElement('div');
        panel.id = 'wpi-force-register-device-panel';
        panel.style.cssText = 'position:fixed;right:12px;bottom:12px;z-index:999999;background:#fff;border:1px solid #cbd5e1;border-radius:12px;padding:10px;box-shadow:0 8px 20px rgba(0,0,0,.18);font-family:Arial,sans-serif;max-width:280px;';
        panel.innerHTML =
          '<div style="font-weight:700;font-size:13px;margin-bottom:6px;">Audit4me Device Push</div>' +
          '<button type="button" onclick="wpiForceRegisterThisDevice(true)" style="background:#2563eb;color:#fff;border:0;border-radius:8px;padding:8px 10px;font-weight:700;">Force Register This Device</button>' +
          '<div id="wpi-force-register-status" style="font-size:11px;color:#475569;margin-top:6px;">Logged in user: ' + ((window.wpInspector && wpInspector.user && wpInspector.user.id) || '?') + '</div>';
        document.body.appendChild(panel);
      }

      window.addEventListener('load', function(){
        var isPushTest = location.search.indexOf('push_test=1') !== -1;
        var isWTN = !!(window.WTN);
        if (isPushTest) setTimeout(wpiAddForceRegisterButton, 1200);
      });
    </script>


    <script>
      // wpi-auto-force-register-on-load
      window.addEventListener('load', function(){
        setTimeout(function(){
          try {
            if (!window.wpInspector || !wpInspector.user || !wpInspector.user.id) return;
            if (typeof window.wpiForceRegisterThisDevice !== 'function') return;

            var uid = String(wpInspector.user.id);
            var key = 'wpi_auto_fcm_registered_uid';
            var last = '';
            try { last = localStorage.getItem(key) || ''; } catch(e) {}

            // Always register once on load, and definitely when user changes.
            // This reassigns the device token from old user to current user automatically.
            window.wpiForceRegisterThisDevice(false);

            try { localStorage.setItem(key, uid); } catch(e) {}
          } catch(e) {
            console.log('[Audit4me auto FCM register error]', e.message);
          }
        }, 350);
      });
    </script>


<script>
// instant-login-fcm-register
(function(){
  function wpiInstantRegisterAfterLogin(){
    try{
      if(typeof window.wpiForceRegisterThisDevice === 'function'){
        setTimeout(function(){
          window.wpiForceRegisterThisDevice(false);
        }, 150);
      }
    }catch(e){}
  }

  document.addEventListener('submit', function(e){
    var f = e.target;
    if(!f) return;
    var t = (f.className || '') + ' ' + (f.id || '');
    if(/login/i.test(t)){
      setTimeout(wpiInstantRegisterAfterLogin, 800);
      setTimeout(wpiInstantRegisterAfterLogin, 2000);
    }
  }, true);

  window.wpiInstantRegisterAfterLogin = wpiInstantRegisterAfterLogin;
})();
</script>

</body>
</html>
    <?php
}

function wpi_render_reset_password_page() {
    $icon_url     = WPI_PLUGIN_URL . 'assets/icons/app-icon-180.png';
    $manifest_url = home_url( '/?wpi_manifest=1' );
    $key          = sanitize_text_field( $_GET['key']   ?? '' );
    $login        = sanitize_text_field( $_GET['login'] ?? '' );
    $error        = '';
    $success      = false;

    // Handle form submission
    if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['wpi_reset_submit'] ) ) {
        if ( ! wp_verify_nonce( $_POST['wpi_reset_nonce'] ?? '', 'wpi_reset_password' ) ) {
            $error = 'Security check failed. Please try again.';
        } else {
            $pass1 = $_POST['pass1'] ?? '';
            $pass2 = $_POST['pass2'] ?? '';
            $rkey  = sanitize_text_field( $_POST['rp_key']   ?? '' );
            $rlogin= sanitize_text_field( $_POST['rp_login'] ?? '' );
            if ( strlen($pass1) < 8 ) {
                $error = 'Password must be at least 8 characters.';
            } elseif ( $pass1 !== $pass2 ) {
                $error = 'Passwords do not match.';
            } else {
                $user = check_password_reset_key( $rkey, $rlogin );
                if ( is_wp_error($user) ) {
                    $error = 'This reset link has expired or already been used. Please request a new one.';
                } else {
                    reset_password( $user, $pass1 );
                    $success = true;
                }
            }
        }
    }

    nocache_headers();
    header( 'Content-Type: text/html; charset=UTF-8' );
    ?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0,minimum-scale=1.0,maximum-scale=1.0,user-scalable=no,viewport-fit=cover">
    <title>Audit4me — Set Password</title>
    <link rel="manifest" href="<?php echo esc_url($manifest_url); ?>">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="Audit4me">
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo esc_url($icon_url); ?>">
    <meta name="theme-color" content="#1a3a5c">
    <style>
        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
        html{height:100dvh;background:linear-gradient(160deg,#0f2440 0%,#1a3a5c 50%,#1e4976 100%);}
        body{min-height:100dvh;background:linear-gradient(160deg,#0f2440 0%,#1a3a5c 50%,#1e4976 100%);
            font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;
            display:flex;align-items:center;justify-content:center;
            padding:20px;padding-top:max(20px,env(safe-area-inset-top));
            padding-bottom:max(20px,env(safe-area-inset-bottom));}
        .card{background:#fff;border-radius:20px;padding:36px 28px;width:100%;max-width:400px;
            box-shadow:0 24px 80px rgba(0,0,0,.35);}
        .brand{display:flex;align-items:center;gap:12px;margin-bottom:28px;}
        .brand img{width:44px;height:44px;border-radius:10px;}
        .brand-name{font-size:20px;font-weight:800;color:#0f2440;}
        .brand-tag{font-size:11px;color:#64748b;}
        h1{font-size:22px;font-weight:800;color:#0f172a;margin-bottom:6px;}
        .sub{font-size:14px;color:#64748b;margin-bottom:24px;line-height:1.5;}
        .field{margin-bottom:16px;}
        label{display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:6px;}
        input[type=password]{width:100%;padding:13px 14px;border-radius:10px;
            border:1.5px solid #e2e8f0;font-size:15px;background:#f8fafc;outline:none;
            -webkit-appearance:none;transition:border .15s;}
        input[type=password]:focus{border-color:#1a3a5c;background:#fff;}
        .btn{width:100%;padding:14px;border-radius:10px;border:none;
            background:#1a3a5c;color:#fff;font-size:16px;font-weight:700;
            cursor:pointer;margin-top:4px;-webkit-appearance:none;display:flex;
            align-items:center;justify-content:center;gap:10px;transition:opacity .15s;}
        .btn:active{opacity:.85;}
        .error{background:#fef2f2;border:1.5px solid #fca5a5;border-radius:10px;
            padding:12px 16px;font-size:14px;color:#dc2626;margin-bottom:20px;line-height:1.5;}
        .success-box{background:#f0fdf4;border:1.5px solid #86efac;border-radius:10px;
            padding:20px 16px;text-align:center;margin-bottom:20px;}
        .success-box .icon{font-size:36px;margin-bottom:8px;}
        .success-box p{font-size:14px;color:#16a34a;font-weight:600;}
        .login-link{text-align:center;margin-top:20px;}
        .login-link a{font-size:14px;font-weight:700;color:#1a3a5c;text-decoration:none;}
        .hint{font-size:12px;color:#94a3b8;margin-top:5px;}
        .footer{text-align:center;margin-top:24px;font-size:12px;color:#94a3b8;}
    </style>
</head>
<body>
<div class="card">
    <div class="brand">
        <img src="<?php echo esc_url($icon_url); ?>" alt="Audit4me">
        <div><div class="brand-name">Audit4me</div><div class="brand-tag">Inspection &amp; Audit Platform</div></div>
    </div>

    <?php if ( $success ) : ?>
        <div class="success-box">
            <div class="icon">✅</div>
            <p>Password set successfully!</p>
        </div>
        <p style="font-size:14px;color:#374151;text-align:center;margin-bottom:20px;">You can now sign in with your new password.</p>
        <a href="<?php echo esc_url(home_url('/?wpi=1')); ?>" class="btn">Sign In →</a>
    <?php else : ?>
        <h1>Set your password</h1>
        <p class="sub">Choose a new password for your account.</p>
        <?php if ( $error ) : ?><div class="error"><?php echo esc_html($error); ?></div><?php endif; ?>
        <form method="post" action="">
            <?php wp_nonce_field('wpi_reset_password','wpi_reset_nonce'); ?>
            <input type="hidden" name="wpi_reset_submit" value="1">
            <input type="hidden" name="rp_key" value="<?php echo esc_attr($key); ?>">
            <input type="hidden" name="rp_login" value="<?php echo esc_attr($login); ?>">
            <div class="field">
                <label for="pass1">New Password</label>
                <input type="password" id="pass1" name="pass1" autocomplete="new-password" placeholder="Min. 8 characters" required>
            </div>
            <div class="field">
                <label for="pass2">Confirm Password</label>
                <input type="password" id="pass2" name="pass2" autocomplete="new-password" placeholder="Repeat password" required>
            </div>
            <button type="submit" class="btn">Set Password →</button>
        </form>
        <div class="login-link"><a href="<?php echo esc_url(home_url('/?wpi=1')); ?>">← Back to Sign In</a></div>
    <?php endif; ?>
    <div class="footer">© <?php echo date('Y'); ?> Audit4me · All rights reserved</div>
</div>

    <script>
      // wpi:logout-click-handler
      document.addEventListener('click', function(e){
        var a = e.target && e.target.closest ? e.target.closest('a') : null;
        if (!a || !a.href) return;
        if (a.href.indexOf('wpi_logout=1') !== -1 && window.wpi_wtn_logout_cleanup_then_go) {
          e.preventDefault();
          window.wpi_wtn_logout_cleanup_then_go(a.href);
        }
      }, true);
    </script>


    <script>
      // wpi-force-register-device-panel
      function wpiForceRegisterThisDevice(showAlert) {
        function done(msg) {
          console.log('[Audit4me FCM Force Register]', msg);
          var el = document.getElementById('wpi-force-register-status');
          if (el) el.textContent = msg;
          if (showAlert) alert(msg);
        }
        try {
          if (!window.wpInspector || !wpInspector.user || !wpInspector.user.id) {
            done('No logged-in Audit4me user found.');
            return;
          }
          if (!(window.WTN && window.WTN.Firebase && window.WTN.Firebase.Messaging)) {
            done('WTN Firebase bridge not available. Open inside the WebToNative Android app.');
            return;
          }
          window.WTN.Firebase.Messaging.getFCMToken({
            callback: function(data) {
              var token = data && data.token ? data.token : '';
              if (!token) {
                done('No FCM token returned from WebToNative.');
                return;
              }
              var fd = new FormData();
              fd.append('action', 'wpi_wtn_register');
              fd.append('nonce', wpInspector.nonce);
              fd.append('body', JSON.stringify({
                fcm_token: token,
                user_id: wpInspector.user.id,
                force: 1
              }));
              fetch((wpInspector.ajaxUrl || wpInspector.ajax), {method:'POST', credentials:'include', body:fd})
                .then(function(r){ return r.json(); })
                .then(function(j){
                  var d = j && (j.data || j);
                  if (j && j.success === false) throw new Error((d && d.message) || 'Registration failed');
                  done('Device registered to user ID ' + (d.uid || wpInspector.user.id) + ' successfully.');
                })
                .catch(function(e){ done('Register failed: ' + e.message); });
            }
          });
        } catch(e) {
          done('Register error: ' + e.message);
        }
      }

      function wpiAddForceRegisterButton() {
        if (document.getElementById('wpi-force-register-device-panel')) return;
        var panel = document.createElement('div');
        panel.id = 'wpi-force-register-device-panel';
        panel.style.cssText = 'position:fixed;right:12px;bottom:12px;z-index:999999;background:#fff;border:1px solid #cbd5e1;border-radius:12px;padding:10px;box-shadow:0 8px 20px rgba(0,0,0,.18);font-family:Arial,sans-serif;max-width:280px;';
        panel.innerHTML =
          '<div style="font-weight:700;font-size:13px;margin-bottom:6px;">Audit4me Device Push</div>' +
          '<button type="button" onclick="wpiForceRegisterThisDevice(true)" style="background:#2563eb;color:#fff;border:0;border-radius:8px;padding:8px 10px;font-weight:700;">Force Register This Device</button>' +
          '<div id="wpi-force-register-status" style="font-size:11px;color:#475569;margin-top:6px;">Logged in user: ' + ((window.wpInspector && wpInspector.user && wpInspector.user.id) || '?') + '</div>';
        document.body.appendChild(panel);
      }

      window.addEventListener('load', function(){
        var isPushTest = location.search.indexOf('push_test=1') !== -1;
        var isWTN = !!(window.WTN);
        if (isPushTest) setTimeout(wpiAddForceRegisterButton, 1200);
      });
    </script>


    <script>
      // wpi-auto-force-register-on-load
      window.addEventListener('load', function(){
        setTimeout(function(){
          try {
            if (!window.wpInspector || !wpInspector.user || !wpInspector.user.id) return;
            if (typeof window.wpiForceRegisterThisDevice !== 'function') return;

            var uid = String(wpInspector.user.id);
            var key = 'wpi_auto_fcm_registered_uid';
            var last = '';
            try { last = localStorage.getItem(key) || ''; } catch(e) {}

            // Always register once on load, and definitely when user changes.
            // This reassigns the device token from old user to current user automatically.
            window.wpiForceRegisterThisDevice(false);

            try { localStorage.setItem(key, uid); } catch(e) {}
          } catch(e) {
            console.log('[Audit4me auto FCM register error]', e.message);
          }
        }, 350);
      });
    </script>


<script>
// instant-login-fcm-register
(function(){
  function wpiInstantRegisterAfterLogin(){
    try{
      if(typeof window.wpiForceRegisterThisDevice === 'function'){
        setTimeout(function(){
          window.wpiForceRegisterThisDevice(false);
        }, 150);
      }
    }catch(e){}
  }

  document.addEventListener('submit', function(e){
    var f = e.target;
    if(!f) return;
    var t = (f.className || '') + ' ' + (f.id || '');
    if(/login/i.test(t)){
      setTimeout(wpiInstantRegisterAfterLogin, 800);
      setTimeout(wpiInstantRegisterAfterLogin, 2000);
    }
  }, true);

  window.wpiInstantRegisterAfterLogin = wpiInstantRegisterAfterLogin;
})();
</script>

</body>
</html>
    <?php
}

function wpi_render_install_page() {
    $icon_url   = WPI_PLUGIN_URL . 'assets/icons/app-icon-180.png';
    $app_url    = home_url( '/?wpi=1' );
    $site       = get_bloginfo('name') ?: 'Audit4me';
    $manifest   = home_url( '/?wpi_manifest=1' );
    nocache_headers();
    header( 'Content-Type: text/html; charset=UTF-8' );
    ?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<title>Install <?php echo esc_html($site); ?></title>
<link rel="manifest" href="<?php echo esc_url($manifest); ?>">
<meta name="theme-color" content="#1a3a5c">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="<?php echo esc_attr($site); ?>">
<link rel="apple-touch-icon" href="<?php echo esc_url($icon_url); ?>">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
body{min-height:100dvh;background:linear-gradient(160deg,#0f2440,#1a3a5c);
  font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;
  color:#fff;padding:env(safe-area-inset-top,20px) 20px 20px;}
.hero{text-align:center;padding:40px 0 32px;}
.hero img{width:96px;height:96px;border-radius:22px;
  box-shadow:0 8px 32px rgba(0,0,0,.4);margin-bottom:20px;}
.hero h1{font-size:28px;font-weight:800;margin-bottom:8px;}
.hero p{font-size:15px;opacity:.8;line-height:1.6;max-width:300px;margin:0 auto;}
.features{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:28px;}
.feat{background:rgba(255,255,255,.1);border-radius:14px;padding:16px 14px;
  border:1px solid rgba(255,255,255,.15);}
.feat .icon{font-size:24px;margin-bottom:8px;}
.feat .title{font-size:13px;font-weight:700;margin-bottom:4px;}
.feat .desc{font-size:11px;opacity:.7;line-height:1.4;}
.install-card{background:#fff;border-radius:20px;padding:24px;margin-bottom:16px;color:#0f172a;}
.install-card h2{font-size:16px;font-weight:800;margin-bottom:16px;
  display:flex;align-items:center;gap:8px;}
.install-card h2 .os-badge{font-size:20px;}
.step{display:flex;gap:12px;align-items:flex-start;margin-bottom:14px;}
.step:last-child{margin-bottom:0;}
.step .num{width:28px;height:28px;border-radius:14px;
  background:#1a3a5c;color:#fff;font-size:12px;font-weight:800;
  display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.step .text{font-size:13px;line-height:1.5;color:#374151;padding-top:5px;}
.step .text strong{color:#0f172a;}
.step .text code{background:#f1f5f9;border-radius:4px;padding:1px 6px;
  font-size:11px;font-family:monospace;}
.open-btn{display:block;width:100%;padding:16px;border-radius:14px;
  background:#1a3a5c;color:#fff;text-decoration:none;text-align:center;
  font-size:16px;font-weight:700;border:none;cursor:pointer;
  box-shadow:0 4px 20px rgba(0,0,0,.3);}
.divider{text-align:center;font-size:12px;opacity:.5;margin:12px 0;}
.or-divider{display:flex;align-items:center;gap:12px;margin:16px 0;}
.or-divider::before,.or-divider::after{content:'';flex:1;height:1px;background:rgba(255,255,255,.2);}
.or-divider span{font-size:12px;opacity:.6;}
.play-badge{display:flex;align-items:center;justify-content:center;gap:10px;
  background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.2);
  border-radius:14px;padding:14px;text-decoration:none;color:#fff;}
.play-badge .pb-icon{font-size:28px;}
.play-badge .pb-text .pb-sub{font-size:11px;opacity:.7;}
.play-badge .pb-text .pb-name{font-size:15px;font-weight:700;}
.footer{text-align:center;font-size:11px;opacity:.5;margin-top:24px;}
#ios-steps,#android-steps{display:none;}
</style>
</head>
<body>
<div class="hero">
  <img src="<?php echo esc_url($icon_url); ?>" alt="<?php echo esc_attr($site); ?>">
  <h1><?php echo esc_html($site); ?></h1>
  <p>Inspection & Audit Management — install for the best experience</p>
</div>

<div class="features">
  <div class="feat"><div class="icon">📋</div><div class="title">Inspections</div><div class="desc">Conduct audits anywhere</div></div>
  <div class="feat"><div class="icon">📷</div><div class="title">Photos</div><div class="desc">Capture evidence instantly</div></div>
  <div class="feat"><div class="icon">⚡</div><div class="title">Fast</div><div class="desc">Works offline too</div></div>
  <div class="feat"><div class="icon">🔔</div><div class="title">Alerts</div><div class="desc">Real-time notifications</div></div>
</div>

<!-- iOS Instructions -->
<div class="install-card" id="ios-steps">
  <h2><span class="os-badge"></span>Install on iPhone / iPad</h2>
  <div class="step"><div class="num">1</div><div class="text">Tap the <strong>Share button</strong> <code>⎙</code> at the bottom of Safari</div></div>
  <div class="step"><div class="num">2</div><div class="text">Scroll down and tap <strong>"Add to Home Screen"</strong></div></div>
  <div class="step"><div class="num">3</div><div class="text">Tap <strong>"Add"</strong> in the top right — done!</div></div>
</div>

<!-- Android Instructions -->
<div class="install-card" id="android-steps">
  <h2><span class="os-badge">🤖</span>Install on Android</h2>
  <div class="step"><div class="num">1</div><div class="text">Tap the <strong>menu (⋮)</strong> in Chrome top right</div></div>
  <div class="step"><div class="num">2</div><div class="text">Tap <strong>"Add to Home screen"</strong> or <strong>"Install app"</strong></div></div>
  <div class="step"><div class="num">3</div><div class="text">Tap <strong>"Install"</strong> — the app icon appears on your home screen</div></div>
</div>

<!-- Desktop fallback -->
<div class="install-card" id="desktop-steps">
  <h2>📱 Open on your phone</h2>
  <div class="step"><div class="num">1</div><div class="text">Open this page on your iPhone or Android phone</div></div>
  <div class="step"><div class="num">2</div><div class="text">Follow the install instructions to add it to your home screen</div></div>
</div>

<a href="<?php echo esc_url($app_url); ?>" class="open-btn" id="open-btn">Open Audit4me</a>

<div class="or-divider"><span>or install directly</span></div>

<a href="#" class="play-badge" id="install-btn" style="display:none;">
  <div class="pb-icon">📲</div>
  <div class="pb-text">
    <div class="pb-sub">Install</div>
    <div class="pb-name">Audit4me App</div>
  </div>
</a>

<div class="footer">© <?php echo date('Y'); ?> <?php echo esc_html($site); ?> · All rights reserved</div>

<script>
(function(){
  var ua = navigator.userAgent || '';
  var isIOS = /iphone|ipad|ipod/i.test(ua);
  var isAndroid = /android/i.test(ua);
  var isMobile = isIOS || isAndroid;

  // Show correct install instructions
  if (isIOS) {
    document.getElementById('ios-steps').style.display = 'block';
  } else if (isAndroid) {
    document.getElementById('android-steps').style.display = 'block';
  } else {
    document.getElementById('desktop-steps').style.display = 'block';
  }

  // PWA install prompt (Android/Desktop Chrome)
  var deferredPrompt = null;
  window.addEventListener('beforeinstallprompt', function(e) {
    e.preventDefault();
    deferredPrompt = e;
    var btn = document.getElementById('install-btn');
    btn.style.display = 'flex';
    btn.addEventListener('click', function(ev) {
      ev.preventDefault();
      if (deferredPrompt) {
        deferredPrompt.prompt();
        deferredPrompt.userChoice.then(function(r){
          deferredPrompt = null;
          if (r.outcome === 'accepted') {
            btn.style.display = 'none';
          }
        });
      }
    });
  });

  // Already installed — just open app
  if (window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone) {
    window.location.href = '<?php echo esc_js($app_url); ?>';
  }

  // Register service worker
  if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/sw.js', {scope:'/'})
      .catch(function(){});
  }
})();
</script>

    <script>
      // wpi:logout-click-handler
      document.addEventListener('click', function(e){
        var a = e.target && e.target.closest ? e.target.closest('a') : null;
        if (!a || !a.href) return;
        if (a.href.indexOf('wpi_logout=1') !== -1 && window.wpi_wtn_logout_cleanup_then_go) {
          e.preventDefault();
          window.wpi_wtn_logout_cleanup_then_go(a.href);
        }
      }, true);
    </script>


    <script>
      // wpi-force-register-device-panel
      function wpiForceRegisterThisDevice(showAlert) {
        function done(msg) {
          console.log('[Audit4me FCM Force Register]', msg);
          var el = document.getElementById('wpi-force-register-status');
          if (el) el.textContent = msg;
          if (showAlert) alert(msg);
        }
        try {
          if (!window.wpInspector || !wpInspector.user || !wpInspector.user.id) {
            done('No logged-in Audit4me user found.');
            return;
          }
          if (!(window.WTN && window.WTN.Firebase && window.WTN.Firebase.Messaging)) {
            done('WTN Firebase bridge not available. Open inside the WebToNative Android app.');
            return;
          }
          window.WTN.Firebase.Messaging.getFCMToken({
            callback: function(data) {
              var token = data && data.token ? data.token : '';
              if (!token) {
                done('No FCM token returned from WebToNative.');
                return;
              }
              var fd = new FormData();
              fd.append('action', 'wpi_wtn_register');
              fd.append('nonce', wpInspector.nonce);
              fd.append('body', JSON.stringify({
                fcm_token: token,
                user_id: wpInspector.user.id,
                force: 1
              }));
              fetch((wpInspector.ajaxUrl || wpInspector.ajax), {method:'POST', credentials:'include', body:fd})
                .then(function(r){ return r.json(); })
                .then(function(j){
                  var d = j && (j.data || j);
                  if (j && j.success === false) throw new Error((d && d.message) || 'Registration failed');
                  done('Device registered to user ID ' + (d.uid || wpInspector.user.id) + ' successfully.');
                })
                .catch(function(e){ done('Register failed: ' + e.message); });
            }
          });
        } catch(e) {
          done('Register error: ' + e.message);
        }
      }

      function wpiAddForceRegisterButton() {
        if (document.getElementById('wpi-force-register-device-panel')) return;
        var panel = document.createElement('div');
        panel.id = 'wpi-force-register-device-panel';
        panel.style.cssText = 'position:fixed;right:12px;bottom:12px;z-index:999999;background:#fff;border:1px solid #cbd5e1;border-radius:12px;padding:10px;box-shadow:0 8px 20px rgba(0,0,0,.18);font-family:Arial,sans-serif;max-width:280px;';
        panel.innerHTML =
          '<div style="font-weight:700;font-size:13px;margin-bottom:6px;">Audit4me Device Push</div>' +
          '<button type="button" onclick="wpiForceRegisterThisDevice(true)" style="background:#2563eb;color:#fff;border:0;border-radius:8px;padding:8px 10px;font-weight:700;">Force Register This Device</button>' +
          '<div id="wpi-force-register-status" style="font-size:11px;color:#475569;margin-top:6px;">Logged in user: ' + ((window.wpInspector && wpInspector.user && wpInspector.user.id) || '?') + '</div>';
        document.body.appendChild(panel);
      }

      window.addEventListener('load', function(){
        var isPushTest = location.search.indexOf('push_test=1') !== -1;
        var isWTN = !!(window.WTN);
        if (isPushTest) setTimeout(wpiAddForceRegisterButton, 1200);
      });
    </script>


    <script>
      // wpi-auto-force-register-on-load
      window.addEventListener('load', function(){
        setTimeout(function(){
          try {
            if (!window.wpInspector || !wpInspector.user || !wpInspector.user.id) return;
            if (typeof window.wpiForceRegisterThisDevice !== 'function') return;

            var uid = String(wpInspector.user.id);
            var key = 'wpi_auto_fcm_registered_uid';
            var last = '';
            try { last = localStorage.getItem(key) || ''; } catch(e) {}

            // Always register once on load, and definitely when user changes.
            // This reassigns the device token from old user to current user automatically.
            window.wpiForceRegisterThisDevice(false);

            try { localStorage.setItem(key, uid); } catch(e) {}
          } catch(e) {
            console.log('[Audit4me auto FCM register error]', e.message);
          }
        }, 350);
      });
    </script>


<script>
// instant-login-fcm-register
(function(){
  function wpiInstantRegisterAfterLogin(){
    try{
      if(typeof window.wpiForceRegisterThisDevice === 'function'){
        setTimeout(function(){
          window.wpiForceRegisterThisDevice(false);
        }, 150);
      }
    }catch(e){}
  }

  document.addEventListener('submit', function(e){
    var f = e.target;
    if(!f) return;
    var t = (f.className || '') + ' ' + (f.id || '');
    if(/login/i.test(t)){
      setTimeout(wpiInstantRegisterAfterLogin, 800);
      setTimeout(wpiInstantRegisterAfterLogin, 2000);
    }
  }, true);

  window.wpiInstantRegisterAfterLogin = wpiInstantRegisterAfterLogin;
})();
</script>

</body>
</html>
<?php
}

function wpi_render_registration_page( $invite_token = '' ) {
    $icon_url     = WPI_PLUGIN_URL . 'assets/icons/app-icon-180.png';
    $manifest_url = home_url( '/?wpi_manifest=1' );
    $login_url    = home_url( '/?wpi=1' );
    $ajax_url     = site_url( '/wp-admin/admin-ajax.php' );
    $site         = get_bloginfo('name') ?: get_option('blogname') ?: 'Audit4me';
    nocache_headers();
    header( 'Content-Type: text/html; charset=UTF-8' );
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0,minimum-scale=1.0,maximum-scale=1.0,user-scalable=no,viewport-fit=cover">
<title><?php echo esc_html($site); ?> &mdash; Create Account</title>
<meta name="theme-color" content="#1a3a5c">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
body{min-height:100dvh;background:linear-gradient(160deg,#0f2440,#1a3a5c);
  font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;
  display:flex;align-items:center;justify-content:center;
  padding:20px;padding-top:max(20px,env(safe-area-inset-top));}
.card{background:#fff;border-radius:20px;padding:32px 28px;width:100%;max-width:420px;
  box-shadow:0 24px 80px rgba(0,0,0,.35);}
.brand{display:flex;align-items:center;gap:12px;margin-bottom:24px;}
.brand img{width:44px;height:44px;border-radius:10px;}
.brand-name{font-size:20px;font-weight:800;color:#0f2440;}
.brand-tag{font-size:11px;color:#64748b;}
h1{font-size:22px;font-weight:800;color:#0f172a;margin-bottom:4px;}
.sub{font-size:13px;color:#64748b;margin-bottom:20px;line-height:1.5;}
.row2{display:grid;grid-template-columns:1fr 1fr;gap:10px;}
.field{margin-bottom:12px;}
label{display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;}
input{width:100%;padding:12px 14px;border-radius:10px;border:1.5px solid #e2e8f0;
  font-size:15px;background:#f8fafc;outline:none;-webkit-appearance:none;}
input:focus{border-color:#1a3a5c;background:#fff;}
.pw-wrap{position:relative;}
.pw-wrap input{padding-right:50px;}
.pw-btn{position:absolute;right:12px;top:50%;transform:translateY(-50%);
  background:none;border:none;cursor:pointer;font-size:13px;
  color:#1a3a5c;font-weight:600;padding:4px 6px;}

.btn{width:100%;padding:14px;border-radius:10px;border:none;
  background:#1a3a5c;color:#fff;font-size:16px;font-weight:700;
  cursor:pointer;margin-top:4px;}
.btn:active{background:#0d1f36;}
.alert{padding:12px 14px;border-radius:10px;font-size:13px;line-height:1.6;
  margin-bottom:14px;display:none;word-break:break-word;}
.alert.err{background:#fef2f2;border:1.5px solid #fca5a5;color:#dc2626;}
.alert.ok{background:#f0fdf4;border:1.5px solid #86efac;color:#15803d;}
.signin-link{text-align:center;margin-top:16px;font-size:14px;}
.signin-link a{font-weight:700;color:#1a3a5c;}
.footer{text-align:center;margin-top:20px;font-size:11px;color:#94a3b8;}
.success{text-align:center;padding:10px 0;display:none;}
.success .icon{font-size:48px;margin-bottom:12px;}
.success h2{font-size:20px;font-weight:800;color:#0f172a;margin-bottom:8px;}
.success p{font-size:13px;color:#64748b;margin-bottom:16px;}
.badge{display:inline-block;padding:6px 16px;border-radius:20px;
  font-size:13px;font-weight:700;margin-bottom:12px;}
.badge.full{background:#f0fdf4;border:1.5px solid #86efac;color:#15803d;}
.badge.basic{background:#fffbeb;border:1.5px solid #fcd34d;color:#92400e;}
</style>
</head>
<body>
<div class="card">
  <div class="brand">
    <img src="<?php echo esc_url($icon_url); ?>" alt="">
    <div>
      <div class="brand-name"><?php echo esc_html($site); ?></div>
      <div class="brand-tag">Inspection &amp; Audit Platform</div>
    </div>
  </div>

  <div id="form-wrap">
    <?php if ( $invite_token ) :
        global $wpdb;
        $wpi_ri = $wpdb->prefix . 'wpi_invitations';
        $wpi_inv_reg = null;
        if ( $wpdb->get_var( "SHOW TABLES LIKE '$wpi_ri'" ) === $wpi_ri ) {
            $wpi_inv_reg = $wpdb->get_row( $wpdb->prepare(
                "SELECT i.email, o.name AS org_name, u.display_name AS inviter_name
                 FROM {$wpdb->prefix}wpi_invitations i
                 LEFT JOIN {$wpdb->prefix}wpi_organisations o ON o.id=i.org_id
                 LEFT JOIN {$wpdb->users} u ON u.ID=i.invited_by
                 WHERE i.token=%s AND i.status='pending' AND i.expires_at > NOW()",
                $invite_token
            ) );
        }
        if ( $wpi_inv_reg ) : ?>
    <div style="background:linear-gradient(135deg,#1e3a5f 0%,#312e81 100%);border-radius:12px;padding:16px 18px;margin-bottom:20px;color:#fff;">
        <div style="font-size:10px;font-weight:700;letter-spacing:1px;opacity:.7;text-transform:uppercase;margin-bottom:4px;">You've been invited</div>
        <div style="font-size:16px;font-weight:800;margin-bottom:2px;"><?php echo esc_html( $wpi_inv_reg->org_name ); ?></div>
        <div style="font-size:12px;opacity:.8;">by <?php echo esc_html( $wpi_inv_reg->inviter_name ); ?></div>
    </div>
    <?php endif; endif; ?>
<h1>Create your account</h1>
    <p class="sub"><?php echo $invite_token ? 'Register to accept your invitation.' : 'Sign up to start conducting inspections and audits.'; ?></p>

    <div id="alert" class="alert err"></div>

    <div class="row2">
      <div class="field">
        <label>First Name *</label>
        <input type="text" id="r-first" placeholder="First name" autocomplete="given-name">
      </div>
      <div class="field">
        <label>Last Name *</label>
        <input type="text" id="r-last" placeholder="Last name" autocomplete="family-name">
      </div>
    </div>
    <div class="field">
      <label>Email Address *</label>
      <input type="email" id="r-email" placeholder="your@email.com" autocomplete="email" value="<?php echo isset($wpi_inv_reg) && $wpi_inv_reg ? esc_attr($wpi_inv_reg->email) : ''; ?>">
    </div>
    <div class="field">
      <label>Password *</label>
      <div class="pw-wrap">
        <input type="password" id="r-pass" placeholder="Min. 8 chars, 1 uppercase, 1 number, 1 symbol" autocomplete="new-password">
        <button type="button" class="pw-btn" id="pw-btn">Show</button>
      </div>
    </div>
    <div class="field">
      <label>Confirm Password *</label>
      <div class="pw-wrap">
        <input type="password" id="r-pass2" placeholder="Repeat your password" autocomplete="new-password">
        <button type="button" class="pw-btn" id="pw-btn2">Show</button>
      </div>
    </div>

    <button type="button" class="btn" id="reg-btn">Create Account</button>
    <div class="signin-link">Already have an account? <a href="<?php echo esc_url($login_url); ?>">Sign In</a></div>
  </div>

  <!-- Email verification step -->
  <div id="verify-wrap" style="display:none;">
    <div style="text-align:center;margin-bottom:24px;">
      <div style="font-size:44px;margin-bottom:12px;">📧</div>
      <h2 style="margin:0 0 8px;font-size:22px;font-weight:800;color:#0f172a;">Check your email</h2>
      <p style="margin:0;font-size:14px;color:#64748b;line-height:1.6;">We sent a 6-digit code to <strong id="verify-email-display"></strong><br>Enter it below to verify your email.</p>
    </div>
    <div id="verify-alrt" style="display:none;background:#fce8e6;color:#dc2626;padding:10px 14px;border-radius:8px;margin-bottom:14px;font-size:13px;border-left:3px solid #dc2626;"></div>
    <div style="display:flex;gap:8px;justify-content:center;margin-bottom:16px;">
      <input id="v-c1" type="text" inputmode="numeric" maxlength="1" style="width:44px;height:52px;text-align:center;font-size:22px;font-weight:800;border:2px solid #dbe3f0;border-radius:10px;font-family:monospace;color:#0f172a;">
      <input id="v-c2" type="text" inputmode="numeric" maxlength="1" style="width:44px;height:52px;text-align:center;font-size:22px;font-weight:800;border:2px solid #dbe3f0;border-radius:10px;font-family:monospace;color:#0f172a;">
      <input id="v-c3" type="text" inputmode="numeric" maxlength="1" style="width:44px;height:52px;text-align:center;font-size:22px;font-weight:800;border:2px solid #dbe3f0;border-radius:10px;font-family:monospace;color:#0f172a;">
      <span style="font-size:24px;line-height:52px;color:#94a3b8;">-</span>
      <input id="v-c4" type="text" inputmode="numeric" maxlength="1" style="width:44px;height:52px;text-align:center;font-size:22px;font-weight:800;border:2px solid #dbe3f0;border-radius:10px;font-family:monospace;color:#0f172a;">
      <input id="v-c5" type="text" inputmode="numeric" maxlength="1" style="width:44px;height:52px;text-align:center;font-size:22px;font-weight:800;border:2px solid #dbe3f0;border-radius:10px;font-family:monospace;color:#0f172a;">
      <input id="v-c6" type="text" inputmode="numeric" maxlength="1" style="width:44px;height:52px;text-align:center;font-size:22px;font-weight:800;border:2px solid #dbe3f0;border-radius:10px;font-family:monospace;color:#0f172a;">
    </div>
    <button id="verify-btn" class="btn" style="width:100%;margin-bottom:14px;">Verify &amp; Create Account</button>
    <div style="text-align:center;">
      <span style="font-size:13px;color:#64748b;">Didn't receive it? </span>
      <button id="resend-btn" style="background:none;border:none;color:#4f46e5;font-size:13px;font-weight:700;cursor:pointer;padding:0;">Resend code</button>
      <span id="resend-cd" style="font-size:12px;color:#94a3b8;display:none;"> (wait <span id="resend-s">30</span>s)</span>
    </div>
    <div style="text-align:center;margin-top:14px;">
      <button onclick="document.getElementById('verify-wrap').style.display='none';document.getElementById('form-wrap').style.display='block';" style="background:none;border:none;color:#94a3b8;font-size:12px;cursor:pointer;text-decoration:underline;">← Change details</button>
    </div>
  </div>

  <div class="success" id="success-wrap">
    <div class="icon">&#10003;</div>
    <h2>Account Created!</h2>
    <p id="s-msg">Your account has been created. To get started, please purchase a subscription.</p>
    <a href="<?php echo esc_url(get_option('wpi_billing_url', 'https://audit4me.net/#pricing')); ?>" class="btn" style="display:inline-block;text-decoration:none;padding:14px 32px;margin-bottom:10px;">Purchase Subscription →</a>
    <br><a href="<?php echo esc_url($login_url); ?>" style="display:inline-block;margin-top:8px;font-size:13px;color:#64748b;text-decoration:none;">Sign In with Basic Access</a>
  </div>

  <div class="footer">&copy; <?php echo date('Y'); ?> <?php echo esc_html($site); ?> &middot; All rights reserved</div>
</div>
<script>
(function(){
  var ajax = <?php echo json_encode($ajax_url); ?>;
  var btn  = document.getElementById('reg-btn');
  var alrt = document.getElementById('alert');

  document.getElementById('pw-btn').onclick = function(){
    var p = document.getElementById('r-pass');
    p.type = p.type==='password'?'text':'password';
    this.textContent = p.type==='password'?'Show':'Hide';
  };
  document.getElementById('pw-btn2').onclick = function(){
    var p = document.getElementById('r-pass2');
    p.type = p.type==='password'?'text':'password';
    this.textContent = p.type==='password'?'Show':'Hide';
  };



  function err(msg){ alrt.textContent=msg; alrt.style.display='block'; }
  function g(id){ return document.getElementById(id).value||'';}

  // ── digit-box helpers ────────────────────────────────────────────
  var vcells=[document.getElementById('v-c1'),document.getElementById('v-c2'),document.getElementById('v-c3'),document.getElementById('v-c4'),document.getElementById('v-c5'),document.getElementById('v-c6')];
  var va=document.getElementById('verify-alrt');
  function verrShow(m){va.textContent=m;va.style.display='block';}
  function verrHide(){va.style.display='none';}
  vcells.forEach(function(c,i){
    c.addEventListener('input',function(){
      this.value=this.value.replace(/[^0-9]/g,'');
      if(this.value&&i<5)vcells[i+1].focus();
      verrHide();
    });
    c.addEventListener('keydown',function(e){
      if(e.key==='Backspace'&&!this.value&&i>0)vcells[i-1].focus();
    });
    c.addEventListener('paste',function(e){
      e.preventDefault();
      var p=(e.clipboardData||window.clipboardData).getData('text').replace(/\D/g,'').slice(0,6);
      for(var j=0;j<p.length&&j<6;j++)vcells[j].value=p[j];
      if(p.length)vcells[Math.min(p.length,5)].focus();
    });
  });
  var rsBtn=document.getElementById('resend-btn');
  var rscd=document.getElementById('resend-cd');
  var rss=document.getElementById('resend-s');
  var rsTimer=null;
  function startResend(){
    var s=30; rsBtn.disabled=true; rsBtn.style.opacity='0.4';
    rscd.style.display='inline'; rss.textContent=s;
    rsTimer=setInterval(function(){
      s--; rss.textContent=s;
      if(s<=0){clearInterval(rsTimer);rsBtn.disabled=false;rsBtn.style.opacity='1';rscd.style.display='none';}
    },1000);
  }
  function doSendCode(email,cb){
    var fd2=new FormData();
    fd2.append('action','wpi_send_verify_code');fd2.append('email',email);
    fetch(ajax,{method:'POST',body:fd2}).then(function(r){return r.json();})
      .then(function(d){
        if(!d.success){err((d.data&&d.data.message)||'Could not send code.');return;}
        if(cb)cb();
      }).catch(function(){err('Network error. Please try again.');});
  }
  rsBtn.onclick=function(){
    verrHide();vcells.forEach(function(c){c.value='';});vcells[0].focus();
    doSendCode(g('r-email').trim(),function(){startResend();});
  };
  // ── Step 1: validate & send code ────────────────────────────────
  btn.onclick = function(){
    alrt.style.display='none';
    var first=g('r-first').trim(),last=g('r-last').trim(),
        email=g('r-email').trim(),pass=document.getElementById('r-pass').value;
    if(!first){err('Enter your first name.');return;}
    if(!last){err('Enter your last name.');return;}
    if(!email||email.indexOf('@')<1){err('Enter a valid email.');return;}
    if(pass.length<8){err('Password must be at least 8 characters.');return;}
    if(!/[A-Z]/.test(pass)){err('Password must contain at least one uppercase letter.');return;}
    if(!/[0-9]/.test(pass)){err('Password must contain at least one number.');return;}
    if(!/[^a-zA-Z0-9]/.test(pass)){err('Password must contain at least one special character.');return;}
    var pass2=document.getElementById('r-pass2').value;
    if(pass!==pass2){err('Passwords do not match. Please try again.');return;}
    btn.disabled=true;btn.textContent='Sending code...';
    doSendCode(email,function(){
      btn.disabled=false;btn.textContent='Create Account';
      document.getElementById('form-wrap').style.display='none';
      document.getElementById('verify-wrap').style.display='block';
      document.getElementById('verify-email-display').textContent=email;
      vcells.forEach(function(c){c.value='';});vcells[0].focus();
      startResend();
    });
    setTimeout(function(){btn.disabled=false;btn.textContent='Create Account';},10000);
  };
  // ── Step 2: verify code, then create account ─────────────────────
  document.getElementById('verify-btn').onclick=function(){
    verrHide();
    var code=vcells.map(function(c){return c.value;}).join('');
    if(code.length<6){verrShow('Enter all 6 digits.');return;}
    var email=g('r-email').trim(),vb=this;
    vb.disabled=true;vb.textContent='Verifying...';
    var fd3=new FormData();
    fd3.append('action','wpi_verify_email_code');fd3.append('email',email);fd3.append('code',code);
    fetch(ajax,{method:'POST',body:fd3}).then(function(r){return r.json();})
      .then(function(d){
        if(!d.success){vb.disabled=false;vb.textContent='Verify & Create Account';verrShow((d.data&&d.data.message)||'Incorrect code.');return;}
        vb.textContent='Creating account...';
        var first=g('r-first').trim(),last=g('r-last').trim(),pass=document.getElementById('r-pass').value;
        var fd4=new FormData();
        fd4.append('action','wpi_register_user');fd4.append('first_name',first);
        fd4.append('last_name',last);fd4.append('email',email);fd4.append('password',pass);
        fetch(ajax,{method:'POST',body:fd4}).then(function(r){return r.json();})
          .then(function(d2){
            vb.disabled=false;vb.textContent='Verify & Create Account';
            if(!d2.success){verrShow((d2.data&&d2.data.message)||'Registration failed.');return;}
            document.getElementById('verify-wrap').style.display='none';
            document.getElementById('success-wrap').style.display='block';
          }).catch(function(){vb.disabled=false;verrShow('Network error.');});
      }).catch(function(){vb.disabled=false;vb.textContent='Verify & Create Account';verrShow('Network error.');});
  };
})();
</script>

    <script>
      // wpi:logout-click-handler
      document.addEventListener('click', function(e){
        var a = e.target && e.target.closest ? e.target.closest('a') : null;
        if (!a || !a.href) return;
        if (a.href.indexOf('wpi_logout=1') !== -1 && window.wpi_wtn_logout_cleanup_then_go) {
          e.preventDefault();
          window.wpi_wtn_logout_cleanup_then_go(a.href);
        }
      }, true);
    </script>


    <script>
      // wpi-force-register-device-panel
      function wpiForceRegisterThisDevice(showAlert) {
        function done(msg) {
          console.log('[Audit4me FCM Force Register]', msg);
          var el = document.getElementById('wpi-force-register-status');
          if (el) el.textContent = msg;
          if (showAlert) alert(msg);
        }
        try {
          if (!window.wpInspector || !wpInspector.user || !wpInspector.user.id) {
            done('No logged-in Audit4me user found.');
            return;
          }
          if (!(window.WTN && window.WTN.Firebase && window.WTN.Firebase.Messaging)) {
            done('WTN Firebase bridge not available. Open inside the WebToNative Android app.');
            return;
          }
          window.WTN.Firebase.Messaging.getFCMToken({
            callback: function(data) {
              var token = data && data.token ? data.token : '';
              if (!token) {
                done('No FCM token returned from WebToNative.');
                return;
              }
              var fd = new FormData();
              fd.append('action', 'wpi_wtn_register');
              fd.append('nonce', wpInspector.nonce);
              fd.append('body', JSON.stringify({
                fcm_token: token,
                user_id: wpInspector.user.id,
                force: 1
              }));
              fetch((wpInspector.ajaxUrl || wpInspector.ajax), {method:'POST', credentials:'include', body:fd})
                .then(function(r){ return r.json(); })
                .then(function(j){
                  var d = j && (j.data || j);
                  if (j && j.success === false) throw new Error((d && d.message) || 'Registration failed');
                  done('Device registered to user ID ' + (d.uid || wpInspector.user.id) + ' successfully.');
                })
                .catch(function(e){ done('Register failed: ' + e.message); });
            }
          });
        } catch(e) {
          done('Register error: ' + e.message);
        }
      }

      function wpiAddForceRegisterButton() {
        if (document.getElementById('wpi-force-register-device-panel')) return;
        var panel = document.createElement('div');
        panel.id = 'wpi-force-register-device-panel';
        panel.style.cssText = 'position:fixed;right:12px;bottom:12px;z-index:999999;background:#fff;border:1px solid #cbd5e1;border-radius:12px;padding:10px;box-shadow:0 8px 20px rgba(0,0,0,.18);font-family:Arial,sans-serif;max-width:280px;';
        panel.innerHTML =
          '<div style="font-weight:700;font-size:13px;margin-bottom:6px;">Audit4me Device Push</div>' +
          '<button type="button" onclick="wpiForceRegisterThisDevice(true)" style="background:#2563eb;color:#fff;border:0;border-radius:8px;padding:8px 10px;font-weight:700;">Force Register This Device</button>' +
          '<div id="wpi-force-register-status" style="font-size:11px;color:#475569;margin-top:6px;">Logged in user: ' + ((window.wpInspector && wpInspector.user && wpInspector.user.id) || '?') + '</div>';
        document.body.appendChild(panel);
      }

      window.addEventListener('load', function(){
        var isPushTest = location.search.indexOf('push_test=1') !== -1;
        var isWTN = !!(window.WTN);
        if (isPushTest) setTimeout(wpiAddForceRegisterButton, 1200);
      });
    </script>


    <script>
      // wpi-auto-force-register-on-load
      window.addEventListener('load', function(){
        setTimeout(function(){
          try {
            if (!window.wpInspector || !wpInspector.user || !wpInspector.user.id) return;
            if (typeof window.wpiForceRegisterThisDevice !== 'function') return;

            var uid = String(wpInspector.user.id);
            var key = 'wpi_auto_fcm_registered_uid';
            var last = '';
            try { last = localStorage.getItem(key) || ''; } catch(e) {}

            // Always register once on load, and definitely when user changes.
            // This reassigns the device token from old user to current user automatically.
            window.wpiForceRegisterThisDevice(false);

            try { localStorage.setItem(key, uid); } catch(e) {}
          } catch(e) {
            console.log('[Audit4me auto FCM register error]', e.message);
          }
        }, 350);
      });
    </script>


<script>
// instant-login-fcm-register
(function(){
  function wpiInstantRegisterAfterLogin(){
    try{
      if(typeof window.wpiForceRegisterThisDevice === 'function'){
        setTimeout(function(){
          window.wpiForceRegisterThisDevice(false);
        }, 150);
      }
    }catch(e){}
  }

  document.addEventListener('submit', function(e){
    var f = e.target;
    if(!f) return;
    var t = (f.className || '') + ' ' + (f.id || '');
    if(/login/i.test(t)){
      setTimeout(wpiInstantRegisterAfterLogin, 800);
      setTimeout(wpiInstantRegisterAfterLogin, 2000);
    }
  }, true);

  window.wpiInstantRegisterAfterLogin = wpiInstantRegisterAfterLogin;
})();
</script>

</body>
</html>
<?php
}

function wpi_render_forgot_username_page() {
    $icon_url     = WPI_PLUGIN_URL . 'assets/icons/app-icon-180.png';
    $manifest_url = home_url( '/?wpi_manifest=1' );
    $sent = false;
    $error = '';

    if ( isset($_POST['wpi_forgot_user_submit']) ) {
        $email = sanitize_email( $_POST['email'] ?? '' );
        if ( ! is_email($email) ) {
            $error = 'Please enter a valid email address.';
        } else {
            $user = get_user_by( 'email', $email );
            if ( $user ) {
                $blogname = get_bloginfo('name');
                $message  = "Hi,

Your username for {$blogname} is:

    {$user->user_login}

You can sign in at: " . home_url('/?wpi=1') . "

If you did not request this, please ignore this email.

— The {$blogname} Team";
                wp_mail( $email, "Your {$blogname} Username", $message );
            }
            // Always show success (don't reveal if email exists)
            $sent = true;
        }
    }

    nocache_headers();
    header( 'Content-Type: text/html; charset=UTF-8' );
    ?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>Audit4me — Forgot Username</title>
    <link rel="manifest" href="<?php echo esc_url($manifest_url); ?>">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="Audit4me">
    <link rel="apple-touch-icon" href="<?php echo esc_url($icon_url); ?>">
    <meta name="theme-color" content="#1a3a5c">
    <style>
        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
        html,body{height:100%;min-height:100dvh;background:linear-gradient(160deg,#0f2440 0%,#1a3a5c 50%,#1e4976 100%);
            font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;
            display:flex;align-items:center;justify-content:center;padding:20px;}
        .card{background:#fff;border-radius:20px;padding:40px 36px;width:100%;max-width:400px;box-shadow:0 24px 80px rgba(0,0,0,.35);}
        .brand{display:flex;align-items:center;gap:12px;margin-bottom:28px;}
        .brand img{width:44px;height:44px;border-radius:10px;}
        .brand-name{font-size:20px;font-weight:800;color:#0f2440;}
        .brand-tag{font-size:11px;color:#64748b;}
        h1{font-size:18px;font-weight:700;color:#0f172a;margin-bottom:6px;}
        .sub{font-size:13px;color:#64748b;margin-bottom:24px;line-height:1.5;}
        label{display:block;font-size:12px;font-weight:700;color:#374151;margin-bottom:6px;text-transform:uppercase;letter-spacing:.5px;}
        input[type=email]{width:100%;padding:13px 16px;border-radius:10px;border:1.5px solid #e2e8f0;
            font-size:15px;color:#0f172a;outline:none;margin-bottom:16px;-webkit-appearance:none;background:#fff;}
        input[type=email]:focus{border-color:#1a3a5c;box-shadow:0 0 0 3px rgba(26,58,92,.1);}
        .btn{width:100%;padding:14px;border-radius:10px;border:none;background:#1a3a5c;color:#fff;
            font-size:15px;font-weight:700;cursor:pointer;-webkit-appearance:none;}
        .btn:active{background:#0d1f36;}
        .success{background:#f0fdf4;color:#166534;border:1.5px solid #bbf7d0;border-radius:10px;
            padding:14px 16px;margin-bottom:16px;font-size:14px;line-height:1.5;}
        .error{background:#fef2f2;color:#dc2626;border:1.5px solid #fca5a5;border-radius:10px;
            padding:10px 14px;margin-bottom:16px;font-size:13px;}
        .back{text-align:center;margin-top:18px;font-size:13px;}
        .back a{color:#1a3a5c;text-decoration:none;font-weight:600;}
        .footer{text-align:center;margin-top:28px;font-size:12px;color:#94a3b8;}
    </style>
</head>
<body>
<div class="card">
    <div class="brand">
        <img src="<?php echo esc_url($icon_url); ?>" alt="Audit4me">
        <div><div class="brand-name">Audit4me</div><div class="brand-tag">Inspection &amp; Audit Platform</div></div>
    </div>
    <h1>Forgot your username?</h1>
    <p class="sub">Enter your email address and we'll send your username to your inbox.</p>

    <?php if ($sent): ?>
        <div class="success">
            ✅ If an account exists for that email, your username has been sent. Please check your inbox (and spam folder).
        </div>
        <div class="back"><a href="<?php echo esc_url(home_url('/?wpi=1')); ?>">← Back to Sign In</a></div>
    <?php else: ?>
        <?php if ($error): ?>
            <div class="error"><?php echo esc_html($error); ?></div>
        <?php endif; ?>
        <form method="post">
            <?php wp_nonce_field('wpi_forgot_user','wpi_fu_nonce'); ?>
            <label for="wpi-email">Your Email Address</label>
            <input type="email" id="wpi-email" name="email" autocomplete="email" required>
            <input type="hidden" name="wpi_forgot_user_submit" value="1">
            <button type="submit" class="btn">Send My Username →</button>
        </form>
        <div class="back"><a href="<?php echo esc_url(home_url('/?wpi=1')); ?>">← Back to Sign In</a></div>
    <?php endif; ?>
    <div class="footer">© <?php echo esc_html(date('Y')); ?> Audit4me · All rights reserved</div>
</div>

    <script>
      // wpi:logout-click-handler
      document.addEventListener('click', function(e){
        var a = e.target && e.target.closest ? e.target.closest('a') : null;
        if (!a || !a.href) return;
        if (a.href.indexOf('wpi_logout=1') !== -1 && window.wpi_wtn_logout_cleanup_then_go) {
          e.preventDefault();
          window.wpi_wtn_logout_cleanup_then_go(a.href);
        }
      }, true);
    </script>


    <script>
      // wpi-force-register-device-panel
      function wpiForceRegisterThisDevice(showAlert) {
        function done(msg) {
          console.log('[Audit4me FCM Force Register]', msg);
          var el = document.getElementById('wpi-force-register-status');
          if (el) el.textContent = msg;
          if (showAlert) alert(msg);
        }
        try {
          if (!window.wpInspector || !wpInspector.user || !wpInspector.user.id) {
            done('No logged-in Audit4me user found.');
            return;
          }
          if (!(window.WTN && window.WTN.Firebase && window.WTN.Firebase.Messaging)) {
            done('WTN Firebase bridge not available. Open inside the WebToNative Android app.');
            return;
          }
          window.WTN.Firebase.Messaging.getFCMToken({
            callback: function(data) {
              var token = data && data.token ? data.token : '';
              if (!token) {
                done('No FCM token returned from WebToNative.');
                return;
              }
              var fd = new FormData();
              fd.append('action', 'wpi_wtn_register');
              fd.append('nonce', wpInspector.nonce);
              fd.append('body', JSON.stringify({
                fcm_token: token,
                user_id: wpInspector.user.id,
                force: 1
              }));
              fetch((wpInspector.ajaxUrl || wpInspector.ajax), {method:'POST', credentials:'include', body:fd})
                .then(function(r){ return r.json(); })
                .then(function(j){
                  var d = j && (j.data || j);
                  if (j && j.success === false) throw new Error((d && d.message) || 'Registration failed');
                  done('Device registered to user ID ' + (d.uid || wpInspector.user.id) + ' successfully.');
                })
                .catch(function(e){ done('Register failed: ' + e.message); });
            }
          });
        } catch(e) {
          done('Register error: ' + e.message);
        }
      }

      function wpiAddForceRegisterButton() {
        if (document.getElementById('wpi-force-register-device-panel')) return;
        var panel = document.createElement('div');
        panel.id = 'wpi-force-register-device-panel';
        panel.style.cssText = 'position:fixed;right:12px;bottom:12px;z-index:999999;background:#fff;border:1px solid #cbd5e1;border-radius:12px;padding:10px;box-shadow:0 8px 20px rgba(0,0,0,.18);font-family:Arial,sans-serif;max-width:280px;';
        panel.innerHTML =
          '<div style="font-weight:700;font-size:13px;margin-bottom:6px;">Audit4me Device Push</div>' +
          '<button type="button" onclick="wpiForceRegisterThisDevice(true)" style="background:#2563eb;color:#fff;border:0;border-radius:8px;padding:8px 10px;font-weight:700;">Force Register This Device</button>' +
          '<div id="wpi-force-register-status" style="font-size:11px;color:#475569;margin-top:6px;">Logged in user: ' + ((window.wpInspector && wpInspector.user && wpInspector.user.id) || '?') + '</div>';
        document.body.appendChild(panel);
      }

      window.addEventListener('load', function(){
        var isPushTest = location.search.indexOf('push_test=1') !== -1;
        var isWTN = !!(window.WTN);
        if (isPushTest) setTimeout(wpiAddForceRegisterButton, 1200);
      });
    </script>


    <script>
      // wpi-auto-force-register-on-load
      window.addEventListener('load', function(){
        setTimeout(function(){
          try {
            if (!window.wpInspector || !wpInspector.user || !wpInspector.user.id) return;
            if (typeof window.wpiForceRegisterThisDevice !== 'function') return;

            var uid = String(wpInspector.user.id);
            var key = 'wpi_auto_fcm_registered_uid';
            var last = '';
            try { last = localStorage.getItem(key) || ''; } catch(e) {}

            // Always register once on load, and definitely when user changes.
            // This reassigns the device token from old user to current user automatically.
            window.wpiForceRegisterThisDevice(false);

            try { localStorage.setItem(key, uid); } catch(e) {}
          } catch(e) {
            console.log('[Audit4me auto FCM register error]', e.message);
          }
        }, 350);
      });
    </script>


<script>
// instant-login-fcm-register
(function(){
  function wpiInstantRegisterAfterLogin(){
    try{
      if(typeof window.wpiForceRegisterThisDevice === 'function'){
        setTimeout(function(){
          window.wpiForceRegisterThisDevice(false);
        }, 150);
      }
    }catch(e){}
  }

  document.addEventListener('submit', function(e){
    var f = e.target;
    if(!f) return;
    var t = (f.className || '') + ' ' + (f.id || '');
    if(/login/i.test(t)){
      setTimeout(wpiInstantRegisterAfterLogin, 800);
      setTimeout(wpiInstantRegisterAfterLogin, 2000);
    }
  }, true);

  window.wpiInstantRegisterAfterLogin = wpiInstantRegisterAfterLogin;
})();
</script>

</body>
</html>
    <?php
}

function wpi_render_login_page( $invite_token = '' ) {
    // Security headers for login page
    header( 'X-Frame-Options: DENY' );
    header( 'X-Content-Type-Options: nosniff' );
    header( 'Referrer-Policy: strict-origin' );
    header( 'Cache-Control: no-store, no-cache, must-revalidate' );
    $redirect_to  = home_url( '/?wpi=1' );
    $login_action = site_url( 'wp-login.php', 'login_post' );
    $lost_pw_url  = wp_lostpassword_url( $redirect_to );
    $icon_url     = WPI_PLUGIN_URL . 'assets/icons/app-icon-180.png';
    $manifest_url = home_url( '/?wpi_manifest=1' );
    $sw_url       = WPI_PLUGIN_URL . 'sw.js';

    // Clear any WP login cookies that might cause loops
    nocache_headers();
    header( 'Content-Type: text/html; charset=UTF-8' );
    header( 'Service-Worker-Allowed: /' );
    header( 'Link: <' . esc_url_raw( $sw_url ) . '>; rel="serviceworker"; scope="/"' );
    ?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="format-detection" content="telephone=no">
    <title>Audit4me — Sign In</title>
    <link rel="manifest" href="<?php echo esc_url( $manifest_url ); ?>">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Audit4me">
    <link rel="apple-touch-icon" href="<?php echo esc_url( $icon_url ); ?>">
    <meta name="theme-color" content="#1a3a5c">
    <script>
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/sw.js', { scope: '/' })
            .catch(function(){});
    }
    </script>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html {
            height: 100dvh; overflow: hidden;
            background: linear-gradient(160deg, #0f2440 0%, #1a3a5c 50%, #1e4976 100%);
        }
        body {
            height: 100dvh; overflow-y: auto; overflow-x: hidden;
            -webkit-overflow-scrolling: touch;
            background: linear-gradient(160deg, #0f2440 0%, #1a3a5c 50%, #1e4976 100%);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            display: flex; align-items: center; justify-content: center;
            padding: 20px;
            padding-top: max(20px, env(safe-area-inset-top));
            padding-bottom: max(20px, env(safe-area-inset-bottom));
        }
        .card {
            background: #fff; border-radius: 20px; padding: 36px 28px;
            width: 100%; max-width: 400px;
            box-shadow: 0 24px 80px rgba(0,0,0,.35); flex-shrink: 0;
        }
        .brand { display: flex; align-items: center; gap: 12px; margin-bottom: 32px; }
        .brand img { width: 44px; height: 44px; border-radius: 10px; }
        .brand-name { font-size: 22px; font-weight: 800; color: #0f2440; }
        .brand-tag  { font-size: 12px; color: #64748b; margin-top: 1px; }
        h1 { font-size: 20px; font-weight: 700; color: #0f172a; margin-bottom: 6px; }
        .sub { font-size: 14px; color: #64748b; margin-bottom: 28px; }
        label { display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 6px; }
        input[type=text], input[type=password] {
            width: 100%; padding: 13px 16px; border-radius: 10px;
            border: 1.5px solid #e2e8f0; font-size: 15px; color: #0f172a;
            outline: none; transition: border-color .15s; margin-bottom: 16px;
            -webkit-appearance: none; background: #fff;
        }
        input:focus { border-color: #1a3a5c; box-shadow: 0 0 0 3px rgba(26,58,92,.1); }
        .btn {
            width: 100%; padding: 14px; border-radius: 10px; border: none;
            background: #1a3a5c; color: #fff; font-size: 16px; font-weight: 700;
            cursor: pointer; margin-top: 4px; -webkit-appearance: none;
            display: flex; align-items: center; justify-content: center; gap: 10px;
            transition: background .15s, opacity .15s;
        }
        .btn:active { background: #0d1f36; }
        .btn:disabled { opacity: 0.7; cursor: not-allowed; }
        .btn .spinner {
            display: none; width: 18px; height: 18px; border-radius: 50%;
            border: 2.5px solid rgba(255,255,255,.3);
            border-top-color: #fff;
            animation: spin .7s linear infinite;
        }
        .btn.loading .spinner { display: block; }
        .btn.loading .btn-text { opacity: 0.8; }
        @keyframes spin { to { transform: rotate(360deg); } }
        .forgot { text-align: center; margin-top: 18px; font-size: 13px; }
        .forgot a { color: #1a3a5c; text-decoration: none; font-weight: 600; }
        .success-msg {
            background: #f0fdf4; color: #166534; border: 1.5px solid #bbf7d0;
            border-radius: 8px; padding: 10px 14px; font-size: 13px; margin-bottom: 16px;
        }
        .error-msg {
            background: #fef2f2; color: #dc2626; border: 1.5px solid #fca5a5;
            border-radius: 8px; padding: 10px 14px; font-size: 13px; margin-bottom: 16px;
        }
        .footer { text-align: center; margin-top: 32px; font-size: 12px; color: #94a3b8; }
    </style>
</head>
<body>
<div class="card">
    <div class="brand">
        <img src="<?php echo esc_url( $icon_url ); ?>" alt="Audit4me">
        <div>
            <div class="brand-name">Audit4me</div>
            <div class="brand-tag">Inspection &amp; Audit Platform</div>
        </div>
    </div>
    <?php if ( $invite_token ) :
        global $wpdb;
        $wpi_ensure_inv = $wpdb->prefix . 'wpi_invitations';
        $inv_row = null;
        if ( $wpdb->get_var( "SHOW TABLES LIKE '$wpi_ensure_inv'" ) === $wpi_ensure_inv ) {
            $inv_row = $wpdb->get_row( $wpdb->prepare(
                "SELECT i.email, o.name AS org_name, u.display_name AS inviter_name
                 FROM {$wpdb->prefix}wpi_invitations i
                 LEFT JOIN {$wpdb->prefix}wpi_organisations o ON o.id=i.org_id
                 LEFT JOIN {$wpdb->users} u ON u.ID=i.invited_by
                 WHERE i.token=%s AND i.status='pending' AND i.expires_at > NOW()",
                $invite_token
            ) );
        }
        if ( $inv_row ) : ?>
    <div style="background:linear-gradient(135deg,#1e3a5f 0%,#312e81 100%);border-radius:12px;padding:16px 18px;margin-bottom:24px;color:#fff;">
        <div style="font-size:10px;font-weight:700;letter-spacing:1px;opacity:.7;text-transform:uppercase;margin-bottom:6px;">Organisation Invitation</div>
        <div style="font-size:16px;font-weight:800;margin-bottom:2px;"><?php echo esc_html( $inv_row->org_name ); ?></div>
        <div style="font-size:12px;opacity:.8;margin-bottom:10px;">Invited by <?php echo esc_html( $inv_row->inviter_name ); ?></div>
        <div style="background:rgba(255,255,255,.15);border-radius:8px;padding:8px 12px;font-size:12px;">
            Sign in or register with <strong><?php echo esc_html( $inv_row->email ); ?></strong> to accept.
        </div>
    </div>
    <?php endif; endif; ?>
    <h1>Sign in to your account</h1>
    <p class="sub">Enter your credentials to access the platform</p>

    <?php
    // Show check-email message after password reset request
    $login_param = sanitize_text_field( $_GET['login'] ?? '' );
    if ( $login_param === 'checkemail' ) {
        echo '<div class="success-msg">📧 Password reset email sent — please check your inbox.</div>';
    } elseif ( $login_param ) {
        $err_msgs = array(
            'empty_username'    => 'Please enter your username or email.',
            'empty_password'    => 'Please enter your password.',
            'invalid_username'  => 'Username not found. Please check and try again.',
            'invalid_email'     => 'Email address not found. Please check and try again.',
            'incorrect_password'=> 'Incorrect password. Please try again.',
            'invalidcombo'           => 'Invalid username or password.',
            'registration_disabled'  => 'Registration is currently closed. Please contact your administrator.',
        );
        $msg = $err_msgs[ $login_param ] ?? 'Sign in failed — please check your credentials.';
        echo '<div class="error-msg">' . esc_html( $msg ) . '</div>';
    }
    ?>

    <form method="post" action="#" id="wpi-login-form">
        <input type="hidden" name="redirect_to" value="<?php echo esc_attr( $redirect_to ); ?>">
        <input type="hidden" name="testcookie" value="1">
        <input type="hidden" id="wpi_device_id" name="wpi_device_id" value="">
        <input type="hidden" id="wpi_device_info" name="wpi_device_info" value="">
        <label for="wpi-user">Email or Username</label>
        <input type="text" id="wpi-user" name="log"
               autocomplete="username" autocapitalize="none" spellcheck="false" required
               value="<?php echo isset($inv_row) && $inv_row ? esc_attr($inv_row->email) : ''; ?>">
        <label for="wpi-pass">Password</label>
        <input type="password" id="wpi-pass" name="pwd"
               autocomplete="current-password" required>
        <button type="submit" class="btn" id="wpi-signin-btn">
            <span class="spinner"></span>
            <span class="btn-text">Sign In →</span>
        </button>
    </form>

    <div class="forgot">
        <a href="<?php echo esc_url( $lost_pw_url ); ?>">Forgot your password?</a>
    </div>
    <?php
    wp_cache_delete( 'wpi_registration_enabled', 'options' );
    $reg_enabled = (bool) get_option('wpi_registration_enabled', 0);
    if ( $reg_enabled || $invite_token ) :
        $reg_url = $invite_token
            ? home_url( '/?wpi=1&action=register&wpi_invite=' . rawurlencode( $invite_token ) )
            : home_url( '/?wpi=1&action=register' ); ?>
    <div class="forgot" style="margin-top:8px;">
        <a href="<?php echo esc_url( $reg_url ); ?>" style="color:#1a3a5c;font-weight:700;"><?php echo $invite_token ? 'No account yet? Register to accept &rarr;' : 'Don\'t have an account? Create one &rarr;'; ?></a>
    </div>
    <?php endif; ?>
    <div class="footer">© <?php echo esc_html( date( 'Y' ) ); ?> Audit4me · All rights reserved</div>
</div>
<script>
(function(){
var AJAX_URL = '<?php echo esc_js( admin_url("admin-ajax.php") ); ?>';
var WPI_LOGIN_URL = '<?php echo esc_js( rest_url("wp_inspector/v1/auth") ); ?>';
var WPI_LOGIN_NONCE = '<?php echo esc_js( wp_create_nonce("wpi_login") ); ?>';
var form = document.querySelector('form');
var btn  = document.getElementById('wpi-signin-btn');
var deviceCheckDone = false;
var _loginInProgress = false;

function wpiUuid(){
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g,function(c){var r=Math.random()*16|0,v=c==='x'?r:(r&3|8);return v.toString(16);});
}
function wpiIsIOS(){
    var n=window.navigator||{}, ua=n.userAgent||'', pf=n.platform||'';
    return /iPhone|iPad|iPod/i.test(ua) || /iPhone|iPad|iPod/i.test(pf) || (pf==='MacIntel' && n.maxTouchPoints>1);
}
function wpiDeviceInfo(){
    var n=window.navigator||{}, ua=n.userAgent||'', pf=n.platform||'', os='Unknown', br='Browser';
    if (wpiIsIOS()) os = (/iPad/i.test(ua)||/iPad/i.test(pf)) ? 'iPad' : 'iPhone';
    else if (/Android/i.test(ua)) os='Android';
    else if (/Windows/i.test(ua)) os='Windows';
    else if (/Macintosh|Mac OS/i.test(ua)||/Mac/i.test(pf)) os='Mac';
    else if (/Linux/i.test(ua)) os='Linux';
    if (/CriOS|Chrome/i.test(ua) && !/Edg/i.test(ua)) br='Chrome';
    else if (/Edg/i.test(ua)) br='Edge';
    else if (/FxiOS|Firefox/i.test(ua)) br='Firefox';
    else if (/Safari/i.test(ua)) br='Safari';
    else if (/WebView|wv/i.test(ua)) br='App WebView';
    var standalone = !!(window.navigator && window.navigator.standalone) || (window.matchMedia && window.matchMedia('(display-mode: standalone)').matches);
    return {ua:ua, platform:pf, maxTouchPoints:n.maxTouchPoints||0, os:os, browser:br, standalone:standalone, displayMode:standalone?'standalone':'browser'};
}
function wpiIsDesktopWeb(){
    var info = wpiDeviceInfo();
    var haystack = String((info.ua||'')+' '+(info.os||'')+' '+(info.platform||'')+' '+(info.browser||'')).toLowerCase();
    var mobile = /iphone|ipad|ipod|android|mobile|tablet|webview|wv|app webview/.test(haystack);
    return !mobile && !info.standalone;
}
function wpiPrepareDeviceFields(){
    var id='';
    try { id = localStorage.getItem('wpi_device_id') || ''; } catch(e) {}
    if (!id) { id = wpiUuid(); try { localStorage.setItem('wpi_device_id', id); } catch(e) {} }
    var info = JSON.stringify(wpiDeviceInfo());
    var idEl = document.getElementById('wpi_device_id');
    var infoEl = document.getElementById('wpi_device_info');
    if (idEl) idEl.value = id;
    if (infoEl) infoEl.value = info;
    try {
        document.cookie = 'wpi_device_id=' + encodeURIComponent(id) + '; path=/; max-age=31536000; SameSite=Lax';
        document.cookie = 'wpi_device_info=' + encodeURIComponent(info) + '; path=/; max-age=31536000; SameSite=Lax';
    } catch(e) {}
    return {id:id, info:info};
}
wpiPrepareDeviceFields();

function setLoading(on, text) {
    btn.disabled = on;
    btn.querySelector('.btn-text').textContent = text || (on ? 'Checking…' : 'Sign In →');
    btn.classList.toggle('loading', on);
}

function showLoginError(msg) {
    removeError();
    var div = document.createElement('div');
    div.className = 'error-msg'; div.id = 'wpi-login-error';
    div.textContent = msg;
    form.parentNode.insertBefore(div, form);
}
function removeError() {
    ['wpi-device-limit-msg','wpi-login-error'].forEach(function(id){
        var el = document.getElementById(id); if (el) el.remove();
    });
}

function showDeviceLimit(data) {
    removeError();
    var div = document.createElement('div');
    div.id = 'wpi-device-limit-msg';
    div.style.cssText = 'background:#fef2f2;border:1.5px solid #fca5a5;border-radius:12px;padding:16px;margin-bottom:16px;';

    var html = '<div style="font-weight:700;font-size:14px;color:#dc2626;margin-bottom:6px;">📱 Device Limit Reached (' + data.active + '/' + data.max + ')</div>';
    html += '<div style="font-size:12px;color:#7f1d1d;margin-bottom:12px;">' + (data.can_remove ? 'Remove a device below to sign in on this device.' : 'Device limit reached. Your account is not blocked. To remove an old device, log in from the desktop web view or contact the System Owner.') + '</div>';
    html += '<div id="wpi-device-list">';
    data.devices.forEach(function(d, i) {
        var ago = '';
        try {
            var diff = Math.floor((Date.now() - new Date(d.last_active.replace(' ','T') + 'Z')) / 1000);
            ago = diff < 60 ? 'Active now' : diff < 3600 ? Math.floor(diff/60) + 'm ago' : Math.floor(diff/3600) + 'h ago';
        } catch(e) { ago = d.last_active; }
        html += '<div id="wpi-dev-' + i + '" style="display:flex;align-items:center;gap:10px;padding:10px 12px;background:#fff;border-radius:8px;margin-bottom:6px;border:1px solid #fecaca;">';
        html += '<div style="flex:1;min-width:0;">';
        html += '<div style="font-size:13px;font-weight:600;color:#1e293b;">' + escHtml(d.device_label) + '</div>';
        if (d.device_id) html += '<div style="font-size:10px;font-family:monospace;color:#6b7280;">ID: ' + escHtml(d.device_id) + '</div>';
        html += '<div style="font-size:11px;color:#9ca3af;">' + escHtml(ago) + '</div>';
        html += '</div>';
        if (data.can_remove) html += '<button type="button" data-idx="' + i + '" data-session="' + escAttr(d.session_id) + '" data-user="' + data.user_id + '" style="padding:6px 14px;border-radius:8px;border:none;background:#ef4444;color:#fff;font-size:12px;font-weight:700;cursor:pointer;flex-shrink:0;">Remove</button>';
        html += '</div>';
    });
    html += '</div>';
    div.innerHTML = html;
    div.querySelectorAll('button[data-session]').forEach(function(b){
        b.addEventListener('click', function(){
            wpiRemoveDevice(parseInt(this.getAttribute('data-idx'),10), this.getAttribute('data-session'), this.getAttribute('data-user'));
        });
    });
    form.parentNode.insertBefore(div, form);
    setLoading(false, 'Sign In →');
}

function escHtml(s) {
    return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function escAttr(s) {
    return String(s||'').replace(/'/g,"\'");
}

window.wpiRemoveDevice = function(idx, sessionKey, userId) {
    var btn2 = document.querySelector('#wpi-dev-' + idx + ' button');
    if (btn2) { btn2.disabled = true; btn2.textContent = '…'; }
    var fd = new FormData();
    fd.append('action', 'wpi_remove_device_for_login');
    fd.append('session_id', sessionKey);
    fd.append('user_id', userId);
    fd.append('device_info', JSON.stringify(wpiDeviceInfo()));
    fetch(AJAX_URL, {method:'POST', credentials:'include', body:fd})
        .then(function(r){ return r.json(); })
        .then(function(d) {
            if (d && d.success) {
                var row = document.getElementById('wpi-dev-' + idx);
                if (row) { row.style.opacity='0.4'; row.innerHTML='<div style="flex:1;font-size:12px;color:#16a34a;font-weight:600;">✓ Device removed</div>'; }
                // Re-check after short delay — if now under limit, allow login
                setTimeout(function() {
                    var log = document.getElementById('wpi-user') && document.getElementById('wpi-user').value;
                    if (!log) return;
                    var dev2 = wpiPrepareDeviceFields();
                    var fd2 = new FormData();
                    fd2.append('action', 'wpi_check_device_limit');
                    fd2.append('log', log);
                    fd2.append('device_id', dev2.id);
                    fd2.append('device_info', dev2.info);
                    fetch(AJAX_URL, {method:'POST', credentials:'include', body:fd2})
                        .then(function(r2){ return r2.json(); })
                        .then(function(d2) {
                            if (d2 && d2.data && !d2.data.at_limit) {
                                removeError();
                                deviceCheckDone = true;
                                doAjaxLogin();
                            } else if (d2 && d2.data) {
                                showDeviceLimit(d2.data);
                            }
                        }).catch(function(){});
                }, 600);
            }
        }).catch(function(){
            if (btn2) { btn2.disabled=false; btn2.textContent='Remove'; }
        });
};

function doAjaxLogin() {
    if (_loginInProgress) return;
    _loginInProgress = true;
    deviceCheckDone = true;
    setLoading(true, 'Signing in…');
    var dev = wpiPrepareDeviceFields();
    var fd = new FormData();
    fd.append('action', 'wpi_do_login');
    fd.append('wpi_u', document.getElementById('wpi-user').value.trim());
    fd.append('wpi_k', document.getElementById('wpi-pass').value);
    fd.append('device_id',   dev.id);
    fd.append('device_info', dev.info);
    fd.append('nonce', WPI_LOGIN_NONCE);
    // Try REST endpoint first; fall back to admin-ajax.php if REST returns non-JSON
    function wpiHandleLoginResponse(d) {
        if (d && d.success) {
            window.location.href = (d.data && d.data.redirect) ? d.data.redirect : '/?wpi=1';
        } else {
            _loginInProgress = false;
            deviceCheckDone = false;
            setLoading(false, 'Sign In →');
            var msg = (d && d.data && d.data.message) ? d.data.message : 'Sign in failed. Please check your credentials.';
            showLoginError(msg);
        }
    }
    function wpiAjaxLogin(fd2) {
        fd2.append('action', 'wpi_ajax_login');
        fetch(AJAX_URL, {method: 'POST', credentials: 'include', body: fd2})
            .then(function(r) { return r.text(); })
            .then(function(t) {
                var d; try { d = JSON.parse(t); } catch(e) { d = null; }
                if (!d) {
                    _loginInProgress = false; deviceCheckDone = false;
                    showLoginError('Sign in failed. Please try again.');
                    setLoading(false, 'Sign In →'); return;
                }
                wpiHandleLoginResponse(d);
            })
            .catch(function(err) {
                _loginInProgress = false; deviceCheckDone = false;
                setLoading(false, 'Sign In →');
                showLoginError('Could not reach the server. Please try again.');
            });
    }
    fetch(WPI_LOGIN_URL, {method: 'POST', credentials: 'include', body: fd})
        .then(function(r) { return r.text(); })
        .then(function(t) {
            var d;
            try { d = JSON.parse(t); } catch(e) {
                // REST API returned HTML (blocked, permalink issue, security plugin)
                // Fall back silently to admin-ajax.php
                var fd2 = new FormData();
                fd2.append('wpi_u', document.getElementById('wpi-user').value.trim());
                fd2.append('wpi_k', document.getElementById('wpi-pass').value);
                fd2.append('nonce', WPI_LOGIN_NONCE);
                fd2.append('rememberme', '1');
                var dev2 = wpiPrepareDeviceFields();
                fd2.append('device_id',   dev2.id);
                fd2.append('device_info', dev2.info);
                wpiAjaxLogin(fd2);
                return;
            }
            wpiHandleLoginResponse(d);
        })
        .catch(function(err) {
            _loginInProgress = false;
            deviceCheckDone = false;
            setLoading(false, 'Sign In →');
            showLoginError('Could not reach the server (' + (err && err.message ? err.message : 'fetch failed') + '). Please try again.');
            console.error('WPI login fetch error:', err);
        });
}

form.addEventListener('submit', function(e) {
    e.preventDefault(); // NEVER submit to wp-login.php - use AJAX instead
    if (deviceCheckDone) {
        doAjaxLogin();
        return;
    }
    var log = document.getElementById('wpi-user').value.trim();
    if (!log) return; // let normal validation handle it

    e.preventDefault();
    setLoading(true, 'Checking devices…');
    removeError();

    var dev = wpiPrepareDeviceFields();
    var fd = new FormData();
    fd.append('action', 'wpi_check_device_limit');
    fd.append('log', log);
    fd.append('device_id', dev.id);
    fd.append('device_info', dev.info);

    fetch(AJAX_URL, {method:'POST', credentials:'include', body:fd})
        .then(function(r){ return r.text(); })
        .then(function(t) {
            var d; try { d = JSON.parse(t); } catch(e) { d = null; }
            if (d && d.data && d.data.at_limit) {
                showDeviceLimit(d.data);
            } else {
                deviceCheckDone = true;
                doAjaxLogin();
            }
        })
        .catch(function(err) {
            // Device check failed - still try login (device check is non-critical)
            console.warn('WPI device check failed:', err);
            deviceCheckDone = true;
            doAjaxLogin();
        });
});
})();
</script>

    <script>
      // wpi:logout-click-handler
      document.addEventListener('click', function(e){
        var a = e.target && e.target.closest ? e.target.closest('a') : null;
        if (!a || !a.href) return;
        if (a.href.indexOf('wpi_logout=1') !== -1 && window.wpi_wtn_logout_cleanup_then_go) {
          e.preventDefault();
          window.wpi_wtn_logout_cleanup_then_go(a.href);
        }
      }, true);
    </script>


    <script>
      // wpi-force-register-device-panel
      function wpiForceRegisterThisDevice(showAlert) {
        function done(msg) {
          console.log('[Audit4me FCM Force Register]', msg);
          var el = document.getElementById('wpi-force-register-status');
          if (el) el.textContent = msg;
          if (showAlert) alert(msg);
        }
        try {
          if (!window.wpInspector || !wpInspector.user || !wpInspector.user.id) {
            done('No logged-in Audit4me user found.');
            return;
          }
          if (!(window.WTN && window.WTN.Firebase && window.WTN.Firebase.Messaging)) {
            done('WTN Firebase bridge not available. Open inside the WebToNative Android app.');
            return;
          }
          window.WTN.Firebase.Messaging.getFCMToken({
            callback: function(data) {
              var token = data && data.token ? data.token : '';
              if (!token) {
                done('No FCM token returned from WebToNative.');
                return;
              }
              var fd = new FormData();
              fd.append('action', 'wpi_wtn_register');
              fd.append('nonce', wpInspector.nonce);
              fd.append('body', JSON.stringify({
                fcm_token: token,
                user_id: wpInspector.user.id,
                force: 1
              }));
              fetch((wpInspector.ajaxUrl || wpInspector.ajax), {method:'POST', credentials:'include', body:fd})
                .then(function(r){ return r.json(); })
                .then(function(j){
                  var d = j && (j.data || j);
                  if (j && j.success === false) throw new Error((d && d.message) || 'Registration failed');
                  done('Device registered to user ID ' + (d.uid || wpInspector.user.id) + ' successfully.');
                })
                .catch(function(e){ done('Register failed: ' + e.message); });
            }
          });
        } catch(e) {
          done('Register error: ' + e.message);
        }
      }

      function wpiAddForceRegisterButton() {
        if (document.getElementById('wpi-force-register-device-panel')) return;
        var panel = document.createElement('div');
        panel.id = 'wpi-force-register-device-panel';
        panel.style.cssText = 'position:fixed;right:12px;bottom:12px;z-index:999999;background:#fff;border:1px solid #cbd5e1;border-radius:12px;padding:10px;box-shadow:0 8px 20px rgba(0,0,0,.18);font-family:Arial,sans-serif;max-width:280px;';
        panel.innerHTML =
          '<div style="font-weight:700;font-size:13px;margin-bottom:6px;">Audit4me Device Push</div>' +
          '<button type="button" onclick="wpiForceRegisterThisDevice(true)" style="background:#2563eb;color:#fff;border:0;border-radius:8px;padding:8px 10px;font-weight:700;">Force Register This Device</button>' +
          '<div id="wpi-force-register-status" style="font-size:11px;color:#475569;margin-top:6px;">Logged in user: ' + ((window.wpInspector && wpInspector.user && wpInspector.user.id) || '?') + '</div>';
        document.body.appendChild(panel);
      }

      window.addEventListener('load', function(){
        var isPushTest = location.search.indexOf('push_test=1') !== -1;
        var isWTN = !!(window.WTN);
        if (isPushTest) setTimeout(wpiAddForceRegisterButton, 1200);
      });
    </script>


    <script>
      // wpi-auto-force-register-on-load
      window.addEventListener('load', function(){
        setTimeout(function(){
          try {
            if (!window.wpInspector || !wpInspector.user || !wpInspector.user.id) return;
            if (typeof window.wpiForceRegisterThisDevice !== 'function') return;

            var uid = String(wpInspector.user.id);
            var key = 'wpi_auto_fcm_registered_uid';
            var last = '';
            try { last = localStorage.getItem(key) || ''; } catch(e) {}

            // Always register once on load, and definitely when user changes.
            // This reassigns the device token from old user to current user automatically.
            window.wpiForceRegisterThisDevice(false);

            try { localStorage.setItem(key, uid); } catch(e) {}
          } catch(e) {
            console.log('[Audit4me auto FCM register error]', e.message);
          }
        }, 350);
      });
    </script>


<script>
// instant-login-fcm-register
(function(){
  function wpiInstantRegisterAfterLogin(){
    try{
      if(typeof window.wpiForceRegisterThisDevice === 'function'){
        setTimeout(function(){
          window.wpiForceRegisterThisDevice(false);
        }, 150);
      }
    }catch(e){}
  }

  document.addEventListener('submit', function(e){
    var f = e.target;
    if(!f) return;
    var t = (f.className || '') + ' ' + (f.id || '');
    if(/login/i.test(t)){
      setTimeout(wpiInstantRegisterAfterLogin, 800);
      setTimeout(wpiInstantRegisterAfterLogin, 2000);
    }
  }, true);

  window.wpiInstantRegisterAfterLogin = wpiInstantRegisterAfterLogin;
})();
</script>

</body>
</html>
    <?php
}

function wpi_render_app_page() {
    nocache_headers();
    header( 'Content-Type: text/html; charset=UTF-8' );

    $uid  = get_current_user_id();
    $u    = wp_get_current_user();
    $fn   = get_user_meta( $uid, 'first_name', true );
    $ln   = get_user_meta( $uid, 'last_name',  true );
    $full = trim( "$fn $ln" ) ?: $u->display_name;

    global $wpdb;
    $wpi_role = 'standard';
    if ( ! $u->has_cap( 'manage_options' ) ) {
        $table = $wpdb->prefix . 'wpi_user_roles';
        if ( $wpdb->get_var( "SHOW TABLES LIKE '$table'" ) === $table ) {
            $row = $wpdb->get_var( $wpdb->prepare( "SELECT role FROM $table WHERE user_id=%d", $uid ) );
            if ( $row ) $wpi_role = $row;
        }
        // Elevate org admin
        if ( in_array( $wpi_role, array( 'standard', 'guest' ) ) ) {
            $org_table = $wpdb->prefix . 'wpi_org_users';
            if ( $wpdb->get_var( "SHOW TABLES LIKE '$org_table'" ) === $org_table ) {
                $org_role = $wpdb->get_var( $wpdb->prepare(
                    "SELECT role FROM $org_table WHERE user_id=%d AND role='admin' LIMIT 1", $uid
                ) );
                if ( $org_role === 'admin' ) $wpi_role = 'administrator';
            }
        }
    } else {
        $wpi_role = 'administrator';
    }

    $is_system_owner = WPI_Admin::is_system_owner( $uid );
    $org_id          = WPI_Admin::get_user_org_id( $uid );
    $org_licence     = $is_system_owner ? array( 'status' => 'active' ) : WPI_Ajax::get_org_licence( $org_id );

    $user_tz = get_user_meta( $uid, 'wpi_timezone', true );
    if ( ! $user_tz ) {
        $user_tz = get_option( 'timezone_string', '' ) ?: 'UTC';
    }

    $js_file  = WPI_PLUGIN_DIR . 'assets/js/app.js';
    $ver      = file_exists( $js_file ) ? filemtime( $js_file ) . '-' . WPI_VERSION : WPI_VERSION;
    $js_url   = WPI_PLUGIN_URL . 'assets/js/app.js?ver=' . urlencode( $ver );
    $css_url  = WPI_PLUGIN_URL . 'assets/css/app.css?ver=' . urlencode( $ver );
    $icon_url = WPI_PLUGIN_URL . 'assets/icons/app-icon-180.png';
    $icon_192 = WPI_PLUGIN_URL . 'assets/icons/icon-192x192.png';
    $sw_url   = WPI_PLUGIN_URL . 'sw.js';
    $manifest = home_url( '/?wpi_manifest=1' );

    $data = array(
        'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
        'nonce'         => wp_create_nonce( 'wpi_nonce' ),
        'pluginUrl'     => WPI_PLUGIN_URL,
        'homeUrl'       => home_url( '/' ),
        'utcOffset'     => (float) get_option( 'gmt_offset' ),
        'logoutUrl'     => home_url( '/?wpi=1&wpi_logout=1&_wpnonce=' . wp_create_nonce( 'wpi_logout' ) ),
        'userTimezone'  => $user_tz,
        'isSystemOwner' => $is_system_owner,
        'isBasicAccess' => ! $is_system_owner && (bool) get_user_meta( $uid, 'wpi_access_basic', true ),
        'billingUrl'    => get_option( 'wpi_billing_url', 'https://audit4me.net/#pricing' ),
        'orgLicence'    => $org_licence,
        'orgId'         => $org_id,
        'user'          => array(
            'id'      => $uid,
            'name'    => $full,
            'email'   => $u->user_email,
            'login'   => $u->user_login,
            'isAdmin' => (bool) current_user_can( 'manage_options' ),
            'wpiRole' => $wpi_role,
        ),
        'pendingInviteToken' => (function() use ($uid) {
            global $wpdb;
            // Check URL param first
            $url_token = sanitize_text_field( $_GET['wpi_invite'] ?? '' );
            // Check transient
            $t = get_transient( 'wpi_pending_invite_' . $uid );
            // Check cookie
            $c = sanitize_text_field( $_COOKIE['wpi_pending_invite'] ?? '' );
            $token = $url_token ?: $t ?: $c;
            if ( ! $token ) return '';
            // Verify the token is still pending (not already accepted/cancelled)
            $table = $wpdb->prefix . 'wpi_invitations';
            $exists = $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM {$table} WHERE token=%s AND status='pending' AND expires_at > NOW()",
                $token
            ) );
            if ( ! $exists ) {
                // Token no longer valid - clear it
                delete_transient( 'wpi_pending_invite_' . $uid );
                setcookie( 'wpi_pending_invite', '', time()-3600, '/', '', is_ssl(), true );
                return '';
            }
            // Clear transient/cookie since we have it now
            if ( $t ) delete_transient( 'wpi_pending_invite_' . $uid );
            if ( $c ) setcookie( 'wpi_pending_invite', '', time()-3600, '/', '', is_ssl(), true );
            return $token;
        })(),
    );
    $install_url = home_url('/?wpi=1&action=install');
    ?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>Audit4me</title>
    <link rel="manifest" href="<?php echo esc_url( $manifest ); ?>">
    <!-- PWA iOS -->
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Audit4me">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="application-name" content="Audit4me">
    <!-- Icons -->
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo esc_url( $icon_url ); ?>">
    <link rel="apple-touch-icon" sizes="192x192" href="<?php echo esc_url( $icon_192 ); ?>">
    <link rel="icon" type="image/png" sizes="192x192" href="<?php echo esc_url( $icon_192 ); ?>">
    <!-- Theme -->
    <meta name="theme-color" content="#1a3a5c">
    <!-- Description -->
    <meta name="description" content="Professional inspection and audit management platform.">
    <link rel="stylesheet" href="<?php echo esc_url( $css_url ); ?>">
    <style>
        html, body { margin: 0; padding: 0; background: #1a3a5c; height: 100%; overflow: hidden; }
        #wp-inspector-root { height: 100%; }
        #wpi-install-banner{
            position:fixed;bottom:0;left:0;right:0;
            background:linear-gradient(135deg,#1a3a5c,#1e4976);
            color:#fff;display:none;align-items:center;gap:12px;
            padding:12px 16px;
            padding-bottom:calc(12px + env(safe-area-inset-bottom,0px));
            box-shadow:0 -4px 24px rgba(0,0,0,.35);z-index:9999;
        }
        #wpi-install-banner img{width:40px;height:40px;border-radius:10px;flex-shrink:0;}
        #wpi-install-banner .ib-text{flex:1;min-width:0;}
        #wpi-install-banner .ib-title{font-size:14px;font-weight:700;}
        #wpi-install-banner .ib-sub{font-size:11px;opacity:.75;margin-top:2px;}
        #wpi-install-banner .ib-btn{background:#fff;color:#1a3a5c;border:none;
            border-radius:20px;padding:8px 16px;font-size:13px;font-weight:700;
            cursor:pointer;flex-shrink:0;}
        #wpi-install-banner .ib-close{background:none;border:none;color:rgba(255,255,255,.6);
            font-size:22px;cursor:pointer;padding:0 0 0 4px;flex-shrink:0;line-height:1;}
    </style>
    <script>
    if ('serviceWorker' in navigator) {
        var swUrl = '/sw.js'; // Served from root for iOS push compatibility
        var ver   = '<?php echo esc_js( $ver ); ?>';
        function postVer(reg) {
            var msg = { type: 'SET_VERSION', version: ver };
            if (reg.active)  reg.active.postMessage(msg);
            if (reg.waiting) reg.waiting.postMessage(msg);
            reg.addEventListener('updatefound', function() {
                var sw = reg.installing;
                if (sw) sw.addEventListener('statechange', function() {
                    if (sw.state === 'activated') sw.postMessage(msg);
                });
            });
        }
        // Unregister any old SW registrations (plugin path) before registering root SW
        navigator.serviceWorker.getRegistrations().then(function(regs) {
            regs.forEach(function(reg) {
                if (reg.scope !== window.location.origin + '/') {
                    reg.unregister();
                }
            });
        }).catch(function(){});

        navigator.serviceWorker.register(swUrl, { scope: '/' })
          .then(function(reg) {
            postVer(reg);
            // Register periodic background sync (refresh cache every 24h)
            if ('periodicSync' in reg) {
              navigator.permissions.query({ name: 'periodic-background-sync' }).then(function(status) {
                if (status.state === 'granted') {
                  reg.periodicSync.register('wpi-refresh', { minInterval: 24 * 60 * 60 * 1000 }).catch(function(){});
                }
              }).catch(function(){});
            }
            // Register background sync tag for offline inspection saves
            if ('sync' in reg) {
              reg.sync.register('wpi-sync-inspections').catch(function(){});
            }
          })
          .catch(function(err) {
            // iOS strict scope: if Service-Worker-Allowed header missing, fall back to plugin scope
            if (err && err.name === 'SecurityError') {
                navigator.serviceWorker.register(swUrl, { scope: '/wp-content/plugins/wp-inspector/' })
                  .then(postVer).catch(function(){});
            }
          });
    }
    var _wpiDeferredPrompt = null;
    window.addEventListener('beforeinstallprompt', function(e) {
        e.preventDefault();
        _wpiDeferredPrompt = e;
        var dismissed = localStorage.getItem('wpi_install_dismissed');
        var installed = window.matchMedia('(display-mode:standalone)').matches || navigator.standalone;
        if (!dismissed && !installed) {
            var b = document.getElementById('wpi-install-banner');
            if (b) b.style.display = 'flex';
        }
    });
    window.addEventListener('appinstalled', function() {
        var b = document.getElementById('wpi-install-banner');
        if (b) b.style.display = 'none';
        _wpiDeferredPrompt = null;
        localStorage.removeItem('wpi_install_dismissed');
    });
    </script>
    <!-- React + ReactDOM (required outside WP admin context) -->
    <script crossorigin src="https://unpkg.com/react@18/umd/react.production.min.js"></script>
    <script crossorigin src="https://unpkg.com/react-dom@18/umd/react-dom.production.min.js"></script>
    <script>
    // Bridge: expose React as window.wp.element so app.js finds it
    window.wp = window.wp || {};
    window.wp.element = {
        createElement:    React.createElement.bind(React),
        createRoot:       ReactDOM.createRoot.bind(ReactDOM),
        render:           ReactDOM.render ? ReactDOM.render.bind(ReactDOM) : null,
        useState:         React.useState.bind(React),
        useEffect:        React.useEffect.bind(React),
        useRef:           React.useRef.bind(React),
        useCallback:      React.useCallback.bind(React),
        useMemo:          React.useMemo.bind(React),
        useContext:       React.useContext.bind(React),
        createContext:    React.createContext.bind(React),
        Fragment:         React.Fragment,
        Component:        React.Component,
    };
    </script>
</head>
<body>
    <div id="wp-inspector-root"></div>

    <!-- PWA Install Banner (Android/Desktop Chrome) -->
    <div id="wpi-install-banner">
        <img src="<?php echo esc_url($icon_url); ?>" alt="Audit4me">
        <div class="ib-text">
            <div class="ib-title">Install Audit4me</div>
            <div class="ib-sub">Add to your home screen for the best experience</div>
        </div>
        <button class="ib-btn" onclick="
            if(_wpiDeferredPrompt){
                _wpiDeferredPrompt.prompt();
                _wpiDeferredPrompt.userChoice.then(function(r){
                    _wpiDeferredPrompt=null;
                    document.getElementById('wpi-install-banner').style.display='none';
                });
            }
        ">Install</button>
        <button class="ib-close" onclick="
            document.getElementById('wpi-install-banner').style.display='none';
            localStorage.setItem('wpi_install_dismissed','1');
        ">×</button>
    </div>

    <script>var wpInspector = <?php echo wp_json_encode( $data ); ?>;</script>
    <script src="<?php echo esc_url( $js_url ); ?>"></script>
    <script src="https://unpkg.com/webtonative@1.0.96/webtonative.min.js"></script>
    <script>
      // WebToNative Firebase push token registration
      function wpi_register_fcm_token(token) {
        if (!token || !window.wpInspector) return;
        var fd = new FormData();
        fd.append('action', 'wpi_wtn_register');
        fd.append('nonce', wpInspector.nonce);
        fd.append('body', JSON.stringify({fcm_token: token, user_id: (wpInspector.user && wpInspector.user.id) ? wpInspector.user.id : 0}));
        fetch((wpInspector.ajaxUrl || wpInspector.ajax), {method:'POST', credentials:'include', body:fd}).catch(function(){});
      }
      // Get FCM token via WebToNative JS bridge
      
      function wpi_unregister_fcm_token(token) {
        if (!token || !window.wpInspector) return Promise.resolve();
        var fd = new FormData();
        fd.append('action', 'wpi_wtn_unregister');
        fd.append('nonce', wpInspector.nonce);
        fd.append('body', JSON.stringify({
          fcm_token: token,
          user_id: (wpInspector.user && wpInspector.user.id) ? wpInspector.user.id : 0
        }));
        return fetch((wpInspector.ajaxUrl || wpInspector.ajax), {method:'POST', credentials:'include', body:fd}).catch(function(){});
      }

      function wpi_wtn_logout_cleanup_then_go(url) {
        try {
          var done = false;
          function finish(){
            if (done) return;
            done = true;
            try {
              if (window.WTN && typeof window.WTN.logout === 'function') window.WTN.logout();
              if (window.WebToNative && typeof window.WebToNative.logout === 'function') window.WebToNative.logout();
            } catch(e) {}
            window.location.href = url;
          }
          if (window.WTN && window.WTN.Firebase && window.WTN.Firebase.Messaging) {
            window.WTN.Firebase.Messaging.getFCMToken({
              callback: function(data) {
                var token = data && data.token ? data.token : '';
                wpi_unregister_fcm_token(token).finally(function(){ setTimeout(finish, 150); });
              }
            });
            setTimeout(finish, 1800);
          } else {
            finish();
          }
        } catch(e) {
          window.location.href = url;
        }
      }
      window.wpi_wtn_logout_cleanup_then_go = wpi_wtn_logout_cleanup_then_go;

      try {
        var currentAppUid = (window.wpInspector && window.wpInspector.user && window.wpInspector.user.id) ? String(window.wpInspector.user.id) : '';
        var lastUid = localStorage.getItem('wpi_last_push_uid') || '';
        if (currentAppUid && currentAppUid !== lastUid) {
          localStorage.setItem('wpi_last_push_uid', currentAppUid);
          window.__wpi_force_token_reregister = true;
        }
      } catch(e) {}

      function wpi_init_wtn_firebase() {
        if (window.WTN && window.WTN.Firebase && window.WTN.Firebase.Messaging) {
          window.WTN.Firebase.Messaging.getFCMToken({
            callback: function(data) {
              if (data && data.token) wpi_register_fcm_token(data.token);
            }
          });
          return true;
        }
        return false;
      }
      // Try on load, then retry every second for 10 seconds
      var wpi_wtn_attempts = 0;
      function wpi_try_wtn() {
        if (wpi_init_wtn_firebase()) return;
        if (++wpi_wtn_attempts < 10) setTimeout(wpi_try_wtn, 1000);
      }
      window.addEventListener('load', function(){ setTimeout(wpi_try_wtn, 500); });
    </script>
    <script>
      window.OneSignalDeferred = window.OneSignalDeferred || [];
      OneSignalDeferred.push(async function(OneSignal) {
        try {
          await OneSignal.init({
            appId: "a9e30544-a512-4633-8ce4-114ebd13d8de",
            notifyButton: { enable: false },
          });

          // Set external user ID so we can target by WP user ID
          var wpUserId = '<?php echo get_current_user_id(); ?>';
          if (wpUserId && wpUserId !== '0') {
            OneSignal.login(wpUserId);
          }

          // Request permission if not already granted
          var permission = await OneSignal.Notifications.permission;
          if (!permission) {
            await OneSignal.Notifications.requestPermission();
          }

          // Register player ID with our server as backup
          function registerPlayer(pid) {
            if (!pid || !window.wpInspector) return;
            var fd = new FormData();
            fd.append('action', 'wpi_onesignal_register');
            fd.append('nonce', wpInspector.nonce);
            fd.append('body', JSON.stringify({player_id: pid}));
            fetch((wpInspector.ajaxUrl || wpInspector.ajax), {method:'POST', credentials:'include', body:fd}).catch(function(){});
          }
          registerPlayer(OneSignal.User.PushSubscription.id);
          OneSignal.User.PushSubscription.addEventListener('change', function(e){ registerPlayer(e.current.id); });

        } catch(e) { console.warn('[WPI] OneSignal init failed:', e); }
      });
    </script>

    <script>
      // wpi:logout-click-handler
      document.addEventListener('click', function(e){
        var a = e.target && e.target.closest ? e.target.closest('a') : null;
        if (!a || !a.href) return;
        if (a.href.indexOf('wpi_logout=1') !== -1 && window.wpi_wtn_logout_cleanup_then_go) {
          e.preventDefault();
          window.wpi_wtn_logout_cleanup_then_go(a.href);
        }
      }, true);
    </script>


    <script>
      // wpi-force-register-device-panel
      function wpiForceRegisterThisDevice(showAlert) {
        function done(msg) {
          console.log('[Audit4me FCM Force Register]', msg);
          var el = document.getElementById('wpi-force-register-status');
          if (el) el.textContent = msg;
          if (showAlert) alert(msg);
        }
        try {
          if (!window.wpInspector || !wpInspector.user || !wpInspector.user.id) {
            done('No logged-in Audit4me user found.');
            return;
          }
          if (!(window.WTN && window.WTN.Firebase && window.WTN.Firebase.Messaging)) {
            done('WTN Firebase bridge not available. Open inside the WebToNative Android app.');
            return;
          }
          window.WTN.Firebase.Messaging.getFCMToken({
            callback: function(data) {
              var token = data && data.token ? data.token : '';
              if (!token) {
                done('No FCM token returned from WebToNative.');
                return;
              }
              var fd = new FormData();
              fd.append('action', 'wpi_wtn_register');
              fd.append('nonce', wpInspector.nonce);
              fd.append('body', JSON.stringify({
                fcm_token: token,
                user_id: wpInspector.user.id,
                force: 1
              }));
              fetch((wpInspector.ajaxUrl || wpInspector.ajax), {method:'POST', credentials:'include', body:fd})
                .then(function(r){ return r.json(); })
                .then(function(j){
                  var d = j && (j.data || j);
                  if (j && j.success === false) throw new Error((d && d.message) || 'Registration failed');
                  done('Device registered to user ID ' + (d.uid || wpInspector.user.id) + ' successfully.');
                })
                .catch(function(e){ done('Register failed: ' + e.message); });
            }
          });
        } catch(e) {
          done('Register error: ' + e.message);
        }
      }

      function wpiAddForceRegisterButton() {
        if (document.getElementById('wpi-force-register-device-panel')) return;
        var panel = document.createElement('div');
        panel.id = 'wpi-force-register-device-panel';
        panel.style.cssText = 'position:fixed;right:12px;bottom:12px;z-index:999999;background:#fff;border:1px solid #cbd5e1;border-radius:12px;padding:10px;box-shadow:0 8px 20px rgba(0,0,0,.18);font-family:Arial,sans-serif;max-width:280px;';
        panel.innerHTML =
          '<div style="font-weight:700;font-size:13px;margin-bottom:6px;">Audit4me Device Push</div>' +
          '<button type="button" onclick="wpiForceRegisterThisDevice(true)" style="background:#2563eb;color:#fff;border:0;border-radius:8px;padding:8px 10px;font-weight:700;">Force Register This Device</button>' +
          '<div id="wpi-force-register-status" style="font-size:11px;color:#475569;margin-top:6px;">Logged in user: ' + ((window.wpInspector && wpInspector.user && wpInspector.user.id) || '?') + '</div>';
        document.body.appendChild(panel);
      }

      window.addEventListener('load', function(){
        var isPushTest = location.search.indexOf('push_test=1') !== -1;
        var isWTN = !!(window.WTN);
        if (isPushTest) setTimeout(wpiAddForceRegisterButton, 1200);
      });
    </script>


    <script>
      // wpi-auto-force-register-on-load
      window.addEventListener('load', function(){
        setTimeout(function(){
          try {
            if (!window.wpInspector || !wpInspector.user || !wpInspector.user.id) return;
            if (typeof window.wpiForceRegisterThisDevice !== 'function') return;

            var uid = String(wpInspector.user.id);
            var key = 'wpi_auto_fcm_registered_uid';
            var last = '';
            try { last = localStorage.getItem(key) || ''; } catch(e) {}

            // Always register once on load, and definitely when user changes.
            // This reassigns the device token from old user to current user automatically.
            window.wpiForceRegisterThisDevice(false);

            try { localStorage.setItem(key, uid); } catch(e) {}
          } catch(e) {
            console.log('[Audit4me auto FCM register error]', e.message);
          }
        }, 350);
      });
    </script>


<script>
// instant-login-fcm-register
(function(){
  function wpiInstantRegisterAfterLogin(){
    try{
      if(typeof window.wpiForceRegisterThisDevice === 'function'){
        setTimeout(function(){
          window.wpiForceRegisterThisDevice(false);
        }, 150);
      }
    }catch(e){}
  }

  document.addEventListener('submit', function(e){
    var f = e.target;
    if(!f) return;
    var t = (f.className || '') + ' ' + (f.id || '');
    if(/login/i.test(t)){
      setTimeout(wpiInstantRegisterAfterLogin, 800);
      setTimeout(wpiInstantRegisterAfterLogin, 2000);
    }
  }, true);

  window.wpiInstantRegisterAfterLogin = wpiInstantRegisterAfterLogin;
})();
</script>

</body>
</html>
    <?php
}


function wpi_share_token_endpoint() {
    if ( empty( $_GET['wpi_share'] ) ) return;
    $token = sanitize_text_field( $_GET['wpi_share'] );
    $data  = get_transient( 'wpi_share_' . $token );
    if ( ! $data ) { status_header(404); wp_die('Link expired'); }
    $id = absint( $data['id'] );
    if ( ! $id ) { status_header(400); wp_die('Invalid'); }
    if ( session_status() === PHP_SESSION_ACTIVE ) session_write_close();
    $_GET['download'] = '1';
    $_GET['id']       = $id;
    // Clear ALL captured output before generating PDF binary
    if ( session_status() === PHP_SESSION_ACTIVE ) session_write_close();
    ob_end_clean(); // discard any stray output captured since ob_start() above
    while ( ob_get_level() ) ob_end_clean();

    try {
        require_once WPI_PLUGIN_DIR . 'includes/class-pdf.php';
        require_once WPI_PLUGIN_DIR . 'includes/class-pdf-email.php';
require_once WPI_PLUGIN_DIR . 'includes/class-access.php';
        $pdf = new WPI_PDF();
        $pdf->generate( $id );
    } catch ( Throwable $e ) {
        while ( ob_get_level() ) ob_end_clean();
        status_header( 500 );
        wp_die( 'PDF generation failed: ' . esc_html( $e->getMessage() ) );
    }
    exit;
}

function wpi_pdf_endpoint() {
    if ( empty( $_GET['wpi_pdf'] ) ) return;

    // Suppress ALL PHP output immediately — WP_DEBUG output corrupts PDF binary
    $prev_error_reporting = error_reporting( 0 );
    ini_set( 'display_errors', '0' );
    while ( ob_get_level() ) ob_end_clean();
    ob_start(); // Capture any stray output

    // Auth — verify nonce and login
    $nonce = sanitize_text_field( $_GET['nonce'] ?? '' );
    if ( ! is_user_logged_in() ) {
        // Redirect to login then back
        wp_safe_redirect( wp_login_url( home_url( add_query_arg( array() ) ) ) );
        exit;
    }
    if ( ! wp_verify_nonce( $nonce, 'wpi_nonce' ) ) {
        status_header( 403 );
        wp_die( 'Security check failed. Please refresh the page and try again.', 'Session Expired', array( 'response' => 403 ) );
    }

    $id = absint( $_GET['id'] ?? 0 );
    if ( ! $id ) { status_header( 400 ); wp_die( 'Invalid ID' ); }

    // Verify the user has access to this inspection
    global $wpdb;
    $uid = get_current_user_id();
    $ins = $wpdb->get_row( $wpdb->prepare(
        "SELECT conducted_by, org_id FROM {$wpdb->prefix}wpi_inspections WHERE id=%d", $id
    ) );
    if ( ! $ins ) { status_header( 404 ); wp_die( 'Inspection not found' ); }

    // System owner sees all
    $is_owner = WPI_Admin::is_system_owner( $uid );
    if ( ! $is_owner ) {
        $user_org  = (int) WPI_Admin::get_user_org_id( $uid );
        $ins_org   = (int) $ins->org_id;
        $own_it    = ( (int)$ins->conducted_by === (int)$uid );
        // Allow if: user conducted it, OR same org, OR inspection has no org (legacy org_id=0)
        $same_org  = ( $ins_org === 0 || ( $user_org > 0 && $ins_org === $user_org ) );
        if ( ! $own_it && ! $same_org ) {
            status_header( 403 ); wp_die( 'Access denied' );
        }
    }

    if ( session_status() === PHP_SESSION_ACTIVE ) session_write_close();
    while ( ob_get_level() ) ob_end_clean();
    require_once WPI_PLUGIN_DIR . 'includes/class-pdf.php';
    try {
        $pdf = new WPI_PDF();
        $pdf->generate( $id );
    } catch ( Throwable $e ) {
        while ( ob_get_level() ) ob_end_clean();
        status_header( 500 );
        wp_die( 'PDF generation failed: ' . esc_html( $e->getMessage() ), 'Report Error' );
    }
    exit;
}

/**
 * Run migrations only when the stored DB version differs from plugin version.
 * This means it runs once after each update, not on every page load.
 */
function wpi_maybe_migrate() {
    $db_ver = get_option( 'wpi_db_version', '0' );
    if ( version_compare( $db_ver, WPI_VERSION, '>=' ) ) {
        return; // Already up to date
    }
    wpi_run_migrations();
    // Re-register endpoint then flush — but only if $wp_rewrite is ready.
    // During early init it may not be, so defer flush to shutdown to be safe.
    if ( isset( $GLOBALS['wp_rewrite'] ) && is_object( $GLOBALS['wp_rewrite'] ) ) {
        wpi_register_app_endpoint();
        flush_rewrite_rules();
    } else {
        add_action( 'wp_loaded', function() {
            wpi_register_app_endpoint();
            flush_rewrite_rules();
        } );
    }
    update_option( 'wpi_db_version', WPI_VERSION );
}

function wpi_run_migrations() {
    global $wpdb;
    $wpdb->suppress_errors( true );

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    // Re-run full table creation via dbDelta (safe — only adds missing)
    WPI_Activator::create_tables();

    // Migrate photo_url → photos
    if ( $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}wpi_responses'" ) ) {
        $cols = $wpdb->get_col( "SHOW COLUMNS FROM {$wpdb->prefix}wpi_responses", 0 );
        if ( $cols && in_array( 'photo_url', $cols ) && ! in_array( 'photos', $cols ) ) {
            $wpdb->query( "ALTER TABLE {$wpdb->prefix}wpi_responses ADD COLUMN photos LONGTEXT DEFAULT NULL" );
            $wpdb->query( "UPDATE {$wpdb->prefix}wpi_responses SET photos = CONCAT('[\"', photo_url, '\"]') WHERE photo_url IS NOT NULL AND photo_url != ''" );
            $wpdb->query( "ALTER TABLE {$wpdb->prefix}wpi_responses DROP COLUMN photo_url" );
        }
        // Ensure question_id is VARCHAR
        $qid = $wpdb->get_row( "SHOW COLUMNS FROM {$wpdb->prefix}wpi_responses LIKE 'question_id'" );
        if ( $qid && stripos( $qid->Type, 'bigint' ) !== false ) {
            $wpdb->query( "ALTER TABLE {$wpdb->prefix}wpi_responses MODIFY COLUMN question_id VARCHAR(100) NOT NULL DEFAULT '0'" );
        }
        // Ensure value is LONGTEXT (for large signature base64 data)
        $val_col = $wpdb->get_row( "SHOW COLUMNS FROM {$wpdb->prefix}wpi_responses LIKE 'value'" );
        if ( $val_col && stripos( $val_col->Type, 'longtext' ) === false ) {
            $wpdb->query( "ALTER TABLE {$wpdb->prefix}wpi_responses MODIFY COLUMN value LONGTEXT" );
        }
    }

    // Ensure questions.type is VARCHAR not ENUM
    if ( $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}wpi_questions'" ) ) {
        $type_col = $wpdb->get_row( "SHOW COLUMNS FROM {$wpdb->prefix}wpi_questions LIKE 'type'" );
        if ( $type_col && stripos( $type_col->Type, 'varchar' ) === false ) {
            $wpdb->query( "ALTER TABLE {$wpdb->prefix}wpi_questions MODIFY COLUMN type VARCHAR(50) DEFAULT 'yes_no'" );
        }
        // Add yes_no_colors column if missing
        $q_cols = $wpdb->get_col( "SHOW COLUMNS FROM {$wpdb->prefix}wpi_questions", 0 );
        if ( $q_cols && ! in_array( 'yes_no_colors', $q_cols ) ) {
            $wpdb->query( "ALTER TABLE {$wpdb->prefix}wpi_questions ADD COLUMN yes_no_colors TEXT DEFAULT NULL" );
        }
    }

    // Add licence columns to organisations if missing
    if ( $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}wpi_organisations'" ) ) {
        $org_cols = $wpdb->get_col( "SHOW COLUMNS FROM {$wpdb->prefix}wpi_organisations", 0 );
        if ( $org_cols ) {
            if ( ! in_array( 'licence_type',  $org_cols ) ) $wpdb->query( "ALTER TABLE {$wpdb->prefix}wpi_organisations ADD COLUMN licence_type VARCHAR(20) DEFAULT 'lifetime'" );
            if ( ! in_array( 'licence_start', $org_cols ) ) $wpdb->query( "ALTER TABLE {$wpdb->prefix}wpi_organisations ADD COLUMN licence_start DATE DEFAULT NULL" );
            if ( ! in_array( 'licence_end',   $org_cols ) ) $wpdb->query( "ALTER TABLE {$wpdb->prefix}wpi_organisations ADD COLUMN licence_end DATE DEFAULT NULL" );
            if ( ! in_array( 'trial_days',    $org_cols ) ) $wpdb->query( "ALTER TABLE {$wpdb->prefix}wpi_organisations ADD COLUMN trial_days INT(11) DEFAULT 14" );
        }
    }

    // Add subscription billing cycle column if missing
    if ( $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}wpi_subscriptions'" ) ) {
        $sub_cols = $wpdb->get_col( "SHOW COLUMNS FROM {$wpdb->prefix}wpi_subscriptions", 0 );
        if ( $sub_cols && ! in_array( 'billing_cycle', $sub_cols ) ) {
            $wpdb->query( "ALTER TABLE {$wpdb->prefix}wpi_subscriptions ADD COLUMN billing_cycle VARCHAR(20) NOT NULL DEFAULT '' AFTER current_period_end" );
        }
    }

    // Create activity log table if missing
    if ( ! $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}wpi_activity_log'" ) ) {
        $wpdb->query( "CREATE TABLE {$wpdb->prefix}wpi_activity_log (
            id            BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            object_type   VARCHAR(20) NOT NULL,
            object_id     BIGINT(20) UNSIGNED NOT NULL,
            action        VARCHAR(50) NOT NULL,
            detail        TEXT DEFAULT NULL,
            user_id       BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            user_name     VARCHAR(255) DEFAULT '',
            org_id        BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            created_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY object (object_type, object_id),
            KEY user_id (user_id),
            KEY created_at (created_at)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" );
    }

    // Create corrective actions table if missing
    if ( ! $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}wpi_actions'" ) ) {
        $wpdb->query( "CREATE TABLE {$wpdb->prefix}wpi_actions (
            id              BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            inspection_id   BIGINT(20) UNSIGNED NOT NULL,
            question_id     VARCHAR(100) DEFAULT '',
            question_label  VARCHAR(500) DEFAULT '',
            note            TEXT DEFAULT NULL,
            assigned_to     BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            assigned_name   VARCHAR(255) DEFAULT '',
            assigned_email  VARCHAR(255) DEFAULT '',
            due_date        DATE DEFAULT NULL,
            priority        VARCHAR(10) DEFAULT 'medium',
            status          VARCHAR(20) DEFAULT 'open',
            resolved_note   TEXT DEFAULT NULL,
            resolved_at     DATETIME DEFAULT NULL,
            resolved_by     BIGINT(20) UNSIGNED DEFAULT 0,
            created_by      BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            org_id          BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY inspection_id (inspection_id),
            KEY assigned_to (assigned_to),
            KEY status (status),
            KEY org_id (org_id),
            KEY due_date (due_date)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" );
    }

    // One-time migration: resolve report_title tokens for all existing inspections
    if ( ! get_option( 'wpi_migrated_inspection_titles' ) ) {
        $inspections = $wpdb->get_results(
            "SELECT i.id, i.title, i.conducted_at, i.score, i.site_name,
                    t.title as template_title, t.settings as template_settings
             FROM {$wpdb->prefix}wpi_inspections i
             LEFT JOIN {$wpdb->prefix}wpi_templates t ON t.id = i.template_id
             WHERE i.status IN ('completed','in_progress')"
        );
        foreach ( $inspections as $ins ) {
            $ts      = $ins->template_settings ? json_decode( $ins->template_settings, true ) : array();
            $pattern = $ts['report_title'] ?? '';
            if ( ! $pattern ) continue;
            $dt      = $ins->conducted_at ? new DateTime( $ins->conducted_at ) : new DateTime();
            $pattern = str_replace( '{date}',     $dt->format('d/m/Y'),                        $pattern );
            $pattern = str_replace( '{template}', $ins->template_title ?? '',                  $pattern );
            $pattern = str_replace( '{score}',    $ins->score !== null ? $ins->score.'%' : '', $pattern );
            $pattern = str_replace( '{site}',     $ins->site_name ?? '',                       $pattern );
            if ( preg_match( '/\{field:[^}]+\}/', $pattern ) ) {
                $resp_rows = $wpdb->get_results( $wpdb->prepare(
                    "SELECT q.label, r.value as response_value
                     FROM {$wpdb->prefix}wpi_responses r
                     JOIN {$wpdb->prefix}wpi_questions q ON q.id = CAST(r.question_id AS UNSIGNED)
                     WHERE r.inspection_id=%d AND r.question_id REGEXP '^[0-9]+$'
                       AND q.type NOT IN ('instruction','page','section') LIMIT 50",
                    $ins->id
                ) );
                $field_map = array();
                foreach ( $resp_rows as $rr ) {
                    $slug = trim( strtolower( preg_replace('/[^a-z0-9]+/i','_', trim($rr->label)) ), '_' );
                    $field_map[$slug] = $rr->response_value;
                }
                $pattern = preg_replace_callback( '/\{field:([^}]+)\}/', function($m) use ($field_map) {
                    return $field_map[$m[1]] ?? '';
                }, $pattern );
            }
            $pattern = preg_replace('/(\s*[\/\-]\s*){2,}/', ' / ', $pattern);
            $pattern = trim( $pattern, ' /-' );
            if ( $pattern && $pattern !== $ins->title ) {
                $wpdb->update(
                    $wpdb->prefix . 'wpi_inspections',
                    array( 'title' => sanitize_text_field( $pattern ) ),
                    array( 'id'    => $ins->id )
                );
            }
        }
        update_option( 'wpi_migrated_inspection_titles', '1' );
    }

    $wpdb->suppress_errors( false );
}

/**
 * Always ensure licence columns exist on wpi_organisations.
 * Runs on every page load but is a fast no-op when columns already exist.
 */
function wpi_ensure_org_licence_columns() {
    global $wpdb;
    $table = $wpdb->prefix . 'wpi_organisations';
    if ( ! $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" ) ) return;
    $wpdb->suppress_errors( true );
    $cols = $wpdb->get_col( "SHOW COLUMNS FROM {$table}", 0 );
    if ( ! $cols ) { $wpdb->suppress_errors( false ); return; }
    if ( ! in_array( 'licence_type',  $cols ) ) $wpdb->query( "ALTER TABLE {$table} ADD COLUMN licence_type VARCHAR(20) NOT NULL DEFAULT 'lifetime'" );
    if ( ! in_array( 'licence_start', $cols ) ) $wpdb->query( "ALTER TABLE {$table} ADD COLUMN licence_start DATE DEFAULT NULL" );
    if ( ! in_array( 'licence_end',   $cols ) ) $wpdb->query( "ALTER TABLE {$table} ADD COLUMN licence_end DATE DEFAULT NULL" );
    if ( ! in_array( 'trial_days',    $cols ) ) $wpdb->query( "ALTER TABLE {$table} ADD COLUMN trial_days INT(11) NOT NULL DEFAULT 14" );
    $wpdb->suppress_errors( false );
}
