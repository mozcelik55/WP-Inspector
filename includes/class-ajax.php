<?php
/**
 * WPI_Ajax – All AJAX handlers via admin-ajax.php
 * Works on ALL WordPress installs regardless of permalink settings.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class WPI_Ajax {

    public function init() {
        $actions = array(
            'wpi_get_dashboard',
            'wpi_get_templates',
            'wpi_get_template',
            'wpi_create_template',
            'wpi_update_template',
            'wpi_get_questions',
            'wpi_save_questions',
            'wpi_get_inspections',
            'wpi_create_inspection',
            'wpi_get_inspection',
            'wpi_update_inspection',
            'wpi_archive_inspection',
            'wpi_delete_inspection',
            'wpi_restore_inspection',
            'wpi_archive_template',
            'wpi_restore_template',
            'wpi_upload_photo',
            'wpi_download_pdf',
            // Teams
            'wpi_get_teams',
            'wpi_create_team',
            'wpi_update_team',
            'wpi_delete_team',
            'wpi_add_team_member',
            'wpi_remove_team_member',
            'wpi_get_wp_users',
            // Template sharing
            'wpi_get_template_shares',
            // API settings
            'wpi_get_api_settings',
            'wpi_save_api_settings',
            'wpi_test_webhook',
            'wpi_import_template',
            // User roles
            'wpi_get_user_roles',
            'wpi_set_user_role',
            'wpi_delete_user',
            'wpi_deactivate_user',
            'wpi_get_role_info',
            'wpi_add_template_share',
            'wpi_remove_template_share',
            'wpi_remove_my_template_share',
            'wpi_user_archive_template',
            'wpi_user_restore_template',
            'wpi_update_share_access',
            'wpi_update_share_visibility',
            // Template delete/hide
            'wpi_delete_template',
            'wpi_hide_template',
            // System settings (timezone)
            'wpi_get_system_settings',
            'wpi_save_system_settings',
            'wpi_refresh_inspection_titles',
            'wpi_read_push_log',
            'wpi_push_diagnostics',
            'wpi_owner_test_push',
            'wpi_owner_test_action_push',
            'wpi_owner_test_overdue_action_push',
            // Sites
            'wpi_get_sites',
            'wpi_create_site',
            'wpi_update_site',
            'wpi_delete_site',
            'wpi_get_site_users',
            'wpi_add_site_user',
            'wpi_remove_site_user',
            // Organisations
            'wpi_get_orgs',
            'wpi_create_org',
            'wpi_get_licences',
            'wpi_create_licence',
            'wpi_assign_licence',
            'wpi_revoke_licence',
            'wpi_delete_licence',
            'wpi_get_token_log',
            'wpi_get_org_licence',
            'wpi_get_user_token_status',
            'wpi_assign_user_licence',
            'wpi_revoke_user_licence',
            'wpi_activate_user_token',
            'wpi_register_user',
            'wpi_get_licence_seats',
            'wpi_register_seat_user',
            'wpi_assign_existing_to_seat',
            'wpi_revoke_seat',
            'wpi_update_org',
            'wpi_delete_org',
            'wpi_get_org_users',
            'wpi_add_org_user',
            'wpi_send_invitation',
            'wpi_get_invitations',
            'wpi_cancel_invitation',
            'wpi_accept_invitation',
            'wpi_leave_org',
            'wpi_get_pending_invite',
            'wpi_remove_org_user',
            'wpi_create_org_user',
            'wpi_get_org_licence',
            'wpi_get_user_detail',
            'wpi_update_user',
            'wpi_set_org_licence',
            'wpi_set_org_user_role',
            'wpi_create_share_token',
            'wpi_get_schedule',
            'wpi_save_schedule',
            'wpi_test_schedule_email',
            'wpi_get_activity_log',
            // Billing & subscriptions
            'wpi_get_plans',
            'wpi_get_org_seats',
            'wpi_buy_seats',
            'wpi_confirm_seat_session',
            'wpi_resume_seat_reduction',
            'wpi_get_invoices',
            'wpi_resend_invoice',
            'wpi_get_all_invoices',
            'wpi_billing_admin_get',
            'wpi_billing_admin_save_sub',
            'wpi_billing_admin_save_seats',
            'wpi_billing_admin_delete_sub',
            'wpi_billing_admin_delete_seats',
            'wpi_assign_seat',
            'wpi_unassign_seat',
            'wpi_get_seat_price',
            'wpi_save_seat_price',
            'wpi_update_seat_qty',
            'wpi_cancel_seat_sub',
            'wpi_get_my_subscription',
            'wpi_create_checkout_session',
            'wpi_cancel_subscription',
            'wpi_resume_subscription',
            'wpi_get_billing_portal',
            // System owner: plan management
            'wpi_save_plan',
            'wpi_delete_plan',
            'wpi_get_stripe_settings',
            'wpi_save_stripe_settings',
            // API key management
            'wpi_list_api_keys',
            'wpi_create_api_key',
            'wpi_toggle_api_key',
            'wpi_revoke_api_key',
            'wpi_ping',
            'wpi_get_sessions',
            'wpi_kick_session',
            'wpi_get_my_sessions',
            'wpi_kick_my_session',
            'wpi_get_session_limit',
            'wpi_get_device_control',
            'wpi_save_device_control',
            'wpi_set_user_device_limit',
            'wpi_create_action',
            'wpi_reassign_action',
            'wpi_get_actions',
            'wpi_resolve_action',
            'wpi_delete_action',
            'wpi_get_my_actions',
            'wpi_save_action_photos',
            'wpi_delete_action_photo',
            'wpi_push_subscribe',
            'wpi_onesignal_register',
            'wpi_wtn_register',
            'wpi_wtn_debug',
            'wpi_wtn_unregister',
            'wpi_save_firebase_sa',
            'wpi_onesignal_status',
            'wpi_get_push_settings',
            'wpi_save_push_settings',
            'wpi_push_unsubscribe',
            'wpi_get_vapid_public_key',
            'wpi_test_push',
            'wpi_clear_push_subs',
        );
        foreach ( $actions as $action ) {
            add_action( 'wp_ajax_' . $action, array( $this, $action ) );
        }
        // Public (no-login) actions
        add_action( 'wp_ajax_nopriv_wpi_register_user',      array( $this, 'wpi_register_user' ) );
        add_action( 'wp_ajax_nopriv_wpi_send_verify_code',  array( $this, 'wpi_send_verify_code' ) );
        add_action( 'wp_ajax_nopriv_wpi_verify_email_code', array( $this, 'wpi_verify_email_code' ) );
        add_action( 'wp_ajax_nopriv_wpi_ajax_login',   array( $this, 'wpi_ajax_login' ) );
        add_action( 'wp_ajax_nopriv_wpi_check_device_limit', array( $this, 'wpi_check_device_limit' ) );
        add_action( 'wp_ajax_nopriv_wpi_remove_device_for_login', array( $this, 'wpi_remove_device_for_login' ) );
        add_action( 'wp_ajax_nopriv_wpi_get_pending_invite',    array( $this, 'wpi_get_pending_invite' ) );
        add_action( 'wp_ajax_wpi_check_my_invitations',           array( $this, 'wpi_check_my_invitations' ) );
        add_action( 'wp_ajax_nopriv_wpi_do_login',               array( $this, 'wpi_do_login' ) );
        add_action( 'wp_ajax_wpi_check_device_limit', array( $this, 'wpi_check_device_limit' ) );
        add_action( 'wp_ajax_wpi_remove_device_for_login', array( $this, 'wpi_remove_device_for_login' ) );
    }

    /* ── Helpers ──────────────────────────────────────────────── */
    private function is_system_owner() {
        return WPI_Admin::is_system_owner( get_current_user_id() );
    }
    private function get_org_id() {
        return WPI_Admin::get_user_org_id( get_current_user_id() );
    }

    /**
     * Return the user's organisation, creating a personal organisation when the
     * user is not linked to one yet. This is used for subscription checkout so
     * standalone/free users can purchase a plan instead of being blocked by
     * "No organisation linked".
     */
    private function get_or_create_billing_org_id() {
        global $wpdb;
        $uid = get_current_user_id();
        if ( ! $uid ) return 0;

        $org_id = (int) $this->get_org_id();
        if ( $org_id ) return $org_id;

        $user = get_userdata( $uid );
        $base_name = $user && $user->display_name ? $user->display_name : ( $user ? $user->user_login : 'My Organisation' );
        $org_name  = trim( $base_name . ' Organisation' );

        // If an orphan organisation already exists for this owner, link it.
        $existing = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}wpi_organisations WHERE owner_id=%d ORDER BY id ASC LIMIT 1",
            $uid
        ) );
        if ( $existing ) {
            $wpdb->query( $wpdb->prepare(
                "INSERT IGNORE INTO {$wpdb->prefix}wpi_org_users (org_id, user_id, role) VALUES (%d, %d, %s)",
                $existing, $uid, 'admin'
            ) );
            return $existing;
        }

        $wpdb->insert(
            $wpdb->prefix . 'wpi_organisations',
            array(
                'name'        => $org_name,
                'description' => '',
                'owner_id'    => $uid,
                'status'      => 'active',
                'created_at'  => current_time('mysql'),
            ),
            array( '%s', '%s', '%d', '%s', '%s' )
        );
        $new_org_id = (int) $wpdb->insert_id;
        if ( ! $new_org_id ) return 0;

        $wpdb->query( $wpdb->prepare(
            "INSERT IGNORE INTO {$wpdb->prefix}wpi_org_users (org_id, user_id, role) VALUES (%d, %d, %s)",
            $new_org_id, $uid, 'admin'
        ) );

        return $new_org_id;
    }
    private function org_filter( $alias = '' ) {
        if ( $this->is_system_owner() ) return ''; // system owner sees all
        $org_id = $this->get_org_id();
        $col = $alias ? $alias.'.org_id' : 'org_id';
        return " AND {$col} = {$org_id}";
    }
    private function org_id_for_insert() {
        if ( $this->is_system_owner() ) return 0;
        return $this->get_org_id();
    }

    /**
     * Build a WHERE clause fragment that scopes inspections to the current user's org.
     * Uses org_id stamp OR conducted_by membership to cover old records (org_id=0).
     * Returns empty string for system owner (sees all).
     * Alias: table alias prefix e.g. 'i' → 'i.org_id', 'i.conducted_by'
     */
    private function org_inspection_where( $alias = 'i' ) {
        global $wpdb;
        if ( $this->is_system_owner() ) return '';
        $org_id = $this->get_org_id();
        if ( !$org_id ) return '';
        $member_ids = $wpdb->get_col( $wpdb->prepare(
            "SELECT user_id FROM {$wpdb->prefix}wpi_org_users WHERE org_id=%d", $org_id
        ) );
        if ( empty($member_ids) ) {
            return $wpdb->prepare( " AND {$alias}.org_id=%d", $org_id );
        }
        $in = implode( ',', array_map( 'absint', $member_ids ) );
        return $wpdb->prepare( " AND ({$alias}.org_id=%d OR {$alias}.conducted_by IN ($in))", $org_id );
    }

    /**
     * Build a WHERE clause that scopes templates to the current user's org.
     * Uses org_id stamp OR created_by membership to cover old records.
     */
    private function org_template_where( $alias = 't' ) {
        global $wpdb;
        if ( $this->is_system_owner() ) return '';
        $org_id = $this->get_org_id();
        if ( !$org_id ) return '';
        $member_ids = $wpdb->get_col( $wpdb->prepare(
            "SELECT user_id FROM {$wpdb->prefix}wpi_org_users WHERE org_id=%d", $org_id
        ) );
        if ( empty($member_ids) ) {
            return $wpdb->prepare( " AND {$alias}.org_id=%d", $org_id );
        }
        $in = implode( ',', array_map( 'absint', $member_ids ) );
        // Include templates owned by this org OR created by org members (legacy org_id=0 records)
        return $wpdb->prepare( " AND ({$alias}.org_id=%d OR ({$alias}.created_by IN ($in) AND {$alias}.org_id=0))", $org_id );
    }


    /**
     * Get licence status for an org.
     * Returns: array with keys: status (active|trial|expired|suspended|lifetime),
     *          days_remaining (int or null), expires (date string or null)
     */
    public static function get_org_licence( $org_id ) {
        global $wpdb;
        if ( !$org_id ) return array('status'=>'active','days_remaining'=>null,'expires'=>null);

        // Stripe subscription is the source of truth for paid accounts. Hide the
        // trial banner as soon as Stripe confirms an active/trialing/past_due subscription.
        if ( class_exists( 'WPI_Billing' ) ) {
            WPI_Billing::sync_org_subscription_from_stripe( (int) $org_id );
            $stripe_sub = WPI_Billing::get_org_plan( (int) $org_id );
            if ( $stripe_sub && in_array( $stripe_sub->status, array( 'active', 'trialing', 'past_due' ), true ) ) {
                $cycle = ! empty( $stripe_sub->billing_cycle ) ? $stripe_sub->billing_cycle : 'monthly';
                return array(
                    'status'         => 'active',
                    'days_remaining' => null,
                    'expires'        => $stripe_sub->current_period_end ?? null,
                    'type'           => $cycle,
                    'licence_type'   => $cycle,
                    'licence_start'  => null,
                    'licence_end'    => $stripe_sub->current_period_end ?? null,
                );
            }
        }

        $org = $wpdb->get_row( $wpdb->prepare(
            "SELECT status, licence_type, licence_start, licence_end, trial_days, created_at
             FROM {$wpdb->prefix}wpi_organisations WHERE id=%d", $org_id
        ) );
        if ( !$org ) return array('status'=>'active','days_remaining'=>null,'expires'=>null);

        // Manually suspended
        if ( $org->status === 'suspended' ) return array('status'=>'suspended','days_remaining'=>0,'expires'=>null);

        $type  = !empty($org->licence_type) ? $org->licence_type : 'lifetime'; // default lifetime if unset
        $today = new DateTime('today');

        // Lifetime — never expires
        if ( $type === 'lifetime' ) return array('status'=>'active','days_remaining'=>null,'expires'=>null,'type'=>'lifetime','licence_type'=>'lifetime','trial_days'=>(int)($org->trial_days??14),'licence_start'=>$org->licence_start??null);

        // Trial — expires licence_start + trial_days (falls back to created_at)
        if ( $type === 'trial' ) {
            $trial_days = max(1, (int)($org->trial_days ?: 14));
            $start_date = !empty($org->licence_start) ? $org->licence_start : $org->created_at;
            $start = new DateTime( $start_date ?: 'now' );
            $end   = clone $start;
            $end->modify("+{$trial_days} days");
            $diff  = $today->diff($end);
            $days  = $diff->invert ? -$diff->days : $diff->days;
            return array(
                'status'         => $days >= 0 ? 'trial' : 'expired',
                'days_remaining' => max(0, $days),
                'expires'        => $end->format('Y-m-d'),
                'type'           => 'trial',
                'licence_type'   => 'trial',
                'trial_days'     => $trial_days,
                'licence_start'  => $start->format('Y-m-d'),
            );
        }

        // Dated licence (monthly, annual, custom) — use licence_start/licence_end
        if ( $org->licence_end ) {
            $end  = new DateTime( $org->licence_end );
            $diff = $today->diff($end);
            $days = $diff->invert ? -$diff->days : $diff->days;
            return array(
                'status'         => $days >= 0 ? 'active' : 'expired',
                'days_remaining' => max(0, $days),
                'expires'        => $org->licence_end,
                'type'           => $type,
                'licence_type'   => $type,
                'trial_days'     => (int)($org->trial_days??14),
                'licence_start'  => $org->licence_start??null,
                'licence_end'    => $org->licence_end,
            );
        }

        // No end date set — treat as active
        return array('status'=>'active','days_remaining'=>null,'expires'=>null,'type'=>$type,'licence_type'=>$type,'trial_days'=>(int)($org->trial_days??14),'licence_start'=>$org->licence_start??null);
    }

    /**
     * Check if current user's org licence allows write operations.
     * System owner is always allowed.
     * Expired/suspended orgs → read-only.
     */
    private function require_write_licence() {
        if ( $this->is_system_owner() ) return; // always allowed
        $org_id = $this->get_org_id();
        if ( !$org_id ) return; // unassigned user — allow

        // Check Stripe subscription status first
        if ( class_exists('WPI_Billing') ) {
            $sub = WPI_Billing::get_org_plan( $org_id );
            if ( $sub ) {
                if ( in_array( $sub->status, array('canceled','unpaid'), true ) ) {
                    $this->error( 'Your subscription has ended. Please renew your plan to continue.', 403 );
                }
                // past_due: still allow writes but frontend shows warning
            }
            // No subscription = free tier, still allow (limits enforced per-feature)
        }

        // Legacy token licence check (kept for backward compat)
        try {
            $lic = self::get_org_licence( $org_id );
            if ( in_array( $lic['status'], array('expired','suspended') ) ) {
                $msg = $lic['status'] === 'suspended'
                    ? 'Your organisation has been suspended. Contact your administrator.'
                    : 'Your licence has expired. Please upgrade your plan to continue.';
                $this->error( $msg, 403 );
            }
        } catch ( Exception $e ) {
            // Licence check failed — allow write (fail open)
        }
    }

    /**
     * Check a billing limit before a create action.
     * Call this before inserting users, templates, inspections, sites.
     * Returns error if over limit, void if ok.
     */
    private function check_billing_limit( $feature ) {
        if ( $this->is_system_owner() ) return;
        if ( ! class_exists('WPI_Billing') ) return;
        global $wpdb;
        $org_id = $this->get_org_id();
        if ( ! $org_id ) return;

        $limits = WPI_Billing::get_limits( $org_id );
        $count  = 0;

        switch ( $feature ) {
            case 'create_user':
                $count = (int) $wpdb->get_var( $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}wpi_org_users WHERE org_id=%d", $org_id ) );
                $max = $limits['max_users'] ?? 1;
                break;
            case 'create_template':
                $count = (int) $wpdb->get_var( $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}wpi_templates WHERE org_id=%d AND status!='deleted'", $org_id ) );
                $max = $limits['max_templates'] ?? 3;
                break;
            case 'create_inspection':
                $count = (int) $wpdb->get_var( $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}wpi_inspections WHERE org_id=%d AND MONTH(conducted_at)=MONTH(NOW()) AND YEAR(conducted_at)=YEAR(NOW())", $org_id ) );
                $max = $limits['max_inspections'] ?? 10;
                break;
            case 'create_site':
                $count = (int) $wpdb->get_var( $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}wpi_sites WHERE org_id=%d", $org_id ) );
                $max = $limits['max_sites'] ?? 1;
                break;
            default:
                return;
        }

        if ( $max >= 0 && $count >= $max ) {
            $label_map = array(
                'create_user'       => 'users ('.$max.' max on your plan)',
                'create_template'   => 'templates ('.$max.' max on your plan)',
                'create_inspection' => 'inspections this month ('.$max.' max on your plan)',
                'create_site'       => 'sites ('.$max.' max on your plan)',
            );
            $label = $label_map[$feature] ?? $feature;
            $this->error(
                'Plan limit reached: you have reached the maximum ' . $label .
                '. Upgrade your plan to add more.',
                402  // 402 Payment Required — frontend can detect this and show upgrade prompt
            );
        }
    }

    private function json( $data, $status = 200 ) {
        wp_send_json( $data, $status );
    }

    private function error( $message, $status = 400 ) {
        wp_send_json_error( array( 'message' => $message ), $status );
    }

    private function check_nonce() {
        if ( ! check_ajax_referer( 'wpi_nonce', 'nonce', false ) ) {
            $this->error( 'Invalid nonce', 403 );
        }
        if ( ! is_user_logged_in() ) {
            $this->error( 'Not logged in', 401 );
        }
    }

    private function input() {
        // JS sends JSON in a FormData field called "body"
        if ( isset( $_POST["body"] ) ) {
            return json_decode( stripslashes( $_POST["body"] ), true ) ?: array();
        }
        $raw = file_get_contents( "php://input" );
        return $raw ? json_decode( $raw, true ) : array();
    }

    /* ── Role helpers ─────────────────────────────────────────── */

    /**
     * Get WPI app role for any user.
     * WP admins always → 'administrator'. Unassigned → 'guest'.
     * Roles: administrator | manager | standard | basic | guest
     */
    private function get_wpi_role( $user_id = null ) {
        if ( $user_id === null ) $user_id = get_current_user_id();
        // WP admins are always 'administrator' in WPI
        if ( user_can( (int)$user_id, 'manage_options' ) ) return 'administrator';
        global $wpdb;
        $table = $wpdb->prefix . 'wpi_user_roles';
        if ( $wpdb->get_var("SHOW TABLES LIKE '$table'") !== $table ) return 'standard';
        $r = $wpdb->get_var( $wpdb->prepare(
            "SELECT role FROM $table WHERE user_id=%d", $user_id ) );
        $valid = array('administrator','super_manager','manager','standard','basic','guest');
        $role  = in_array($r, $valid) ? $r : 'standard';
        // If no meaningful role set, check if user is an org admin — elevate them
        if ( in_array($role, array('standard','guest')) ) {
            $org_table = $wpdb->prefix . 'wpi_org_users';
            if ( $wpdb->get_var("SHOW TABLES LIKE '$org_table'") === $org_table ) {
                $org_role = $wpdb->get_var( $wpdb->prepare(
                    "SELECT role FROM $org_table WHERE user_id=%d AND role='admin' LIMIT 1", $user_id ) );
                if ( $org_role === 'admin' ) {
                    $role = 'administrator';
                    // Persist so future calls are fast
                    $wpdb->replace( $wpdb->prefix.'wpi_user_roles', array(
                        'user_id' => $user_id, 'role' => 'administrator', 'set_by' => 0
                    ) );
                }
            }
        }
        return $role;
    }

    private function role_level( $role ) {
        $l = array('administrator'=>6,'super_manager'=>5,'manager'=>4,'standard'=>3,'basic'=>2,'guest'=>1);
        return $l[$role] ?? 1;
    }

    /** True if current user has at least the given role level */
    private function can( $required_role ) {
        // System owner always has full access
        if ( $this->is_system_owner() ) return true;
        return $this->role_level( $this->get_wpi_role() ) >= $this->role_level( $required_role );
    }

    public function wpi_get_dashboard() {
        $this->check_nonce();
        global $wpdb;
        $uid      = get_current_user_id();
        $wpi_role = $this->get_wpi_role();
        $is_admin = in_array( $wpi_role, array('administrator','super_manager') );

        $is_sys_owner = $this->is_system_owner();
        $user_org_id  = $this->get_org_id();

        // Date range filter
        $days = absint( $_GET['days'] ?? 30 );
        if ( ! in_array( $days, array(7,30,90,365), true ) ) $days = 30;
        $date_from = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        if ( $is_sys_owner ) {
            $uw = '';
        } elseif ( $is_admin && $user_org_id ) {
            $uw = $this->org_inspection_where('i');
        } else {
            $am = $this->get_accessible_templates('conduct');
            if ( empty($am) ) {
                $uw = $wpdb->prepare(' AND i.conducted_by=%d', $uid);
            } else {
                $ids = implode(',', array_map('absint', array_keys($am)));
                $uw  = $wpdb->prepare(' AND (i.conducted_by=%d OR i.template_id IN ('.$ids.'))', $uid);
            }
            if ( $user_org_id ) $uw .= $this->org_inspection_where('i');
        }

        // Apply inspection_visibility filters from share records
        if ( !$is_sys_owner ) {
            $vis_rows = $wpdb->get_results( $wpdb->prepare(
                "SELECT template_id, inspection_visibility, shared_at
                 FROM {$wpdb->prefix}wpi_template_shares
                 WHERE shared_with_type='user' AND shared_with_id=%d
                   AND inspection_visibility != 'all'", $uid
            ) );
            $date_cases = array();
            $own_only_ids = array();
            foreach ( $vis_rows as $vr ) {
                if ( $vr->inspection_visibility === 'from_share_date' && $vr->shared_at ) {
                    $date_cases[] = $wpdb->prepare("(i.template_id=%d AND i.conducted_at < %s)", (int)$vr->template_id, $vr->shared_at);
                } elseif ( $vr->inspection_visibility === 'own_only' ) {
                    $own_only_ids[] = (int)$vr->template_id;
                }
            }
            if ( !empty($date_cases) ) {
                $uw .= ' AND NOT (' . implode(' OR ', $date_cases) . ')';
            }
            if ( !empty($own_only_ids) ) {
                $ids_oo = implode(',', $own_only_ids);
                $uw .= $wpdb->prepare(" AND (i.template_id NOT IN ($ids_oo) OR i.conducted_by=%d)", $uid);
            }
        }

        $acc_all = $this->get_accessible_templates('view');
        $tmpl    = count($acc_all);
        $safe_uw = $uw;
        $dw      = $wpdb->prepare( " AND i.conducted_at >= %s", $date_from );

        // ── FIX: Collapse 3 separate status COUNTs into one GROUP BY query ──
        $status_counts = array( 'in_progress' => 0, 'completed' => 0 );
        $status_rows = $wpdb->get_results(
            "SELECT i.status, COUNT(*) as cnt
             FROM {$wpdb->prefix}wpi_inspections i
             WHERE i.status IN ('in_progress','completed'){$safe_uw}
             GROUP BY i.status"
        );
        foreach ( $status_rows as $sr ) {
            $status_counts[ $sr->status ] = (int) $sr->cnt;
        }
        $total     = $status_counts['in_progress'] + $status_counts['completed'];
        $completed = $status_counts['completed'];
        $in_prog   = $status_counts['in_progress'];

        // AVG score and flagged count (2 queries — both need different tables)
        $avg     = $wpdb->get_var( "SELECT AVG(i.score) FROM {$wpdb->prefix}wpi_inspections i WHERE i.status='completed' AND i.score IS NOT NULL{$safe_uw}" );
        $flagged = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}wpi_responses r JOIN {$wpdb->prefix}wpi_inspections i ON i.id=r.inspection_id WHERE r.flagged=1{$safe_uw}" );

        // ── FIX: Replace LIKE '%show_score%' pattern with explicit template ID exclusion list ──
        // Pull once the set of template IDs that have show_score disabled — avoids repeated full-scan LIKEs.
        $no_score_tpl_ids = array();
        $ss_like = '%"show_score":false%';
        $tpl_settings_rows = $wpdb->get_results(
            $wpdb->prepare( "SELECT id, settings FROM {$wpdb->prefix}wpi_templates WHERE settings LIKE %s", $ss_like )
        );
        foreach ( $tpl_settings_rows as $tsr ) {
            $ts_decoded = $tsr->settings ? json_decode( $tsr->settings, true ) : array();
            if ( isset($ts_decoded['show_score']) && $ts_decoded['show_score'] === false ) {
                $no_score_tpl_ids[] = (int) $tsr->id;
            }
        }
        // Build an exclusion clause reused by all scored queries — no more per-query LIKE
        $scored_excl = '';
        if ( !empty($no_score_tpl_ids) ) {
            $excl_in    = implode(',', $no_score_tpl_ids);
            $scored_excl = " AND (i.template_id IS NULL OR i.template_id NOT IN ($excl_in))";
        }

        // Derived helpers used inline in SQL strings
        // $scored_excl already excludes score-disabled templates via template_id NOT IN
        // For use inside CASE WHEN (no JOIN available), we need the bare NOT IN fragment
        $scored_excl_bare = $scored_excl; // identical — both use i.template_id
        $no_score_excl_in = !empty($no_score_tpl_ids) ? implode(',', $no_score_tpl_ids) : '0';

        $scored = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}wpi_inspections i
             WHERE i.status='completed' AND i.score IS NOT NULL{$safe_uw}{$scored_excl}"
        );

        // Recent inspections (1 query — settings already fetched above and cached in $no_score_tpl_ids)
        $recent_raw = $wpdb->get_results(
            "SELECT i.*, t.title as template_title, t.settings as template_settings,
                COALESCE(NULLIF(TRIM(CONCAT(COALESCE(um_fn.meta_value,''),' ',COALESCE(um_ln.meta_value,''))), ''), u.display_name) as inspector_name
             FROM {$wpdb->prefix}wpi_inspections i
             LEFT JOIN {$wpdb->prefix}wpi_templates t ON t.id=i.template_id
             LEFT JOIN {$wpdb->users} u ON u.ID=i.conducted_by
             LEFT JOIN {$wpdb->usermeta} um_fn ON um_fn.user_id=i.conducted_by AND um_fn.meta_key='first_name'
             LEFT JOIN {$wpdb->usermeta} um_ln ON um_ln.user_id=i.conducted_by AND um_ln.meta_key='last_name'
             WHERE i.status != 'archived'{$safe_uw} ORDER BY i.conducted_at DESC LIMIT 5"
        );

        // ── FIX: Batch-fetch {field:} token responses for all recent inspections in one query ──
        $recent_ids_needing_tokens = array();
        $recent_patterns = array();
        foreach ( $recent_raw as $row ) {
            $tsettings = $row->template_settings ? json_decode($row->template_settings, true) : array();
            $pattern = $tsettings['report_title'] ?? '';
            if ( $pattern && preg_match('/\{field:[^}]+\}/', $pattern) ) {
                $recent_ids_needing_tokens[] = (int) $row->id;
                $recent_patterns[ $row->id ] = $pattern;
            }
        }
        // Single bulk query for all token responses instead of one per inspection
        $token_responses_by_insp = array();
        if ( !empty($recent_ids_needing_tokens) ) {
            $ids_in_tokens = implode(',', $recent_ids_needing_tokens);
            $token_rows = $wpdb->get_results(
                "SELECT r.inspection_id, q.label, r.value as response_value
                 FROM `{$wpdb->prefix}wpi_responses` r
                 JOIN `{$wpdb->prefix}wpi_questions` q ON q.id = r.question_id
                 WHERE r.inspection_id IN ($ids_in_tokens)
                   AND q.type NOT IN ('instruction','page','section')"
            );
            foreach ( $token_rows as $tr ) {
                $slug = trim( strtolower( preg_replace('/[^a-z0-9]+/i', '_', trim($tr->label)) ), '_' );
                $token_responses_by_insp[ $tr->inspection_id ][ $slug ] = $tr->response_value;
            }
        }

        $recent = array();
        foreach ( $recent_raw as $row ) {
            $tsettings  = $row->template_settings ? json_decode($row->template_settings, true) : array();
            $show_score = isset($tsettings['show_score']) ? (bool)$tsettings['show_score'] : true;
            if ( ! $show_score ) $row->score = null;
            $pattern = $tsettings['report_title'] ?? '';
            if ( $pattern && isset($token_responses_by_insp[ $row->id ]) ) {
                $responses = $token_responses_by_insp[ $row->id ];
                $pattern = preg_replace_callback('/\{field:([^}]+)\}/', function($m) use ($responses) {
                    return isset($responses[$m[1]]) && $responses[$m[1]] !== '' ? $responses[$m[1]] : '';
                }, $pattern);
                $pattern = trim( preg_replace('/(\s*\/\s*){2,}/', ' / ', $pattern), ' /' );
            }
            $row->report_title_pattern = $pattern;
            $row->logo_url     = $tsettings['logo_url']     ?? '';
            $row->header_color = $tsettings['header_color'] ?? '';
            unset($row->template_settings);
            $recent[] = $row;
        }

        // ── Chart data (filtered by date range) ──────────────────

        // 1. Inspections over time
        if ( $days <= 30 ) {
            $group_fmt = '%Y-%m-%d';
            $label_fmt = '%d %b';
        } elseif ( $days <= 90 ) {
            $group_fmt = '%Y-%u';
            $label_fmt = 'W%u';
        } else {
            $group_fmt = '%Y-%m';
            $label_fmt = '%b %Y';
        }
        $trend_rows = $wpdb->get_results(
            "SELECT DATE_FORMAT(i.conducted_at, '{$group_fmt}') as period,
                    DATE_FORMAT(MIN(i.conducted_at), '{$label_fmt}') as label,
                    COUNT(*) as total,
                    SUM(CASE WHEN i.status='completed' THEN 1 ELSE 0 END) as completed,
                    ROUND(AVG(CASE WHEN i.score IS NOT NULL{$scored_excl_bare} THEN i.score END),1) as avg_score
             FROM {$wpdb->prefix}wpi_inspections i
             WHERE i.status IN ('in_progress','completed'){$safe_uw}{$dw}
             GROUP BY period ORDER BY period ASC LIMIT 60"
        );

        // ── FIX: Collapse 4 separate score-bucket COUNTs into one CASE WHEN query ──
        $score_dist_row = $wpdb->get_row(
            "SELECT
                SUM(CASE WHEN i.score >= 90 THEN 1 ELSE 0 END) as excellent,
                SUM(CASE WHEN i.score >= 75 AND i.score < 90 THEN 1 ELSE 0 END) as good,
                SUM(CASE WHEN i.score >= 50 AND i.score < 75 THEN 1 ELSE 0 END) as fair,
                SUM(CASE WHEN i.score < 50 AND i.score IS NOT NULL THEN 1 ELSE 0 END) as poor
             FROM {$wpdb->prefix}wpi_inspections i
             WHERE i.status='completed'{$safe_uw}{$dw}"
        );
        $score_dist = array(
            'excellent' => (int)($score_dist_row->excellent ?? 0),
            'good'      => (int)($score_dist_row->good      ?? 0),
            'fair'      => (int)($score_dist_row->fair      ?? 0),
            'poor'      => (int)($score_dist_row->poor      ?? 0),
        );

        // 3. Top templates
        $top_templates = $wpdb->get_results(
            "SELECT t.title, COUNT(i.id) as count,
                    ROUND(AVG(CASE WHEN i.score IS NOT NULL THEN i.score END),1) as avg_score
             FROM {$wpdb->prefix}wpi_inspections i
             LEFT JOIN {$wpdb->prefix}wpi_templates t ON t.id=i.template_id
             WHERE i.status IN ('in_progress','completed'){$safe_uw}{$dw}
             GROUP BY i.template_id ORDER BY count DESC LIMIT 5"
        );

        // 4. Top inspectors
        $top_inspectors = $wpdb->get_results(
            "SELECT COALESCE(NULLIF(TRIM(CONCAT(COALESCE(um_fn.meta_value,''),' ',COALESCE(um_ln.meta_value,''))), ''), u.display_name) as name,
                    COUNT(i.id) as count,
                    ROUND(AVG(CASE WHEN i.score IS NOT NULL THEN i.score END),1) as avg_score
             FROM {$wpdb->prefix}wpi_inspections i
             LEFT JOIN {$wpdb->users} u ON u.ID=i.conducted_by
             LEFT JOIN {$wpdb->usermeta} um_fn ON um_fn.user_id=i.conducted_by AND um_fn.meta_key='first_name'
             LEFT JOIN {$wpdb->usermeta} um_ln ON um_ln.user_id=i.conducted_by AND um_ln.meta_key='last_name'
             WHERE i.status IN ('in_progress','completed'){$safe_uw}{$dw}
             GROUP BY i.conducted_by ORDER BY count DESC LIMIT 5"
        );

        // ── FIX: Collapse 3 pass/fail/no_score COUNTs + LIKE into one CASE WHEN query ──
        $pf_row = $wpdb->get_row(
            "SELECT
                SUM(CASE WHEN i.score >= 75{$scored_excl_bare} THEN 1 ELSE 0 END) as pass,
                SUM(CASE WHEN i.score < 75 AND i.score IS NOT NULL{$scored_excl_bare} THEN 1 ELSE 0 END) as fail,
                SUM(CASE WHEN i.score IS NULL OR i.template_id IN ({$no_score_excl_in}) THEN 1 ELSE 0 END) as no_score
             FROM {$wpdb->prefix}wpi_inspections i
             WHERE i.status='completed'{$safe_uw}{$dw}"
        );
        $pass     = (int)($pf_row->pass     ?? 0);
        $fail     = (int)($pf_row->fail     ?? 0);
        $no_score = (int)($pf_row->no_score ?? 0);

        $this->json( array(
            'total_inspections' => $total,
            'completed'         => $completed,
            'in_progress'       => $in_prog,
            'avg_score'         => $avg ? round( $avg, 1 ) : null,
            'total_templates'   => $tmpl,
            'flagged_items'     => $flagged,
            'scored_count'      => $scored,
            'recent_inspections'=> $recent,
            'days'              => $days,
            'trend'             => $trend_rows,
            'score_dist'        => $score_dist,
            'top_templates'     => $top_templates,
            'top_inspectors'    => $top_inspectors,
            'pass_fail'         => array('pass'=>$pass,'fail'=>$fail,'no_score'=>$no_score),
        ) );
    }

    /* ── Permission helpers ───────────────────────────────────── */

    /**
     * Get all template IDs accessible to the current user, plus their access level.
     * Returns array of ['id' => int, 'access' => 'owner'|'edit'|'conduct'|'view']
     * Admins (manage_options) see everything as owner.
     */
    private function get_accessible_templates( $min_access = 'view', $allow_archived = false ) {
        global $wpdb;
        $uid      = get_current_user_id();
        $wpi_role = $this->get_wpi_role();

        $is_sys_owner = $this->is_system_owner();
        $user_org_id  = $this->get_org_id(); // 0 for system owner

        // Allow archived templates when reading for inspection reports
        $status_filter = $allow_archived ? "status IN ('active','archived')" : "status='active'";
        $status_join   = $allow_archived ? "t.status IN ('active','archived')" : "t.status='active'";

        // System owner sees ALL templates across all orgs
        if ( $is_sys_owner ) {
            $rows = $wpdb->get_results( "SELECT id FROM {$wpdb->prefix}wpi_templates WHERE {$status_filter}" );
            $out  = array();
            foreach ( $rows as $r ) $out[ (int)$r->id ] = 'owner';
            return $out;
        }

        // Org admins/super_managers see ALL templates in their org
        // PLUS any templates explicitly shared with them (including system-owner templates with org_id=0)
        if ( in_array( $wpi_role, array('administrator','super_manager') ) ) {
            if ( $user_org_id ) {
                $tw   = $this->org_template_where('t');
                $rows = $wpdb->get_results( "SELECT id FROM {$wpdb->prefix}wpi_templates t WHERE {$status_filter} $tw" );
            } else {
                $rows = $wpdb->get_results( $wpdb->prepare(
                    "SELECT id FROM {$wpdb->prefix}wpi_templates WHERE {$status_filter} AND created_by=%d", $uid
                ) );
            }
            $out = array();
            foreach ( $rows as $r ) $out[ (int)$r->id ] = 'owner';

            // Also include templates explicitly shared with this user or their teams
            $shared_ext = $wpdb->get_results( $wpdb->prepare(
                "SELECT s.template_id, s.access
                 FROM {$wpdb->prefix}wpi_template_shares s
                 JOIN {$wpdb->prefix}wpi_templates t ON t.id=s.template_id AND {$status_join}
                 WHERE (s.shared_with_type='user' AND s.shared_with_id=%d)
                    OR (s.shared_with_type='team' AND s.shared_with_id IN (
                        SELECT team_id FROM {$wpdb->prefix}wpi_team_members WHERE user_id=%d
                    ))", $uid, $uid
            ) );
            $levels = array('view'=>1,'conduct'=>2,'edit'=>3,'owner'=>4);
            foreach ( $shared_ext as $r ) {
                $tid = (int)$r->template_id;
                // Only add/upgrade — never downgrade existing owner access
                if ( !isset($out[$tid]) ) {
                    $out[$tid] = $r->access;
                } elseif ( ($levels[$r->access]??0) > ($levels[$out[$tid]]??0) ) {
                    $out[$tid] = $r->access;
                }
            }

            // Filter by minimum access level
            $min = $levels[$min_access] ?? 1;
            return array_filter( $out, function($a) use ($levels, $min) {
                return ($levels[$a] ?? 0) >= $min;
            });
        }

        $out = array();

        // 1. Templates the user created (owner) — scoped to their org
        $owned = $wpdb->get_results( $wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}wpi_templates WHERE created_by=%d AND {$status_filter}", $uid ) );
        foreach ( $owned as $r ) $out[ (int)$r->id ] = 'owner';

        // 2. Templates shared directly with this user
        $shared = $wpdb->get_results( $wpdb->prepare(
            "SELECT s.template_id, s.access
             FROM {$wpdb->prefix}wpi_template_shares s
             JOIN {$wpdb->prefix}wpi_templates t ON t.id=s.template_id AND {$status_join}
             WHERE s.shared_with_type='user' AND s.shared_with_id=%d", $uid ) );
        foreach ( $shared as $r ) {
            $tid = (int)$r->template_id;
            // Higher access wins (owner > edit > conduct > view)
            $levels = array('view'=>1,'conduct'=>2,'edit'=>3,'owner'=>4);
            if ( !isset($out[$tid]) || ($levels[$r->access]??0) > ($levels[$out[$tid]]??0) ) {
                $out[$tid] = $r->access;
            }
        }

        // 3. Templates shared with a team this user belongs to
        $team_shared = $wpdb->get_results( $wpdb->prepare(
            "SELECT s.template_id, s.access
             FROM {$wpdb->prefix}wpi_template_shares s
             JOIN {$wpdb->prefix}wpi_templates t ON t.id=s.template_id AND {$status_join}
             JOIN {$wpdb->prefix}wpi_team_members tm ON tm.team_id=s.shared_with_id AND tm.user_id=%d
             WHERE s.shared_with_type='team'", $uid ) );
        foreach ( $team_shared as $r ) {
            $tid = (int)$r->template_id;
            $levels = array('view'=>1,'conduct'=>2,'edit'=>3,'owner'=>4);
            if ( !isset($out[$tid]) || ($levels[$r->access]??0) > ($levels[$out[$tid]]??0) ) {
                $out[$tid] = $r->access;
            }
        }

        // Filter by minimum access level
        $levels = array('view'=>1,'conduct'=>2,'edit'=>3,'owner'=>4);
        $min    = $levels[$min_access] ?? 1;
        return array_filter( $out, function($a) use ($levels, $min) {
            return ($levels[$a] ?? 0) >= $min;
        });
    }

    /* ── Templates ────────────────────────────────────────────── */

    public function wpi_get_templates() {
        $this->check_nonce();
        global $wpdb;
        $status   = sanitize_text_field( $_GET['status'] ?? 'active' );
        $uid      = get_current_user_id();
        $wpi_role = $this->get_wpi_role();

        // User's personal archived list (shared templates hidden only for them)
        $user_archived_ids = get_user_meta( $uid, 'wpi_archived_templates', true );
        if ( !is_array($user_archived_ids) ) $user_archived_ids = array();
        $user_archived_ids = array_map('intval', $user_archived_ids);

        // user_archived_rows: templates personally archived by this user (shown in Archived tab)
        $user_archived_rows = array();
        if ( $status === 'archived' && !empty($user_archived_ids) ) {
            $ids_in_ua = implode(',', $user_archived_ids);
            $ua_rows = $wpdb->get_results(
                "SELECT t.*, t.org_id, u.display_name as author_name,
                    (SELECT COUNT(*) FROM {$wpdb->prefix}wpi_questions q WHERE q.template_id=t.id) as question_count,
                    (SELECT COUNT(*) FROM {$wpdb->prefix}wpi_inspections i WHERE i.template_id=t.id) as inspection_count
                 FROM {$wpdb->prefix}wpi_templates t
                 LEFT JOIN {$wpdb->users} u ON u.ID=t.created_by
                 WHERE t.status='active' AND t.id IN ($ids_in_ua)
                 ORDER BY t.updated_at DESC"
            );
            foreach ( $ua_rows as &$ua_r ) {
                $ua_r->my_access = 'shared';
                $s = $ua_r->settings ? json_decode($ua_r->settings, true) : array();
                $ua_r->settings = is_array($s) ? $s : array();
            }
            $user_archived_rows = $ua_rows ?: array();
            // If user has no owned/org archived templates, return user_archived only immediately
            if ( !$this->is_system_owner() ) {
                $has_own_archived = (bool)$wpdb->get_var( $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}wpi_templates WHERE created_by=%d AND status='archived'", $uid
                ) );
                $has_org_archived = false;
                if ( in_array($this->get_wpi_role(), array('administrator','super_manager','manager')) ) {
                    $oid = $this->get_org_id();
                    if ( $oid ) $has_org_archived = (bool)$wpdb->get_var( $wpdb->prepare(
                        "SELECT COUNT(*) FROM {$wpdb->prefix}wpi_templates WHERE org_id=%d AND status='archived'", $oid
                    ) );
                }
                if ( !$has_own_archived && !$has_org_archived ) {
                    $this->json( array('items'=>$user_archived_rows,'total'=>count($user_archived_rows),'page'=>1,'per_page'=>20,'total_pages'=>1) );
                    return;
                }
            }
        }

        // For archived/deleted, admins see all in their org; others see only their own
        $is_admin = $this->is_system_owner() || in_array($this->get_wpi_role(), array('administrator','super_manager','manager'));

        if ( $status !== 'active' ) {
            // Show archived/deleted — admins see all, others see own
            $org_id = $this->get_org_id();
            $org_where = '';
            if ( !$this->is_system_owner() && $org_id ) {
                $org_where = $wpdb->prepare(' AND t.org_id=%d', $org_id);
            }
            $user_where = $is_admin ? '' : $wpdb->prepare(' AND t.created_by=%d', $uid);
            $per_page = 20;
            $page     = max( 1, absint( $_GET['page'] ?? 1 ) );
            $offset   = ( $page - 1 ) * $per_page;
            // 'hidden' status only visible to system owner
            if ( $status === 'hidden' && ! $this->is_system_owner() ) {
                $this->error('Access denied', 403);
            }
            // System owner 'hidden' view: only their own hidden+archived templates
            if ( $status === 'hidden' && $this->is_system_owner() ) {
                $base_sql = $wpdb->prepare(
                    "FROM {$wpdb->prefix}wpi_templates t
                     LEFT JOIN {$wpdb->users} u ON u.ID=t.created_by
                     WHERE t.status IN ('hidden','archived') AND t.created_by=%d",
                    $uid
                );
            } else {
                $base_sql = $wpdb->prepare(
                    "FROM {$wpdb->prefix}wpi_templates t
                     LEFT JOIN {$wpdb->users} u ON u.ID=t.created_by
                     WHERE t.status=%s{$org_where}{$user_where}", $status
                );
            }
            $total = (int) $wpdb->get_var( "SELECT COUNT(*) $base_sql" );
            $rows = $wpdb->get_results(
                "SELECT t.*, t.org_id, u.display_name as author_name,
                    (SELECT COUNT(*) FROM {$wpdb->prefix}wpi_questions q WHERE q.template_id=t.id) as question_count,
                    (SELECT COUNT(*) FROM {$wpdb->prefix}wpi_inspections i WHERE i.template_id=t.id) as inspection_count
                 $base_sql ORDER BY t.updated_at DESC LIMIT $per_page OFFSET $offset"
            );
            foreach ( $rows as &$r ) {
                $s = $r->settings ? json_decode($r->settings, true) : array();
                $defaults = array('show_score'=>false,'show_summary'=>false,'show_photos'=>true,'show_signature'=>true,'show_notes'=>true,'show_flagged_only'=>false,'show_inspector'=>false,'show_date'=>false,'show_site'=>false,'show_gallery'=>true,'show_section_scores'=>false,'show_audit_title'=>true,'show_flagged_summary'=>false,'header_color'=>'#ffffff','header_text_color'=>'#000000','accent_color'=>'#1a3a5c','logo_url'=>'','logo_position'=>'left','report_title'=>'','footer_left'=>'{template}','footer_center'=>'','footer_right'=>'Page {page} of {pages}','footer_text'=>'','page_margin'=>'normal','pdf_filename'=>'','repeatable_sections'=>array());
                $r->settings = array_merge($defaults, is_array($s)?$s:array());
                $r->my_access = 'owner';
            }
            // Merge user-archived shared templates into the archived list
            if ( !empty($user_archived_rows) ) {
                $existing_ids = array_map(function($r){ return (int)$r->id; }, $rows);
                foreach ( $user_archived_rows as $ua ) {
                    if ( !in_array((int)$ua->id, $existing_ids) ) {
                        $rows[] = $ua;
                    }
                }
                $total = count($rows);
            }
            $this->json(array('items'=>$rows,'total'=>$total,'page'=>$page,'per_page'=>$per_page,'total_pages'=>(int)ceil($total/$per_page)));
            return;
        }

        // Active templates — use access map
        $access_map = $this->get_accessible_templates( 'view' );
        // Remove templates this user has personally archived from their active view
        if ( !empty($user_archived_ids) ) {
            foreach ( $user_archived_ids as $hidden_id ) {
                unset( $access_map[$hidden_id] );
            }
        }
        if ( empty( $access_map ) ) { $this->json( array('items'=>array(),'total'=>0,'page'=>1,'per_page'=>20,'total_pages'=>0) ); return; }
        $ids_in = implode( ',', array_map( 'intval', array_keys( $access_map ) ) );

        $per_page = 20;
        $page     = max( 1, absint( $_GET['page'] ?? 1 ) );
        $offset   = ( $page - 1 ) * $per_page;
        $total    = count( $access_map );

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT t.*, t.org_id, u.display_name as author_name,
                    (SELECT COUNT(*) FROM {$wpdb->prefix}wpi_questions q WHERE q.template_id=t.id) as question_count,
                    (SELECT COUNT(*) FROM {$wpdb->prefix}wpi_inspections i WHERE i.template_id=t.id) as inspection_count
                 FROM {$wpdb->prefix}wpi_templates t
                 LEFT JOIN {$wpdb->users} u ON u.ID=t.created_by
                 WHERE t.status=%s AND t.id IN ($ids_in)
                 ORDER BY t.updated_at DESC LIMIT $per_page OFFSET $offset", $status
            )
        );

        $defaults = array(
            'show_score'=>false,'show_summary'=>false,'show_photos'=>true,'show_signature'=>true,
            'show_notes'=>true,'show_flagged_only'=>false,'show_inspector'=>false,
            'show_date'=>false,'show_site'=>false,'show_gallery'=>true,'show_section_scores'=>false,
            'header_color'=>'#ffffff','header_text_color'=>'#000000','accent_color'=>'#1a3a5c','logo_url'=>'','logo_position'=>'left','report_title'=>'','footer_left'=>'{template}','footer_center'=>'','footer_right'=>'Page {page} of {pages}','footer_text'=>'','page_margin'=>'normal','pdf_filename'=>'',
            'repeatable_sections'=>array(),
        );
        $is_sys = $this->is_system_owner();
        foreach ( $rows as &$r ) {
            $s = $r->settings ? json_decode( $r->settings, true ) : array();
            $r->settings = array_merge( $defaults, is_array($s) ? $s : array() );
            $access = $access_map[ $r->id ] ?? 'view';
            // System owner always gets owner access regardless of share level
            if ( $is_sys ) $access = 'owner';
            $r->my_access = $access;
        }
        $this->json( array('items'=>$rows,'total'=>$total,'page'=>$page,'per_page'=>$per_page,'total_pages'=>(int)ceil($total/$per_page)) );
    }

    public function wpi_get_template() {
        $this->check_nonce();
        global $wpdb;
        $id = absint( $_GET['id'] ?? 0 );
        if ( !$id ) $this->error('id required');
        $row = $wpdb->get_row( $wpdb->prepare(
            "SELECT t.*, t.org_id, u.display_name as author_name,
                (SELECT COUNT(*) FROM {$wpdb->prefix}wpi_questions q WHERE q.template_id=t.id) as question_count,
                (SELECT COUNT(*) FROM {$wpdb->prefix}wpi_inspections i WHERE i.template_id=t.id) as inspection_count
             FROM {$wpdb->prefix}wpi_templates t
             LEFT JOIN {$wpdb->users} u ON u.ID=t.created_by
             WHERE t.id=%d", $id
        ) );
        // If template was deleted, return a minimal stub so inspection view still loads
        if ( !$row ) {
            $this->json( array( 'id' => $id, 'title' => '[Deleted Template]', 'status' => 'deleted',
                'settings' => array(), 'my_access' => 'none', 'deleted' => true ) );
        }
        $defaults = array('show_score'=>false,'show_summary'=>false,'show_photos'=>true,'show_signature'=>true,'show_notes'=>true,'show_flagged_only'=>false,'show_inspector'=>false,'show_date'=>false,'show_site'=>false,'show_gallery'=>true,'show_section_scores'=>false,'show_audit_title'=>true,'show_flagged_summary'=>false,'header_color'=>'#ffffff','header_text_color'=>'#000000','accent_color'=>'#1a3a5c','logo_url'=>'','logo_position'=>'left','report_title'=>'','footer_left'=>'{template}','footer_center'=>'','footer_right'=>'Page {page} of {pages}','footer_text'=>'','page_margin'=>'normal','pdf_filename'=>'','repeatable_sections'=>array());
        $s = $row->settings ? json_decode($row->settings, true) : array();
        $row->settings = array_merge($defaults, is_array($s)?$s:array());
        // Determine actual access level for current user
        $uid = get_current_user_id();
        if ( $this->is_system_owner() || (int)$row->created_by === (int)$uid ) {
            $row->my_access = 'owner';
        } else {
            $share = $wpdb->get_var( $wpdb->prepare(
                "SELECT access FROM {$wpdb->prefix}wpi_template_shares
                 WHERE template_id=%d AND (
                    (shared_with_type='user' AND shared_with_id=%d) OR
                    (shared_with_type='team' AND shared_with_id IN (
                        SELECT team_id FROM {$wpdb->prefix}wpi_team_members WHERE user_id=%d
                    ))
                 ) ORDER BY FIELD(access,'edit','conduct','view') LIMIT 1",
                $id, $uid, $uid
            ) );
            $row->my_access = $share ?: 'view';
        }
        $this->json($row);
    }

    public function wpi_create_template() {
        $this->check_nonce();
        $this->require_write_licence();
        $this->check_billing_limit('create_template');
        if ( ! $this->can('manager') ) $this->error('Permission denied — manager role required', 403);
        global $wpdb;
        $body = $this->input();
        $title = sanitize_text_field( $body['title'] ?? '' );
        if ( ! $title ) $this->error( 'Title is required' );
        $now = current_time( 'mysql' );
        $wpdb->insert( $wpdb->prefix . 'wpi_templates', array(
            'title'       => $title,
            'description' => sanitize_textarea_field( $body['description'] ?? '' ),
            'created_by'  => get_current_user_id(),
            'org_id'      => $this->org_id_for_insert(),
            'status'      => 'active',
            'created_at'  => $now,
            'updated_at'  => $now,
        ) );
        $template_id = $wpdb->insert_id;

        // Seed default header fields as plain text questions in General section
        $default_header_fields = array(
            array( 'label' => 'Audit Title',  'sort_order' => 0 ),
            array( 'label' => 'Site',         'sort_order' => 1 ),
            array( 'label' => 'Conducted on', 'sort_order' => 2 ),
            array( 'label' => 'Prepared by',  'sort_order' => 3 ),
        );
        foreach ( $default_header_fields as $hf ) {
            $wpdb->insert( $wpdb->prefix . 'wpi_questions', array(
                'template_id' => $template_id,
                'label'       => $hf['label'],
                'type'        => 'text',
                'section'     => 'General',
                'sort_order'  => $hf['sort_order'],
                'is_required' => 0,
                'is_scored'   => 1,
                'passing_answer' => '',
                'options'     => null,
                'logic'       => null,
                'repeatable'  => 0,
            ) );
        }

        $this->json( array( 'id' => $template_id, 'title' => $title ) );
    }

    public function wpi_update_template() {
        $this->check_nonce();
        global $wpdb;
        $body   = $this->input();
        $id     = absint( $body['id'] ?? 0 );
        // Require edit/owner access — super_manager+ sees all
        if ( ! $this->can('super_manager') ) {
            $am = $this->get_accessible_templates('edit');
            if ( ! isset($am[$id]) ) $this->error('Access denied', 403);
        }
        $fields = array();
        if ( isset( $body['title'] ) )       $fields['title']       = sanitize_text_field( $body['title'] );
        if ( isset( $body['description'] ) ) $fields['description'] = sanitize_textarea_field( $body['description'] );
        if ( isset( $body['status'] ) )      $fields['status']      = sanitize_text_field( $body['status'] );
        if ( isset( $body['settings'] ) && is_array( $body['settings'] ) ) {
            $s = $body['settings'];
            $clean = array(
                'show_score'         => ! empty( $s['show_score'] ),
                'show_summary'       => ! empty( $s['show_summary'] ),
                'show_photos'        => ! empty( $s['show_photos'] ),
                'show_signature'     => ! empty( $s['show_signature'] ),
                'show_notes'         => ! empty( $s['show_notes'] ),
                'show_flagged_only'  => ! empty( $s['show_flagged_only'] ),
                'show_inspector'     => ! empty( $s['show_inspector'] ),
                'show_date'          => ! empty( $s['show_date'] ),
                'show_site'          => ! empty( $s['show_site'] ),
                'show_gallery'       => ! empty( $s['show_gallery'] ),
                'show_section_scores' => ! empty( $s['show_section_scores'] ),
                'show_audit_title'    => ! empty( $s['show_audit_title'] ),
                'show_flagged_summary'=> ! empty( $s['show_flagged_summary'] ),
                'header_color'       => sanitize_hex_color( $s['header_color']      ?? '' ) ?: '#ffffff',
                'header_text_color'  => sanitize_hex_color( $s['header_text_color'] ?? '' ) ?: '#000000',
                'accent_color'       => sanitize_hex_color( $s['accent_color'] ?? '' ) ?: '#ffffff',
                'logo_url'           => esc_url_raw( $s['logo_url'] ?? '' ),
                'logo_position'      => in_array( ($s['logo_position'] ?? 'left'), array('left','center','right'), true ) ? $s['logo_position'] : 'left',
                'report_title'       => sanitize_textarea_field( $s['report_title'] ?? '' ),
                'footer_left'        => sanitize_text_field( $s['footer_left'] ?? '' ),
                'footer_center'      => sanitize_text_field( $s['footer_center'] ?? '' ),
                'footer_right'       => sanitize_text_field( $s['footer_right'] ?? '' ),
                'footer_text'        => sanitize_text_field( $s['footer_text'] ?? '' ),
                'page_margin'        => in_array( ($s['page_margin'] ?? 'normal'), array('narrow','normal','wide'), true ) ? $s['page_margin'] : 'normal',
                'pdf_filename'       => sanitize_text_field( $s['pdf_filename'] ?? '' ),
                'answer_colors'      => array(
                    'yes' => array(
                        'bg'     => sanitize_hex_color( $s['answer_colors']['yes']['bg']     ?? '#e6f4ea' ) ?: '#e6f4ea',
                        'border' => sanitize_hex_color( $s['answer_colors']['yes']['border'] ?? '#34a853' ) ?: '#34a853',
                        'text'   => sanitize_hex_color( $s['answer_colors']['yes']['text']   ?? '#34a853' ) ?: '#34a853',
                    ),
                    'no'  => array(
                        'bg'     => sanitize_hex_color( $s['answer_colors']['no']['bg']      ?? '#fce8e6' ) ?: '#fce8e6',
                        'border' => sanitize_hex_color( $s['answer_colors']['no']['border']  ?? '#ea4335' ) ?: '#ea4335',
                        'text'   => sanitize_hex_color( $s['answer_colors']['no']['text']    ?? '#ea4335' ) ?: '#ea4335',
                    ),
                    'na'  => array(
                        'bg'     => sanitize_hex_color( $s['answer_colors']['na']['bg']      ?? '#f8f9fa' ) ?: '#f8f9fa',
                        'border' => sanitize_hex_color( $s['answer_colors']['na']['border']  ?? '#5f6368' ) ?: '#5f6368',
                        'text'   => sanitize_hex_color( $s['answer_colors']['na']['text']    ?? '#5f6368' ) ?: '#5f6368',
                    ),
                ),
                'repeatable_sections' => ( isset( $s['repeatable_sections'] ) && is_array( $s['repeatable_sections'] ) )
                    ? array_fill_keys( array_map( 'sanitize_text_field', array_keys( $s['repeatable_sections'] ) ), true )
                    : array(),
                // Section show/hide conditions for report filtering.
                // Stored as: section name => { question_id, question_key, question_db_id, value, mode }.
                'section_conditions' => ( isset( $s['section_conditions'] ) && is_array( $s['section_conditions'] ) )
                    ? ( function( $conds ) {
                        $out = array();
                        foreach ( $conds as $sec_name => $cond ) {
                            if ( ! is_array( $cond ) ) continue;
                            $sec = sanitize_text_field( (string) $sec_name );
                            if ( $sec === '' ) continue;
                            $out[$sec] = array(
                                'question_id'    => sanitize_text_field( $cond['question_id'] ?? '' ),
                                'question_key'   => sanitize_text_field( $cond['question_key'] ?? '' ),
                                'question_db_id' => sanitize_text_field( $cond['question_db_id'] ?? '' ),
                                'value'          => sanitize_text_field( $cond['value'] ?? '' ),
                                'mode'           => in_array( ( $cond['mode'] ?? 'show' ), array( 'show', 'hide' ), true ) ? $cond['mode'] : 'show',
                            );
                        }
                        return $out;
                    } )( $s['section_conditions'] )
                    : array(),
                'notify_email'   => sanitize_textarea_field( $s['notify_email'] ?? '' ),
                'notify_subject' => sanitize_text_field( $s['notify_subject'] ?? '' ),
                'notify_body'    => sanitize_textarea_field( $s['notify_body'] ?? '' ),
            );
            $fields['settings'] = wp_json_encode( $clean );
        }
        if ( ! $id || empty( $fields ) ) $this->error( 'Invalid request' );

        // Touch updated_at so any edited template moves to the top of the Templates list.
        $fields['updated_at'] = current_time( 'mysql' );

        $wpdb->update( $wpdb->prefix . 'wpi_templates', $fields, array( 'id' => $id ) );
        $this->json( array( 'success' => true ) );
    }

    /* ── Questions ────────────────────────────────────────────── */


    /**
     * Hard delete — permanently removes template and all its data.
     * Only the template owner OR an administrator can do this.
     */
    public function wpi_delete_template() {
        $this->check_nonce();
        if ( ! $this->can('basic') ) $this->error( 'Access denied', 403 );
        global $wpdb;
        $body  = $this->input();
        $id    = absint( $body['id'] ?? 0 );
        $force = ! empty( $body['force'] ); // force=true only for templates with zero inspections
        if ( ! $id ) $this->error( 'id required' );
        $tmpl = $wpdb->get_row( $wpdb->prepare(
            "SELECT created_by, org_id, title FROM {$wpdb->prefix}wpi_templates WHERE id=%d", $id
        ) );
        if ( ! $tmpl ) $this->error( 'Template not found', 404 );
        $uid      = get_current_user_id();
        $is_owner = ( (int)$tmpl->created_by === (int)$uid );
        $is_admin = $this->can('administrator');
        if ( ! $this->is_system_owner() ) {
            $user_org = $this->get_org_id();
            if ( $user_org && (int)$tmpl->org_id !== (int)$user_org ) {
                $this->error( 'Access denied', 403 );
            }
        }
        if ( ! $is_owner && ! $is_admin ) {
            // Non-owner users must NOT archive/delete the template globally.
            // If the template is visible to them (shared directly, shared via team, or otherwise accessible),
            // hide it only from their own Templates list. Completed inspections remain untouched.
            $accessible = $this->get_accessible_templates( 'view', true );
            if ( isset( $accessible[ $id ] ) ) {
                $hidden = get_user_meta( $uid, 'wpi_archived_templates', true );
                if ( ! is_array( $hidden ) ) $hidden = array();
                $hidden[] = $id;
                $hidden   = array_values( array_unique( array_map( 'intval', $hidden ) ) );
                update_user_meta( $uid, 'wpi_archived_templates', $hidden );
                $this->json( array(
                    'success'       => true,
                    'user_archived' => true,
                    'message'       => 'Template removed from your templates. Completed inspections are preserved.'
                ) );
                return;
            }
            $this->error( 'Only the template owner or an Administrator can delete a template.', 403 );
        }

        // force=true = permanent delete (called from archived state via UI)
        // Otherwise archive — never hard-delete without explicit user intent
        if ( ! $force ) {
            $insp_count = (int)$wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}wpi_inspections WHERE template_id=%d", $id
            ) );
            $wpdb->update(
                $wpdb->prefix . 'wpi_templates',
                array( 'status' => 'archived' ),
                array( 'id' => $id )
            );
            $msg = $insp_count > 0
                ? 'Template archived — ' . $insp_count . ' inspection' . ($insp_count !== 1 ? 's' : '') . ' preserved.'
                : 'Template archived.';
            $this->json( array( 'success' => true, 'archived' => true, 'message' => $msg ) );
            return;
        }

        // force=true = "Delete from My List"
        // Sets status='hidden' — disappears from all normal lists
        // Only system owner can see and recover hidden templates
        // All questions, responses, inspections PRESERVED — reports keep working
        $wpdb->update(
            $wpdb->prefix . 'wpi_templates',
            array( 'status' => 'hidden' ),
            array( 'id' => $id )
        );
        $this->json( array( 'success' => true, 'hidden' => true ) );
    }

    /**
     * Hide — removes only the user's own share record for this template.
     * The template remains intact; just disappears from this user's list.
     */
    public function wpi_hide_template() {
        $this->check_nonce();
        global $wpdb;
        $body = $this->input();
        $id   = absint( $body['id'] ?? 0 );
        $uid  = get_current_user_id();
        if ( ! $id ) $this->error( 'id required' );
        $wpdb->delete( $wpdb->prefix . 'wpi_template_shares', array(
            'template_id'      => $id,
            'shared_with_type' => 'user',
            'shared_with_id'   => $uid,
        ) );
        $this->json( array( 'success' => true, 'hidden' => true ) );
    }

    public function wpi_get_questions() {
        $this->check_nonce();
        global $wpdb;
        $tid = absint( $_GET['template_id'] ?? 0 );
        // Access check — allow reading questions for active AND archived templates
        // so inspection reports always work regardless of template status
        if ( ! $this->can('manager') ) {
            $am = $this->get_accessible_templates('view', true); // true = include archived
            if ( ! isset($am[$tid]) ) $this->error('Access denied', 403);
        }
        $rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wpi_questions WHERE template_id=%d ORDER BY sort_order ASC", $tid
        ) );
        foreach ( $rows as &$r ) {
            if ( $r->options ) {
                $decoded = json_decode( $r->options, true );
                // Support both plain strings and {label,color} objects
                $r->options = is_array( $decoded ) ? array_values( array_filter( $decoded, function($o){
                    return is_array($o) ? !empty($o['label']) : strlen((string)$o);
                })) : array();
            } else {
                $r->options = array();
            }
            $r->logic = $r->logic ? json_decode( $r->logic, true ) : array();
            $r->answer_colors = ( isset($r->answer_colors) && $r->answer_colors )
                ? json_decode( $r->answer_colors, true )
                : null;
            $r->yes_no_colors = ( isset($r->yes_no_colors) && $r->yes_no_colors )
                ? json_decode( $r->yes_no_colors, true )
                : null;
            $r->repeatable      = (int)($r->repeatable ?? 0);
            $r->is_required     = (int)($r->is_required ?? 0);
            $r->is_scored       = isset($r->is_scored) ? (int)$r->is_scored : 1;
            $r->passing_answer  = isset($r->passing_answer) ? (string)$r->passing_answer : '';
        }
        $this->json( $rows );
    }

    public function wpi_save_questions() {
        $this->check_nonce();
        $this->require_write_licence();
        global $wpdb;
        $body      = $this->input();
        $tid       = absint( $body['template_id'] ?? 0 );
        $questions = $body['questions'] ?? array();
        if ( ! $tid ) $this->error( 'template_id required' );
        // Require edit/owner access — super_manager+ gets all, manager needs explicit edit access
        if ( ! $this->can('super_manager') ) {
            $am = $this->get_accessible_templates('edit');
            if ( ! isset($am[$tid]) ) $this->error('Access denied — edit permission required', 403);
        }

        // Get existing question IDs for this template
        $existing_ids = $wpdb->get_col( $wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}wpi_questions WHERE template_id=%d ORDER BY sort_order ASC", $tid
        ) );

        $used_ids    = array();
        $saved_count = 0;

        foreach ( $questions as $i => $q ) {
            $label   = sanitize_text_field( $q['label'] ?? '' );
            $type    = sanitize_text_field( $q['type']  ?? 'yes_no' );
            // Options: support both plain strings and {label,color} objects
            if ( isset( $q['options'] ) && is_array( $q['options'] ) ) {
                $clean_opts = array();
                foreach ( (array)$q['options'] as $opt ) {
                    if ( is_array($opt) ) {
                        $opt_label  = sanitize_text_field($opt['label'] ?? $opt['value'] ?? '');
                        $color      = sanitize_hex_color($opt['color'] ?? '') ?: '';
                        // text_color always black — all 10 preset background colours are readable with black
                        $text_color = '#000000';
                        if ( $opt_label !== '' ) $clean_opts[] = array('label'=>$opt_label,'color'=>$color,'text_color'=>$text_color);
                    } elseif ( strlen(trim((string)$opt)) ) {
                        $clean_opts[] = sanitize_text_field((string)$opt);
                    }
                }
                $options = wp_json_encode($clean_opts);
            } else {
                $options = null;
            }
            $logic         = isset( $q['logic'] ) && is_array( $q['logic'] ) && count( $q['logic'] ) > 0 ? wp_json_encode( $q['logic'] ) : null;
            $req            = ! empty( $q['is_required'] ) ? 1 : 0;
            $answer_colors  = isset( $q['answer_colors'] ) && is_array( $q['answer_colors'] ) ? wp_json_encode( $q['answer_colors'] ) : null;
            $yes_no_colors = null;
            if ( isset( $q['yes_no_colors'] ) && is_array( $q['yes_no_colors'] ) ) {
                $yes_no_colors = wp_json_encode( array(
                    'yes'      => sanitize_hex_color( $q['yes_no_colors']['yes']      ?? '' ) ?: '#16a34a',
                    'no'       => sanitize_hex_color( $q['yes_no_colors']['no']       ?? '' ) ?: '#dc2626',
                    'na'       => sanitize_hex_color( $q['yes_no_colors']['na']       ?? '' ) ?: '#6b7280',
                    'yes_text' => sanitize_hex_color( $q['yes_no_colors']['yes_text'] ?? '' ) ?: '#ffffff',
                    'no_text'  => sanitize_hex_color( $q['yes_no_colors']['no_text']  ?? '' ) ?: '#ffffff',
                    'na_text'  => sanitize_hex_color( $q['yes_no_colors']['na_text']  ?? '' ) ?: '#ffffff',
                ) );
            } elseif ( isset( $q['yes_no_colors'] ) && is_string( $q['yes_no_colors'] ) && $q['yes_no_colors'] ) {
                // Already JSON string — pass through
                $yes_no_colors = sanitize_text_field( $q['yes_no_colors'] );
            }
            $repeatable     = ! empty( $q['repeatable'] ) ? 1 : 0;
            $section        = sanitize_text_field( $q['section'] ?? '' );
            $is_scored      = isset( $q['is_scored'] ) ? (int)(bool)$q['is_scored'] : 1;
            $passing_answer = sanitize_text_field( $q['passing_answer'] ?? '' );

            // If this question has a real DB id (not a _key), update it
            $db_id = isset( $q['id'] ) && is_numeric( $q['id'] ) && $q['id'] > 0 ? absint( $q['id'] ) : 0;

            if ( $db_id && in_array( $db_id, $existing_ids ) ) {
                $wpdb->update( $wpdb->prefix . 'wpi_questions', array(
                    'label'          => $label,
                    'type'           => $type,
                    'options'        => $options,
                    'logic'          => $logic,
                    'answer_colors'  => $answer_colors,
                    'yes_no_colors'  => $yes_no_colors,
                    'is_required'    => $req,
                    'sort_order'     => $i,
                    'section'        => $section,
                    'repeatable'     => $repeatable,
                    'is_scored'      => $is_scored,
                    'passing_answer' => $passing_answer,
                ), array( 'id' => $db_id ) );
                $used_ids[] = $db_id;
            } else {
                $wpdb->insert( $wpdb->prefix . 'wpi_questions', array(
                    'template_id'    => $tid,
                    'label'          => $label,
                    'type'           => $type,
                    'options'        => $options,
                    'logic'          => $logic,
                    'answer_colors'  => $answer_colors,
                    'yes_no_colors'  => $yes_no_colors,
                    'is_required'    => $req,
                    'sort_order'     => $i,
                    'section'        => $section,
                    'repeatable'     => $repeatable,
                    'is_scored'      => $is_scored,
                    'passing_answer' => $passing_answer,
                ) );
                $used_ids[] = $wpdb->insert_id;
            }
            $saved_count++;
        }

        // Delete only questions that were removed (not in the new list)
        $to_delete = array_diff( $existing_ids, $used_ids );
        foreach ( $to_delete as $del_id ) {
            $wpdb->delete( $wpdb->prefix . 'wpi_questions', array( 'id' => absint( $del_id ) ) );
            // Also remove orphaned responses for this question
            $wpdb->delete( $wpdb->prefix . 'wpi_responses', array( 'question_id' => absint( $del_id ) ) );
        }

        // Touch parent template so question/section edits move it to the top of the Templates list.
        $wpdb->update(
            $wpdb->prefix . 'wpi_templates',
            array( 'updated_at' => current_time( 'mysql' ) ),
            array( 'id' => $tid )
        );

        $this->json( array( 'success' => true, 'count' => $saved_count ) );
    }

    /* ── Inspections ──────────────────────────────────────────── */

    public function wpi_get_inspections() {
        $this->check_nonce();
        global $wpdb;
        $uid      = get_current_user_id();
        $wpi_role = $this->get_wpi_role();
        $is_admin = in_array( $wpi_role, array('administrator','super_manager') );
        $status   = sanitize_text_field( $_GET['status'] ?? '' );

        $sw = $status
            ? $wpdb->prepare( ' AND i.status=%s', $status )
            : " AND i.status IN ('in_progress','completed')";

        // Server-side search
        $search_q = sanitize_text_field( $_GET['search'] ?? '' );
        $search_where = '';
        if ( $search_q ) {
            $like = '%' . $wpdb->esc_like( $search_q ) . '%';
            $search_where = $wpdb->prepare(
                ' AND (i.title LIKE %s OR t.title LIKE %s OR i.site_name LIKE %s)',
                $like, $like, $like
            );
        }

        // Site filter
        $site_name_filter = sanitize_text_field( $_GET['site_name'] ?? '' );
        $site_where = $site_name_filter
            ? $wpdb->prepare( ' AND i.site_name = %s', $site_name_filter )
            : '';

        $is_sys_owner = $this->is_system_owner();
        $user_org_id  = $this->get_org_id();

        // Get share records for this user to apply inspection_visibility filter
        $share_date_filters = array(); // template_id => shared_at date
        $own_only_template_ids = array(); // template_ids where user sees only own
        if ( !$is_sys_owner ) {
            $share_rows = $wpdb->get_results( $wpdb->prepare(
                "SELECT template_id, inspection_visibility, shared_at
                 FROM {$wpdb->prefix}wpi_template_shares
                 WHERE shared_with_type='user' AND shared_with_id=%d
                   AND inspection_visibility != 'all'",
                $uid
            ) );
            foreach ( $share_rows as $sr ) {
                if ( $sr->inspection_visibility === 'from_share_date' && $sr->shared_at ) {
                    $share_date_filters[(int)$sr->template_id] = $sr->shared_at;
                } elseif ( $sr->inspection_visibility === 'own_only' ) {
                    $own_only_template_ids[] = (int)$sr->template_id;
                }
            }
        }

        // Build date restriction clause for from_share_date templates
        $share_date_where = '';
        if ( !empty($share_date_filters) ) {
            $cases = array();
            foreach ( $share_date_filters as $tmpl_id => $shared_at ) {
                $cases[] = $wpdb->prepare(
                    "(i.template_id=%d AND i.conducted_at < %s)", $tmpl_id, $shared_at
                );
            }
            $share_date_where = ' AND NOT (' . implode(' OR ', $cases) . ')';
        }

        // Build own_only restriction — for those templates, only show own inspections
        $own_only_where = '';
        if ( !empty($own_only_template_ids) ) {
            $ids_oo = implode(',', $own_only_template_ids);
            $own_only_where = $wpdb->prepare(
                " AND (i.template_id NOT IN ($ids_oo) OR i.conducted_by=%d)", $uid
            );
        }

        // Build removed-orgs exclusion — inspections from orgs the user left are hidden but preserved
        $removed_orgs_where = '';
        if ( !$is_sys_owner ) {
            $removed_orgs = get_user_meta( $uid, 'wpi_removed_orgs', true );
            if ( is_array($removed_orgs) && !empty($removed_orgs) ) {
                $removed_in = implode(',', array_map('intval', $removed_orgs));
                $removed_orgs_where = " AND (i.org_id NOT IN ($removed_in) OR i.conducted_by={$uid})";
            }
        }

        if ( $is_sys_owner ) {
            $access_where = $share_date_where . $own_only_where;
        } elseif ( $is_admin && $user_org_id ) {
            $access_where = $this->org_inspection_where('i') . $share_date_where . $own_only_where;
        } else {
            $access_map = $this->get_accessible_templates( 'conduct' );
            if ( empty( $access_map ) ) {
                $access_where = $wpdb->prepare( ' AND i.conducted_by=%d', $uid );
            } else {
                $ids_in = implode( ',', array_map( 'absint', array_keys( $access_map ) ) );
                $access_where = $wpdb->prepare( ' AND (i.conducted_by=%d OR i.template_id IN ('.$ids_in.'))', $uid );
            }
            if ( $user_org_id ) $access_where .= $this->org_inspection_where('i');
            $access_where .= $share_date_where . $own_only_where . $removed_orgs_where;
        }
        $per_page = 20;
        $page     = max( 1, absint( $_GET['page'] ?? 1 ) );
        $offset   = ( $page - 1 ) * $per_page;

        $base_sql = "FROM {$wpdb->prefix}wpi_inspections i
             LEFT JOIN {$wpdb->prefix}wpi_templates t ON t.id=i.template_id
             LEFT JOIN {$wpdb->users} u ON u.ID=i.conducted_by
             LEFT JOIN {$wpdb->usermeta} um_fn ON um_fn.user_id=i.conducted_by AND um_fn.meta_key='first_name'
             LEFT JOIN {$wpdb->usermeta} um_ln ON um_ln.user_id=i.conducted_by AND um_ln.meta_key='last_name'
             WHERE 1=1 $access_where $sw $search_where $site_where";

        $total = (int) $wpdb->get_var( "SELECT COUNT(*) $base_sql" );

        $rows = $wpdb->get_results(
            "SELECT i.*, t.title as template_title, t.status as template_status,
                t.settings as template_settings,
                NULLIF(TRIM(CONCAT(COALESCE(um_fn.meta_value,''),' ',COALESCE(um_ln.meta_value,''))), '') as inspector_name,
                u.display_name as inspector_display
             $base_sql ORDER BY i.conducted_at DESC LIMIT $per_page OFFSET $offset"
        );
        // FIX: decode template_settings once per row — extract all needed fields here,
        // including show_score, so no second loop or extra DB queries are needed.
        foreach ( $rows as &$r ) {
            if ( empty($r->inspector_name) ) $r->inspector_name = $r->inspector_display;
            $ts = $r->template_settings ? json_decode($r->template_settings, true) : array();
            $r->logo_url     = $ts['logo_url']     ?? '';
            $r->header_color = $ts['header_color'] ?? '';
            $show_score      = isset($ts['show_score']) ? (bool)$ts['show_score'] : true;
            if ( ! $show_score ) $r->score = null;
            unset($r->template_settings);
        }
        unset($r);

        // FIX: sub-counts in one GROUP BY instead of two separate COUNT queries
        $counts = array();
        if ( ! $status ) {
            $sub_rows = $wpdb->get_results( "SELECT i.status, COUNT(*) as cnt $base_sql AND i.status IN ('in_progress','completed') GROUP BY i.status" );
            foreach ( $sub_rows as $sr ) {
                $counts[ $sr->status ] = (int) $sr->cnt;
            }
            $counts += array( 'in_progress' => 0, 'completed' => 0 ); // ensure keys exist
        }

        $this->json( array(
            'items'      => $rows,
            'total'      => $total,
            'page'       => $page,
            'per_page'   => $per_page,
            'total_pages'=> (int) ceil( $total / $per_page ),
            'counts'     => $counts,
        ) );
    }

    public function wpi_archive_inspection() {
        $this->check_nonce();
        global $wpdb;
        $body = $this->input();
        $id   = absint( $body['id'] ?? 0 );
        if ( ! $id ) $this->error( 'id required' );
        // Managers+ can archive any inspection in their org; standard users only their own.
        if ( ! $this->can('manager') ) {
            $uid = get_current_user_id();
            $owner = $wpdb->get_var( $wpdb->prepare(
                "SELECT conducted_by FROM {$wpdb->prefix}wpi_inspections WHERE id=%d", $id
            ) );
            if ( (int)$owner !== (int)$uid ) $this->error( 'Permission denied', 403 );
        }
        $wpdb->update(
            $wpdb->prefix . 'wpi_inspections',
            array( 'status' => 'archived' ),
            array( 'id' => $id )
        );
        $this->json( array( 'success' => true ) );
    }

    public function wpi_delete_inspection() {
        $this->check_nonce();
        // Only administrator or super_manager can delete
        if ( ! $this->can('administrator') && ! $this->can('super_manager') ) {
            $this->error('Permission denied — only administrators can delete inspections', 403);
        }
        global $wpdb;
        $id = absint( ($this->input())['id'] ?? 0 );
        if ( !$id ) $this->error('id required');
        // Must not be in_progress — only completed/archived can be deleted
        $current = $wpdb->get_var( $wpdb->prepare(
            "SELECT status FROM {$wpdb->prefix}wpi_inspections WHERE id=%d", $id
        ) );
        if ( $current === 'in_progress' ) {
            $this->error('Cannot delete an in-progress inspection. Complete or archive it first.', 400);
        }
        // Hard delete — remove responses and inspection permanently
        $wpdb->delete( $wpdb->prefix . 'wpi_responses', array( 'inspection_id' => $id ) );
        $wpdb->delete( $wpdb->prefix . 'wpi_inspections', array( 'id' => $id ) );
        $this->json( array('success' => true) );
    }

    public function wpi_restore_inspection() {
        $this->check_nonce();
        global $wpdb;
        $id = absint( ($this->input())['id'] ?? 0 );
        if ( !$id ) $this->error('id required');
        // Managers+ can restore any inspection in their org; standard users only their own.
        if ( ! $this->can('manager') ) {
            $uid = get_current_user_id();
            $owner = $wpdb->get_var( $wpdb->prepare(
                "SELECT conducted_by FROM {$wpdb->prefix}wpi_inspections WHERE id=%d", $id
            ) );
            if ( (int)$owner !== (int)$uid ) $this->error( 'Permission denied', 403 );
        }
        $wpdb->update( $wpdb->prefix.'wpi_inspections', array('status'=>'in_progress'), array('id'=>$id) );
        $this->json( array('success'=>true) );
    }

    public function wpi_archive_template() {
        $this->check_nonce();
        global $wpdb;
        $body   = $this->input();
        $id     = absint( $body['id'] ?? 0 );
        $status = sanitize_text_field( $body['status'] ?? 'archived' );
        if ( !in_array($status, array('archived','deleted','hidden','active')) ) $status = 'archived';
        if ( !$id ) $this->error('id required');
        if ( $status==='deleted' && !$this->can('administrator') ) $this->error('Permission denied', 403);
        
        $uid      = get_current_user_id();
        $tmpl_row = $wpdb->get_row( $wpdb->prepare(
            "SELECT created_by, org_id FROM {$wpdb->prefix}wpi_templates WHERE id=%d", $id
        ) );
        if ( ! $tmpl_row ) $this->error('Template not found', 404);
        $is_tmpl_owner = ( (int)$tmpl_row->created_by === (int)$uid );
        
        // Check permission
        if ( ! $this->is_system_owner() && ! $is_tmpl_owner ) {
            // Non-owner users must NOT archive the template globally.
            // If they can see/use the template, remove it only from their own Templates list.
            // This covers direct shares, team shares, and templates otherwise returned by access rules.
            $accessible = $this->get_accessible_templates( 'view', true );
            if ( ! isset( $accessible[ $id ] ) ) {
                $this->error('Only the template owner can archive this template', 403);
            }

            $hidden = get_user_meta( $uid, 'wpi_archived_templates', true );
            if ( !is_array($hidden) ) $hidden = array();
            $hidden[] = $id;
            $hidden   = array_values( array_unique( array_map('intval', $hidden) ) );
            update_user_meta( $uid, 'wpi_archived_templates', $hidden );
            return $this->json( array(
                'success'       => true,
                'user_archived' => true,
                'message'       => 'Template removed from your templates. Completed inspections are preserved.'
            ) );
        }
        
        // Owner or system owner: archive globally (change template status)
        if ( $status === 'active' ) {
            $cur_status = $wpdb->get_var( $wpdb->prepare(
                "SELECT status FROM {$wpdb->prefix}wpi_templates WHERE id=%d", $id
            ) );
            if ( $cur_status === 'hidden' && ! $this->is_system_owner() ) {
                $this->error('Only system owners can recover deleted templates', 403);
            }
        }
        $wpdb->update( $wpdb->prefix.'wpi_templates', array('status'=>$status), array('id'=>$id) );
        $this->json( array('success'=>true) );
    }

    public function wpi_restore_template() {
        $this->check_nonce();
        global $wpdb;
        $id = absint( ($this->input())['id'] ?? 0 );
        if ( !$id ) $this->error('id required');
        
        $uid = get_current_user_id();
        $owner = $wpdb->get_var( $wpdb->prepare(
            "SELECT created_by FROM {$wpdb->prefix}wpi_templates WHERE id=%d", $id
        ) );
        
        if ( ! $owner ) $this->error('Template not found', 404);
        
        $is_owner = ( (int)$owner === (int)$uid );
        
        // Check permission
        if ( ! $this->is_system_owner() && ! $is_owner ) {
            // Non-owner restore means restore it only to this user's own Templates list.
            // Allow this when it exists in that user's personal archived list, even if share method changed later.
            $hidden = get_user_meta( $uid, 'wpi_archived_templates', true );
            if ( !is_array($hidden) ) $hidden = array();
            $was_personally_archived = in_array( $id, array_map( 'intval', $hidden ), true );
            $accessible = $this->get_accessible_templates( 'view', true );
            if ( ! $was_personally_archived && ! isset( $accessible[ $id ] ) ) {
                $this->error( 'Permission denied', 403 );
            }
            $hidden = array_values( array_filter( $hidden, function($tid) use($id){ return (int)$tid !== $id; } ) );
            update_user_meta( $uid, 'wpi_archived_templates', $hidden );
            return $this->json( array('success'=>true, 'user_restored'=>true) );
        }
        
        // Owner or system owner: restore globally
        $wpdb->update( $wpdb->prefix.'wpi_templates', array('status'=>'active'), array('id'=>$id) );
        $this->json( array('success'=>true) );
    }

    public function wpi_create_inspection() {
        $this->check_nonce();
        $this->require_write_licence();
        $this->check_billing_limit('create_inspection');
        global $wpdb;
        $body = $this->input();
        $tid  = absint( $body['template_id'] ?? 0 );
        if ( ! $tid ) $this->error( 'template_id required' );
        $tmpl = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}wpi_templates WHERE id=%d", $tid ) );
        if ( ! $tmpl ) $this->error( 'Template not found', 404 );
        // Basic and guest cannot conduct — need standard+
        if ( ! $this->can('standard') ) {
            $this->error('Your role does not allow conducting inspections. Contact your administrator.', 403);
        }
        // super_manager+ can conduct any template; manager uses access map
        if ( ! $this->can('super_manager') ) {
            $uid = get_current_user_id();
            // Owner of this template can always conduct it
            $is_owner = ( (int)$tmpl->created_by === (int)$uid );
            if ( ! $is_owner ) {
                // get_accessible_templates includes team-shared templates
                $am = $this->get_accessible_templates('conduct');
                if ( ! isset($am[$tid]) ) {
                    // Also check view access to give better error message
                    $am_view = $this->get_accessible_templates('view');
                    if ( isset($am_view[$tid]) ) {
                        $this->error('You have view-only access to this template. Ask the owner to grant Conduct access.', 403);
                    }
                    $this->error('You do not have access to this template.', 403);
                }
            }
        }
        $title = sanitize_text_field( $body['title'] ?? ( $tmpl->title . ' – ' . date( 'Y-m-d' ) ) );
        $wpdb->insert( $wpdb->prefix . 'wpi_inspections', array(
            'template_id'  => $tid,
            'title'        => $title,
            'conducted_by' => get_current_user_id(),
            'org_id'       => $this->org_id_for_insert(),
            'site_name'    => sanitize_text_field( $body['site_name'] ?? '' ),
            'status'       => 'in_progress',
            'conducted_at' => current_time( 'mysql' ),
        ) );
        $this->json( array( 'id' => $wpdb->insert_id, 'title' => $title, 'template_id' => $tid ) );
    }

    public function wpi_get_inspection() {
        $this->check_nonce();
        global $wpdb;
        $id  = absint( $_GET['id'] ?? 0 );
        // Guests cannot view inspections
        if ( ! $this->can('basic') ) $this->error('Access denied', 403);
        $uid = get_current_user_id();
        // Check user can access this inspection
        if ( $this->is_system_owner() ) {
            // System owner: unrestricted
        } elseif ( $this->can('manager') ) {
            // Managers can see any inspection in their own org only
            $org_id   = $this->get_org_id();
            $ins_check = $wpdb->get_row( $wpdb->prepare(
                "SELECT org_id, conducted_by FROM {$wpdb->prefix}wpi_inspections WHERE id=%d", $id
            ) );
            if ( $ins_check && $org_id && (int)$ins_check->org_id !== (int)$org_id ) {
                $this->error('Access denied', 403);
            }
        } else {
            // Standard/basic: own inspections or accessible templates only
            $ins = $wpdb->get_row( $wpdb->prepare("SELECT conducted_by, template_id FROM {$wpdb->prefix}wpi_inspections WHERE id=%d", $id) );
            if ( $ins ) {
                $own_it = ( (int)$ins->conducted_by === (int)$uid );
                $am     = $this->get_accessible_templates('conduct');
                if ( ! $own_it && ! isset($am[$ins->template_id]) ) $this->error('Access denied', 403);
            }
        }
        $row = $wpdb->get_row( $wpdb->prepare(
            "SELECT i.*,
                t.title AS template_title,
                t.status AS template_status,
                COALESCE(
                    NULLIF(TRIM(CONCAT(COALESCE(um_fn.meta_value,''),' ',COALESCE(um_ln.meta_value,''))), ''),
                    u.display_name
                ) AS inspector_name
             FROM {$wpdb->prefix}wpi_inspections i
             LEFT JOIN {$wpdb->prefix}wpi_templates t ON t.id=i.template_id
             LEFT JOIN {$wpdb->users} u ON u.ID=i.conducted_by
             LEFT JOIN {$wpdb->usermeta} um_fn ON um_fn.user_id=i.conducted_by AND um_fn.meta_key='first_name'
             LEFT JOIN {$wpdb->usermeta} um_ln ON um_ln.user_id=i.conducted_by AND um_ln.meta_key='last_name'
             WHERE i.id=%d", $id
        ) );
        if ( ! $row ) $this->error( 'Not found', 404 );

        $raw_resp = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wpi_responses WHERE inspection_id=%d", $id
        ) );
        $responses = array();
        foreach ( $raw_resp as $resp ) {
            if ( ! empty( $resp->photos ) ) {
                $decoded = json_decode( $resp->photos, true );
                if ( is_array( $decoded ) ) {
                    $resp->photos = array_values( array_map( function( $item ) {
                        if ( is_array( $item ) ) return $item;
                        if ( is_string( $item ) ) return array( 'url' => $item, 'thumb' => $item, 'id' => 0 );
                        return null;
                    }, $decoded ) );
                    $resp->photos = array_values( array_filter( $resp->photos ) );
                } else {
                    $resp->photos = array();
                }
            } else {
                $resp->photos = array();
            }
            $responses[] = $resp;
        }

        $row->responses = $responses;
        $this->json( $row );
    }

    public function wpi_update_inspection() {
        $this->check_nonce();
        $this->require_write_licence();
        global $wpdb;
        $body      = $this->input();
        $id        = absint( $body['id'] ?? 0 );
        $responses = $body['responses'] ?? array();
        $status    = sanitize_text_field( $body['status'] ?? 'in_progress' );
        $notes     = sanitize_textarea_field( $body['notes'] ?? '' );
        if ( ! $id ) $this->error( 'id required' );

        // Verify the current user has conduct (or higher) access to this inspection.
        // Managers+ can update any inspection in their org.
        if ( ! $this->can('manager') ) {
            $uid = get_current_user_id();
            $ins = $wpdb->get_row( $wpdb->prepare(
                "SELECT conducted_by, template_id FROM {$wpdb->prefix}wpi_inspections WHERE id=%d", $id
            ) );
            if ( ! $ins ) $this->error( 'Inspection not found', 404 );
            $own_it = ( (int)$ins->conducted_by === (int)$uid );
            if ( ! $own_it ) {
                $am = $this->get_accessible_templates('conduct');
                if ( ! isset($am[$ins->template_id]) ) $this->error('Access denied', 403);
            }
        }

        // Before saving, collect which __r{n}__ instances have actual data in this payload.
        // Any instance with no data will have its DB rows deleted (cleans up ghost repeats).
        $repeat_groups = array(); // [ 'ri' => bool has_data ]
        foreach ( $responses as $r ) {
            $raw_qid = $r['question_id'] ?? '';
            if ( preg_match('/^__r(\d+)__/', (string)$raw_qid, $m) ) {
                $ri = $m[1];
                if ( ! isset($repeat_groups[$ri]) ) $repeat_groups[$ri] = false;
                $v = trim((string)($r['value'] ?? ''));
                $has_photos = ! empty($r['photos']);
                $has_notes  = trim((string)($r['notes'] ?? '')) !== '';
                if ( $v !== '' || $has_photos || $has_notes ) {
                    $repeat_groups[$ri] = true;
                }
            }
        }
        // Delete DB rows for repeat instances that have no data
        foreach ( $repeat_groups as $ri => $has_data ) {
            if ( ! $has_data ) {
                $wpdb->query( $wpdb->prepare(
                    "DELETE FROM {$wpdb->prefix}wpi_responses WHERE inspection_id=%d AND question_id LIKE %s",
                    $id, $wpdb->esc_like('__r'.$ri.'__') . '%'
                ) );
            }
        }

        foreach ( $responses as $r ) {
            $raw_qid  = $r['question_id'] ?? 0;
            // child_ keys: conditional follow-up; __r keys: repeatable section instances
            $is_child = is_string($raw_qid) && (
                strpos($raw_qid, 'child_') !== false ||
                strpos($raw_qid, '__r')    !== false
            );
            $qid = $is_child ? sanitize_text_field($raw_qid) : absint($raw_qid);

            if ( ! $qid ) continue;

            // Skip saving empty repeat instance responses — already deleted above
            if ( preg_match('/^__r(\d+)__/', (string)$raw_qid, $m) ) {
                if ( isset($repeat_groups[$m[1]]) && ! $repeat_groups[$m[1]] ) continue;
            }

            // For child questions, use string key stored as question_id varchar
            // We match on inspection_id + question_id (stored as string)
            if ( $is_child ) {
                $existing = $wpdb->get_var( $wpdb->prepare(
                    "SELECT id FROM {$wpdb->prefix}wpi_responses WHERE inspection_id=%d AND question_id=%s",
                    $id, $qid
                ) );
            } else {
                $existing = $wpdb->get_var( $wpdb->prepare(
                    "SELECT id FROM {$wpdb->prefix}wpi_responses WHERE inspection_id=%d AND question_id=%s",
                    $id, $qid
                ) );
            }

            $row = array(
                'inspection_id' => $id,
                'question_id'   => $qid,
                'value'         => sanitize_textarea_field( $r['value'] ?? '' ),
                'photos'        => isset( $r['photos'] ) ? wp_json_encode( array_map( function( $photo ) {
                    if ( is_array( $photo ) ) {
                        return array(
                            'url'   => esc_url_raw( $photo['url']   ?? '' ),
                            'thumb' => esc_url_raw( $photo['thumb'] ?? '' ),
                            'id'    => absint( $photo['id']          ?? 0  ),
                        );
                    }
                    return array( 'url' => esc_url_raw( (string) $photo ), 'thumb' => esc_url_raw( (string) $photo ), 'id' => 0 );
                }, (array) $r['photos'] ) ) : null,
                'flagged'       => ! empty( $r['flagged'] ) ? 1 : 0,
                'notes'         => sanitize_textarea_field( $r['notes'] ?? '' ),
            );
            if ( $existing ) $wpdb->update( $wpdb->prefix . 'wpi_responses', $row, array( 'id' => $existing ) );
            else             $wpdb->insert( $wpdb->prefix . 'wpi_responses', $row );
        }

        // ── Scoring rules ────────────────────────────────────────────
        // Only yes_no and multiple_choice variants are scored.
        // N/A answers are excluded from the average entirely (like unanswered).
        // is_scored = NULL → excluded from average
        // is_scored = 0    → always fail (if answered and not N/A)
        // is_scored = 1    → pass if answer matches passing_answer
        //                    for yes_no with no passing_answer set, 'yes' is the default pass
        $scoreable_types = array('yes_no','multiple_choice','select','dropdown','checkbox','radio','multi_select');
        // Any of these values = N/A — excluded from scoring entirely
        $na_values = array('n/a','na','n-a','not applicable','not_applicable','not applicable','n / a','none','not app','n.a','n.a.');
        // Helper to check if a value is N/A
        $is_na = function( $val_lc ) use ( $na_values ) {
            if ( in_array( $val_lc, $na_values, true ) ) return true;
            // Also treat any value starting with "n/a" or "not app" as N/A
            if ( strpos( $val_lc, 'n/a' ) === 0 ) return true;
            if ( strpos( $val_lc, 'not app' ) === 0 ) return true;
            return false;
        };

        $scored_questions = $wpdb->get_results( $wpdb->prepare(
            "SELECT q.id, q.type, q.is_scored, q.passing_answer, r.value
              FROM {$wpdb->prefix}wpi_questions q
              LEFT JOIN {$wpdb->prefix}wpi_responses r
                ON r.question_id = q.id AND r.inspection_id = %d
              WHERE q.template_id = (SELECT template_id FROM {$wpdb->prefix}wpi_inspections WHERE id=%d)
                AND q.is_scored IS NOT NULL",
            $id, $id
        ) );

        $score = null;
        if ( $scored_questions ) {
            $total  = 0;
            $passed = 0;

            foreach ( $scored_questions as $sq ) {
                // Only score yes_no and multiple_choice type questions
                if ( ! in_array( $sq->type, $scoreable_types, true ) ) continue;

                $is_scored = $sq->is_scored !== null ? (int)$sq->is_scored : null;
                if ( $is_scored === null ) continue; // explicitly excluded

                $val    = trim( (string)$sq->value );
                $val_lc = strtolower( $val );

                // Unanswered — exclude from average
                if ( $val === '' ) continue;

                // N/A answer — exclude from scoring entirely
                if ( $is_na( $val_lc ) ) continue;

                // is_scored = 0 → always fail
                if ( $is_scored === 0 ) {
                    $total++;
                    continue; // does not increment $passed
                }

                // is_scored = 1 → scored normally
                $total++;
                $passing = strtolower( trim( (string)$sq->passing_answer ) );

                if ( $passing === '' ) {
                    // No passing answer configured:
                    // yes_no defaults to 'yes' as pass; others: any answer = pass
                    if ( $sq->type === 'yes_no' ) {
                        if ( $val_lc === 'yes' ) $passed++;
                    } else {
                        $passed++; // any selection = pass when no passing_answer set
                    }
                } else {
                    if ( $val_lc === $passing ) $passed++;
                }
            }

            if ( $total > 0 ) {
                $score = round( ( $passed / $total ) * 100, 2 );
            }
        }

        $update = array( 'status' => $status, 'notes' => $notes, 'score' => $score );
        if ( $status === 'completed' ) $update['completed_at'] = current_time( 'mysql' );
        // Allow inline site_name update
        if ( isset( $body['site_name'] ) ) {
            $update['site_name'] = sanitize_text_field( $body['site_name'] );
        }

        // Resolve and save the form title (report_title pattern with field values)
        $tmpl_row = $wpdb->get_row( $wpdb->prepare(
            "SELECT settings, title FROM {$wpdb->prefix}wpi_templates WHERE id=(SELECT template_id FROM {$wpdb->prefix}wpi_inspections WHERE id=%d)",
            $id
        ) );
        if ( $tmpl_row ) {
            $ts      = $tmpl_row->settings ? json_decode( $tmpl_row->settings, true ) : array();
            $pattern = $ts['report_title'] ?? '';
            if ( ! $pattern ) $pattern = $tmpl_row->title;
            // Replace standard tokens
            $conducted_at = $wpdb->get_var( $wpdb->prepare( "SELECT conducted_at FROM {$wpdb->prefix}wpi_inspections WHERE id=%d", $id ) );
            $dt = $conducted_at ? new DateTime( $conducted_at ) : new DateTime();
            $pattern = str_replace( '{date}',     $dt->format('d/m/Y'),       $pattern );
            $pattern = str_replace( '{template}', $tmpl_row->title,           $pattern );
            $pattern = str_replace( '{score}',    $score !== null ? $score.'%' : '', $pattern );
            $cur_uid = get_current_user_id();
            $pattern = str_replace( '{user}', wp_get_current_user()->display_name ?? '', $pattern );
            // Replace {field:slug} tokens from saved responses
            if ( preg_match( '/\{field:[^}]+\}/', $pattern ) ) {
                $resp_rows = $wpdb->get_results( $wpdb->prepare(
                    "SELECT q.label, r.value as response_value
                     FROM {$wpdb->prefix}wpi_responses r
                     JOIN {$wpdb->prefix}wpi_questions q ON q.id = CAST(r.question_id AS UNSIGNED)
                     WHERE r.inspection_id=%d AND r.question_id REGEXP '^[0-9]+$'
                       AND q.type NOT IN ('instruction','page','section') LIMIT 50",
                    $id
                ) );
                $field_map = array();
                foreach ( $resp_rows as $rr ) {
                    $slug = trim( strtolower( preg_replace('/[^a-z0-9]+/i','_', trim($rr->label)) ), '_' );
                    $field_map[$slug] = $rr->response_value;
                }
                $pattern = preg_replace_callback( '/\{field:([^}]+)\}/', function($m) use ($field_map) {
                    $val = $field_map[$m[1]] ?? '';
                    if ( $val && preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}/', $val) ) {
                        try { $val = (new DateTime($val))->format('d/m/Y'); } catch(\Exception $e) {}
                    } elseif ( $val && preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}/', $val) ) {
                        try { $val = (new DateTime($val))->format('d/m/Y'); } catch(\Exception $e) {}
                    }
                    return $val;
                }, $pattern );
            }
            // Clean up empty separators
            $pattern = preg_replace('/(\s*[\/\-]\s*){2,}/', ' / ', $pattern);
            $pattern = trim( $pattern, ' /-' );
            if ( $pattern ) $update['title'] = sanitize_text_field( $pattern );
        }

        $wpdb->update( $wpdb->prefix . 'wpi_inspections', $update, array( 'id' => $id ) );

        // Send completion email + all pending action notifications
        if ( $status === 'completed' ) {
            self::send_completion_email( $id );
            // Send action notifications for all unnotified actions on this inspection
            $pending_actions = $wpdb->get_results( $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}wpi_actions
                  WHERE inspection_id=%d AND notified=0 AND assigned_email != '' AND assigned_email IS NOT NULL",
                $id
            ) );
            if ( $pending_actions ) {
                $cur_uid  = get_current_user_id();
                $cur_fn   = get_user_meta( $cur_uid, 'first_name', true );
                $cur_ln   = get_user_meta( $cur_uid, 'last_name', true );
                $cur_name = trim("$cur_fn $cur_ln") ?: wp_get_current_user()->display_name;
                $notified_ids = array();
                foreach ( $pending_actions as $a ) {
                    // Get original creator name
                    $creator_fn   = get_user_meta( $a->created_by, 'first_name', true );
                    $creator_ln   = get_user_meta( $a->created_by, 'last_name', true );
                    $creator_name = trim("$creator_fn $creator_ln")
                        ?: get_userdata($a->created_by)->display_name ?? $cur_name;
                    $this->send_action_notification(
                        $a->id, $a->assigned_email, $a->assigned_name,
                        $a->question_label, $a->note,
                        ($a->due_date && $a->due_date !== '0000-00-00') ? $a->due_date : '',
                        $a->priority, $creator_name,
                        $a->question_answer ?? '',
                        $a->question_note ?? ''
                    );
                    // Push already sent at action create time — skip duplicate
                    $notified_ids[] = (int)$a->id;
                }
                if ( $notified_ids ) {
                    $in = implode(',', $notified_ids);
                    $wpdb->query( "UPDATE {$wpdb->prefix}wpi_actions SET notified=1 WHERE id IN ($in)" );
                }
            }
        }

        $this->json( array( 'success' => true, 'score' => $score ) );
    }

    /* ── Photo Upload ─────────────────────────────────────────── */

    public function wpi_upload_photo() {
        // Verify nonce
        if ( ! check_ajax_referer( 'wpi_nonce', 'nonce', false ) ) {
            wp_send_json_error( array( 'message' => 'Security check failed. Please refresh the page and try again.' ), 403 );
        }

        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array( 'message' => 'You must be logged in to upload photos.' ), 401 );
        }

        // Check the file arrived
        if ( empty( $_FILES['photo'] ) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK ) {
            $php_errors = array(
                UPLOAD_ERR_INI_SIZE   => 'File exceeds server upload_max_filesize limit.',
                UPLOAD_ERR_FORM_SIZE  => 'File exceeds form MAX_FILE_SIZE limit.',
                UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded.',
                UPLOAD_ERR_NO_FILE    => 'No file was sent.',
                UPLOAD_ERR_NO_TMP_DIR => 'Server is missing a temporary folder.',
                UPLOAD_ERR_CANT_WRITE => 'Server failed to write file to disk.',
                UPLOAD_ERR_EXTENSION  => 'A PHP extension blocked the upload.',
            );
            $code = isset( $_FILES['photo'] ) ? $_FILES['photo']['error'] : UPLOAD_ERR_NO_FILE;
            $msg  = isset( $php_errors[ $code ] ) ? $php_errors[ $code ] : 'Upload error code: ' . $code;
            wp_send_json_error( array( 'message' => $msg ), 400 );
        }

        $file = $_FILES['photo'];

        // Size check (20 MB)
        if ( $file['size'] > 20 * 1024 * 1024 ) {
            wp_send_json_error( array( 'message' => 'File too large. Maximum 20 MB per photo.' ), 400 );
        }

        // MIME check — use finfo if available, otherwise fall back to getimagesize
        $allowed_mime = array( 'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp' );
        if ( function_exists( 'finfo_open' ) ) {
            $finfo = new finfo( FILEINFO_MIME_TYPE );
            $mime  = $finfo->file( $file['tmp_name'] );
        } else {
            $info = @getimagesize( $file['tmp_name'] );
            $mime = $info ? $info['mime'] : 'unknown';
        }
        if ( ! in_array( $mime, $allowed_mime, true ) ) {
            wp_send_json_error( array( 'message' => 'Invalid file type (' . $mime . '). Only JPEG, PNG, GIF and WebP allowed.' ), 400 );
        }

        // Load WP upload helpers
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        // upload_files cap is granted via wpi_filter_upload_cap (class-admin.php)
        // only when action === 'wpi_upload_photo', so no manual add/remove needed.
        $attachment_id = media_handle_upload( 'photo', 0 );

        if ( is_wp_error( $attachment_id ) ) {
            wp_send_json_error( array( 'message' => $attachment_id->get_error_message() ), 500 );
        }

        $url   = wp_get_attachment_url( $attachment_id );
        $thumb = wp_get_attachment_image_url( $attachment_id, 'medium' );

        wp_send_json_success( array(
            'id'    => $attachment_id,
            'url'   => $url,
            'thumb' => $thumb ? $thumb : $url,
        ) );
    }

    /* ── Email Notification ───────────────────────────────────── */

    /**
     * Send a completion notification email for an inspection.
     * Resolves {date}, {time}, {template}, {site}, {inspector}, {score},
     * and {field:slug} tokens against the inspection's actual responses.
     */
    public static function send_completion_email( $inspection_id ) {
        global $wpdb;

        // Load inspection + template settings
        $inspection = $wpdb->get_row( $wpdb->prepare(
            "SELECT i.*,
                    t.title AS template_title, t.settings AS t_settings,
                    COALESCE(NULLIF(TRIM(CONCAT(COALESCE(um_fn.meta_value,''),' ',COALESCE(um_ln.meta_value,''))), ''), u.display_name) AS inspector_name
             FROM {$wpdb->prefix}wpi_inspections i
             LEFT JOIN {$wpdb->prefix}wpi_templates t ON t.id = i.template_id
             LEFT JOIN {$wpdb->users} u ON u.ID = i.conducted_by
             LEFT JOIN {$wpdb->usermeta} um_fn ON um_fn.user_id = i.conducted_by AND um_fn.meta_key = 'first_name'
             LEFT JOIN {$wpdb->usermeta} um_ln ON um_ln.user_id = i.conducted_by AND um_ln.meta_key = 'last_name'
             WHERE i.id = %d", $inspection_id
        ) );
        if ( ! $inspection ) return;

        $cfg = array(
            'notify_email'   => '',
            'notify_subject' => 'Inspection Complete: {template} - {date}',
            'notify_body'    => "Hi,\n\nThe following inspection has been completed:\n\nTemplate: {template}\nInspector: {inspector}\nDate: {date}\nSite: {site}\nScore: {score}\n\nPlease log in to view the full report.",
        );
        $t_cfg = $inspection->t_settings ? json_decode( $inspection->t_settings, true ) : array();
        if ( is_array( $t_cfg ) ) $cfg = array_merge( $cfg, $t_cfg );

        $to = trim( $cfg['notify_email'] );
        if ( ! $to ) return;

        // Parse recipients — supports JSON array or legacy plain email
        $recipients = array();
        $notify_filter = '';
        $decoded = json_decode( $to, true );
        if ( is_array( $decoded ) ) {
            // New format: array of {type, id, email, name} OR {recipients:[], __filter:''}
            if ( isset( $decoded['recipients'] ) || isset( $decoded['__filter'] ) ) {
                $notify_filter = $decoded['__filter'] ?? '';
                $list = $decoded['recipients'] ?? array();
            } else {
                $list = $decoded;
            }
            foreach ( $list as $r ) {
                if ( ! empty( $r['email'] ) && is_email( $r['email'] ) ) {
                    $recipients[] = $r['email'];
                }
            }
        } else {
            // Legacy plain email
            if ( is_email( $to ) ) $recipients[] = $to;
        }
        if ( empty( $recipients ) ) return;

        // Build field slug → value map from responses
        $questions = $wpdb->get_results( $wpdb->prepare(
            "SELECT q.id, q.label, q.type FROM {$wpdb->prefix}wpi_questions q
             WHERE q.template_id = %d AND q.type NOT IN ('instruction','page')
             ORDER BY q.sort_order ASC", $inspection->template_id
        ) );
        $responses = $wpdb->get_results( $wpdb->prepare(
            "SELECT question_id, value FROM {$wpdb->prefix}wpi_responses WHERE inspection_id = %d", $inspection_id
        ) );
        $rmap = array();
        foreach ( $responses as $r ) {
            if ( is_numeric( $r->question_id ) ) $rmap[ (int)$r->question_id ] = $r->value;
        }
        $format_token_value = function( $raw, $type ) {
            if ( $raw === null || $raw === '' ) return '';
            $type = (string) $type;
            try {
                if ( in_array( $type, array('datetime','date_time'), true ) ) {
                    $dt = new DateTime( (string) $raw, wp_timezone() );
                    return $dt->format('d M Y, g:i A');
                }
                if ( $type === 'date' ) {
                    $dt = new DateTime( (string) $raw, wp_timezone() );
                    return $dt->format('d M Y');
                }
                if ( $type === 'time' ) {
                    $dt = new DateTime( '1970-01-01 ' . trim((string) $raw), wp_timezone() );
                    return $dt->format('g:i A');
                }
            } catch ( Exception $e ) {}
            return is_scalar( $raw ) ? (string) $raw : '';
        };

        $field_map = array();
        foreach ( $questions as $q ) {
            $slug = strtolower( preg_replace( '/[^a-z0-9]+/i', '_', $q->label ?? '' ) );
            $slug = trim( $slug, '_' );
            $slug = substr( $slug, 0, 40 );
            if ( $slug && isset( $rmap[ (int)$q->id ] ) ) {
                $field_map[ $slug ] = $format_token_value( $rmap[ (int)$q->id ], $q->type ?? '' );
            }
        }

        // Token resolver closure
        $dt = ! empty( $inspection->completed_at ) ? new DateTime( $inspection->completed_at ) : new DateTime();
        $resolve = function( $text ) use ( $inspection, $dt, $field_map ) {
            $text = str_replace( '{date}',      $dt->format('d M Y'),                                            $text );
            $text = str_replace( '{time}',      $dt->format('g:i A'),                                            $text );
            $text = str_replace( '{template}',  $inspection->template_title ?? $inspection->title,              $text );
            $text = str_replace( '{site}',      $inspection->site_name ?: '',                                   $text );
            $text = str_replace( '{inspector}', $inspection->inspector_name ?? '',                              $text );
            $text = str_replace( '{score}',     ( $inspection->score !== null ? round( $inspection->score ).'%' : '—' ), $text );
            $text = preg_replace_callback( '/\{field:([^}]+)\}/', function( $m ) use ( $field_map ) {
                $t = trim( $m[1] );
                if ( isset( $field_map[ $t ] ) ) return $field_map[ $t ];
                // Partial prefix match
                foreach ( $field_map as $k => $v ) {
                    if ( strpos( $k, $t ) === 0 ) return $v;
                }
                return '';
            }, $text );
            return $text;
        };

        $subject = $resolve( $cfg['notify_subject'] );
        $body    = $resolve( $cfg['notify_body'] );

        // Strip any raw wpi_pdf download URLs that may have been saved into the body template
        $body = preg_replace( '/https?:\/\/[^\s]*[?&]wpi_pdf=[^\s]*/i', '', $body );
        $body = preg_replace( '/[?&]wpi_pdf=[^\s]*/i', '', $body );
        $body = trim( $body );

        $attachments = array();
        $tmp_path    = class_exists( 'WPI_PDF' ) && method_exists( 'WPI_PDF', 'get_rich_pdf_file' )
            ? WPI_PDF::get_rich_pdf_file( $inspection_id )
            : WPI_PDF_Email::get_pdf_file( $inspection_id );
        if ( ( ! $tmp_path || ! file_exists( $tmp_path ) ) && class_exists( 'WPI_PDF_Email' ) && method_exists( 'WPI_PDF_Email', 'get_legacy_pdf_file' ) ) {
            $tmp_path = WPI_PDF_Email::get_legacy_pdf_file( $inspection_id );
        }
        $named_path  = null;
        if ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
            error_log( 'WPI Email: pdf tmp_path=' . var_export( $tmp_path, true ) . ' exists=' . ( $tmp_path && file_exists( $tmp_path ) ? 'yes' : 'no' ) );
        }
        if ( $tmp_path && file_exists( $tmp_path ) ) {
            // Attachment filename = Report Title (same as the PDF download filename)
            $raw_attach_name = trim( $cfg['report_title'] ?? '' );
            if ( $raw_attach_name ) {
                $attach_name = $resolve( $raw_attach_name );
            } else {
                $attach_name = $inspection->title;
            }
            $attach_name = trim( preg_replace( '/\s+/', ' ', $attach_name ) ) ?: $inspection->title;
            $safe_name   = preg_replace( '/[^a-zA-Z0-9_\-\. ]/', '_', $attach_name );
            // Use a unique suffix so concurrent reports never collide
            $named_path  = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $safe_name . '_' . $inspection_id . '.pdf';
            // copy instead of rename — safer across filesystem boundaries
            if ( copy( $tmp_path, $named_path ) ) {
                @unlink( $tmp_path ); // remove the original temp file immediately
                $attachments[] = $named_path;
            } else {
                // Fall back to original path if copy failed
                $attachments[] = $tmp_path;
                $named_path    = $tmp_path;
            }
        }

        // Apply __filter: only send if any response value contains the filter text
        if ( ! empty( $notify_filter ) ) {
            $filter_lc = strtolower( trim( $notify_filter ) );
            $has_match = false;
            foreach ( $rmap as $val ) {
                if ( strpos( strtolower( (string) $val ), $filter_lc ) !== false ) {
                    $has_match = true;
                    break;
                }
            }
            if ( ! $has_match ) return; // condition not met — skip notification
        }

        $headers = array( 'Content-Type: text/plain; charset=UTF-8' );
        foreach ( $recipients as $recipient_email ) {
            $mail_sent = wp_mail( $recipient_email, $subject, $body, $headers, $attachments );
            if ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
                error_log( 'WPI Email: sent=' . var_export( $mail_sent, true ) . ' to=' . $recipient_email );
            }
        }
        $mail_sent = ! empty( $recipients );

        // Clean up temp file AFTER wp_mail returns
        if ( $named_path && file_exists( $named_path ) ) {
            @unlink( $named_path );
        }

        // ── Outbound integrations: Webhook / Zapier / Slack / Custom API ──
        // Load org-scoped settings for this inspection's org
        $int_org_id  = (int)( $inspection->org_id ?? 0 );
        $int_settings = $int_org_id
            ? get_option( 'wpi_api_settings_org_' . $int_org_id, array() )
            : get_option( 'wpi_api_settings', array() );

        // Build shared inspection payload
        $int_payload = array(
            'event'          => 'inspection.completed',
            'source'         => 'Audit4Me WP Inspector',
            'timestamp'      => current_time( 'c' ),
            'inspection_id'  => (int) $inspection_id,
            'title'          => $inspection->title ?? '',
            'template'       => $inspection->template_title ?? '',
            'inspector'      => $inspection->inspector_name ?? '',
            'site'           => $inspection->site_name ?? '',
            'score'          => isset( $inspection->score ) ? (float) $inspection->score : null,
            'status'         => $inspection->status ?? 'completed',
            'conducted_at'   => $inspection->conducted_at ?? '',
            'completed_at'   => $inspection->completed_at ?? '',
            'report_url'     => home_url( '/?wpi=1&inspection=' . $inspection_id ),
        );
        $int_json = json_encode( $int_payload );

        // Helper: fire-and-forget POST with timeout 8s
        $do_post = function( $url, $headers, $body ) {
            if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) return;
            wp_remote_post( $url, array(
                'method'    => 'POST',
                'headers'   => $headers,
                'body'      => $body,
                'timeout'   => 8,
                'blocking'  => false, // non-blocking so it never delays the user
                'sslverify' => true,
            ) );
        };

        // 1. Custom Webhook (existing)
        $webhook_url = trim( $int_settings['webhook_url'] ?? '' );
        if ( $webhook_url ) {
            $wh_headers = array( 'Content-Type' => 'application/json' );
            if ( ! empty( $int_settings['webhook_secret'] ) ) {
                $wh_headers['X-WPI-Signature'] = 'sha256=' . hash_hmac( 'sha256', $int_json, $int_settings['webhook_secret'] );
            }
            $do_post( $webhook_url, $wh_headers, $int_json );
        }

        // 2. Custom API Endpoint
        $custom_url = trim( $int_settings['custom_api_url'] ?? '' );
        if ( $custom_url ) {
            $custom_headers = array( 'Content-Type' => 'application/json' );
            $custom_key = trim( $int_settings['custom_api_key'] ?? '' );
            if ( $custom_key ) {
                $custom_headers['Authorization'] = 'Bearer ' . $custom_key;
            }
            $do_post( $custom_url, $custom_headers, $int_json );
        }
    }

    /* ── PDF ──────────────────────────────────────────────────── */

    public function wpi_download_pdf() {
        // PDF uses its own nonce check (query param based)
        $nonce = sanitize_text_field( $_GET['nonce'] ?? '' );
        if ( ! wp_verify_nonce( $nonce, 'wpi_nonce' ) || ! is_user_logged_in() ) {
            wp_die( 'Unauthorised', 401 );
        }
        $id = absint( $_GET['id'] ?? 0 );
        if ( ! $id ) wp_die( 'Invalid inspection ID', 400 );

        // Verify the current user can access this inspection (mirrors wpi_get_inspection).
        // Managers+ can download any inspection PDF in their org.
        if ( ! $this->can('manager') ) {
            global $wpdb;
            $uid = get_current_user_id();
            $ins = $wpdb->get_row( $wpdb->prepare(
                "SELECT conducted_by, template_id FROM {$wpdb->prefix}wpi_inspections WHERE id=%d", $id
            ) );
            if ( ! $ins ) wp_die( 'Inspection not found', 404 );
            $own_it = ( (int)$ins->conducted_by === (int)$uid );
            if ( ! $own_it ) {
                $am = $this->get_accessible_templates('conduct');
                if ( ! isset($am[$ins->template_id]) ) wp_die( 'Access denied', 403 );
            }
        }

        // Clean any output buffers before sending file
        while ( ob_get_level() ) ob_end_clean();
        $pdf = new WPI_PDF();
        $pdf->generate( $id );
    }

    /* ── Teams ───────────────────────────────────────────────── */

    public function wpi_get_teams() {
        $this->check_nonce();
        global $wpdb;
        $uid      = get_current_user_id();
        $wpi_role = $this->get_wpi_role();
        $is_admin     = in_array( $wpi_role, array('administrator','super_manager') );
        $is_sys_owner = $this->is_system_owner();
        $user_org_id  = $this->get_org_id();
        if ( $is_sys_owner ) {
            $teams = $wpdb->get_results( "SELECT t.*, u.display_name as creator_name,
                o.name as org_name,
                (SELECT COUNT(*) FROM {$wpdb->prefix}wpi_team_members WHERE team_id=t.id) as member_count
                FROM {$wpdb->prefix}wpi_teams t
                LEFT JOIN {$wpdb->users} u ON u.ID=t.created_by
                LEFT JOIN {$wpdb->prefix}wpi_organisations o ON o.id=t.org_id
                ORDER BY t.created_at DESC" );
        } elseif ( $is_admin && $user_org_id ) {
            $teams = $wpdb->get_results( $wpdb->prepare(
                "SELECT t.*, u.display_name as creator_name,
                o.name as org_name,
                (SELECT COUNT(*) FROM {$wpdb->prefix}wpi_team_members WHERE team_id=t.id) as member_count
                FROM {$wpdb->prefix}wpi_teams t
                LEFT JOIN {$wpdb->users} u ON u.ID=t.created_by
                LEFT JOIN {$wpdb->prefix}wpi_organisations o ON o.id=t.org_id
                WHERE t.org_id=%d ORDER BY t.created_at DESC", $user_org_id ) );
        } else {
            $teams = $wpdb->get_results( $wpdb->prepare(
                "SELECT t.*, u.display_name as creator_name, tm.role as my_role,
                o.name as org_name,
                (SELECT COUNT(*) FROM {$wpdb->prefix}wpi_team_members WHERE team_id=t.id) as member_count
                FROM {$wpdb->prefix}wpi_teams t
                LEFT JOIN {$wpdb->users} u ON u.ID=t.created_by
                LEFT JOIN {$wpdb->prefix}wpi_organisations o ON o.id=t.org_id
                JOIN {$wpdb->prefix}wpi_team_members tm ON tm.team_id=t.id AND tm.user_id=%d
                ORDER BY t.created_at DESC", $uid ) );
        }
        foreach ( $teams as &$team ) {
            $team->members = $wpdb->get_results( $wpdb->prepare(
                "SELECT tm.*, COALESCE(NULLIF(TRIM(CONCAT(COALESCE(um_f.meta_value,''),' ',COALESCE(um_l.meta_value,''))), ''), u.display_name) as full_name, u.user_email
                FROM {$wpdb->prefix}wpi_team_members tm JOIN {$wpdb->users} u ON u.ID=tm.user_id
                LEFT JOIN {$wpdb->usermeta} um_f ON um_f.user_id=tm.user_id AND um_f.meta_key='first_name'
                LEFT JOIN {$wpdb->usermeta} um_l ON um_l.user_id=tm.user_id AND um_l.meta_key='last_name'
                WHERE tm.team_id=%d ORDER BY tm.role, u.display_name", $team->id ) );
        }
        $this->json( $teams );
    }

    public function wpi_create_team() {
        $this->check_nonce();
        $this->require_write_licence();
        global $wpdb;
        $body = $this->input();
        $name = sanitize_text_field( $body['name'] ?? '' );
        if ( ! $name ) $this->error( 'Team name required' );
        $wpdb->insert( $wpdb->prefix.'wpi_teams', array( 'name'=>$name, 'description'=>sanitize_textarea_field($body['description']??''), 'created_by'=>get_current_user_id(), 'org_id'=>$this->org_id_for_insert() ) );
        $team_id = $wpdb->insert_id;
        $wpdb->insert( $wpdb->prefix.'wpi_team_members', array( 'team_id'=>$team_id, 'user_id'=>get_current_user_id(), 'role'=>'owner' ) );
        $this->json( array( 'id'=>$team_id, 'name'=>$name ) );
    }

    public function wpi_update_team() {
        $this->check_nonce();
        global $wpdb;
        $body = $this->input();
        $id   = absint( $body['id'] ?? 0 );
        if ( !$id ) $this->error('id required');
        // Managers+ can update any team in their org; others must own the team.
        if ( ! $this->can('manager') ) {
            $uid      = get_current_user_id();
            $is_owner = $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}wpi_team_members
                 WHERE team_id=%d AND user_id=%d AND role IN ('owner','admin')",
                $id, $uid
            ) );
            if ( ! $is_owner ) $this->error('Permission denied — team owner or admin required', 403);
        }
        $fields = array();
        if ( isset($body['name']) )        $fields['name']        = sanitize_text_field($body['name']);
        if ( isset($body['description']) ) $fields['description'] = sanitize_textarea_field($body['description']);
        if ( !empty($fields) ) $wpdb->update( $wpdb->prefix.'wpi_teams', $fields, array('id'=>$id) );
        $this->json( array('success'=>true) );
    }

    public function wpi_delete_team() {
        $this->check_nonce();
        global $wpdb;
        $body = $this->input();
        $id   = absint( $body['id'] ?? 0 );
        if ( !$id ) $this->error('id required');
        // Managers+ can delete any team in their org; others must own the team.
        if ( ! $this->can('manager') ) {
            $uid      = get_current_user_id();
            $is_owner = $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}wpi_team_members
                 WHERE team_id=%d AND user_id=%d AND role='owner'",
                $id, $uid
            ) );
            if ( ! $is_owner ) $this->error('Permission denied — team owner required', 403);
        }
        $wpdb->delete( $wpdb->prefix.'wpi_teams',        array('id'=>$id) );
        $wpdb->delete( $wpdb->prefix.'wpi_team_members', array('team_id'=>$id) );
        $this->json( array('success'=>true) );
    }

    public function wpi_add_team_member() {
        $this->check_nonce();
        $this->require_write_licence();
        global $wpdb;
        $body    = $this->input();
        $team_id = absint($body['team_id']??0);
        $user_id = absint($body['user_id']??0);
        $role    = sanitize_text_field($body['role']??'member');
        if (!in_array($role, array('owner','admin','member','viewer'))) $role='member';
        if (!$team_id||!$user_id) $this->error('team_id and user_id required');
        // Verify user belongs to same org (prevents cross-org data leaks)
        if ( !$this->is_system_owner() ) {
            $my_org = $this->get_org_id();
            if ( $my_org ) {
                $in_org = $wpdb->get_var( $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}wpi_org_users WHERE org_id=%d AND user_id=%d",
                    $my_org, $user_id
                ) );
                if ( !$in_org ) $this->error('User does not belong to your organisation.', 403);
            }
        }
        $wpdb->replace( $wpdb->prefix.'wpi_team_members', array('team_id'=>$team_id,'user_id'=>$user_id,'role'=>$role) );
        $this->json( array('success'=>true) );
    }

    public function wpi_remove_team_member() {
        $this->check_nonce();
        global $wpdb;
        $body    = $this->input();
        $team_id = absint($body['team_id']??0);
        $user_id = absint($body['user_id']??0);
        if ( !$team_id || !$user_id ) $this->error('team_id and user_id required');
        // Users can remove themselves; managers+ can remove anyone; team owners/admins can remove members.
        $uid = get_current_user_id();
        if ( (int)$user_id !== (int)$uid && ! $this->can('manager') ) {
            $is_team_admin = $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}wpi_team_members
                 WHERE team_id=%d AND user_id=%d AND role IN ('owner','admin')",
                $team_id, $uid
            ) );
            if ( ! $is_team_admin ) $this->error('Permission denied — team owner or admin required', 403);
        }
        $wpdb->delete( $wpdb->prefix.'wpi_team_members', array('team_id'=>$team_id,'user_id'=>$user_id) );
        $this->json( array('success'=>true) );
    }

    public function wpi_get_wp_users() {
        $this->check_nonce();
        global $wpdb;
        $is_sys_owner = $this->is_system_owner();
        $user_org_id  = $this->get_org_id();

        if ( $is_sys_owner ) {
            // System owner sees all WP users
            $users = get_users( array('number'=>500,'orderby'=>'display_name') );
        } elseif ( $user_org_id ) {
            // Org users only — scoped strictly to their org
            $org_user_ids = $wpdb->get_col( $wpdb->prepare(
                "SELECT user_id FROM {$wpdb->prefix}wpi_org_users WHERE org_id=%d", $user_org_id
            ) );
            if ( empty($org_user_ids) ) { $this->json( array() ); return; }
            $users = get_users( array('include'=>$org_user_ids,'orderby'=>'display_name') );
        } else {
            // No org assigned — return only the current user
            $users = get_users( array('include'=>array(get_current_user_id())) );
        }

        // Build org lookup for all returned users
        $user_ids = array_map(function($u){ return $u->ID; }, $users);
        $org_map  = array();
        if ( !empty($user_ids) ) {
            $ids_in   = implode(',', array_map('intval', $user_ids));
            $org_rows = $wpdb->get_results(
                "SELECT ou.user_id, o.name AS org_name, o.id AS org_id
                 FROM {$wpdb->prefix}wpi_org_users ou
                 JOIN {$wpdb->prefix}wpi_organisations o ON o.id=ou.org_id
                 WHERE ou.user_id IN ($ids_in)"
            );
            foreach ( $org_rows as $r ) $org_map[$r->user_id] = array('name'=>$r->org_name,'id'=>$r->org_id);
        }

        // Get last_seen from wpi_user_roles for all returned users
        $last_seen_map = array();
        if ( !empty($user_ids) ) {
            $ids_in = implode(',', array_map('intval', $user_ids));
            $ls_rows = $wpdb->get_results(
                "SELECT user_id, last_seen, role FROM {$wpdb->prefix}wpi_user_roles WHERE user_id IN ($ids_in)"
            );
            foreach ( $ls_rows as $r ) {
                $last_seen_map[$r->user_id] = array('last_seen'=>$r->last_seen,'role'=>$r->role);
            }
        }

        $out = array();
        foreach ($users as $u) {
            $fn  = get_user_meta($u->ID,'first_name',true);
            $ln  = get_user_meta($u->ID,'last_name',true);
            $org = $org_map[$u->ID] ?? null;
            $ls  = $last_seen_map[$u->ID] ?? null;
            $out[] = array(
                'id'          => $u->ID,
                'name'        => trim("$fn $ln") ?: $u->display_name,
                'email'       => $u->user_email,
                'login'       => $u->user_login,
                'org_name'    => $org ? $org['name'] : '',
                'org_id'      => $org ? $org['id']   : null,
                'last_seen'   => $ls  ? $ls['last_seen'] : null,
                'wpi_role'    => $ls  ? $ls['role']      : 'standard',
                'deactivated' => (bool) get_user_meta( $u->ID, 'wpi_deactivated', true ),
            );
        }
        $this->json($out);
    }

    /* ── Template Sharing ──────────────────────────────────── */

    public function wpi_get_template_shares() {
        $this->check_nonce();
        global $wpdb;
        $tid = absint($_GET['template_id']??0);
        if (!$tid) $this->error('template_id required');
        $shares = $wpdb->get_results( $wpdb->prepare(
            "SELECT s.*,
                CASE WHEN s.shared_with_type='user'
                    THEN COALESCE( NULLIF( TRIM( CONCAT( COALESCE(um_f.meta_value,''), ' ', COALESCE(um_l.meta_value,'') ) ), '' ), u.display_name )
                    ELSE t.name
                END as shared_with_name,
                CASE WHEN s.shared_with_type='user' THEN u.user_email ELSE NULL END as shared_with_email
             FROM {$wpdb->prefix}wpi_template_shares s
             LEFT JOIN {$wpdb->users} u ON s.shared_with_type='user' AND u.ID=s.shared_with_id
             LEFT JOIN {$wpdb->usermeta} um_f ON s.shared_with_type='user' AND um_f.user_id=s.shared_with_id AND um_f.meta_key='first_name'
             LEFT JOIN {$wpdb->usermeta} um_l ON s.shared_with_type='user' AND um_l.user_id=s.shared_with_id AND um_l.meta_key='last_name'
             LEFT JOIN {$wpdb->prefix}wpi_teams t ON s.shared_with_type='team' AND t.id=s.shared_with_id
             WHERE s.template_id=%d ORDER BY s.shared_at DESC", $tid ) );
        $this->json($shares);
    }

    public function wpi_add_template_share() {
        $this->check_nonce();
        global $wpdb;
        $body   = $this->input();
        $tid    = absint($body['template_id']??0);
        $type   = sanitize_text_field($body['shared_with_type']??'');
        $wid    = absint($body['shared_with_id']??0);
        $access = sanitize_text_field($body['access']??'view');
        $insp_vis = sanitize_text_field($body['inspection_visibility']??'all');
        if (!in_array($access,array('edit','conduct','view'))) $access='view';
        if (!in_array($insp_vis,array('all','from_share_date','own_only'))) $insp_vis='all';
        if (!in_array($type,array('user','team'))) $this->error('Invalid type');
        if (!$tid||!$wid) $this->error('template_id and shared_with_id required');

        $wpdb->replace( $wpdb->prefix.'wpi_template_shares', array(
            'template_id'            => $tid,
            'shared_with_type'       => $type,
            'shared_with_id'         => $wid,
            'access'                 => $access,
            'inspection_visibility'  => $insp_vis,
            'shared_at'              => current_time('mysql'),
            'shared_by'              => get_current_user_id()
        ) );

        // ── Send share notification email ──────────────────────
        $this->send_share_notification( $tid, $type, $wid, $access );

        $this->json( array('success'=>true) );
    }

    /**
     * Send a professional HTML email notifying a user (or team members)
     * that a template has been shared with them.
     */
    private function send_share_notification( $template_id, $type, $shared_with_id, $access ) {
        global $wpdb;

        // Get template info
        $tmpl = $wpdb->get_row( $wpdb->prepare(
            "SELECT t.title, t.settings, u.display_name as owner_name
             FROM {$wpdb->prefix}wpi_templates t
             LEFT JOIN {$wpdb->users} u ON u.ID = t.created_by
             WHERE t.id = %d", $template_id
        ) );
        if ( ! $tmpl ) return;

        $cfg        = is_string($tmpl->settings) ? (array)json_decode($tmpl->settings,true) : array();
        $logo_url   = $cfg['logo_url']        ?? '';
        $header_col = $cfg['header_color']    ?? '#1a3a5c';
        $header_txt = $cfg['header_text_color'] ?? '#ffffff';
        $sharer     = $tmpl->owner_name ?: get_bloginfo('name');

        // Get current user (sharer) display name
        $current_user = wp_get_current_user();
        $sharer_name  = trim( $current_user->first_name . ' ' . $current_user->last_name ) ?: $current_user->display_name;

        // Resolve access label
        $access_labels = array(
            'edit'    => 'Edit — can modify the template and conduct audits',
            'conduct' => 'Conduct — can run audits using this template',
            'view'    => 'View — can view audits and reports',
        );
        $access_label = $access_labels[$access] ?? $access;

        // Collect recipient emails
        $recipients = array();
        if ( $type === 'user' ) {
            $user = get_userdata( $shared_with_id );
            if ( $user && $user->user_email ) {
                $recipients[] = array(
                    'email' => $user->user_email,
                    'name'  => trim($user->first_name.' '.$user->last_name) ?: $user->display_name,
                );
            }
        } elseif ( $type === 'team' ) {
            $team_users = $wpdb->get_results( $wpdb->prepare(
                "SELECT u.user_email, u.display_name,
                        um_fn.meta_value as first_name, um_ln.meta_value as last_name
                 FROM {$wpdb->prefix}wpi_team_members tm
                 JOIN {$wpdb->users} u ON u.ID = tm.user_id
                 LEFT JOIN {$wpdb->usermeta} um_fn ON um_fn.user_id = tm.user_id AND um_fn.meta_key = 'first_name'
                 LEFT JOIN {$wpdb->usermeta} um_ln ON um_ln.user_id = tm.user_id AND um_ln.meta_key = 'last_name'
                 WHERE tm.team_id = %d", $shared_with_id
            ) );
            foreach ( $team_users as $tu ) {
                $name = trim(($tu->first_name??'').' '.($tu->last_name??'')) ?: $tu->display_name;
                $recipients[] = array('email'=>$tu->user_email,'name'=>$name);
            }
        }

        if ( empty($recipients) ) return;

        $site_url    = admin_url('admin.php?page=wp-inspector');
        $site_name   = get_bloginfo('name') ?: get_option('blogname') ?: 'Audit4me';
        $today       = (new DateTime('now', wp_timezone()))->format('d M Y');

        foreach ( $recipients as $rec ) {
            $first_name = explode(' ', $rec['name'])[0] ?: 'there';

            $body_text = "Hi {$first_name},\n\n"
                . "{$sharer_name} has shared a template with you on {$site_name}.\n\n"
                . "Template: {$tmpl->title}\n"
                . "Access level: {$access_label}\n\n"
                . "You can now access this template by logging into Audit4me. "
                . "Click the button below to get started.\n\n"
                . "If you have any questions, please contact your administrator.";

            // Build HTML using scheduler email style
            $body_html = implode('', array_map(function($line){
                $line = trim($line);
                if ($line === '') return '<br>';
                return '<p style="margin:0 0 12px 0;color:#374151;font-size:14px;line-height:1.6;">'.esc_html($line).'</p>';
            }, explode("\n", $body_text)));

            $logo_html = $logo_url
                ? '<img src="'.esc_url($logo_url).'" alt="Logo" style="max-height:48px;max-width:160px;object-fit:contain;display:block;margin-bottom:12px;">'
                : '';

            $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Helvetica,Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f6;padding:32px 16px;">
<tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 2px 16px rgba(0,0,0,.08);">

  <!-- Header -->
  <tr><td style="background:'.esc_attr($header_col).';padding:28px 32px;">
    '.$logo_html.'
    <h1 style="margin:0;font-size:22px;font-weight:800;color:'.esc_attr($header_txt).';line-height:1.2;">Template Shared With You</h1>
    <p style="margin:8px 0 0;font-size:13px;color:'.esc_attr($header_txt).';opacity:.8;">'.esc_html($site_name).' · '.esc_html($today).'</p>
  </td></tr>

  <!-- Status badge -->
  <tr><td style="padding:20px 32px 0;">
    <span style="display:inline-block;background:#3b82f622;color:#3b82f6;border:1.5px solid #3b82f6;border-radius:20px;padding:4px 14px;font-size:12px;font-weight:700;">📋 TEMPLATE SHARED</span>
  </td></tr>

  <!-- Template info card -->
  <tr><td style="padding:20px 32px 0;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f8faff;border:1.5px solid #e0e7ff;border-radius:10px;padding:0;">
      <tr><td style="padding:16px 20px;">
        <p style="margin:0 0 4px;font-size:11px;font-weight:700;color:#6366f1;text-transform:uppercase;letter-spacing:.5px;">Template</p>
        <p style="margin:0 0 12px;font-size:17px;font-weight:700;color:#111827;">'.esc_html($tmpl->title).'</p>
        <p style="margin:0 0 4px;font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.5px;">Shared by</p>
        <p style="margin:0 0 12px;font-size:14px;color:#374151;">'.esc_html($sharer_name).'</p>
        <p style="margin:0 0 4px;font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.5px;">Access Level</p>
        <p style="margin:0;font-size:14px;color:#374151;">
          <span style="display:inline-block;background:'.($access==='edit'?'#fef3c7':($access==='conduct'?'#d1fae5':'#ede9fe')).';color:'.($access==='edit'?'#92400e':($access==='conduct'?'#065f46':'#4c1d95')).';border-radius:6px;padding:2px 10px;font-size:12px;font-weight:700;">'.strtoupper($access).'</span>
          <span style="color:#6b7280;font-size:13px;margin-left:8px;">'.esc_html($access_labels[$access]??$access).'</span>
        </p>
      </td></tr>
    </table>
  </td></tr>

  <!-- Body -->
  <tr><td style="padding:20px 32px 8px;">
    '.$body_html.'
  </td></tr>

  <!-- CTA Button -->
  <tr><td style="padding:8px 32px 32px;text-align:center;">
    <a href="'.esc_url($site_url).'" style="display:inline-block;background:'.esc_attr($header_col).';color:#ffffff;text-decoration:none;padding:13px 32px;border-radius:8px;font-size:15px;font-weight:700;">Open Audit4me →</a>
  </td></tr>

  <!-- Footer -->
  <tr><td style="background:#f9fafb;padding:16px 32px;border-top:1px solid #e5e7eb;text-align:center;">
    <p style="margin:0;font-size:11px;color:#9ca3af;">This is an automated notification from <a href="'.esc_url(home_url()).'" style="color:#6b7280;">'.esc_html($site_name).'</a>. Do not reply to this email.</p>
  </td></tr>

</table>
</td></tr></table>
</body></html>';

            $subject = '📋 Template Shared: ' . $tmpl->title . ' — ' . $site_name;
            $headers = array(
                'Content-Type: text/html; charset=UTF-8',
                'From: ' . $site_name . ' <' . get_option('admin_email') . '>',
            );
            wp_mail( $rec['email'], $subject, $html, $headers );
        }
    }

    public function wpi_remove_template_share() {
        $this->check_nonce();
        if ( ! $this->can('manager') ) $this->error( 'Access denied', 403 );
        global $wpdb;
        $body = $this->input();
        $id   = absint( $body['id'] ?? 0 );
        if ( ! $id ) $this->error( 'id required' );

        // Verify the share record belongs to a template in the caller's org (IDOR fix)
        if ( ! $this->is_system_owner() ) {
            $share = $wpdb->get_row( $wpdb->prepare(
                "SELECT t.org_id, t.created_by FROM {$wpdb->prefix}wpi_template_shares s
                 JOIN {$wpdb->prefix}wpi_templates t ON t.id = s.template_id
                 WHERE s.id = %d", $id
            ) );
            if ( ! $share ) $this->error( 'Share not found', 404 );
            $my_org = $this->get_org_id();
            $uid    = get_current_user_id();
            $is_tmpl_owner = ( (int) $share->created_by === (int) $uid );
            $is_org_match  = ( $my_org && (int) $share->org_id === (int) $my_org );
            if ( ! $is_tmpl_owner && ! $is_org_match ) {
                $this->error( 'Access denied', 403 );
            }
        }

        $wpdb->delete( $wpdb->prefix.'wpi_template_shares', array('id' => $id) );
        $this->json( array('success'=>true) );
    }

    public function wpi_remove_my_template_share() {
        // Legacy — redirect to user_archive
        $this->wpi_user_archive_template();
    }

    // Archive a shared template from current user's view only (stored in usermeta)
    public function wpi_user_archive_template() {
        $this->check_nonce();
        $uid         = get_current_user_id();
        $input       = $this->input();
        $template_id = absint( $input['template_id'] ?? $input['id'] ?? 0 );
        if ( !$template_id ) $this->error('template_id required');
        $hidden = get_user_meta( $uid, 'wpi_archived_templates', true );
        if ( !is_array($hidden) ) $hidden = array();
        $hidden[] = $template_id;
        $hidden   = array_unique( array_map('intval', $hidden) );
        update_user_meta( $uid, 'wpi_archived_templates', $hidden );
        $this->json( array('success'=>true) );
    }

    // Restore a user-archived template back to active view
    public function wpi_user_restore_template() {
        $this->check_nonce();
        $uid         = get_current_user_id();
        $input       = $this->input();
        $template_id = absint( $input['template_id'] ?? $input['id'] ?? 0 );
        if ( !$template_id ) $this->error('template_id required');
        $hidden = get_user_meta( $uid, 'wpi_archived_templates', true );
        if ( !is_array($hidden) ) $hidden = array();
        $hidden = array_values( array_filter( $hidden, function($id) use($template_id){ return (int)$id !== $template_id; } ) );
        update_user_meta( $uid, 'wpi_archived_templates', $hidden );
        $this->json( array('success'=>true) );
    }

    public function wpi_update_share_access() {
        $this->check_nonce();
        if ( ! $this->can('manager') ) $this->error( 'Access denied', 403 );
        global $wpdb;
        $body   = $this->input();
        $id     = absint($body['id']??0);
        $access = sanitize_text_field($body['access']??'view');
        if (!in_array($access,array('edit','conduct','view'),true)) $this->error('Invalid access');
        // Verify the share record belongs to a template this user can manage
        if ( ! $this->is_system_owner() ) {
            $share = $wpdb->get_row( $wpdb->prepare(
                "SELECT t.created_by FROM {$wpdb->prefix}wpi_template_shares s
                 JOIN {$wpdb->prefix}wpi_templates t ON t.id=s.template_id
                 WHERE s.id=%d", $id
            ) );
            if ( ! $share ) $this->error( 'Share not found', 404 );
            if ( (int)$share->created_by !== (int)get_current_user_id() && ! $this->can('super_manager') ) {
                $this->error( 'Access denied', 403 );
            }
        }
        $wpdb->update( $wpdb->prefix.'wpi_template_shares', array('access'=>$access), array('id'=>$id) );
        $this->json( array('success'=>true) );
    }

    public function wpi_update_share_visibility() {
        $this->check_nonce();
        if ( ! $this->can('manager') ) $this->error( 'Access denied', 403 );
        global $wpdb;
        $body = $this->input();
        $id   = absint($body['id']??0);
        $vis  = sanitize_text_field($body['inspection_visibility']??'all');
        if (!in_array($vis,array('all','from_share_date','own_only'),true)) $this->error('Invalid visibility');
        if ( ! $id ) $this->error('id required');
        // Self-heal schema once per installation, not on every request
        if ( ! get_option('wpi_shares_cols_v1') ) {
            $wpdb->hide_errors();
            $wpdb->query("ALTER TABLE `{$wpdb->prefix}wpi_template_shares` ADD COLUMN `inspection_visibility` VARCHAR(20) NOT NULL DEFAULT 'all'");
            $wpdb->query("ALTER TABLE `{$wpdb->prefix}wpi_template_shares` ADD COLUMN `shared_at` DATETIME DEFAULT NULL");
            $wpdb->show_errors();
            update_option('wpi_shares_cols_v1', '1', false);
        }
        $update = array('inspection_visibility' => $vis);
        // Set shared_at if not already set (for rows created before this column existed)
        $existing = $wpdb->get_row( $wpdb->prepare(
            "SELECT shared_at FROM {$wpdb->prefix}wpi_template_shares WHERE id=%d", $id
        ) );
        if ( $existing && empty($existing->shared_at) ) {
            $update['shared_at'] = current_time('mysql');
        }
        $wpdb->update( $wpdb->prefix.'wpi_template_shares', $update, array('id'=>$id) );
        $this->json( array('success'=>true) );
    }

    /* ── User Roles ──────────────────────────────────────────── */

    /* ── API Connection Settings ─────────────────────────────── */

    /**
     * Keys that only the system owner can read/write.
     * Stored globally in wpi_api_settings (not per-org).
     */
    private static $system_only_keys = array('gemini_api_key','anthropic_api_key');

    /**
     * Keys that belong to an org.
     * Stored per-org in wpi_api_settings_org_{org_id}.
     * System owner uses the global wpi_api_settings for these too.
     */
    private static $org_keys = array('webhook_url','webhook_secret','zapier_webhook',
                                     'slack_webhook','email_notify','custom_api_url','custom_api_key');

    public function wpi_get_api_settings() {
        $this->check_nonce();
        if ( ! $this->can('administrator') ) $this->error('Access denied', 403);

        $is_owner = $this->is_system_owner();
        $global   = get_option( 'wpi_api_settings', array() );

        if ( $is_owner ) {
            // System owner gets everything from the global option
            $this->json( $global );
            return;
        }

        // Org admin: read org-scoped settings only — never expose AI keys
        $org_id   = (int) $this->get_org_id();
        $org_data = $org_id ? get_option( 'wpi_api_settings_org_' . $org_id, array() ) : array();
        $out = array();
        foreach ( self::$org_keys as $k ) {
            $out[$k] = $org_data[$k] ?? '';
        }
        $this->json( $out );
    }

    public function wpi_save_api_settings() {
        $this->check_nonce();
        if ( ! $this->can('administrator') ) $this->error('Access denied', 403);

        $body     = $this->input();
        $raw      = $body['settings'] ?? array();
        $is_owner = $this->is_system_owner();

        if ( $is_owner ) {
            // System owner saves everything to global option
            $all_keys = array_merge( self::$org_keys, self::$system_only_keys );
            $clean    = array();
            foreach ( $all_keys as $k ) {
                if ( array_key_exists($k, $raw) ) {
                    $clean[$k] = sanitize_text_field( $raw[$k] );
                }
            }
            $existing = get_option( 'wpi_api_settings', array() );
            update_option( 'wpi_api_settings', array_merge( $existing, $clean ), false );
        } else {
            // Org admin: only save org-scoped keys — AI keys are blocked
            $org_id = (int) $this->get_org_id();
            if ( ! $org_id ) $this->error('No organisation assigned', 403);
            $clean = array();
            foreach ( self::$org_keys as $k ) {
                if ( array_key_exists($k, $raw) ) {
                    $clean[$k] = sanitize_text_field( $raw[$k] );
                }
            }
            $existing = get_option( 'wpi_api_settings_org_' . $org_id, array() );
            update_option( 'wpi_api_settings_org_' . $org_id, array_merge( $existing, $clean ), false );
        }

        $this->json( array('success' => true) );
    }

    public function wpi_test_webhook() {
        $this->check_nonce();
        if ( ! $this->can('administrator') ) $this->error('Access denied', 403);
        // Load org-scoped settings (or global for system owner)
        if ( $this->is_system_owner() ) {
            $settings = get_option( 'wpi_api_settings', array() );
        } else {
            $org_id   = (int) $this->get_org_id();
            $settings = $org_id ? get_option( 'wpi_api_settings_org_' . $org_id, array() ) : array();
        }
        $url = $settings['webhook_url'] ?? '';
        if ( ! $url ) $this->error('No webhook URL configured');
        $payload = array(
            'event'       => 'test',
            'source'      => 'Audit4Me WP Inspector',
            'timestamp'   => current_time('c'),
            'message'     => 'This is a test webhook from Audit4Me',
            'site_url'    => get_site_url(),
        );
        $args = array(
            'method'  => 'POST',
            'headers' => array('Content-Type' => 'application/json'),
            'body'    => json_encode($payload),
            'timeout' => 10,
        );
        if ( ! empty($settings['webhook_secret']) ) {
            $sig = hash_hmac('sha256', json_encode($payload), $settings['webhook_secret']);
            $args['headers']['X-WPI-Signature'] = 'sha256='.$sig;
        }
        $resp = wp_remote_post( $url, $args );
        if ( is_wp_error($resp) ) {
            $this->error( 'Webhook failed: '.$resp->get_error_message() );
        }
        $code = wp_remote_retrieve_response_code($resp);
        $this->json( array('success' => true, 'status' => $code) );
    }

    /**
     * Import template from PDF/Excel via Claude API (server-side — API key never exposed to browser)
     */
    public function wpi_import_template() {
        $this->check_nonce();

        $settings      = get_option( 'wpi_api_settings', array() );
        $gemini_key    = trim( $settings['gemini_api_key']    ?? '' );
        $anthropic_key = trim( $settings['anthropic_api_key'] ?? '' );

        if ( ! $gemini_key && ! $anthropic_key ) {
            $this->error( 'No AI API key configured. Go to Settings → API Settings and add a Google Gemini API key (free).' );
        }

        $body      = $this->input();
        $file_b64  = $body['file_data']  ?? '';
        $file_name = sanitize_text_field( $body['file_name'] ?? '' );
        $is_pdf    = ! empty( $body['is_pdf'] );

        if ( ! $file_b64 ) $this->error('No file data received');

        // Limit base64 payload to ~10 MB decoded (~13.5 MB base64 encoded)
        if ( strlen( $file_b64 ) > 14000000 ) {
            $this->error( 'File too large. Maximum 10 MB for AI import.' );
        }

        // Strip data URI prefix if present
        if ( strpos($file_b64, ',') !== false ) {
            $file_b64 = substr($file_b64, strpos($file_b64, ',') + 1);
        }

        $prompt = 'Extract all questions, fields and sections from this inspection/audit form. Return ONLY valid JSON with no markdown fences or extra text:
{
  "title": "form title",
  "description": "brief description",
  "sections": [
    {
      "name": "Section Name",
      "questions": [
        {"label":"Question text","type":"yes_no","required":false},
        {"label":"Open text field","type":"textarea","required":false},
        {"label":"Short answer","type":"text","required":false},
        {"label":"Choice field","type":"multiple_choice","required":false,"options":["Option 1","Option 2"]},
        {"label":"Signature","type":"signature","required":false}
      ]
    }
  ]
}
Valid types: yes_no, text, textarea, number, multiple_choice, checkbox, date_time, photo, signature, instruction';

        $text       = '';
        $debug_info = array();

        // ── Gemini ──────────────────────────────────────────────────
        if ( $gemini_key ) {
            // Build parts — PDF inline or text
            if ( $is_pdf ) {
                $parts = array(
                    array('inline_data' => array('mime_type' => 'application/pdf', 'data' => $file_b64)),
                    array('text' => $prompt),
                );
            } else {
                $decoded = base64_decode( $file_b64 );
                $parts = array(
                    array('text' => 'File: '.$file_name."\n\nContent:\n".mb_substr($decoded, 0, 50000)),
                    array('text' => $prompt),
                );
            }

            $gemini_payload = json_encode(array(
                'contents'         => array(array('parts' => $parts)),
                'generationConfig' => array(
                    'maxOutputTokens' => 8192,
                    'temperature'     => 0.1,
                    'responseMimeType' => 'application/json',
                ),
            ));

            $response = wp_remote_post(
                'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key='.$gemini_key,
                array(
                    'timeout'    => 90,
                    'sslverify'  => true,
                    'headers'    => array('Content-Type' => 'application/json'),
                    'body'       => $gemini_payload,
                )
            );

            if ( is_wp_error($response) ) {
                $debug_info['gemini_wp_error'] = $response->get_error_message();
            } else {
                $g_code = wp_remote_retrieve_response_code($response);
                $g_body = wp_remote_retrieve_body($response);
                $g_data = json_decode($g_body, true);
                $debug_info['gemini_http']   = $g_code;
                $debug_info['gemini_finish'] = $g_data['candidates'][0]['finishReason'] ?? null;

                if ( $g_code === 200 && ! empty($g_data['candidates'][0]['content']['parts'][0]['text']) ) {
                    $text = $g_data['candidates'][0]['content']['parts'][0]['text'];
                } else {
                    $debug_info['gemini_error']    = $g_data['error']['message'] ?? null;
                    $debug_info['gemini_response'] = mb_substr($g_body, 0, 500);
                    if ( ! $anthropic_key ) {
                        $err = $g_data['error']['message'] ?? ('Gemini HTTP '.$g_code.': '.mb_substr($g_body,0,200));
                        $this->error( $err );
                    }
                }
            }
        }

        // ── Anthropic fallback ───────────────────────────────────────
        if ( ! $text && $anthropic_key ) {
            if ( $is_pdf ) {
                $messages = array(array('role'=>'user','content'=>array(
                    array('type'=>'document','source'=>array('type'=>'base64','media_type'=>'application/pdf','data'=>$file_b64)),
                    array('type'=>'text','text'=>$prompt)
                )));
            } else {
                $messages = array(array('role'=>'user','content'=>array(
                    array('type'=>'text','text'=>'File: '.$file_name."\n\n".$prompt."\n\nContent (base64):\n".$file_b64)
                )));
            }
            $a_response = wp_remote_post('https://api.anthropic.com/v1/messages', array(
                'timeout'   => 90,
                'sslverify' => true,
                'headers'   => array(
                    'Content-Type'      => 'application/json',
                    'x-api-key'         => $anthropic_key,
                    'anthropic-version' => '2023-06-01',
                ),
                'body' => json_encode(array(
                    'model'      => 'claude-haiku-4-5-20251001',
                    'max_tokens' => 4000,
                    'messages'   => $messages,
                )),
            ));
            if ( ! is_wp_error($a_response) ) {
                $a_code = wp_remote_retrieve_response_code($a_response);
                $a_data = json_decode(wp_remote_retrieve_body($a_response), true);
                if ( $a_code === 200 && ! empty($a_data['content']) ) {
                    foreach ( $a_data['content'] as $block ) {
                        if ( $block['type'] === 'text' ) $text .= $block['text'];
                    }
                } else {
                    $debug_info['anthropic_error'] = $a_data['error']['message'] ?? ('HTTP '.$a_code);
                    $this->error( 'Anthropic: '.($a_data['error']['message'] ?? 'HTTP '.$a_code) );
                }
            } else {
                $this->error( 'Anthropic request failed: '.$a_response->get_error_message() );
            }
        }

        if ( ! $text ) {
            $this->error( 'AI returned no content. Debug: '.json_encode($debug_info) );
        }

        // Debug info stripped from production response to prevent API key/response leakage
        $this->json( array('success' => true, 'text' => $text) );
    }

    public function wpi_get_user_detail() {
        $this->check_nonce();
        global $wpdb;
        $uid = absint( $_GET['user_id'] ?? 0 );
        if ( !$uid ) $this->error('user_id required');

        // System owner is invisible to all app-layer users
        $system_owner_id = (int) get_option( 'wpi_system_owner_id', 0 );
        if ( $system_owner_id && (int) $uid === $system_owner_id && ! $this->is_system_owner() ) {
            $this->error( 'User not found', 404 );
        }

        // Non-system-owners can only view users in their own org (or themselves)
        if ( ! $this->is_system_owner() ) {
            $my_org = $this->get_org_id();
            if ( (int)$uid !== (int)get_current_user_id() ) {
                if ( ! $my_org ) $this->error('Access denied', 403);
                $in_org = $wpdb->get_var( $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}wpi_org_users WHERE org_id=%d AND user_id=%d",
                    $my_org, $uid
                ) );
                if ( ! $in_org ) $this->error('Access denied', 403);
            }
        }

        $u  = get_userdata($uid);
        if ( !$u ) $this->error('User not found', 404);

        $first = get_user_meta($uid,'first_name',true);
        $last  = get_user_meta($uid,'last_name',true);
        $full  = trim("$first $last") ?: $u->display_name;

        // WPI role
        $wr = $wpdb->get_var($wpdb->prepare("SELECT role FROM {$wpdb->prefix}wpi_user_roles WHERE user_id=%d", $uid));
        $wpi_role = $wr ?: 'guest';

        // Organisation(s)
        $orgs = $wpdb->get_results($wpdb->prepare(
            "SELECT o.id, o.name, ou.role AS org_role
             FROM {$wpdb->prefix}wpi_org_users ou
             JOIN {$wpdb->prefix}wpi_organisations o ON o.id=ou.org_id
             WHERE ou.user_id=%d", $uid
        ));

        // Teams
        $teams = $wpdb->get_results($wpdb->prepare(
            "SELECT t.id, t.name, tm.role AS team_role
             FROM {$wpdb->prefix}wpi_team_members tm
             JOIN {$wpdb->prefix}wpi_teams t ON t.id=tm.team_id
             WHERE tm.user_id=%d ORDER BY t.name", $uid
        ));

        // Sites
        $sites = $wpdb->get_results($wpdb->prepare(
            "SELECT s.id, s.name, su.role AS site_role
             FROM {$wpdb->prefix}wpi_site_users su
             JOIN {$wpdb->prefix}wpi_sites s ON s.id=su.site_id
             WHERE su.user_id=%d ORDER BY s.name", $uid
        ));

        // Activity stats
        $total_insp = (int)$wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}wpi_inspections WHERE conducted_by=%d", $uid));
        $completed  = (int)$wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}wpi_inspections WHERE conducted_by=%d AND status='completed'", $uid));
        $last_insp  = $wpdb->get_var($wpdb->prepare(
            "SELECT conducted_at FROM {$wpdb->prefix}wpi_inspections WHERE conducted_by=%d ORDER BY conducted_at DESC LIMIT 1", $uid));

        $this->json(array(
            'id'            => $uid,
            'first_name'    => $first,
            'last_name'     => $last,
            'display_name'  => $full,
            'username'      => $u->user_login,
            'email'         => $u->user_email,
            'registered'    => $u->user_registered,
            'wpi_role'      => $wpi_role,
            'is_wp_admin'   => user_can($uid,'manage_options'),
            'organisations' => $orgs,
            'teams'         => $teams,
            'sites'         => $sites,
            'total_inspections' => $total_insp,
            'completed_inspections' => $completed,
            'last_inspection' => $last_insp,
        ));
    }

    /**
     * Update a user's profile fields and WPI role.
     * System owner can edit anyone.
     * Org admin / super_manager can edit users within their own org.
     */
    public function wpi_update_user() {
        $this->check_nonce();
        global $wpdb;

        $body    = $this->input();
        $uid     = absint( $body['user_id'] ?? 0 );
        if ( ! $uid ) $this->error('user_id required');

        // System owner account cannot be edited by anyone else
        $system_owner_id = (int) get_option( 'wpi_system_owner_id', 0 );
        if ( $system_owner_id && (int) $uid === $system_owner_id && ! $this->is_system_owner() ) {
            $this->error( 'Access denied', 403 );
        }

        // Permission check
        if ( ! $this->is_system_owner() ) {
            if ( ! $this->can('super_manager') ) $this->error('Access denied', 403);
            // Must be in same org
            $my_org = $this->get_org_id();
            if ( $my_org && (int)$uid !== (int)get_current_user_id() ) {
                $in_org = $wpdb->get_var( $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}wpi_org_users WHERE org_id=%d AND user_id=%d",
                    $my_org, $uid
                ) );
                if ( ! $in_org ) $this->error('Access denied', 403);
            }
        }

        $u = get_userdata( $uid );
        if ( ! $u ) $this->error('User not found', 404);

        // Update name fields
        if ( isset($body['first_name']) ) update_user_meta( $uid, 'first_name', sanitize_text_field($body['first_name']) );
        if ( isset($body['last_name'])  ) update_user_meta( $uid, 'last_name',  sanitize_text_field($body['last_name']) );

        // Update display name
        if ( isset($body['first_name']) || isset($body['last_name']) ) {
            $fn   = get_user_meta($uid,'first_name',true);
            $ln   = get_user_meta($uid,'last_name',true);
            $full = trim("$fn $ln") ?: $u->display_name;
            wp_update_user( array('ID'=>$uid, 'display_name'=>$full) );
        }

        // Update email — system owner or editing self only
        if ( isset($body['email']) ) {
            $new_email = sanitize_email($body['email']);
            if ( $new_email && $new_email !== $u->user_email ) {
                if ( (int)$uid === (int)get_current_user_id() || $this->is_system_owner() ) {
                    if ( email_exists($new_email) && email_exists($new_email) !== $uid ) {
                        $this->error('Email already in use');
                    }
                    wp_update_user( array('ID'=>$uid, 'user_email'=>$new_email) );
                }
            }
        }

        // Update password — system owner or editing self only
        if ( ! empty($body['password']) ) {
            $new_pw = $body['password'];
            if ( strlen($new_pw) < 8 ) $this->error('Password must be at least 8 characters');
            if ( ! preg_match('/[A-Z]/', $new_pw) || ! preg_match('/[0-9]/', $new_pw) || ! preg_match('/[^a-zA-Z0-9]/', $new_pw) ) {
                $this->error('Password must contain at least one uppercase letter, one number, and one special character');
            }
            if ( (int)$uid === (int)get_current_user_id() || $this->is_system_owner() ) {
                wp_set_password( $new_pw, $uid );
            }
        }

        // Update WPI role — system owner can set any role; org admin/super_manager can set standard and below
        if ( isset($body['wpi_role']) ) {
            $new_role = sanitize_text_field($body['wpi_role']);
            $allowed  = array('administrator','super_manager','manager','standard','basic','guest');
            if ( in_array($new_role, $allowed) ) {
                // Org admins cannot promote to administrator or super_manager
                if ( ! $this->is_system_owner() && in_array($new_role, array('administrator','super_manager')) ) {
                    $this->error('You cannot assign this role', 403);
                }
                $wpdb->replace( $wpdb->prefix.'wpi_user_roles', array(
                    'user_id' => $uid,
                    'role'    => $new_role,
                    'set_by'  => get_current_user_id(),
                ) );
            }
        }

        $this->json( array('success'=>true) );
    }

    public function wpi_set_org_user_role() {
        $this->check_nonce();
        if ( ! $this->is_system_owner() ) $this->error('Access denied', 403);
        global $wpdb;
        $body    = $this->input();
        $org_id  = absint($body['org_id']  ?? 0);
        $user_id = absint($body['user_id'] ?? 0);
        $role    = sanitize_text_field($body['role'] ?? 'member');
        $wpdb->update( $wpdb->prefix.'wpi_org_users', array('role'=>$role), array('org_id'=>$org_id,'user_id'=>$user_id) );
        $wpi_role = $role === 'admin' ? 'administrator' : ($role === 'manager' ? 'manager' : 'standard');
        $wpdb->replace( $wpdb->prefix.'wpi_user_roles', array('user_id'=>$user_id,'role'=>$wpi_role,'set_by'=>get_current_user_id()) );
        $this->json( array('success'=>true) );
    }


    /* ── User Roles ──────────────────────────────────────────────── */

    public function wpi_get_user_roles() {
        $this->check_nonce();
        global $wpdb;

        $is_sys_owner = $this->is_system_owner();
        $my_org_id    = $this->get_org_id();

        // Scope to org — system owner sees all
        if ( $is_sys_owner ) {
            $org_join  = '';
            $org_where = '';
        } elseif ( $my_org_id ) {
            $org_join  = " JOIN {$wpdb->prefix}wpi_org_users org_scope ON org_scope.user_id = ur.user_id AND org_scope.org_id = " . (int) $my_org_id;
            $org_where = '';
        } else {
            // No org assigned — only see yourself
            $org_join  = '';
            $org_where = $wpdb->prepare( ' AND ur.user_id = %d', get_current_user_id() );
        }

        $rows = $wpdb->get_results(
            "SELECT ur.user_id AS id, ur.role, ur.last_seen, u.user_email AS email,
                    COALESCE(NULLIF(TRIM(CONCAT(COALESCE(um1.meta_value,''),' ',COALESCE(um2.meta_value,''))), ''), u.display_name) AS name,
                    o.name AS org_name,
                    0 AS is_wp_admin
             FROM {$wpdb->prefix}wpi_user_roles ur
             JOIN {$wpdb->users} u ON u.ID = ur.user_id
             {$org_join}
             LEFT JOIN {$wpdb->usermeta} um1 ON um1.user_id = ur.user_id AND um1.meta_key = 'first_name'
             LEFT JOIN {$wpdb->usermeta} um2 ON um2.user_id = ur.user_id AND um2.meta_key = 'last_name'
             LEFT JOIN {$wpdb->prefix}wpi_org_users ou ON ou.user_id = ur.user_id
             LEFT JOIN {$wpdb->prefix}wpi_organisations o ON o.id = ou.org_id
             WHERE 1=1 {$org_where}
             ORDER BY ur.role DESC, name ASC"
        );

        // Pre-fetch seat assignments for this org to avoid N+1 queries
        $org_id_for_seats = $my_org_id;
        $seated_user_ids  = array();
        if ( $org_id_for_seats ) {
            $seat_rows = $wpdb->get_col( $wpdb->prepare(
                "SELECT user_id FROM {$wpdb->prefix}wpi_seat_assignments WHERE org_id=%d AND status='assigned' AND user_id IS NOT NULL",
                $org_id_for_seats
            ) );
            $seated_user_ids = array_map( 'intval', $seat_rows );
        }

        $seen_ids        = array();
        $out             = array();
        $system_owner_id = (int) get_option( 'wpi_system_owner_id', 0 );
        foreach ( $rows as $r ) {
            // Never expose the system owner to any app-layer user
            if ( $system_owner_id && (int) $r->id === $system_owner_id ) continue;
            $seen_ids[] = (int) $r->id;
            $r->id          = (int) $r->id;
            $r->is_wp_admin = (bool) user_can( $r->id, 'manage_options' );
            if ( $r->is_wp_admin ) $r->role = 'administrator';
            $r->deactivated = (bool) get_user_meta( $r->id, 'wpi_deactivated', true );
            $r->last_seen   = $r->last_seen ?: null;
            $r->has_seat    = in_array( (int) $r->id, $seated_user_ids, true );
            $out[] = $r;
        }

        // Include WP admins in scope who may not have a wpi_user_roles row
        $admin_args = array( 'role' => 'administrator', 'fields' => array( 'ID', 'user_email', 'display_name' ) );
        if ( ! $is_sys_owner && $my_org_id ) {
            $org_admin_ids = $wpdb->get_col( $wpdb->prepare(
                "SELECT user_id FROM {$wpdb->prefix}wpi_org_users WHERE org_id = %d", $my_org_id
            ) );
            if ( empty( $org_admin_ids ) ) { $this->json( $out ?: array() ); return; }
            $admin_args['include'] = $org_admin_ids;
        } elseif ( ! $is_sys_owner ) {
            $this->json( $out ?: array() );
            return;
        }

        $wp_admins = get_users( $admin_args );
        foreach ( $wp_admins as $u ) {
            if ( in_array( (int) $u->ID, $seen_ids ) ) continue;
            // Never show system owner in org user lists
            if ( $system_owner_id && (int) $u->ID === $system_owner_id ) continue;
            $fn   = get_user_meta( $u->ID, 'first_name', true );
            $ln   = get_user_meta( $u->ID, 'last_name',  true );
            $name = trim( "$fn $ln" ) ?: $u->display_name;
            $org  = $wpdb->get_var( $wpdb->prepare(
                "SELECT o.name FROM {$wpdb->prefix}wpi_organisations o
                 JOIN {$wpdb->prefix}wpi_org_users ou ON ou.org_id = o.id
                 WHERE ou.user_id = %d LIMIT 1", $u->ID
            ) );
            $admin_ls = $wpdb->get_var( $wpdb->prepare(
                "SELECT last_seen FROM {$wpdb->prefix}wpi_user_roles WHERE user_id = %d", $u->ID
            ) );
            $out[] = (object) array(
                'id'          => (int) $u->ID,
                'role'        => 'administrator',
                'email'       => $u->user_email,
                'name'        => $name,
                'org_name'    => $org ?: '',
                'is_wp_admin' => true,
                'last_seen'   => $admin_ls ?: null,
                'deactivated' => (bool) get_user_meta( $u->ID, 'wpi_deactivated', true ),
                'has_seat'    => in_array( (int) $u->ID, $seated_user_ids, true ),
            );
        }

        $this->json( $out ?: array() );
    }

    public function wpi_set_user_role() {
        $this->check_nonce();
        if ( ! is_user_logged_in() ) $this->error('Access denied', 403);
        global $wpdb;
        $body    = $this->input();
        $user_id = absint( $body['user_id'] ?? 0 );
        $role    = sanitize_text_field( $body['role'] ?? 'standard' );
        if ( ! $user_id ) $this->error('user_id required');
        $allowed = array('administrator','super_manager','manager','standard','basic','guest');
        if ( ! in_array($role, $allowed) ) $this->error('Invalid role');

        // Security: non-system-owners can only manage users in their own org (IDOR fix)
        if ( ! $this->is_system_owner() ) {
            $my_org = $this->get_org_id();
            if ( $my_org ) {
                $in_org = $wpdb->get_var( $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}wpi_org_users WHERE org_id=%d AND user_id=%d",
                    $my_org, $user_id
                ) );
                if ( ! $in_org ) $this->error('Access denied', 403);
            }
        }

        // Security: only system owner can assign roles above 'basic'
        // Non-system-owners cannot upgrade users who have no active token
        if ( ! $this->is_system_owner() && $role !== 'basic' ) {
            // Check if target user has an active seat/token
            require_once WPI_PLUGIN_DIR . 'includes/class-access.php';
            $seat = WPI_Access::get_user_seat( $user_id );
            $direct = WPI_Access::get_user_direct_licence( $user_id );
            $org_row = $wpdb->get_row( $wpdb->prepare(
                "SELECT org_id FROM {$wpdb->prefix}wpi_org_users WHERE user_id=%d LIMIT 1", $user_id
            ) );
            $has_valid_licence = $seat
                || $direct
                || ( $org_row && WPI_Access::org_has_valid_licence( (int)$org_row->org_id ) );

            if ( ! $has_valid_licence ) {
                $this->error( 'Cannot assign this role: user does not have an active subscription token. Assign a token first.' );
            }
        }

        $wpdb->replace( $wpdb->prefix.'wpi_user_roles', array(
            'user_id' => $user_id,
            'role'    => $role,
            'set_by'  => get_current_user_id()
        ) );
        $this->json( array('success'=>true) );
    }

    public function wpi_deactivate_user() {
        $this->check_nonce();
        if ( ! $this->is_system_owner() ) $this->error('Access denied', 403);
        global $wpdb;
        $body    = $this->input();
        $user_id = absint( $body['user_id'] ?? 0 );
        if ( ! $user_id ) $this->error('user_id required');
        // Cannot deactivate self or other system owners
        if ( $user_id === get_current_user_id() ) $this->error('Cannot deactivate yourself');
        require_once WPI_PLUGIN_DIR . 'includes/class-admin.php';
        if ( WPI_Admin::is_system_owner( $user_id ) ) $this->error('Cannot deactivate system owner');
        $active = isset( $body['active'] ) ? (bool) $body['active'] : false; // true = reactivate, false = deactivate
        if ( $active ) {
            // Reactivate — restore basic role
            delete_user_meta( $user_id, 'wpi_deactivated' );
            delete_user_meta( $user_id, 'wpi_deactivated_at' );
            update_user_meta( $user_id, 'wpi_access_basic', 1 );
        } else {
            // Deactivate — lock out
            update_user_meta( $user_id, 'wpi_deactivated', 1 );
            update_user_meta( $user_id, 'wpi_deactivated_at', current_time('mysql') );
            update_user_meta( $user_id, 'wpi_access_basic', 1 );
            // Destroy all sessions
            $sessions = WP_Session_Tokens::get_instance( $user_id );
            $sessions->destroy_all();
        }
        $this->json( array('success'=>true,'active'=>$active) );
    }

    public function wpi_delete_user() {
        $this->check_nonce();
        if ( ! $this->is_system_owner() ) $this->error('Access denied', 403);
        global $wpdb;
        $body    = $this->input();
        $user_id = absint( $body['user_id'] ?? 0 );
        if ( ! $user_id ) $this->error('user_id required');
        if ( $user_id === get_current_user_id() ) $this->error('Cannot delete yourself');
        require_once WPI_PLUGIN_DIR . 'includes/class-admin.php';
        if ( WPI_Admin::is_system_owner( $user_id ) ) $this->error('Cannot delete system owner');
        // Clean up WPI data
        $wpdb->delete( $wpdb->prefix.'wpi_user_roles',  array('user_id'=>$user_id) );
        $wpdb->delete( $wpdb->prefix.'wpi_org_users',   array('user_id'=>$user_id) );
        $wpdb->delete( $wpdb->prefix.'wpi_team_members',array('user_id'=>$user_id) );
        // Revoke any direct token
        $wpdb->update( $wpdb->prefix.'wpi_licences',
            array('status'=>'revoked','user_id'=>0,'assigned_to'=>'none'),
            array('user_id'=>$user_id,'assigned_to'=>'user')
        );
        // Delete WP user (reassign content to system owner)
        require_once ABSPATH.'wp-admin/includes/user.php';
        $owner_id = $wpdb->get_var("SELECT user_id FROM {$wpdb->prefix}wpi_user_roles WHERE role='administrator' ORDER BY user_id ASC LIMIT 1") ?: 1;
        wp_delete_user( $user_id, $owner_id );
        $this->json( array('success'=>true) );
    }

    public function wpi_get_role_info() {
        $this->check_nonce();
        global $wpdb;
        $uid = absint( $_GET['user_id'] ?? get_current_user_id() );
        $role = $wpdb->get_var( $wpdb->prepare("SELECT role FROM {$wpdb->prefix}wpi_user_roles WHERE user_id=%d", $uid) );
        $this->json( array('user_id'=>$uid, 'role'=>$role ?: 'guest') );
    }

    /* ── System Settings ─────────────────────────────────────────── */

    public function wpi_get_system_settings() {
        $this->check_nonce();
        $uid      = get_current_user_id();
        $is_owner = $this->is_system_owner();
        $is_admin = $this->can('administrator');

        // Personal timezone — stored per-user so every user can have their own
        $personal_tz = get_user_meta( $uid, 'wpi_timezone', true );
        $system_tz   = get_option('timezone_string', 'UTC');
        $timezone    = $personal_tz ?: $system_tz;

        $settings = array(
            'timezone' => $timezone,
        );

        // System-wide settings only visible to admins+
        if ( $is_admin ) {
            $sys = get_option('wpi_system_settings', array());
            $settings['registration_enabled'] = (int) get_option('wpi_registration_enabled', 0);
            $settings['email_verification']    = (int) get_option('wpi_email_verification',    1);
            $settings['max_sessions']         = (int) get_option('wpi_max_sessions', 5);
            $settings = array_merge($sys, $settings);
        }

        $this->json( $settings );
    }

    public function wpi_save_system_settings() {
        $this->check_nonce();
        $uid      = get_current_user_id();
        $is_owner = $this->is_system_owner();
        $body     = $this->input();

        // ── Personal settings — any logged-in user can save these ──
        if ( isset($body['timezone']) ) {
            $tz = sanitize_text_field($body['timezone']);
            // Store as user meta so each user has their own timezone
            update_user_meta( $uid, 'wpi_timezone', $tz );
        }

        // ── System-wide settings — system owner only ───────────────
        if ( $is_owner ) {
            if ( isset($body['registration_enabled']) ) {
                update_option('wpi_registration_enabled', absint($body['registration_enabled']), false);
            }
            if ( isset($body['email_verification']) ) {
                update_option('wpi_email_verification', absint($body['email_verification']), false);
            }
            if ( isset($body['max_sessions']) ) {
                update_option('wpi_max_sessions', absint($body['max_sessions']), false);
            }
            // System owner can also set the global default timezone
            if ( isset($body['timezone']) && !empty($body['set_global_timezone']) ) {
                $settings = get_option('wpi_system_settings', array());
                $settings['timezone'] = sanitize_text_field($body['timezone']);
                update_option('wpi_system_settings', $settings, false);
                update_option('timezone_string', $settings['timezone']);
            }
        }

        $this->json( array('success'=>true) );
    }

    public function wpi_refresh_inspection_titles() {
        $this->check_nonce();
        global $wpdb;
        $uid = get_current_user_id();
        $is_owner = $this->is_system_owner();
        // Owners re-resolve all org inspections; regular users only their own.
        // Use per-user meta instead of a global option to avoid cross-org side effects.
        if ( $is_owner ) {
            delete_user_meta( $uid, 'wpi_migrated_inspection_titles' );
        }
        $updated = 0;
        $where = $is_owner
            ? "WHERE i.status IN ('completed','in_progress')"
            : $wpdb->prepare("WHERE i.status IN ('completed','in_progress') AND i.conducted_by = %d", $uid);
        $inspections = $wpdb->get_results(
            "SELECT i.id, i.title, i.conducted_at, i.score, i.site_name,
                    t.title as template_title, t.settings as template_settings
             FROM {$wpdb->prefix}wpi_inspections i
             LEFT JOIN {$wpdb->prefix}wpi_templates t ON t.id = i.template_id
             $where"
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
                    $val = $field_map[$m[1]] ?? '';
                    if ( $val && preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}/', $val) ) {
                        try { $val = (new DateTime($val))->format('d/m/Y'); } catch(\Exception $e) {}
                    } elseif ( $val && preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}/', $val) ) {
                        try { $val = (new DateTime($val))->format('d/m/Y'); } catch(\Exception $e) {}
                    }
                    return $val;
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
                $updated++;
            }
        }
        update_user_meta( $uid, 'wpi_migrated_inspection_titles', '1' );
        $this->json( array('success' => true, 'updated' => $updated) );
    }

    /* ── Sites ───────────────────────────────────────────────────── */

    public function wpi_get_sites() {
        $this->check_nonce();
        global $wpdb;

        // Self-heal: add parent_id column if missing — guarded by flag so it only runs once
        if ( ! get_option('wpi_sites_parent_id_col') ) {
            $cols = $wpdb->get_col( "SHOW COLUMNS FROM `{$wpdb->prefix}wpi_sites`", 0 );
            if ( $cols && ! in_array( 'parent_id', $cols, true ) ) {
                $wpdb->query( "ALTER TABLE `{$wpdb->prefix}wpi_sites` ADD COLUMN `parent_id` BIGINT(20) UNSIGNED DEFAULT NULL AFTER `org_id`" );
            }
            update_option( 'wpi_sites_parent_id_col', '1', false );
        }

        $context    = sanitize_text_field( $_GET['context'] ?? '' );
        $is_picker  = ( $context === 'picker' );
        $uid        = get_current_user_id();
        $wpi_role   = $this->get_wpi_role();
        // super_manager and above see all org sites in picker; standard/basic only see assigned
        $is_manager = $this->is_system_owner() || in_array( $wpi_role, array('administrator','super_manager','manager'), true );

        if ( $this->is_system_owner() ) {
            $sites = $wpdb->get_results(
                "SELECT s.*, COUNT(DISTINCT su.user_id) as user_count, 0 as is_directory
                 FROM {$wpdb->prefix}wpi_sites s
                 LEFT JOIN {$wpdb->prefix}wpi_site_users su ON su.site_id=s.id
                 GROUP BY s.id ORDER BY s.name ASC"
            );
        } else {
            $org_id = $this->get_org_id();
            if ( ! $org_id ) { $this->json( array() ); return; }

            if ( $is_picker && ! $is_manager ) {
                // Standard users: only sites they are explicitly assigned to
                $assigned_ids = $wpdb->get_col( $wpdb->prepare(
                    "SELECT site_id FROM {$wpdb->prefix}wpi_site_users WHERE user_id=%d", $uid
                ) );

                if ( empty( $assigned_ids ) ) {
                    $this->json( array() );
                    return;
                }

                // Also load ancestor sites for path/depth context (marked as directories)
                $all_sites = $wpdb->get_results( $wpdb->prepare(
                    "SELECT s.*, 0 as user_count FROM {$wpdb->prefix}wpi_sites s WHERE s.org_id=%d", $org_id
                ) );
                // Build id→site map
                $all_by_id = array();
                foreach ( $all_sites as $s ) $all_by_id[$s->id] = $s;

                // Collect assigned sites + all their ancestors
                $needed_ids = array_flip( array_map( 'intval', $assigned_ids ) );
                foreach ( array_map( 'intval', $assigned_ids ) as $aid ) {
                    $cur = isset($all_by_id[$aid]) ? $all_by_id[$aid] : null;
                    while ( $cur && $cur->parent_id && isset($all_by_id[$cur->parent_id]) ) {
                        $needed_ids[(int)$cur->parent_id] = true;
                        $cur = $all_by_id[$cur->parent_id];
                    }
                }

                $sites = array();
                foreach ( $all_sites as $s ) {
                    if ( ! isset($needed_ids[(int)$s->id]) ) continue;
                    // Mark ancestor-only sites as directories (not selectable)
                    $s->is_directory = in_array( (int)$s->id, array_map('intval',$assigned_ids), true ) ? 0 : 1;
                    $sites[] = $s;
                }
            } else {
                // Managers+: all org sites; mark parent-only sites as directories
                $sites = $wpdb->get_results( $wpdb->prepare(
                    "SELECT s.*, COUNT(DISTINCT su.user_id) as user_count, 0 as is_directory
                     FROM {$wpdb->prefix}wpi_sites s
                     LEFT JOIN {$wpdb->prefix}wpi_site_users su ON su.site_id=s.id
                     WHERE s.org_id=%d GROUP BY s.id ORDER BY s.name ASC", $org_id
                ) );
                // Mark sites that have children but are not themselves assigned to anyone as directories
                $all_ids = array_map(function($s){ return (int)$s->id; }, $sites);
                $parent_ids = array_unique(array_filter(array_map(function($s){ return (int)$s->parent_id; }, $sites)));
                // Sites that are only parents (never a leaf selectable site) should be non-selectable
                // We mark a site as directory if it has children AND has zero direct users
                foreach ( $sites as $s ) {
                    $has_children = in_array( (int)$s->id, $parent_ids, true );
                    $s->is_directory = ( $has_children && (int)$s->user_count === 0 ) ? 1 : 0;
                }
            }
        }
        // Build path for each site (e.g. Australia > Victoria > Lansell Square)
        $sites = $sites ?: array();
        $by_id = array();
        foreach ( $sites as $s ) $by_id[$s->id] = $s;
        foreach ( $sites as $s ) {
            $path  = array();
            $cur   = $s;
            $depth = 0;
            while ( $cur && $depth < 10 ) {
                array_unshift( $path, $cur->name );
                $cur = isset($by_id[$cur->parent_id]) ? $by_id[$cur->parent_id] : null;
                $depth++;
            }
            $s->path         = implode( ' > ', $path );
            $s->depth        = count($path) - 1;
            $s->parent_id    = $s->parent_id ? (int)$s->parent_id : null;
            $s->user_count   = (int)($s->user_count ?? 0);
            $s->is_directory = (int)($s->is_directory ?? 0);
        }

        // A site/folder with children is a folder/category in the picker and must not be selectable.
        // Only leaf items under a folder (for example Cleaning > Burwood One, Security > Westfield Geelong) can be selected.
        $parent_ids = array();
        foreach ( $sites as $child ) {
            if ( ! empty( $child->parent_id ) ) {
                $parent_ids[(int) $child->parent_id] = true;
            }
        }
        foreach ( $sites as $s ) {
            $s->has_children  = ! empty( $parent_ids[(int) $s->id] ) ? 1 : 0;
            $s->is_directory = ( ! empty( $s->has_children ) || ! empty( $s->is_directory ) ) ? 1 : 0;
        }

        $this->json( $sites );
    }

    public function wpi_create_site() {
        $this->check_nonce();
        if ( ! $this->can('administrator') ) $this->error('Access denied', 403);
        $this->check_billing_limit('create_site');
        global $wpdb;

        // Self-heal: add parent_id column if missing (existing installs)
        $cols = $wpdb->get_col( "SHOW COLUMNS FROM `{$wpdb->prefix}wpi_sites`", 0 );
        if ( $cols && ! in_array( 'parent_id', $cols, true ) ) {
            $wpdb->query( "ALTER TABLE `{$wpdb->prefix}wpi_sites` ADD COLUMN `parent_id` BIGINT(20) UNSIGNED DEFAULT NULL AFTER `org_id`" );
            $wpdb->query( "ALTER TABLE `{$wpdb->prefix}wpi_sites` ADD KEY `parent_id` (`parent_id`)" );
        }

        $body   = $this->input();
        $name   = sanitize_text_field( $body['name'] ?? '' );
        if ( ! $name ) $this->error('Site name required');
        $org_id = $this->get_org_id() ?: absint($body['org_id'] ?? 0);
        // System owner: if no org specified, assign to first available org or leave null
        if ( $this->is_system_owner() && ! $org_id ) {
            $first_org = $wpdb->get_var( "SELECT id FROM {$wpdb->prefix}wpi_orgs ORDER BY id ASC LIMIT 1" );
            if ( $first_org ) $org_id = (int) $first_org;
        }
        // Clear error before org check so message is user-friendly
        if ( ! $org_id ) {
            $this->error( 'Please select an organisation before creating a site.' );
        }
        $parent_id = isset($body['parent_id']) && $body['parent_id'] ? absint($body['parent_id']) : null;
        // Validate parent belongs to same org
        if ( $parent_id ) {
            $parent_org = $wpdb->get_var( $wpdb->prepare( "SELECT org_id FROM {$wpdb->prefix}wpi_sites WHERE id=%d", $parent_id ) );
            if ( ! $parent_org || ( $org_id && (int)$parent_org !== (int)$org_id ) ) $parent_id = null;
        }
        $result = $wpdb->insert( $wpdb->prefix.'wpi_sites', array(
            'name'        => $name,
            'address'     => sanitize_text_field($body['address'] ?? ''),
            'description' => sanitize_text_field($body['description'] ?? ''),
            'org_id'      => $org_id ?: null,
            'parent_id'   => $parent_id,
        ));
        if ( $result === false ) {
            $this->error( 'Database error: ' . $wpdb->last_error, 500 );
        }
        $this->json( array('success'=>true, 'id'=>$wpdb->insert_id, 'name'=>$name) );
    }

    public function wpi_update_site() {
        $this->check_nonce();
        if ( ! $this->can('administrator') ) $this->error('Access denied', 403);
        global $wpdb;
        $body = $this->input();
        $id   = absint($body['id'] ?? 0);
        if ( ! $id ) $this->error('id required');
        // Verify site belongs to caller's org
        if ( ! $this->is_system_owner() ) {
            $org_id  = $this->get_org_id();
            $site_org = $wpdb->get_var( $wpdb->prepare( "SELECT org_id FROM {$wpdb->prefix}wpi_sites WHERE id=%d", $id ) );
            if ( (int)$site_org !== (int)$org_id ) $this->error('Access denied', 403);
        }
        $upd_parent = array_key_exists('parent_id', $body)
            ? ( $body['parent_id'] ? absint($body['parent_id']) : null )
            : false; // false = not provided, don't change
        $upd_data = array(
            'name'        => sanitize_text_field($body['name'] ?? ''),
            'address'     => sanitize_text_field($body['address'] ?? ''),
            'description' => sanitize_text_field($body['description'] ?? ''),
        );
        if ( $upd_parent !== false ) $upd_data['parent_id'] = $upd_parent;
        $wpdb->update( $wpdb->prefix.'wpi_sites', $upd_data, array('id'=>$id) );
        $this->json( array('success'=>true) );
    }

    public function wpi_delete_site() {
        $this->check_nonce();
        if ( ! $this->can('administrator') ) $this->error('Access denied', 403);
        global $wpdb;
        $body = $this->input();
        $id   = absint($body['id'] ?? 0);
        if ( ! $id ) $this->error('id required');
        // Verify site belongs to caller's org
        if ( ! $this->is_system_owner() ) {
            $org_id  = $this->get_org_id();
            $site_org = $wpdb->get_var( $wpdb->prepare( "SELECT org_id FROM {$wpdb->prefix}wpi_sites WHERE id=%d", $id ) );
            if ( (int)$site_org !== (int)$org_id ) $this->error('Access denied', 403);
        }
        $wpdb->delete( $wpdb->prefix.'wpi_sites', array('id'=>$id) );
        $this->json( array('success'=>true) );
    }

    public function wpi_get_site_users() {
        $this->check_nonce();
        global $wpdb;
        $site_id = absint( $_GET['site_id'] ?? 0 );
        if ( !$site_id ) $this->error('site_id required');
        $users = $wpdb->get_results( $wpdb->prepare(
            "SELECT su.user_id, u.display_name as full_name, u.user_email
             FROM {$wpdb->prefix}wpi_site_users su
             LEFT JOIN {$wpdb->users} u ON u.ID = su.user_id
             WHERE su.site_id = %d
             ORDER BY u.display_name ASC", $site_id
        ) );
        $this->json( $users ?: array() );
    }

    public function wpi_add_site_user() {
        $this->check_nonce();
        if ( ! $this->can('administrator') ) $this->error('Access denied', 403);
        global $wpdb;
        $body    = $this->input();
        $site_id = absint($body['site_id'] ?? 0);
        $user_id = absint($body['user_id'] ?? 0);
        if ( ! $site_id || ! $user_id ) $this->error('site_id and user_id required');
        $wpdb->replace( $wpdb->prefix.'wpi_site_users', array('site_id'=>$site_id,'user_id'=>$user_id) );
        $this->json( array('success'=>true) );
    }

    public function wpi_remove_site_user() {
        $this->check_nonce();
        if ( ! $this->can('administrator') ) $this->error('Access denied', 403);
        global $wpdb;
        $body    = $this->input();
        $site_id = absint($body['site_id'] ?? 0);
        $user_id = absint($body['user_id'] ?? 0);
        $wpdb->delete( $wpdb->prefix.'wpi_site_users', array('site_id'=>$site_id,'user_id'=>$user_id) );
        $this->json( array('success'=>true) );
    }

    /* ── Organisations ───────────────────────────────────────────── */

    /* ── Audit4me Access — Licence Management ──────────────────── */

    public function wpi_get_licences() {
        $this->check_nonce();
        if ( ! $this->is_system_owner() ) $this->error( 'Access denied', 403 );
        require_once WPI_PLUGIN_DIR . 'includes/class-access.php';

        // Auto top-up pool to minimum 10 unassigned
        global $wpdb;
        $unassigned = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}wpi_licences WHERE status='unassigned'"
        );
        if ( $unassigned < 10 ) {
            $pool_mix = array(
                'trial'     => array('qty'=>2,'seats'=>3),
                'monthly'   => array('qty'=>2,'seats'=>5),
                'quarterly' => array('qty'=>1,'seats'=>5),
                '6monthly'  => array('qty'=>1,'seats'=>10),
                'annual'    => array('qty'=>3,'seats'=>10),
                'lifetime'  => array('qty'=>1,'seats'=>20),
            );
            $needed = 10 - $unassigned;
            $generated = 0;
            foreach ( $pool_mix as $type => $cfg ) {
                if ( $generated >= $needed ) break;
                $to_gen = min( $cfg['qty'], $needed - $generated );
                for ( $i = 0; $i < $to_gen; $i++ ) {
                    WPI_Access::create_token( $type, $cfg['seats'], 'Auto-generated pool', 0 );
                    $generated++;
                }
            }
        }

        $status = sanitize_text_field( $_GET['status'] ?? '' );
        $this->json( WPI_Access::get_all_tokens( $status ) );
    }

    public function wpi_create_licence() {
        $this->check_nonce();
        if ( ! $this->is_system_owner() ) $this->error( 'Access denied', 403 );
        require_once WPI_PLUGIN_DIR . 'includes/class-access.php';
        $body  = $this->input();
        $type  = sanitize_text_field( $body['licence_type'] ?? 'trial' );
        $seats = absint( $body['seats'] ?? 5 );
        $notes = sanitize_textarea_field( $body['notes'] ?? '' );
        $count = absint( $body['count'] ?? 1 );
        $count = min( $count, 50 ); // max 50 at once
        $tokens = array();
        for ( $i = 0; $i < $count; $i++ ) {
            $result = WPI_Access::create_token( $type, $seats, $notes );
            if ( $result ) $tokens[] = $result;
        }
        $this->json( array( 'success' => true, 'tokens' => $tokens ) );
    }

    public function wpi_assign_licence() {
        $this->check_nonce();
        if ( ! $this->is_system_owner() ) $this->error( 'Access denied', 403 );
        require_once WPI_PLUGIN_DIR . 'includes/class-access.php';
        $body       = $this->input();
        $licence_id = absint( $body['licence_id'] ?? 0 );
        $org_id     = absint( $body['org_id'] ?? 0 );
        if ( ! $licence_id || ! $org_id ) $this->error( 'licence_id and org_id required' );
        $ok = WPI_Access::assign_token( $licence_id, $org_id );
        if ( ! $ok ) $this->error( 'Token already assigned or not found' );
        $this->json( array( 'success' => true ) );
    }

    public function wpi_revoke_licence() {
        $this->check_nonce();
        if ( ! $this->is_system_owner() ) $this->error( 'Access denied', 403 );
        require_once WPI_PLUGIN_DIR . 'includes/class-access.php';
        $body       = $this->input();
        $licence_id = absint( $body['licence_id'] ?? 0 );
        $reason     = sanitize_text_field( $body['reason'] ?? '' );
        if ( ! $licence_id ) $this->error( 'licence_id required' );
        WPI_Access::revoke_token( $licence_id, $reason );
        $this->json( array( 'success' => true ) );
    }

    public function wpi_delete_licence() {
        $this->check_nonce();
        if ( ! $this->is_system_owner() ) $this->error( 'Access denied', 403 );
        global $wpdb;
        $body       = $this->input();
        $licence_id = absint( $body['licence_id'] ?? 0 );
        if ( ! $licence_id ) $this->error( 'licence_id required' );
        $lic = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wpi_licences WHERE id=%d", $licence_id
        ) );
        if ( ! $lic ) $this->error( 'Not found', 404 );
        if ( $lic->status === 'assigned' ) $this->error( 'Cannot delete an assigned token. Revoke first.' );
        $wpdb->delete( $wpdb->prefix . 'wpi_licences', array( 'id' => $licence_id ) );
        $this->json( array( 'success' => true ) );
    }

    public function wpi_get_token_log() {
        $this->check_nonce();
        if ( ! $this->is_system_owner() ) $this->error( 'Access denied', 403 );
        global $wpdb;
        $licence_id = absint( $_GET['licence_id'] ?? 0 );
        $where = $licence_id ? $wpdb->prepare( ' WHERE l.licence_id=%d', $licence_id ) : '';
        $rows = $wpdb->get_results(
            "SELECT l.*, u.display_name as performed_by_name
             FROM {$wpdb->prefix}wpi_token_log l
             LEFT JOIN {$wpdb->users} u ON u.ID = l.performed_by
             {$where}
             ORDER BY l.created_at DESC LIMIT 100"
        );
        $this->json( $rows ?: array() );
    }

    public function wpi_get_licence_seats() {
        $this->check_nonce();
        $licence_id = absint( $_GET['licence_id'] ?? 0 );
        if ( ! $licence_id ) $this->error('licence_id required');
        require_once WPI_PLUGIN_DIR . 'includes/class-access.php';
        // Only system owner OR the licence holder can view seats
        if ( ! $this->is_system_owner() ) {
            $seat = WPI_Access::get_user_seat( get_current_user_id() );
            if ( ! $seat || (int)$seat->licence_id !== $licence_id || $seat->status !== 'holder' ) {
                $this->error('Access denied', 403);
            }
        }
        $licence = WPI_Access::get_licence( $licence_id );
        $seats   = WPI_Access::get_seats( $licence_id );
        $this->json( array(
            'licence' => $licence,
            'seats'   => $seats,
            'used'    => WPI_Access::count_used_seats( $licence_id ),
        ) );
    }

    public function wpi_register_seat_user() {
        $this->check_nonce();
        require_once WPI_PLUGIN_DIR . 'includes/class-access.php';
        $body       = $this->input();
        $licence_id = absint( $body['licence_id'] ?? 0 );
        $first      = sanitize_text_field( $body['first_name'] ?? '' );
        $last       = sanitize_text_field( $body['last_name']  ?? '' );
        $email      = sanitize_email( $body['email']           ?? '' );
        $password   = $body['password'] ?? '';

        if ( ! $licence_id ) $this->error('licence_id required');
        if ( ! $first || ! $last ) $this->error('First and last name required');
        if ( ! is_email($email) ) $this->error('Valid email required');
        if ( strlen($password) < 8 ) $this->error('Password must be at least 8 characters');
        if ( ! preg_match('/[A-Z]/', $password) || ! preg_match('/[0-9]/', $password) || ! preg_match('/[^a-zA-Z0-9]/', $password) ) {
            $this->error('Password must contain at least one uppercase letter, one number, and one special character');
        }

        // Verify caller is system owner OR the licence holder
        if ( ! $this->is_system_owner() ) {
            $seat = WPI_Access::get_user_seat( get_current_user_id() );
            if ( ! $seat || (int)$seat->licence_id !== $licence_id || $seat->status !== 'holder' ) {
                $this->error('Access denied', 403);
            }
        }

        // Check available seats
        $used  = WPI_Access::count_used_seats( $licence_id );
        $lic   = WPI_Access::get_licence( $licence_id );
        if ( ! $lic ) $this->error('Licence not found');
        if ( $used >= (int) $lic->seats ) $this->error('Unable to activate this token. No seats are currently available. Please contact your administrator for help.');

        // Check email not already registered
        if ( email_exists($email) ) $this->error('An account with this email already exists');

        // Create WP user
        $username = sanitize_user( strstr($email, '@', true) );
        $base = $username; $i = 1;
        while ( username_exists($username) ) { $username = $base . $i++; }

        global $wpdb;
        $uid = wp_insert_user( array(
            'user_login'   => $username,
            'user_email'   => $email,
            'user_pass'    => $password,
            'first_name'   => $first,
            'last_name'    => $last,
            'display_name' => trim("$first $last"),
        ) );
        if ( is_wp_error($uid) ) $this->error( $uid->get_error_message() );

        // Set role
        $wpdb->replace( $wpdb->prefix.'wpi_user_roles', array(
            'user_id' => $uid, 'role' => 'standard', 'set_by' => get_current_user_id()
        ) );

        // Fill seat
        $seat_id = WPI_Access::fill_seat( $licence_id, $uid, false );
        if ( ! $seat_id ) {
            wp_delete_user($uid);
            $this->error('No seats available');
        }

        // Clear basic access
        delete_user_meta( $uid, 'wpi_access_basic' );

        // Send welcome email
        $site      = get_bloginfo('name') ?: 'Audit4me';
        $login_url = home_url('/?wpi=1');
        $body_html = '
            <p style="margin:0 0 16px;color:#374151;font-size:14px;line-height:1.6;">Hi '.esc_html($first).',</p>
            <p style="margin:0 0 20px;color:#374151;font-size:14px;line-height:1.6;">
                You have been added to <strong>'.esc_html($site).'</strong>. Your account is ready to use.
            </p>
            <table width="100%" cellpadding="0" cellspacing="0" style="background:#f8faff;border:1.5px solid #e0e7ff;border-radius:10px;margin-bottom:20px;">
              <tr><td style="padding:16px 18px;">
                <p style="margin:0 0 4px;font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;">Your Login</p>
                <p style="margin:0;font-size:14px;color:#111827;">'.esc_html($email).'</p>
              </td></tr>
            </table>';
        require_once WPI_PLUGIN_DIR . 'includes/class-scheduler.php';
        WPI_Scheduler::send_branded_email(
            $email,
            'Welcome to ' . $site . ' — Your account is ready',
            $body_html, '#1a3a5c', '👋 WELCOME', $login_url, 'Sign In →'
        );

        $this->json( array('success'=>true,'user_id'=>$uid,'seat_id'=>$seat_id) );
    }

    public function wpi_assign_existing_to_seat() {
        $this->check_nonce();
        require_once WPI_PLUGIN_DIR . 'includes/class-access.php';
        $body       = $this->input();
        $licence_id = absint( $body['licence_id'] ?? 0 );
        $user_id    = absint( $body['user_id']    ?? 0 );
        if ( ! $licence_id || ! $user_id ) $this->error('licence_id and user_id required');

        // Verify caller is system owner OR the licence holder
        if ( ! $this->is_system_owner() ) {
            $seat = WPI_Access::get_user_seat( get_current_user_id() );
            if ( ! $seat || (int)$seat->licence_id !== $licence_id || $seat->status !== 'holder' ) {
                $this->error('Access denied', 403);
            }
        }

        // Check user not already on a seat
        $existing = WPI_Access::get_user_seat( $user_id );
        if ( $existing ) $this->error('This user already has an active seat');

        // Check available seats
        $used = WPI_Access::count_used_seats( $licence_id );
        $lic  = WPI_Access::get_licence( $licence_id );
        if ( ! $lic ) $this->error('Licence not found');
        if ( $used >= (int) $lic->seats ) $this->error('Unable to activate this token. No seats are currently available. Please contact your administrator for help.');

        // Fill seat
        $seat_id = WPI_Access::fill_seat( $licence_id, $user_id, false );
        if ( ! $seat_id ) $this->error('No seats available');

        // Clear basic access
        delete_user_meta( $user_id, 'wpi_access_basic' );

        // Upgrade role if still basic
        global $wpdb;
        $role = $wpdb->get_var( $wpdb->prepare(
            "SELECT role FROM {$wpdb->prefix}wpi_user_roles WHERE user_id=%d", $user_id
        ) );
        if ( ! $role || $role === 'basic' ) {
            $wpdb->replace( $wpdb->prefix . 'wpi_user_roles', array(
                'user_id' => $user_id,
                'role'    => 'standard',
                'set_by'  => get_current_user_id(),
            ) );
        }

        $this->json( array('success'=>true,'seat_id'=>$seat_id) );
    }

    public function wpi_revoke_seat() {
        $this->check_nonce();
        require_once WPI_PLUGIN_DIR . 'includes/class-access.php';
        $body    = $this->input();
        $seat_id = absint( $body['seat_id'] ?? 0 );
        if ( ! $seat_id ) $this->error('seat_id required');

        global $wpdb;
        $seat = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wpi_licence_seats WHERE id=%d", $seat_id
        ) );
        if ( ! $seat ) $this->error('Seat not found', 404);
        if ( $seat->status === 'holder' ) $this->error('Cannot revoke the token holder seat');

        // Verify caller is system owner OR the licence holder
        if ( ! $this->is_system_owner() ) {
            $my_seat = WPI_Access::get_user_seat( get_current_user_id() );
            if ( ! $my_seat || (int)$my_seat->licence_id !== (int)$seat->licence_id || $my_seat->status !== 'holder' ) {
                $this->error('Access denied', 403);
            }
        }

        $ok = WPI_Access::revoke_seat( $seat_id, get_current_user_id() );
        if ( ! $ok ) $this->error('Failed to revoke seat');
        $this->json( array('success'=>true) );
    }

    public function wpi_get_user_token_status() {
        $this->check_nonce();
        $uid = absint( $_GET['user_id'] ?? get_current_user_id() );
        require_once WPI_PLUGIN_DIR . 'includes/class-access.php';
        $status = WPI_Access::get_user_token_status( $uid );
        // Add seat info — graceful fallback if seats table doesn't exist yet
        try {
            $seat = WPI_Access::get_user_seat( $uid );
            if ( $seat ) {
                $status['seat_status'] = $seat->status;
                $status['licence_id']  = (int) $seat->licence_id;
                $status['total_seats'] = (int) $seat->total_seats;
                $status['used_seats']  = WPI_Access::count_used_seats( $seat->licence_id );
                $status['is_holder']   = $seat->status === 'holder';
            }
        } catch ( Exception $e ) {
            // Seats table may not exist yet — return basic status
        }
        $this->json( $status );
    }

    /**
     * Create a personal organisation for a self-registered individual user
     * Returns org_id
     */
    private function create_personal_org( $uid, $first, $last, $email ) {
        global $wpdb;
        wpi_ensure_org_licence_columns();
        $org_name = trim( $first . ' ' . $last ) ?: strstr( $email, '@', true );
        $wpdb->insert( $wpdb->prefix . 'wpi_organisations', array(
            'name'         => sanitize_text_field( $org_name ),
            'description'  => 'Personal organisation',
            'owner_id'     => $uid,
            'licence_type' => 'trial',
            'status'       => 'active',
            'created_at'   => current_time( 'mysql' ),
        ) );
        $org_id = $wpdb->insert_id;
        if ( ! $org_id ) return 0;
        // Add user as admin of their personal org
        $wpdb->replace( $wpdb->prefix . 'wpi_org_users', array(
            'org_id'  => $org_id,
            'user_id' => $uid,
            'role'    => 'administrator',
        ) );
        return $org_id;
    }


    /**
     * Fallback AJAX login — used when the REST API endpoint is unavailable
     * (blocked by security plugin, permalink issue, or server config).
     * Mirrors wpi_rest_login from class-api.php but via admin-ajax.php.
     */
    public function wpi_ajax_login() {
        $ip  = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $log = sanitize_text_field( $_POST['wpi_u'] ?? '' );
        $pwd = $_POST['wpi_k'] ?? '';
        $nonce = sanitize_text_field( $_POST['nonce'] ?? '' );

        if ( ! $nonce || ! wp_verify_nonce( $nonce, 'wpi_login' ) ) {
            wp_send_json( array( 'success' => false, 'data' => array( 'message' => 'Security check failed. Please refresh the page.' ) ) );
        }

        $rate_key_ip  = 'wpi_login_ip_'  . md5( $ip );
        $rate_key_usr = 'wpi_login_usr_' . md5( strtolower( $log ) );
        if ( (int) get_transient( $rate_key_ip ) >= 10 || (int) get_transient( $rate_key_usr ) >= 20 ) {
            wp_send_json( array( 'success' => false, 'data' => array( 'message' => 'Too many login attempts. Please wait a few minutes.' ) ) );
        }
        set_transient( $rate_key_ip,  (int) get_transient( $rate_key_ip )  + 1, 5 * MINUTE_IN_SECONDS );
        set_transient( $rate_key_usr, (int) get_transient( $rate_key_usr ) + 1, 5 * MINUTE_IN_SECONDS );

        if ( ! $log || ! $pwd ) {
            wp_send_json( array( 'success' => false, 'data' => array( 'message' => 'Please enter your username and password.' ) ) );
        }

        $username = $log;
        if ( is_email( $log ) ) {
            $u = get_user_by( 'email', $log );
            if ( $u ) $username = $u->user_login;
        }

        $user = wp_authenticate( $username, $pwd );

        if ( is_wp_error( $user ) ) {
            wp_send_json( array( 'success' => false, 'data' => array( 'message' => 'Incorrect username or password.' ) ) );
        }

        $remember = ! empty( $_POST['rememberme'] );
        wp_set_auth_cookie( $user->ID, $remember, is_ssl() );
        do_action( 'wp_login', $user->user_login, $user );

        delete_transient( $rate_key_ip );
        delete_transient( $rate_key_usr );
        wp_send_json( array( 'success' => true, 'data' => array( 'redirect' => home_url( '/?wpi=1' ) ) ) );
    }

    public function wpi_register_user() {
        // Check registration is enabled
        if ( ! get_option( 'wpi_registration_enabled', 0 ) ) {
            wp_send_json_error( array( 'message' => 'Registration is currently disabled.' ) );
            return;
        }

        // Rate limiting: max 10 registrations per IP per hour
        $ip         = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $rate_key   = 'wpi_reg_' . md5( $ip );
        $rate_count = (int) get_transient( $rate_key );
        if ( $rate_count >= 10 ) {
            wp_send_json_error( array( 'message' => 'Too many registration attempts. Please try again later.' ) );
            return;
        }
        set_transient( $rate_key, $rate_count + 1, HOUR_IN_SECONDS );

        global $wpdb;
        // Read from $_POST (FormData multipart)
        $first     = sanitize_text_field( $_POST['first_name'] ?? '' );
        $last      = sanitize_text_field( $_POST['last_name']  ?? '' );
        $email     = sanitize_email( $_POST['email']           ?? '' );
        $password  = $_POST['password']     ?? '';


        if ( ! $first || ! $last )     { wp_send_json_error( array( 'message' => 'First and last name are required.' ) ); return; }
        if ( ! is_email( $email ) )    { wp_send_json_error( array( 'message' => 'A valid email address is required.' ) ); return; }
        if ( strlen( $password ) < 8 ) { wp_send_json_error( array( 'message' => 'Password must be at least 8 characters.' ) ); return; }
        if ( ! preg_match('/[A-Z]/', $password) || ! preg_match('/[0-9]/', $password) || ! preg_match('/[^a-zA-Z0-9]/', $password) ) {
            wp_send_json_error( array( 'message' => 'Password must contain at least one uppercase letter, one number, and one special character.' ) ); return;
        }
        if ( email_exists( $email ) )  { wp_send_json_error( array( 'message' => 'Registration failed. Please check your details and try again.' ) ); return; }

        // Require email verification (if enabled)
        if ( get_option( 'wpi_email_verification', 1 ) ) {
            $verified_key = 'wpi_email_verified_' . md5( strtolower( $email ) );
            if ( ! get_transient( $verified_key ) ) {
                wp_send_json_error( array( 'message' => 'Email not verified. Please complete the verification step.' ) );
                return;
            }
            delete_transient( $verified_key );
        }

        // Generate unique username from email
        $username = sanitize_user( strstr( $email, '@', true ) );
        $base = $username;
        $i = 1;
        while ( username_exists( $username ) ) { $username = $base . $i++; }

        // Create WP user
        $uid = wp_insert_user( array(
            'user_login'   => $username,
            'user_email'   => $email,
            'user_pass'    => $password,
            'first_name'   => $first,
            'last_name'    => $last,
            'display_name' => trim( $first . ' ' . $last ),
        ) );
        if ( is_wp_error( $uid ) ) {
            wp_send_json_error( array( 'message' => $uid->get_error_message() ) );
            return;
        }

        // Set WPI role based on token
        // Will be updated after token check below
        $initial_role = 'basic'; // default until token confirmed

        // Send welcome email
        $site      = get_bloginfo('name') ?: get_option('blogname') ?: 'Audit4me';
        $login_url = home_url( '/?wpi=1' );
        $body_html = '
            <p style="margin:0 0 16px;color:#374151;font-size:14px;line-height:1.6;">Hi ' . esc_html($first) . ',</p>
            <p style="margin:0 0 20px;color:#374151;font-size:14px;line-height:1.6;">
                Welcome to <strong>' . esc_html($site) . '</strong>! Your account has been created successfully.
                You can now sign in using your email address and the password you chose during registration.
            </p>
            <table width="100%" cellpadding="0" cellspacing="0" style="background:#f8faff;border:1.5px solid #e0e7ff;border-radius:10px;margin-bottom:20px;">
              <tr><td style="padding:16px 18px;">
                <p style="margin:0 0 4px;font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.5px;">Your Login Email</p>
                <p style="margin:0 0 12px;font-size:14px;color:#111827;">' . esc_html($email) . '</p>
                <p style="margin:0;font-size:12px;color:#6b7280;">Sign in at any time using the button below.</p>
              </td></tr>
            </table>';
        require_once WPI_PLUGIN_DIR . 'includes/class-scheduler.php';
        WPI_Scheduler::send_branded_email(
            $email,
            'Welcome to ' . $site . ' — Your account is ready',
            $body_html,
            '#1a3a5c',
            '👋 WELCOME',
            $login_url,
            'Sign In to ' . $site . ' →'
        );

        // Self-registered users always start with basic access.
        // They must purchase a subscription to unlock full access.
        update_user_meta( $uid, 'wpi_access_basic', 1 );

        // Set WPI role to basic
        $wpdb->replace( $wpdb->prefix . 'wpi_user_roles', array(
            'user_id' => $uid,
            'role'    => 'basic',
            'set_by'  => 0,
        ) );

        wp_send_json_success( array(
            'user_id' => $uid,
        ) );
    }

    public function wpi_activate_user_token() {
        $this->check_nonce();
        global $wpdb;

        // Rate limit: max 10 token activation attempts per user per hour
        $uid_rl    = get_current_user_id();
        $rl_key    = 'wpi_tok_act_' . $uid_rl;
        $rl_count  = (int) get_transient( $rl_key );
        if ( $rl_count >= 10 ) {
            $this->error( 'Too many activation attempts. Please try again later.', 429 );
        }
        set_transient( $rl_key, $rl_count + 1, HOUR_IN_SECONDS );

        require_once WPI_PLUGIN_DIR . 'includes/class-access.php';
        $body    = $this->input();
        $token   = strtoupper( sanitize_text_field( $body['token'] ?? '' ) );
        $user_id = absint( $body['user_id'] ?? get_current_user_id() );

        // Users can only activate for themselves; system owner can activate for anyone
        if ( ! $this->is_system_owner() && $user_id !== get_current_user_id() ) {
            $this->error( 'Access denied', 403 );
        }

        if ( ! $token ) $this->error( 'Token required' );

        // Check token exists first for friendly error messages
        $lic = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wpi_licences WHERE token=%s", $token
        ) );
        if ( ! $lic ) {
            $this->json( array( 'success' => false, 'error' => 'Unable to activate this token. Please verify the token is correct, has not been used, or contact your administrator for help.' ) );
            return;
        }
        if ( $lic->status !== 'unassigned' ) {
            $this->json( array( 'success' => false, 'error' => 'Unable to activate this token. Please verify the token is correct, has not been used, or contact your administrator for help.' ) );
            return;
        }

        $ok = WPI_Access::assign_to_user( $lic->id, $user_id );
        if ( $ok ) {
            // Clear basic access flag and upgrade to full administrator role
            delete_user_meta( $user_id, 'wpi_access_basic' );
            $wpdb->replace( $wpdb->prefix . 'wpi_user_roles', array(
                'user_id' => $user_id,
                'role'    => 'administrator',
                'set_by'  => 0,
            ) );
            // Create personal org if user has no org
            $has_org = $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}wpi_org_users WHERE user_id=%d", $user_id
            ) );
            if ( ! $has_org ) {
                $u      = get_userdata( $user_id );
                $fname  = get_user_meta( $user_id, 'first_name', true ) ?: '';
                $lname  = get_user_meta( $user_id, 'last_name',  true ) ?: '';
                $email  = $u ? $u->user_email : '';
                $org_id = $this->create_personal_org( $user_id, $fname, $lname, $email );
                if ( $org_id ) {
                    $wpdb->update( $wpdb->prefix . 'wpi_licences',
                        array( 'org_id' => $org_id ),
                        array( 'id' => $lic->id )
                    );
                }
            }
            $this->json( array( 'success' => true ) );
        } else {
            $this->json( array( 'success' => false, 'error' => 'Failed to activate token. Please try again.' ) );
        }
    }

    public function wpi_assign_user_licence() {
        $this->check_nonce();
        if ( ! $this->is_system_owner() ) $this->error( 'Access denied', 403 );
        require_once WPI_PLUGIN_DIR . 'includes/class-access.php';
        $body       = $this->input();
        $licence_id = absint( $body['licence_id'] ?? 0 );
        $user_id    = absint( $body['user_id'] ?? 0 );
        if ( ! $licence_id || ! $user_id ) $this->error( 'licence_id and user_id required' );
        $ok = WPI_Access::assign_to_user( $licence_id, $user_id );
        if ( ! $ok ) $this->error( 'Token already assigned or not found' );
        $this->json( array( 'success' => true ) );
    }

    public function wpi_revoke_user_licence() {
        $this->check_nonce();
        if ( ! $this->is_system_owner() ) $this->error( 'Access denied', 403 );
        global $wpdb;
        require_once WPI_PLUGIN_DIR . 'includes/class-access.php';
        $body    = $this->input();
        $user_id = absint( $body['user_id'] ?? 0 );
        if ( ! $user_id ) $this->error( 'user_id required' );
        $lic = WPI_Access::get_user_direct_licence( $user_id );
        if ( ! $lic ) $this->error( 'No direct licence found' );
        $wpdb->update( $wpdb->prefix . 'wpi_licences', array(
            'status'  => 'revoked',
            'user_id' => 0,
            'assigned_to' => 'none',
        ), array( 'id' => $lic->id ) );
        update_user_meta( $user_id, 'wpi_access_basic', 1 );
        WPI_Access::log( $lic->id, 0, 'revoked', 'User direct licence revoked from user ID ' . $user_id );
        $this->json( array( 'success' => true ) );
    }

    public function wpi_get_org_licence() {
        $this->check_nonce();
        global $wpdb;
        $org_id = absint( $_GET['org_id'] ?? $this->get_org_id() );
        require_once WPI_PLUGIN_DIR . 'includes/class-access.php';
        $lic = WPI_Access::get_org_licence( $org_id );
        // Merge token details into org licence response
        if ( $lic ) {
            $token_data = (object) array(
                'token'        => $lic->token,
                'token_status' => $lic->status,
                'token_type'   => $lic->licence_type,
                'token_expiry' => $lic->expiry_date,
            );
            $this->json( (object) array_merge( (array) $token_data, (array) $lic ) );
        } else {
            // Fall back to legacy org licence data
            $org = $wpdb->get_row( $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}wpi_organisations WHERE id=%d", $org_id
            ) );
            $this->json( $org ?: (object) array() );
        }
    }

    public function wpi_get_orgs() {
        $this->check_nonce();
        if ( ! $this->is_system_owner() ) $this->error('Access denied', 403);
        global $wpdb;
        wpi_ensure_org_licence_columns();
        $orgs = $wpdb->get_results(
            "SELECT o.*,
                    COUNT(DISTINCT ou.user_id) as user_count,
                    COUNT(DISTINCT t.id) as template_count,
                    COUNT(DISTINCT i.id) as inspection_count,
                    COALESCE(NULLIF(TRIM(CONCAT(COALESCE(um1.meta_value,''),' ',COALESCE(um2.meta_value,''))), ''), u.display_name) as owner_name
             FROM {$wpdb->prefix}wpi_organisations o
             LEFT JOIN {$wpdb->prefix}wpi_org_users ou ON ou.org_id=o.id
             LEFT JOIN {$wpdb->prefix}wpi_templates t ON t.org_id=o.id AND t.status='active'
             LEFT JOIN {$wpdb->prefix}wpi_inspections i ON i.org_id=o.id
             LEFT JOIN {$wpdb->users} u ON u.ID=o.owner_id
             LEFT JOIN {$wpdb->usermeta} um1 ON um1.user_id=o.owner_id AND um1.meta_key='first_name'
             LEFT JOIN {$wpdb->usermeta} um2 ON um2.user_id=o.owner_id AND um2.meta_key='last_name'
             GROUP BY o.id ORDER BY o.name ASC"
        );
        $orgs = $orgs ?: array();
        foreach ( $orgs as $org ) {
            $lic = self::get_org_licence( $org->id );
            $org->licence_status    = $lic['status'];
            $org->licence_days_left = $lic['days_remaining'];
            $org->licence_expires   = $lic['expires'] ?? null;
            if ( empty($org->licence_type) ) $org->licence_type = $lic['type'] ?? 'lifetime';
        }
        $this->json( $orgs );
    }

    public function wpi_create_org() {
        $this->check_nonce();
        if ( ! $this->is_system_owner() ) $this->error('Access denied', 403);
        global $wpdb;
        wpi_ensure_org_licence_columns();
        $body = $this->input();
        $name = sanitize_text_field( $body['name'] ?? '' );
        if ( ! $name ) $this->error('Organisation name required');
        $wpdb->insert( $wpdb->prefix.'wpi_organisations', array(
            'name'         => $name,
            'description'  => sanitize_text_field($body['description'] ?? ''),
            'owner_id'     => absint($body['owner_id'] ?? 0),
            'licence_type' => 'lifetime',
            'status'       => 'active',
            'created_at'   => current_time('mysql'),
        ));
        $id = $wpdb->insert_id;
        $this->json( array('success'=>true,'id'=>$id,'name'=>$name,'licence_type'=>'lifetime','status'=>'active') );
    }

    public function wpi_update_org() {
        $this->check_nonce();
        if ( ! $this->is_system_owner() ) $this->error('Access denied', 403);
        global $wpdb;
        $body = $this->input();
        $id   = absint($body['id'] ?? 0);
        if ( ! $id ) $this->error('id required');
        $wpdb->update( $wpdb->prefix.'wpi_organisations', array(
            'name'        => sanitize_text_field($body['name'] ?? ''),
            'description' => sanitize_text_field($body['description'] ?? ''),
            'owner_id'    => absint($body['owner_id'] ?? 0) ?: null,
        ), array('id'=>$id) );
        $this->json( array('success'=>true) );
    }

    public function wpi_delete_org() {
        $this->check_nonce();
        if ( ! $this->is_system_owner() ) $this->error('Access denied', 403);
        global $wpdb;
        $body = $this->input();
        $id   = absint($body['id'] ?? 0);
        if ( ! $id ) $this->error('id required');
        $wpdb->delete( $wpdb->prefix.'wpi_organisations', array('id'=>$id) );
        $wpdb->delete( $wpdb->prefix.'wpi_org_users', array('org_id'=>$id) );
        $this->json( array('success'=>true) );
    }

    public function wpi_get_org_users() {
        $this->check_nonce();
        global $wpdb;
        $org_id = absint( $_GET['org_id'] ?? $this->get_org_id() );
        if ( ! $org_id ) $this->error('org_id required');
        // System owner can query any org. All others may only query their own org.
        if ( ! $this->is_system_owner() ) {
            $my_org = $this->get_org_id();
            if ( (int)$my_org !== (int)$org_id ) $this->error('Access denied', 403);
        }
        // Get org owner_id so we can flag them in the list
        $org_owner_id = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT owner_id FROM {$wpdb->prefix}wpi_organisations WHERE id=%d", $org_id
        ) );

        $users = $wpdb->get_results( $wpdb->prepare(
            "SELECT ou.user_id, ou.role, u.user_email,
                    COALESCE(NULLIF(TRIM(CONCAT(COALESCE(um1.meta_value,''),' ',COALESCE(um2.meta_value,''))), ''), u.display_name) as display_name
             FROM {$wpdb->prefix}wpi_org_users ou
             JOIN {$wpdb->users} u ON u.ID=ou.user_id
             LEFT JOIN {$wpdb->usermeta} um1 ON um1.user_id=ou.user_id AND um1.meta_key='first_name'
             LEFT JOIN {$wpdb->usermeta} um2 ON um2.user_id=ou.user_id AND um2.meta_key='last_name'
             WHERE ou.org_id=%d ORDER BY display_name ASC", $org_id
        ));
        $out = array();
        foreach ( ($users ?: array()) as $u ) {
            $u->full_name   = $u->display_name;
            $u->deactivated  = (bool) get_user_meta( $u->user_id, 'wpi_deactivated', true );
            $u->is_owner     = ( (int) $u->user_id === $org_owner_id );
            $out[] = $u;
        }
        $this->json( $out );
    }


    // ═══════════════════════════════════════════════════════════════
    // ── Organisation Invitation Handlers ───────────────────────────
    // ═══════════════════════════════════════════════════════════════

    /** Send an email invitation to join the org. */
    public function wpi_send_invitation() {
        $this->check_nonce();
        if ( ! $this->can('administrator') ) $this->error('Access denied', 403);
        $this->ensure_invitations_table();
        global $wpdb;

        $body    = $this->input();
        $email   = sanitize_email( $body['email'] ?? '' );
        $role    = sanitize_text_field( $body['role'] ?? 'standard' );
        $message = sanitize_textarea_field( $body['message'] ?? '' );
        $org_id  = $this->get_org_id();

        if ( ! $email )  $this->error( 'Email is required' );
        if ( ! $org_id ) $this->error( 'No organisation found' );

        // Check not already a member
        $already = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}wpi_org_users ou
             INNER JOIN {$wpdb->users} u ON u.ID=ou.user_id
             WHERE ou.org_id=%d AND u.user_email=%s",
            $org_id, $email
        ) );
        if ( $already ) $this->error( 'This user is already a member of your organisation' );

        // Check for existing pending invite
        $pending = $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}wpi_invitations
             WHERE org_id=%d AND email=%s AND status='pending' AND expires_at > NOW()",
            $org_id, $email
        ) );
        if ( $pending ) $this->error( 'A pending invitation already exists for this email' );

        // Create token
        $token      = bin2hex( random_bytes(32) );
        $expires_at = date('Y-m-d H:i:s', strtotime('+7 days') );
        $uid        = get_current_user_id();
        $inviter    = get_userdata( $uid );
        $org        = $wpdb->get_row( $wpdb->prepare(
            "SELECT name FROM {$wpdb->prefix}wpi_organisations WHERE id=%d", $org_id
        ) );

        $wpdb->insert( $wpdb->prefix . 'wpi_invitations', array(
            'org_id'     => $org_id,
            'invited_by' => $uid,
            'email'      => $email,
            'role'       => $role,
            'token'      => $token,
            'status'     => 'pending',
            'message'    => $message,
            'expires_at' => $expires_at,
            'created_at' => current_time('mysql'),
        ) );

        // Send invitation email via Mailjet
        $accept_url  = home_url( '/?wpi=1&wpi_invite=' . $token );
        $org_name    = $org ? $org->name : 'the organisation';
        $inviter_name = $inviter ? ($inviter->display_name ?: $inviter->user_email) : 'Your administrator';

        $site_name  = get_bloginfo('name') ?: 'Audit4me';
        $today      = date('j M Y');
        $role_label = ucfirst($role);

        $email_html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>'
            . '<body style="margin:0;padding:0;background:#f3f4f6;font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Helvetica,Arial,sans-serif;">'
            . '<table width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f6;padding:32px 16px;">'
            . '<tr><td align="center">'
            . '<table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 2px 16px rgba(0,0,0,.08);">'

            . '<tr><td style="background:#1a3a5c;padding:28px 32px;">'
            . '<h1 style="margin:0;font-size:22px;font-weight:800;color:#ffffff;line-height:1.2;">You have been invited!</h1>'
            . '<p style="margin:8px 0 0;font-size:13px;color:#ffffff;opacity:.8;">' . esc_html($site_name) . ' &middot; ' . esc_html($today) . '</p>'
            . '</td></tr>'

            . '<tr><td style="padding:20px 32px 0;">'
            . '<span style="display:inline-block;background:#2563eb22;color:#2563eb;border:1.5px solid #2563eb;border-radius:20px;padding:4px 14px;font-size:12px;font-weight:700;">&#x2709;&#xFE0F; ORGANISATION INVITATION</span>'
            . '</td></tr>'

            . '<tr><td style="padding:20px 32px 0;">'
            . '<table width="100%" cellpadding="0" cellspacing="0" style="background:#f8faff;border:1.5px solid #e0e7ff;border-radius:10px;">'
            . '<tr><td style="padding:16px 20px;">'
            . '<p style="margin:0 0 4px;font-size:11px;font-weight:700;color:#6366f1;text-transform:uppercase;letter-spacing:.5px;">Organisation</p>'
            . '<p style="margin:0 0 12px;font-size:17px;font-weight:700;color:#111827;">' . esc_html($org_name) . '</p>'
            . '<p style="margin:0 0 4px;font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.5px;">Invited by</p>'
            . '<p style="margin:0 0 12px;font-size:14px;color:#374151;">' . esc_html($inviter_name) . '</p>'
            . '<p style="margin:0 0 4px;font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.5px;">Your Role</p>'
            . '<p style="margin:0;font-size:14px;color:#374151;">'
            . '<span style="display:inline-block;background:#d1fae5;color:#065f46;border-radius:6px;padding:2px 10px;font-size:12px;font-weight:700;">' . esc_html(strtoupper($role)) . '</span>'
            . '</p>'
            . '</td></tr>'
            . '</table>'
            . '</td></tr>'

            . ( $message ? '<tr><td style="padding:16px 32px 0;">'
            . '<table width="100%" cellpadding="0" cellspacing="0" style="background:#fffbeb;border:1px solid #fde68a;border-radius:10px;">'
            . '<tr><td style="padding:14px 18px;">'
            . '<p style="margin:0 0 4px;font-size:11px;font-weight:700;color:#92400e;text-transform:uppercase;">Personal Message</p>'
            . '<p style="margin:0;font-size:14px;color:#374151;font-style:italic;">&ldquo;' . esc_html($message) . '&rdquo;</p>'
            . '</td></tr></table>'
            . '</td></tr>' : '' )

            . '<tr><td style="padding:24px 32px 8px;">'
            . '<p style="margin:0;font-size:14px;color:#374151;line-height:1.6;">Tap the button below to accept this invitation and join ' . esc_html($org_name) . ' on Audit4me. Your existing data and subscription are not affected.</p>'
            . '</td></tr>'

            . '<tr><td style="padding:8px 32px 32px;text-align:center;">'
            . '<a href="' . esc_url($accept_url) . '" style="display:inline-block;background:#2563eb;color:#ffffff;text-decoration:none;padding:14px 36px;border-radius:10px;font-size:16px;font-weight:700;">Accept Invitation &rarr;</a>'
            . '<p style="margin:12px 0 0;font-size:12px;color:#9ca3af;">This invitation expires in 7 days.</p>'
            . '</td></tr>'

            . '<tr><td style="background:#f9fafb;padding:16px 32px;border-top:1px solid #e5e7eb;text-align:center;">'
            . '<p style="margin:0;font-size:11px;color:#9ca3af;">If you did not expect this invitation, you can safely ignore this email. This notification was sent from <a href="' . esc_url(home_url()) . '" style="color:#6b7280;">' . esc_html($site_name) . '</a>.</p>'
            . '</td></tr>'

            . '</table></td></tr></table>'
            . '</body></html>';

        $subject = 'Invitation to join ' . $org_name . ' on Audit4me';
        $settings   = get_option( 'wpi_api_settings', array() );
        $api_key    = $settings['mailjet_api_key']    ?? '';
        $api_secret = $settings['mailjet_api_secret'] ?? '';
        $from_email = $settings['from_email'] ?? get_option('admin_email');
        $from_name  = $settings['from_name']  ?? $site_name;

        if ( $api_key && $api_secret ) {
            wp_remote_post( 'https://api.mailjet.com/v3.1/send', array(
                'headers' => array(
                    'Authorization' => 'Basic ' . base64_encode( "$api_key:$api_secret" ),
                    'Content-Type'  => 'application/json',
                ),
                'body'    => wp_json_encode( array(
                    'Messages' => array( array(
                        'From'     => array( 'Email' => $from_email, 'Name' => $from_name ),
                        'To'       => array( array( 'Email' => $email ) ),
                        'Subject'  => $subject,
                        'HTMLPart' => $email_html,
                    ) ),
                ) ),
                'timeout' => 15,
            ) );
        } else {
            $headers = array(
                'Content-Type: text/html; charset=UTF-8',
                'From: ' . $from_name . ' <' . $from_email . '>',
            );
            wp_mail( $email, $subject, $email_html, $headers );
        }

        $this->json( array( 'success' => true, 'token' => $token ) );
    }

    /** Ensure wpi_invitations table exists (safe to call on every request). */
    private function ensure_invitations_table() {
        global $wpdb;
        $table = $wpdb->prefix . 'wpi_invitations';
        if ( $wpdb->get_var( "SHOW TABLES LIKE '$table'" ) === $table ) return;
        $charset = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE IF NOT EXISTS {$table} (
            id          bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            org_id      bigint(20) unsigned NOT NULL,
            invited_by  bigint(20) unsigned NOT NULL,
            email       varchar(200) NOT NULL,
            role        varchar(50) NOT NULL DEFAULT 'standard',
            token       varchar(80) NOT NULL,
            status      varchar(20) NOT NULL DEFAULT 'pending',
            message     text,
            accepted_at datetime DEFAULT NULL,
            expires_at  datetime NOT NULL,
            created_at  datetime NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY token (token),
            KEY org_email (org_id, email),
            KEY org_status (org_id, status)
        ) $charset;";
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    /** Get pending invitations for the admin's org. */
    public function wpi_get_invitations() {
        $this->check_nonce();
        if ( ! $this->can('administrator') ) $this->error('Access denied', 403);
        global $wpdb;

        $org_id = $this->get_org_id();
        $rows   = $wpdb->get_results( $wpdb->prepare(
            "SELECT i.*, u.display_name as inviter_name
             FROM {$wpdb->prefix}wpi_invitations i
             LEFT JOIN {$wpdb->users} u ON u.ID = i.invited_by
             WHERE i.org_id = %d
             ORDER BY i.created_at DESC
             LIMIT 50",
            $org_id
        ) );
        $this->json( $rows ?: array() );
    }

    /** Cancel a pending invitation. */
    public function wpi_cancel_invitation() {
        $this->check_nonce();
        if ( ! $this->can('administrator') ) $this->error('Access denied', 403);
        global $wpdb;

        $id     = absint( ($this->input())['id'] ?? 0 );
        $org_id = $this->get_org_id();
        // Only cancel own org's invitations
        $wpdb->update(
            $wpdb->prefix . 'wpi_invitations',
            array( 'status' => 'cancelled' ),
            array( 'id' => $id, 'org_id' => $org_id )
        );
        $this->json( array( 'success' => true ) );
    }

    /** Get invitation details by token (public — no auth needed). */
    /**
     * AJAX login handler - processes credentials via admin-ajax.php
     * completely bypassing wp-login.php (blocked by corporate proxies like Zscaler).
     */
    public function wpi_do_login() {
        // Verify nonce (created on login page, does not require logged-in user)
        $nonce = sanitize_text_field( $_POST['nonce'] ?? '' );
        if ( ! $nonce || ! wp_verify_nonce( $nonce, 'wpi_login' ) ) {
            wp_send_json_error( array( 'message' => 'Security check failed. Please refresh the page and try again.' ), 403 );
            return;
        }
        // Rate limit: max 10 attempts per IP per 5 minutes
        $ip       = sanitize_text_field( $_SERVER['REMOTE_ADDR'] ?? '' );
        $rate_key = 'wpi_login_attempts_' . md5( $ip );
        $attempts = (int) get_transient( $rate_key );
        if ( $attempts >= 10 ) {
            wp_send_json_error( array( 'message' => 'Too many login attempts. Please wait a few minutes and try again.' ), 429 );
            return;
        }
        set_transient( $rate_key, $attempts + 1, 5 * MINUTE_IN_SECONDS );

        $log  = sanitize_text_field( wp_unslash( $_POST['wpi_u']  ?? '' ) );
        $pwd  = wp_unslash( $_POST['wpi_k']  ?? '' );
        $remember = ! empty( $_POST['rememberme'] );

        if ( ! $log || ! $pwd ) {
            wp_send_json_error( array( 'message' => 'Please enter your username and password.' ), 400 );
            return;
        }

        // Resolve username from email if needed
        $username = $log;
        if ( is_email( $log ) ) {
            $user_by_email = get_user_by( 'email', $log );
            if ( $user_by_email ) $username = $user_by_email->user_login;
        }

        $creds = array(
            'user_login'    => $username,
            'user_password' => $pwd,
            'remember'      => $remember,
        );

        $user = wp_signon( $creds, is_ssl() );

        if ( is_wp_error( $user ) ) {
            wp_send_json_error( array( 'message' => 'Incorrect username or password.' ), 401 );
            return;
        }

        // Clear rate limit on success
        delete_transient( $rate_key );

        wp_send_json_success( array(
            'redirect' => home_url( '/?wpi=1' ),
            'user_id'  => $user->ID,
        ) );
    }

    /** Check if the logged-in user has any pending invitations waiting for their email. */
    public function wpi_check_my_invitations() {
        $this->check_nonce();
        $uid = get_current_user_id();
        if ( ! $uid ) $this->json( array( 'pending' => array() ) );
        $user = get_userdata( $uid );
        if ( ! $user ) $this->json( array( 'pending' => array() ) );
        global $wpdb;
        $this->ensure_invitations_table();
        $rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT i.id, i.token, i.role, i.org_id, i.expires_at,
                    o.name AS org_name, u.display_name AS inviter_name
             FROM {$wpdb->prefix}wpi_invitations i
             LEFT JOIN {$wpdb->prefix}wpi_organisations o ON o.id = i.org_id
             LEFT JOIN {$wpdb->users} u ON u.ID = i.invited_by
             WHERE i.email = %s AND i.status = 'pending' AND i.expires_at > NOW()",
            $user->user_email
        ) );
        $this->json( array( 'pending' => $rows ?: array() ) );
    }

    public function wpi_get_pending_invite() {
        $this->check_nonce();
        $token = sanitize_text_field( $_GET['token'] ?? '' );
        if ( ! $token ) { wp_send_json_error( 'token required', 400 ); return; }
        global $wpdb;

        $inv = $wpdb->get_row( $wpdb->prepare(
            "SELECT i.*, o.name as org_name, u.display_name as inviter_name
             FROM {$wpdb->prefix}wpi_invitations i
             LEFT JOIN {$wpdb->prefix}wpi_organisations o ON o.id=i.org_id
             LEFT JOIN {$wpdb->users} u ON u.ID=i.invited_by
             WHERE i.token=%s AND i.status='pending' AND i.expires_at > NOW()",
            $token
        ) );

        if ( ! $inv ) { wp_send_json_error( 'Invitation not found or expired', 404 ); return; }

        // Include current user's org name (if logged in) so frontend can show
        // the 'you will leave X' warning before they accept.
        $current_org_name = '';
        if ( is_user_logged_in() ) {
            $uid = get_current_user_id();
            $cur_org_id = WPI_Admin::get_user_org_id( $uid );
            if ( $cur_org_id && (int)$cur_org_id !== (int)$inv->org_id ) {
                $cur_org = $wpdb->get_var( $wpdb->prepare(
                    "SELECT name FROM {$wpdb->prefix}wpi_organisations WHERE id=%d",
                    $cur_org_id
                ) );
                $current_org_name = $cur_org ?: '';
            }
        }

        wp_send_json_success( array(
            'org_name'         => $inv->org_name,
            'inviter_name'     => $inv->inviter_name,
            'role'             => $inv->role,
            'email'            => $inv->email,
            'message'          => $inv->message,
            'current_org_name' => $current_org_name,
            'logout_url'       => wp_logout_url( home_url('/?wpi=1&wpi_invite=' . rawurlencode($inv->token)) ),
        ) );
    }

    /** Accept an invitation — join the org. */
    public function wpi_accept_invitation() {
        $this->check_nonce();
        global $wpdb;

        $body  = $this->input();
        $token = sanitize_text_field( $body['token'] ?? '' );
        if ( ! $token ) $this->error( 'Token required' );

        $uid = get_current_user_id();
        if ( ! $uid ) $this->error( 'You must be logged in to accept an invitation', 401 );

        $inv = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wpi_invitations
             WHERE token=%s AND status='pending' AND expires_at > NOW()",
            $token
        ) );
        if ( ! $inv ) $this->error( 'Invitation not found or has expired' );

        // Verify the logged-in user's email matches
        $user = get_userdata( $uid );
        if ( strtolower($user->user_email) !== strtolower($inv->email) ) {
            $this->error( 'This invitation was not sent to your account. Please log in with the correct account to accept.' );
        }

        $org_id      = (int) $inv->org_id;
        $current_org = $this->get_org_id();

        // Save their current personal org for potential return
        if ( $current_org && $current_org !== $org_id ) {
            update_user_meta( $uid, 'wpi_personal_org_id', $current_org );
        }

        // Remove from old org (if different)
        if ( $current_org && $current_org !== $org_id ) {
            $wpdb->delete( $wpdb->prefix.'wpi_org_users', array('org_id'=>$current_org,'user_id'=>$uid) );

            // PRIVACY: Detach user's templates from old org (set org_id=0).
            // Old-org admins can no longer see them. New org cannot see them.
            // All template data, questions, responses, and inspections are preserved.
            $wpdb->query( $wpdb->prepare(
                "UPDATE {$wpdb->prefix}wpi_templates
                 SET org_id = 0
                 WHERE created_by = %d AND org_id = %d",
                $uid, $current_org
            ) );

            // PRIVACY: Tag old org as removed so old-org inspections are
            // hidden from the Inspections list. Data is fully preserved.
            $removed_orgs = get_user_meta( $uid, 'wpi_removed_orgs', true );
            if ( ! is_array( $removed_orgs ) ) $removed_orgs = array();
            if ( ! in_array( (int)$current_org, $removed_orgs, true ) ) {
                $removed_orgs[] = (int) $current_org;
                update_user_meta( $uid, 'wpi_removed_orgs', $removed_orgs );
            }
        }

        // Join the new org
        $wpdb->replace( $wpdb->prefix.'wpi_org_users', array(
            'org_id'  => $org_id,
            'user_id' => $uid,
            'role'    => $inv->role,
        ) );

        // Set WPI role
        $wpdb->query( $wpdb->prepare(
            "INSERT INTO {$wpdb->prefix}wpi_user_roles (user_id, role, set_by)
             VALUES (%d, %s, %d)
             ON DUPLICATE KEY UPDATE role=%s, set_by=%d",
            $uid, $inv->role, (int)$inv->invited_by,
            $inv->role, (int)$inv->invited_by
        ) );

        // Clear basic access
        delete_user_meta( $uid, 'wpi_access_basic' );

        // Mark invitation accepted
        $wpdb->update( $wpdb->prefix.'wpi_invitations',
            array( 'status' => 'accepted', 'accepted_at' => current_time('mysql') ),
            array( 'id' => $inv->id )
        );

        $this->json( array( 'success' => true, 'org_id' => $org_id ) );
    }

    /** Leave the current organisation — return to personal org. */
    public function wpi_leave_org() {
        $this->check_nonce();
        global $wpdb;

        $uid    = get_current_user_id();
        $org_id = $this->get_org_id();
        if ( ! $org_id ) $this->error( 'You are not in an organisation' );

        // Prevent the last admin from leaving
        $admin_count = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}wpi_org_users ou
             INNER JOIN {$wpdb->prefix}wpi_user_roles ur ON ur.user_id=ou.user_id
             WHERE ou.org_id=%d AND ur.role IN ('administrator','admin')",
            $org_id
        ) );
        $my_role = $wpdb->get_var( $wpdb->prepare(
            "SELECT role FROM {$wpdb->prefix}wpi_user_roles WHERE user_id=%d", $uid
        ) );
        if ( in_array($my_role, array('administrator','admin'), true) && $admin_count <= 1 ) {
            $this->error( 'You are the only administrator. Transfer admin rights before leaving.' );
        }

        // Remove from current org
        $wpdb->delete( $wpdb->prefix.'wpi_org_users', array('org_id'=>$org_id,'user_id'=>$uid) );

        // Restore personal org if saved, or create a new one
        $personal_org_id = (int) get_user_meta( $uid, 'wpi_personal_org_id', true );
        if ( $personal_org_id ) {
            $wpdb->replace( $wpdb->prefix.'wpi_org_users', array(
                'org_id'  => $personal_org_id,
                'user_id' => $uid,
                'role'    => 'admin',
            ) );
            delete_user_meta( $uid, 'wpi_personal_org_id' );
        } else {
            // Create a new personal org
            $user = get_userdata( $uid );
            $this->create_personal_org( $uid, $user->first_name ?: $user->display_name, $user->last_name ?? '', $user->user_email );
        }

        // Restore administrator role
        $wpdb->query( $wpdb->prepare(
            "INSERT INTO {$wpdb->prefix}wpi_user_roles (user_id, role, set_by)
             VALUES (%d, 'administrator', 0)
             ON DUPLICATE KEY UPDATE role='administrator'",
            $uid
        ) );

        $this->json( array( 'success' => true ) );
    }

    /** Helper: send email via Mailjet */
    private function send_mailjet_email( $to, $subject, $html_body ) {
        $settings = get_option( 'wpi_api_settings', array() );
        $api_key  = $settings['mailjet_api_key'] ?? '';
        $api_sec  = $settings['mailjet_api_secret'] ?? '';
        $from_email = $settings['from_email'] ?? get_option('admin_email');
        $from_name  = $settings['from_name']  ?? get_bloginfo('name');

        if ( $api_key && $api_sec ) {
            wp_remote_post( 'https://api.mailjet.com/v3.1/send', array(
                'headers' => array(
                    'Authorization' => 'Basic ' . base64_encode("$api_key:$api_sec"),
                    'Content-Type'  => 'application/json',
                ),
                'body' => wp_json_encode( array(
                    'Messages' => array( array(
                        'From'     => array( 'Email' => $from_email, 'Name' => $from_name ),
                        'To'       => array( array( 'Email' => $to ) ),
                        'Subject'  => $subject,
                        'HTMLPart' => $html_body,
                    ) )
                ) ),
                'timeout' => 15,
            ) );
        } else {
            // Fallback to wp_mail
            wp_mail( $to, $subject, strip_tags($html_body), array('Content-Type: text/html; charset=UTF-8') );
        }
    }

    public function wpi_add_org_user() {
        $this->check_nonce();
        if ( ! $this->can('administrator') ) $this->error('Access denied', 403);
        $this->check_billing_limit('create_user');
        global $wpdb;
        $body    = $this->input();
        $org_id  = absint($body['org_id'] ?? 0);
        $user_id = absint($body['user_id'] ?? 0);
        $role    = sanitize_text_field($body['role'] ?? 'member');
        if ( ! $org_id || ! $user_id ) $this->error('org_id and user_id required');
        $wpdb->replace( $wpdb->prefix.'wpi_org_users', array('org_id'=>$org_id,'user_id'=>$user_id,'role'=>$role) );
        // Clear removed_orgs entry so user regains access to their inspections
        $removed_orgs = get_user_meta( $user_id, 'wpi_removed_orgs', true );
        if ( is_array($removed_orgs) ) {
            $removed_orgs = array_values( array_filter( $removed_orgs, function($oid) use($org_id){ return (int)$oid !== (int)$org_id; } ) );
            update_user_meta( $user_id, 'wpi_removed_orgs', $removed_orgs );
        }
        $this->json( array('success'=>true) );
    }

    public function wpi_remove_org_user() {
        $this->check_nonce();
        if ( ! $this->can('administrator') ) $this->error('Access denied', 403);
        global $wpdb;
        $body    = $this->input();
        $org_id  = absint($body['org_id'] ?? 0);
        $user_id = absint($body['user_id'] ?? 0);
        if ( ! $org_id || ! $user_id ) $this->error('org_id and user_id required');

        // 1. Remove from org
        $wpdb->delete( $wpdb->prefix.'wpi_org_users', array('org_id'=>$org_id,'user_id'=>$user_id) );

        // 2. Remove from all teams in this org
        $team_ids = $wpdb->get_col( $wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}wpi_teams WHERE org_id=%d", $org_id
        ) );
        if ( !empty($team_ids) ) {
            $in = implode(',', array_map('intval', $team_ids));
            $wpdb->query( $wpdb->prepare(
                "DELETE FROM {$wpdb->prefix}wpi_team_members WHERE user_id=%d AND team_id IN ($in)", $user_id
            ) );
        }

        // 3. Revoke all template shares for this user in this org
        $wpdb->query( $wpdb->prepare(
            "DELETE s FROM {$wpdb->prefix}wpi_template_shares s
             JOIN {$wpdb->prefix}wpi_templates t ON t.id = s.template_id
             WHERE s.shared_with_type='user' AND s.shared_with_id=%d AND t.org_id=%d",
            $user_id, $org_id
        ) );

        // 4. Clear user's personal archived list of org templates
        $org_template_ids = $wpdb->get_col( $wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}wpi_templates WHERE org_id=%d AND status != 'deleted'", $org_id
        ) );
        if ( !empty($org_template_ids) ) {
            $hidden = get_user_meta( $user_id, 'wpi_archived_templates', true );
            if ( !is_array($hidden) ) $hidden = array();
            $hidden = array_values( array_diff( $hidden, $org_template_ids ) );
            update_user_meta( $user_id, 'wpi_archived_templates', $hidden );
        }

        // 5. Store removed org on user meta — inspections hidden but data preserved
        $removed_orgs = get_user_meta( $user_id, 'wpi_removed_orgs', true );
        if ( !is_array($removed_orgs) ) $removed_orgs = array();
        $removed_orgs[] = $org_id;
        $removed_orgs   = array_unique( array_map('intval', $removed_orgs) );
        update_user_meta( $user_id, 'wpi_removed_orgs', $removed_orgs );

        // 6. Destroy active sessions immediately
        $sessions = WP_Session_Tokens::get_instance( $user_id );
        $sessions->destroy_all();

        $this->json( array('success'=>true) );
    }

    public function wpi_create_org_user() {
        $this->check_nonce();
        // System owner can create for any org; org admins can create for their own org only
        if ( ! $this->is_system_owner() && ! $this->can('administrator') ) {
            $this->error('Access denied', 403);
        }
        $body     = $this->input();
        $email    = sanitize_email($body['email'] ?? '');
        $username = sanitize_user($body['username'] ?? '');
        $password = $body['password'] ?? wp_generate_password();
        $org_id   = absint($body['org_id'] ?? 0);
        $role     = sanitize_text_field($body['role'] ?? 'member');

        // Non-system-owners can only create users for their own org
        if ( ! $this->is_system_owner() ) {
            $my_org = $this->get_org_id();
            if ( ! $my_org ) $this->error('Access denied — no organisation assigned', 403);
            $org_id = $my_org; // force to their own org, ignore any passed org_id
        }

        if ( ! $email ) $this->error('Email required');
        if ( email_exists($email) ) $this->error('Email already in use');
        $uid = wp_create_user( $username ?: sanitize_user(explode('@',$email)[0]), $password, $email );
        if ( is_wp_error($uid) ) $this->error( $uid->get_error_message() );
        if ( isset($body['first_name']) ) update_user_meta($uid,'first_name',sanitize_text_field($body['first_name']));
        if ( isset($body['last_name'])  ) update_user_meta($uid,'last_name', sanitize_text_field($body['last_name']));
        global $wpdb;
        if ( $org_id ) $wpdb->replace( $wpdb->prefix.'wpi_org_users', array('org_id'=>$org_id,'user_id'=>$uid,'role'=>$role) );
        $wpdb->replace( $wpdb->prefix.'wpi_user_roles', array('user_id'=>$uid,'role'=>'standard','set_by'=>get_current_user_id()) );

        // Send branded welcome email with set-password link
        $first_name  = sanitize_text_field($body['first_name'] ?? '');
        $fname       = $first_name ?: explode('@', $email)[0];
        $site        = get_bloginfo('name') ?: get_option('blogname') ?: 'Audit4me';
        $creator     = wp_get_current_user();
        $creator_name= trim($creator->first_name.' '.$creator->last_name) ?: $creator->display_name ?: $site;
        $user_data   = get_userdata($uid);
        $reset_key   = get_password_reset_key($user_data);
        $set_pw_url  = ! is_wp_error($reset_key)
            ? home_url( add_query_arg( array('wpi'=>'1','action'=>'rp','key'=>$reset_key,'login'=>rawurlencode($user_data->user_login)), '/' ) )
            : home_url('/?wpi=1');

        require_once WPI_PLUGIN_DIR . 'includes/class-scheduler.php';

        $body_html = '
            <p style="margin:0 0 16px;color:#374151;font-size:14px;line-height:1.6;">Hi ' . esc_html($fname) . ',</p>
            <p style="margin:0 0 20px;color:#374151;font-size:14px;line-height:1.6;">
                <strong>' . esc_html($creator_name) . '</strong> has created an account for you on <strong>' . esc_html($site) . '</strong>.
                Click the button below to set your password and get started.
            </p>
            <table width="100%" cellpadding="0" cellspacing="0" style="background:#f8faff;border:1.5px solid #e0e7ff;border-radius:10px;margin-bottom:20px;">
              <tr><td style="padding:16px 18px;">
                <p style="margin:0 0 4px;font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.5px;">Your Login Email</p>
                <p style="margin:0 0 12px;font-size:14px;color:#111827;">' . esc_html($email) . '</p>
                <p style="margin:0;font-size:12px;color:#6b7280;font-style:italic;">Use the button below to set your password — the link expires in 24 hours.</p>
              </td></tr>
            </table>';

        WPI_Scheduler::send_branded_email(
            $email,
            'Welcome to ' . $site . ' — Set your password to get started',
            $body_html,
            '#1a3a5c',
            '👋 WELCOME',
            $set_pw_url,
            'Click Here to Set My Password →'
        );

        // Activate access token if provided
        $access_token = strtoupper( sanitize_text_field( $body['access_token'] ?? '' ) );
        $token_result = null;
        if ( $access_token ) {
            require_once WPI_PLUGIN_DIR . 'includes/class-access.php';
            global $wpdb;
            $lic = $wpdb->get_row( $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}wpi_licences WHERE token=%s AND status='unassigned'",
                $access_token
            ) );
            if ( $lic ) {
                WPI_Access::assign_to_user( $lic->id, $uid );
                $token_result = 'activated';
            } else {
                // Token already used or not found — still create user, just note token issue
                $token_result = 'invalid';
            }
        }

        $this->json( array('success'=>true,'user_id'=>$uid,'token_result'=>$token_result) );
    }



    public function wpi_set_org_licence() {
        $this->check_nonce();
        if ( ! $this->is_system_owner() ) $this->error('Access denied', 403);
        wpi_ensure_org_licence_columns();
        global $wpdb;
        $body   = $this->input();
        $org_id = absint( $body['org_id'] ?? 0 );
        if ( ! $org_id ) $this->error('org_id required');
        $type   = sanitize_text_field( $body['licence_type'] ?? 'trial' );
        $start  = sanitize_text_field( $body['licence_start'] ?? '' );
        $end    = sanitize_text_field( $body['licence_end'] ?? '' );
        $trial  = absint( $body['trial_days'] ?? 14 );
        $status = sanitize_text_field( $body['status'] ?? 'active' );
        if ( in_array($type, array('monthly','annual')) && $start && !$end ) {
            $dt = new DateTime($start);
            $dt->modify( $type==='monthly' ? '+1 month' : '+1 year' );
            $end = $dt->format('Y-m-d');
        }
        if ( $type==='trial' )    { $end=null; $start=$start ?: date('Y-m-d'); }
        if ( $type==='lifetime' ) { $end=null; $start=$start ?: date('Y-m-d'); }
        $update = array(
            'licence_type'  => $type,
            'licence_start' => $start ?: null,
            'licence_end'   => $end   ?: null,
            'trial_days'    => $trial,
            'status'        => $status,
        );
        if ( array_key_exists('max_sessions', $body) ) {
            $update['max_sessions'] = ( $body['max_sessions'] === '' || $body['max_sessions'] === null ) ? null : max(0, absint($body['max_sessions']));
        }
        $wpdb->update( $wpdb->prefix.'wpi_organisations', $update, array('id'=>$org_id) );
        $lic = self::get_org_licence( $org_id );
        $this->json( array_merge( array('success'=>true,'_rows_updated'=>$wpdb->rows_affected,'_db_error'=>$wpdb->last_error?:null), $lic ) );
    }

    public function wpi_get_schedule() {
        $this->check_nonce();
        global $wpdb;
        $tid = absint( $_GET['template_id'] ?? 0 );
        if ( !$tid ) $this->error('template_id required');
        $row = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wpi_schedules WHERE template_id=%d LIMIT 1", $tid
        ) );
        if ( $row && $row->recipients ) $row->recipients = json_decode($row->recipients, true);
        $this->json( $row ?: (object)array(
            'template_id'=>$tid,'enabled'=>0,'frequency'=>'weekly',
            'day_of_week'=>1,'day_of_month'=>1,'time_of_day'=>'08:00',
            'recipients'=>array(),'subject'=>'Reminder: {template} inspection due',
            'body'=>"Hi,

This is a reminder that the {template} inspection is due today ({date}).

Last completed: {last_completed}

Please log in to complete the inspection.

".home_url(),
            'overdue_hours'=>null,'last_sent_at'=>null
        ));
    }

    public function wpi_save_schedule() {
        $this->check_nonce();
        if ( !$this->can('manager') ) $this->error('Permission denied', 403);
        global $wpdb;
        $b   = $this->input();
        $tid = absint( $b['template_id'] ?? 0 );
        if ( !$tid ) $this->error('template_id required');
        $row = array(
            'template_id'  => $tid,
            'enabled'      => empty($b['enabled']) ? 0 : 1,
            'frequency'    => in_array($b['frequency']??'weekly',array('daily','weekly','monthly')) ? $b['frequency'] : 'weekly',
            'day_of_week'  => isset($b['day_of_week'])  ? max(1,min(7,absint($b['day_of_week'])))  : 1,
            'day_of_month' => isset($b['day_of_month']) ? max(1,min(31,absint($b['day_of_month']))) : 1,
            'time_of_day'  => preg_match('/^\d{1,2}:\d{2}$/', $b['time_of_day']??'08:00') ? $b['time_of_day'] : '08:00',
            'recipients'   => wp_json_encode( array_values( array_filter( array_map('sanitize_email', (array)($b['recipients']??array())) ) ) ),
            'subject'      => sanitize_text_field( $b['subject'] ?? 'Reminder: {template} inspection due' ),
            'body'         => sanitize_textarea_field( $b['body'] ?? '' ),
            'overdue_hours'=> !empty($b['overdue_hours']) ? absint($b['overdue_hours']) : null,
        );
        $existing = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}wpi_schedules WHERE template_id=%d",$tid));
        if ($existing) $wpdb->update($wpdb->prefix.'wpi_schedules',$row,array('id'=>$existing));
        else           $wpdb->insert($wpdb->prefix.'wpi_schedules',$row);
        $this->json(array('success'=>true));
    }

    public function wpi_test_schedule_email() {
        $this->check_nonce();
        if ( !$this->can('manager') ) $this->error('Permission denied', 403);
        $b   = $this->input();
        $to  = sanitize_email( $b['email'] ?? get_option('admin_email') );
        $tid = absint( $b['template_id'] ?? 0 );
        global $wpdb;
        $cfg = array();
        if ($tid) {
            $t = $wpdb->get_var($wpdb->prepare("SELECT settings FROM {$wpdb->prefix}wpi_templates WHERE id=%d",$tid));
            $cfg = $t ? (json_decode($t,true)??array()) : array();
        }
        require_once WPI_PLUGIN_DIR.'includes/class-scheduler.php';
        WPI_Scheduler::send_email($to, '🧪 Test: Inspection Reminder', "This is a test reminder email from Audit4me.

If you received this, your schedule email settings are working correctly.", $cfg, false);
        $this->json(array('success'=>true,'sent_to'=>$to));
    }

    public function wpi_create_share_token() {
        if ( ! check_ajax_referer( 'wpi_nonce', 'nonce', false ) ) {
            wp_send_json_error( 'bad_nonce', 403 ); return;
        }
        $id = absint( $_POST['id'] ?? 0 );
        if ( ! $id ) { wp_send_json_error( 'no_id', 400 ); return; }
        // Verify the requesting user can actually access this inspection
        global $wpdb;
        $uid = get_current_user_id();
        if ( ! $uid ) { wp_send_json_error( 'not_logged_in', 401 ); return; }
        $ins = $wpdb->get_row( $wpdb->prepare(
            "SELECT conducted_by, template_id, org_id FROM {$wpdb->prefix}wpi_inspections WHERE id=%d", $id
        ) );
        if ( ! $ins ) { wp_send_json_error( 'not_found', 404 ); return; }
        // Must own it, be a manager in the same org, or be system owner
        if ( ! $this->is_system_owner() ) {
            $own_org = $this->get_org_id();
            $is_mgr  = $this->can('manager');
            if ( $is_mgr ) {
                if ( $own_org && (int)$ins->org_id !== (int)$own_org ) {
                    wp_send_json_error( 'access_denied', 403 ); return;
                }
            } else {
                if ( (int)$ins->conducted_by !== (int)$uid ) {
                    wp_send_json_error( 'access_denied', 403 ); return;
                }
            }
        }
        $token = bin2hex( random_bytes( 16 ) );
        set_transient( 'wpi_share_' . $token, array( 'id' => $id ), 86400 );
        wp_send_json_success( array( 'url' => home_url( '/?wpi_share=' . $token ) ) );
    }


    /* ── Web Push Notifications ─────────────────────────────────── */

    /**
     * Return the VAPID public key so the browser can subscribe.
     */
    public function wpi_get_vapid_public_key() {
        $this->check_nonce();
        // Force regenerate if stored keys are invalid/empty
        $stored = get_option('wpi_vapid_keys');
        if ( $stored && ( empty($stored['public']) || empty($stored['method']) ) ) {
            delete_option('wpi_vapid_keys');
        }
        $keys = self::get_vapid_keys();
        if ( ! $keys || empty($keys['public']) ) {
            $this->error( 'Push notifications could not be initialised on this server.' );
        }
        $this->json( array( 'publicKey' => $keys['public'], 'method' => $keys['method'] ?? 'unknown' ) );
    }

    /**
     * Save a push subscription for the current user.
     */
    public function wpi_push_subscribe() {
        $this->check_nonce();
        $uid  = get_current_user_id();
        $body = $this->input();
        $endpoint = esc_url_raw( $body['endpoint'] ?? '' );
        $p256dh   = sanitize_text_field( $body['p256dh']  ?? '' );
        $auth     = sanitize_text_field( $body['auth']     ?? '' );
        if ( ! $endpoint || ! $p256dh || ! $auth ) $this->error( 'Invalid subscription data' );

        // Store per-user (multiple devices supported — capped at 10 to prevent meta bloat)
        WPI_Ajax::push_log('wpi_push_subscribe called uid=' . $uid . ' logged_in=' . (is_user_logged_in()?'yes':'NO') . ' endpoint=' . substr($endpoint,0,60) . ' p256dh=' . (empty($p256dh)?'MISSING':'ok') . ' auth=' . (empty($auth)?'MISSING':'ok'));
        $subs = get_user_meta( $uid, 'wpi_push_subscriptions', true ) ?: array();
        if ( ! is_array($subs) ) $subs = array();
        // Deduplicate by endpoint
        $subs = array_filter( $subs, function($s) use ($endpoint) {
            return isset($s['endpoint']) && $s['endpoint'] !== $endpoint;
        });
        $subs[] = array(
            'endpoint' => $endpoint,
            'p256dh'   => $p256dh,
            'auth'     => $auth,
            'added'    => current_time('mysql'),
        );
        // Cap at 10 subscriptions per user — keep the most recent
        if ( count($subs) > 10 ) {
            $subs = array_slice( array_values($subs), -10 );
        }
        update_user_meta( $uid, 'wpi_push_subscriptions', array_values($subs) );
        WPI_Ajax::push_log('wpi_push_subscribe SAVED uid=' . $uid . ' total_subs=' . count($subs));
        $this->json( array( 'success' => true ) );
    }

    /**
     * Remove a push subscription.
     */
    public function wpi_push_unsubscribe() {
        $this->check_nonce();
        $uid      = get_current_user_id();
        $body     = $this->input();
        $endpoint = esc_url_raw( $body['endpoint'] ?? '' );
        if ( ! $endpoint ) $this->error( 'endpoint required' );
        $subs = get_user_meta( $uid, 'wpi_push_subscriptions', true ) ?: array();
        $subs = array_values( array_filter( $subs, function($s) use ($endpoint) {
            return isset($s['endpoint']) && $s['endpoint'] !== $endpoint;
        }));
        update_user_meta( $uid, 'wpi_push_subscriptions', $subs );
        $this->json( array( 'success' => true ) );
    }

    /* ── VAPID / Push send helpers ───────────────────────────────── */

    /**
     * Get or generate VAPID key pair.
     */
    public static function get_vapid_keys() {
        require_once WPI_PLUGIN_DIR . 'includes/class-vapid.php';
        return WPI_Vapid::get_keys();
    }

    public static function base64url_encode( $data ) {
        return rtrim( strtr( base64_encode( $data ), '+/', '-_' ), '=' );
    }

    public static function base64url_decode( $data ) {
        return base64_decode( strtr( $data, '-_', '+/' ) . str_repeat('=', (4 - strlen($data) % 4) % 4) );
    }

    /**
     * Send a push notification to a specific user (all their devices).
     * $payload = ['title'=>'...', 'body'=>'...', 'url'=>'...', 'tag'=>'...']
     */
    public function wpi_read_push_log() {
        $this->check_nonce();
        if ( ! $this->is_system_owner() ) $this->error('Access denied', 403);
        $upload  = wp_upload_dir();
        $file    = trailingslashit($upload['basedir']) . 'wpi-logs/wpi-push-debug.log';
        $content = file_exists($file) ? file_get_contents($file) : 'No log yet.';
        $this->json(array('log' => $content));
    }


    public function wpi_push_diagnostics() {
        $this->check_nonce();
        if ( ! $this->is_system_owner() ) $this->error('Access denied', 403);

        $body = $this->input();
        $target_uid = absint($body['user_id'] ?? get_current_user_id());
        if ( ! $target_uid || ! get_user_by('id', $target_uid) ) {
            $target_uid = get_current_user_id();
        }

        $service_account = get_option('wpi_firebase_service_account', '');
        $sa = $service_account ? json_decode($service_account, true) : null;
        $firebase = array(
            'service_account_saved' => ! empty($service_account),
            'json_valid'            => is_array($sa),
            'project_id'            => is_array($sa) ? ($sa['project_id'] ?? '') : '',
            'client_email'          => is_array($sa) ? ($sa['client_email'] ?? '') : '',
            'has_private_key'       => is_array($sa) && ! empty($sa['private_key']),
            'openssl_available'     => function_exists('openssl_sign'),
            'access_token_ok'       => false,
            'access_token_error'    => '',
        );

        if ( is_array($sa) && ! empty($sa['project_id']) && ! empty($sa['private_key']) && function_exists('openssl_sign') ) {
            $tok = self::get_firebase_access_token($sa);
            if ( $tok ) {
                $firebase['access_token_ok'] = true;
            } else {
                $firebase['access_token_error'] = 'Could not create Firebase OAuth access token. Check service account JSON/private key and server outbound connection.';
            }
        }

        $fcm_tokens = self::get_user_fcm_tokens($target_uid);
        $fcm_device_records = self::get_user_fcm_device_records($target_uid);
        $fcm_device_details = array();
        if ( is_array($fcm_device_records) ) {
            foreach ( $fcm_device_records as $device_key => $rec ) {
                if ( ! is_array($rec) ) continue;
                $tok = sanitize_text_field($rec['token'] ?? '');
                $di  = $rec['device_info'] ?? array();
                if ( ! is_array($di) ) $di = array();
                $fcm_device_details[] = array(
                    'device_id'   => sanitize_text_field($rec['device_id'] ?? $device_key),
                    'token_short' => $tok ? substr($tok,0,18) . '...' . substr($tok,-6) : '',
                    'source'      => sanitize_text_field($rec['source'] ?? ''),
                    'status'      => sanitize_text_field($rec['status'] ?? ''),
                    'first_seen'  => sanitize_text_field($rec['first_seen'] ?? ''),
                    'last_seen'   => sanitize_text_field($rec['last_seen'] ?? ''),
                    'ua'          => substr(sanitize_text_field($di['ua'] ?? ''), 0, 120),
                    'platform'    => sanitize_text_field($di['platform'] ?? ''),
                );
            }
        }

        $web_subs = get_user_meta($target_uid, 'wpi_push_subscriptions', true);
        if ( ! is_array($web_subs) ) $web_subs = array();
        $onesignal = get_user_meta($target_uid, 'wpi_onesignal_player_ids', true);
        if ( ! is_array($onesignal) ) $onesignal = array();

        $all_users_with_fcm = get_users(array(
            'fields' => 'ID',
            'number' => 9999,
        ));
        $total_fcm_tokens = 0;
        $users_with_fcm_details = array();
        foreach ( $all_users_with_fcm as $u ) {
            $uid2 = absint($u);
            $device_records2 = self::get_user_fcm_device_records($uid2);
            $t = self::get_user_fcm_tokens($uid2);
            if ( is_array($t) && count($t) > 0 ) {
                $total_fcm_tokens += count($t);
                $user_obj = get_userdata($uid2);
                $last_token = end($t);
                $users_with_fcm_details[] = array(
                    'id'          => $uid2,
                    'login'       => $user_obj ? $user_obj->user_login : '',
                    'display'     => $user_obj ? $user_obj->display_name : '',
                    'token_count' => count($t),
                    'device_count' => count($device_records2),
                    'token_short' => substr($last_token,0,18) . '...' . substr($last_token,-6),
                );
            }
        }

        $upload = wp_upload_dir();
        $log_file = trailingslashit($upload['basedir']) . 'wpi-logs/wpi-push-debug.log';
        $log_exists = file_exists($log_file);
        $log_writable = $log_exists ? is_writable($log_file) : is_writable(dirname($log_file));
        $last_log = 'No log yet.';
        if ( $log_exists ) {
            $content = file_get_contents($log_file);
            $lines = preg_split('/\r\n|\r|\n/', trim($content));
            $last_log = implode("\n", array_slice($lines, -40));
        }

        $checks = array();
        $checks[] = array('name'=>'Firebase service account saved', 'ok'=>$firebase['service_account_saved'], 'detail'=>$firebase['project_id'] ?: 'Missing');
        $checks[] = array('name'=>'Firebase JSON valid', 'ok'=>$firebase['json_valid'], 'detail'=>$firebase['json_valid'] ? 'OK' : 'Invalid JSON');
        $checks[] = array('name'=>'Firebase private key / OpenSSL', 'ok'=>($firebase['has_private_key'] && $firebase['openssl_available']), 'detail'=>$firebase['openssl_available'] ? 'OpenSSL available' : 'OpenSSL missing');
        $checks[] = array('name'=>'Firebase access token', 'ok'=>$firebase['access_token_ok'], 'detail'=>$firebase['access_token_ok'] ? 'OK' : ($firebase['access_token_error'] ?: 'Not tested'));
        $checks[] = array('name'=>'Selected user FCM token', 'ok'=>count($fcm_tokens) > 0, 'detail'=>count($fcm_tokens) . ' token(s) saved');
        $checks[] = array('name'=>'Web push subscription', 'ok'=>count($web_subs) > 0, 'detail'=>count($web_subs) . ' browser subscription(s) saved');
        $checks[] = array('name'=>'OneSignal player ID', 'ok'=>count($onesignal) > 0, 'detail'=>count($onesignal) . ' player ID(s) saved');
        $checks[] = array('name'=>'Push log file', 'ok'=>$log_writable, 'detail'=>$log_exists ? 'Exists' : 'Will be created on next log');

        self::push_log('OWNER PUSH DIAGNOSTICS run by uid='.get_current_user_id().' target_uid='.$target_uid.' fcm_tokens='.count($fcm_tokens).' web_subs='.count($web_subs).' onesignal='.count($onesignal));

        $this->json(array(
            'target_user_id'       => $target_uid,
            'target_user_login'    => get_userdata($target_uid) ? get_userdata($target_uid)->user_login : '',
            'firebase'             => $firebase,
            'checks'               => $checks,
            'fcm_token_count'      => count($fcm_tokens),
            'fcm_tokens_short'     => array_map(function($t){ return substr($t,0,18) . '...' . substr($t,-6); }, $fcm_tokens),
            'fcm_device_count'     => count($fcm_device_records),
            'fcm_device_details'   => $fcm_device_details,
            'web_push_count'       => count($web_subs),
            'onesignal_count'      => count($onesignal),
            'users_with_fcm'       => count($all_users_with_fcm),
            'total_fcm_tokens'     => $total_fcm_tokens,
            'users_with_fcm_details' => $users_with_fcm_details,
            'log_file'             => $log_file,
            'last_log'             => $last_log,
            'server_time'          => current_time('mysql'),
        ));
    }

    public function wpi_onesignal_status() {
        $this->check_nonce();
        if ( ! $this->is_system_owner() ) $this->error('Access denied', 403);
        // Test OneSignal connection by fetching app info
        // Test by sending a ping notification with invalid player to verify auth works
        $resp = wp_remote_post( 'https://onesignal.com/api/v1/notifications', array(
            'timeout' => 10,
            'headers' => array(
                'Content-Type'  => 'application/json',
                'Authorization' => 'Key ' . self::get_onesignal_api_key(),
            ),
            'body' => json_encode(array(
                'app_id'             => self::get_onesignal_app_id(),
                'include_player_ids' => array('test-ping-id'),
                'contents'           => array('en' => 'test'),
            )),
        ) );
        $code = wp_remote_retrieve_response_code($resp);
        $body = json_decode(wp_remote_retrieve_body($resp), true);
        // 200 with errors about invalid player = auth OK, 403/401 = bad key
        $auth_ok = in_array($code, array(200, 400)) || ($code === 200 && isset($body['errors']));
        if ( $code !== 403 && $code !== 401 ) {
            $this->json(array(
                'connected'   => true,
                'app_name'    => 'Audit4me',
                'subscribers' => 'N/A',
                'android'     => true,
                'ios'         => false,
                'note'        => 'Auth OK (code '.$code.')',
            ));
        } else {
            $this->json(array(
                'connected' => false,
                'error'     => $body['errors'][0] ?? 'Auth failed (code '.$code.')',
            ));
        }
    }

    public function wpi_save_firebase_sa() {
        $this->check_nonce();
        if (!$this->is_system_owner()) $this->error('Access denied', 403);
        $body = $this->input();
        $sa   = $body['service_account'] ?? '';
        if (!$sa) $this->error('No data');
        $parsed = json_decode($sa, true);
        if (!$parsed || empty($parsed['project_id']) || empty($parsed['private_key'])) {
            $this->error('Invalid service account JSON');
        }
        update_option('wpi_firebase_service_account', $sa);
        self::push_log('Firebase service account saved project='.$parsed['project_id']);
        $this->json(array('success' => true));
    }

    // ── Push Notification Settings (system owner only) ─────────────

    public function wpi_get_push_settings() {
        $this->check_nonce();
        if ( ! $this->is_system_owner() ) $this->error('Access denied', 403);

        $cfg = get_option('wpi_push_settings', array());
        $firebase_sa = get_option('wpi_firebase_service_account', '');
        $vapid = get_option('wpi_vapid_keys', array());

        // Parse Firebase SA for display info
        $firebase_info = array();
        if ($firebase_sa) {
            $parsed = json_decode($firebase_sa, true);
            if ($parsed) {
                $firebase_info = array(
                    'project_id'   => $parsed['project_id'] ?? '',
                    'client_email' => $parsed['client_email'] ?? '',
                    'has_sa'       => true,
                );
            }
        }

        $this->json(array(
            // OneSignal
            'onesignal_app_id'     => $cfg['onesignal_app_id']  ?? '',
            'onesignal_api_key'    => $cfg['onesignal_api_key']  ?? '',
            // Firebase
            'firebase_info'        => $firebase_info,
            'firebase_has_sa'      => ! empty($firebase_sa),
            // VAPID (Web Push)
            'vapid_public_key'     => $vapid['public'] ?? '',
            'vapid_has_private'    => ! empty($vapid['private'] ?? ''),
        ));
    }

    public function wpi_save_push_settings() {
        $this->check_nonce();
        if ( ! $this->is_system_owner() ) $this->error('Access denied', 403);
        $body = $this->input();

        $cfg = get_option('wpi_push_settings', array());
        if ( isset($body['onesignal_app_id']) )
            $cfg['onesignal_app_id']  = sanitize_text_field($body['onesignal_app_id']);
        if ( isset($body['onesignal_api_key']) )
            $cfg['onesignal_api_key'] = sanitize_text_field($body['onesignal_api_key']);

        update_option('wpi_push_settings', $cfg);
        $this->json(array('success' => true));
    }


    public static function get_user_fcm_device_records( $uid ) {
        $records = get_user_meta( $uid, 'wpi_fcm_device_tokens', true );
        if ( ! is_array( $records ) ) $records = array();

        // Backward compatibility: import old token-only list as legacy device records.
        $legacy = get_user_meta( $uid, 'wpi_fcm_tokens', true );
        if ( is_array( $legacy ) ) {
            foreach ( $legacy as $tok ) {
                $tok = is_string($tok) ? trim($tok) : '';
                if ( ! $tok ) continue;
                $found = false;
                foreach ( $records as $rec ) {
                    if ( is_array($rec) && ! empty($rec['token']) && $rec['token'] === $tok ) { $found = true; break; }
                }
                if ( ! $found ) {
                    $key = 'legacy_' . substr( md5($tok), 0, 12 );
                    $records[$key] = array(
                        'device_id'    => $key,
                        'token'        => $tok,
                        'source'       => 'legacy_import',
                        'device_info'  => array(),
                        'first_seen'   => current_time('mysql'),
                        'last_seen'    => current_time('mysql'),
                        'status'       => 'active',
                    );
                }
            }
        }

        // Keep only valid active records with a token.
        $clean = array();
        foreach ( $records as $key => $rec ) {
            if ( ! is_array($rec) || empty($rec['token']) ) continue;
            $status = ! empty($rec['status']) ? $rec['status'] : 'active';
            if ( $status !== 'active' ) continue;
            $device_id = ! empty($rec['device_id']) ? sanitize_text_field($rec['device_id']) : sanitize_key($key);
            if ( ! $device_id ) $device_id = 'device_' . substr( md5($rec['token']), 0, 12 );
            $rec['device_id'] = $device_id;
            $rec['token'] = sanitize_text_field($rec['token']);
            $clean[$device_id] = $rec;
        }
        return $clean;
    }

    public static function get_user_fcm_tokens( $uid ) {
        $records = self::get_user_fcm_device_records( $uid );
        $tokens = array();
        foreach ( $records as $rec ) {
            if ( ! empty($rec['token']) ) $tokens[] = $rec['token'];
        }
        return array_values( array_unique( $tokens ) );
    }

    public static function sync_user_fcm_legacy_tokens( $uid, $records = null ) {
        if ( $records === null ) $records = self::get_user_fcm_device_records( $uid );
        $tokens = array();
        foreach ( $records as $rec ) {
            if ( is_array($rec) && ! empty($rec['token']) && ( empty($rec['status']) || $rec['status'] === 'active' ) ) $tokens[] = $rec['token'];
        }
        $tokens = array_values( array_unique( $tokens ) );
        if ( $tokens ) update_user_meta( $uid, 'wpi_fcm_tokens', $tokens );
        else delete_user_meta( $uid, 'wpi_fcm_tokens' );
        return $tokens;
    }


    public function wpi_wtn_debug() {
        $this->check_nonce();
        $body = $this->input();
        $uid = get_current_user_id();
        $posted_uid = absint($body['user_id'] ?? 0);
        $stage = sanitize_text_field($body['stage'] ?? 'unknown');
        $device_id = sanitize_text_field($body['device_id'] ?? '');
        $has_wtn = ! empty($body['has_wtn']) ? '1' : '0';
        $has_firebase = ! empty($body['has_firebase']) ? '1' : '0';
        $has_token = ! empty($body['has_token']) ? '1' : '0';
        $token = sanitize_text_field($body['token'] ?? '');
        $token_short = $token ? substr($token, 0, 18) . '...' . substr($token, -6) : 'none';
        $ua = '';
        if ( ! empty($body['device_info']) && is_array($body['device_info']) ) {
            $ua = sanitize_text_field($body['device_info']['ua'] ?? '');
        }
        self::push_log('WTN DEBUG uid='.$uid.' posted='.$posted_uid.' stage='.$stage.' device='.substr($device_id,0,18).' has_wtn='.$has_wtn.' has_firebase='.$has_firebase.' has_token='.$has_token.' token='.$token_short.' ua='.substr($ua,0,80));
        $this->json(array('success'=>true));
    }

    public function wpi_wtn_register() {
        $this->check_nonce();

        $body  = $this->input();
        $token = sanitize_text_field($body['fcm_token'] ?? '');
        if (!$token) $this->error('Invalid token');

        $current_uid = get_current_user_id();
        $posted_uid  = absint($body['user_id'] ?? 0);
        $uid         = $posted_uid ?: $current_uid;

        if (!$uid || !get_user_by('id', $uid)) {
            self::push_log('WTN FCM token register failed invalid uid posted='.$posted_uid.' current='.$current_uid.' token='.substr($token,0,20).'...');
            $this->error('Invalid user for token registration');
        }

        $raw_device_id = sanitize_text_field($body['device_id'] ?? '');
        $device_id = $raw_device_id ? $raw_device_id : ('device_' . substr(md5($token), 0, 12));
        $source = sanitize_text_field($body['source'] ?? (!empty($body['force']) ? 'wtn_force_register' : 'wtn_auto_login'));
        $device_info = $body['device_info'] ?? array();
        if ( is_string($device_info) ) {
            $decoded = json_decode($device_info, true);
            $device_info = is_array($decoded) ? $decoded : array('raw' => sanitize_text_field($device_info));
        }
        if ( ! is_array($device_info) ) $device_info = array();

        // If this token was previously attached to another user, remove it from that user.
        $users = get_users(array('fields' => 'ID', 'number' => 9999));
        foreach ($users as $other_uid) {
            $other_uid = absint($other_uid);
            if ($other_uid === $uid) continue;

            $changed = false;
            $records = get_user_meta($other_uid, 'wpi_fcm_device_tokens', true);
            if (is_array($records)) {
                foreach ($records as $k => $rec) {
                    if (is_array($rec) && !empty($rec['token']) && $rec['token'] === $token) {
                        unset($records[$k]);
                        $changed = true;
                    }
                }
                if ($changed) {
                    if ($records) update_user_meta($other_uid, 'wpi_fcm_device_tokens', $records);
                    else delete_user_meta($other_uid, 'wpi_fcm_device_tokens');
                    self::sync_user_fcm_legacy_tokens($other_uid, $records);
                    self::push_log('WTN FCM token removed from old uid='.$other_uid.' token='.substr($token,0,20).'...');
                }
            }

            $tokens = get_user_meta($other_uid, 'wpi_fcm_tokens', true);
            if (is_array($tokens) && in_array($token, $tokens, true)) {
                $new_tokens = array_values(array_filter($tokens, function($t) use ($token) { return $t !== $token; }));
                if ($new_tokens) update_user_meta($other_uid, 'wpi_fcm_tokens', $new_tokens);
                else delete_user_meta($other_uid, 'wpi_fcm_tokens');
                if (!$changed) self::push_log('WTN FCM legacy token removed from old uid='.$other_uid.' token='.substr($token,0,20).'...');
            }
        }

        $records = get_user_meta($uid, 'wpi_fcm_device_tokens', true);
        if (!is_array($records)) $records = array();

        // Avoid one device/token overwriting another device. The seat key is uid + device_id.
        $first_seen = current_time('mysql');
        if (isset($records[$device_id]) && is_array($records[$device_id]) && !empty($records[$device_id]['first_seen'])) {
            $first_seen = $records[$device_id]['first_seen'];
        }
        $records[$device_id] = array(
            'device_id'   => $device_id,
            'token'       => $token,
            'source'      => $source,
            'device_info' => $device_info,
            'first_seen'  => $first_seen,
            'last_seen'   => current_time('mysql'),
            'status'      => 'active',
        );

        // Keep the most recent 20 device-token records for this user.
        uasort($records, function($a, $b) {
            return strcmp($b['last_seen'] ?? '', $a['last_seen'] ?? '');
        });
        $records = array_slice($records, 0, 20, true);
        update_user_meta($uid, 'wpi_fcm_device_tokens', $records);
        $tokens = self::sync_user_fcm_legacy_tokens($uid, $records);

        self::push_log('WTN FCM token registered uid='.$uid.' current='.$current_uid.' posted='.$posted_uid.' device='.substr($device_id,0,12).' token='.substr($token,0,20).'... source='.$source.' devices='.count($records).' tokens='.count($tokens));
        $this->json(array('success' => true, 'uid' => $uid, 'current_uid' => $current_uid, 'posted_uid' => $posted_uid, 'device_id' => $device_id, 'device_count' => count($records), 'token_count' => count($tokens)));
    }

    public function wpi_onesignal_register() {
        $this->check_nonce();
        $uid       = get_current_user_id();
        $body      = $this->input();
        $player_id = sanitize_text_field( $body['player_id'] ?? '' );
        if ( ! $player_id ) $this->error('Invalid player ID');
        $existing = get_user_meta( $uid, 'wpi_onesignal_player_ids', true ) ?: array();
        if ( ! in_array( $player_id, $existing ) ) {
            $existing[] = $player_id;
            update_user_meta( $uid, 'wpi_onesignal_player_ids', array_values($existing) );
        }
        self::push_log( 'OneSignal player registered uid=' . $uid . ' player=' . $player_id );
        $this->json( array('success' => true) );
    }

    public static function push_log( $msg ) {
        $upload   = wp_upload_dir();
        $log_dir  = trailingslashit( $upload['basedir'] ) . 'wpi-logs';
        $file     = $log_dir . '/wpi-push-debug.log';

        // Create the directory and protect it on first use
        if ( ! is_dir( $log_dir ) ) {
            wp_mkdir_p( $log_dir );
            // Block direct HTTP access to all files in this directory
            $htaccess = $log_dir . '/.htaccess';
            if ( ! file_exists( $htaccess ) ) {
                file_put_contents( $htaccess, "Order deny,allow\nDeny from all\n" );
            }
            // Also drop an index file as a second layer
            file_put_contents( $log_dir . '/index.php', '<?php // Silence is golden.' );
        }

        $line = '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL;
        file_put_contents( $file, $line, FILE_APPEND | LOCK_EX );
    }

    public function wpi_clear_push_subs() {
        $this->check_nonce();
        $uid = get_current_user_id();
        delete_user_meta( $uid, 'wpi_push_subscriptions' );
        WPI_Ajax::push_log('Cleared all push subs for uid=' . $uid);
        $this->json( array( 'success' => true ) );
    }

    public function wpi_owner_test_push() {
        $this->check_nonce();
        if ( ! $this->is_system_owner() ) $this->error('Access denied', 403);
        $body = $this->input();
        $target_uid = absint($body['user_id'] ?? 0);
        if ( ! $target_uid || ! get_user_by('id', $target_uid) ) {
            $this->error('Select a valid user with a Firebase token');
        }
        $tokens = self::get_user_fcm_tokens($target_uid);
        if ( ! is_array($tokens) || empty($tokens) ) {
            self::push_log('OWNER TEST PUSH blocked target_uid='.$target_uid.' no FCM token');
            $this->error('Selected user has no Firebase token saved');
        }
        self::push_log('OWNER TEST PUSH sending target_uid='.$target_uid.' fcm_tokens='.count($tokens));
        self::send_push( $target_uid, array(
            'title' => 'Audit4me test notification',
            'body'  => 'Firebase/Android notification test sent by System Owner.',
            'url'   => home_url('/?wpi=1#settings'),
            'tag'   => 'wpi-owner-test-' . time(),
        ) );
        $this->json(array('success'=>true,'target_uid'=>$target_uid,'fcm_tokens'=>count($tokens)));
    }

    public function wpi_owner_test_action_push() {
        $this->check_nonce();
        if ( ! $this->is_system_owner() ) $this->error('Access denied', 403);
        $body = $this->input();
        $target_uid = absint($body['user_id'] ?? 0);
        if ( ! $target_uid || ! get_user_by('id', $target_uid) ) {
            $this->error('Select a valid user with a Firebase token');
        }
        $tokens = self::get_user_fcm_tokens($target_uid);
        if ( ! is_array($tokens) || empty($tokens) ) {
            self::push_log('OWNER TEST ACTION PUSH blocked target_uid='.$target_uid.' no FCM token');
            $this->error('Selected user has no Firebase token saved');
        }
        self::push_log('OWNER TEST ACTION PUSH sending target_uid='.$target_uid.' fcm_tokens='.count($tokens));
        self::send_push( $target_uid, array(
            'title' => 'New Action Assigned to You',
            'body'  => 'Test corrective action notification. This uses the same push route as Assign Corrective Action.',
            'url'   => home_url('/?wpi=1#actions'),
            'tag'   => 'wpi-owner-test-action-' . time(),
        ) );
        $this->json(array('success'=>true,'target_uid'=>$target_uid,'fcm_tokens'=>count($tokens)));
    }


    public function wpi_owner_test_overdue_action_push() {
        $this->check_nonce();
        if ( ! $this->is_system_owner() ) $this->error('Access denied', 403);
        $body = $this->input();
        $target_uid = absint($body['user_id'] ?? 0);
        if ( ! $target_uid || ! get_user_by('id', $target_uid) ) {
            $this->error('Select a valid user with a Firebase token');
        }
        $tokens = self::get_user_fcm_tokens($target_uid);
        if ( ! is_array($tokens) || empty($tokens) ) {
            self::push_log('OWNER TEST OVERDUE ACTION PUSH blocked target_uid='.$target_uid.' no FCM token');
            $this->error('Selected user has no Firebase token saved');
        }
        self::push_log('OWNER TEST OVERDUE ACTION PUSH sending target_uid='.$target_uid.' fcm_tokens='.count($tokens));
        self::send_push( $target_uid, array(
            'title' => '⚠️ Overdue Action',
            'body'  => 'Test overdue corrective action notification. This uses the same push route as overdue action reminders.',
            'url'   => home_url('/?wpi=1#actions'),
            'tag'   => 'wpi-owner-test-overdue-action-' . time(),
        ) );
        $this->json(array('success'=>true,'target_uid'=>$target_uid,'fcm_tokens'=>count($tokens)));
    }

    public function wpi_test_push() {
        $this->check_nonce();
        $uid = get_current_user_id();
        $result = self::send_push( $uid, array(
            'title' => 'New Action Assigned to You',
            'body'  => 'High priority: Test action item. Please review and address it.',
            'url'   => home_url('/?wpi=1#actions'),
            'tag'   => 'wpi-test-' . time(),
        ) );
        $subs = get_user_meta( $uid, 'wpi_push_subscriptions', true ) ?: array();
        $this->json( array( 'success' => true, 'subs' => count($subs) ) );
    }

    // OneSignal credentials — stored in DB so system owner can update from Settings
    // Legacy fallback constants — configure via Settings > Push Notifications.
    // The API key below has been rotated. Set live credentials in the admin panel.
    const ONESIGNAL_APP_ID  = 'a9e30544-a512-4633-8ce4-114ebd13d8de'; // legacy fallback only
    const ONESIGNAL_API_KEY = ''; // rotated — configure via Settings > Push Notifications

    public static function get_onesignal_app_id() {
        $cfg = get_option('wpi_push_settings', array());
        return ! empty($cfg['onesignal_app_id']) ? $cfg['onesignal_app_id'] : self::ONESIGNAL_APP_ID;
    }
    public static function get_onesignal_api_key() {
        $cfg = get_option('wpi_push_settings', array());
        return ! empty($cfg['onesignal_api_key']) ? $cfg['onesignal_api_key'] : self::ONESIGNAL_API_KEY;
    }

    public static function send_push( $user_id, $payload ) {
        $title = $payload['title'] ?? 'Audit4me';
        $body  = $payload['body']  ?? 'You have a new notification';
        $url   = $payload['url']   ?? home_url('/?wpi=1#actions');

        // Send via WebToNative Firebase FCM tokens if available.
        // Important: do not stop here. Some Android devices are Web/PWA or may have
        // a web-push subscription rather than a native WTN FCM token. Send all
        // available channels so Samsung/WTN and TCL/PWA/browser devices both work.
        $fcm_tokens = self::get_user_fcm_tokens($user_id);
        if (!empty($fcm_tokens)) {
            self::push_log('send_push route user_id=' . $user_id . ' fcm_tokens=' . count($fcm_tokens) . ' continuing_to_fallbacks=1');
            self::send_push_fcm($fcm_tokens, $title, $body, $url);
        } else {
            self::push_log('send_push route user_id=' . $user_id . ' fcm_tokens=0 continuing_to_fallbacks=1');
        }

        // Always try OneSignal by external user ID (WP user ID)
        $data = array(
            'app_id'               => self::get_onesignal_app_id(),
            'include_external_user_ids' => array( (string)$user_id ),
            'channel_for_external_user_ids' => 'push',
            'headings'             => array('en' => $title),
            'contents'             => array('en' => $body),
            'url'                  => $url,
            'priority'             => 10,
            'ttl'                  => 259200,
        );
        $resp = wp_remote_post( 'https://onesignal.com/api/v1/notifications', array(
            'method'  => 'POST',
            'timeout' => 15,
            'headers' => array(
                'Content-Type'  => 'application/json',
                'Authorization' => 'Key ' . self::get_onesignal_api_key(),
            ),
            'body' => json_encode($data),
        ) );
        $code = wp_remote_retrieve_response_code($resp);
        $resp_body = wp_remote_retrieve_body($resp);
        self::push_log('OneSignal external_id=' . $user_id . ' code=' . $code . ' ' . substr($resp_body,0,200));

        // Also send via VAPID as fallback
        self::send_push_vapid( $user_id, $payload );
    }

    public static function send_push_fcm( $tokens, $title, $body, $url ) {
        // Firebase FCM V1 API using service account
        $service_account = get_option('wpi_firebase_service_account');
        if (!$service_account) return;
        $sa = json_decode($service_account, true);
        if (!$sa || empty($sa['project_id'])) return;

        // Get access token via JWT
        $token = self::get_firebase_access_token($sa);
        if (!$token) return;

        $project_id = $sa['project_id'];
        $dead_tokens = array();
        foreach ($tokens as $fcm_token) {
            $message = array(
                'message' => array(
                    'token' => $fcm_token,
                    'notification' => array(
                        'title' => $title,
                        'body'  => $body,
                    ),
                    'data' => array(
                        'title'    => $title,
                        'body'     => $body,
                        'url'      => $url,
                        'deepLink' => $url,
                    ),
                    'android' => array(
                        'priority' => 'HIGH',
                        'notification' => array(
                            'title' => $title,
                            'body'  => $body,
                        ),
                    ),
                )
            );
            $resp = wp_remote_post(
                "https://fcm.googleapis.com/v1/projects/{$project_id}/messages:send",
                array(
                    'method'  => 'POST',
                    'timeout' => 15,
                    'headers' => array(
                        'Content-Type'  => 'application/json',
                        'Authorization' => 'Bearer ' . $token,
                    ),
                    'body' => json_encode($message),
                )
            );
            $code = wp_remote_retrieve_response_code($resp);
            self::push_log('FCM V1 token='.substr($fcm_token,0,20).'... code='.$code.' '.substr(wp_remote_retrieve_body($resp),0,100));
            if ($code === 404 || $code === 410) $dead_tokens[] = $fcm_token;
        }
        if (!empty($dead_tokens)) {
            foreach (get_users(array('fields'=>'ID')) as $uid) {
                $records = get_user_meta($uid, 'wpi_fcm_device_tokens', true);
                if (is_array($records)) {
                    $changed = false;
                    foreach ($records as $k => $rec) {
                        if (is_array($rec) && !empty($rec['token']) && in_array($rec['token'], $dead_tokens, true)) { unset($records[$k]); $changed = true; }
                    }
                    if ($changed) {
                        if ($records) update_user_meta($uid, 'wpi_fcm_device_tokens', $records); else delete_user_meta($uid, 'wpi_fcm_device_tokens');
                        self::sync_user_fcm_legacy_tokens($uid, $records);
                    }
                } else {
                    $t = get_user_meta($uid, 'wpi_fcm_tokens', true) ?: array();
                    $c = array_values(array_diff($t, $dead_tokens));
                    if (count($c) !== count($t)) update_user_meta($uid, 'wpi_fcm_tokens', $c);
                }
            }
        }
    }

    public static function get_firebase_access_token($sa) {
        $now = time();
        $header = base64_encode(json_encode(array('alg'=>'RS256','typ'=>'JWT')));
        $claims = base64_encode(json_encode(array(
            'iss'   => $sa['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud'   => 'https://oauth2.googleapis.com/token',
            'iat'   => $now,
            'exp'   => $now + 3600,
        )));
        $input = $header . '.' . $claims;
        $key = $sa['private_key'];
        $sig = '';
        if (!openssl_sign($input, $sig, $key, OPENSSL_ALGO_SHA256)) return null;
        $jwt = $input . '.' . rtrim(strtr(base64_encode($sig), '+/', '-_'), '=');
        $resp = wp_remote_post('https://oauth2.googleapis.com/token', array(
            'body' => array(
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion'  => $jwt,
            ),
        ));
        $data = json_decode(wp_remote_retrieve_body($resp), true);
        return $data['access_token'] ?? null;
    }

    public static function send_push_vapid( $user_id, $payload ) {
        $subs = get_user_meta( $user_id, 'wpi_push_subscriptions', true );
        self::push_log('send_push called for user_id=' . $user_id . ' subs=' . (is_array($subs) ? count($subs) : 'none'));
        if ( empty($subs) || ! is_array($subs) ) return;

        $keys = self::get_vapid_keys();
        if ( ! $keys ) return;

        $json    = json_encode( array_merge( array(
            'title' => 'Audit4me',
            'body'  => 'You have a new notification',
            'icon'  => home_url('/wp-content/plugins/wp-inspector/assets/icons/icon-192x192.png'),
            'badge' => home_url('/wp-content/plugins/wp-inspector/assets/icons/icon-192x192.png'),
            'url'   => home_url('/?wpi=1'),
        ), $payload ) );

        $dead_endpoints = array();

        WPI_Ajax::push_log('Sending JSON: ' . substr($json, 0, 200));
        foreach ( $subs as $sub ) {
            if ( empty($sub['endpoint']) ) continue;
            $result = self::vapid_send( $sub, $json, $keys );
            if ( $result === 410 || $result === 404 || $result === 400 ) {
                // Subscription expired — remove it
                $dead_endpoints[] = $sub['endpoint'];
            }
        }

        // Clean up expired subscriptions
        if ( $dead_endpoints ) {
            $subs = array_values( array_filter( $subs, function($s) use ($dead_endpoints) {
                return ! in_array( $s['endpoint'], $dead_endpoints );
            }));
            update_user_meta( $user_id, 'wpi_push_subscriptions', $subs );
        }
    }

    /**
     * Send a push message using VAPID authentication.
     * Returns HTTP response code, or 0 on error.
     */
    private static function vapid_send( $sub, $body, $keys ) {
        $endpoint = $sub['endpoint'];
        $p256dh   = $sub['p256dh'];
        $auth     = $sub['auth'];

        // Parse endpoint origin for VAPID audience
        $parsed   = wp_parse_url( $endpoint );
        $audience = $parsed['scheme'] . '://' . $parsed['host'];

        // Build VAPID JWT
        $header  = self::base64url_encode( json_encode( array('typ'=>'JWT','alg'=>'ES256') ) );
        $claims  = self::base64url_encode( json_encode( array(
            'aud' => $audience,
            'exp' => time() + 43200,
            'sub' => 'mailto:' . get_option('admin_email'),
        ) ) );
        $signing_input = $header . '.' . $claims;

        // Sign with EC private key
        $sig = '';
        openssl_sign( $signing_input, $sig, $keys['private'], OPENSSL_ALGO_SHA256 );

        // Convert DER signature to raw r||s format for JWT
        // DER: 30 [len] 02 [r_len] [r] 02 [s_len] [s]
        // Handle both short (1-byte) and long (2-byte) length encoding
        $offset = 2; // skip 0x30 and length byte
        if (ord($sig[1]) === 0x81) $offset = 3; // long form length
        $offset++; // skip 0x02
        $r_len = ord($sig[$offset++]);
        $r = substr($sig, $offset, $r_len);
        $offset += $r_len;
        $offset++; // skip 0x02
        $s_len = ord($sig[$offset++]);
        $s = substr($sig, $offset, $s_len);
        // Pad/trim to 32 bytes each
        $r = str_pad(ltrim($r, chr(0)), 32, chr(0), STR_PAD_LEFT);
        $s = str_pad(ltrim($s, chr(0)), 32, chr(0), STR_PAD_LEFT);
        $jwt_sig = self::base64url_encode($r . $s);

        $jwt = $signing_input . '.' . $jwt_sig;
        $vapid_pub = $keys['public'];

        // Encrypt the payload using Web Push content encryption (RFC 8291 / aes128gcm)
        // Log PHP version and OpenSSL for debugging
        WPI_Ajax::push_log('VAPID pub key: '.substr($vapid_pub,0,20));
        WPI_Ajax::push_log('PHP: '.PHP_VERSION.' OpenSSL: '.(defined('OPENSSL_VERSION_TEXT')?OPENSSL_VERSION_TEXT:'none').' pkey_derive: '.(function_exists('openssl_pkey_derive')?'yes':'NO'));
        $encrypted = self::encrypt_payload( $body, $p256dh, $auth );
        WPI_Ajax::push_log('encrypt_payload result: ' . ($encrypted !== false ? 'ok len='.strlen($encrypted) : 'FAILED'));

        if ( $encrypted ) {
            $args = array(
                'method'  => 'POST',
                'timeout' => 15,
                'headers' => array(
                    'Content-Type'     => 'application/octet-stream',
                    'Content-Encoding' => 'aes128gcm',
                    'TTL'              => '86400',
                    'Urgency'          => 'high',
                    'Authorization'    => 'vapid t=' . $jwt . ',k=' . $vapid_pub,
                    'apns-push-type'   => 'alert',
                    'apns-priority'    => '10',
                ),
                'body' => $encrypted,
            );
        } else {
            // Send as plaintext JSON
            $args = array(
                'method'  => 'POST',
                'timeout' => 15,
                'headers' => array(
                    'Content-Type'  => 'application/json',
                    'TTL'           => '86400',
                    'Urgency'       => 'high',
                    'Topic'         => 'wpi-action',
                    'Authorization' => 'vapid t=' . $jwt . ',k=' . $vapid_pub,
                ),
                'body' => $body,
            );
        }

        $response = wp_remote_post( $endpoint, $args );
        if ( is_wp_error($response) ) {
            self::push_log('ERROR wp_remote_post: ' . $response->get_error_message() . ' endpoint: ' . $endpoint);
            return 0;
        }
        $code = wp_remote_retrieve_response_code( $response );
        $resp_body = wp_remote_retrieve_body( $response );
        self::push_log('Code: ' . $code . ' Body: ' . substr($resp_body,0,300) . ' Endpoint: ' . substr($endpoint,0,60));
        return $code;
    }

    /**
     * Encrypt push payload using Web Push content encryption (aes128gcm / RFC 8291).
     * Required for Apple Push Notification service via Web Push.
     */
    private static function encrypt_payload( $plaintext, $p256dh_b64, $auth_b64 ) {
        if ( ! function_exists('openssl_encrypt') ) return false;
        if ( ! function_exists('openssl_pkey_new') ) return false;

        try {
            // Decode subscriber keys
            $receiver_pub = self::base64url_decode( $p256dh_b64 );  // 65 bytes uncompressed point
            $auth         = self::base64url_decode( $auth_b64 );     // 16 bytes

            // Generate ephemeral EC key pair
            $ephemeral = openssl_pkey_new( array(
                'curve_name'       => 'prime256v1',
                'private_key_type' => OPENSSL_KEYTYPE_EC,
            ) );
            if ( ! $ephemeral ) return false;

            $eph_details = openssl_pkey_get_details( $ephemeral );
            if ( ! isset( $eph_details['ec']['x'], $eph_details['ec']['y'] ) ) return false;

            $eph_pub_raw = chr(4)
                . str_pad( $eph_details['ec']['x'], 32, chr(0), STR_PAD_LEFT )
                . str_pad( $eph_details['ec']['y'], 32, chr(0), STR_PAD_LEFT );

            // Reconstruct receiver public key as PEM for ECDH
            $receiver_x = substr( $receiver_pub, 1, 32 );
            $receiver_y = substr( $receiver_pub, 33, 32 );

            // Build receiver public key PEM
            $seq = pack('H*', '3059301306072a8648ce3d020106082a8648ce3d030107034200') . $receiver_pub;
            $receiver_pem = "-----BEGIN PUBLIC KEY-----
"
                . chunk_split( base64_encode( $seq ), 64, "
" )
                . "-----END PUBLIC KEY-----";

            $receiver_key = openssl_pkey_get_public( $receiver_pem );
            if ( ! $receiver_key ) return false;

            // ECDH shared secret
            if ( ! openssl_pkey_derive( $receiver_key, $ephemeral, $shared_secret ) ) return false;

            // HKDF for Web Push key derivation (RFC 8291)
            $salt = random_bytes(16);

            // ikm = HKDF-Extract(auth, ECDH result || "WebPush: info " || receiver_pub || eph_pub)
            $info_key = "WebPush: info " . $receiver_pub . $eph_pub_raw;
            $ikm = self::hkdf( $auth, $shared_secret, $info_key, 32 );

            // Derive content encryption key (16 bytes) and nonce (12 bytes)
            $cek   = self::hkdf( $salt, $ikm, "Content-Encoding: aes128gcm ", 16 );
            $nonce = self::hkdf( $salt, $ikm, "Content-Encoding: nonce ", 12 );

            // Encrypt: AES-128-GCM
            $padded     = $plaintext . ""; // record delimiter
            $tag        = '';
            $ciphertext = openssl_encrypt( $padded, 'aes-128-gcm', $cek, OPENSSL_RAW_DATA, $nonce, $tag, '', 16 );
            if ( $ciphertext === false ) return false;

            // Build aes128gcm content: salt(16) + rs(4) + idlen(1) + keyid(eph_pub) + ciphertext + tag
            $rs     = pack( 'N', 4096 );  // record size
            $idlen  = chr( strlen($eph_pub_raw) );
            $record = $salt . $rs . $idlen . $eph_pub_raw . $ciphertext . $tag;

            return $record;

        } catch ( Exception $e ) {
            return false;
        }
    }

    /**
     * HKDF (RFC 5869) using SHA-256
     */
    private static function hkdf( $salt, $ikm, $info, $length ) {
        $prk    = hash_hmac( 'sha256', $ikm, $salt, true );
        $output = '';
        $T      = '';
        $i      = 1;
        while ( strlen($output) < $length ) {
            $T      = hash_hmac( 'sha256', $T . $info . chr($i), $prk, true );
            $output .= $T;
            $i++;
        }
        return substr( $output, 0, $length );
    }

    /* ── Activity Log ──────────────────────────────────────────────── */

    /**
     * Log an activity event.
     * @param string $object_type  'inspection' | 'template'
     * @param int    $object_id    ID of the object
     * @param string $action       e.g. 'created', 'updated', 'completed', 'deleted', 'question_added'
     * @param string $detail       Optional human-readable detail
     */
    public static function log( $object_type, $object_id, $action, $detail = '' ) {
        // Fire-and-forget — never let logging break core operations
        try {
            global $wpdb;
            $wpdb->hide_errors();
            if ( ! $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}wpi_activity_log'" ) ) return;
            $uid  = get_current_user_id();
            $user = $uid ? get_userdata( $uid ) : null;
            $name = '';
            if ( $user ) {
                $first = get_user_meta( $uid, 'first_name', true );
                $last  = get_user_meta( $uid, 'last_name', true );
                $name  = trim( "$first $last" ) ?: $user->display_name;
            }
            $org_id = 0;
            $org_row = $wpdb->get_var( $wpdb->prepare(
                "SELECT org_id FROM {$wpdb->prefix}wpi_user_roles WHERE user_id=%d LIMIT 1", $uid
            ) );
            if ( $org_row ) $org_id = (int)$org_row;
            $wpdb->insert( $wpdb->prefix . 'wpi_activity_log', array(
                'object_type' => sanitize_text_field( $object_type ),
                'object_id'   => absint( $object_id ),
                'action'      => sanitize_text_field( $action ),
                'detail'      => sanitize_text_field( $detail ),
                'user_id'     => $uid,
                'user_name'   => $name,
                'org_id'      => $org_id,
                'created_at'  => current_time( 'mysql' ),
            ) );
        } catch ( Exception $e ) {
            // Silently swallow
        }
    }

    /* ── Corrective Actions ────────────────────────────────────── */

    /* ── Session Management ─────────────────────────────────────── */

    /**
     * System owner device control.
     * wpi_max_sessions = default total devices per organisation. 0 = unlimited.
     * organisation max_sessions = total active devices allowed for that organisation.
     * user meta wpi_user_max_devices = active devices allowed for that username.
     */
    private function get_system_max_sessions() {
        $opt = get_option('wpi_max_sessions', null);
        if ( $opt !== null && $opt !== false && $opt !== '' ) return (int) $opt;
        $settings = get_option('wpi_system_settings', array());
        return isset($settings['max_sessions']) ? (int)$settings['max_sessions'] : 5;
    }

    private function get_max_sessions_for_org( $org_id ) {
        global $wpdb;
        if ( $org_id ) {
            $org_max = $wpdb->get_var( $wpdb->prepare(
                "SELECT max_sessions FROM `{$wpdb->prefix}wpi_organisations` WHERE id=%d", $org_id
            ) );
            if ( $org_max !== null && $org_max !== '' ) return (int)$org_max;
        }
        return $this->get_system_max_sessions();
    }

    private function get_user_device_limit( $uid, $org_id = 0 ) {
        $u = get_user_meta( (int)$uid, 'wpi_user_max_devices', true );
        if ( $u !== '' && $u !== null ) return (int)$u;
        $default = (int) get_option('wpi_default_user_devices', 1);
        return max(0, $default);
    }

    /**
     * Get or create a session key for the current user/device.
     * Stored in a transient scoped to the WP session.
     */
    private function get_or_create_session_key() {
        $uid = get_current_user_id();

        // Prefer client-supplied device_id (a UUID generated and stored in
        // localStorage by the app). This gives each browser/app instance a
        // stable unique identity that survives IP changes and works correctly
        // when multiple same-UA devices are logged in simultaneously.
        $body_device_id = '';
        if ( isset( $_POST['body'] ) ) {
            $tmp = json_decode( stripslashes( $_POST['body'] ), true );
            if ( is_array( $tmp ) && ! empty( $tmp['device_id'] ) ) {
                $body_device_id = $tmp['device_id'];
            }
        }
        $device_id = sanitize_text_field( $_POST['device_id'] ?? $_GET['device_id'] ?? $body_device_id );
        if ( $device_id && preg_match('/^[0-9a-f\-]{32,40}$/i', $device_id) ) {
            // Bind device_id to this user so one user can't hijack another's key
            return 'dev_' . $uid . '_' . $device_id;
        }

        // Fallback for requests that don't send device_id (e.g. page load)
        $ua_hash       = md5( $_SERVER['HTTP_USER_AGENT'] ?? 'unknown' );
        $cookie_hash   = md5( $_COOKIE[COOKIEHASH] ?? session_id() ?: 'x' );
        $transient_key = 'wpi_sk_' . $uid . '_' . md5( $cookie_hash . $ua_hash );
        $key = get_transient($transient_key);
        if (!$key) {
            $key = wp_generate_password(32, false);
            set_transient($transient_key, $key, 24 * HOUR_IN_SECONDS);
        }
        return $key;
    }

    /**
     * Count active devices for an org (active = last_active within 15 min).
     */
    private function count_active_sessions( $org_id ) {
        $this->ensure_device_history_columns();
        global $wpdb;
        $cutoff = date('Y-m-d H:i:s', time() - 15 * 60);
        if ($org_id) {
            return (int)$wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(DISTINCT session_key) FROM `{$wpdb->prefix}wpi_sessions`
                 WHERE org_id=%d AND status='active' AND last_active >= %s", $org_id, $cutoff
            ));
        }
        return 0;
    }

    /**
     * Check if session is allowed. Returns true if OK, false if blocked.
     */
    private function check_session_allowed( $uid, $org_id, $session_key ) {
        $this->ensure_device_history_columns();
        global $wpdb;

        $cutoff = date('Y-m-d H:i:s', time() - 15 * 60);

        // Returning device: allow and refresh.
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM `{$wpdb->prefix}wpi_sessions` WHERE session_key=%s AND status='active'", $session_key
        ));
        if ($existing) return true;

        // Per-username/device limit, controlled by System Admin.
        $user_max = $this->get_user_device_limit( $uid, $org_id );
        if ( $user_max > 0 ) {
            $user_active = (int)$wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(DISTINCT session_key) FROM `{$wpdb->prefix}wpi_sessions`
                 WHERE user_id=%d AND status='active' AND last_active >= %s",
                $uid, $cutoff
            ));
            if ( $user_active >= $user_max ) return false;
        }

        // Total organisation device limit, also controlled by System Admin.
        $org_max = $this->get_max_sessions_for_org($org_id);
        if ( $org_id && $org_max > 0 ) {
            $org_active = (int)$wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(DISTINCT session_key) FROM `{$wpdb->prefix}wpi_sessions`
                 WHERE org_id=%d AND status='active' AND last_active >= %s",
                (int)$org_id, $cutoff
            ));
            if ( $org_active >= $org_max ) return false;
        }

        return true;
    }


    /**
     * Make sure the device/session table supports permanent device history.
     * Removed/blocked/expired devices are kept for audit history and do not count toward active limits.
     */
    private function ensure_device_history_columns() {
        global $wpdb;
        static $done = false;
        if ( $done ) return;
        $done = true;
        $table = $wpdb->prefix . 'wpi_sessions';
        $cols = $wpdb->get_col( "SHOW COLUMNS FROM `$table`", 0 );
        if ( ! is_array( $cols ) ) return;
        if ( ! in_array( 'status', $cols, true ) ) {
            $wpdb->query( "ALTER TABLE `$table` ADD status VARCHAR(20) NOT NULL DEFAULT 'active' AFTER ip_address" );
            $wpdb->query( "ALTER TABLE `$table` ADD INDEX status (status)" );
        }
        if ( ! in_array( 'removed_at', $cols, true ) ) {
            $wpdb->query( "ALTER TABLE `$table` ADD removed_at DATETIME NULL AFTER created_at" );
        }
        if ( ! in_array( 'blocked_at', $cols, true ) ) {
            $wpdb->query( "ALTER TABLE `$table` ADD blocked_at DATETIME NULL AFTER removed_at" );
        }
        if ( ! in_array( 'expired_at', $cols, true ) ) {
            $wpdb->query( "ALTER TABLE `$table` ADD expired_at DATETIME NULL AFTER blocked_at" );
        }
    }

    /**
     * Parse a user-agent string into a friendly device label.
     */
    private function parse_device( $raw ) {
        $raw = (string) $raw;
        // Handle JSON format (new) vs plain UA string (legacy).
        $decoded = @json_decode( $raw, true );

        // Prefer the client-side detected OS/browser. This is more accurate for
        // iPhone/iPad WebViews because some wrappers/proxies can send a generic
        // Android/Chrome server user-agent.
        if ( is_array( $decoded ) ) {
            $client_os = isset( $decoded['os'] ) ? sanitize_text_field( $decoded['os'] ) : '';
            $client_br = isset( $decoded['browser'] ) ? sanitize_text_field( $decoded['browser'] ) : '';
            if ( $client_os || $client_br ) {
                return trim( ( $client_os ?: 'Device' ) . ' / ' . ( $client_br ?: 'Browser' ) );
            }
        }

        $ua = $decoded && isset( $decoded['ua'] ) ? $decoded['ua'] : ( $decoded['server_ua'] ?? $raw );
        $platform = $decoded && isset( $decoded['platform'] ) ? $decoded['platform'] : '';

        // OS
        if      ( stripos( $ua, 'iPhone' ) !== false || stripos( $platform, 'iPhone' ) !== false ) $os = 'iPhone';
        elseif  ( stripos( $ua, 'iPad' ) !== false || stripos( $platform, 'iPad' ) !== false )     $os = 'iPad';
        elseif  ( stripos( $ua, 'Android' ) !== false )                                           $os = 'Android';
        elseif  ( stripos( $ua, 'Windows' ) !== false )                                           $os = 'Windows';
        elseif  ( stripos( $ua, 'Macintosh' ) !== false || stripos( $platform, 'Mac' ) !== false ) $os = 'Mac';
        elseif  ( stripos( $ua, 'Linux' ) !== false )                                             $os = 'Linux';
        else                                                                                      $os = 'Unknown';
        // Browser
        if      ( ( stripos( $ua, 'CriOS' ) !== false || stripos( $ua, 'Chrome' ) !== false ) && stripos( $ua, 'Edg' ) === false ) $br = 'Chrome';
        elseif  ( stripos( $ua, 'Edg' ) !== false )      $br = 'Edge';
        elseif  ( stripos( $ua, 'FxiOS' ) !== false || stripos( $ua, 'Firefox' ) !== false )  $br = 'Firefox';
        elseif  ( stripos( $ua, 'Safari' ) !== false )   $br = 'Safari';
        elseif  ( stripos( $ua, 'MSIE' ) !== false || stripos( $ua, 'Trident' ) !== false ) $br = 'IE';
        else                                          $br = 'Browser';
        return "$os / $br";
    }

    /* ── Session / Device Tracking ──────────────────────────── */

    public function wpi_ping() {
        $this->ensure_device_history_columns();
        $this->check_nonce();
        global $wpdb;
        $uid     = get_current_user_id();
        $org_id  = $this->get_org_id();
        $now     = current_time( 'mysql' );
        $body    = $this->input();
        $session_key = $this->get_or_create_session_key();
        $ua      = substr( $_SERVER['HTTP_USER_AGENT'] ?? '', 0, 200 );
        $device_id = sanitize_text_field( $body['device_id'] ?? ( $_GET['device_id'] ?? '' ) );
        $client_info = array();
        if ( ! empty( $body['device_info'] ) ) {
            $tmp = is_array( $body['device_info'] ) ? $body['device_info'] : @json_decode( stripslashes( (string) $body['device_info'] ), true );
            if ( is_array( $tmp ) ) {
                $client_info = array(
                    'ua'             => isset( $tmp['ua'] ) ? substr( sanitize_text_field( $tmp['ua'] ), 0, 250 ) : '',
                    'platform'       => isset( $tmp['platform'] ) ? substr( sanitize_text_field( $tmp['platform'] ), 0, 80 ) : '',
                    'os'             => isset( $tmp['os'] ) ? substr( sanitize_text_field( $tmp['os'] ), 0, 40 ) : '',
                    'browser'        => isset( $tmp['browser'] ) ? substr( sanitize_text_field( $tmp['browser'] ), 0, 40 ) : '',
                    'maxTouchPoints' => isset( $tmp['maxTouchPoints'] ) ? absint( $tmp['maxTouchPoints'] ) : 0,
                    'standalone'     => ! empty( $tmp['standalone'] ),
                );
            }
        }
        $device  = json_encode( array_merge( array( 'server_ua' => $ua ), $client_info, array( 'did' => $device_id ) ), JSON_UNESCAPED_SLASHES );
        $device  = substr( $device, 0, 700 );
        $ip      = $_SERVER['REMOTE_ADDR'] ?? '';

        $allowed = $this->check_session_allowed( $uid, $org_id, $session_key );

        if ( $allowed ) {
            $wpdb->query( $wpdb->prepare(
                "INSERT INTO `{$wpdb->prefix}wpi_sessions`
                 (user_id, org_id, session_key, device_info, ip_address, status, last_active, created_at)
                 VALUES (%d,%d,%s,%s,%s,'active',%s,%s)
                 ON DUPLICATE KEY UPDATE status='active', removed_at=NULL, expired_at=NULL, last_active=%s, device_info=%s",
                $uid, $org_id, $session_key, $device, $ip, $now, $now, $now, $device
            ) );
            // Clean up sessions inactive > 24h
            $wpdb->query( "UPDATE `{$wpdb->prefix}wpi_sessions`
                SET status='expired', expired_at=IFNULL(expired_at, NOW())
                WHERE status='active' AND last_active < DATE_SUB(NOW(), INTERVAL 24 HOUR)" );
        }

        $max    = $org_id ? $this->get_max_sessions_for_org( $org_id ) : 5;
        $active = $org_id ? $this->count_active_sessions( $org_id ) : 0;
        $this->json( array(
            'success' => true,
            'allowed' => $allowed,
            'active'  => $active,
            'max'     => $max,
        ) );
    }

    public function wpi_get_sessions() {
        $this->ensure_device_history_columns();
        $this->check_nonce();
        if ( ! $this->is_system_owner() ) {
            $this->error( 'Only the System Owner can manage all devices.', 403 );
        }
        global $wpdb;
        $is_sys    = $this->is_system_owner();
        $org_id    = absint( $_GET['org_id'] ?? 0 ) ?: $this->get_org_id();
        $cutoff    = date( 'Y-m-d H:i:s', time() - 15 * 60 );
        $org_where = ( $is_sys && ! $org_id ) ? '' : $wpdb->prepare( ' AND s.org_id=%d', $org_id );

        $sql = "SELECT s.id, s.user_id, s.session_key, s.device_info, s.ip_address, s.status, s.last_active, s.created_at,
                    COALESCE(NULLIF(TRIM(CONCAT(COALESCE(um1.meta_value,''),' ',COALESCE(um2.meta_value,''))), ''), u.display_name) AS user_name
             FROM `{$wpdb->prefix}wpi_sessions` s
             LEFT JOIN `{$wpdb->users}` u ON u.ID = s.user_id
             LEFT JOIN `{$wpdb->usermeta}` um1 ON um1.user_id = s.user_id AND um1.meta_key='first_name'
             LEFT JOIN `{$wpdb->usermeta}` um2 ON um2.user_id = s.user_id AND um2.meta_key='last_name'
             WHERE (s.status='active' OR s.last_active >= DATE_SUB(NOW(), INTERVAL 180 DAY))$org_where
             ORDER BY s.user_id ASC, s.last_active DESC";
        $rows = $wpdb->get_results( $sql );

        foreach ( $rows as $r ) {
            $r->device_label    = $this->parse_device( $r->device_info );
            $info               = @json_decode( $r->device_info, true );
            $did                = $info && isset( $info['did'] ) ? $info['did'] : '';
            // Android ID is typically 16 hex chars; UUID is 36 chars with dashes
            $is_android_id      = $did && strlen( $did ) <= 20 && ctype_xdigit( str_replace('-', '', $did ) );
            $r->device_id_short = $did ? ( $is_android_id ? strtoupper( $did ) : strtoupper( substr( $did, 0, 8 ) ) ) : '';
            $r->is_current      = false;
        }

        $max = $org_id ? $this->get_max_sessions_for_org( $org_id ) : 5;
        $active_count = $org_id ? $this->count_active_sessions( $org_id ) : 0;
        $this->json( array( 'sessions' => $rows ?: array(), 'max' => $max, 'active' => $active_count ) );
    }

    public function wpi_get_my_sessions() {
        $this->ensure_device_history_columns();
        $this->check_nonce();
        global $wpdb;
        $uid         = get_current_user_id();
        $org_id      = $this->get_org_id();
        $current_key = $this->get_or_create_session_key();
        $now         = current_time( 'mysql' );
        $device_id   = sanitize_text_field( $_GET['device_id'] ?? $_POST['device_id'] ?? '' );
        $ua          = substr( $_SERVER['HTTP_USER_AGENT'] ?? '', 0, 200 );
        $client_info = array();
        $client_raw  = $_GET['device_info'] ?? $_POST['device_info'] ?? '';
        if ( $client_raw ) {
            $tmp = is_array( $client_raw ) ? $client_raw : @json_decode( stripslashes( (string) $client_raw ), true );
            if ( is_array( $tmp ) ) {
                $client_info = array(
                    'ua'             => isset( $tmp['ua'] ) ? substr( sanitize_text_field( $tmp['ua'] ), 0, 250 ) : '',
                    'platform'       => isset( $tmp['platform'] ) ? substr( sanitize_text_field( $tmp['platform'] ), 0, 80 ) : '',
                    'os'             => isset( $tmp['os'] ) ? substr( sanitize_text_field( $tmp['os'] ), 0, 40 ) : '',
                    'browser'        => isset( $tmp['browser'] ) ? substr( sanitize_text_field( $tmp['browser'] ), 0, 40 ) : '',
                    'maxTouchPoints' => isset( $tmp['maxTouchPoints'] ) ? absint( $tmp['maxTouchPoints'] ) : 0,
                    'standalone'     => ! empty( $tmp['standalone'] ),
                );
            }
        }
        $device      = substr( json_encode( array_merge( array( 'server_ua' => $ua ), $client_info, array( 'did' => $device_id ) ), JSON_UNESCAPED_SLASHES ), 0, 700 );
        $ip          = $_SERVER['REMOTE_ADDR'] ?? '';

        // Self-register/refresh the current device when the My Devices page is opened.
        // This fixes cases where the background ping failed or had not run yet.
        if ( $uid && $this->check_session_allowed( $uid, $org_id, $current_key ) ) {
            $wpdb->query( $wpdb->prepare(
                "INSERT INTO `{$wpdb->prefix}wpi_sessions`
                 (user_id, org_id, session_key, device_info, ip_address, status, last_active, created_at)
                 VALUES (%d,%d,%s,%s,%s,'active',%s,%s)
                 ON DUPLICATE KEY UPDATE status='active', removed_at=NULL, expired_at=NULL, last_active=%s, device_info=%s, ip_address=%s",
                $uid, $org_id, $current_key, $device, $ip, $now, $now, $now, $device, $ip
            ) );
        }

        $cutoff      = date( 'Y-m-d H:i:s', time() - 180 * 24 * 60 * 60 );

        // WPI FIX: Permanent device history - no time cutoff.
        // Every unique device stays in the list until explicitly removed.
        // Active devices appear first; removed/expired shown below as history.
        $rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT id, session_key, device_info, ip_address, status, last_active, created_at
             FROM `{$wpdb->prefix}wpi_sessions`
             WHERE user_id=%d
             ORDER BY CASE WHEN status='active' THEN 0 ELSE 1 END ASC, last_active DESC",
            $uid
        ) );

        $max = $this->get_user_device_limit( $uid, $org_id );
        if ( $max <= 0 ) $max = $org_id ? $this->get_max_sessions_for_org( $org_id ) : 5;

        foreach ( $rows as $r ) {
            $r->device_label    = $this->parse_device( $r->device_info );
            $info               = @json_decode( $r->device_info, true );
            $did                = $info && isset( $info['did'] ) ? $info['did'] : '';
            // Android ID is typically 16 hex chars; UUID is 36 chars with dashes
            $is_android_id      = $did && strlen( $did ) <= 20 && ctype_xdigit( str_replace('-', '', $did ) );
            $r->device_id_short = $did ? ( $is_android_id ? strtoupper( $did ) : strtoupper( substr( $did, 0, 8 ) ) ) : '';
            $r->is_current      = ( $r->session_key === $current_key );
            $r->user_name       = wp_get_current_user()->display_name;
            $r->user_id         = $uid;
        }

        // WPI FIX: active count = all status='active' sessions (permanent, no time window).
        $active_count = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(DISTINCT session_key) FROM `{$wpdb->prefix}wpi_sessions`
             WHERE user_id=%d AND status='active'",
            $uid
        ) );
        $this->json( array( 'sessions' => $rows ?: array(), 'max' => $max, 'active' => $active_count ) );
    }

    public function wpi_kick_session() {
        $this->ensure_device_history_columns();
        $this->check_nonce();
        if ( ! $this->is_system_owner() ) {
            $this->error( 'Only the System Owner can manage all devices.', 403 );
        }
        global $wpdb;
        $body = $this->input();
        $id   = absint( $body['id'] ?? 0 );
        if ( ! $id ) $this->error( 'id required' );
        $wpdb->update( $wpdb->prefix . 'wpi_sessions', array( 'status' => 'removed', 'removed_at' => current_time( 'mysql' ) ), array( 'id' => $id ) );
        $this->json( array( 'success' => true ) );
    }

    public function wpi_kick_my_session() {
        $this->ensure_device_history_columns();
        $this->check_nonce();
        $body = $this->input();
        $device_info = $body['device_info'] ?? '';
        if ( ! $this->wpi_is_desktop_device_request( $device_info ) ) {
            $this->error( 'Devices can only be removed from the desktop web view.', 403 );
        }
        global $wpdb;
        $uid  = get_current_user_id();
        $id   = absint( $body['id'] ?? 0 );
        if ( ! $id ) $this->error( 'id required' );
        $wpdb->update( $wpdb->prefix . 'wpi_sessions', array( 'status' => 'removed', 'removed_at' => current_time( 'mysql' ) ), array( 'id' => $id, 'user_id' => $uid ) );
        $this->json( array( 'success' => true ) );
    }

    public function wpi_get_session_limit() {
        $this->check_nonce();
        $org_id = $this->get_org_id();
        $max    = $org_id ? $this->get_max_sessions_for_org( $org_id ) : 5;
        $active = $org_id ? $this->count_active_sessions( $org_id ) : 0;
        $this->json( array( 'max' => $max, 'active' => $active ) );
    }



    private function wpi_is_desktop_device_request( $raw_info = null ) {
        $ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? (string) $_SERVER['HTTP_USER_AGENT'] : '';
        $info = array();
        if ( $raw_info === null ) {
            $body = $this->input();
            $raw_info = $body['device_info'] ?? $_POST['device_info'] ?? $_GET['device_info'] ?? '';
        }
        if ( $raw_info ) {
            $tmp = is_array( $raw_info ) ? $raw_info : @json_decode( stripslashes( (string) $raw_info ), true );
            if ( is_array( $tmp ) ) $info = $tmp;
        }
        $client_ua = isset( $info['ua'] ) ? (string) $info['ua'] : $ua;
        $os        = strtolower( (string) ( $info['os'] ?? '' ) );
        $platform  = strtolower( (string) ( $info['platform'] ?? '' ) );
        $browser   = strtolower( (string) ( $info['browser'] ?? '' ) );
        $standalone = ! empty( $info['standalone'] ) || ( isset( $info['displayMode'] ) && $info['displayMode'] === 'standalone' );
        $haystack = strtolower( $client_ua . ' ' . $os . ' ' . $platform . ' ' . $browser );
        $is_mobile = (bool) preg_match( '/iphone|ipad|ipod|android|mobile|tablet|webview|wv|app webview/', $haystack );
        return ( ! $is_mobile && ! $standalone );
    }

    /**
     * Pre-login: check if a username/email is at device limit.
     * Returns active sessions (with session_key for removal) if over limit.
     * No authentication required — uses username only, exposes no sensitive data.
     */
    public function wpi_check_device_limit() {
        $this->ensure_device_history_columns();
        global $wpdb;
        $body     = $this->input();
        $login    = sanitize_text_field( $body['log'] ?? $body['username'] ?? '' );
        if ( ! $login ) wp_send_json_success( array( 'at_limit' => false ) );

        $user = get_user_by( 'login', $login );
        if ( ! $user ) $user = get_user_by( 'email', $login );
        if ( ! $user ) wp_send_json_success( array( 'at_limit' => false ) );

        $uid    = $user->ID;
        $org_id = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT org_id FROM {$wpdb->prefix}wpi_org_users WHERE user_id=%d LIMIT 1", $uid
        ) );
        $user_max = $this->get_user_device_limit( $uid, $org_id );
        $org_max  = $org_id ? $this->get_max_sessions_for_org( $org_id ) : 0;

        // PWA/Web login sends a localStorage device_id before authentication.
        // If this exact device is already registered, do not block it just
        // because the user/organisation is already at the device limit.
        $current_device_id = sanitize_text_field( $body['device_id'] ?? '' );
        $current_session_key = $current_device_id ? ( 'dev_' . $uid . '_' . $current_device_id ) : '';
        if ( $current_session_key ) {
            $existing_current = $wpdb->get_var( $wpdb->prepare(
                "SELECT id FROM `{$wpdb->prefix}wpi_sessions` WHERE user_id=%d AND session_key=%s AND status='active' LIMIT 1",
                $uid, $current_session_key
            ) );
            if ( $existing_current ) {
                wp_send_json_success( array(
                    'at_limit' => false,
                    'active'   => 1,
                    'max'      => $user_max,
                    'org_active' => 0,
                    'org_max'    => $org_max,
                    'devices'  => array(),
                    'user_id'  => $uid,
                    'can_remove' => $this->wpi_is_desktop_device_request( $body['device_info'] ?? '' ),
                ) );
            }
        }

        $cutoff  = date( 'Y-m-d H:i:s', time() - 15 * 60 );
        $rows    = $wpdb->get_results( $wpdb->prepare(
            "SELECT id, session_key, device_info, status, last_active
             FROM `{$wpdb->prefix}wpi_sessions`
             WHERE user_id=%d AND status='active' AND last_active >= %s
             ORDER BY last_active DESC",
            $uid, $cutoff
        ) );

        $devices = array();
        foreach ( $rows as $r ) {
            $info   = @json_decode( $r->device_info, true );
            $did    = $info && isset( $info['did'] ) ? $info['did'] : '';
            $is_android = $did && strlen( $did ) <= 20 && ctype_xdigit( str_replace( '-', '', $did ) );
            $devices[] = array(
                'session_id'   => (int) $r->id,   // opaque DB id — session_key never exposed pre-login
                'device_label' => $this->parse_device( $r->device_info ),
                'device_id'    => $did ? ( $is_android ? strtoupper( $did ) : strtoupper( substr( $did, 0, 8 ) ) ) : '',
                'last_active'  => $r->last_active,
            );
        }

        $user_active = count( $rows );
        $org_active = 0;
        if ( $org_id ) {
            $org_active = (int)$wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(DISTINCT session_key) FROM `{$wpdb->prefix}wpi_sessions` WHERE org_id=%d AND status='active' AND last_active >= %s",
                $org_id, $cutoff
            ));
        }
        $at_limit = ( $user_max > 0 && $user_active >= $user_max ) || ( $org_max > 0 && $org_active >= $org_max );

        if ( ! $at_limit ) {
            wp_send_json_success( array( 'at_limit' => false ) );
            return;
        }

        wp_send_json_success( array(
            'at_limit'   => true,
            'active'     => $user_active,
            'max'        => $user_max,
            'org_active' => $org_active,
            'org_max'    => $org_max,
            'devices'    => $devices,
            'user_id'    => $uid,
            'can_remove' => $this->wpi_is_desktop_device_request( $body['device_info'] ?? '' ),
        ) );
    }

    /**
     * Pre-login: remove a specific session by session_key + user_id.
     * Safe because: session_key was returned by wpi_check_device_limit for that user,
     * and we re-verify the user_id matches before deleting.
     */
    public function wpi_remove_device_for_login() {
        $nonce = sanitize_text_field( $_POST['nonce'] ?? '' );
        if ( ! $nonce || ! wp_verify_nonce( $nonce, 'wpi_login' ) ) {
            wp_send_json_error( array( 'message' => 'Invalid request.' ), 403 );
            return;
        }
        $this->ensure_device_history_columns();
        $body       = $this->input();
        if ( ! $this->wpi_is_desktop_device_request( $body['device_info'] ?? '' ) ) {
            wp_send_json_error( array( 'message' => 'Devices can only be removed from the desktop web view.' ), 403 );
        }
        global $wpdb;
        $session_id = absint( $body['session_id'] ?? 0 );  // Use opaque DB id — never accept raw session_key pre-login
        $user_id    = absint( $body['user_id'] ?? 0 );
        if ( ! $session_id || ! $user_id ) {
            wp_send_json_error( array( 'message' => 'Invalid request' ) );
            return;
        }
        // Verify the session belongs to the claimed user_id before deleting
        $updated = $wpdb->update(
            $wpdb->prefix . 'wpi_sessions',
            array( 'status' => 'removed', 'removed_at' => current_time( 'mysql' ) ),
            array( 'id' => $session_id, 'user_id' => $user_id ),
            array( '%s', '%s' ),
            array( '%d', '%d' )
        );
        wp_send_json_success( array( 'removed' => (bool) $updated ) );
    }





    public function wpi_get_device_control() {
        $this->check_nonce();
        if ( ! $this->is_system_owner() ) $this->error('Access denied', 403);
        global $wpdb;
        $orgs = $wpdb->get_results( "SELECT id, name, max_sessions FROM {$wpdb->prefix}wpi_organisations ORDER BY name ASC" );
        $users = $wpdb->get_results( "SELECT u.ID as user_id, u.display_name, u.user_email, ou.org_id, o.name as org_name
            FROM {$wpdb->users} u
            LEFT JOIN {$wpdb->prefix}wpi_org_users ou ON ou.user_id=u.ID
            LEFT JOIN {$wpdb->prefix}wpi_organisations o ON o.id=ou.org_id
            ORDER BY u.display_name ASC LIMIT 500" );
        foreach ( $users as $u ) {
            $u->max_devices = get_user_meta( (int)$u->user_id, 'wpi_user_max_devices', true );
            if ( $u->max_devices === '' ) $u->max_devices = null;
        }
        $this->json(array(
            'system_org_default' => $this->get_system_max_sessions(),
            'default_user_devices' => (int)get_option('wpi_default_user_devices', 1),
            'allow_user_device_remove' => (int)get_option('wpi_allow_user_device_remove', 0),
            'allow_web_device_remove' => (int)get_option('wpi_allow_web_device_remove', 0),
            'orgs' => $orgs ?: array(),
            'users' => $users ?: array(),
        ));
    }

    public function wpi_save_device_control() {
        $this->check_nonce();
        if ( ! $this->is_system_owner() ) $this->error('Access denied', 403);
        global $wpdb;
        $body = $this->input();
        if ( isset($body['system_org_default']) ) update_option('wpi_max_sessions', max(0, absint($body['system_org_default'])), false);
        if ( isset($body['default_user_devices']) ) update_option('wpi_default_user_devices', max(0, absint($body['default_user_devices'])), false);
        if ( isset($body['allow_user_device_remove']) ) update_option('wpi_allow_user_device_remove', absint($body['allow_user_device_remove']) ? 1 : 0, false);
        if ( isset($body['allow_web_device_remove']) ) update_option('wpi_allow_web_device_remove', absint($body['allow_web_device_remove']) ? 1 : 0, false);
        if ( isset($body['org_id']) ) {
            $org_id = absint($body['org_id']);
            $limit  = isset($body['org_max_devices']) && $body['org_max_devices'] !== '' ? max(0, absint($body['org_max_devices'])) : null;
            $wpdb->update($wpdb->prefix.'wpi_organisations', array('max_sessions'=>$limit), array('id'=>$org_id), array('%d'), array('%d'));
        }
        if ( isset($body['user_id']) ) {
            $uid = absint($body['user_id']);
            if ( isset($body['user_max_devices']) && $body['user_max_devices'] !== '' ) update_user_meta($uid, 'wpi_user_max_devices', max(0, absint($body['user_max_devices'])));
            else delete_user_meta($uid, 'wpi_user_max_devices');
        }
        $this->json(array('success'=>true));
    }

    public function wpi_set_user_device_limit() {
        $this->wpi_save_device_control();
    }

    /* ── Corrective Actions ───────────────────────────────────── */

    public function wpi_create_action() {
        $this->check_nonce();
        if ( ! $this->can( 'standard' ) ) $this->error( 'Access denied', 403 );
        global $wpdb;

        $body = $this->input();
        $inspection_id    = absint( $body['inspection_id'] ?? 0 );
        $question_id      = sanitize_text_field( $body['question_id'] ?? '' );
        $question_label   = sanitize_text_field( $body['question_label'] ?? 'Action' );
        $question_answer  = sanitize_text_field( $body['question_answer'] ?? '' );
        $question_note    = sanitize_textarea_field( $body['question_note'] ?? '' );
        $note             = sanitize_textarea_field( $body['note'] ?? '' );
        $assigned_to      = absint( $body['assigned_to'] ?? 0 );
        $due_date_raw     = sanitize_text_field( $body['due_date'] ?? '' );
        $priority         = sanitize_text_field( $body['priority'] ?? 'medium' );
        $org_id           = $this->get_org_id();
        $created_by       = get_current_user_id();

        if ( ! $inspection_id ) $this->error( 'inspection_id required' );
        if ( ! $assigned_to ) $this->error( 'Please select a user to assign to' );
        if ( ! in_array( $priority, array( 'low', 'medium', 'high', 'critical' ), true ) ) $priority = 'medium';

        // Browser date inputs should send YYYY-MM-DD. Accept AU/display dates as a safe fallback.
        $due_date = '';
        if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $due_date_raw ) ) {
            $due_date = $due_date_raw;
        } elseif ( $due_date_raw ) {
            $ts = strtotime( str_replace( '/', '-', $due_date_raw ) );
            if ( $ts ) $due_date = date( 'Y-m-d', $ts );
        }
        if ( ! $due_date ) $this->error( 'Please select a valid due date' );

        $user = get_userdata( $assigned_to );
        if ( ! $user ) $this->error( 'Assigned user not found' );

        // Keep actions inside the same organisation unless this is the system owner.
        if ( ! $this->is_system_owner() ) {
            $assignee_org = (int) $wpdb->get_var( $wpdb->prepare(
                "SELECT org_id FROM {$wpdb->prefix}wpi_org_users WHERE user_id=%d LIMIT 1", $assigned_to
            ) );
            if ( $org_id && $assignee_org && $assignee_org !== (int) $org_id ) {
                $this->error( 'Selected user is not in this organisation', 403 );
            }
        }

        $assigned_name  = $user->display_name ?: $user->user_login;
        $assigned_email = $user->user_email;

        $ok = $wpdb->insert(
            $wpdb->prefix . 'wpi_actions',
            array(
                'inspection_id'   => $inspection_id,
                'question_id'     => $question_id,
                'question_label'  => $question_label,
                'note'            => $note,
                'assigned_to'     => $assigned_to,
                'assigned_name'   => $assigned_name,
                'assigned_email'  => $assigned_email,
                'due_date'        => $due_date,
                'priority'        => $priority,
                'status'          => 'open',
                'created_by'      => $created_by,
                'org_id'          => $org_id,
                'created_at'      => current_time( 'mysql' ),
            ),
            array( '%d','%s','%s','%s','%d','%s','%s','%s','%s','%s','%d','%d','%s' )
        );
        if ( ! $ok ) $this->error( $wpdb->last_error ?: 'Failed to create action' );

        $action_id = (int) $wpdb->insert_id;
        $by = wp_get_current_user();
        $assigned_by_name = $by && $by->display_name ? $by->display_name : 'Audit4me';
        if ( $assigned_email ) {
            $this->send_action_notification(
                $action_id,
                $assigned_email,
                $assigned_name,
                $question_label,
                $note,
                $due_date,
                $priority,
                $assigned_by_name,
                $question_answer,
                $question_note
            );
        }

        // Mobile/WebToNative Firebase push for action assignment.
        // This is separate from the email so Android devices still receive a push when an action is assigned.
        self::push_log( 'ACTION ASSIGN push route action_id=' . $action_id . ' assigned_to=' . $assigned_to );
        self::send_push( $assigned_to, array(
            'title' => 'New Action Assigned to You',
            'body'  => ($priority ? strtoupper($priority) . ': ' : '') . wp_strip_all_tags( $question_label ),
            'url'   => home_url('/?wpi=1#actions'),
            'tag'   => 'wpi-action-' . $action_id,
        ) );

        $this->json( array( 'success' => true, 'id' => $action_id ) );
    }

    public function wpi_get_actions() {
        $this->check_nonce();
        global $wpdb;
        $inspection_id = absint( $_GET['inspection_id'] ?? 0 );
        if ( ! $inspection_id ) $this->error( 'inspection_id required' );

        $org_id = $this->get_org_id();
        $where  = $wpdb->prepare( 'inspection_id=%d', $inspection_id );
        if ( $org_id && ! $this->is_system_owner() ) {
            $where .= $wpdb->prepare( ' AND org_id=%d', $org_id );
        }

        $rows = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}wpi_actions WHERE {$where} ORDER BY created_at DESC" );
        foreach ( $rows ?: array() as $r ) {
            if ( isset( $r->photos ) && is_string( $r->photos ) ) {
                $decoded = json_decode( $r->photos, true );
                $r->photos = is_array( $decoded ) ? $decoded : array();
            }
        }
        $this->json( $rows ?: array() );
    }

    public function wpi_reassign_action() {
        $this->check_nonce();
        if ( ! $this->can( 'standard' ) ) $this->error( 'Access denied', 403 );
        global $wpdb;
        $body = $this->input();
        $action_id   = absint( $body['action_id'] ?? 0 );
        $assigned_to = absint( $body['assigned_to'] ?? 0 );
        if ( ! $action_id || ! $assigned_to ) $this->error( 'Invalid request' );
        $user = get_userdata( $assigned_to );
        if ( ! $user ) $this->error( 'Assigned user not found' );

        // Org-scope: only creator, current assignee, or org manager can reassign
        if ( ! $this->is_system_owner() ) {
            $action = $wpdb->get_row( $wpdb->prepare(
                "SELECT created_by, assigned_to, org_id FROM {$wpdb->prefix}wpi_actions WHERE id=%d", $action_id
            ) );
            if ( ! $action ) $this->error( 'Action not found', 404 );
            $uid    = get_current_user_id();
            $my_org = $this->get_org_id();
            $org_match  = ( $my_org && (int) $action->org_id === (int) $my_org );
            $is_involved = ( (int) $action->assigned_to === (int) $uid || (int) $action->created_by === (int) $uid );
            if ( ! $is_involved && ! ( $this->can('manager') && $org_match ) ) {
                $this->error( 'Access denied', 403 );
            }
            // Assignee must also be in the same org
            if ( $my_org ) {
                $in_org = $wpdb->get_var( $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}wpi_org_users WHERE org_id=%d AND user_id=%d",
                    $my_org, $assigned_to
                ) );
                if ( ! $in_org ) $this->error( 'Selected user is not in your organisation', 403 );
            }
        }
        $wpdb->update(
            $wpdb->prefix . 'wpi_actions',
            array(
                'assigned_to'    => $assigned_to,
                'assigned_name'  => $user->display_name ?: $user->user_login,
                'assigned_email' => $user->user_email,
                'status'         => 'open',
            ),
            array( 'id' => $action_id ),
            array( '%d','%s','%s','%s' ),
            array( '%d' )
        );
        self::push_log( 'ACTION REASSIGN push route action_id=' . $action_id . ' assigned_to=' . $assigned_to );
        self::send_push( $assigned_to, array(
            'title' => 'Action Reassigned to You',
            'body'  => 'A corrective action has been reassigned to you.',
            'url'   => home_url('/?wpi=1#actions'),
            'tag'   => 'wpi-action-reassign-' . $action_id . '-' . time(),
        ) );
        $this->json( array( 'success' => true ) );
    }

    public function wpi_resolve_action() {
        $this->check_nonce();
        global $wpdb;
        $body = $this->input();
        $id = absint( $body['id'] ?? 0 );
        $status = sanitize_text_field( $body['status'] ?? 'resolved' );
        $note = sanitize_textarea_field( $body['resolved_note'] ?? '' );
        if ( ! $id ) $this->error( 'id required' );
        if ( ! in_array( $status, array( 'open', 'in_progress', 'resolved' ), true ) ) $status = 'resolved';

        // Org-scope + ownership check: only assignee, creator, or org manager can update
        if ( ! $this->is_system_owner() ) {
            $action = $wpdb->get_row( $wpdb->prepare(
                "SELECT created_by, assigned_to, org_id FROM {$wpdb->prefix}wpi_actions WHERE id=%d", $id
            ) );
            if ( ! $action ) $this->error( 'Action not found', 404 );
            $uid    = get_current_user_id();
            $my_org = $this->get_org_id();
            $org_match  = ( $my_org && (int) $action->org_id === (int) $my_org );
            $is_involved = ( (int) $action->assigned_to === (int) $uid || (int) $action->created_by === (int) $uid );
            if ( ! $is_involved && ! ( $this->can('manager') && $org_match ) ) {
                $this->error( 'Access denied', 403 );
            }
        }
        $data = array( 'status' => $status, 'resolved_note' => $note );
        $formats = array( '%s', '%s' );
        if ( 'resolved' === $status ) {
            $data['resolved_at'] = current_time( 'mysql' );
            $data['resolved_by'] = get_current_user_id();
            $formats[] = '%s';
            $formats[] = '%d';
        }
        $wpdb->update( $wpdb->prefix . 'wpi_actions', $data, array( 'id' => $id ), $formats, array( '%d' ) );
        $this->json( array( 'success' => true ) );
    }

    public function wpi_delete_action() {
        $this->check_nonce();
        if ( ! $this->can( 'standard' ) ) $this->error( 'Access denied', 403 );
        global $wpdb;
        $body = $this->input();
        $id = absint( $body['id'] ?? 0 );
        if ( ! $id ) $this->error( 'id required' );

        // Verify caller owns the action or is a manager in the same org (IDOR fix)
        if ( ! $this->is_system_owner() ) {
            $action = $wpdb->get_row( $wpdb->prepare(
                "SELECT created_by, assigned_to, org_id FROM {$wpdb->prefix}wpi_actions WHERE id=%d", $id
            ) );
            if ( ! $action ) $this->error( 'Action not found', 404 );
            $uid    = get_current_user_id();
            $my_org = $this->get_org_id();
            $is_creator  = ( (int) $action->created_by  === (int) $uid );
            $is_assignee = ( (int) $action->assigned_to === (int) $uid );
            $is_manager  = $this->can( 'manager' );
            $org_match   = ( $my_org && (int) $action->org_id === (int) $my_org );
            if ( ! $is_creator && ! $is_assignee && ! ( $is_manager && $org_match ) ) {
                $this->error( 'Access denied', 403 );
            }
        }

        $wpdb->delete( $wpdb->prefix . 'wpi_actions', array( 'id' => $id ), array( '%d' ) );
        $this->json( array( 'success' => true ) );
    }

    public function wpi_get_my_actions() {
        $this->check_nonce();
        global $wpdb;
        $uid = get_current_user_id();
        $rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wpi_actions WHERE assigned_to=%d ORDER BY FIELD(status,'open','in_progress','resolved'), due_date ASC, created_at DESC",
            $uid
        ) );
        foreach ( $rows ?: array() as $r ) {
            if ( isset( $r->photos ) && is_string( $r->photos ) ) {
                $decoded = json_decode( $r->photos, true );
                $r->photos = is_array( $decoded ) ? $decoded : array();
            }
        }
        $this->json( $rows ?: array() );
    }

        private function send_action_notification( $action_id, $to, $name, $question, $note, $due_date, $priority, $assigned_by, $question_answer = '', $question_note = '' ) {
        $site      = get_bloginfo('name') ?: 'Audit4me';
        $fname     = explode(' ', $name)[0] ?: 'there';
        $due_str   = $due_date ? date('d M Y', strtotime($due_date)) : 'No due date set';
        $pri_labels= array('low'=>'Low','medium'=>'Medium','high'=>'High','critical'=>'CRITICAL');
        $pri_colors= array('low'=>'#22c55e','medium'=>'#f59e0b','high'=>'#ef4444','critical'=>'#7c2d12');
        $pri_bgs   = array('low'=>'#f0fdf4','medium'=>'#fffbeb','high'=>'#fef2f2','critical'=>'#fff1f0');
        $pri_label = $pri_labels[$priority] ?? 'Medium';
        $pri_color = $pri_colors[$priority] ?? '#f59e0b';
        $pri_bg    = $pri_bgs[$priority]    ?? '#fffbeb';

        $body_html = '
            <p style="margin:0 0 16px;color:#374151;font-size:14px;line-height:1.6;">Hi ' . esc_html($fname) . ',</p>
            <p style="margin:0 0 20px;color:#374151;font-size:14px;line-height:1.6;"><strong>' . esc_html($assigned_by) . '</strong> has assigned you a corrective action in <strong>' . esc_html($site) . '</strong>.</p>

            <table width="100%" cellpadding="0" cellspacing="0" style="background:#f8faff;border:1.5px solid #e0e7ff;border-radius:10px;">
              <tr><td style="padding:16px 18px;">
                <span style="display:inline-block;background:' . esc_attr($pri_bg) . ';color:' . esc_attr($pri_color) . ';border:1.5px solid ' . esc_attr($pri_color) . ';border-radius:20px;padding:2px 10px;font-size:11px;font-weight:700;margin-bottom:12px;">' . esc_html(strtoupper($pri_label)) . '</span>

                <p style="margin:0 0 4px;font-size:11px;font-weight:700;color:#6366f1;text-transform:uppercase;letter-spacing:.5px;">Flagged Question</p>
                <p style="margin:0 0 12px;font-size:15px;font-weight:700;color:#111827;">' . esc_html($question) . '</p>

                ' . ($question_answer ? '<p style="margin:0 0 4px;font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.5px;">Answer Recorded</p><p style="margin:0 0 12px;font-size:14px;font-weight:700;color:#111827;">' . esc_html($question_answer) . '</p>' : '') . '
                ' . ($question_note   ? '<p style="margin:0 0 4px;font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.5px;">Inspector\'s Note</p><p style="margin:0 0 12px;font-size:14px;color:#374151;font-style:italic;">' . nl2br(esc_html($question_note)) . '</p>' : '') . '
                ' . ($note            ? '<p style="margin:0 0 4px;font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.5px;">Action Instructions</p><p style="margin:0 0 12px;font-size:14px;color:#374151;">' . nl2br(esc_html($note)) . '</p>' : '') . '

                <p style="margin:0 0 4px;font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.5px;">Due Date</p>
                <p style="margin:0;font-size:14px;color:#374151;font-weight:600;">' . esc_html($due_str) . '</p>
              </td></tr>
            </table>';

        $subject = '⚡ Action Assigned: ' . $question . ' — ' . $site;
        require_once WPI_PLUGIN_DIR . 'includes/class-scheduler.php';
        WPI_Scheduler::send_branded_email( $to, $subject, $body_html, '#6366f1', '⚡ ACTION ASSIGNED' );
    }

    /* ── Action Photos ─────────────────────────────────────────── */

    public function wpi_save_action_photos() {
        $this->check_nonce();
        global $wpdb;
        $body      = $this->input();
        $action_id = absint( $body['action_id'] ?? 0 );
        $photos    = $body['photos'] ?? array(); // array of {id,url,thumb}
        if ( ! $action_id ) $this->error( 'action_id required' );

        // Ownership: only assignee, creator, or org manager can add photos
        if ( ! $this->is_system_owner() ) {
            $action = $wpdb->get_row( $wpdb->prepare(
                "SELECT created_by, assigned_to, org_id FROM {$wpdb->prefix}wpi_actions WHERE id=%d", $action_id
            ) );
            if ( ! $action ) $this->error( 'Action not found', 404 );
            $uid    = get_current_user_id();
            $my_org = $this->get_org_id();
            $org_match   = ( $my_org && (int) $action->org_id === (int) $my_org );
            $is_involved = ( (int) $action->assigned_to === (int) $uid || (int) $action->created_by === (int) $uid );
            if ( ! $is_involved && ! ( $this->can('manager') && $org_match ) ) {
                $this->error( 'Access denied', 403 );
            }
        }

        if ( ! is_array( $photos ) ) $photos = array();
        // Max 5
        $photos = array_slice( $photos, 0, 5 );
        // Sanitise each photo entry
        $clean = array();
        foreach ( $photos as $p ) {
            if ( empty($p['url']) ) continue;
            $clean[] = array(
                'id'    => absint( $p['id'] ?? 0 ),
                'url'   => esc_url_raw( $p['url'] ),
                'thumb' => esc_url_raw( $p['thumb'] ?? $p['url'] ),
            );
        }
        $wpdb->update(
            $wpdb->prefix . 'wpi_actions',
            array( 'photos' => wp_json_encode( $clean ) ),
            array( 'id' => $action_id )
        );
        $this->json( array( 'success' => true, 'photos' => $clean ) );
    }

    public function wpi_delete_action_photo() {
        $this->check_nonce();
        global $wpdb;
        $body          = $this->input();
        $action_id     = absint( $body['action_id'] ?? 0 );
        $attachment_id = absint( $body['attachment_id'] ?? 0 );
        if ( ! $action_id ) $this->error( 'action_id required' );

        // Ownership: only assignee, creator, or org manager can delete photos
        if ( ! $this->is_system_owner() ) {
            $action = $wpdb->get_row( $wpdb->prepare(
                "SELECT created_by, assigned_to, org_id FROM {$wpdb->prefix}wpi_actions WHERE id=%d", $action_id
            ) );
            if ( ! $action ) $this->error( 'Action not found', 404 );
            $uid    = get_current_user_id();
            $my_org = $this->get_org_id();
            $org_match   = ( $my_org && (int) $action->org_id === (int) $my_org );
            $is_involved = ( (int) $action->assigned_to === (int) $uid || (int) $action->created_by === (int) $uid );
            if ( ! $is_involved && ! ( $this->can('manager') && $org_match ) ) {
                $this->error( 'Access denied', 403 );
            }
        }

        $row = $wpdb->get_var( $wpdb->prepare(
            "SELECT photos FROM {$wpdb->prefix}wpi_actions WHERE id=%d", $action_id
        ) );
        $photos = $row ? json_decode( $row, true ) : array();
        if ( ! is_array($photos) ) $photos = array();
        // Remove the photo with this attachment_id
        $photos = array_values( array_filter( $photos, function($p) use ($attachment_id) {
            return (int)($p['id'] ?? 0) !== $attachment_id;
        }));
        // Also delete from media library
        if ( $attachment_id ) wp_delete_attachment( $attachment_id, true );
        $wpdb->update(
            $wpdb->prefix . 'wpi_actions',
            array( 'photos' => wp_json_encode( $photos ) ),
            array( 'id' => $action_id )
        );
        $this->json( array( 'success' => true, 'photos' => $photos ) );
    }

    /* ── Get Activity Log ──────────────────────────────────────── */
    // ═══════════════════════════════════════════════════════════════
    // ── REST API Key Management ─────────────────────────────────────
    // ═══════════════════════════════════════════════════════════════

    /**
     * List API keys for the current org (admin/licence-owner only).
     * Returns key metadata — never the raw key itself.
     */
    public function wpi_list_api_keys() {
        $this->check_nonce();
        if ( ! $this->can('administrator') ) $this->error( 'Access denied — administrator required', 403 );
        global $wpdb;

        // Return empty list gracefully if table doesn't exist yet
        if ( ! $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}wpi_api_keys'" ) ) {
            $this->json( array() );
        }

        $org_id = $this->get_org_id();

        // Build WHERE: system owner sees all their own keys; org members see org keys
        $uid = get_current_user_id();
        if ( $this->is_system_owner() ) {
            $where = $org_id
                ? $wpdb->prepare( 'k.org_id = %d OR k.created_by = %d', $org_id, $uid )
                : $wpdb->prepare( 'k.created_by = %d', $uid );
        } else {
            $where = $wpdb->prepare( 'k.org_id = %d', $org_id );
        }

        $rows = $wpdb->get_results(
            "SELECT k.id, k.label, k.description, k.key_prefix, k.scopes,
                    k.is_active, k.last_used_at, k.created_at,
                    COALESCE(
                        NULLIF(TRIM(CONCAT(COALESCE(um1.meta_value,''),' ',COALESCE(um2.meta_value,''))), ''),
                        u.display_name
                    ) AS created_by_name
             FROM {$wpdb->prefix}wpi_api_keys k
             LEFT JOIN {$wpdb->prefix}users u   ON u.ID = k.created_by
             LEFT JOIN {$wpdb->prefix}usermeta um1 ON um1.user_id = k.created_by AND um1.meta_key = 'first_name'
             LEFT JOIN {$wpdb->prefix}usermeta um2 ON um2.user_id = k.created_by AND um2.meta_key = 'last_name'
             WHERE {$where}
             ORDER BY k.created_at DESC"
        );

        $this->json( $rows ?: array() );
    }

    /**
     * Generate a new API key.
     * Returns the full plaintext key ONCE — not stored, never shown again.
     */
    public function wpi_create_api_key() {
        $this->check_nonce();
        if ( ! $this->can('administrator') ) $this->error( 'Access denied — administrator required', 403 );
        global $wpdb;

        // Self-heal: create table if missing (handles installs that existed before this feature)
        if ( ! $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}wpi_api_keys'" ) ) {
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
            if ( $wpdb->last_error ) {
                $this->error( 'Could not create API keys table: ' . $wpdb->last_error, 500 );
            }
        } else {
            // Table exists — ensure new columns are present (older installs)
            $cols = $wpdb->get_col( "SHOW COLUMNS FROM `{$wpdb->prefix}wpi_api_keys`", 0 );
            if ( $cols && ! in_array( 'description', $cols, true ) ) {
                $wpdb->query( "ALTER TABLE `{$wpdb->prefix}wpi_api_keys` ADD COLUMN `description` VARCHAR(255) NOT NULL DEFAULT '' AFTER `label`" );
            }
            if ( $cols && ! in_array( 'is_active', $cols, true ) ) {
                $wpdb->query( "ALTER TABLE `{$wpdb->prefix}wpi_api_keys` ADD COLUMN `is_active` TINYINT(1) NOT NULL DEFAULT 1 AFTER `scopes`" );
            }
        }

        $body  = $this->input();
        $label = sanitize_text_field( $body['label'] ?? '' );
        $desc  = sanitize_text_field( $body['description'] ?? '' );
        $scope = sanitize_text_field( $body['scopes'] ?? 'read' );
        if ( ! $label ) $this->error( 'Label is required' );
        if ( ! in_array( $scope, array('read', 'read_write'), true ) ) $scope = 'read';

        $uid    = get_current_user_id();
        $org_id = $this->org_id_for_insert(); // 0 for system owner, real org_id for everyone else
        // If no org yet, try get_org_id as fallback
        if ( ! $org_id && ! $this->is_system_owner() ) {
            $org_id = $this->get_org_id();
        }

        // Count existing keys — cap at 10 per org
        $count_sql = $org_id
            ? $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}wpi_api_keys WHERE org_id = %d", $org_id )
            : $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}wpi_api_keys WHERE created_by = %d", $uid );
        $count = (int) $wpdb->get_var( $count_sql );
        if ( $count >= 10 ) $this->error( 'Maximum 10 API keys. Revoke an existing key first.' );

        // Generate: wpi_ + 32 random hex chars
        $raw    = 'wpi_' . bin2hex( random_bytes( 16 ) );
        $prefix = substr( $raw, 0, 12 ); // wpi_ + first 8 hex chars
        $hash   = hash( 'sha256', $raw );

        $inserted = $wpdb->insert( $wpdb->prefix . 'wpi_api_keys', array(
            'org_id'      => $org_id,
            'created_by'  => $uid,
            'label'       => $label,
            'description' => $desc,
            'key_prefix'  => $prefix,
            'key_hash'    => $hash,
            'scopes'      => $scope,
            'is_active'   => 1,
            'created_at'  => current_time('mysql'),
        ) );

        if ( ! $inserted ) {
            $this->error( 'Failed to save API key: ' . ( $wpdb->last_error ?: 'Unknown DB error' ), 500 );
        }

        // Capture insert_id BEFORE any other query (self::log runs another query)
        $new_id = (int) $wpdb->insert_id;
        self::log( 'api_key', $new_id, 'created', $label );

        // Return the full key ONCE — it is not stored and cannot be recovered
        $this->json( array(
            'success'    => true,
            'id'         => $new_id,
            'key'        => $raw,
            'key_prefix' => $prefix,
            'label'      => $label,
            'scopes'     => $scope,
        ) );
    }

    /**
     * Enable or disable an API key without deleting it.
     */
    public function wpi_toggle_api_key() {
        $this->check_nonce();
        if ( ! $this->can('administrator') ) $this->error( 'Access denied — administrator required', 403 );
        global $wpdb;

        $body      = $this->input();
        $id        = absint( $body['id'] ?? 0 );
        $is_active = isset($body['is_active']) ? (int)(bool)$body['is_active'] : null;
        if ( ! $id || $is_active === null ) $this->error( 'id and is_active required' );

        $org_id = $this->get_org_id();
        $uid    = get_current_user_id();
        if ( $this->is_system_owner() ) {
            $key = $wpdb->get_row( $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}wpi_api_keys WHERE id = %d AND (org_id = %d OR created_by = %d)",
                $id, $org_id, $uid
            ) );
        } else {
            $key = $wpdb->get_row( $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}wpi_api_keys WHERE id = %d AND org_id = %d",
                $id, $org_id
            ) );
        }
        if ( ! $key ) $this->error( 'Key not found', 404 );

        $wpdb->update(
            $wpdb->prefix . 'wpi_api_keys',
            array( 'is_active' => $is_active ),
            array( 'id' => $id ),
            array( '%d' ),
            array( '%d' )
        );
        self::log( 'api_key', $id, $is_active ? 'enabled' : 'disabled', $key->label );

        $this->json( array( 'success' => true, 'is_active' => $is_active ) );
    }

    /**
     * Revoke (permanently delete) an API key.
     */
    public function wpi_revoke_api_key() {
        $this->check_nonce();
        if ( ! $this->can('administrator') ) $this->error( 'Access denied — administrator required', 403 );
        global $wpdb;

        $body = $this->input();
        $id   = absint( $body['id'] ?? 0 );
        if ( ! $id ) $this->error( 'id required' );

        $org_id = $this->get_org_id();
        $uid    = get_current_user_id();

        // System owner: match on created_by since their org_id is 0
        if ( $this->is_system_owner() ) {
            $key = $wpdb->get_row( $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}wpi_api_keys WHERE id = %d AND (org_id = %d OR created_by = %d)",
                $id, $org_id, $uid
            ) );
        } else {
            $key = $wpdb->get_row( $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}wpi_api_keys WHERE id = %d AND org_id = %d",
                $id, $org_id
            ) );
        }
        if ( ! $key ) $this->error( 'Key not found', 404 );

        $wpdb->delete( $wpdb->prefix . 'wpi_api_keys', array( 'id' => $id ) );
        self::log( 'api_key', $id, 'revoked', $key->label );

        $this->json( array( 'success' => true ) );
    }


    // ═══════════════════════════════════════════════════════════════
    // ── Billing & Subscription Handlers ────────────────────────────
    // ═══════════════════════════════════════════════════════════════

    /** Get all active plans (public-facing). */
    public function wpi_get_plans() {
        $this->check_nonce();
        global $wpdb;

        // Self-heal: create tables and seed plans if missing
        $table_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}wpi_plans'" );
        if ( ! $table_exists ) {
            wpi_ensure_critical_tables();
        }

        // If still empty after self-heal, seed defaults
        $count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}wpi_plans" );
        if ( $count === 0 ) {
            wpi_ensure_critical_tables();
        }

        // System owner sees all plans (including inactive); others see only active
        $active_filter = $this->is_system_owner() ? '' : ' WHERE is_active=1';
        $rows = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}wpi_plans{$active_filter} ORDER BY sort_order ASC"
        );

        if ( $wpdb->last_error ) {
            $this->error( 'Plans table error: ' . $wpdb->last_error, 500 );
        }

        foreach ( $rows as $r ) {
            $r->limits   = $r->limits   ? json_decode( $r->limits,   true ) : array();
            $r->features = $r->features ? json_decode( $r->features, true ) : array();
            // Hoist annual_enabled and is_seat_plan to top-level
            $r->annual_enabled = ! empty( $r->limits['annual_enabled'] );
            $r->is_seat_plan   = ! empty( $r->limits['is_seat_plan'] );
            $r->id = (int) $r->id;
            $r->is_active = (int) $r->is_active;
            $r->is_free = (int) $r->is_free;
            $r->sort_order = (int) $r->sort_order;
            $r->monthly_price_cents = (int) $r->monthly_price_cents;
            $r->annual_price_cents  = (int) $r->annual_price_cents;
        }
        $this->json( $rows ?: array() );
    }

    /** Get the current org's subscription details. */
    public function wpi_get_my_subscription() {
        $this->check_nonce();
        global $wpdb;
        $org_id = $this->get_org_id();
        if ( ! $org_id ) { $this->json( array( 'status' => 'free', 'plan' => null ) ); return; }

        // Self-heal older installs: add billing_cycle if missing.
        $sub_cols = $wpdb->get_col( "SHOW COLUMNS FROM {$wpdb->prefix}wpi_subscriptions", 0 );
        if ( $sub_cols && ! in_array( 'billing_cycle', $sub_cols ) ) {
            $wpdb->query( "ALTER TABLE {$wpdb->prefix}wpi_subscriptions ADD COLUMN billing_cycle VARCHAR(20) NOT NULL DEFAULT '' AFTER current_period_end" );
        }

        // Best-effort live sync from Stripe. This fixes pending/period/cycle issues when webhooks are delayed.
        if ( class_exists( 'WPI_Billing' ) ) {
            WPI_Billing::sync_org_subscription_from_stripe( $org_id );
        }

        // Display the latest real subscription record.
        // IMPORTANT: do not treat an abandoned Stripe Checkout attempt as a subscription.
        // Older builds inserted status='pending' before the user completed payment.
        // If the user pressed Back/Cancel in Stripe, that row had no stripe_subscription_id
        // and incorrectly blocked the purchase button as "current plan".
        $wpdb->query( $wpdb->prepare(
            "UPDATE {$wpdb->prefix}wpi_subscriptions
             SET status='checkout_abandoned', updated_at=%s
             WHERE org_id=%d AND status='pending'
               AND (stripe_subscription_id IS NULL OR stripe_subscription_id='')",
            current_time('mysql'), $org_id
        ) );

        $sub = $wpdb->get_row( $wpdb->prepare(
            "SELECT s.*, p.name as plan_name, p.limits as plan_limits, p.features as plan_features
             FROM {$wpdb->prefix}wpi_subscriptions s
             LEFT JOIN {$wpdb->prefix}wpi_plans p ON p.id = s.plan_id
             WHERE s.org_id = %d
               AND NOT (s.status IN ('checkout_abandoned','pending') AND (s.stripe_subscription_id IS NULL OR s.stripe_subscription_id=''))
             ORDER BY s.id DESC LIMIT 1",
            $org_id
        ) );
        $limits = WPI_Billing::get_limits( $org_id );

        // Count current usage
        $usage = array(
            'users'       => (int) $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}wpi_org_users ou
                  WHERE ou.org_id=%d
                    AND NOT EXISTS (
                        SELECT 1 FROM {$wpdb->usermeta} um
                        WHERE um.user_id=ou.user_id
                          AND um.meta_key='wpi_deactivated'
                          AND um.meta_value='1'
                    )", $org_id ) ),
            'templates'   => (int) $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}wpi_templates WHERE org_id=%d AND status!='deleted'", $org_id ) ),
            'inspections_this_month' => (int) $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}wpi_inspections WHERE org_id=%d AND MONTH(conducted_at)=MONTH(NOW()) AND YEAR(conducted_at)=YEAR(NOW())", $org_id ) ),
            'sites'       => (int) $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}wpi_sites WHERE org_id=%d", $org_id ) ),
        );

        // Count purchased seats for this org
        $seat_total = 0;
        $seat_used  = 0;
        if ( $org_id ) {
            $seat_rows = $wpdb->get_results( $wpdb->prepare(
                "SELECT seats_total, seats_used FROM {$wpdb->prefix}wpi_org_seats
                 WHERE org_id=%d AND status='active'", $org_id
            ) );
            foreach ( $seat_rows as $sr ) {
                $seat_total += (int)$sr->seats_total;
                $seat_used  += (int)$sr->seats_used;
            }
        }

        $this->json( array(
            'status'    => $sub ? $sub->status : 'free',
            'plan'      => ( $sub && $sub->plan_id ) ? array(
                'id'    => (int)$sub->plan_id,
                'name'  => $sub->plan_name,
                'limits'=> $limits,
            ) : ( function() use ( $org_id, $wpdb, $limits ) {
                // No Stripe plan — check for a token licence
                $tok = $wpdb->get_row( $wpdb->prepare(
                    "SELECT l.licence_type, l.expiry_date
                     FROM {$wpdb->prefix}wpi_licences l
                     WHERE l.org_id = %d AND l.status = 'assigned'
                     ORDER BY l.id DESC LIMIT 1",
                    $org_id
                ) );
                if ( ! $tok ) return null;
                $labels = array(
                    'trial'     => 'Trial',      'monthly'   => 'Monthly',
                    'quarterly' => 'Quarterly',  '6monthly'  => '6-Monthly',
                    'annual'    => 'Annual',      'lifetime'  => 'Lifetime',
                );
                return array(
                    'id'     => 0,
                    'name'   => $labels[ $tok->licence_type ] ?? ucfirst( $tok->licence_type ),
                    'limits' => $limits,
                );
            } )(),
            'billing_cycle'      => $sub ? ( $sub->billing_cycle ?: '' ) : ( function() use ( $org_id, $wpdb ) {
                $tok = $wpdb->get_row( $wpdb->prepare(
                    "SELECT licence_type FROM {$wpdb->prefix}wpi_licences
                     WHERE org_id = %d AND status = 'assigned' ORDER BY id DESC LIMIT 1",
                    $org_id
                ) );
                return $tok ? $tok->licence_type : '';
            } )(),
            'is_trial'           => $sub ? (bool)($sub->is_trial ?? 0) : false,
            'trial_ends_at'      => $sub ? ($sub->trial_ends_at ?? null) : null,
            'period_end'         => $sub
                                        ? ( function() use ( $sub ) {
                                              $val = $sub->trial_ends_at ?: $sub->current_period_end;
                                              // Stripe stores current_period_end as Unix timestamp — convert to ISO
                                              if ( $val && is_numeric( $val ) ) {
                                                  $val = date( 'Y-m-d H:i:s', (int) $val );
                                              }
                                              return $val;
                                          } )()
                                        : ( function() use ( $org_id, $wpdb ) {
                                              // No Stripe sub — fall back to token licence expiry_date
                                              $tok = $wpdb->get_row( $wpdb->prepare(
                                                  "SELECT l.expiry_date, l.licence_type
                                                   FROM {$wpdb->prefix}wpi_licences l
                                                   WHERE l.org_id = %d AND l.status = 'assigned'
                                                     AND l.expiry_date IS NOT NULL
                                                   ORDER BY l.id DESC LIMIT 1",
                                                  $org_id
                                              ) );
                                              return $tok ? $tok->expiry_date : null;
                                          } )(),
            'cancel_at_period_end' => $sub ? (bool)$sub->cancel_at_period_end : false,
            'limits'    => $limits,
            'usage'     => $usage,
            'seats'     => array( 'total' => $seat_total, 'used' => $seat_used, 'available' => max(0,$seat_total-$seat_used) ),
            'has_stripe' => ! empty( WPI_Billing::get_stripe_keys()['publishable'] ),
        ) );
    }

    /** Create a Stripe Checkout session and return the URL. */
    public function wpi_create_checkout_session() {
        $this->check_nonce();
        if ( ! is_user_logged_in() ) $this->error('Access denied', 403);
        global $wpdb;

        $body     = $this->input();
        $plan_id  = absint( $body['plan_id']  ?? 0 );
        $cycle    = sanitize_text_field( $body['cycle'] ?? 'monthly' ); // monthly|annual
        $org_id   = $this->get_or_create_billing_org_id();
        if ( ! $org_id ) $this->error('Unable to create an organisation for this subscription. Please contact support.', 500);

        // Prevent duplicate purchases only when there is a real Stripe subscription.
        // Abandoned checkout sessions must not block the user from trying again.
        $wpdb->query( $wpdb->prepare(
            "UPDATE {$wpdb->prefix}wpi_subscriptions
             SET status='checkout_abandoned', updated_at=%s
             WHERE org_id=%d AND status='pending'
               AND (stripe_subscription_id IS NULL OR stripe_subscription_id='')",
            current_time('mysql'), $org_id
        ) );
        $current_sub = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wpi_subscriptions
             WHERE org_id=%d
               AND status IN ('active','trialing','past_due','unpaid','pending')
               AND stripe_subscription_id IS NOT NULL AND stripe_subscription_id <> ''
             ORDER BY id DESC LIMIT 1",
            $org_id
        ) );
        if ( ! $plan_id ) $this->error('plan_id required');
        $plan = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wpi_plans WHERE id=%d AND is_active=1", $plan_id
        ) );
        if ( ! $plan ) $this->error('Plan not found');

        if ( $current_sub ) {
            $this->error('This organisation already has a subscription. Purchase option is disabled.', 403);
        }

        $is_trial = ! empty( $plan->is_trial );
        $trial_days = (int)( $plan->trial_days ?? 7 );

        if ( $is_trial ) {
            // Trial uses a one-time price
            $price_id = $plan->stripe_trial_price_id ?? '';
            if ( ! $price_id ) $this->error('Trial price not configured. Please contact support.');
        } else {
            $price_id = $cycle === 'annual' ? $plan->stripe_annual_price_id : $plan->stripe_monthly_price_id;
            if ( ! $price_id ) $this->error('Stripe price ID not configured for this plan. Please contact support.');
        }

        $uid  = get_current_user_id();
        $user = get_userdata( $uid );
        $org  = $wpdb->get_row( $wpdb->prepare( "SELECT name FROM {$wpdb->prefix}wpi_organisations WHERE id=%d", $org_id ) );

        // Get or create Stripe customer — scoped to this user's email so the right
        // email appears on the Stripe checkout page
        $existing_sub = $wpdb->get_row( $wpdb->prepare(
            "SELECT stripe_customer_id FROM {$wpdb->prefix}wpi_subscriptions
             WHERE org_id=%d ORDER BY id DESC LIMIT 1",
            $org_id
        ) );
        $customer_id = '';

        // Verify the stored customer belongs to this user by checking email in Stripe
        if ( $existing_sub && $existing_sub->stripe_customer_id ) {
            $stripe_customer = WPI_Billing::stripe_request( 'GET',
                'customers/' . $existing_sub->stripe_customer_id
            );
            if ( ! is_wp_error($stripe_customer)
                && isset($stripe_customer['email'])
                && strtolower($stripe_customer['email']) === strtolower($user->user_email) ) {
                $customer_id = $existing_sub->stripe_customer_id;
            }
            // If email doesn't match, we create a new customer below
        }

        // Create Stripe customer for this user if not found
        if ( ! $customer_id ) {
            // Check if Stripe already has a customer with this email
            $existing = WPI_Billing::stripe_request( 'GET', 'customers',
                array( 'email' => $user->user_email, 'limit' => 1 )
            );
            if ( ! is_wp_error($existing) && ! empty($existing['data'][0]['id']) ) {
                $customer_id = $existing['data'][0]['id'];
            } else {
                $customer = WPI_Billing::stripe_request( 'POST', 'customers', array(
                    'email'    => $user->user_email,
                    'name'     => $org ? $org->name : $user->display_name,
                    'metadata' => array( 'org_id' => $org_id, 'wp_user_id' => $uid ),
                ) );
                if ( is_wp_error($customer) ) $this->error( $customer->get_error_message() );
                $customer_id = $customer['id'];
            }
        }

        $success_url = home_url( '/?wpi=1&billing=success&session_id={CHECKOUT_SESSION_ID}' );
        $cancel_url  = home_url( '/?wpi=1&billing=cancel' );

        $session_params = array(
            'customer'             => $customer_id,
            'payment_method_types' => array( 'card' ),
            'line_items'           => array(
                array( 'price' => $price_id, 'quantity' => 1 )
            ),
            'success_url'  => $success_url,
            'cancel_url'   => $cancel_url,
            'metadata'     => array(
                'org_id'     => $org_id,
                'plan_id'    => $plan_id,
                'cycle'      => $cycle,
                'is_trial'   => $is_trial ? '1' : '0',
                'trial_days' => (string)$trial_days,
            ),
            'allow_promotion_codes' => 'true',
        );

        if ( $is_trial ) {
            // One-time payment mode for fixed-price trial
            $session_params['mode'] = 'payment';
        } else {
            // Recurring subscription -- explicitly NO trial period
            $session_params['mode'] = 'subscription';
            $session_params['subscription_data'] = array(
                'metadata' => array( 'org_id' => $org_id, 'plan_id' => $plan_id, 'cycle' => $cycle ),
                'trial_period_days' => 0,
            );
        }

        $session = WPI_Billing::stripe_request( 'POST', 'checkout/sessions', $session_params );
        if ( is_wp_error( $session ) ) $this->error( $session->get_error_message() );

        // Do NOT mark the organisation as pending here.
        // A Checkout Session is only an intent to pay. If the user cancels/backs out,
        // no subscription exists and the purchase button must remain available.
        // The real subscription is stored by checkout.session.completed or customer.subscription.* webhooks.
        $this->json( array( 'checkout_url' => $session['url'], 'session_id' => $session['id'] ) );
    }

    /** Get Stripe invoices for the current org's subscription. */
    public function wpi_get_invoices() {
        $this->check_nonce();
        if ( ! is_user_logged_in() ) $this->error('Access denied', 403);
        global $wpdb;
        $org_id = $this->get_org_id();
        if ( ! $org_id ) $this->error('No organisation found');

        // Only the subscription purchaser (stripe_customer_id owner) or system owner
        $sub = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wpi_subscriptions WHERE org_id=%d
             AND status NOT IN ('checkout_abandoned') ORDER BY id DESC LIMIT 1",
            $org_id
        ) );
        if ( ! $sub || ! $sub->stripe_customer_id ) $this->json( array('invoices'=>array()) );

        // Verify current user is the purchaser (matches stripe customer email)
        if ( ! $this->is_system_owner() ) {
            $stripe_customer = \WPI_Billing::stripe_request( 'GET', 'customers/' . $sub->stripe_customer_id );
            if ( ! is_wp_error($stripe_customer) ) {
                $customer_email = strtolower($stripe_customer['email'] ?? '');
                $current_email  = strtolower( get_userdata(get_current_user_id())->user_email );
                if ( $customer_email && $customer_email !== $current_email ) {
                    $this->error('Access denied. Only the subscription purchaser can view invoices.', 403);
                }
            }
        }

        $invoices = array();

        // ── A. Standard Stripe invoices (recurring subscriptions) ────────────────
        $result = \WPI_Billing::stripe_request( 'GET', 'invoices',
            array( 'customer' => $sub->stripe_customer_id, 'limit' => 24 )
        );
        if ( ! is_wp_error($result) ) {
            foreach ( ($result['data'] ?? array()) as $inv ) {
                $invoices[] = array(
                    'id'          => $inv['id'],
                    'number'      => $inv['number'] ?? '',
                    'date'        => $inv['created'] ?? 0,
                    'amount'      => $inv['amount_paid'] ?? 0,
                    'currency'    => strtoupper($inv['currency'] ?? 'USD'),
                    'status'      => $inv['status'] ?? '',
                    'pdf_url'     => $inv['invoice_pdf'] ?? '',
                    'hosted_url'  => $inv['hosted_invoice_url'] ?? '',
                    'description' => $inv['lines']['data'][0]['description'] ?? '',
                    'type'        => 'invoice',
                );
            }
        }

        // ── B. One-time payment receipts (trials use mode=payment, no Stripe invoice) ─
        // If this subscription is a trial, fetch payment_intents for the receipt URL.
        if ( $sub->is_trial || $sub->status === 'trialing' ) {
            $pi_result = \WPI_Billing::stripe_request( 'GET', 'payment_intents',
                array( 'customer' => $sub->stripe_customer_id, 'limit' => 10 )
            );
            if ( ! is_wp_error( $pi_result ) ) {
                foreach ( ($pi_result['data'] ?? array()) as $pi ) {
                    if ( $pi['status'] === 'succeeded' ) {
                        $amount     = (int)( $pi['amount'] ?? 0 );
                        $currency   = strtoupper( $pi['currency'] ?? 'AUD' );
                        $date_ts    = (int)( $pi['created'] ?? 0 );
                        $receipt    = '';
                        $inv_number = $pi['id'] ?? '';
                        if ( ! empty( $pi['latest_charge'] ) ) {
                            $charge = \WPI_Billing::stripe_request( 'GET',
                                'charges/' . $pi['latest_charge'] );
                            if ( ! is_wp_error( $charge ) ) {
                                $receipt = $charge['receipt_url'] ?? '';
                                $amount  = (int)( $charge['amount'] ?? $amount );
                            }
                        }
                        $invoices[] = array(
                            'id'          => 'pi_' . $pi['id'],
                            'number'      => $inv_number,
                            'date'        => $date_ts,
                            'amount'      => $amount,
                            'currency'    => $currency,
                            'status'      => 'paid',
                            'pdf_url'     => '',
                            'hosted_url'  => $receipt,
                            'description' => 'Trial subscription — ' . ( $sub->trial_ends_at ? date('j M Y', strtotime($sub->trial_ends_at)) : '7 days' ),
                            'type'        => 'trial',
                        );
                    }
                }
            }
        }

        // Sort newest first
        usort( $invoices, function( $a, $b ) { return (int)$b['date'] - (int)$a['date']; } );

        $this->json( array('invoices' => $invoices, 'customer_id' => $sub->stripe_customer_id) );
    }


    /**
     * System-owner endpoint: return ALL invoices across every org.
     * Combines Stripe invoices + internal token-licence records + trial subscriptions.
     */
    public function wpi_get_all_invoices() {
        $this->check_nonce();
        if ( ! $this->is_system_owner() ) $this->error( 'Access denied — system owner only', 403 );
        global $wpdb;

        $all_invoices = array();
        $currency_default = strtoupper( get_option( 'wpi_currency', 'AUD' ) );

        // ── 1. Stripe invoices for every org that has a stripe_customer_id ──────────
        $stripe_subs = $wpdb->get_results(
            "SELECT s.org_id, s.stripe_customer_id, s.billing_cycle, s.is_trial, s.status,
                    s.trial_ends_at, s.current_period_end,
                    o.name AS org_name, p.name AS plan_name, p.monthly_price_cents, p.annual_price_cents, p.currency
             FROM {$wpdb->prefix}wpi_subscriptions s
             LEFT JOIN {$wpdb->prefix}wpi_organisations o ON o.id = s.org_id
             LEFT JOIN {$wpdb->prefix}wpi_plans p ON p.id = s.plan_id
             WHERE s.stripe_customer_id IS NOT NULL AND s.stripe_customer_id != ''
             GROUP BY s.stripe_customer_id
             ORDER BY s.id DESC"
        );

        $stripe_keys = class_exists('WPI_Billing') ? WPI_Billing::get_stripe_keys() : array();
        $has_stripe  = ! empty( $stripe_keys['secret'] );

        foreach ( ($stripe_subs ?: array()) as $sub ) {
            if ( ! $has_stripe ) break;
            $result = WPI_Billing::stripe_request( 'GET', 'invoices', array(
                'customer' => $sub->stripe_customer_id,
                'limit'    => 100,
            ) );
            if ( is_wp_error( $result ) ) continue;
            foreach ( ($result['data'] ?? array()) as $inv ) {
                $amount_cents = (int)( $inv['amount_paid'] ?? $inv['amount_due'] ?? 0 );
                $all_invoices[] = array(
                    'id'          => $inv['id'],
                    'source'      => 'stripe',
                    'org_id'      => (int) $sub->org_id,
                    'org_name'    => $sub->org_name ?: '—',
                    'plan_name'   => $inv['lines']['data'][0]['description'] ?? $sub->plan_name ?? '—',
                    'type'        => $sub->is_trial ? 'trial' : ( $sub->billing_cycle === 'annual' ? 'annual' : 'monthly' ),
                    'number'      => $inv['number'] ?? '',
                    'date'        => (int)( $inv['created'] ?? 0 ),
                    'amount'      => $amount_cents,
                    'currency'    => strtoupper( $inv['currency'] ?? $sub->currency ?? $currency_default ),
                    'status'      => $inv['status'] ?? '',
                    'pdf_url'     => $inv['invoice_pdf'] ?? '',
                    'hosted_url'  => $inv['hosted_invoice_url'] ?? '',
                    'customer_id' => $sub->stripe_customer_id,
                    'customer_email' => $inv['customer_email'] ?? '',
                );
            }
        }

        // ── 2. Internal token licences (no Stripe) ───────────────────────────────
        $token_licences = $wpdb->get_results(
            "SELECT l.id, l.token, l.licence_type, l.status, l.seats,
                    l.start_date, l.assigned_at, l.expiry_date, l.notes,
                    o.id AS org_id, o.name AS org_name,
                    u.user_email AS owner_email, u.display_name AS owner_name,
                    p.monthly_price_cents, p.annual_price_cents, p.currency
             FROM {$wpdb->prefix}wpi_licences l
             LEFT JOIN {$wpdb->prefix}wpi_organisations o ON o.id = l.org_id
             LEFT JOIN {$wpdb->prefix}wpi_organisations own_org ON own_org.id = l.org_id
             LEFT JOIN {$wpdb->users} u ON u.ID = own_org.owner_id
             LEFT JOIN {$wpdb->prefix}wpi_plans p ON 1=0
             WHERE l.org_id > 0 AND l.status IN ('assigned','expired')
             ORDER BY COALESCE(l.assigned_at, l.start_date) DESC"
        );

        $type_labels = array(
            'trial'     => 'Trial',      'monthly'   => 'Monthly',
            'quarterly' => 'Quarterly',  '6monthly'  => '6-Monthly',
            'annual'    => 'Annual',      'lifetime'  => 'Lifetime',
        );

        // Price lookup from system options
        $token_prices = array(
            'trial'     => (int) get_option('wpi_token_price_trial',     0),
            'monthly'   => (int) get_option('wpi_token_price_monthly',   0),
            'quarterly' => (int) get_option('wpi_token_price_quarterly', 0),
            '6monthly'  => (int) get_option('wpi_token_price_6monthly',  0),
            'annual'    => (int) get_option('wpi_token_price_annual',    0),
            'lifetime'  => (int) get_option('wpi_token_price_lifetime',  0),
        );

        foreach ( ($token_licences ?: array()) as $lic ) {
            $ltype   = $lic->licence_type ?? 'monthly';
            $amount  = ($token_prices[$ltype] ?? 0) * (int)($lic->seats ?: 1);
            $date_ts = $lic->assigned_at ? strtotime( $lic->assigned_at )
                     : ( $lic->start_date ? strtotime( $lic->start_date ) : 0 );
            $all_invoices[] = array(
                'id'           => 'tok_' . $lic->id,
                'source'       => 'token',
                'org_id'       => (int) $lic->org_id,
                'org_name'     => $lic->org_name ?: '—',
                'plan_name'    => ( $type_labels[$ltype] ?? ucfirst($ltype) ) . ' Token'
                                  . ( $lic->seats > 1 ? ' (' . $lic->seats . ' seats)' : '' ),
                'type'         => $ltype,
                'number'       => strtoupper( $lic->token ?? '' ),
                'date'         => $date_ts,
                'amount'       => $amount,
                'currency'     => $currency_default,
                'status'       => $lic->status === 'assigned' ? 'paid' : $lic->status,
                'pdf_url'      => '',
                'hosted_url'   => '',
                'customer_id'  => '',
                'customer_email' => $lic->owner_email ?? '',
                'expiry_date'  => $lic->expiry_date ?? null,
                'notes'        => $lic->notes ?? '',
            );
        }

        // ── 3. Trial subscriptions — ALL (with or without stripe_customer_id) ─────
        // Trials use Stripe mode=payment (one-time), NOT mode=subscription.
        // Stripe does NOT generate an invoice for payment-mode sessions.
        // We use our wpi_subscriptions row as the invoice record, and optionally
        // fetch the actual charge from Stripe for the real amount + receipt URL.
        $trial_subs = $wpdb->get_results(
            "SELECT s.id, s.org_id, s.status, s.billing_cycle, s.is_trial,
                    s.trial_ends_at, s.current_period_end, s.updated_at, s.created_at,
                    s.stripe_customer_id,
                    o.name AS org_name,
                    p.name AS plan_name, p.monthly_price_cents, p.trial_days, p.currency,
                    u.user_email AS owner_email
             FROM {$wpdb->prefix}wpi_subscriptions s
             LEFT JOIN {$wpdb->prefix}wpi_organisations o ON o.id = s.org_id
             LEFT JOIN {$wpdb->prefix}wpi_plans p ON p.id = s.plan_id
             LEFT JOIN {$wpdb->users} u ON u.ID = o.owner_id
             WHERE s.is_trial = 1 OR s.status = 'trialing'
             ORDER BY s.id DESC"
        );

        // Track stripe_customer_ids we've already fetched Stripe invoices for
        // so we don't double-count if section 1 already picked up a charge
        $stripe_customer_ids_in_section1 = array();
        foreach ( ($stripe_subs ?: array()) as $_ss ) {
            $stripe_customer_ids_in_section1[] = $_ss->stripe_customer_id;
        }

        foreach ( ($trial_subs ?: array()) as $sub ) {
            $date_ts = $sub->created_at ? strtotime( $sub->created_at ) : 0;
            $amount       = 0;
            $currency     = strtoupper( $sub->currency ?? $currency_default );
            $hosted_url   = '';
            $pdf_url      = '';
            $inv_status   = $sub->status;
            $inv_number   = '';

            // If the trial has a stripe_customer_id, try to get the actual charge
            // (one-time payment_intent, NOT a subscription invoice)
            if ( $has_stripe && ! empty( $sub->stripe_customer_id )
                 && ! in_array( $sub->stripe_customer_id, $stripe_customer_ids_in_section1, true ) ) {
                // Fetch payment intents for this customer — trial used mode=payment
                $pi_result = WPI_Billing::stripe_request( 'GET', 'payment_intents', array(
                    'customer' => $sub->stripe_customer_id,
                    'limit'    => 10,
                ) );
                if ( ! is_wp_error( $pi_result ) && ! empty( $pi_result['data'] ) ) {
                    foreach ( $pi_result['data'] as $pi ) {
                        if ( $pi['status'] === 'succeeded' ) {
                            $amount     = (int)( $pi['amount'] ?? 0 );
                            $currency   = strtoupper( $pi['currency'] ?? $currency );
                            $inv_status = 'paid';
                            $date_ts    = (int)( $pi['created'] ?? $date_ts );
                            $inv_number = $pi['id'] ?? '';
                            // Get receipt URL from charge
                            if ( ! empty( $pi['latest_charge'] ) ) {
                                $charge = WPI_Billing::stripe_request( 'GET',
                                    'charges/' . $pi['latest_charge'] );
                                if ( ! is_wp_error( $charge ) ) {
                                    $hosted_url = $charge['receipt_url'] ?? '';
                                    $amount     = (int)( $charge['amount'] ?? $amount );
                                }
                            }
                            break; // use the most recent succeeded payment
                        }
                    }
                }
            }

            $all_invoices[] = array(
                'id'             => 'trial_' . $sub->id,
                'source'         => 'trial',
                'org_id'         => (int) $sub->org_id,
                'org_name'       => $sub->org_name ?: '—',
                'plan_name'      => ( $sub->plan_name ?: 'Trial' )
                                    . ' (' . (int)( $sub->trial_days ?? 7 ) . '-day trial)',
                'type'           => 'trial',
                'number'         => $inv_number,
                'date'           => $date_ts,
                'amount'         => $amount,
                'currency'       => $currency,
                'status'         => $inv_status,
                'pdf_url'        => $pdf_url,
                'hosted_url'     => $hosted_url,
                'customer_id'    => $sub->stripe_customer_id ?? '',
                'customer_email' => $sub->owner_email ?? '',
                'expiry_date'    => $sub->trial_ends_at ?? null,
            );
        }

        // Sort all by date descending
        usort( $all_invoices, function( $a, $b ) {
            return (int)$b['date'] - (int)$a['date'];
        } );

        // Summary totals
        $total_revenue = 0;
        $total_count   = count( $all_invoices );
        foreach ( $all_invoices as $inv ) {
            if ( $inv['status'] === 'paid' || $inv['status'] === 'active' || $inv['status'] === 'assigned' ) {
                $total_revenue += (int)$inv['amount'];
            }
        }

        $this->json( array(
            'invoices'      => $all_invoices,
            'total_count'   => $total_count,
            'total_revenue' => $total_revenue,
            'currency'      => $currency_default,
        ) );
    }

    /** Send invoice to customer email via Stripe. */
    public function wpi_resend_invoice() {
        $this->check_nonce();
        if ( ! is_user_logged_in() ) $this->error('Access denied', 403);
        global $wpdb;
        $invoice_id = sanitize_text_field( ($this->input())['invoice_id'] ?? '' );
        if ( ! $invoice_id ) $this->error('invoice_id required');
        $org_id = $this->get_org_id();
        $sub = $wpdb->get_row( $wpdb->prepare(
            "SELECT stripe_customer_id FROM {$wpdb->prefix}wpi_subscriptions WHERE org_id=%d LIMIT 1", $org_id
        ) );
        if ( ! $sub ) $this->error('No subscription found');
        // Verify purchaser
        if ( ! $this->is_system_owner() ) {
            $stripe_customer = \WPI_Billing::stripe_request( 'GET', 'customers/' . $sub->stripe_customer_id );
            if ( ! is_wp_error($stripe_customer) ) {
                $customer_email = strtolower($stripe_customer['email'] ?? '');
                $current_email  = strtolower( get_userdata(get_current_user_id())->user_email );
                if ( $customer_email && $customer_email !== $current_email ) {
                    $this->error('Access denied. Only the subscription purchaser can resend invoices.', 403);
                }
            }
        }
        // Fetch the invoice to check collection_method
        $invoice = \WPI_Billing::stripe_request( 'GET', 'invoices/' . $invoice_id );
        if ( is_wp_error($invoice) ) $this->error($invoice->get_error_message());

        $collection_method = $invoice['collection_method'] ?? 'charge_automatically';
        $hosted_url = $invoice['hosted_invoice_url'] ?? '';
        $customer_email = '';
        if ( ! empty($invoice['customer']) ) {
            $cust = \WPI_Billing::stripe_request( 'GET', 'customers/' . $invoice['customer'] );
            if ( ! is_wp_error($cust) ) $customer_email = $cust['email'] ?? '';
        }

        if ( $collection_method === 'send_invoice' ) {
            // Can use Stripe's send endpoint
            $result = \WPI_Billing::stripe_request( 'POST', 'invoices/' . $invoice_id . '/send' );
            if ( is_wp_error($result) ) $this->error($result->get_error_message());
            $this->json( array('success' => true, 'method' => 'stripe_send') );
        } else {
            // charge_automatically invoices cannot be resent via Stripe API
            // Return the hosted URL so the user can share/view it themselves
            $this->json( array(
                'success'     => false,
                'hosted_url'  => $hosted_url,
                'email'       => $customer_email,
                'message'     => 'This invoice cannot be resent automatically. Use the View button to open and share it.',
            ) );
        }
    }

    /** Cancel subscription at end of current period. */
    public function wpi_cancel_subscription() {
        $this->check_nonce();
        if ( ! $this->can('administrator') ) $this->error('Access denied', 403);
        global $wpdb;

        $org_id = $this->get_org_id();
        $sub    = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wpi_subscriptions WHERE org_id=%d AND status='active' LIMIT 1", $org_id
        ) );
        if ( ! $sub || ! $sub->stripe_subscription_id ) $this->error('No active subscription found');

        $result = WPI_Billing::stripe_request( 'POST',
            'subscriptions/' . $sub->stripe_subscription_id,
            array( 'cancel_at_period_end' => 'true' )
        );
        if ( is_wp_error( $result ) ) $this->error( $result->get_error_message() );

        $wpdb->update( $wpdb->prefix . 'wpi_subscriptions',
            array( 'cancel_at_period_end' => 1, 'updated_at' => current_time('mysql') ),
            array( 'id' => $sub->id )
        );
        $this->json( array( 'success' => true ) );
    }

    /** Resume a cancelled subscription (undo cancel_at_period_end). */
    public function wpi_resume_subscription() {
        $this->check_nonce();
        if ( ! $this->can('administrator') ) $this->error('Access denied', 403);
        global $wpdb;

        $org_id = $this->get_org_id();
        $sub    = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wpi_subscriptions WHERE org_id=%d LIMIT 1", $org_id
        ) );
        if ( ! $sub || ! $sub->stripe_subscription_id ) $this->error('No subscription found');

        $result = WPI_Billing::stripe_request( 'POST',
            'subscriptions/' . $sub->stripe_subscription_id,
            array( 'cancel_at_period_end' => 'false' )
        );
        if ( is_wp_error( $result ) ) $this->error( $result->get_error_message() );

        $wpdb->update( $wpdb->prefix . 'wpi_subscriptions',
            array( 'cancel_at_period_end' => 0, 'status' => 'active', 'updated_at' => current_time('mysql') ),
            array( 'id' => $sub->id )
        );
        $this->json( array( 'success' => true ) );
    }

    // ── System owner: Plan management ─────────────────────────────

    /** Save (create or update) a plan. System owner only. */
    public function wpi_save_plan() {
        $this->check_nonce();
        if ( ! $this->is_system_owner() ) $this->error('Access denied — system owner only', 403);
        global $wpdb;

        $body = $this->input();
        $id   = absint( $body['id'] ?? 0 );

        $limits = array(
            'max_users'       => isset($body['max_users'])       ? (int)$body['max_users']       : -1,
            'max_templates'   => isset($body['max_templates'])   ? (int)$body['max_templates']   : -1,
            'max_inspections' => isset($body['max_inspections']) ? (int)$body['max_inspections'] : -1,
            'max_sites'       => isset($body['max_sites'])       ? (int)$body['max_sites']       : -1,
            'api_access'      => ! empty($body['api_access']),
            'scheduler'       => ! empty($body['scheduler']),
            'custom_branding' => ! empty($body['custom_branding']),
            'annual_enabled'  => ! empty($body['annual_enabled']),
            'is_seat_plan'    => ! empty($body['is_seat_plan']),
        );

        $features = array();
        if ( ! empty($body['features']) && is_array($body['features']) ) {
            $features = array_values( array_filter( array_map( 'sanitize_text_field', $body['features'] ) ) );
        }

        $data = array(
            'name'                    => sanitize_text_field($body['name'] ?? ''),
            'slug'                    => sanitize_title($body['slug'] ?? $body['name'] ?? ''),
            'description'             => sanitize_textarea_field($body['description'] ?? ''),
            'is_active'               => empty($body['is_active']) ? 0 : 1,
            'is_free'                 => empty($body['is_free'])   ? 0 : 1,
            'is_trial'                => empty($body['is_trial'])  ? 0 : 1,
            'trial_days'              => absint($body['trial_days'] ?? 7),
            'sort_order'              => absint($body['sort_order'] ?? 0),
            'monthly_price_cents'     => absint($body['monthly_price_cents'] ?? 0),
            'annual_price_cents'      => absint($body['annual_price_cents']  ?? 0),
            'currency'                => strtoupper( sanitize_text_field($body['currency'] ?? 'AUD') ),
            'stripe_monthly_price_id' => sanitize_text_field($body['stripe_monthly_price_id'] ?? ''),
            'stripe_annual_price_id'  => sanitize_text_field($body['stripe_annual_price_id']  ?? ''),
            'stripe_trial_price_id'   => sanitize_text_field($body['stripe_trial_price_id']   ?? ''),
            'limits'                  => wp_json_encode($limits),
            'features'                => wp_json_encode($features),
            'updated_at'              => current_time('mysql'),
        );

        // Ensure slug is unique - duplicate slug causes silent INSERT failure
        if ( empty( $data['slug'] ) ) {
            $data['slug'] = sanitize_title( $data['name'] ) ?: 'plan';
        }
        $base_slug = $data['slug'];
        $n         = 1;
        $exclude   = $id ? " AND id != $id" : '';
        while ( $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}wpi_plans WHERE slug=%s$exclude LIMIT 1",
            $data['slug']
        ) ) ) {
            $data['slug'] = $base_slug . '-' . ( ++$n );
        }

        if ( $id ) {
            $wpdb->update( $wpdb->prefix . 'wpi_plans', $data, array('id' => $id) );
            if ( $wpdb->last_error ) $this->error( 'Database error: ' . $wpdb->last_error, 500 );
            $this->json( array('success' => true, 'id' => $id) );
        } else {
            $data['created_at'] = current_time('mysql');
            $wpdb->insert( $wpdb->prefix . 'wpi_plans', $data );
            if ( $wpdb->last_error ) $this->error( 'Database error: ' . $wpdb->last_error, 500 );
            $new_id = (int) $wpdb->insert_id;
            if ( ! $new_id ) $this->error( 'Failed to create plan', 500 );
            $this->json( array('success' => true, 'id' => $new_id ) );
        }
    }

    /** Delete a plan. */
    public function wpi_delete_plan() {
        $this->check_nonce();
        if ( ! $this->is_system_owner() ) $this->error('Access denied', 403);
        global $wpdb;
        $id = absint( ($this->input())['id'] ?? 0 );
        if ( ! $id ) $this->error('id required');
        // Don't delete if orgs are subscribed to this plan
        $count = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}wpi_subscriptions WHERE plan_id=%d AND status IN ('active','trialing')", $id
        ) );
        if ( $count > 0 ) $this->error('Cannot delete — ' . $count . ' active subscription(s) on this plan. Deactivate it instead.');
        $wpdb->delete( $wpdb->prefix . 'wpi_plans', array('id' => $id) );
        $this->json( array('success' => true) );
    }

    /** Get Stripe settings. */
    public function wpi_get_stripe_settings() {
        $this->check_nonce();
        if ( ! $this->is_system_owner() ) $this->error('Access denied', 403);
        $cfg = get_option( 'wpi_stripe_settings', array() );
        // Mask secret keys for display
        $mask = function($k) { return $k ? substr($k,0,8) . str_repeat('•',max(0,strlen($k)-12)) . substr($k,-4) : ''; };
        $this->json( array(
            'live_mode'             => ! empty($cfg['live_mode']),
            'test_publishable_key'  => $cfg['test_publishable_key']  ?? '',
            'test_secret_key'       => $mask($cfg['test_secret_key']  ?? ''),
            'live_publishable_key'  => $cfg['live_publishable_key']  ?? '',
            'live_secret_key'       => $mask($cfg['live_secret_key']  ?? ''),
            'webhook_secret'        => $mask($cfg['webhook_secret']   ?? ''),
            'webhook_url'           => home_url( '/?wpi_stripe_webhook=1' ),
        ) );
    }

    /** Save Stripe settings. */
    public function wpi_save_stripe_settings() {
        $this->check_nonce();
        if ( ! $this->is_system_owner() ) $this->error('Access denied', 403);
        $body = $this->input();
        $cfg  = get_option( 'wpi_stripe_settings', array() );

        $cfg['live_mode'] = ! empty($body['live_mode']);
        // Only update keys if they contain new values (not masked)
        foreach ( array('test_publishable_key','test_secret_key','live_publishable_key','live_secret_key','webhook_secret') as $k ) {
            if ( isset($body[$k]) && strpos($body[$k], '•') === false && $body[$k] !== '' ) {
                $cfg[$k] = sanitize_text_field($body[$k]);
            }
        }
        update_option( 'wpi_stripe_settings', $cfg );
        $this->json( array('success' => true) );
    }


    // ═══════════════════════════════════════════════════════════════
    // ── Seat Management Handlers ────────────────────────────────────
    // ═══════════════════════════════════════════════════════════════

    /** Get org seat summary — total purchased, used, available. */
    public function wpi_get_org_seats() {
        $this->check_nonce();
        if ( ! is_user_logged_in() ) $this->error('Access denied', 403);
        global $wpdb;
        $org_id = $this->get_org_id() ?: $this->get_or_create_billing_org_id();
        if ( ! $org_id ) { $this->json( array('seats'=>array(),'total'=>0,'used'=>0,'available'=>0,'plan_limit'=>-1) ); return; }

        // Self-heal: add pending_seats_total column if missing
        $cols = $wpdb->get_col( "SHOW COLUMNS FROM `{$wpdb->prefix}wpi_org_seats`", 0 );
        if ( $cols && ! in_array('pending_seats_total', $cols) ) {
            $wpdb->query( "ALTER TABLE `{$wpdb->prefix}wpi_org_seats` ADD COLUMN `pending_seats_total` INT(11) DEFAULT NULL AFTER `seats_total`" );
        }

        $seats = $wpdb->get_results( $wpdb->prepare(
            "SELECT s.*, COUNT(a.id) as assigned_count
             FROM {$wpdb->prefix}wpi_org_seats s
             LEFT JOIN {$wpdb->prefix}wpi_seat_assignments a ON a.seat_id=s.id AND a.status='assigned'
             WHERE s.org_id=%d AND s.status='active'
             GROUP BY s.id ORDER BY s.created_at DESC",
            $org_id
        ) );

        $total   = 0;
        $used    = 0;
        foreach ( $seats as $s ) {
            $total += (int)$s->seats_total;
            $used  += (int)$s->assigned_count;
        }

        // Also count users covered by plan subscription
        $plan_limit = -1;
        $sub = \WPI_Billing::get_org_plan( $org_id );
        if ( $sub ) {
            $limits     = \WPI_Billing::get_limits( $org_id );
            $plan_limit = $limits['max_users'] ?? -1;
        }

        $this->json( array(
            'seats'       => $seats,
            'total'       => $total,
            'used'        => $used,
            'available'   => max(0, $total - $used),
            'plan_limit'  => $plan_limit,
        ) );
    }

    /* =========================================================
     * BILLING ADMIN -- system owner tools
     * ========================================================= */

    /** Get all orgs with their subscriptions and seats for the billing admin panel. */
    public function wpi_billing_admin_get() {
        $this->check_nonce();
        if ( ! $this->is_system_owner() ) $this->error('Access denied', 403);
        global $wpdb;

        $orgs = $wpdb->get_results(
            "SELECT o.id, o.name,
                    s.id as sub_id, s.status, s.plan_id, s.billing_cycle,
                    s.current_period_end, s.cancel_at_period_end,
                    s.stripe_subscription_id, s.stripe_customer_id,
                    p.name as plan_name,
                    (SELECT COUNT(*) FROM {$wpdb->prefix}wpi_org_users ou WHERE ou.org_id=o.id) as user_count
             FROM {$wpdb->prefix}wpi_organisations o
             LEFT JOIN {$wpdb->prefix}wpi_subscriptions s ON s.org_id=o.id
               AND s.status NOT IN ('checkout_abandoned')
             LEFT JOIN {$wpdb->prefix}wpi_plans p ON p.id=s.plan_id
             ORDER BY o.name ASC"
        );

        $plans = $wpdb->get_results(
            "SELECT id, name, monthly_price_cents, annual_price_cents, currency
             FROM {$wpdb->prefix}wpi_plans WHERE is_active=1 AND is_active=1
             ORDER BY name ASC"
        );

        // Seat records per org
        $seat_rows = $wpdb->get_results(
            "SELECT s.*, COUNT(a.id) as assigned_count
             FROM {$wpdb->prefix}wpi_org_seats s
             LEFT JOIN {$wpdb->prefix}wpi_seat_assignments a ON a.seat_id=s.id AND a.status='assigned'
             GROUP BY s.id ORDER BY s.org_id ASC"
        );

        // Cast IDs to int so JS === comparison works
        foreach ( $orgs as $o ) { $o->id = (int)$o->id; $o->sub_id = $o->sub_id ? (int)$o->sub_id : null; $o->plan_id = (int)$o->plan_id; }
        foreach ( $seat_rows as $s ) { $s->id = (int)$s->id; $s->org_id = (int)$s->org_id; $s->seats_total = (int)$s->seats_total; $s->assigned_count = (int)$s->assigned_count; }
        foreach ( $plans as $p ) { $p->id = (int)$p->id; }
        $this->json(array(
            'orgs'  => array_values($orgs  ?: array()),
            'plans' => array_values($plans ?: array()),
            'seats' => array_values($seat_rows ?: array()),
        ));
    }

    /** Create or update a subscription record manually (no Stripe required). */
    public function wpi_billing_admin_save_sub() {
        $this->check_nonce();
        if ( ! $this->is_system_owner() ) $this->error('Access denied', 403);
        global $wpdb;
        $body   = $this->input();
        $sub_id = absint($body['sub_id'] ?? 0);
        $org_id = absint($body['org_id'] ?? 0);
        if ( ! $org_id ) $this->error('org_id required');

        $allowed_statuses = array('active','trialing','past_due','unpaid','canceled','checkout_abandoned','pending');
        $raw_status = sanitize_text_field($body['status'] ?? 'active');
        $sub_status = in_array($raw_status, $allowed_statuses, true) ? $raw_status : 'active';

        $data = array(
            'org_id'               => $org_id,
            'plan_id'              => absint($body['plan_id'] ?? 0) ?: null,
            'status'               => $sub_status,
            'billing_cycle'        => sanitize_text_field($body['billing_cycle'] ?? 'monthly'),
            'current_period_end'   => !empty($body['period_end']) ? sanitize_text_field($body['period_end']) : null,
            'cancel_at_period_end' => !empty($body['cancel_at_period_end']) ? 1 : 0,
            'stripe_subscription_id' => sanitize_text_field($body['stripe_subscription_id'] ?? ''),
            'stripe_customer_id'   => sanitize_text_field($body['stripe_customer_id'] ?? ''),
            'updated_at'           => current_time('mysql'),
        );

        if ( $sub_id ) {
            $wpdb->update($wpdb->prefix.'wpi_subscriptions', $data, array('id'=>$sub_id));
            if ( $wpdb->last_error ) $this->error('DB error: '.$wpdb->last_error);
        } else {
            $data['created_at'] = current_time('mysql');
            $wpdb->insert($wpdb->prefix.'wpi_subscriptions', $data);
            if ( $wpdb->last_error ) $this->error('DB error: '.$wpdb->last_error);
            $sub_id = $wpdb->insert_id;
        }

        // Sync org licence/access based on new status
        if ( class_exists('WPI_Billing') ) {
            if ( in_array($data['status'], array('active','trialing'), true) ) {
                WPI_Billing::unlock_org_team($org_id);
                WPI_Billing::sync_org_licence_from_subscription($org_id, $data['status'], $data['billing_cycle']);
            } elseif ( in_array($data['status'], array('canceled','unpaid'), true) ) {
                WPI_Billing::lock_org_team($org_id);
            }
        }

        $this->json(array('success'=>true,'sub_id'=>$sub_id));
    }

    /** Create or update a seat record manually. */
    public function wpi_billing_admin_save_seats() {
        $this->check_nonce();
        if ( ! $this->is_system_owner() ) $this->error('Access denied', 403);
        global $wpdb;

        // Self-heal columns
        $sc = $wpdb->get_col("SHOW COLUMNS FROM `{$wpdb->prefix}wpi_org_seats`", 0);
        if ($sc) {
            if (!in_array('stripe_subscription_id',$sc)) $wpdb->query("ALTER TABLE `{$wpdb->prefix}wpi_org_seats` ADD COLUMN `stripe_subscription_id` VARCHAR(100) NOT NULL DEFAULT ''");
            if (!in_array('billing_cycle',$sc))          $wpdb->query("ALTER TABLE `{$wpdb->prefix}wpi_org_seats` ADD COLUMN `billing_cycle` VARCHAR(20) NOT NULL DEFAULT 'monthly'");
            if (!in_array('renewal_date',$sc))           $wpdb->query("ALTER TABLE `{$wpdb->prefix}wpi_org_seats` ADD COLUMN `renewal_date` DATETIME DEFAULT NULL");
        }

        $body    = $this->input();
        $seat_id = absint($body['seat_id'] ?? 0);
        $org_id  = absint($body['org_id'] ?? 0);
        if ( ! $org_id ) $this->error('org_id required');

        $data = array(
            'org_id'                 => $org_id,
            'purchased_by'           => absint($body['purchased_by'] ?? get_current_user_id()),
            'seats_total'            => absint($body['seats_total'] ?? 1),
            'seats_used'             => absint($body['seats_used'] ?? 0),
            'price_per_seat_cents'   => absint($body['price_per_seat_cents'] ?? 0),
            'currency'               => strtoupper(sanitize_text_field($body['currency'] ?? 'AUD')),
            'stripe_subscription_id' => sanitize_text_field($body['stripe_subscription_id'] ?? ''),
            'stripe_customer_id'     => sanitize_text_field($body['stripe_customer_id'] ?? ''),
            'status'                 => sanitize_text_field($body['status'] ?? 'active'),
            'billing_cycle'          => sanitize_text_field($body['billing_cycle'] ?? 'monthly'),
            'renewal_date'           => !empty($body['renewal_date']) ? sanitize_text_field($body['renewal_date']) : null,
            'updated_at'             => current_time('mysql'),
        );

        if ( $seat_id ) {
            $wpdb->update($wpdb->prefix.'wpi_org_seats', $data, array('id'=>$seat_id));
            if ( $wpdb->last_error ) $this->error('DB error: '.$wpdb->last_error);
        } else {
            $data['created_at'] = current_time('mysql');
            $wpdb->insert($wpdb->prefix.'wpi_org_seats', $data);
            if ( $wpdb->last_error ) $this->error('DB error: '.$wpdb->last_error);
            $seat_id = $wpdb->insert_id;
        }

        $this->json(array('success'=>true,'seat_id'=>$seat_id));
    }

    /** Delete a subscription record. */
    public function wpi_billing_admin_delete_sub() {
        $this->check_nonce();
        if ( ! $this->is_system_owner() ) $this->error('Access denied', 403);
        global $wpdb;
        $sub_id = absint(($this->input())['sub_id'] ?? 0);
        if ( !$sub_id ) $this->error('sub_id required');
        $wpdb->delete($wpdb->prefix.'wpi_subscriptions', array('id'=>$sub_id));
        $this->json(array('success'=>true));
    }

    /** Delete a seat record. */
    public function wpi_billing_admin_delete_seats() {
        $this->check_nonce();
        if ( ! $this->is_system_owner() ) $this->error('Access denied', 403);
        global $wpdb;
        $seat_id = absint(($this->input())['seat_id'] ?? 0);
        if ( !$seat_id ) $this->error('seat_id required');
        $wpdb->delete($wpdb->prefix.'wpi_org_seats', array('id'=>$seat_id));
        $wpdb->delete($wpdb->prefix.'wpi_seat_assignments', array('seat_id'=>$seat_id));
        $this->json(array('success'=>true));
    }

    /** Confirm a seat checkout session - fetch from Stripe and record seats immediately. */
    public function wpi_confirm_seat_session() {
        $this->check_nonce();
        if ( ! is_user_logged_in() ) $this->error('Access denied', 403);
        global $wpdb;

        $body       = $this->input();
        $session_id = sanitize_text_field( $body['session_id'] ?? '' );
        if ( ! $session_id ) $this->error('session_id required');

        $session = \WPI_Billing::stripe_request( 'GET', 'checkout/sessions/' . $session_id );
        if ( is_wp_error($session) ) $this->error('Could not verify session: ' . $session->get_error_message() );

        $meta          = $session['metadata'] ?? array();
        $type          = $meta['type'] ?? '';
        $org_id        = absint( $meta['org_id'] ?? 0 );
        $seat_qty      = absint( $meta['seat_qty'] ?? 0 );
        $purchased_by  = absint( $meta['purchased_by'] ?? get_current_user_id() );
        $stripe_sub_id = $session['subscription'] ?? '';
        $customer_id   = $session['customer'] ?? '';
        $amount        = absint( $session['amount_total'] ?? 0 );
        $currency      = strtoupper( $session['currency'] ?? 'AUD' );
        $pay_status    = $session['payment_status'] ?? '';

        if ( $type !== 'seat_subscription' ) $this->error('Not a seat session');
        if ( $pay_status !== 'paid' ) $this->error('Payment not completed yet. Please wait a moment and refresh.');
        if ( ! $org_id || ! $seat_qty ) $this->error('Session metadata incomplete');

        $my_org = $this->get_org_id() ?: $this->get_or_create_billing_org_id();
        if ( (int)$my_org !== (int)$org_id && ! $this->is_system_owner() ) {
            $this->error('Access denied', 403);
        }

        $price_each = $seat_qty > 0 ? intdiv($amount, $seat_qty) : 0;

        // Self-heal: add missing columns to wpi_org_seats if this is an older install
        $seat_cols = $wpdb->get_col( "SHOW COLUMNS FROM `{$wpdb->prefix}wpi_org_seats`", 0 );
        if ( $seat_cols ) {
            if ( ! in_array('pending_seats_total', $seat_cols) )
                $wpdb->query( "ALTER TABLE `{$wpdb->prefix}wpi_org_seats` ADD COLUMN `pending_seats_total` INT(11) DEFAULT NULL AFTER `seats_total`" );
            if ( ! in_array('stripe_subscription_id', $seat_cols) )
                $wpdb->query( "ALTER TABLE `{$wpdb->prefix}wpi_org_seats` ADD COLUMN `stripe_subscription_id` VARCHAR(100) NOT NULL DEFAULT '' AFTER `stripe_customer_id`" );
            if ( ! in_array('billing_cycle', $seat_cols) )
                $wpdb->query( "ALTER TABLE `{$wpdb->prefix}wpi_org_seats` ADD COLUMN `billing_cycle` VARCHAR(20) NOT NULL DEFAULT 'monthly' AFTER `status`" );
            if ( ! in_array('renewal_date', $seat_cols) )
                $wpdb->query( "ALTER TABLE `{$wpdb->prefix}wpi_org_seats` ADD COLUMN `renewal_date` DATETIME DEFAULT NULL AFTER `billing_cycle`" );
        }

        $exists = $stripe_sub_id ? $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}wpi_org_seats WHERE stripe_subscription_id=%s LIMIT 1",
            $stripe_sub_id
        ) ) : null;

        if ( ! $exists ) {
            $wpdb->insert( $wpdb->prefix . 'wpi_org_seats', array(
                'org_id'                 => $org_id,
                'purchased_by'           => $purchased_by,
                'seats_total'            => $seat_qty,
                'seats_used'             => 0,
                'price_per_seat_cents'   => $price_each,
                'currency'               => $currency,
                'stripe_subscription_id' => $stripe_sub_id,
                'stripe_customer_id'     => $customer_id,
                'status'                 => 'active',
                'billing_cycle'          => 'monthly',
                'renewal_date'           => date('Y-m-d H:i:s', strtotime('+1 month')),
                'created_at'             => current_time('mysql'),
                'updated_at'             => current_time('mysql'),
            ) );
            if ( $wpdb->last_error ) $this->error('DB error: ' . $wpdb->last_error);
        }

        $this->json( array('success' => true, 'seats' => $seat_qty, 'already_recorded' => (bool)$exists) );
    }

    /** Resume a pending seat reduction -- restore original quantity. */
    public function wpi_resume_seat_reduction() {
        $this->check_nonce();
        if ( ! is_user_logged_in() ) $this->error('Access denied', 403);
        global $wpdb;
        $seat_id = absint( ($this->input())['seat_id'] ?? 0 );
        $org_id  = $this->get_org_id() ?: $this->get_or_create_billing_org_id();
        $seat = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wpi_org_seats WHERE id=%d AND org_id=%d", $seat_id, $org_id
        ) );
        if ( ! $seat ) $this->error('Seat record not found');
        if ( $seat->pending_seats_total === null ) $this->error('No pending reduction to resume');
        // Only the purchaser (or system owner / WP admin) can manage this seat record
        $current_uid = get_current_user_id();
        if ( (int)$seat->purchased_by !== $current_uid && ! $this->is_system_owner() && ! current_user_can('manage_options') ) {
            $this->error('Access denied. Only the user who purchased these seats can manage them.', 403);
        }


        // Restore original quantity on Stripe
        if ( $seat->stripe_subscription_id ) {
            $stripe_sub = \WPI_Billing::stripe_request('GET', 'subscriptions/'.$seat->stripe_subscription_id);
            if ( !is_wp_error($stripe_sub) && !empty($stripe_sub['items']['data'][0]['id']) ) {
                $item_id = $stripe_sub['items']['data'][0]['id'];
                \WPI_Billing::stripe_request('POST', 'subscription_items/'.$item_id,
                    array('quantity' => (int)$seat->seats_total, 'proration_behavior' => 'none')
                );
            }
        }

        // Clear pending reduction
        $wpdb->update( $wpdb->prefix.'wpi_org_seats',
            array('pending_seats_total' => null, 'updated_at' => current_time('mysql')),
            array('id' => $seat_id)
        );
        $this->json(array('success' => true));
    }

    /** Create Stripe checkout for purchasing seats. */
    public function wpi_buy_seats() {
        $this->check_nonce();
        if ( ! is_user_logged_in() ) $this->error('Access denied', 403);
        global $wpdb;

        $body       = $this->input();
        $qty        = absint( $body['quantity'] ?? 1 );
        $price_id   = sanitize_text_field( $body['price_id'] ?? '' );
        $org_id     = $this->get_org_id() ?: $this->get_or_create_billing_org_id();
        if ( ! $org_id ) $this->error('Unable to create organisation for this purchase. Please contact support.', 500);
        $uid        = get_current_user_id();

        if ( $qty < 1 || $qty > 100 ) $this->error('Quantity must be between 1 and 100');
        if ( ! $price_id ) $this->error('Seat price not configured');

        $user = get_userdata( $uid );
        $org  = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wpi_organisations WHERE id=%d", $org_id
        ) );

        // Get or create Stripe customer
        $existing_sub = $wpdb->get_row( $wpdb->prepare(
            "SELECT stripe_customer_id FROM {$wpdb->prefix}wpi_subscriptions WHERE org_id=%d ORDER BY id DESC LIMIT 1", $org_id
        ) );
        $customer_id = $existing_sub ? $existing_sub->stripe_customer_id : '';

        if ( ! $customer_id ) {
            $customer = \WPI_Billing::stripe_request( 'POST', 'customers', array(
                'email'    => $user->user_email,
                'name'     => $org ? $org->name : $user->display_name,
                'metadata' => array( 'org_id' => $org_id, 'wp_user_id' => $uid ),
            ) );
            if ( is_wp_error($customer) ) $this->error( $customer->get_error_message() );
            $customer_id = $customer['id'];
        }

        $success_url = home_url( '/?wpi=1&seats_purchased=' . $qty . '&session_id={CHECKOUT_SESSION_ID}' );
        $cancel_url  = home_url( '/?wpi=1' );

        $session = \WPI_Billing::stripe_request( 'POST', 'checkout/sessions', array(
            'customer'             => $customer_id,
            'mode'                 => 'subscription',
            'payment_method_types' => array( 'card' ),
            'line_items'           => array(
                array( 'price' => $price_id, 'quantity' => $qty )
            ),
            'success_url'          => $success_url,
            'cancel_url'           => $cancel_url,
            'metadata'             => array(
                'org_id'       => $org_id,
                'seat_qty'     => $qty,
                'purchased_by' => $uid,
                'type'         => 'seat_subscription',
            ),
            'subscription_data'    => array(
                'metadata' => array(
                    'org_id'       => $org_id,
                    'seat_qty'     => $qty,
                    'purchased_by' => $uid,
                    'type'         => 'seat_subscription',
                )
            ),
            'allow_promotion_codes' => 'true',
        ) );
        if ( is_wp_error($session) ) $this->error( $session->get_error_message() );

        $this->json( array( 'checkout_url' => $session['url'] ) );
    }

    /** Update seat quantity on an existing Stripe subscription. */
    public function wpi_update_seat_qty() {
        $this->check_nonce();
        if ( ! $this->can('administrator') ) $this->error('Access denied', 403);
        global $wpdb;
        $body    = $this->input();
        $seat_id = absint( $body['seat_id'] ?? 0 );
        $new_qty = absint( $body['quantity'] ?? 0 );
        $org_id  = $this->get_org_id();
        if ( $new_qty < 1 ) $this->error('Minimum 1 seat required');
        $seat = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wpi_org_seats WHERE id=%d AND org_id=%d", $seat_id, $org_id
        ) );
        if ( ! $seat ) $this->error('Seat plan not found');
        $used = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}wpi_seat_assignments WHERE seat_id=%d AND status='assigned'", $seat_id
        ) );
        if ( $new_qty < $used ) $this->error('Cannot reduce below ' . $used . ' assigned seats');
        // Only the purchaser (or system owner / WP admin) can manage this seat record
        $current_uid = get_current_user_id();
        if ( (int)$seat->purchased_by !== $current_uid && ! $this->is_system_owner() && ! current_user_can('manage_options') ) {
            $this->error('Access denied. Only the user who purchased these seats can manage them.', 403);
        }


        // INCREASES require a valid Stripe subscription -- no free upgrades
        if ( $new_qty > (int)$seat->seats_total ) {
            if ( empty( $seat->stripe_subscription_id ) ) {
                $this->error('Cannot increase seats: no Stripe subscription linked. Please purchase additional seats through checkout.', 403);
            }
        }

        if ( $seat->stripe_subscription_id ) {
            // Must update Stripe FIRST -- only update DB if Stripe confirms
            $stripe_sub = WPI_Billing::stripe_request('GET', 'subscriptions/'.$seat->stripe_subscription_id);
            if ( is_wp_error($stripe_sub) ) {
                $this->error('Could not reach Stripe: ' . $stripe_sub->get_error_message());
            }
            if ( empty($stripe_sub['items']['data'][0]['id']) ) {
                $this->error('Subscription item not found in Stripe. Contact support.');
            }
            $item_id = $stripe_sub['items']['data'][0]['id'];
            // Reductions take effect at period end (no proration credit - user keeps access they paid for)
            // Increases charge prorated immediately
            $is_increase = $new_qty > (int)$seat->seats_total;
            $proration = $is_increase ? 'always_invoice' : 'none';
            $stripe_result = WPI_Billing::stripe_request('POST', 'subscription_items/'.$item_id,
                array('quantity' => $new_qty, 'proration_behavior' => $proration)
            );
            if ( is_wp_error($stripe_result) ) {
                $this->error('Stripe update failed: ' . $stripe_result->get_error_message());
            }
            if ( empty($stripe_result['id']) ) {
                $this->error('Stripe did not confirm the change. No changes were made.');
            }
        } else {
            // No Stripe subscription linked -- block increase without payment
            if ( $new_qty > (int)$seat->seats_total ) {
                $this->error('Cannot increase seats without a linked Stripe subscription. Purchase additional seats instead.', 403);
            }
        }
        if ( $is_increase ) {
            // Increase: update immediately - Stripe charged prorated amount
            $wpdb->update($wpdb->prefix.'wpi_org_seats',
                array('seats_total'=>$new_qty,'updated_at'=>current_time('mysql')),
                array('id'=>$seat_id));
        } else {
            // Reduction: keep current seats_total, store pending change for end of period
            $renewal = $wpdb->get_var($wpdb->prepare(
                "SELECT renewal_date FROM {$wpdb->prefix}wpi_org_seats WHERE id=%d", $seat_id
            ));
            $wpdb->update($wpdb->prefix.'wpi_org_seats',
                array('pending_seats_total'=>$new_qty,'updated_at'=>current_time('mysql')),
                array('id'=>$seat_id));
        }
        $this->json(array('success'=>true,'new_qty'=>$new_qty,'effective'=>$is_increase?'immediate':'period_end'));
    }

    /** Cancel a seat subscription at end of billing period. */
    public function wpi_cancel_seat_sub() {
        $this->check_nonce();
        if ( ! $this->can('administrator') ) $this->error('Access denied', 403);
        global $wpdb;
        $seat_id = absint( ($this->input())['seat_id'] ?? 0 );
        $org_id  = $this->get_org_id();
        $seat = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wpi_org_seats WHERE id=%d AND org_id=%d", $seat_id, $org_id
        ));
        if ( !$seat ) $this->error('Seat plan not found');
        // Only purchaser or system owner can cancel
        $current_uid = get_current_user_id();
        if ( (int)$seat->purchased_by !== $current_uid && ! $this->is_system_owner() && ! current_user_can('manage_options') ) {
            $this->error('Access denied. Only the user who purchased these seats can cancel them.', 403);
        }
        if ( $seat->stripe_subscription_id ) {
            WPI_Billing::stripe_request('POST', 'subscriptions/'.$seat->stripe_subscription_id,
                array('cancel_at_period_end'=>'true'));
        }
        $wpdb->update($wpdb->prefix.'wpi_org_seats',
            array('status'=>'cancelling','updated_at'=>current_time('mysql')),
            array('id'=>$seat_id));
        $this->json(array('success'=>true));
    }

    /** Get the seat price configuration for this org. */
    public function wpi_get_seat_price() {
        $this->check_nonce();
        global $wpdb;
        if ( ! is_user_logged_in() ) $this->error('Access denied', 403);
        // Always read from global -- system owner sets one price for all orgs
        $config = get_option( 'wpi_seat_price_global', array() );

        // Fall back to the seat plan from Plans & Pricing if global config not set
        if ( empty( $config ) || empty( $config['stripe_monthly'] ) ) {
            $seat_plan = $wpdb->get_row(
                "SELECT * FROM {$wpdb->prefix}wpi_plans
                 WHERE is_active=1
                 ORDER BY id ASC LIMIT 1"
            );
            // Find a seat plan
            $all_plans = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}wpi_plans WHERE is_active=1" );
            $seat_plan = null;
            foreach ( $all_plans as $p ) {
                $lim = $p->limits ? json_decode( $p->limits, true ) : array();
                if ( ! empty( $lim['is_seat_plan'] ) ) { $seat_plan = $p; break; }
            }
            if ( $seat_plan ) {
                $annual_cents = (int) $seat_plan->annual_price_cents;
                $lim          = $seat_plan->limits ? json_decode( $seat_plan->limits, true ) : array();
                $annual_enabled = ! empty( $lim['annual_enabled'] );
                $config = array(
                    'monthly_cents'  => (int) $seat_plan->monthly_price_cents,
                    'annual_cents'   => $annual_enabled ? $annual_cents : 0,
                    'currency'       => $seat_plan->currency ?: 'AUD',
                    'stripe_monthly' => $seat_plan->stripe_monthly_price_id ?: '',
                    'stripe_annual'  => $annual_enabled ? ( $seat_plan->stripe_annual_price_id ?: '' ) : '',
                    'from_plan'      => true,
                    'plan_id'        => (int) $seat_plan->id,
                    'plan_name'      => $seat_plan->name,
                );
            }
        }

        $this->json( $config ?: null );
    }

    /** Save the seat price configuration. WP admin or system owner. */
    public function wpi_save_seat_price() {
        $this->check_nonce();
        if ( ! $this->is_system_owner() ) $this->error('Access denied - system owner only', 403);
        $body   = $this->input();
        $config = array(
            'price_cents'     => absint( $body['monthly_cents'] ?? $body['price_cents'] ?? 0 ),
            'monthly_cents'   => absint( $body['monthly_cents'] ?? $body['price_cents'] ?? 0 ),
            'annual_cents'    => absint( $body['annual_cents']  ?? 0 ),
            'currency'        => strtoupper( sanitize_text_field( $body['currency']       ?? 'AUD' ) ),
            'stripe_price_id' => sanitize_text_field( $body['stripe_monthly'] ?? $body['stripe_price_id'] ?? '' ),
            'stripe_monthly'  => sanitize_text_field( $body['stripe_monthly'] ?? $body['stripe_price_id'] ?? '' ),
            'stripe_annual'   => sanitize_text_field( $body['stripe_annual']  ?? '' ),
        );
        // Store globally (all orgs share the same seat price set by system owner)
        update_option( 'wpi_seat_price_global', $config );
        $this->json( array( 'success' => true ) );
    }

    /** Assign a purchased seat to a user or email. */
    public function wpi_assign_seat() {
        $this->check_nonce();
        if ( ! $this->can('administrator') ) $this->error('Access denied', 403);
        global $wpdb;

        $body    = $this->input();
        $user_id = absint( $body['user_id'] ?? 0 );
        $email   = sanitize_email( $body['email'] ?? '' );
        $org_id  = $this->get_org_id();
        $uid     = get_current_user_id();

        if ( ! $user_id && ! $email ) $this->error('user_id or email required');

        // Only purchaser or system owner/admin can assign seats
        $pb_check = $wpdb->get_var( $wpdb->prepare(
            "SELECT purchased_by FROM {$wpdb->prefix}wpi_org_seats WHERE org_id=%d AND status='active' LIMIT 1",
            $org_id
        ) );
        if ( $pb_check && (int)$pb_check !== (int)$uid && ! $this->is_system_owner() && ! current_user_can('manage_options') ) {
            $this->error('Access denied. Only the user who purchased the seats can assign them.', 403);
        }
        // Find available seat
        $seat = $wpdb->get_row( $wpdb->prepare(
            "SELECT s.id, s.seats_total,
             (SELECT COUNT(*) FROM {$wpdb->prefix}wpi_seat_assignments a WHERE a.seat_id=s.id AND a.status='assigned') as used
             FROM {$wpdb->prefix}wpi_org_seats s
             WHERE s.org_id=%d AND s.status='active'
             HAVING used < s.seats_total
             ORDER BY s.created_at ASC LIMIT 1",
            $org_id
        ) );
        if ( ! $seat ) $this->error('No available seats. Please purchase more seats first.' );

        // Check not already assigned
        if ( $user_id ) {
            $already = $wpdb->get_var( $wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}wpi_seat_assignments WHERE org_id=%d AND user_id=%d AND status='assigned'",
                $org_id, $user_id
            ) );
            if ( $already ) $this->error('This user already has a seat assigned');
        }

        $wpdb->insert( $wpdb->prefix.'wpi_seat_assignments', array(
            'org_id'      => $org_id,
            'seat_id'     => $seat->id,
            'user_id'     => $user_id ?: null,
            'email'       => $email,
            'assigned_by' => $uid,
            'status'      => 'assigned',
            'created_at'  => current_time('mysql'),
        ) );

        // Grant access if assigning to existing user
        if ( $user_id ) {
            delete_user_meta( $user_id, 'wpi_access_basic' );
        }

        $this->json( array( 'success' => true ) );
    }

    /** Unassign/revoke a seat. */
    public function wpi_unassign_seat() {
        $this->check_nonce();
        if ( ! $this->can('administrator') ) $this->error('Access denied', 403);
        global $wpdb;

        $body    = $this->input();
        $id      = absint( $body['id']      ?? 0 );
        $user_id = absint( $body['user_id'] ?? 0 );
        $org_id  = $this->get_org_id();
        $unassign_uid = get_current_user_id();

        // Only purchaser or system owner/admin can unassign
        $pb_check2 = $wpdb->get_var( $wpdb->prepare(
            "SELECT purchased_by FROM {$wpdb->prefix}wpi_org_seats WHERE org_id=%d AND status='active' LIMIT 1",
            $org_id
        ) );
        if ( $pb_check2 && (int)$pb_check2 !== (int)$unassign_uid && ! $this->is_system_owner() && ! current_user_can('manage_options') ) {
            $this->error('Access denied. Only the user who purchased the seats can unassign them.', 403);
        }

        if ( $user_id ) {
            // Unassign by user_id — find the assignment record
            $assign = $wpdb->get_row( $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}wpi_seat_assignments WHERE user_id=%d AND org_id=%d AND status='assigned'",
                $user_id, $org_id
            ) );
        } else {
            $assign = $wpdb->get_row( $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}wpi_seat_assignments WHERE id=%d AND org_id=%d", $id, $org_id
            ) );
        }
        if ( ! $assign ) $this->error('Not found');

        $wpdb->update( $wpdb->prefix.'wpi_seat_assignments',
            array( 'status' => 'revoked' ),
            array( 'id' => $assign->id )
        );

        // Revoke access if user no longer covered by plan
        if ( $assign->user_id ) {
            // Re-check if covered by org subscription
            $covered = \WPI_Billing::get_org_plan( $org_id );
            if ( ! $covered ) {
                update_user_meta( (int)$assign->user_id, 'wpi_access_basic', 1 );
            }
        }

        $this->json( array( 'success' => true ) );
    }

    public function wpi_get_activity_log() {
        $this->check_nonce();
        global $wpdb;
        $type = sanitize_text_field( $_GET['object_type'] ?? '' );
        $id   = absint( $_GET['object_id'] ?? 0 );
        if ( ! $type || ! $id ) $this->error( 'object_type and object_id required' );

        // Access check
        if ( ! $this->can('basic') ) $this->error( 'Access denied', 403 );

        // Org-scope: non-system-owners can only view log entries for their own org
        $org_where = '';
        if ( ! $this->is_system_owner() ) {
            $my_org = $this->get_org_id();
            if ( $my_org ) {
                $org_where = $wpdb->prepare( ' AND org_id = %d', $my_org );
            } else {
                // No org — only own entries
                $org_where = $wpdb->prepare( ' AND user_id = %d', get_current_user_id() );
            }
        }

        $rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wpi_activity_log
             WHERE object_type=%s AND object_id=%d{$org_where}
             ORDER BY created_at DESC LIMIT 50",
            $type, $id
        ) );
        $this->json( $rows ?: array() );
    }

    /**
     * Step 1 — Send 6-digit verification code to email before registration.
     */
    public function wpi_send_verify_code() {
        if ( ! get_option( 'wpi_registration_enabled', 0 ) ) {
            wp_send_json_error( array( 'message' => 'Registration is currently disabled.' ) );
            return;
        }
        $email = sanitize_email( $_POST['email'] ?? '' );
        if ( ! is_email( $email ) ) {
            wp_send_json_error( array( 'message' => 'A valid email address is required.' ) );
            return;
        }
        $rate_key = 'wpi_vcode_rate_' . md5( strtolower( $email ) );
        $hits     = (int) get_transient( $rate_key );
        if ( $hits >= 5 ) {
            wp_send_json_error( array( 'message' => 'Too many requests. Please wait an hour and try again.' ) );
            return;
        }
        set_transient( $rate_key, $hits + 1, HOUR_IN_SECONDS );
        if ( email_exists( $email ) ) {
            wp_send_json_error( array( 'message' => 'An account with this email already exists. Please sign in.' ) );
            return;
        }
        $code      = str_pad( (string) random_int( 100000, 999999 ), 6, '0', STR_PAD_LEFT );
        $trans_key = 'wpi_vcode_' . md5( strtolower( $email ) );
        set_transient( $trans_key, $code, 10 * MINUTE_IN_SECONDS );

        $site      = get_bloginfo( 'name' ) ?: 'Audit4me';
        $site_safe = esc_html( $site );
        $code_safe = esc_html( $code );

        $body  = "<div style=\"font-family:Arial,sans-serif;max-width:480px;margin:0 auto;\">";
        $body .= "<div style=\"background:#1a3a5c;padding:24px 32px;border-radius:12px 12px 0 0;text-align:center;\">";
        $body .= "<h2 style=\"color:#fff;margin:0;font-size:20px;\">{$site_safe}</h2>";
        $body .= "<p style=\"color:rgba(255,255,255,.75);font-size:13px;margin:6px 0 0;\">Email Verification</p></div>";
        $body .= "<div style=\"background:#fff;padding:32px;border-radius:0 0 12px 12px;border:1.5px solid #e2e8f0;\">";
        $body .= "<p style=\"color:#374151;font-size:14px;margin:0 0 20px;\">Enter this code to complete your registration. It expires in <strong>10 minutes</strong>.</p>";
        $body .= "<div style=\"background:#f0f4ff;border:2px solid #c7d2fe;border-radius:12px;padding:24px;text-align:center;margin-bottom:20px;\">";
        $body .= "<p style=\"margin:0 0 6px;font-size:11px;font-weight:700;color:#4f46e5;text-transform:uppercase;letter-spacing:1px;\">Your code</p>";
        $body .= "<p style=\"margin:0;font-size:40px;font-weight:800;color:#1a3a5c;letter-spacing:12px;font-family:monospace;\">{$code_safe}</p></div>";
        $body .= "<p style=\"margin:0;font-size:12px;color:#9ca3af;\">If you did not request this, ignore this email.</p>";
        $body .= "</div></div>";

        $sent = wp_mail( $email, 'Your ' . $site . ' verification code', $body,
                         array( 'Content-Type: text/html; charset=UTF-8' ) );
        if ( ! $sent ) {
            wp_send_json_error( array( 'message' => 'Could not send email. Please try again.' ) );
            return;
        }
        wp_send_json_success( array( 'message' => 'Verification code sent.' ) );
    }

    /**
     * Step 2 — Verify the 6-digit code entered by the user.
     */
    public function wpi_verify_email_code() {
        $email = sanitize_email( $_POST['email'] ?? '' );
        $code  = sanitize_text_field( trim( $_POST['code'] ?? '' ) );
        if ( ! is_email( $email ) || ! $code ) {
            wp_send_json_error( array( 'message' => 'Invalid request.' ) );
            return;
        }
        $attempt_key = 'wpi_vcode_attempts_' . md5( strtolower( $email ) );
        $attempts    = (int) get_transient( $attempt_key );
        if ( $attempts >= 10 ) {
            wp_send_json_error( array( 'message' => 'Too many incorrect attempts. Please request a new code.' ) );
            return;
        }
        $trans_key   = 'wpi_vcode_' . md5( strtolower( $email ) );
        $stored_code = get_transient( $trans_key );
        if ( ! $stored_code ) {
            wp_send_json_error( array( 'message' => 'Code has expired. Please request a new one.' ) );
            return;
        }
        if ( ! hash_equals( $stored_code, $code ) ) {
            set_transient( $attempt_key, $attempts + 1, HOUR_IN_SECONDS );
            $left = max( 0, 10 - $attempts - 1 );
            wp_send_json_error( array(
                'message' => 'Incorrect code.' . ( $left > 0
                    ? ' ' . $left . ' attempt' . ( 1 === $left ? '' : 's' ) . ' remaining.'
                    : ' Please request a new code.' ),
            ) );
            return;
        }
        delete_transient( $trans_key );
        delete_transient( $attempt_key );
        $verified_key = 'wpi_email_verified_' . md5( strtolower( $email ) );
        set_transient( $verified_key, '1', 15 * MINUTE_IN_SECONDS );
        wp_send_json_success( array( 'verified' => true ) );
    }

}
