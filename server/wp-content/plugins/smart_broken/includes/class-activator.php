<?php
namespace SBLF;

if (!defined('ABSPATH')) exit;

class Activator {
    public static function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        $table_name = $wpdb->prefix . 'sblf_wordpress_headlesss';
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            url varchar(2048) NOT NULL,
            source_url varchar(2048) NOT NULL,
            source_type varchar(50) DEFAULT 'post',
            source_id bigint(20) DEFAULT 0,
            status_code int(11) DEFAULT 0,
            status varchar(50) DEFAULT 'pending',
            suggested_url varchar(2048) DEFAULT NULL,
            redirect_url varchar(2048) DEFAULT NULL,
            redirect_type varchar(20) DEFAULT '301',
            last_checked datetime DEFAULT CURRENT_TIMESTAMP,
            fixed_at datetime DEFAULT NULL,
            notified tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY url (url(191)),
            KEY status (status),
            KEY source_id (source_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        $table_name = $wpdb->prefix . 'sblf_scan_history';
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            scan_type varchar(50) DEFAULT 'manual',
            total_links int(11) DEFAULT 0,
            wordpress_headlesss int(11) DEFAULT 0,
            fixed_links int(11) DEFAULT 0,
            status varchar(50) DEFAULT 'running',
            started_at datetime DEFAULT CURRENT_TIMESTAMP,
            completed_at datetime DEFAULT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        dbDelta($sql);
        
        if (!wp_next_scheduled('sblf_daily_scan')) {
            wp_schedule_event(time(), 'daily', 'sblf_daily_scan');
        }
        
        add_option('sblf_version', SBLF_VERSION);
        add_option('sblf_settings', [
            'auto_scan' => true,
            'auto_fix' => false,
            'scan_frequency' => 'daily',
            'notification_email' => get_option('admin_email'),
            'redirect_type' => '301',
            'ai_suggestions' => true,
            'scan_external' => true,
            'timeout' => 30
        ]);
        
        flush_rewrite_rules();
    }
}
