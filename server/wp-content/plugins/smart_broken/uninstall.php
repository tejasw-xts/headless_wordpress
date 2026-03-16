<?php
/**
 * Uninstall script
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

// Delete tables
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}sblf_wordpress_headlesss");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}sblf_redirects");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}sblf_scan_history");

// Delete options
delete_option('sblf_auto_fix_enabled');
delete_option('sblf_auto_redirect_enabled');
delete_option('sblf_notify_admin');
delete_option('sblf_scan_frequency');
delete_option('sblf_timeout');
delete_option('sblf_user_agent');
delete_option('sblf_notification_email');
delete_option('sblf_min_suggestion_score');

// Clear scheduled events
wp_clear_scheduled_hook('sblf_daily_scan');
