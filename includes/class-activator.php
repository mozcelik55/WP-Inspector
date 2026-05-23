<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WPI_Activator {

    public static function activate() {
        self::create_tables();
        update_option( 'wpi_version', WPI_VERSION );
        flush_rewrite_rules();
    }

    public static function create_tables() {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

        // Templates
        dbDelta( "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wpi_templates (
            id          BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            title       VARCHAR(255) NOT NULL,
            description TEXT,
            settings    LONGTEXT DEFAULT NULL,
            created_by  BIGINT(20) UNSIGNED NOT NULL,
            org_id      BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            status      VARCHAR(20) DEFAULT 'active',
            created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at  DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset;" );

        // Questions — use VARCHAR not ENUM so new types never break
        dbDelta( "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wpi_questions (
            id          BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            template_id BIGINT(20) UNSIGNED NOT NULL,
            label       TEXT NOT NULL,
            type        VARCHAR(50) DEFAULT 'yes_no',
            options     LONGTEXT,
            logic       LONGTEXT DEFAULT NULL,
            answer_colors  LONGTEXT DEFAULT NULL,
            repeatable     TINYINT(1) DEFAULT 0,
            is_required    TINYINT(1) DEFAULT 0,
            is_scored      TINYINT(1) NOT NULL DEFAULT 1,
            passing_answer VARCHAR(500) NOT NULL DEFAULT '',
            sort_order  INT DEFAULT 0,
            section     VARCHAR(255) DEFAULT '',
            PRIMARY KEY (id),
            KEY template_id (template_id)
        ) $charset;" );

        // Inspections
        dbDelta( "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wpi_inspections (
            id           BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            template_id  BIGINT(20) UNSIGNED NOT NULL,
            title        VARCHAR(255) NOT NULL,
            conducted_by BIGINT(20) UNSIGNED NOT NULL,
            org_id       BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            site_name    VARCHAR(255) DEFAULT '',
            status       VARCHAR(20) DEFAULT 'in_progress',
            score        DECIMAL(5,2) DEFAULT NULL,
            notes        TEXT,
            conducted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            completed_at DATETIME DEFAULT NULL,
            PRIMARY KEY (id),
            KEY template_id (template_id),
            KEY conducted_by (conducted_by)
        ) $charset;" );

        // Responses — question_id as VARCHAR to support child keys
        dbDelta( "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wpi_responses (
            id            BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            inspection_id BIGINT(20) UNSIGNED NOT NULL,
            question_id   VARCHAR(100) NOT NULL DEFAULT '0',
            value         LONGTEXT,
            photos        LONGTEXT DEFAULT NULL,
            flagged       TINYINT(1) DEFAULT 0,
            notes         TEXT,
            PRIMARY KEY (id),
            KEY inspection_id (inspection_id),
            KEY question_id (question_id)
        ) $charset;" );

        // Teams
        dbDelta( "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wpi_teams (
            id          BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name        VARCHAR(255) NOT NULL,
            description TEXT,
            created_by  BIGINT(20) UNSIGNED NOT NULL,
            org_id      BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset;" );

        // Team members
        dbDelta( "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wpi_team_members (
            id        BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            team_id   BIGINT(20) UNSIGNED NOT NULL,
            user_id   BIGINT(20) UNSIGNED NOT NULL,
            role      VARCHAR(20) DEFAULT 'member',
            joined_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY team_user (team_id, user_id),
            KEY team_id (team_id),
            KEY user_id (user_id)
        ) $charset;" );

        // Template shares
        dbDelta( "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wpi_template_shares (
            id               BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            template_id      BIGINT(20) UNSIGNED NOT NULL,
            shared_with_type VARCHAR(10) NOT NULL,
            shared_with_id   BIGINT(20) UNSIGNED NOT NULL,
            access           VARCHAR(20) DEFAULT 'view',
            shared_by        BIGINT(20) UNSIGNED NOT NULL,
            shared_at        DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY share_unique (template_id, shared_with_type, shared_with_id),
            KEY template_id (template_id)
        ) $charset;" );

        // User roles
        dbDelta( "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wpi_user_roles (
            id      BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            role    VARCHAR(20) NOT NULL DEFAULT 'standard',
            set_by  BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            set_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_id (user_id)
        ) $charset;" );

        // Organisations
        dbDelta( "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wpi_organisations (
            id              BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name            VARCHAR(255) NOT NULL,
            description     TEXT,
            owner_id        BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            status          VARCHAR(20) DEFAULT 'active',
            licence_type    VARCHAR(20) DEFAULT 'lifetime',
            licence_start   DATE DEFAULT NULL,
            licence_end     DATE DEFAULT NULL,
            trial_days      INT(11) DEFAULT 14,
            max_sessions    INT(11) DEFAULT NULL,
            created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset;" );

        // Organisation users
        dbDelta( "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wpi_org_users (
            id      BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            org_id  BIGINT(20) UNSIGNED NOT NULL,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            role    VARCHAR(20) DEFAULT 'member',
            PRIMARY KEY (id),
            UNIQUE KEY org_user (org_id, user_id),
            KEY org_id (org_id),
            KEY user_id (user_id)
        ) $charset;" );

        // Sites
        dbDelta( "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wpi_sites (
            id          BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name        VARCHAR(255) NOT NULL,
            description TEXT,
            address     VARCHAR(255) DEFAULT '',
            created_by  BIGINT(20) UNSIGNED NOT NULL,
            org_id      BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset;" );

        // Site users
        dbDelta( "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wpi_site_users (
            id        BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            site_id   BIGINT(20) UNSIGNED NOT NULL,
            user_id   BIGINT(20) UNSIGNED NOT NULL,
            role      VARCHAR(20) DEFAULT 'member',
            added_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY site_user (site_id, user_id),
            KEY site_id (site_id),
            KEY user_id (user_id)
        ) $charset;" );

        // Activity log — audit trail for inspections and templates
        dbDelta( "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wpi_activity_log (
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
        ) $charset;" );

        // Corrective actions — raised during inspections on flagged items
        dbDelta( "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wpi_actions (
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
        ) $charset;" );

        // ── Audit4me Access — licence token pool ──────────────────────
        dbDelta( "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wpi_licences (
            id              BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            token           VARCHAR(32) NOT NULL,
            licence_type    VARCHAR(20) NOT NULL DEFAULT 'trial',
            status          VARCHAR(20) NOT NULL DEFAULT 'unassigned',
            org_id          BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            user_id         BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            assigned_to     VARCHAR(10) NOT NULL DEFAULT 'none',
            seats           INT(11) NOT NULL DEFAULT 1,
            start_date      DATE DEFAULT NULL,
            expiry_date     DATE DEFAULT NULL,
            notes           TEXT DEFAULT NULL,
            created_by      BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            assigned_at     DATETIME DEFAULT NULL,
            created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY token (token),
            KEY org_id (org_id),
            KEY user_id (user_id),
            KEY status (status),
            KEY expiry_date (expiry_date)
        ) $charset;" );

        // ── Audit4me Access — token audit log ─────────────────────────
        dbDelta( "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wpi_token_log (
            id              BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            licence_id      BIGINT(20) UNSIGNED NOT NULL,
            org_id          BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            event           VARCHAR(50) NOT NULL,
            detail          TEXT DEFAULT NULL,
            performed_by    BIGINT(20) UNSIGNED DEFAULT 0,
            created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY licence_id (licence_id),
            KEY event (event),
            KEY created_at (created_at)
        ) $charset;" );

        dbDelta( "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wpi_licence_seats (
            id              BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            licence_id      BIGINT(20) UNSIGNED NOT NULL,
            seat_number     INT(11) NOT NULL DEFAULT 1,
            user_id         BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            status          VARCHAR(20) NOT NULL DEFAULT 'available',
            joined_at       DATETIME DEFAULT NULL,
            revoked_at      DATETIME DEFAULT NULL,
            revoked_by      BIGINT(20) UNSIGNED DEFAULT NULL,
            created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY licence_id (licence_id),
            KEY user_id (user_id),
            KEY status (status),
            UNIQUE KEY unique_seat (licence_id, seat_number)
        ) $charset;" );

        // Device/session tracking for System Admin controlled login limits
        dbDelta( "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wpi_sessions (
            id          BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id     BIGINT(20) UNSIGNED NOT NULL,
            org_id      BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            session_key VARCHAR(100) NOT NULL,
            device_info VARCHAR(700) DEFAULT '',
            ip_address  VARCHAR(45) DEFAULT '',
            status      VARCHAR(20) NOT NULL DEFAULT 'active',
            last_active DATETIME NOT NULL,
            created_at  DATETIME NOT NULL,
            removed_at  DATETIME NULL,
            blocked_at  DATETIME NULL,
            expired_at  DATETIME NULL,
            PRIMARY KEY (id),
            UNIQUE KEY session_key (session_key),
            KEY user_id (user_id),
            KEY org_id (org_id),
            KEY last_active (last_active),
            KEY status (status)
        ) $charset;" );

        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wpi_invitations (
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
        ) $charset_collate;";
        dbDelta( $sql );


    }
}
