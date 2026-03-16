<?php
namespace SBLF;

if (!defined('ABSPATH')) exit;

class Deactivator {
    public static function deactivate() {
        wp_clear_scheduled_hook('sblf_daily_scan');
        flush_rewrite_rules();
    }
}
