<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Audit4me Access — Licence & Token Management
 */
class WPI_Access {

    // Licence types and their durations in days (null = lifetime)
    public static function licence_types() {
        return array(
            'trial'     => array( 'label' => 'Trial',      'days' => 14  ),
            'monthly'   => array( 'label' => 'Monthly',    'days' => 30  ),
            'quarterly' => array( 'label' => 'Quarterly',  'days' => 90  ),
            '6monthly'  => array( 'label' => '6-Monthly',  'days' => 180 ),
            'annual'    => array( 'label' => 'Annual',     'days' => 365 ),
            'lifetime'  => array( 'label' => 'Lifetime',   'days' => null ),
        );
    }

    // ── Token generation ──────────────────────────────────────────────

    /**
     * Generate a unique token in format A4M-{TYPE}-{SEATS}-XXXX
     * e.g. A4M-ANN-05-X4K2 (Annual, 5 seats)
     */
    public static function generate_token( $licence_type = 'annual', $seats = 1 ) {
        global $wpdb;
        $type_codes = array(
            'trial'     => 'TRL',
            'monthly'   => 'MTH',
            'quarterly' => 'QTR',
            '6monthly'  => '6MO',
            'annual'    => 'ANN',
            'lifetime'  => 'LTF',
        );
        $code      = isset( $type_codes[ $licence_type ] ) ? $type_codes[ $licence_type ] : 'ANN';
        $seat_code = str_pad( min( absint($seats), 99 ), 2, '0', STR_PAD_LEFT );
        do {
            // 12 random chars split into 3 groups of 4 for readability
            $p1    = strtoupper( substr( wp_generate_password( 6, false ), 0, 4 ) );
            $p2    = strtoupper( substr( wp_generate_password( 6, false ), 0, 4 ) );
            $p3    = strtoupper( substr( wp_generate_password( 6, false ), 0, 4 ) );
            $token = "A4M-{$code}-{$seat_code}-{$p1}-{$p2}-{$p3}";
            $exists = $wpdb->get_var( $wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}wpi_licences WHERE token = %s", $token
            ) );
        } while ( $exists );
        return $token;
    }

    /**
     * Create a new token in the pool
     */
    public static function create_token( $licence_type, $seats = 1, $notes = '', $created_by = 0 ) {
        global $wpdb;
        if ( ! array_key_exists( $licence_type, self::licence_types() ) ) return false;
        $seats  = max( 1, absint( $seats ) );
        $token  = self::generate_token( $licence_type, $seats );
        $wpdb->insert( $wpdb->prefix . 'wpi_licences', array(
            'token'        => $token,
            'licence_type' => $licence_type,
            'status'       => 'unassigned',
            'seats'        => $seats,
            'notes'        => sanitize_textarea_field( $notes ),
            'created_by'   => $created_by ?: get_current_user_id(),
            'created_at'   => current_time( 'mysql' ),
        ) );
        $licence_id = $wpdb->insert_id;
        // Pre-create all seat rows
        for ( $i = 1; $i <= $seats; $i++ ) {
            $wpdb->insert( $wpdb->prefix . 'wpi_licence_seats', array(
                'licence_id'  => $licence_id,
                'seat_number' => $i,
                'user_id'     => 0,
                'status'      => 'available',
                'created_at'  => current_time( 'mysql' ),
            ) );
        }
        self::log( $licence_id, 0, 'created', "Token created: {$token} ({$seats} seats)" );
        return array( 'id' => $licence_id, 'token' => $token );
    }


    /**
     * Assign a token directly to a user (individual, no org)
     */
    public static function assign_to_user( $licence_id, $user_id ) {
        global $wpdb;
        // Atomic claim — prevents double-assignment under concurrent requests
        $claimed = $wpdb->query( $wpdb->prepare(
            "UPDATE {$wpdb->prefix}wpi_licences SET status='assigned', user_id=%d, assigned_to='user',
             start_date=CURDATE(), assigned_at=NOW()
             WHERE id=%d AND status IN ('unassigned','claiming')",
            absint( $user_id ), absint( $licence_id )
        ) );
        if ( ! $claimed ) return false;

        // Fetch the updated licence to calculate expiry
        $licence    = self::get_licence( $licence_id );
        $types      = self::licence_types();
        $type_info  = isset( $types[ $licence->licence_type ] ) ? $types[ $licence->licence_type ] : null;
        $start_date = current_time( 'Y-m-d' );
        $expiry     = null;
        if ( $type_info && $type_info['days'] !== null ) {
            $expiry = date( 'Y-m-d', strtotime( '+' . $type_info['days'] . ' days' ) );
        }

        // Update expiry date
        $wpdb->update( $wpdb->prefix . 'wpi_licences',
            array( 'expiry_date' => $expiry ),
            array( 'id' => $licence_id )
        );

        // Clear basic access flag
        delete_user_meta( $user_id, 'wpi_access_basic' );
        // Fill Seat 1 as holder
        self::fill_seat( $licence_id, $user_id, true );
        self::log( $licence_id, 0, 'assigned_user', 'Assigned to user ID ' . $user_id );
        return true;
    }

    /**
     * Get token assigned directly to a user (user-level, not org)
     */
    // ── Seat management ───────────────────────────────────────────────

    /**
     * Get all seats for a licence
     */
    public static function get_seats( $licence_id ) {
        global $wpdb;
        $table = $wpdb->prefix . 'wpi_licence_seats';
        if ( $wpdb->get_var("SHOW TABLES LIKE '$table'") !== $table ) return array();
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT s.*,
                COALESCE(NULLIF(TRIM(CONCAT(COALESCE(um1.meta_value,''),' ',COALESCE(um2.meta_value,''))), ''), u.display_name) as user_name,
                u.user_email
             FROM {$wpdb->prefix}wpi_licence_seats s
             LEFT JOIN {$wpdb->users} u ON u.ID = s.user_id
             LEFT JOIN {$wpdb->usermeta} um1 ON um1.user_id = s.user_id AND um1.meta_key = 'first_name'
             LEFT JOIN {$wpdb->usermeta} um2 ON um2.user_id = s.user_id AND um2.meta_key = 'last_name'
             WHERE s.licence_id = %d
             ORDER BY s.seat_number ASC",
            $licence_id
        ) );
    }

    /**
     * Fill a seat with a user — called when token holder registers someone
     * Returns seat_id or false
     */
    public static function fill_seat( $licence_id, $user_id, $is_holder = false ) {
        global $wpdb;
        // Find first available seat
        $seat = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wpi_licence_seats
             WHERE licence_id = %d AND status = 'available'
             ORDER BY seat_number ASC LIMIT 1",
            $licence_id
        ) );
        if ( ! $seat ) return false;

        $status = $is_holder ? 'holder' : 'active';
        $wpdb->update( $wpdb->prefix . 'wpi_licence_seats', array(
            'user_id'   => absint( $user_id ),
            'status'    => $status,
            'joined_at' => current_time( 'mysql' ),
        ), array( 'id' => $seat->id ) );

        self::log( $licence_id, 0, 'seat_filled',
            "Seat {$seat->seat_number} filled by user {$user_id}" . ($is_holder?' (holder)':'') );
        return $seat->id;
    }

    /**
     * Revoke a seat — user loses access, slot becomes available
     */
    public static function revoke_seat( $seat_id, $revoked_by = 0 ) {
        global $wpdb;
        $seat = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wpi_licence_seats WHERE id = %d", $seat_id
        ) );
        if ( ! $seat || $seat->status === 'holder' ) return false; // can't revoke holder

        $wpdb->update( $wpdb->prefix . 'wpi_licence_seats', array(
            'user_id'    => 0,
            'status'     => 'available',
            'revoked_at' => current_time( 'mysql' ),
            'revoked_by' => $revoked_by ?: get_current_user_id(),
        ), array( 'id' => $seat_id ) );

        // Revoke user access
        if ( $seat->user_id ) {
            update_user_meta( $seat->user_id, 'wpi_access_basic', 1 );
        }
        self::log( $seat->licence_id, 0, 'seat_revoked', "Seat {$seat->seat_number} revoked" );
        return true;
    }

    /**
     * Get the licence a user holds (as holder or active seat member)
     */
    public static function get_user_seat( $user_id ) {
        global $wpdb;
        $table = $wpdb->prefix . 'wpi_licence_seats';
        if ( $wpdb->get_var("SHOW TABLES LIKE '$table'") !== $table ) return null;
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT s.*, l.token, l.licence_type, l.expiry_date, l.status as licence_status,
                    l.seats as total_seats
             FROM {$wpdb->prefix}wpi_licence_seats s
             JOIN {$wpdb->prefix}wpi_licences l ON l.id = s.licence_id
             WHERE s.user_id = %d AND s.status IN ('holder','active')
             AND l.status = 'assigned'
             ORDER BY s.id DESC LIMIT 1",
            $user_id
        ) );
    }

    public static function count_used_seats( $licence_id ) {
        global $wpdb;
        $table = $wpdb->prefix . 'wpi_licence_seats';
        if ( $wpdb->get_var("SHOW TABLES LIKE '$table'") !== $table ) return 0;
        return (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}wpi_licence_seats
             WHERE licence_id = %d AND status IN ('holder','active')",
            $licence_id
        ) );
    }

    public static function get_user_direct_licence( $user_id ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wpi_licences
             WHERE user_id=%d AND assigned_to='user' AND status='assigned'
             ORDER BY id DESC LIMIT 1",
            $user_id
        ) );
    }

    /**
     * Assign a token to an organisation
     */
    public static function assign_token( $licence_id, $org_id ) {
        global $wpdb;
        $licence = self::get_licence( $licence_id );
        if ( ! $licence ) return false;
        if ( $licence->status === 'assigned' ) return false; // already assigned

        $type_info   = self::licence_types()[ $licence->licence_type ] ?? null;
        $start_date  = current_time( 'Y-m-d' );
        $expiry_date = null;
        if ( $type_info && $type_info['days'] !== null ) {
            $expiry_date = date( 'Y-m-d', strtotime( '+' . $type_info['days'] . ' days' ) );
        }

        $wpdb->update( $wpdb->prefix . 'wpi_licences', array(
            'status'      => 'assigned',
            'org_id'      => absint( $org_id ),
            'start_date'  => $start_date,
            'expiry_date' => $expiry_date,
            'assigned_at' => current_time( 'mysql' ),
        ), array( 'id' => $licence_id ) );

        // Update org licence_id
        $wpdb->update( $wpdb->prefix . 'wpi_organisations', array(
            'licence_type'  => $licence->licence_type,
            'licence_start' => $start_date,
            'licence_end'   => $expiry_date,
        ), array( 'id' => $org_id ) );

        self::log( $licence_id, $org_id, 'assigned', 'Assigned to org ID ' . $org_id );
        return true;
    }

    /**
     * Revoke a token — unassign from org, goes back to pool
     */
    public static function revoke_token( $licence_id, $reason = '' ) {
        global $wpdb;
        $licence = self::get_licence( $licence_id );
        if ( ! $licence ) return false;

        $org_id = (int) $licence->org_id;
        $wpdb->update( $wpdb->prefix . 'wpi_licences', array(
            'status'      => 'revoked',
            'org_id'      => 0,
            'expiry_date' => null,
            'assigned_at' => null,
        ), array( 'id' => $licence_id ) );

        // Downgrade org users to basic
        if ( $org_id ) {
            self::downgrade_org_users( $org_id, 'Token revoked' );
        }

        self::log( $licence_id, $org_id, 'revoked', $reason ?: 'Revoked by admin' );
        return true;
    }

    // ── Token validation at login ─────────────────────────────────────

    /**
     * Check if an org has a valid active licence
     * Returns true = full access, false = basic only
     */
    public static function org_has_valid_licence( $org_id ) {
        global $wpdb;
        if ( ! $org_id ) return false;

        // ── Check Stripe subscription first ────────────────────────
        if ( class_exists('WPI_Billing') ) {
            $sub = WPI_Billing::get_org_plan( $org_id );
            if ( $sub ) {
                // active, trialing, past_due (grace) = access granted
                if ( in_array( $sub->status, array('active','trialing','past_due'), true ) ) {
                    return true;
                }
                // canceled/unpaid = no access
                if ( in_array( $sub->status, array('canceled','unpaid'), true ) ) {
                    return false;
                }
            }
        }

        // ── Fall back to legacy token licence ──────────────────────
        $licence = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wpi_licences
             WHERE org_id = %d AND status = 'assigned'
             ORDER BY id DESC LIMIT 1",
            $org_id
        ) );

        if ( ! $licence ) return false;

        // Lifetime — always valid
        if ( $licence->licence_type === 'lifetime' ) return true;

        // Check expiry
        if ( $licence->expiry_date ) {
            $today   = current_time( 'Y-m-d' );
            $expired = $licence->expiry_date < $today;
            if ( $expired ) {
                if ( $licence->status !== 'expired' ) {
                    $wpdb->update( $wpdb->prefix . 'wpi_licences',
                        array( 'status' => 'expired' ),
                        array( 'id' => $licence->id )
                    );
                    self::log( $licence->id, $org_id, 'expired', 'Auto-expired at login check' );
                }
                return false;
            }
        }

        return true;
    }

    /**
     * Called on user login — sets wpi_access_basic if no valid org licence
     */
    public static function check_login_access( $user ) {
        if ( ! ( $user instanceof WP_User ) ) return;
        require_once WPI_PLUGIN_DIR . 'includes/class-admin.php';
        if ( WPI_Admin::is_system_owner( $user->ID ) ) {
            delete_user_meta( $user->ID, 'wpi_access_basic' );
            return;
        }
        global $wpdb;

        // 1. Check user-direct seat first
        $seat = self::get_user_seat( $user->ID );
        if ( $seat ) {
            $valid = $seat->licence_status === 'assigned' &&
                     ( $seat->licence_type === 'lifetime' || ( $seat->expiry_date && $seat->expiry_date >= current_time('Y-m-d') ) );
            if ( $valid ) {
                delete_user_meta( $user->ID, 'wpi_access_basic' );
                update_user_meta( $user->ID, 'wpi_access_checked', current_time('mysql') );
                self::maybe_upgrade_role( $user->ID, (int)$seat->licence_id );
                return;
            }
        }

        // 2. Check direct licence (legacy)
        $direct = self::get_user_direct_licence( $user->ID );
        if ( $direct ) {
            $valid = $direct->licence_type === 'lifetime' || ( $direct->expiry_date && $direct->expiry_date >= current_time('Y-m-d') );
            if ( $valid ) {
                delete_user_meta( $user->ID, 'wpi_access_basic' );
                update_user_meta( $user->ID, 'wpi_access_checked', current_time('mysql') );
                self::maybe_upgrade_role( $user->ID, $direct->id );
                return;
            }
        }

        // 3. Check org licence
        $org_row = $wpdb->get_row( $wpdb->prepare(
            "SELECT org_id FROM {$wpdb->prefix}wpi_org_users WHERE user_id=%d LIMIT 1", $user->ID
        ) );
        $org_id = $org_row ? (int)$org_row->org_id : 0;

        if ( $org_id && self::org_has_valid_licence( $org_id ) ) {
            delete_user_meta( $user->ID, 'wpi_access_basic' );
            update_user_meta( $user->ID, 'wpi_access_checked', current_time('mysql') );
            return;
        }

        // No valid licence — force basic role regardless of what was manually set
        update_user_meta( $user->ID, 'wpi_access_basic', 1 );
        update_user_meta( $user->ID, 'wpi_access_checked', current_time('mysql') );
        // Strip any elevated role back to basic
        $current_role = $wpdb->get_var( $wpdb->prepare(
            "SELECT role FROM {$wpdb->prefix}wpi_user_roles WHERE user_id=%d", $user->ID
        ) );
        if ( $current_role && $current_role !== 'basic' ) {
            $wpdb->update( $wpdb->prefix . 'wpi_user_roles',
                array( 'role' => 'basic' ),
                array( 'user_id' => $user->ID )
            );
        }
    }

    /**
     * Upgrade user role to administrator and create personal org if they have
     * a valid direct licence but are still on basic role / no org
     */
    public static function maybe_upgrade_role( $user_id, $licence_id ) {
        global $wpdb;

        // Check current role
        $current_role = $wpdb->get_var( $wpdb->prepare(
            "SELECT role FROM {$wpdb->prefix}wpi_user_roles WHERE user_id=%d", $user_id
        ) );

        // Already upgraded — nothing to do
        if ( $current_role && $current_role !== 'basic' ) return;

        // Upgrade role
        $wpdb->replace( $wpdb->prefix . 'wpi_user_roles', array(
            'user_id' => $user_id,
            'role'    => 'administrator',
            'set_by'  => 0,
        ) );
        delete_user_meta( $user_id, 'wpi_access_basic' );

        // Create personal org if user has no org
        $has_org = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}wpi_org_users WHERE user_id=%d", $user_id
        ) );
        if ( $has_org ) return;

        $u      = get_userdata( $user_id );
        $fname  = get_user_meta( $user_id, 'first_name', true ) ?: '';
        $lname  = get_user_meta( $user_id, 'last_name',  true ) ?: '';
        $email  = $u ? $u->user_email : '';
        $name   = trim( $fname . ' ' . $lname ) ?: ( $u ? strstr( $u->user_email, '@', true ) : 'Personal' );

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        wpi_ensure_org_licence_columns();

        $wpdb->insert( $wpdb->prefix . 'wpi_organisations', array(
            'name'         => sanitize_text_field( $name ),
            'description'  => 'Personal organisation',
            'owner_id'     => $user_id,
            'licence_type' => 'trial',
            'status'       => 'active',
            'created_at'   => current_time( 'mysql' ),
        ) );
        $org_id = $wpdb->insert_id;
        if ( ! $org_id ) return;

        // Add as admin
        $wpdb->replace( $wpdb->prefix . 'wpi_org_users', array(
            'org_id'  => $org_id,
            'user_id' => $user_id,
            'role'    => 'administrator',
        ) );

        // Link licence to org
        if ( $licence_id ) {
            $wpdb->update( $wpdb->prefix . 'wpi_licences',
                array( 'org_id' => $org_id ),
                array( 'id' => $licence_id )
            );
        }
    }

    /**
     * Get token status for a user's org — for display in user profile
     */
    public static function get_user_token_status( $user_id ) {
        global $wpdb;
        $types = self::licence_types();

        // 1. Direct user licence takes priority
        $direct = self::get_user_direct_licence( $user_id );
        if ( $direct ) {
            $label = isset($types[$direct->licence_type]['label']) ? $types[$direct->licence_type]['label'] : $direct->licence_type;
            return array(
                'status'      => $direct->status,
                'type'        => $direct->licence_type,
                'type_label'  => $label,
                'token'       => $direct->token,
                'expiry'      => $direct->expiry_date,
                'assigned_to' => 'user',
                'org_id'      => 0,
            );
        }

        // 2. Org licence
        $org_row = $wpdb->get_row( $wpdb->prepare(
            "SELECT org_id FROM {$wpdb->prefix}wpi_org_users WHERE user_id=%d LIMIT 1", $user_id
        ) );
        if ( ! $org_row ) return array( 'status' => 'no_org', 'label' => 'No organisation or direct token' );
        $lic = self::get_org_licence( $org_row->org_id );
        if ( ! $lic ) return array( 'status' => 'no_licence', 'label' => 'No licence assigned to org' );
        $label = isset($types[$lic->licence_type]['label']) ? $types[$lic->licence_type]['label'] : $lic->licence_type;
        return array(
            'status'      => $lic->status,
            'type'        => $lic->licence_type,
            'type_label'  => $label,
            'token'       => $lic->token,
            'expiry'      => $lic->expiry_date,
            'assigned_to' => 'org',
            'org_id'      => $org_row->org_id,
        );
    }

    /**
     * Check if current user is in basic-only mode
     */
    public static function current_user_is_basic() {
        return (bool) get_user_meta( get_current_user_id(), 'wpi_access_basic', true );
    }

    // ── Expiry cron ───────────────────────────────────────────────────

    /**
     * Daily cron — expire licences and send warnings
     */
    public static function run_expiry_cron() {
        global $wpdb;
        $today = current_time( 'Y-m-d' );

        // 1. Expire licences past expiry date
        $expired = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wpi_licences
             WHERE status = 'assigned' AND expiry_date IS NOT NULL AND expiry_date < %s",
            $today
        ) );
        foreach ( $expired as $lic ) {
            $wpdb->update( $wpdb->prefix . 'wpi_licences',
                array( 'status' => 'expired' ),
                array( 'id' => $lic->id )
            );
            self::downgrade_org_users( $lic->org_id, 'Licence expired' );
            self::send_expiry_email( $lic, 'expired' );
            self::log( $lic->id, $lic->org_id, 'expired', 'Auto-expired by cron' );
        }

        // 2. Send warning emails — 7, 3, 1 days before expiry
        foreach ( array( 7, 3, 1 ) as $days ) {
            $target_date = date( 'Y-m-d', strtotime( "+{$days} days", strtotime( $today ) ) );
            $upcoming = $wpdb->get_results( $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}wpi_licences
                 WHERE status = 'assigned' AND expiry_date = %s",
                $target_date
            ) );
            foreach ( $upcoming as $lic ) {
                // Check not already sent
                $already = $wpdb->get_var( $wpdb->prepare(
                    "SELECT id FROM {$wpdb->prefix}wpi_token_log
                     WHERE licence_id = %d AND event = %s",
                    $lic->id, "warning_{$days}d"
                ) );
                if ( ! $already ) {
                    self::send_expiry_email( $lic, "warning_{$days}d" );
                    self::log( $lic->id, $lic->org_id, "warning_{$days}d",
                        "{$days}-day expiry warning sent" );
                }
            }
        }
    }

    /**
     * Downgrade all users in an org to basic access
     */
    public static function downgrade_org_users( $org_id, $reason = '' ) {
        global $wpdb;
        $users = $wpdb->get_col( $wpdb->prepare(
            "SELECT user_id FROM {$wpdb->prefix}wpi_org_users WHERE org_id = %d", $org_id
        ) );
        foreach ( $users as $uid ) {
            update_user_meta( $uid, 'wpi_access_basic', 1 );
            update_user_meta( $uid, 'wpi_downgrade_reason', $reason );
            update_user_meta( $uid, 'wpi_downgraded_at', current_time( 'mysql' ) );
        }
    }

    /**
     * Send expiry email to org admin
     */
    public static function send_expiry_email( $licence, $type ) {
        global $wpdb;
        if ( ! $licence->org_id ) return;

        // Get org admin
        $admin = $wpdb->get_row( $wpdb->prepare(
            "SELECT u.display_name, u.user_email
             FROM {$wpdb->prefix}wpi_org_users ou
             JOIN {$wpdb->users} u ON u.ID = ou.user_id
             WHERE ou.org_id = %d AND ou.role = 'admin'
             ORDER BY ou.id ASC LIMIT 1",
            $licence->org_id
        ) );
        if ( ! $admin || ! $admin->user_email ) return;

        $org = $wpdb->get_row( $wpdb->prepare(
            "SELECT name FROM {$wpdb->prefix}wpi_organisations WHERE id = %d", $licence->org_id
        ) );
        $org_name  = $org ? $org->name : 'Your organisation';
        $site      = get_bloginfo('name') ?: 'Audit4me';
        $expiry    = $licence->expiry_date ? date( 'd M Y', strtotime( $licence->expiry_date ) ) : '—';
        $fname     = explode( ' ', $admin->display_name )[0] ?: 'there';

        $_ltypes = self::licence_types();
        $type_label = isset($_ltypes[$licence->licence_type]['label']) ? $_ltypes[$licence->licence_type]['label'] : $licence->licence_type;

        if ( $type === 'expired' ) {
            $badge   = '🔴 LICENCE EXPIRED';
            $color   = '#ef4444';
            $subject = '🔴 Your ' . $site . ' licence has expired — ' . $org_name;
            $message = 'Your <strong>' . esc_html($site) . '</strong> licence for <strong>' . esc_html($org_name) . '</strong> expired on <strong>' . esc_html($expiry) . '</strong>. All users have been downgraded to Basic access. Please contact us to renew.';
        } else {
            $days    = str_replace( 'warning_', '', str_replace( 'd', '', $type ) );
            $badge   = '⚠️ LICENCE EXPIRING SOON';
            $color   = '#f59e0b';
            $subject = '⚠️ Your ' . $site . ' licence expires in ' . $days . ' day' . ($days==1?'':'s') . ' — ' . $org_name;
            $message = 'Your <strong>' . esc_html($site) . '</strong> licence for <strong>' . esc_html($org_name) . '</strong> will expire on <strong>' . esc_html($expiry) . '</strong> (' . $days . ' day' . ($days==1?'':'s') . ' remaining). Please contact us to renew before access is lost.';
        }

        $body_html = '
            <p style="margin:0 0 16px;color:#374151;font-size:14px;line-height:1.6;">Hi ' . esc_html($fname) . ',</p>
            <p style="margin:0 0 20px;color:#374151;font-size:14px;line-height:1.6;">' . $message . '</p>
            <table width="100%" cellpadding="0" cellspacing="0" style="background:#f8faff;border:1.5px solid #e0e7ff;border-radius:10px;margin-bottom:20px;">
              <tr><td style="padding:16px 18px;">
                <p style="margin:0 0 4px;font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;">Organisation</p>
                <p style="margin:0 0 12px;font-size:14px;color:#111827;">' . esc_html($org_name) . '</p>
                <p style="margin:0 0 4px;font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;">Licence Type</p>
                <p style="margin:0 0 12px;font-size:14px;color:#111827;">' . esc_html($type_label) . '</p>
                <p style="margin:0 0 4px;font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;">Expiry Date</p>
                <p style="margin:0;font-size:14px;color:' . esc_attr($color) . ';font-weight:700;">' . esc_html($expiry) . '</p>
              </td></tr>
            </table>';

        require_once WPI_PLUGIN_DIR . 'includes/class-scheduler.php';
        WPI_Scheduler::send_branded_email(
            $admin->user_email,
            $subject,
            $body_html,
            $color,
            $badge
        );
    }

    // ── Helpers ───────────────────────────────────────────────────────

    public static function get_licence( $id ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wpi_licences WHERE id = %d", $id
        ) );
    }

    public static function get_org_licence( $org_id ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wpi_licences WHERE org_id = %d AND status = 'assigned' LIMIT 1",
            $org_id
        ) );
    }

    public static function log( $licence_id, $org_id, $event, $detail = '' ) {
        global $wpdb;
        $wpdb->insert( $wpdb->prefix . 'wpi_token_log', array(
            'licence_id'   => $licence_id,
            'org_id'       => $org_id,
            'event'        => $event,
            'detail'       => $detail,
            'performed_by' => get_current_user_id(),
            'created_at'   => current_time( 'mysql' ),
        ) );
    }

    public static function log_by_org( $org_id, $event, $detail = '' ) {
        global $wpdb;
        $lic = self::get_org_licence( $org_id );
        if ( $lic ) self::log( $lic->id, $org_id, $event, $detail );
    }

    public static function get_all_tokens( $status = '' ) {
        global $wpdb;
        $where = $status ? $wpdb->prepare( ' WHERE l.status = %s', $status ) : '';
        $rows = $wpdb->get_results(
            "SELECT l.*, o.name as org_name,
                CONCAT(COALESCE(um.meta_value,''), ' ', COALESCE(um2.meta_value,'')) as user_name,
                u.user_email as user_email
             FROM {$wpdb->prefix}wpi_licences l
             LEFT JOIN {$wpdb->prefix}wpi_organisations o ON o.id = l.org_id
             LEFT JOIN {$wpdb->users} u ON u.ID = l.user_id
             LEFT JOIN {$wpdb->usermeta} um ON um.user_id = l.user_id AND um.meta_key='first_name'
             LEFT JOIN {$wpdb->usermeta} um2 ON um2.user_id = l.user_id AND um2.meta_key='last_name'
             {$where}
             ORDER BY l.created_at DESC"
        );
        $types = self::licence_types();
        foreach ( $rows as &$r ) {
            $r->type_label  = isset($types[$r->licence_type]['label']) ? $types[$r->licence_type]['label'] : $r->licence_type;
            $r->user_name   = trim($r->user_name) ?: null;
        }
        return $rows ?: array();
    }
}
