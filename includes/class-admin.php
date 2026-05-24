<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WPI_Admin {

    public function init() {
        add_action( 'admin_menu',            array( $this, 'add_menu_pages' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_action( 'admin_init',            array( $this, 'maybe_upgrade_db' ) );
        // Custom login page
        add_action( 'login_enqueue_scripts', array( $this, 'login_styles' ) );
        add_filter( 'login_headerurl',       array( $this, 'login_logo_url' ) );
        add_filter( 'login_headertext',      array( $this, 'login_logo_text' ) );
        add_filter( 'login_title',           array( $this, 'login_title' ) );
        add_action( 'login_footer',          array( $this, 'wpi_login_footer' ) );
        // Remove WP admin footer and bar on our page
        add_action( 'admin_init', array( $this, 'remove_wp_chrome' ) );

        // ── Browser tab titles ──────────────────────────────────
        add_filter( 'admin_title', array( $this, 'wpi_admin_title' ), 10, 2 );

        // ── Access lockdown for non-system-owner users ──────────
        add_filter( 'login_redirect',        array( $this, 'wpi_login_redirect' ), 10, 3 );
        add_action( 'admin_init',            array( $this, 'wpi_restrict_admin_access' ) );
        add_action( 'admin_menu',            array( $this, 'wpi_remove_admin_menus' ), 999 );
        // Block REST API media endpoints for non-system-owners
        add_filter( 'rest_pre_dispatch',     array( $this, 'wpi_block_media_rest' ), 10, 3 );
        // Block WP core media AJAX actions for non-system-owners
        add_action( 'init',                  array( $this, 'wpi_block_media_ajax' ) );
        // Block upload_files capability from being used outside WPI context
        add_filter( 'user_has_cap',          array( $this, 'wpi_filter_upload_cap' ), 10, 4 );
        // Redirect front-end visits to WP Inspector for non-system-owners
        add_action( 'template_redirect',     array( $this, 'wpi_front_redirect' ) );
    }

    /**
     * Redirect non-system-owners away from the public front-end to WP Inspector.
     * Skips PDF share links (wpi_share / wpi_pdf) so those still work.
     */
    public function wpi_front_redirect() {
        if ( ! is_user_logged_in() || self::is_system_owner() ) return;
        // Allow WPI's own front-end endpoints
        if ( ! empty( $_GET['wpi_share'] ) || ! empty( $_GET['wpi_pdf'] ) ) return;
        wp_safe_redirect( admin_url( 'admin.php?page=wp-inspector' ) );
        exit;
    }

    /**
     * After login, redirect non-system-owners straight to WP Inspector.
     */
    public function wpi_login_redirect( $redirect_to, $requested_redirect_to, $user ) {
        if ( is_wp_error( $user ) || self::is_system_owner( $user->ID ) ) {
            return $redirect_to; // System owner goes wherever WP would send them
        }
        return admin_url( 'admin.php?page=wp-inspector' );
    }

    /**
     * Block non-system-owners from accessing any WP admin page except WP Inspector.
     * Also blocks direct URL access to media library, upload.php, etc.
     */
    public function wpi_restrict_admin_access() {
        if ( ! is_user_logged_in() || self::is_system_owner() ) return;

        // Allow admin-ajax.php — required for all WPI AJAX calls
        if ( wp_doing_ajax() ) return;

        $page    = $_GET['page'] ?? '';
        $pagenow = $GLOBALS['pagenow'] ?? '';

        // Allow WP Inspector pages only
        $allowed_pages = array( 'wp-inspector', 'wp-inspector-inspections', 'wp-inspector-templates' );
        if ( $pagenow === 'admin.php' && in_array( $page, $allowed_pages, true ) ) return;

        // Everything else → redirect to WP Inspector
        wp_safe_redirect( admin_url( 'admin.php?page=wp-inspector' ) );
        exit;
    }

    /**
     * Remove all WP admin menu items for non-system-owners, leaving only WP Inspector.
     */
    public function wpi_remove_admin_menus() {
        if ( self::is_system_owner() ) return;

        global $menu, $submenu;

        // Keep only our plugin's menu slug
        $keep = 'wp-inspector';

        foreach ( $menu as $pos => $item ) {
            $slug = $item[2] ?? '';
            if ( $slug !== $keep ) {
                remove_menu_page( $slug );
            }
        }

        // Remove all submenus except ours
        foreach ( array_keys( $submenu ) as $slug ) {
            if ( $slug !== $keep ) {
                remove_submenu_page( $slug, $slug );
            }
        }
    }

    /**
     * Block REST API access to the media/attachments endpoint for non-system-owners.
     * Photos are uploaded exclusively via wpi_upload_photo AJAX — users have no
     * legitimate reason to browse the media library via REST.
     */
    public function wpi_block_media_rest( $result, $server, $request ) {
        if ( self::is_system_owner() ) return $result;

        $route = $request->get_route();
        // Block /wp/v2/media endpoints
        if ( strpos( $route, '/wp/v2/media' ) !== false ) {
            return new WP_Error(
                'wpi_media_forbidden',
                'Access to media library is restricted.',
                array( 'status' => 403 )
            );
        }
        return $result;
    }

    /**
     * Block WP core media-related AJAX actions for non-system-owners.
     * These actions allow browsing/uploading via WP's own media modal —
     * we remove them so only wpi_upload_photo (our controlled handler) works.
     */
    public function wpi_block_media_ajax() {
        if ( ! is_user_logged_in() || self::is_system_owner() ) return;

        $blocked_actions = array(
            'query-attachments',       // Media library browser
            'get-attachment',          // Fetch single attachment data
            'upload-attachment',       // WP's own upload handler
            'send-attachment-to-editor',
            'save-attachment',
            'save-attachment-compat',
            'set-post-thumbnail',
            'media-create-image-subsizes',
            'crop-image',
            'imgedit-preview',
        );

        foreach ( $blocked_actions as $action ) {
            remove_action( 'wp_ajax_' . $action, 'wp_ajax_' . str_replace('-','_',$action) );
            // Add our own 403 handler in place
            add_action( 'wp_ajax_' . $action, function() {
                wp_send_json_error( array( 'message' => 'Access denied.' ), 403 );
            });
        }
    }

    /**
     * Strip the upload_files capability from non-system-owners EXCEPT when
     * called from within WPI's own wpi_upload_photo handler.
     * This prevents any other code path from leveraging the capability.
     */
    public function wpi_filter_upload_cap( $allcaps, $caps, $args, $user ) {
        if ( self::is_system_owner( $user->ID ) ) return $allcaps;

        // Allow only if the current AJAX action is our own photo upload
        // OR if we're in the PDF generation endpoint
        $current_action = $_POST['action'] ?? $_GET['action'] ?? '';
        if ( $current_action === 'wpi_upload_photo' ) return $allcaps;
        if ( ! empty( $_GET['wpi_pdf'] ) || ! empty( $_GET['wpi_share'] ) ) return $allcaps;

        // Remove upload_files from all other contexts
        if ( isset( $allcaps['upload_files'] ) ) {
            $allcaps['upload_files'] = false;
        }
        return $allcaps;
    }

    public function maybe_upgrade_db() {
        global $wpdb;
        // Sites tables
        $wpdb->query( "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}wpi_sites` (
            `id`          BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            `name`        VARCHAR(255) NOT NULL,
            `description` TEXT,
            `address`     VARCHAR(255) DEFAULT '',
            `created_by`  BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            `created_at`  DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) {$wpdb->get_charset_collate()}" );

        $wpdb->query( "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}wpi_site_users` (
            `id`       BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            `site_id`  BIGINT(20) UNSIGNED NOT NULL,
            `user_id`  BIGINT(20) UNSIGNED NOT NULL,
            `role`     VARCHAR(20) DEFAULT 'member',
            `added_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `site_user` (`site_id`, `user_id`),
            KEY `site_id` (`site_id`),
            KEY `user_id` (`user_id`)
        ) {$wpdb->get_charset_collate()}" );

        // Add scoring columns to questions table if missing
        $cols = $wpdb->get_col( "SHOW COLUMNS FROM `{$wpdb->prefix}wpi_questions`", 0 );
        if ( is_array( $cols ) ) {
            if ( !in_array( 'is_scored', $cols ) ) {
                $wpdb->query( "ALTER TABLE `{$wpdb->prefix}wpi_questions` ADD COLUMN `is_scored` TINYINT(1) NOT NULL DEFAULT 1" );
            }
            if ( !in_array( 'passing_answer', $cols ) ) {
                $wpdb->query( "ALTER TABLE `{$wpdb->prefix}wpi_questions` ADD COLUMN `passing_answer` VARCHAR(500) NOT NULL DEFAULT ''" );
            }
        }

        // Organisations table (with licence columns)
        $wpdb->query( "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}wpi_organisations` (
            `id`            BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            `name`          VARCHAR(255) NOT NULL,
            `description`   TEXT,
            `owner_id`      BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            `status`        VARCHAR(20) DEFAULT 'active',
            `licence_type`  VARCHAR(20) DEFAULT 'lifetime',
            `licence_start` DATE DEFAULT NULL,
            `licence_end`   DATE DEFAULT NULL,
            `trial_days`    INT(11) DEFAULT 7,
            `created_at`    DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) {$wpdb->get_charset_collate()}" );

        // Add licence columns to existing organisations table if missing
        $org_cols = $wpdb->get_col( "SHOW COLUMNS FROM `{$wpdb->prefix}wpi_organisations`", 0 );
        if ( is_array($org_cols) ) {
            if ( !in_array('licence_type',  $org_cols) ) $wpdb->query( "ALTER TABLE `{$wpdb->prefix}wpi_organisations` ADD COLUMN `licence_type` VARCHAR(20) DEFAULT 'lifetime'" );
            if ( !in_array('licence_start', $org_cols) ) $wpdb->query( "ALTER TABLE `{$wpdb->prefix}wpi_organisations` ADD COLUMN `licence_start` DATE DEFAULT NULL" );
            if ( !in_array('licence_end',   $org_cols) ) $wpdb->query( "ALTER TABLE `{$wpdb->prefix}wpi_organisations` ADD COLUMN `licence_end` DATE DEFAULT NULL" );
            if ( !in_array('trial_days',    $org_cols) ) $wpdb->query( "ALTER TABLE `{$wpdb->prefix}wpi_organisations` ADD COLUMN `trial_days` INT(11) DEFAULT 7" );
        }

        $wpdb->query( "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}wpi_org_users` (
            `id`      BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            `org_id`  BIGINT(20) UNSIGNED NOT NULL,
            `user_id` BIGINT(20) UNSIGNED NOT NULL,
            `role`    VARCHAR(20) DEFAULT 'member',
            PRIMARY KEY (`id`),
            UNIQUE KEY `org_user` (`org_id`, `user_id`),
            KEY `org_id` (`org_id`),
            KEY `user_id` (`user_id`)
        ) {$wpdb->get_charset_collate()}" );

        // Add org_id to key tables if missing
        foreach ( array( 'wpi_templates', 'wpi_inspections', 'wpi_teams', 'wpi_sites' ) as $tbl ) {
            $tcols = $wpdb->get_col( "SHOW COLUMNS FROM `{$wpdb->prefix}{$tbl}`", 0 );
            if ( is_array($tcols) && !in_array('org_id', $tcols) ) {
                $wpdb->query( "ALTER TABLE `{$wpdb->prefix}{$tbl}` ADD COLUMN `org_id` BIGINT(20) UNSIGNED NOT NULL DEFAULT 0" );
            }
        }
    }

    public function remove_wp_chrome() {
        if ( ! isset( $_GET['page'] ) || strpos( $_GET['page'], 'wp-inspector' ) === false ) return;
        // Remove admin bar
        add_filter( 'show_admin_bar', '__return_false' );
        // Remove admin footer text and version
        add_filter( 'admin_footer_text', '__return_empty_string' );
        add_filter( 'update_footer',    '__return_empty_string', 99 );
    }

    public function add_menu_pages() {
        add_menu_page(
            __( 'WP Inspector', 'wp-inspector' ),
            __( 'WP Inspector', 'wp-inspector' ),
            'read', 'wp-inspector',
            array( $this, 'render_app' ),
            'dashicons-clipboard', 30
        );
        add_submenu_page( 'wp-inspector', 'Dashboard',   'Dashboard',   'read',           'wp-inspector',             array( $this, 'render_app' ) );
        add_submenu_page( 'wp-inspector', 'Inspections', 'Inspections', 'read',           'wp-inspector-inspections', array( $this, 'render_app' ) );
        add_submenu_page( 'wp-inspector', 'Templates',   'Templates',   'read',           'wp-inspector-templates',   array( $this, 'render_app' ) );
    }

    public function enqueue_assets( $hook ) {
        if ( strpos( $hook, 'wp-inspector' ) === false ) return;

        $js_file = WPI_PLUGIN_DIR . 'assets/js/app.js';
        $ver     = file_exists( $js_file ) ? filemtime( $js_file ) . '-' . WPI_VERSION : WPI_VERSION;

        // Fix mobile viewport — must override WP admin's default
        add_action( 'admin_head', array( $this, 'inject_viewport_meta' ) );

        // Explicitly register and enqueue wp-element (React) first
        wp_enqueue_script( 'wp-element' );

        wp_enqueue_script( 'wp-inspector-app', WPI_PLUGIN_URL . 'assets/js/app.js', array('jquery','wp-element'), $ver, true );
        wp_enqueue_style(  'wp-inspector-css', WPI_PLUGIN_URL . 'assets/css/app.css', array(), $ver );

        $uid  = get_current_user_id();
        $u    = wp_get_current_user();
        $fn   = get_user_meta( $uid, 'first_name', true );
        $ln   = get_user_meta( $uid, 'last_name',  true );
        $full = trim("$fn $ln") ?: $u->display_name;
        global $wpdb;
        $wpi_role = 'standard'; // safe default
        if ( ! $u->has_cap('manage_options') ) {
            // Check wpi_user_roles table
            $table = $wpdb->prefix . 'wpi_user_roles';
            if ( $wpdb->get_var( "SHOW TABLES LIKE '$table'" ) === $table ) {
                $row = $wpdb->get_var( $wpdb->prepare(
                    "SELECT role FROM $table WHERE user_id=%d", $uid ) );
                if ( $row ) $wpi_role = $row;
            }
            // If no WPI role set yet but user is an org admin in wpi_org_users, elevate them
            if ( $wpi_role === 'standard' || $wpi_role === 'guest' ) {
                $org_table = $wpdb->prefix . 'wpi_org_users';
                if ( $wpdb->get_var( "SHOW TABLES LIKE '$org_table'" ) === $org_table ) {
                    $org_role = $wpdb->get_var( $wpdb->prepare(
                        "SELECT role FROM $org_table WHERE user_id=%d AND role='admin' LIMIT 1", $uid ) );
                    if ( $org_role === 'admin' ) {
                        $wpi_role = 'administrator';
                        // Also persist this to wpi_user_roles so it's consistent
                        $wpdb->replace( $wpdb->prefix . 'wpi_user_roles', array(
                            'user_id' => $uid,
                            'role'    => 'administrator',
                            'set_by'  => 0,
                        ) );
                    }
                }
            }
        } else {
            $wpi_role = 'administrator';
        }

        $is_system_owner = self::is_system_owner( $uid );
        $org_id          = self::get_user_org_id( $uid );
        $org_licence     = $is_system_owner ? array('status'=>'active') : WPI_Ajax::get_org_licence( $org_id );
        $user_tz = get_user_meta( $uid, 'wpi_timezone', true );
        if ( ! $user_tz ) {
            $user_tz = get_option( 'timezone_string', '' );
            if ( ! $user_tz ) {
                $offset  = (float) get_option( 'gmt_offset', 0 );
                $user_tz = 'UTC' . ( $offset >= 0 ? '+' . $offset : $offset );
            }
        }
        wp_localize_script( 'wp-inspector-app', 'wpInspector', array(
            'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
            'nonce'         => wp_create_nonce( 'wpi_nonce' ),
            'pluginUrl'     => WPI_PLUGIN_URL,
            'homeUrl'       => home_url( '/' ),
            'utcOffset'     => (float) get_option( 'gmt_offset' ),
            'logoutUrl'     => wp_logout_url( admin_url('admin.php?page=wp-inspector') ),
            'userTimezone'  => $user_tz,
            'isSystemOwner' => $is_system_owner,
            'orgLicence'    => $org_licence,
            'orgId'         => $org_id,
            'user'          => array(
                'id'        => $uid,
                'name'      => $full,
                'login'     => $u->user_login,
                'isAdmin'   => current_user_can( 'manage_options' ),
                'wpiRole'   => $wpi_role,
            ),
        ) );
    }

    public function inject_viewport_meta() {
        $manifest_url    = WPI_PLUGIN_URL . 'manifest.json';
        $sw_url          = WPI_PLUGIN_URL . 'sw.js';
        $icon_url        = WPI_PLUGIN_URL . 'assets/icons/app-icon-180.png';
        $icon_192        = WPI_PLUGIN_URL . 'assets/icons/icon-192x192.png';
        ?>
        <script>
        (function(){
            var existing = document.querySelector('meta[name="viewport"]');
            if (existing) existing.remove();
            var m = document.createElement('meta');
            m.name = 'viewport';
            m.content = 'width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover';
            document.head.appendChild(m);
        })();
        </script>

        <!-- PWA Manifest -->
        <link rel="manifest" href="<?php echo esc_url($manifest_url); ?>">

        <!-- iOS PWA meta tags -->
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
        <meta name="apple-mobile-web-app-title" content="Audit4Me">
        <link rel="apple-touch-icon" href="<?php echo esc_url($icon_url); ?>">

        <!-- Android / Chrome theme -->
        <meta name="theme-color" content="#1a3a5c">
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="application-name" content="Audit4Me">

        <!-- Splash screen background for iOS -->
        <style>
        html, body { background: #1a3a5c !important; }
        </style>

        <!-- Orientation lock + standalone-only enforcement -->
        <script>
        (function(){
            // Lock to portrait on mobile only
            var isMobile = /Android|iPhone|iPad|iPod/i.test(navigator.userAgent);
            if (isMobile && screen.orientation && screen.orientation.lock) {
                screen.orientation.lock('portrait').catch(function(){});
            }

            // Desktop access is fully supported — no install wall needed
        })();
        </script>

        <!-- Service Worker Registration + Offline Support -->
        <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('<?php echo esc_js($sw_url); ?>', {scope: '/'})
                    .then(function(reg) {
                        console.log('Audit4Me SW registered');
                        window._wpiSwReg = reg;
                    })
                    .catch(function(err) { console.log('SW registration failed:', err); });

                // Listen for messages from SW
                navigator.serviceWorker.addEventListener('message', function(event) {
                    var msg = event.data;
                    if (!msg) return;
                    if (msg.type === 'OFFLINE_SAVE_QUEUED') {
                        window.dispatchEvent(new CustomEvent('wpi:offline-queued'));
                    }
                    if (msg.type === 'SYNC_COMPLETE') {
                        window.dispatchEvent(new CustomEvent('wpi:sync-complete', {detail: msg}));
                    }
                });
            });
        }
        // Online/offline detection — fire custom events so React can listen
        window.addEventListener('online', function() {
            window.dispatchEvent(new CustomEvent('wpi:online'));
            if (window._wpiSwReg && window._wpiSwReg.sync) {
                window._wpiSwReg.sync.register('wpi-sync-inspections').catch(function(){});
            } else if (navigator.serviceWorker && navigator.serviceWorker.controller) {
                navigator.serviceWorker.controller.postMessage({type:'SYNC_NOW'});
            }
        });
        window.addEventListener('offline', function() {
            window.dispatchEvent(new CustomEvent('wpi:offline'));
        });
        </script>

        <style>
        /* ── Hide ALL WordPress admin chrome on WP Inspector pages ── */
        #wpadminbar,
        #adminmenuwrap,
        #adminmenuback,
        #adminmenu,
        #wpfooter,
        .wp-submenu,
        #collapse-button {
            display: none !important;
            visibility: hidden !important;
            width: 0 !important;
            min-width: 0 !important;
        }
        /* Remove all offsets and margins */
        html {
            margin-top: 0 !important;
        }
        body,
        body.wp-admin,
        body.folded {
            padding: 0 !important;
            margin: 0 !important;
        }
        #wpwrap {
            display: block !important;
            padding: 0 !important;
        }
        #wpcontent,
        #wpbody,
        #wpbody-content {
            margin: 0 !important;
            padding: 0 !important;
            float: none !important;
        }
        /* App container fills full viewport */
        #wp-inspector-root {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            bottom: 0 !important;
            z-index: 9999 !important;
            background: #f8f9fa !important;
        }
        /* PWA safe area support */
        @media (display-mode: standalone) {
            #wp-inspector-root {
                padding-top: env(safe-area-inset-top) !important;
            }
        }
        /* Prevent overscroll */
        html, body {
            overflow: hidden !important;
            overscroll-behavior: none !important;
        }
        /* Force portrait via CSS rotation on landscape (fallback) */
        @media screen and (orientation: landscape) and (max-height: 600px) {
            body::before {
                content: '';
                position: fixed;
                inset: 0;
                background: #1a3a5c;
                z-index: 99999;
            }
            body::after {
                content: '🔄 Please rotate your device to portrait mode';
                position: fixed;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                color: #fff;
                font-family: -apple-system, sans-serif;
                font-size: 16px;
                font-weight: 600;
                text-align: center;
                z-index: 100000;
                width: 80vw;
            }
        }
        </style>
        <?php
    }

    public function login_logo_url()  { return home_url(); }
    public function login_logo_text() { return 'Audit4Me'; }
    public function login_title()     { return 'Sign In | Audit4Me'; }

    /**
     * Override the browser tab title for all WP Inspector admin pages.
     * Format: "Page Name | Audit4Me" — or just "Audit4Me" for the dashboard.
     */
    public function wpi_admin_title( $admin_title, $title ) {
        $page = $_GET['page'] ?? '';
        if ( strpos( $page, 'wp-inspector' ) === false ) {
            return $admin_title; // Not our page — leave it alone
        }
        $brand = 'Audit4Me';
        $map   = array(
            'wp-inspector'              => $brand,          // Dashboard → just brand + slogan handled in JS
            'wp-inspector-inspections'  => 'Inspections | ' . $brand,
            'wp-inspector-templates'    => 'Templates | ' . $brand,
            'wp-inspector-settings'     => 'Settings | ' . $brand,
        );
        return isset( $map[$page] ) ? $map[$page] : $brand;
    }

    public function login_styles() {
        $icon_url = WPI_PLUGIN_URL . 'assets/icons/icon-192x192.png';
        ?>
        <style>
        /* ── Page ── */
        html, body.login {
            height: 100% !important;
            margin: 0 !important;
        }
        body.login {
            background: linear-gradient(160deg, #0a1628 0%, #1a3a5c 45%, #0d2137 100%) !important;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif !important;
            display: flex !important;
            flex-direction: column !important;
            align-items: center !important;
            justify-content: center !important;
            min-height: 100vh !important;
        }

        /* ── Center the whole login wrapper vertically ── */
        #login {
            width: 360px !important;
            max-width: calc(100vw - 32px) !important;
            padding: 0 !important;
            margin: 0 auto !important;
        }

        /* ── Logo / branding area ── */
        #login h1 {
            margin-bottom: 28px !important;
            text-align: center !important;
        }
        #login h1 a {
            background-image: url('<?php echo esc_url($icon_url); ?>') !important;
            background-size: cover !important;
            width: 80px !important;
            height: 80px !important;
            border-radius: 20px !important;
            display: block !important;
            margin: 0 auto 14px !important;
            box-shadow: 0 8px 32px rgba(0,0,0,0.45), 0 0 0 3px rgba(255,255,255,0.08) !important;
        }
        #login h1 a::after {
            content: 'Audit4me';
            display: block;
            color: #ffffff;
            font-size: 26px;
            font-weight: 800;
            letter-spacing: -0.5px;
            margin-top: 12px;
            text-shadow: 0 2px 8px rgba(0,0,0,0.3);
        }
        #login h1::after {
            content: 'Inspection & Audit Management';
            display: block;
            color: rgba(255,255,255,0.45);
            font-size: 12px;
            font-weight: 400;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            margin-top: 6px;
        }

        /* ── Form card ── */
        #loginform, #lostpasswordform, #registerform {
            background: #ffffff !important;
            border: none !important;
            border-radius: 20px !important;
            box-shadow:
                0 24px 64px rgba(0,0,0,0.4),
                0 1px 0 rgba(255,255,255,0.06) inset !important;
            padding: 36px 32px 28px !important;
            margin-top: 0 !important;
        }

        /* ── Labels ── */
        #loginform label,
        #lostpasswordform label {
            color: #6b7280 !important;
            font-size: 11px !important;
            font-weight: 700 !important;
            text-transform: uppercase !important;
            letter-spacing: 0.8px !important;
            margin-bottom: 6px !important;
            display: block !important;
        }

        /* ── Inputs ── */
        #loginform input[type="text"],
        #loginform input[type="password"],
        #loginform input[type="email"],
        #lostpasswordform input[type="text"],
        #lostpasswordform input[type="email"] {
            background: #f9fafb !important;
            border: 1.5px solid #e5e7eb !important;
            border-radius: 12px !important;
            padding: 13px 16px !important;
            font-size: 15px !important;
            color: #111827 !important;
            box-shadow: none !important;
            width: 100% !important;
            height: auto !important;
            box-sizing: border-box !important;
            margin-top: 4px !important;
            transition: border-color 0.2s, box-shadow 0.2s, background 0.2s !important;
        }
        #loginform input[type="text"]:focus,
        #loginform input[type="password"]:focus,
        #lostpasswordform input[type="text"]:focus {
            border-color: #1a3a5c !important;
            background: #fff !important;
            box-shadow: 0 0 0 4px rgba(26,58,92,0.12) !important;
            outline: none !important;
        }

        /* ── Field groups spacing ── */
        #loginform p { margin-bottom: 18px !important; }

        /* ── Remember me ── */
        #loginform .forgetmenot {
            display: flex !important;
            align-items: center !important;
            gap: 7px !important;
        }
        #loginform .forgetmenot label {
            color: #9ca3af !important;
            font-size: 13px !important;
            font-weight: 400 !important;
            text-transform: none !important;
            letter-spacing: 0 !important;
            cursor: pointer !important;
        }
        #loginform input[type="checkbox"] {
            width: 16px !important;
            height: 16px !important;
            accent-color: #1a3a5c !important;
        }

        /* ── Submit row ── */
        #loginform .submit {
            margin-top: 24px !important;
            padding: 0 !important;
        }

        /* ── Submit button ── */
        #loginform .button-primary,
        #lostpasswordform .button-primary,
        input#wp-submit {
            background: linear-gradient(135deg, #1e4080 0%, #2563a8 100%) !important;
            border: none !important;
            border-radius: 12px !important;
            padding: 14px 20px !important;
            font-size: 15px !important;
            font-weight: 700 !important;
            letter-spacing: 0.2px !important;
            width: 100% !important;
            height: auto !important;
            text-shadow: none !important;
            box-shadow: 0 4px 16px rgba(26,58,92,0.4), 0 1px 0 rgba(255,255,255,0.15) inset !important;
            transition: transform 0.15s, box-shadow 0.15s !important;
            cursor: pointer !important;
            color: #fff !important;
        }
        #loginform .button-primary:hover,
        input#wp-submit:hover {
            background: linear-gradient(135deg, #163366 0%, #1a4f8a 100%) !important;
            box-shadow: 0 8px 24px rgba(26,58,92,0.5) !important;
            transform: translateY(-1px) !important;
        }
        #loginform .button-primary:active,
        input#wp-submit:active {
            transform: translateY(0) !important;
            box-shadow: 0 2px 8px rgba(26,58,92,0.4) !important;
        }

        /* ── Divider above remember me ── */
        #loginform .forgetmenot {
            border-top: 1px solid #f3f4f6 !important;
            padding-top: 16px !important;
            margin-top: 4px !important;
        }

        /* ── Links below form ── */
        #nav, #backtoblog {
            text-align: center !important;
            padding: 14px 0 0 !important;
        }
        #nav a, #backtoblog a {
            color: rgba(255,255,255,0.7) !important;
            font-size: 13px !important;
            font-weight: 500 !important;
            text-decoration: none !important;
            transition: color 0.2s !important;
        }
        #nav a:hover { color: #fff !important; text-decoration: underline !important; }
        #wpi-forgot-user {
            text-align: center !important;
            padding: 8px 0 0 !important;
        }
        #wpi-forgot-user a {
            color: rgba(255,255,255,0.5) !important;
            font-size: 12px !important;
            text-decoration: none !important;
        }
        #wpi-forgot-user a:hover { color: rgba(255,255,255,0.85) !important; }

        /* ── Error / message ── */
        #login_error, .message, .success {
            border-radius: 12px !important;
            border-left: none !important;
            padding: 12px 16px !important;
            margin-bottom: 16px !important;
            font-size: 13px !important;
        }
        #login_error {
            background: #fef2f2 !important;
            color: #dc2626 !important;
            border: 1.5px solid #fecaca !important;
        }
        .message {
            background: #eff6ff !important;
            color: #1d4ed8 !important;
            border: 1.5px solid #bfdbfe !important;
        }

        /* ── Password toggle ── */
        .wp-pwd button.button {
            border: none !important;
            box-shadow: none !important;
            background: transparent !important;
        }
        .wp-pwd {
            position: relative !important;
        }

        /* ── Footer version tag ── */
        #login_footer {
            margin-top: 28px !important;
            text-align: center !important;
            color: rgba(255,255,255,0.2) !important;
            font-size: 11px !important;
            letter-spacing: 0.5px !important;
        }

        /* ── Hide back to blog ── */
        #backtoblog { display: none !important; }

        /* ── Privacy policy ── */
        .privacy-policy-page-link {
            color: rgba(255,255,255,0.3) !important;
            font-size: 11px !important;
        }

        /* ── Logout confirmation page ── */
        body.login #login > p,
        body.login .message {
            display: none !important;
        }
        body.login #logoutpage,
        body.login .logout-page {
            display: none !important;
        }
        /* Style the logout confirm box */
        body.login #login > p:has(a[href*="action=logout"]),
        body.login div.login > p {
            background: #ffffff !important;
            border-radius: 20px !important;
            padding: 36px 32px !important;
            box-shadow: 0 24px 64px rgba(0,0,0,0.4) !important;
            text-align: center !important;
            color: #374151 !important;
            font-size: 15px !important;
            line-height: 1.6 !important;
            border: none !important;
        }
        /* Logout page confirmation wrapper */
        body.login #login {
            text-align: center !important;
        }
        /* Style the "log out" confirm link as a button */
        body.login #login a[href*="action=logout"] {
            display: inline-block !important;
            background: #1a3a5c !important;
            color: #ffffff !important;
            padding: 12px 32px !important;
            border-radius: 10px !important;
            font-weight: 700 !important;
            font-size: 14px !important;
            text-decoration: none !important;
            margin-top: 8px !important;
            transition: background 0.2s !important;
        }
        body.login #login a[href*="action=logout"]:hover {
            background: #0d2137 !important;
        }
        </style>
        <?php
        // Inject a cleaner logout page via JS
        ?>
        <script>
        document.addEventListener('DOMContentLoaded', function(){
            var loginDiv = document.getElementById('login');
            if (!loginDiv) return;
            var allP = loginDiv.querySelectorAll('p');
            // Check if this is the logout confirm page
            allP.forEach(function(p){
                var text = p.textContent || '';
                if (text.indexOf('log out') >= 0 || text.indexOf('attempting') >= 0) {
                    // Replace with a clean styled card
                    var logoutLink = p.querySelector('a');
                    var href = logoutLink ? logoutLink.href : '#';
                    var card = document.createElement('div');
                    card.style.cssText = 'background:#fff;border-radius:20px;padding:40px 32px;box-shadow:0 24px 64px rgba(0,0,0,.4);text-align:center;';
                    card.innerHTML = '<div style="font-size:48px;margin-bottom:16px;">👋</div>'
                        + '<h2 style="margin:0 0 8px;font-size:20px;font-weight:800;color:#111827;">Sign Out</h2>'
                        + '<p style="margin:0 0 24px;color:#6b7280;font-size:14px;">Are you sure you want to sign out of Audit4me?</p>'
                        + '<div style="display:flex;gap:12px;justify-content:center;">'
                        + '<a href="' + href + '" style="display:inline-block;background:#1a3a5c;color:#fff;padding:12px 28px;border-radius:10px;font-weight:700;font-size:14px;text-decoration:none;">Yes, Sign Out</a>'
                        + '<a href="javascript:history.back()" style="display:inline-block;background:#f3f4f6;color:#374151;padding:12px 28px;border-radius:10px;font-weight:700;font-size:14px;text-decoration:none;">Cancel</a>'
                        + '</div>';
                    p.replaceWith(card);
                }
            });
        });
        </script>
        <?php
    }

    public function wpi_login_footer() {
        ?>
        <div id="login_footer">Audit4me &nbsp;·&nbsp; v<?php echo WPI_VERSION; ?></div>
        <script>
        var backLink = document.querySelector('#backtoblog');
        if (backLink) backLink.style.display = 'none';
        // Vertically center the login wrapper on tall screens
        var wrap = document.querySelector('#login');
        if (wrap) {
            wrap.style.marginTop = '0';
            wrap.style.paddingTop = '0';
        }
        document.body.style.justifyContent = 'center';
        // Inject "Forgot username?" link below #nav on login page
        var nav = document.querySelector('#nav');
        if (nav && document.querySelector('#loginform')) {
            var fu = document.createElement('div');
            fu.id = 'wpi-forgot-user';
            fu.innerHTML = '<a href="<?php echo esc_js( home_url("/?wpi=1&wpi_forgot_user=1") ); ?>">Forgot your username?</a>';
            nav.parentNode.insertBefore(fu, nav.nextSibling);
        }
        // Add "Back to Sign In" on non-login pages (lostpassword, resetpass)
        if (!document.querySelector('#loginform')) {
            var backDiv = document.createElement('div');
            backDiv.style.cssText = 'text-align:center;margin-top:16px;';
            backDiv.innerHTML = '<a href="<?php echo esc_js(home_url('/?wpi=1')); ?>" style="color:rgba(255,255,255,0.75);font-size:13px;text-decoration:none;font-family:-apple-system,sans-serif;">← Back to Sign In</a>';
            var loginWrap = document.querySelector('#login');
            if (loginWrap) loginWrap.appendChild(backDiv);
        }
        </script>
        <?php
    }

    public function render_app() {
        echo '<div id="wp-inspector-root"></div>';
    }

    public static function is_system_owner( $uid = null ) {
        if ( ! $uid ) $uid = get_current_user_id();
        // System owner = user ID stored in option, defaults to first admin
        $owner_id = (int) get_option( 'wpi_system_owner_id', 0 );
        if ( ! $owner_id ) {
            return false; // Owner must be explicitly set via plugin activation
        }
        return (int)$uid === $owner_id;
    }

    public static function get_user_org_id( $uid = null ) {
        if ( ! $uid ) $uid = get_current_user_id();
        if ( self::is_system_owner( $uid ) ) return 0; // system owner has no org
        global $wpdb;
        // Prefer the user's own personal organisation when they have purchased
        // an individual subscription. This prevents a paid individual account from
        // becoming an administrator across an organisation they were merely invited
        // to. Invited-organisation templates should be accessed only when shared.
        $owned_org_id = $wpdb->get_var( $wpdb->prepare(
            "SELECT ou.org_id
             FROM {$wpdb->prefix}wpi_org_users ou
             JOIN {$wpdb->prefix}wpi_organisations o ON o.id = ou.org_id
             WHERE ou.user_id=%d AND o.owner_id=%d
             ORDER BY ou.org_id ASC LIMIT 1", $uid, $uid
        ) );
        if ( $owned_org_id ) return (int) $owned_org_id;

        $org_id = $wpdb->get_var( $wpdb->prepare(
            "SELECT org_id FROM {$wpdb->prefix}wpi_org_users WHERE user_id=%d ORDER BY org_id ASC LIMIT 1", $uid
        ) );
        return $org_id ? (int)$org_id : 0;
    }
}

