<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WPI_Deactivator {
    public static function deactivate() {
        flush_rewrite_rules();
    }
}
