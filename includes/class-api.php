<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WPI_API {

    const NAMESPACE = 'wp_inspector/v1';

    public function register_routes() {
        add_action( 'rest_api_init', array( $this, 'init_routes' ) );
    }

    public function init_routes() {

        // Login endpoint - Loginizer does not intercept REST API routes
        register_rest_route( self::NAMESPACE, '/auth', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'wpi_rest_login' ),
            'permission_callback' => '__return_true',
        ) );

        // ── Templates ──────────────────────────────────────────────
        register_rest_route( self::NAMESPACE, '/templates', array(
            array( 'methods' => 'GET',  'callback' => array( $this, 'get_templates'    ), 'permission_callback' => array( $this, 'is_logged_in' ) ),
            array( 'methods' => 'POST', 'callback' => array( $this, 'create_template'  ), 'permission_callback' => array( $this, 'is_logged_in' ) ),
        ) );
        register_rest_route( self::NAMESPACE, '/templates/(?P<id>\d+)', array(
            array( 'methods' => 'GET',    'callback' => array( $this, 'get_template'    ), 'permission_callback' => array( $this, 'is_logged_in' ) ),
            array( 'methods' => 'PUT',    'callback' => array( $this, 'update_template' ), 'permission_callback' => array( $this, 'is_logged_in' ) ),
            array( 'methods' => 'DELETE', 'callback' => array( $this, 'delete_template' ), 'permission_callback' => array( $this, 'is_logged_in'  ) ),
        ) );

        // ── Questions ──────────────────────────────────────────────
        register_rest_route( self::NAMESPACE, '/templates/(?P<tid>\d+)/questions', array(
            array( 'methods' => 'GET',  'callback' => array( $this, 'get_questions'  ), 'permission_callback' => array( $this, 'is_logged_in' ) ),
            array( 'methods' => 'POST', 'callback' => array( $this, 'save_questions' ), 'permission_callback' => array( $this, 'is_logged_in' ) ),
        ) );

        // ── Inspections ────────────────────────────────────────────
        register_rest_route( self::NAMESPACE, '/inspections', array(
            array( 'methods' => 'GET',  'callback' => array( $this, 'get_inspections'   ), 'permission_callback' => array( $this, 'is_logged_in' ) ),
            array( 'methods' => 'POST', 'callback' => array( $this, 'create_inspection' ), 'permission_callback' => array( $this, 'is_logged_in' ) ),
        ) );
        register_rest_route( self::NAMESPACE, '/inspections/(?P<id>\d+)', array(
            array( 'methods' => 'GET', 'callback' => array( $this, 'get_inspection'    ), 'permission_callback' => array( $this, 'is_logged_in' ) ),
            array( 'methods' => 'PUT', 'callback' => array( $this, 'update_inspection' ), 'permission_callback' => array( $this, 'is_logged_in' ) ),
        ) );

        // ── PDF Report ─────────────────────────────────────────────
        register_rest_route( self::NAMESPACE, '/inspections/(?P<id>\d+)/pdf', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'download_pdf' ),
            'permission_callback' => array( $this, 'pdf_permission' ),
        ) );

        // ── Dashboard stats ────────────────────────────────────────
        register_rest_route( self::NAMESPACE, '/dashboard', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_dashboard' ),
            'permission_callback' => array( $this, 'is_logged_in' ),
        ) );
    }

    // ── Permission helpers ─────────────────────────────────────────

    public function is_logged_in() {
        return is_user_logged_in();
    }

    public function can_manage() {
        return current_user_can( 'manage_options' );
    }

    /**
     * Return the org_id to scope queries:
     * – API-key requests use the key's org_id
     * – Cookie-auth requests look up the user's org from wpi_org_users
     */
    private function get_api_org_id() {
        global $_wpi_api_key_org_id, $wpdb;
        if ( ! empty( $_wpi_api_key_org_id ) ) return (int) $_wpi_api_key_org_id;
        $uid = get_current_user_id();
        if ( ! $uid ) return 0;
        return (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT org_id FROM {$wpdb->prefix}wpi_org_users WHERE user_id = %d ORDER BY id ASC LIMIT 1",
            $uid
        ) );
    }

    /**
     * PDF permission: accepts nonce via ?_wpnonce= query param
     * so browser <a href> direct links work without AJAX headers.
     */
    public function pdf_permission( $request ) {
        // 1. Already authenticated via cookie + nonce query param (standard WP REST behaviour)
        if ( is_user_logged_in() ) {
            return true;
        }
        // 2. Try verifying nonce passed as query param manually
        $nonce = $request->get_param( '_wpnonce' );
        if ( $nonce && wp_verify_nonce( $nonce, 'wp_rest' ) ) {
            return true;
        }
        return new WP_Error( 'rest_forbidden', 'Please log in to download reports.', array( 'status' => 401 ) );
    }

    // ── Templates ─────────────────────────────────────────────────

    public function get_templates( $request ) {
        global $wpdb;
        $user_id = get_current_user_id();
        $status  = sanitize_text_field( $request->get_param( 'status' ) ?: 'active' );
        $org_id  = $this->get_api_org_id();
        $org_sql = $org_id ? $wpdb->prepare( ' AND t.org_id = %d', $org_id ) : '';

        $rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT t.*, u.display_name as author_name,
                    (SELECT COUNT(*) FROM {$wpdb->prefix}wpi_questions q WHERE q.template_id = t.id) as question_count,
                    (SELECT COUNT(*) FROM {$wpdb->prefix}wpi_inspections i WHERE i.template_id = t.id) as inspection_count
             FROM {$wpdb->prefix}wpi_templates t
             LEFT JOIN {$wpdb->users} u ON u.ID = t.created_by
             WHERE t.status = %s{$org_sql}
             ORDER BY t.created_at DESC",
            $status
        ) );

        return rest_ensure_response( $rows );
    }

    public function get_template( $request ) {
        global $wpdb;
        $id = absint( $request['id'] );
        $row = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wpi_templates WHERE id = %d", $id
        ) );
        if ( ! $row ) return new WP_Error( 'not_found', 'Template not found', array( 'status' => 404 ) );
        $api_org = $this->get_api_org_id();
        if ( $api_org && (int) $row->org_id !== $api_org ) {
            return new WP_Error( 'rest_forbidden', 'Access denied.', array( 'status' => 403 ) );
        }
        return rest_ensure_response( $row );
    }

    public function create_template( $request ) {
        global $wpdb;
        $data = array(
            'title'       => sanitize_text_field( $request->get_param( 'title' ) ),
            'description' => sanitize_textarea_field( $request->get_param( 'description' ) ),
            'created_by'  => get_current_user_id(),
            'status'      => 'active',
        );
        if ( empty( $data['title'] ) ) return new WP_Error( 'missing_title', 'Title is required', array( 'status' => 400 ) );
        $wpdb->insert( $wpdb->prefix . 'wpi_templates', $data );
        $data['id'] = $wpdb->insert_id;
        return rest_ensure_response( $data );
    }

    public function update_template( $request ) {
        global $wpdb;
        $id      = absint( $request['id'] );
        $existing = $wpdb->get_row( $wpdb->prepare(
            "SELECT org_id FROM {$wpdb->prefix}wpi_templates WHERE id = %d", $id
        ) );
        if ( ! $existing ) return new WP_Error( 'not_found', 'Template not found', array( 'status' => 404 ) );
        $api_org = $this->get_api_org_id();
        if ( $api_org && (int) $existing->org_id !== $api_org ) {
            return new WP_Error( 'rest_forbidden', 'Access denied.', array( 'status' => 403 ) );
        }
        $fields = array();
        if ( $request->get_param( 'title' ) )       $fields['title']       = sanitize_text_field( $request->get_param( 'title' ) );
        if ( $request->get_param( 'description' ) !== null ) $fields['description'] = sanitize_textarea_field( $request->get_param( 'description' ) );
        if ( $request->get_param( 'status' ) )       $fields['status']      = sanitize_text_field( $request->get_param( 'status' ) );
        if ( empty( $fields ) ) return new WP_Error( 'no_data', 'Nothing to update', array( 'status' => 400 ) );
        $wpdb->update( $wpdb->prefix . 'wpi_templates', $fields, array( 'id' => $id ) );
        return rest_ensure_response( array( 'success' => true ) );
    }

    public function delete_template( $request ) {
        global $wpdb;
        $id      = absint( $request['id'] );
        $existing = $wpdb->get_row( $wpdb->prepare(
            "SELECT org_id FROM {$wpdb->prefix}wpi_templates WHERE id = %d", $id
        ) );
        if ( ! $existing ) return new WP_Error( 'not_found', 'Template not found', array( 'status' => 404 ) );
        $api_org = $this->get_api_org_id();
        if ( $api_org && (int) $existing->org_id !== $api_org ) {
            return new WP_Error( 'rest_forbidden', 'Access denied.', array( 'status' => 403 ) );
        }
        $wpdb->update( $wpdb->prefix . 'wpi_templates', array( 'status' => 'archived' ), array( 'id' => $id ) );
        return rest_ensure_response( array( 'success' => true ) );
    }

    // ── Questions ─────────────────────────────────────────────────

    public function get_questions( $request ) {
        global $wpdb;
        $template_id = absint( $request['tid'] );
        $rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wpi_questions WHERE template_id = %d ORDER BY sort_order ASC",
            $template_id
        ) );
        foreach ( $rows as &$row ) {
            $row->options = $row->options ? json_decode( $row->options ) : array();
        }
        return rest_ensure_response( $rows );
    }

    public function save_questions( $request ) {
        global $wpdb;
        $template_id = absint( $request['tid'] );
        $questions   = $request->get_param( 'questions' );

        if ( ! is_array( $questions ) ) return new WP_Error( 'invalid', 'Questions must be an array', array( 'status' => 400 ) );

        // Delete old, insert fresh (simple replace strategy)
        $wpdb->delete( $wpdb->prefix . 'wpi_questions', array( 'template_id' => $template_id ) );

        foreach ( $questions as $index => $q ) {
            $wpdb->insert( $wpdb->prefix . 'wpi_questions', array(
                'template_id' => $template_id,
                'label'       => sanitize_text_field( $q['label'] ?? '' ),
                'type'        => sanitize_text_field( $q['type']  ?? 'yes_no' ),
                'options'     => isset( $q['options'] ) ? json_encode( $q['options'] ) : null,
                'is_required' => ! empty( $q['is_required'] ) ? 1 : 0,
                'sort_order'  => $index,
                'section'     => sanitize_text_field( $q['section'] ?? '' ),
            ) );
        }

        return rest_ensure_response( array( 'success' => true ) );
    }

    // ── Inspections ───────────────────────────────────────────────

    public function get_inspections( $request ) {
        global $wpdb;
        $user_id = get_current_user_id();
        $status  = sanitize_text_field( $request->get_param( 'status' ) ?: '' );
        $org_id  = $this->get_api_org_id();
        $limit   = min( absint( $request->get_param('limit') ?: 200 ), 1000 );
        $offset  = absint( $request->get_param('offset') ?: 0 );

        // Org scope takes priority; within org admins see all, others see own
        if ( $org_id ) {
            $where = $wpdb->prepare( 'AND i.org_id = %d', $org_id );
        } else {
            $where = current_user_can( 'manage_options' ) ? '' : $wpdb->prepare( 'AND i.conducted_by = %d', $user_id );
        }
        if ( $status ) $where .= $wpdb->prepare( ' AND i.status = %s', $status );
        // template filter
        $template_id = absint( $request->get_param('template_id') ?: 0 );
        if ( $template_id ) $where .= $wpdb->prepare( ' AND i.template_id = %d', $template_id );
        // date range
        $date_from = sanitize_text_field( $request->get_param('date_from') ?: '' );
        $date_to   = sanitize_text_field( $request->get_param('date_to')   ?: '' );
        if ( $date_from ) $where .= $wpdb->prepare( ' AND i.conducted_at >= %s', $date_from );
        if ( $date_to   ) $where .= $wpdb->prepare( ' AND i.conducted_at <= %s', $date_to . ' 23:59:59' );

        $rows = $wpdb->get_results(
            "SELECT i.*, t.title as template_title, u.display_name as inspector_name
             FROM {$wpdb->prefix}wpi_inspections i
             LEFT JOIN {$wpdb->prefix}wpi_templates t ON t.id = i.template_id
             LEFT JOIN {$wpdb->users} u ON u.ID = i.conducted_by
             WHERE 1=1 $where
             ORDER BY i.conducted_at DESC
             LIMIT $limit OFFSET $offset"
        );
        return rest_ensure_response( $rows );
    }

    public function get_inspection( $request ) {
        global $wpdb;
        $id  = absint( $request['id'] );
        $row = $wpdb->get_row( $wpdb->prepare(
            "SELECT i.*, t.title as template_title
             FROM {$wpdb->prefix}wpi_inspections i
             LEFT JOIN {$wpdb->prefix}wpi_templates t ON t.id = i.template_id
             WHERE i.id = %d", $id
        ) );
        if ( ! $row ) return new WP_Error( 'not_found', 'Inspection not found', array( 'status' => 404 ) );
        $api_org = $this->get_api_org_id();
        $uid     = get_current_user_id();
        if ( $api_org ) {
            if ( (int) $row->org_id !== $api_org ) {
                return new WP_Error( 'rest_forbidden', 'Access denied.', array( 'status' => 403 ) );
            }
        } elseif ( ! current_user_can( 'manage_options' ) && (int) $row->conducted_by !== $uid ) {
            return new WP_Error( 'rest_forbidden', 'Access denied.', array( 'status' => 403 ) );
        }

        // Attach responses
        $responses = $wpdb->get_results( $wpdb->prepare(
            "SELECT r.*, q.label, q.type, q.section
             FROM {$wpdb->prefix}wpi_responses r
             LEFT JOIN {$wpdb->prefix}wpi_questions q ON q.id = r.question_id
             WHERE r.inspection_id = %d", $id
        ) );
        $row->responses = $responses;

        return rest_ensure_response( $row );
    }

    public function create_inspection( $request ) {
        global $wpdb;
        $template_id = absint( $request->get_param( 'template_id' ) );
        if ( ! $template_id ) return new WP_Error( 'missing', 'template_id is required', array( 'status' => 400 ) );

        $template = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}wpi_templates WHERE id = %d", $template_id ) );
        if ( ! $template ) return new WP_Error( 'not_found', 'Template not found', array( 'status' => 404 ) );

        $data = array(
            'template_id'  => $template_id,
            'title'        => sanitize_text_field( $request->get_param( 'title' ) ?: $template->title . ' - ' . date( 'Y-m-d' ) ),
            'conducted_by' => get_current_user_id(),
            'site_name'    => sanitize_text_field( $request->get_param( 'site_name' ) ?: '' ),
            'status'       => 'in_progress',
        );
        $wpdb->insert( $wpdb->prefix . 'wpi_inspections', $data );
        $data['id'] = $wpdb->insert_id;
        return rest_ensure_response( $data );
    }

    public function update_inspection( $request ) {
        global $wpdb;
        $id  = absint( $request['id'] );
        $ins = $wpdb->get_row( $wpdb->prepare(
            "SELECT org_id, conducted_by FROM {$wpdb->prefix}wpi_inspections WHERE id = %d", $id
        ) );
        if ( ! $ins ) return new WP_Error( 'not_found', 'Inspection not found', array( 'status' => 404 ) );
        $api_org = $this->get_api_org_id();
        $uid     = get_current_user_id();
        if ( $api_org ) {
            if ( (int) $ins->org_id !== $api_org ) {
                return new WP_Error( 'rest_forbidden', 'Access denied.', array( 'status' => 403 ) );
            }
        } elseif ( ! current_user_can( 'manage_options' ) && (int) $ins->conducted_by !== $uid ) {
            return new WP_Error( 'rest_forbidden', 'Access denied.', array( 'status' => 403 ) );
        }
        $responses = $request->get_param( 'responses' );
        $status    = sanitize_text_field( $request->get_param( 'status' ) ?: 'in_progress' );
        $notes     = sanitize_textarea_field( $request->get_param( 'notes' ) ?: '' );

        // Save responses
        if ( is_array( $responses ) ) {
            foreach ( $responses as $r ) {
                $question_id = absint( $r['question_id'] ?? 0 );
                $existing    = $wpdb->get_var( $wpdb->prepare(
                    "SELECT id FROM {$wpdb->prefix}wpi_responses WHERE inspection_id = %d AND question_id = %d",
                    $id, $question_id
                ) );
                $row = array(
                    'inspection_id' => $id,
                    'question_id'   => $question_id,
                    'value'         => sanitize_text_field( $r['value'] ?? '' ),
                    'photo_url'     => esc_url_raw( $r['photo_url'] ?? '' ),
                    'flagged'       => ! empty( $r['flagged'] ) ? 1 : 0,
                    'notes'         => sanitize_textarea_field( $r['notes'] ?? '' ),
                );
                if ( $existing ) {
                    $wpdb->update( $wpdb->prefix . 'wpi_responses', $row, array( 'id' => $existing ) );
                } else {
                    $wpdb->insert( $wpdb->prefix . 'wpi_responses', $row );
                }
            }
        }

        // Calculate score
        $score = $this->calculate_score( $id );

        $update = array( 'status' => $status, 'notes' => $notes, 'score' => $score );
        if ( $status === 'completed' ) $update['completed_at'] = current_time( 'mysql' );
        $wpdb->update( $wpdb->prefix . 'wpi_inspections', $update, array( 'id' => $id ) );

        return rest_ensure_response( array( 'success' => true, 'score' => $score ) );
    }

    private function calculate_score( $inspection_id ) {
        global $wpdb;

        $scoreable_types = array('yes_no','multiple_choice','select','dropdown','checkbox','radio','multi_select');
        $na_values       = array('n/a','na','n-a','not applicable','not_applicable');

        $questions = $wpdb->get_results( $wpdb->prepare(
            "SELECT q.type, q.is_scored, q.passing_answer, r.value
               FROM {$wpdb->prefix}wpi_questions q
               LEFT JOIN {$wpdb->prefix}wpi_responses r
                 ON r.question_id = q.id AND r.inspection_id = %d
              WHERE q.template_id = (
                  SELECT template_id FROM {$wpdb->prefix}wpi_inspections WHERE id = %d
              )
                AND q.is_scored IS NOT NULL",
            $inspection_id, $inspection_id
        ) );

        if ( empty( $questions ) ) return null;

        $total  = 0;
        $passed = 0;

        foreach ( $questions as $q ) {
            if ( ! in_array( $q->type, $scoreable_types, true ) ) continue;

            $is_scored = $q->is_scored !== null ? (int)$q->is_scored : null;
            if ( $is_scored === null ) continue;

            $val    = trim( (string)$q->value );
            $val_lc = strtolower( $val );

            if ( $val === '' ) continue;                                      // unanswered
            if ( in_array( $val_lc, $na_values, true ) ) continue;           // N/A — excluded

            if ( $is_scored === 0 ) { $total++; continue; }                  // always fail

            $total++;
            $passing = strtolower( trim( (string)$q->passing_answer ) );

            if ( $passing === '' ) {
                if ( $q->type === 'yes_no' ) {
                    if ( $val_lc === 'yes' ) $passed++;
                } else {
                    $passed++;
                }
            } else {
                if ( $val_lc === $passing ) $passed++;
            }
        }

        return $total > 0 ? round( ( $passed / $total ) * 100, 2 ) : null;
    }

    // ── PDF ───────────────────────────────────────────────────────

    public function download_pdf( $request ) {
        $id  = absint( $request['id'] );
        $pdf = new WPI_PDF();
        $pdf->generate( $id );
    }

    // ── Dashboard ─────────────────────────────────────────────────

    public function get_dashboard( $request ) {
        global $wpdb;
        $user_id    = get_current_user_id();
        $is_admin   = current_user_can( 'manage_options' );
        $user_where = $is_admin ? '' : $wpdb->prepare( ' AND conducted_by = %d', $user_id );

        $total       = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}wpi_inspections WHERE 1=1 $user_where" );
        $completed   = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}wpi_inspections WHERE status='completed' $user_where" );
        $avg_score   = $wpdb->get_var( "SELECT AVG(score) FROM {$wpdb->prefix}wpi_inspections WHERE score IS NOT NULL $user_where" );
        $templates   = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}wpi_templates WHERE status='active'" );
        $recent      = $wpdb->get_results( "SELECT i.*, t.title as template_title FROM {$wpdb->prefix}wpi_inspections i LEFT JOIN {$wpdb->prefix}wpi_templates t ON t.id = i.template_id WHERE 1=1 $user_where ORDER BY i.conducted_at DESC LIMIT 5" );

        return rest_ensure_response( array(
            'total_inspections'  => (int) $total,
            'completed'          => (int) $completed,
            'in_progress'        => (int) $total - (int) $completed,
            'avg_score'          => $avg_score ? round( $avg_score, 1 ) : null,
            'total_templates'    => (int) $templates,
            'recent_inspections' => $recent,
        ) );
    }
    public function wpi_rest_login( WP_REST_Request $request ) {
        // Rate limit
        $ip           = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $log          = sanitize_text_field( $request->get_param( 'wpi_u' ) ?? '' );
        $pwd          = $request->get_param( 'wpi_k' ) ?? '';
        $nonce        = sanitize_text_field( $request->get_param( 'nonce' ) ?? '' );

        if ( ! $nonce || ! wp_verify_nonce( $nonce, 'wpi_login' ) ) {
            return new WP_REST_Response( array( 'success' => false, 'data' => array( 'message' => 'Security check failed. Please refresh the page.' ) ), 403 );
        }

        $rate_key_ip  = 'wpi_login_ip_'  . md5( $ip );
        $rate_key_usr = 'wpi_login_usr_' . md5( strtolower( $log ) );
        if ( (int) get_transient( $rate_key_ip ) >= 10 || (int) get_transient( $rate_key_usr ) >= 20 ) {
            return new WP_REST_Response( array( 'success' => false, 'data' => array( 'message' => 'Too many login attempts. Please wait a few minutes.' ) ), 429 );
        }
        set_transient( $rate_key_ip,  (int) get_transient( $rate_key_ip )  + 1, 5 * MINUTE_IN_SECONDS );
        set_transient( $rate_key_usr, (int) get_transient( $rate_key_usr ) + 1, 5 * MINUTE_IN_SECONDS );

        if ( ! $log || ! $pwd ) {
            return new WP_REST_Response( array( 'success' => false, 'data' => array( 'message' => 'Please enter your username and password.' ) ), 400 );
        }

        $username = $log;
        if ( is_email( $log ) ) {
            $u = get_user_by( 'email', $log );
            if ( $u ) $username = $u->user_login;
        }

        // wp_authenticate does not set cookies - allows us to set them
        // before REST API sends headers, via the rest_pre_serve_request filter
        $user = wp_authenticate( $username, $pwd );

        if ( is_wp_error( $user ) ) {
            return new WP_REST_Response( array( 'success' => false, 'data' => array( 'message' => 'Incorrect username or password.' ) ), 401 );
        }

        $remember = (bool) $request->get_param( 'rememberme' );

        // Set auth cookies via filter before REST sends headers
        add_filter( 'rest_pre_serve_request', function( $served ) use ( $user, $remember ) {
            wp_set_auth_cookie( $user->ID, $remember, is_ssl() );
            do_action( 'wp_login', $user->user_login, $user );
            return $served;
        }, 1 );

        delete_transient( $rate_key_ip );
        delete_transient( $rate_key_usr );
        return new WP_REST_Response( array( 'success' => true, 'data' => array( 'redirect' => home_url( '/?wpi=1' ) ) ), 200 );
    }

}
