<?php
/**
 * WPI_Billing — Stripe subscription management
 * Handles plans, subscriptions, webhooks, enforcement
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class WPI_Billing {

    // ── Plan limits enforcement ──────────────────────────────────────

    /**
     * Get the active plan for an org. Returns plan row or null (free tier).
     */
    public static function get_org_plan( $org_id ) {
        global $wpdb;
        if ( ! $org_id ) return null;
        $sub = $wpdb->get_row( $wpdb->prepare(
            "SELECT s.*, p.name as plan_name, p.limits as plan_limits, p.features as plan_features
             FROM {$wpdb->prefix}wpi_subscriptions s
             LEFT JOIN {$wpdb->prefix}wpi_plans p ON p.id = s.plan_id
             WHERE s.org_id = %d AND s.status IN ('active','trialing','past_due')
             ORDER BY s.id DESC LIMIT 1",
            $org_id
        ) );
        return $sub;
    }

    /**
     * Get plan limits for an org. Returns array of limits or free defaults.
     */
    public static function get_limits( $org_id ) {
        $defaults = array(
            'max_users'        => 1,
            'max_templates'    => 3,
            'max_inspections'  => 10,
            'max_sites'        => 1,
            'api_access'       => false,
            'custom_branding'  => false,
            'scheduler'        => false,
            'priority_support' => false,
        );
        if ( ! $org_id ) return $defaults;
        $sub = self::get_org_plan( $org_id );
        if ( ! $sub || ! $sub->plan_limits ) return $defaults;
        $limits = json_decode( $sub->plan_limits, true );
        return is_array( $limits ) ? array_merge( $defaults, $limits ) : $defaults;
    }

    /**
     * Check if an org can perform an action given their plan limits.
     * Returns true if allowed, false if limit hit.
     */
    public static function can( $org_id, $feature, $current_count = 0 ) {
        $limits = self::get_limits( $org_id );
        switch ( $feature ) {
            case 'create_user':
                $max = $limits['max_users'] ?? 1;
                return $max < 0 || $current_count < $max; // -1 = unlimited
            case 'create_template':
                $max = $limits['max_templates'] ?? 3;
                return $max < 0 || $current_count < $max;
            case 'create_inspection':
                $max = $limits['max_inspections'] ?? 10;
                return $max < 0 || $current_count < $max;
            case 'create_site':
                $max = $limits['max_sites'] ?? 1;
                return $max < 0 || $current_count < $max;
            case 'api_access':
                return ! empty( $limits['api_access'] );
            case 'scheduler':
                return ! empty( $limits['scheduler'] );
            case 'custom_branding':
                return ! empty( $limits['custom_branding'] );
            default:
                return true;
        }
    }

    /**
     * Get subscription status label and style for display.
     */
    public static function get_status_info( $status ) {
        $map = array(
            'active'    => array( 'label' => 'Active',      'color' => '#16a34a', 'bg' => '#f0fdf4' ),
            'trialing'  => array( 'label' => 'Trial',       'color' => '#2563eb', 'bg' => '#eff6ff' ),
            'past_due'  => array( 'label' => 'Past Due',    'color' => '#d97706', 'bg' => '#fffbeb' ),
            'canceled'  => array( 'label' => 'Cancelled',   'color' => '#dc2626', 'bg' => '#fef2f2' ),
            'unpaid'    => array( 'label' => 'Unpaid',      'color' => '#dc2626', 'bg' => '#fef2f2' ),
            'pending'   => array( 'label' => 'Pending Payment', 'color' => '#d97706', 'bg' => '#fffbeb' ),
            'incomplete'=> array( 'label' => 'Payment Incomplete', 'color' => '#d97706', 'bg' => '#fffbeb' ),
            'incomplete_expired' => array( 'label' => 'Payment Expired', 'color' => '#dc2626', 'bg' => '#fef2f2' ),
            'checkout_abandoned' => array( 'label' => 'Free Tier', 'color' => '#6b7280', 'bg' => '#f9fafb' ),
            'free'      => array( 'label' => 'Free Tier',   'color' => '#6b7280', 'bg' => '#f9fafb' ),
            'paused'    => array( 'label' => 'Paused',      'color' => '#6b7280', 'bg' => '#f9fafb' ),
        );
        return $map[ $status ] ?? array( 'label' => ucfirst($status), 'color' => '#6b7280', 'bg' => '#f9fafb' );
    }

    // ── Stripe API helpers ───────────────────────────────────────────

    public static function get_stripe_keys() {
        $cfg = get_option( 'wpi_stripe_settings', array() );
        $live_mode = ! empty( $cfg['live_mode'] );
        return array(
            'secret'      => $live_mode ? ( $cfg['live_secret_key']      ?? '' ) : ( $cfg['test_secret_key']      ?? '' ),
            'publishable' => $live_mode ? ( $cfg['live_publishable_key'] ?? '' ) : ( $cfg['test_publishable_key'] ?? '' ),
            'webhook_secret' => $cfg['webhook_secret'] ?? '',
            'live_mode'   => $live_mode,
        );
    }

    public static function stripe_request( $method, $endpoint, $data = array() ) {
        $keys = self::get_stripe_keys();
        if ( empty( $keys['secret'] ) ) {
            return new WP_Error( 'no_key', 'Stripe secret key not configured.' );
        }
        $args = array(
            'method'  => strtoupper( $method ),
            'headers' => array(
                'Authorization' => 'Bearer ' . $keys['secret'],
                'Content-Type'  => 'application/x-www-form-urlencoded',
            ),
            'timeout' => 30,
        );
        if ( ! empty( $data ) && in_array( $args['method'], array('POST','DELETE'), true ) ) {
            $args['body'] = http_build_query( $data );
        }
        $url      = 'https://api.stripe.com/v1/' . ltrim( $endpoint, '/' );
        if ( ! empty( $data ) && $args['method'] === 'GET' ) {
            $url .= '?' . http_build_query( $data );
        }
        $response = wp_remote_request( $url, $args );
        if ( is_wp_error( $response ) ) return $response;
        $body   = json_decode( wp_remote_retrieve_body( $response ), true );
        $code   = wp_remote_retrieve_response_code( $response );
        if ( $code >= 400 ) {
            $msg = $body['error']['message'] ?? 'Stripe error ' . $code;
            return new WP_Error( 'stripe_error', $msg, array( 'status' => $code, 'body' => $body ) );
        }
        return $body;
    }

    // ── Webhook handler ──────────────────────────────────────────────

    /**
     * Grant full access to all members of an org.
     * Called when a subscription becomes active or is renewed.
     */
    public static function unlock_org_team( $org_id ) {
        if ( ! $org_id ) return;
        global $wpdb;

        // Get all users in this org. The organisation owner of an individually
        // purchased account receives full administrator-level access to their own
        // personal organisation. Other invited members keep their assigned role and
        // only receive template access when the inviter shares templates with them.
        $org = $wpdb->get_row( $wpdb->prepare(
            "SELECT owner_id FROM {$wpdb->prefix}wpi_organisations WHERE id = %d", $org_id
        ) );
        $owner_id = $org && ! empty( $org->owner_id ) ? (int) $org->owner_id : 0;

        $user_ids = $wpdb->get_col( $wpdb->prepare(
            "SELECT user_id FROM {$wpdb->prefix}wpi_org_users WHERE org_id = %d", $org_id
        ) );

        foreach ( $user_ids as $uid ) {
            $uid = (int) $uid;
            delete_user_meta( $uid, 'wpi_access_basic' );

            if ( $owner_id && $uid === $owner_id ) {
                // Full access for the individual purchaser/organisation owner.
                $wpdb->update( $wpdb->prefix . 'wpi_org_users', array( 'role' => 'admin' ), array( 'org_id' => (int) $org_id, 'user_id' => $uid ) );
                $wpdb->replace( $wpdb->prefix . 'wpi_user_roles', array(
                    'user_id' => $uid,
                    'role'    => 'administrator',
                    'set_by'  => 0,
                ) );
                continue;
            }

            // Invited users inherit the organisation subscription, but they do not
            // automatically become administrators or receive every template. The
            // inviter controls template access through template sharing.
            $role = $wpdb->get_var( $wpdb->prepare(
                "SELECT role FROM {$wpdb->prefix}wpi_user_roles WHERE user_id = %d", $uid
            ) );
            if ( ! $role || $role === 'basic' ) {
                $wpdb->query( $wpdb->prepare(
                    "INSERT INTO {$wpdb->prefix}wpi_user_roles (user_id, role, set_by)
                     VALUES (%d, 'standard', 0)
                     ON DUPLICATE KEY UPDATE role = IF(role='basic','standard',role)",
                    $uid
                ) );
            }
        }
    }

    /**
     * Lock all non-admin org members when subscription expires/cancels.
     * Admins keep access so they can re-subscribe.
     */
    public static function lock_org_team( $org_id ) {
        if ( ! $org_id ) return;
        global $wpdb;

        $members = $wpdb->get_results( $wpdb->prepare(
            "SELECT ou.user_id, ur.role
             FROM {$wpdb->prefix}wpi_org_users ou
             LEFT JOIN {$wpdb->prefix}wpi_user_roles ur ON ur.user_id = ou.user_id
             WHERE ou.org_id = %d", $org_id
        ) );

        foreach ( $members as $m ) {
            // Keep administrators so they can re-subscribe
            if ( in_array( $m->role, array('administrator','super_manager'), true ) ) continue;
            update_user_meta( (int)$m->user_id, 'wpi_access_basic', 1 );
        }
    }

    /**
     * Keep the legacy organisation licence fields aligned with Stripe billing.
     * The frontend licence banner reads these fields on first page load, so an
     * active Stripe subscription must switch the organisation out of Trial mode.
     */
    public static function sync_org_licence_from_subscription( $org_id, $status = '', $billing_cycle = '', $period_end = null ) {
        if ( ! $org_id ) return;
        if ( ! in_array( $status, array( 'active', 'trialing', 'past_due' ), true ) ) return;
        global $wpdb;

        $licence_type = $billing_cycle === 'annual' ? 'annual' : ( $billing_cycle === 'monthly' ? 'monthly' : 'monthly' );
        $data = array(
            'licence_type'  => $licence_type,
            'licence_start' => current_time( 'Y-m-d' ),
            'status'        => 'active',
        );
        if ( $period_end ) {
            if ( is_numeric( $period_end ) ) {
                $data['licence_end'] = date( 'Y-m-d', (int) $period_end );
            } else {
                $data['licence_end'] = date( 'Y-m-d', strtotime( $period_end ) );
            }
        } else {
            $data['licence_end'] = null;
        }
        $wpdb->update( $wpdb->prefix . 'wpi_organisations', $data, array( 'id' => (int) $org_id ) );
    }

    public static function handle_webhook() {
        $keys    = self::get_stripe_keys();
        $payload = @file_get_contents( 'php://input' );
        $sig     = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

        // Webhook signature is REQUIRED — reject all events if secret not configured.
        // Without this, anyone can POST fake Stripe events to manipulate subscriptions.
        if ( empty( $keys['webhook_secret'] ) ) {
            status_header( 400 );
            echo json_encode( array( 'error' => 'Webhook secret not configured. Set it in Settings > Stripe.' ) );
            exit;
        }
        $valid = self::verify_webhook_signature( $payload, $sig, $keys['webhook_secret'] );
        if ( ! $valid ) {
            status_header( 400 );
            echo json_encode( array( 'error' => 'Invalid signature' ) );
            exit;
        }

        $event = json_decode( $payload, true );
        if ( ! $event || empty( $event['type'] ) ) {
            status_header( 400 );
            exit;
        }

        self::process_webhook_event( $event );
        status_header( 200 );
        echo json_encode( array( 'received' => true ) );
        exit;
    }

    private static function verify_webhook_signature( $payload, $sig_header, $secret ) {
        $parts = explode( ',', $sig_header );
        $ts    = null;
        $sigs  = array();
        foreach ( $parts as $part ) {
            if ( strpos( $part, 't=' ) === 0 )  $ts   = substr( $part, 2 );
            if ( strpos( $part, 'v1=' ) === 0 ) $sigs[] = substr( $part, 3 );
        }
        if ( ! $ts || empty( $sigs ) ) return false;
        // Reject events older than 5 minutes
        if ( abs( time() - (int)$ts ) > 300 ) return false;
        $signed_payload = $ts . '.' . $payload;
        $expected       = hash_hmac( 'sha256', $signed_payload, $secret );
        foreach ( $sigs as $sig ) {
            if ( hash_equals( $expected, $sig ) ) return true;
        }
        return false;
    }



    /**
     * Stripe API 2026-04-22.dahlia stores the billing period end on
     * subscription items, not always on the subscription root object.
     * Keep the older root fallback for accounts using older Stripe API versions.
     */
    private static function get_subscription_period_end( $subscription ) {
        if ( ! is_array( $subscription ) ) return null;
        if ( ! empty( $subscription['current_period_end'] ) ) {
            return $subscription['current_period_end'];
        }
        if ( ! empty( $subscription['items']['data'][0]['current_period_end'] ) ) {
            return $subscription['items']['data'][0]['current_period_end'];
        }
        if ( ! empty( $subscription['pending_update']['subscription_items'][0]['current_period_end'] ) ) {
            return $subscription['pending_update']['subscription_items'][0]['current_period_end'];
        }
        return null;
    }

    public static function process_webhook_event( $event ) {
        global $wpdb;
        $type   = $event['type'];
        $object = $event['data']['object'] ?? array();

        // Self-heal older installs: add billing_cycle if this update has not migrated yet.
        $sub_cols = $wpdb->get_col( "SHOW COLUMNS FROM {$wpdb->prefix}wpi_subscriptions", 0 );
        if ( $sub_cols && ! in_array( 'billing_cycle', $sub_cols ) ) {
            $wpdb->query( "ALTER TABLE {$wpdb->prefix}wpi_subscriptions ADD COLUMN billing_cycle VARCHAR(20) NOT NULL DEFAULT '' AFTER current_period_end" );
        }

        switch ( $type ) {
            case 'customer.subscription.created':
            case 'customer.subscription.updated':
                $stripe_sub_id = $object['id'] ?? '';
                $customer_id   = $object['customer'] ?? '';
                $status        = $object['status'] ?? 'active';
                $plan_stripe_id = $object['items']['data'][0]['price']['id'] ?? '';
                $current_period_end = self::get_subscription_period_end( $object );
                $cancel_at_end  = ! empty( $object['cancel_at_period_end'] );
                $meta_org_id    = absint( $object['metadata']['org_id'] ?? 0 );
                $meta_plan_id   = absint( $object['metadata']['plan_id'] ?? 0 );
                $meta_cycle     = sanitize_text_field( $object['metadata']['cycle'] ?? '' );

                // Find org by Stripe customer ID
                $sub = $wpdb->get_row( $wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}wpi_subscriptions WHERE stripe_customer_id = %s LIMIT 1",
                    $customer_id
                ) );
                if ( ! $sub && $meta_org_id ) {
                    $sub = $wpdb->get_row( $wpdb->prepare(
                        "SELECT * FROM {$wpdb->prefix}wpi_subscriptions WHERE org_id = %d ORDER BY id DESC LIMIT 1",
                        $meta_org_id
                    ) );
                }

                // Find plan by Stripe price ID (monthly or annual)
                $plan = $wpdb->get_row( $wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}wpi_plans
                     WHERE stripe_monthly_price_id = %s OR stripe_annual_price_id = %s",
                    $plan_stripe_id, $plan_stripe_id
                ) );

                if ( ! $plan && $meta_plan_id ) {
                    $plan = $wpdb->get_row( $wpdb->prepare(
                        "SELECT * FROM {$wpdb->prefix}wpi_plans WHERE id = %d LIMIT 1",
                        $meta_plan_id
                    ) );
                }

                $billing_cycle = $meta_cycle ?: '';
                if ( $plan ) {
                    if ( (string) $plan->stripe_annual_price_id === (string) $plan_stripe_id ) $billing_cycle = 'annual';
                    if ( (string) $plan->stripe_monthly_price_id === (string) $plan_stripe_id ) $billing_cycle = 'monthly';
                }
                if ( ! $billing_cycle ) {
                    $interval = $object['items']['data'][0]['price']['recurring']['interval'] ?? '';
                    if ( $interval === 'year' ) $billing_cycle = 'annual';
                    if ( $interval === 'month' ) $billing_cycle = 'monthly';
                }

                $data = array(
                    'stripe_subscription_id' => $stripe_sub_id,
                    'stripe_customer_id'     => $customer_id,
                    'status'                 => $status,
                    'plan_id'                => $plan ? $plan->id : null,
                    'current_period_end'     => $current_period_end ? date( 'Y-m-d H:i:s', $current_period_end ) : null,
                    'billing_cycle'          => $billing_cycle,
                    'cancel_at_period_end'   => $cancel_at_end ? 1 : 0,
                    'updated_at'             => current_time('mysql'),
                );
                if ( $meta_org_id ) {
                    $data['org_id'] = $meta_org_id;
                }

                if ( $sub ) {
                    $wpdb->update( $wpdb->prefix . 'wpi_subscriptions', $data, array('id' => $sub->id) );
                    $affected_org_id = (int) ( $data['org_id'] ?? $sub->org_id ?? 0 );
                } else {
                    $data['created_at'] = current_time('mysql');
                    $wpdb->insert( $wpdb->prefix . 'wpi_subscriptions', $data );
                    $affected_org_id = (int) ( $data['org_id'] ?? 0 );
                }
                if ( $affected_org_id ) {
                    if ( in_array( $status, array( 'active', 'trialing', 'past_due' ), true ) ) { self::unlock_org_team( $affected_org_id ); self::sync_org_licence_from_subscription( $affected_org_id, $status, $billing_cycle, $current_period_end ); }
                    if ( in_array( $status, array( 'canceled', 'unpaid', 'incomplete_expired' ), true ) ) self::lock_org_team( $affected_org_id );
                }
                break;

            case 'customer.subscription.deleted':
                $stripe_sub_id = $object['id'] ?? '';

                // Check if this is a seat subscription
                $seat_del = $wpdb->get_row( $wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}wpi_org_seats WHERE stripe_subscription_id=%s LIMIT 1",
                    $stripe_sub_id
                ) );
                if ( $seat_del ) {
                    $wpdb->update( $wpdb->prefix . 'wpi_org_seats',
                        array( 'status' => 'canceled', 'updated_at' => current_time('mysql') ),
                        array( 'id' => $seat_del->id )
                    );
                    $assigned = $wpdb->get_col( $wpdb->prepare(
                        "SELECT user_id FROM {$wpdb->prefix}wpi_seat_assignments WHERE seat_id=%d AND status='assigned'",
                        $seat_del->id
                    ) );
                    foreach ( $assigned as $auid ) {
                        if ( $auid ) update_user_meta( (int)$auid, 'wpi_access_basic', 1 );
                    }
                    break;
                }

                // Regular org subscription deleted
                $del_sub = $wpdb->get_row( $wpdb->prepare(
                    "SELECT org_id FROM {$wpdb->prefix}wpi_subscriptions WHERE stripe_subscription_id = %s LIMIT 1",
                    $stripe_sub_id
                ) );
                $wpdb->update( $wpdb->prefix . 'wpi_subscriptions',
                    array( 'status' => 'canceled', 'updated_at' => current_time('mysql') ),
                    array( 'stripe_subscription_id' => $stripe_sub_id )
                );
                if ( $del_sub && $del_sub->org_id ) {
                    self::lock_org_team( (int)$del_sub->org_id );
                }
                break;

            case 'invoice.payment_succeeded':
                $customer_id    = $object['customer'] ?? '';
                $invoice_sub_id = $object['subscription'] ?? '';

                // Check if this is a seat subscription renewal
                if ( $invoice_sub_id ) {
                    $seat_row = $wpdb->get_row( $wpdb->prepare(
                        "SELECT * FROM {$wpdb->prefix}wpi_org_seats WHERE stripe_subscription_id=%s LIMIT 1",
                        $invoice_sub_id
                    ) );
                    if ( $seat_row ) {
                        // Apply pending seat reduction if one exists
                        $update_data = array(
                            'status'       => 'active',
                            'renewal_date' => date('Y-m-d H:i:s', strtotime('+1 month')),
                            'updated_at'   => current_time('mysql'),
                        );
                        if ( $seat_row->pending_seats_total !== null ) {
                            $update_data['seats_total']         = (int) $seat_row->pending_seats_total;
                            $update_data['pending_seats_total'] = null;
                        }
                        $wpdb->update( $wpdb->prefix . 'wpi_org_seats', $update_data, array( 'id' => $seat_row->id ) );
                        break; // Seat renewal — skip org subscription update
                    }
                }

                $wpdb->update( $wpdb->prefix . 'wpi_subscriptions',
                    array( 'status' => 'active', 'updated_at' => current_time('mysql') ),
                    array( 'stripe_customer_id' => $customer_id )
                );
                $sub_row = $wpdb->get_row( $wpdb->prepare(
                    "SELECT org_id FROM {$wpdb->prefix}wpi_subscriptions WHERE stripe_customer_id = %s LIMIT 1",
                    $customer_id
                ) );
                if ( $sub_row && $sub_row->org_id ) {
                    self::unlock_org_team( (int)$sub_row->org_id );
                    self::sync_org_subscription_from_stripe( (int)$sub_row->org_id );
                }
                break;

            case 'invoice.payment_failed':
                $customer_id    = $object['customer'] ?? '';
                $invoice_sub_id = $object['subscription'] ?? '';
                $attempt        = (int)( $object['attempt_count'] ?? 1 );

                // Check if this is a seat subscription failure
                if ( $invoice_sub_id ) {
                    $seat_row = $wpdb->get_row( $wpdb->prepare(
                        "SELECT * FROM {$wpdb->prefix}wpi_org_seats WHERE stripe_subscription_id=%s LIMIT 1",
                        $invoice_sub_id
                    ) );
                    if ( $seat_row && $attempt >= 3 ) {
                        $wpdb->update( $wpdb->prefix . 'wpi_org_seats',
                            array( 'status' => 'suspended', 'updated_at' => current_time('mysql') ),
                            array( 'id' => $seat_row->id )
                        );
                        $assigned = $wpdb->get_col( $wpdb->prepare(
                            "SELECT user_id FROM {$wpdb->prefix}wpi_seat_assignments WHERE seat_id=%d AND status='assigned'",
                            $seat_row->id
                        ) );
                        foreach ( $assigned as $auid ) {
                            if ( $auid ) update_user_meta( (int)$auid, 'wpi_access_basic', 1 );
                        }
                        break;
                    }
                }

                $new_status = $attempt >= 3 ? 'unpaid' : 'past_due';
                $wpdb->update( $wpdb->prefix . 'wpi_subscriptions',
                    array( 'status' => $new_status, 'updated_at' => current_time('mysql') ),
                    array( 'stripe_customer_id' => $customer_id )
                );
                // Lock team if hard failed (3+ attempts)
                if ( $attempt >= 3 ) {
                    $sub_row = $wpdb->get_row( $wpdb->prepare(
                        "SELECT org_id FROM {$wpdb->prefix}wpi_subscriptions WHERE stripe_customer_id = %s LIMIT 1",
                        $customer_id
                    ) );
                    if ( $sub_row && $sub_row->org_id ) {
                        self::lock_org_team( (int)$sub_row->org_id );
                    }
                }
                break;

            case 'checkout.session.completed':
                $customer_id   = $object['customer'] ?? '';
                $sub_id        = $object['subscription'] ?? '';
                $meta          = $object['metadata'] ?? array();
                $org_id        = $meta['org_id'] ?? null;
                $billing_cycle = sanitize_text_field( $meta['cycle'] ?? '' );
                $plan_id       = absint( $meta['plan_id'] ?? 0 );
                $is_trial      = ! empty( $meta['is_trial'] );
                $trial_days    = (int)( $meta['trial_days'] ?? 7 );
                $type          = $meta['type'] ?? '';

                // Handle seat subscription (recurring monthly)
                if ( $type === 'seat_subscription' ) {
                    $seat_qty      = (int)( $meta['seat_qty']     ?? 0 );
                    $purchased_by  = (int)( $meta['purchased_by'] ?? 0 );
                    $amount        = (int)( $object['amount_total'] ?? 0 );
                    $price_each    = $seat_qty > 0 ? intdiv($amount, $seat_qty) : 0;
                    $stripe_sub_id = $sub_id;

                    if ( $org_id && $seat_qty > 0 ) {
                        // Self-heal: add missing columns on older installs
                        $sc = $wpdb->get_col( "SHOW COLUMNS FROM `{$wpdb->prefix}wpi_org_seats`", 0 );
                        if ( $sc ) {
                            if ( ! in_array('stripe_subscription_id',$sc) ) $wpdb->query("ALTER TABLE `{$wpdb->prefix}wpi_org_seats` ADD COLUMN `stripe_subscription_id` VARCHAR(100) NOT NULL DEFAULT ''");
                            if ( ! in_array('billing_cycle',$sc) )          $wpdb->query("ALTER TABLE `{$wpdb->prefix}wpi_org_seats` ADD COLUMN `billing_cycle` VARCHAR(20) NOT NULL DEFAULT 'monthly'");
                            if ( ! in_array('renewal_date',$sc) )           $wpdb->query("ALTER TABLE `{$wpdb->prefix}wpi_org_seats` ADD COLUMN `renewal_date` DATETIME DEFAULT NULL");
                        }
                        $exists = $wpdb->get_var( $wpdb->prepare(
                            "SELECT id FROM {$wpdb->prefix}wpi_org_seats WHERE stripe_subscription_id=%s LIMIT 1",
                            $stripe_sub_id
                        ) );
                        if ( ! $exists ) {
                            $wpdb->insert( $wpdb->prefix . 'wpi_org_seats', array(
                                'org_id'                 => (int)$org_id,
                                'purchased_by'           => $purchased_by,
                                'seats_total'            => $seat_qty,
                                'seats_used'             => 0,
                                'price_per_seat_cents'   => $price_each,
                                'currency'               => strtoupper($object['currency'] ?? 'AUD'),
                                'stripe_subscription_id' => $stripe_sub_id,
                                'stripe_customer_id'     => $customer_id,
                                'status'                 => 'active',
                                'billing_cycle'          => 'monthly',
                                'renewal_date'           => date('Y-m-d H:i:s', strtotime('+1 month')),
                                'created_at'             => current_time('mysql'),
                                'updated_at'             => current_time('mysql'),
                            ) );
                        }
                    }
                    break;
                }

                if ( $org_id ) {
                    $trial_ends_at = null;
                    $status        = 'pending';

                    // One-time trial payment — set trialing status with expiry
                    if ( $is_trial ) {
                        $trial_ends_at = date( 'Y-m-d H:i:s', strtotime( '+' . $trial_days . ' days' ) );
                        $status        = 'trialing';
                    }

                    $exists = $wpdb->get_var( $wpdb->prepare(
                        "SELECT id FROM {$wpdb->prefix}wpi_subscriptions WHERE org_id = %d LIMIT 1", $org_id
                    ) );

                    $data = array(
                        'stripe_customer_id'     => $customer_id,
                        'stripe_subscription_id' => $sub_id,
                        'plan_id'                => $plan_id ?: null,
                        'status'                 => $status,
                        'billing_cycle'          => $billing_cycle,
                        'is_trial'               => $is_trial ? 1 : 0,
                        'trial_ends_at'          => $trial_ends_at,
                        'updated_at'             => current_time('mysql'),
                    );

                    if ( $exists ) {
                        $wpdb->update( $wpdb->prefix . 'wpi_subscriptions', $data, array( 'org_id' => (int)$org_id ) );
                    } else {
                        $data['org_id']     = (int)$org_id;
                        $data['created_at'] = current_time('mysql');
                        $wpdb->insert( $wpdb->prefix . 'wpi_subscriptions', $data );
                    }

                    if ( $is_trial ) {
                        // Unlock team immediately for trial — no waiting for subscription webhook
                        self::unlock_org_team( (int)$org_id );
                    } else {
                        // Regular subscription — sync from Stripe
                        self::sync_org_subscription_from_stripe( (int)$org_id );
                    }
                }
                break;
        }
    }

    /**
     * Best-effort sync for an organisation subscription from Stripe.
     * This fixes pending/period/cycle drift when webhooks arrive late or were missed.
     */
    public static function sync_org_subscription_from_stripe( $org_id ) {
        global $wpdb;
        $org_id = (int) $org_id;
        if ( ! $org_id ) return false;

        $sub = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wpi_subscriptions WHERE org_id=%d ORDER BY id DESC LIMIT 1",
            $org_id
        ) );
        if ( ! $sub || empty( $sub->stripe_subscription_id ) ) return false;

        $remote = self::stripe_request( 'GET', 'subscriptions/' . rawurlencode( $sub->stripe_subscription_id ) );
        if ( is_wp_error( $remote ) || empty( $remote['id'] ) ) return false;

        $price_id = $remote['items']['data'][0]['price']['id'] ?? '';
        $plan = null;
        if ( $price_id ) {
            $plan = $wpdb->get_row( $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}wpi_plans WHERE stripe_monthly_price_id=%s OR stripe_annual_price_id=%s LIMIT 1",
                $price_id, $price_id
            ) );
        }

        $billing_cycle = $sub->billing_cycle ?: '';
        if ( $plan && $price_id ) {
            if ( (string) $plan->stripe_annual_price_id === (string) $price_id ) $billing_cycle = 'annual';
            if ( (string) $plan->stripe_monthly_price_id === (string) $price_id ) $billing_cycle = 'monthly';
        }
        if ( ! $billing_cycle ) {
            $interval = $remote['items']['data'][0]['price']['recurring']['interval'] ?? '';
            if ( $interval === 'year' ) $billing_cycle = 'annual';
            if ( $interval === 'month' ) $billing_cycle = 'monthly';
        }

        $data = array(
            'status'               => sanitize_text_field( $remote['status'] ?? $sub->status ),
            'stripe_customer_id'   => sanitize_text_field( $remote['customer'] ?? $sub->stripe_customer_id ),
            'current_period_end'   => self::get_subscription_period_end( $remote ) ? date( 'Y-m-d H:i:s', (int) self::get_subscription_period_end( $remote ) ) : $sub->current_period_end,
            'billing_cycle'        => sanitize_text_field( $billing_cycle ),
            'cancel_at_period_end' => ! empty( $remote['cancel_at_period_end'] ) ? 1 : 0,
            'updated_at'           => current_time( 'mysql' ),
        );
        if ( $plan ) $data['plan_id'] = (int) $plan->id;

        $wpdb->update( $wpdb->prefix . 'wpi_subscriptions', $data, array( 'id' => (int) $sub->id ) );

        if ( in_array( $data['status'], array( 'active', 'trialing', 'past_due' ), true ) ) {
            self::unlock_org_team( $org_id );
            self::sync_org_licence_from_subscription( $org_id, $data['status'], $billing_cycle, $data['current_period_end'] );
        } elseif ( in_array( $data['status'], array( 'canceled', 'unpaid', 'incomplete_expired' ), true ) ) {
            self::lock_org_team( $org_id );
        }
        return true;
    }

}
