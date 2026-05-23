<?php
/**
 * WPI Scheduler — send reminder/overdue emails based on per-template schedule settings.
 *
 * Called from real server cron or wp-cron.
 * Hook: wpi_run_scheduler  (registered in wp-inspector.php)
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class WPI_Scheduler {

    /* ── Install / migrate schedule table ─────────────────────── */

    public static function create_table() {
        global $wpdb;
        $t   = $wpdb->prefix . 'wpi_schedules';
        $cs  = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE IF NOT EXISTS $t (
            id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            template_id   BIGINT UNSIGNED NOT NULL,
            enabled       TINYINT(1)      NOT NULL DEFAULT 1,
            frequency     VARCHAR(20)     NOT NULL DEFAULT 'weekly',
            day_of_week   TINYINT         DEFAULT NULL,
            day_of_month  TINYINT         DEFAULT NULL,
            time_of_day   VARCHAR(5)      NOT NULL DEFAULT '08:00',
            recipients    TEXT            DEFAULT NULL,
            subject       VARCHAR(500)    NOT NULL DEFAULT 'Reminder: {template} inspection due',
            body          TEXT            NOT NULL,
            overdue_hours INT             DEFAULT NULL,
            last_sent_at  DATETIME        DEFAULT NULL,
            created_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY template_id (template_id),
            KEY enabled (enabled)
        ) $cs;";
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    /* ── Main cron entry point ─────────────────────────────────── */

    public static function run() {
        global $wpdb;
        $now = current_time( 'mysql' );
        $tz  = wp_timezone();

        $schedules = $wpdb->get_results(
            "SELECT s.*, t.title as template_title, t.settings as t_settings
             FROM {$wpdb->prefix}wpi_schedules s
             LEFT JOIN {$wpdb->prefix}wpi_templates t ON t.id = s.template_id
             WHERE s.enabled = 1"
        );

        foreach ( $schedules as $sched ) {
            try {
                self::process_schedule( $sched, $tz );
            } catch ( Exception $e ) {
                if ( defined('WP_DEBUG_LOG') && WP_DEBUG_LOG ) {
                    error_log( 'WPI Scheduler error schedule#' . $sched->id . ': ' . $e->getMessage() );
                }
            }
        }
    }

    private static function process_schedule( $sched, $tz ) {
        global $wpdb;
        $now  = new DateTime( 'now', $tz );
        $cfg  = is_string( $sched->t_settings ) ? json_decode( $sched->t_settings, true ) : array();
        $cfg  = is_array( $cfg ) ? $cfg : array();

        // ── Determine if a reminder is due ──────────────────────
        $due = false;
        $is_overdue = false;

        // Parse the scheduled time (HH:MM)
        list( $sch_h, $sch_m ) = array_map( 'intval', explode( ':', $sched->time_of_day . ':00' ) );
        $now_h = (int) $now->format('H');
        $now_m = (int) $now->format('i');
        $now_dow = (int) $now->format('N'); // 1=Mon..7=Sun
        $now_dom = (int) $now->format('j');

        // Only fire within the correct 15-minute window
        $in_window = ( $now_h === $sch_h && $now_m >= $sch_m && $now_m < $sch_m + 15 );
        if ( ! $in_window ) return;

        // Check we haven't already sent today/this-cycle
        if ( $sched->last_sent_at ) {
            $last = new DateTime( $sched->last_sent_at, $tz );
            $diff_hours = ( $now->getTimestamp() - $last->getTimestamp() ) / 3600;
        } else {
            $diff_hours = 9999;
        }

        switch ( $sched->frequency ) {
            case 'daily':
                $due = ( $diff_hours >= 20 ); // allow once per day
                break;
            case 'weekly':
                $dow = (int)( $sched->day_of_week ?? 1 ); // 1=Mon
                $due = ( $now_dow === $dow && $diff_hours >= 140 );
                break;
            case 'monthly':
                $dom = (int)( $sched->day_of_month ?? 1 );
                $due = ( $now_dom === $dom && $diff_hours >= 600 );
                break;
            default:
                return;
        }

        if ( ! $due ) return;

        // ── Check overdue: find most recent completed inspection ──
        $last_done = $wpdb->get_var( $wpdb->prepare(
            "SELECT completed_at FROM {$wpdb->prefix}wpi_inspections
             WHERE template_id=%d AND status='completed'
             ORDER BY completed_at DESC LIMIT 1",
            $sched->template_id
        ) );

        $overdue_label = '';
        if ( $sched->overdue_hours && $sched->overdue_hours > 0 ) {
            if ( $last_done ) {
                $last_dt    = new DateTime( $last_done, $tz );
                $hours_since = ( $now->getTimestamp() - $last_dt->getTimestamp() ) / 3600;
                $is_overdue  = ( $hours_since > $sched->overdue_hours );
            } else {
                $is_overdue = true; // never done = overdue
            }
            if ( $is_overdue ) {
                $overdue_label = ' OVERDUE';
            }
        }

        // ── Resolve recipients ───────────────────────────────────
        $recipients = array();
        $raw = $sched->recipients ? json_decode( $sched->recipients, true ) : array();
        if ( is_array( $raw ) ) {
            foreach ( $raw as $email ) {
                if ( is_email( $email ) ) $recipients[] = $email;
            }
        }
        if ( empty( $recipients ) ) return;

        // ── Build token map ──────────────────────────────────────
        $site_name  = '';
        $logo_url   = $cfg['logo_url'] ?? '';
        $header_col = $cfg['header_color'] ?? '#1a3a5c';

        $resolve = function( $text ) use ( $sched, $now, $last_done, $is_overdue, $overdue_label ) {
            $text = str_replace( '{template}',     $sched->template_title ?? 'Inspection', $text );
            $text = str_replace( '{date}',         $now->format('d M Y'),                  $text );
            $text = str_replace( '{time}',         $now->format('g:i A'),                  $text );
            $text = str_replace( '{last_completed}',
                $last_done ? ( new DateTime( $last_done ) )->format('d M Y, g:i A') : 'Never',
                $text
            );
            $text = str_replace( '{overdue}', $overdue_label, $text );
            return $text;
        };

        $subject = $resolve( $sched->subject ) . $overdue_label;
        $body_tpl = $sched->body ?: "Hi,\n\nThis is a reminder that the {template} inspection is due today ({date}).\n\nLast completed: {last_completed}\n\nPlease log in to Audit4me to start the inspection.\n\n" . home_url();

        // ── Send branded HTML email ──────────────────────────────
        foreach ( $recipients as $to ) {
            self::send_email( $to, $subject, $resolve( $body_tpl ), $cfg, $is_overdue );
        }

        // ── Push notifications to recipients ──────────────────────
        // Look up each recipient's WP user ID by email and push to their devices
        require_once WPI_PLUGIN_DIR . 'includes/class-ajax.php';
        $template_name = $sched->template_title ?: 'Inspection';
        $push_title = $is_overdue
            ? '⚠️ Overdue: ' . $template_name
            : '📋 Inspection Due: ' . $template_name;
        $push_body = $is_overdue
            ? $template_name . ' is overdue. Last completed: ' . ( $last_done ? date('d M Y', strtotime($last_done)) : 'Never' ) . '. Please complete it now.'
            : $template_name . ' is due today (' . $now->format('d M Y') . '). Tap to start your inspection.';

        foreach ( $recipients as $to ) {
            $recipient_user = get_user_by( 'email', $to );
            if ( $recipient_user ) {
                WPI_Ajax::send_push( $recipient_user->ID, array(
                    'title' => $push_title,
                    'body'  => $push_body,
                    'url'   => home_url( '/?wpi=1#inspections' ),
                ) );
            }
        }

        // ── Update last_sent_at ──────────────────────────────────
        $wpdb->update(
            $wpdb->prefix . 'wpi_schedules',
            array( 'last_sent_at' => $now->format('Y-m-d H:i:s') ),
            array( 'id' => $sched->id )
        );
    }

    /* ── Overdue action reminder emails (daily cron) ───────────── */

    public static function send_action_overdue_reminders() {
        global $wpdb;
        $now     = current_time('mysql');
        $tz      = wp_timezone();
        $site    = get_bloginfo('name') ?: 'Audit4me';
        $app_url = home_url('/?wpi=1');

        // Find all open/in_progress actions that are past their due date
        if ( class_exists('WPI_Ajax') ) { WPI_Ajax::push_log('ACTION OVERDUE cron started at ' . $now); }
        $actions = $wpdb->get_results(
            "SELECT a.*,
                i.title  AS inspection_title,
                COALESCE(
                    NULLIF(TRIM(CONCAT(COALESCE(um_cb.meta_value,''),' ',COALESCE(um_cb2.meta_value,''))), ''),
                    u_cb.display_name
                ) AS creator_name
             FROM {$wpdb->prefix}wpi_actions a
             LEFT JOIN {$wpdb->prefix}wpi_inspections i ON i.id = a.inspection_id
             LEFT JOIN {$wpdb->prefix}usermeta um_cb  ON um_cb.user_id  = a.created_by AND um_cb.meta_key  = 'first_name'
             LEFT JOIN {$wpdb->prefix}usermeta um_cb2 ON um_cb2.user_id = a.created_by AND um_cb2.meta_key = 'last_name'
             LEFT JOIN {$wpdb->prefix}users u_cb      ON u_cb.ID = a.created_by
             WHERE a.status IN ('open','in_progress')
               AND a.due_date IS NOT NULL
               AND a.due_date != ''
               AND a.due_date != '0000-00-00'
               AND a.due_date < CURDATE()
               AND a.assigned_email != ''
             ORDER BY a.due_date ASC"
        );

        if ( empty($actions) ) {
            if ( class_exists('WPI_Ajax') ) { WPI_Ajax::push_log('ACTION OVERDUE cron found 0 overdue actions'); }
            return;
        }
        if ( class_exists('WPI_Ajax') ) { WPI_Ajax::push_log('ACTION OVERDUE cron found ' . count($actions) . ' overdue action(s)'); }

        $pri_labels = array('low'=>'Low','medium'=>'Medium','high'=>'High','critical'=>'CRITICAL');
        $pri_colors = array('low'=>'#22c55e','medium'=>'#f59e0b','high'=>'#ef4444','critical'=>'#7c2d12');
        $pri_bgs    = array('low'=>'#f0fdf4','medium'=>'#fffbeb','high'=>'#fef2f2','critical'=>'#fff1f0');

        // Group by assignee so each person gets one email listing all their overdue actions
        $by_assignee = array();
        foreach ( $actions as $a ) {
            $email = trim($a->assigned_email);
            if ( ! is_email($email) ) continue;
            $by_assignee[$email][] = $a;
        }

        foreach ( $by_assignee as $email => $items ) {
            $first   = $items[0];
            $name    = $first->assigned_name ?: 'there';
            $fname   = explode(' ', $name)[0] ?: 'there';
            $count   = count($items);
            $subject = 'Overdue Action' . ($count > 1 ? 's' : '') . ': ' . $count . ' item' . ($count > 1 ? 's' : '') . ' require your attention — ' . $site;

            // Build action rows
            $rows_html = '';
            foreach ( $items as $a ) {
                $pri       = $a->priority ?: 'medium';
                $pri_label = $pri_labels[$pri] ?? 'Medium';
                $pri_color = $pri_colors[$pri] ?? '#f59e0b';
                $pri_bg    = $pri_bgs[$pri]    ?? '#fffbeb';
                $due       = $a->due_date ? date('d M Y', strtotime($a->due_date)) : '—';
                $days_over = $a->due_date ? (int)floor((time() - strtotime($a->due_date)) / 86400) : 0;
                $status_label = $a->status === 'in_progress' ? 'In Progress' : 'Open';

                $rows_html .= '
                <table width="100%" cellpadding="0" cellspacing="0" style="border:1.5px solid #ef4444;border-radius:10px;margin-bottom:12px;overflow:hidden;">
                  <tr><td style="padding:14px 16px;background:#fff5f5;">
                    <table width="100%" cellpadding="0" cellspacing="0">
                      <tr>
                        <td><span style="display:inline-block;background:' . esc_attr($pri_bg) . ';color:' . esc_attr($pri_color) . ';border:1.5px solid ' . esc_attr($pri_color) . ';border-radius:20px;padding:2px 10px;font-size:11px;font-weight:700;">' . esc_html(strtoupper($pri_label)) . '</span></td>
                        <td align="right"><span style="font-size:11px;color:#ef4444;font-weight:700;">⚠ ' . $days_over . ' day' . ($days_over !== 1 ? 's' : '') . ' overdue</span></td>
                      </tr>
                    </table>
                    <p style="margin:8px 0 4px;font-size:15px;font-weight:700;color:#111827;">' . esc_html($a->question_label ?: 'Action') . '</p>
                    ' . ($a->question_section ? '<p style="margin:0 0 4px;font-size:11px;color:#6b7280;">Section: ' . esc_html($a->question_section) . '</p>' : '') . '
                    ' . ($a->inspection_title ? '<p style="margin:0 0 6px;font-size:11px;color:#6b7280;">Inspection: ' . esc_html($a->inspection_title) . '</p>' : '') . '
                    ' . ($a->note ? '<p style="margin:0 0 6px;font-size:12px;color:#374151;font-style:italic;">' . esc_html($a->note) . '</p>' : '') . '
                    <table width="100%" cellpadding="0" cellspacing="0">
                      <tr>
                        <td style="font-size:12px;color:#6b7280;">📅 Due: <strong style="color:#ef4444;">' . esc_html($due) . '</strong></td>
                        <td align="right" style="font-size:11px;color:#6b7280;">' . esc_html($status_label) . '</td>
                      </tr>
                    </table>
                  </td></tr>
                </table>';
            }

            $body_html = '
                <p style="margin:0 0 16px;color:#374151;font-size:14px;line-height:1.6;">Hi ' . esc_html($fname) . ',</p>
                <p style="margin:0 0 20px;color:#374151;font-size:14px;line-height:1.6;">You have <strong>' . $count . ' overdue action' . ($count > 1 ? 's' : '') . '</strong> that require' . ($count === 1 ? 's' : '') . ' your attention in <strong>' . esc_html($site) . '</strong>. Please review and update them as soon as possible.</p>
                ' . $rows_html . '
                <p style="margin:16px 0 0;font-size:12px;color:#6b7280;">Log in to Audit4me to update the status of these actions.</p>';

            self::send_branded_email( $email, $subject, $body_html, '#ef4444', 'OVERDUE ACTIONS' );

            // Push notification to assignee for overdue actions
            // Look up user ID by email so we can reach their registered devices
            $assignee_user = get_user_by( 'email', $email );
            if ( $assignee_user ) {
                $push_body = $count > 1
                    ? $count . ' overdue actions need your attention in ' . $site . '. Please review them now.'
                    : 'Overdue: ' . ($items[0]->question_label ?: 'Action') . '. Due ' . ($items[0]->due_date ? date('d M Y', strtotime($items[0]->due_date)) : '') . '.';
                require_once WPI_PLUGIN_DIR . 'includes/class-ajax.php';
                WPI_Ajax::push_log('ACTION OVERDUE push route assignee_uid=' . $assignee_user->ID . ' email=' . $email . ' count=' . $count);
                WPI_Ajax::send_push( $assignee_user->ID, array(
                    'title' => '⚠️ Overdue Action' . ($count > 1 ? 's' : '') . ' — ' . $site,
                    'body'  => $push_body,
                    'url'   => home_url( '/?wpi=1#actions' ),
                    'tag'   => 'wpi-overdue-actions-' . $assignee_user->ID . '-' . date('Ymd'),
                ) );
            }
        }
    }

    /* ── Completion notification to assigner ───────────────────── */

    public static function send_action_completion_notification( $action ) {
        global $wpdb;
        if ( empty($action->created_by) ) return;

        // Get assigner's email
        $creator = get_userdata( (int)$action->created_by );
        if ( ! $creator || ! $creator->user_email ) return;

        $site      = get_bloginfo('name') ?: 'Audit4me';
        $resolver  = get_userdata( get_current_user_id() );
        $res_name  = $resolver ? ($resolver->display_name ?: $resolver->user_login) : 'A team member';

        $pri_labels = array('low'=>'Low','medium'=>'Medium','high'=>'High','critical'=>'CRITICAL');
        $pri_colors = array('low'=>'#22c55e','medium'=>'#f59e0b','high'=>'#ef4444','critical'=>'#7c2d12');
        $pri_bgs    = array('low'=>'#f0fdf4','medium'=>'#fffbeb','high'=>'#fef2f2','critical'=>'#fff1f0');
        $pri        = $action->priority ?: 'medium';
        $pri_label  = $pri_labels[$pri] ?? 'Medium';
        $pri_color  = $pri_colors[$pri] ?? '#f59e0b';
        $pri_bg     = $pri_bgs[$pri]    ?? '#fffbeb';
        $due        = ($action->due_date && $action->due_date !== '0000-00-00')
                      ? date('d M Y', strtotime($action->due_date)) : '—';

        $subject = 'Action Resolved: ' . ($action->question_label ?: 'Action') . ' — ' . $site;

        $body_html = '
            <p style="margin:0 0 16px;color:#374151;font-size:14px;line-height:1.6;">Hi ' . esc_html(explode(' ', $creator->display_name)[0] ?: 'there') . ',</p>
            <p style="margin:0 0 20px;color:#374151;font-size:14px;line-height:1.6;"><strong>' . esc_html($res_name) . '</strong> has resolved an action you assigned in <strong>' . esc_html($site) . '</strong>.</p>

            <table width="100%" cellpadding="0" cellspacing="0" style="background:#f0fdf4;border:1.5px solid #86efac;border-radius:10px;margin-bottom:16px;">
              <tr><td style="padding:16px 18px;">
                <span style="display:inline-block;background:' . esc_attr($pri_bg) . ';color:' . esc_attr($pri_color) . ';border:1.5px solid ' . esc_attr($pri_color) . ';border-radius:20px;padding:2px 10px;font-size:11px;font-weight:700;margin-bottom:10px;">' . esc_html(strtoupper($pri_label)) . '</span>

                <p style="margin:0 0 4px;font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.5px;">Question</p>
                <p style="margin:0 0 10px;font-size:15px;font-weight:700;color:#111827;">' . esc_html($action->question_label ?: '—') . '</p>
                ' . ($action->question_section ? '<p style="margin:0 0 10px;font-size:11px;color:#6b7280;">Section: ' . esc_html($action->question_section) . '</p>' : '') . '
                ' . ($action->question_answer ? '<p style="margin:0 0 4px;font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.5px;">Answer Recorded</p><p style="margin:0 0 10px;font-size:13px;color:#374151;">' . esc_html($action->question_answer) . '</p>' : '') . '

                <table width="100%" cellpadding="0" cellspacing="0" style="margin-top:4px;">
                  <tr>
                    <td style="font-size:12px;color:#6b7280;padding-right:12px;">
                      <strong>Assigned to:</strong> ' . esc_html($action->assigned_name ?: '—') . '
                    </td>
                    <td style="font-size:12px;color:#6b7280;">
                      <strong>Due date:</strong> ' . esc_html($due) . '
                    </td>
                  </tr>
                </table>
              </td></tr>
            </table>

            <table width="100%" cellpadding="0" cellspacing="0" style="background:#f0fdf4;border:1.5px solid #22c55e;border-radius:10px;">
              <tr><td style="padding:14px 18px;">
                <p style="margin:0 0 4px;font-size:11px;font-weight:700;color:#16a34a;text-transform:uppercase;letter-spacing:.5px;">Resolved by ' . esc_html($res_name) . '</p>
                ' . ($action->resolved_note ? '<p style="margin:4px 0 0;font-size:13px;color:#374151;">' . nl2br(esc_html($action->resolved_note)) . '</p>' : '<p style="margin:4px 0 0;font-size:12px;color:#6b7280;font-style:italic;">No resolution note provided.</p>') . '
              </td></tr>
            </table>';

        self::send_branded_email( $creator->user_email, $subject, $body_html, '#16a34a', 'ACTION RESOLVED' );

        // Push notification to the assigner (creator) — action resolved
        require_once WPI_PLUGIN_DIR . 'includes/class-ajax.php';
        WPI_Ajax::send_push( (int)$action->created_by, array(
            'title' => '✅ Action Resolved — ' . $site,
            'body'  => $res_name . ' resolved: ' . ($action->question_label ?: 'an action you assigned') . ($action->resolved_note ? '. Note: ' . mb_substr($action->resolved_note, 0, 80) : ''),
            'url'   => home_url( '/?wpi=1#actions' ),
        ) );
    }

    /* ── Shared branded HTML email wrapper ─────────────────────── */

    public static function send_branded_email( $to, $subject, $body_html, $accent_color = '#1a3a5c', $badge_text = '', $cta_url = '', $cta_label = '' ) {
        $site      = get_bloginfo('name') ?: get_option('blogname') ?: 'Audit4me';
        $site_url  = home_url('/?wpi=1');
        $cta_url   = $cta_url   ?: $site_url;
        $cta_label = $cta_label ?: 'Open Audit4me →';
        $today     = (new DateTime('now', wp_timezone()))->format('d M Y');
        $badge_col = $accent_color;

        $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Helvetica,Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f6;padding:32px 16px;">
<tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 2px 16px rgba(0,0,0,.08);">

  <!-- Header -->
  <tr><td style="background:#1a3a5c;padding:28px 32px;">
    <h1 style="margin:0;font-size:22px;font-weight:800;color:#ffffff;line-height:1.2;">' . esc_html($subject) . '</h1>
    <p style="margin:8px 0 0;font-size:13px;color:rgba(255,255,255,.7);">' . esc_html($site) . ' &nbsp;·&nbsp; ' . esc_html($today) . '</p>
  </td></tr>

  <!-- Badge -->
  ' . ($badge_text ? '<tr><td style="padding:20px 32px 0;"><span style="display:inline-block;background:' . esc_attr($badge_col) . '22;color:' . esc_attr($badge_col) . ';border:1.5px solid ' . esc_attr($badge_col) . ';border-radius:20px;padding:4px 14px;font-size:12px;font-weight:700;">' . esc_html($badge_text) . '</span></td></tr>' : '') . '

  <!-- Body -->
  <tr><td style="padding:20px 32px 28px;">' . $body_html . '</td></tr>

  <!-- CTA -->
  <tr><td style="padding:0 32px 32px;text-align:center;">
    <a href="' . esc_url($cta_url) . '" style="display:inline-block;background:#1a3a5c;color:#ffffff;text-decoration:none;padding:13px 32px;border-radius:8px;font-size:15px;font-weight:700;">' . esc_html($cta_label) . '</a>
  </td></tr>

  <!-- Footer -->
  <tr><td style="background:#f9fafb;padding:16px 32px;border-top:1px solid #e5e7eb;text-align:center;">
    <p style="margin:0;font-size:11px;color:#9ca3af;">Automated notification from <a href="' . esc_url($site_url) . '" style="color:#6b7280;">' . esc_html($site) . '</a>. Do not reply to this email.</p>
  </td></tr>

</table>
</td></tr></table>
</body></html>';

        wp_mail( $to, $subject, $html, array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $site . ' <' . get_option('admin_email') . '>',
        ) );
    }



    public static function send_email( $to, $subject, $body_text, $cfg = array(), $is_overdue = false ) {
        $logo_url   = $cfg['logo_url']   ?? '';
        $header_col = $cfg['header_color'] ?? '#1a3a5c';
        $header_txt = $cfg['header_text_color'] ?? '#ffffff';
        $template   = $cfg['template_title'] ?? get_bloginfo('name');
        $site_url   = home_url();
        $accent     = $is_overdue ? '#dc2626' : $header_col;
        $status_txt = $is_overdue ? 'OVERDUE' : 'REMINDER';
        $status_col = $is_overdue ? '#dc2626' : '#16a34a';

        // Convert plain text body to HTML paragraphs
        $body_html = implode( '', array_map( function( $line ) {
            $line = trim( $line );
            if ( $line === '' ) return '<br>';
            return '<p style="margin:0 0 12px 0;color:#374151;font-size:14px;line-height:1.6;">' . esc_html( $line ) . '</p>';
        }, explode( "\n", $body_text ) ) );

        $logo_html = '';
        if ( $logo_url ) {
            $logo_html = '<img src="' . esc_url( $logo_url ) . '" alt="Logo" style="max-height:48px;max-width:160px;object-fit:contain;display:block;margin-bottom:12px;">';
        }

        $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Helvetica,Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f6;padding:32px 16px;">
<tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 2px 16px rgba(0,0,0,.08);">

  <!-- Header -->
  <tr><td style="background:' . esc_attr($accent) . ';padding:28px 32px;">
    ' . $logo_html . '
    <h1 style="margin:0;font-size:22px;font-weight:800;color:' . esc_attr($header_txt) . ';line-height:1.2;">' . esc_html($subject) . '</h1>
    <p style="margin:8px 0 0;font-size:13px;color:' . esc_attr($header_txt) . ';opacity:.8;">' . esc_html( get_bloginfo('name') ) . ' · ' . esc_html( (new DateTime('now',wp_timezone()))->format('d M Y') ) . '</p>
  </td></tr>

  <!-- Status badge -->
  <tr><td style="padding:20px 32px 0;">
    <span style="display:inline-block;background:' . esc_attr($status_col) . '22;color:' . esc_attr($status_col) . ';border:1.5px solid ' . esc_attr($status_col) . ';border-radius:20px;padding:4px 14px;font-size:12px;font-weight:700;">' . $status_txt . '</span>
  </td></tr>

  <!-- Body -->
  <tr><td style="padding:20px 32px 28px;">
    ' . $body_html . '
  </td></tr>

  <!-- CTA Button -->
  <tr><td style="padding:0 32px 32px;text-align:center;">
    <a href="' . esc_url($site_url) . '" style="display:inline-block;background:' . esc_attr($accent) . ';color:#ffffff;text-decoration:none;padding:13px 28px;border-radius:8px;font-size:14px;font-weight:700;">Open Audit4me →</a>
  </td></tr>

  <!-- Footer -->
  <tr><td style="background:#f9fafb;padding:16px 32px;border-top:1px solid #e5e7eb;text-align:center;">
    <p style="margin:0;font-size:11px;color:#9ca3af;">This is an automated reminder from <a href="' . esc_url($site_url) . '" style="color:#6b7280;">' . esc_html(get_bloginfo('name')) . '</a>. Do not reply to this email.</p>
  </td></tr>

</table>
</td></tr></table>
</body></html>';

        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>',
        );
        wp_mail( $to, $subject, $html, $headers );
    }
}
