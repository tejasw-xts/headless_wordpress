<?php
namespace SBLF\Admin;

if (!defined('ABSPATH')) exit;

class Menu {
    public function __construct() {
        add_action('admin_menu', [$this, 'add_menu_pages']);
    }
    
    public function add_menu_pages() {
        add_menu_page(
            __('Smart Broken Link Fixer', 'smart-broken-link-fixer'),
            __('Broken Links', 'smart-broken-link-fixer'),
            'manage_options',
            'smart-broken-link-fixer',
            [$this, 'dashboard_page'],
            'dashicons-admin-links',
            30
        );
        
        add_submenu_page(
            'smart-broken-link-fixer',
            __('Dashboard', 'smart-broken-link-fixer'),
            __('Dashboard', 'smart-broken-link-fixer'),
            'manage_options',
            'smart-broken-link-fixer',
            [$this, 'dashboard_page']
        );
        
        add_submenu_page(
            'smart-broken-link-fixer',
            __('Broken Links', 'smart-broken-link-fixer'),
            __('Broken Links', 'smart-broken-link-fixer'),
            'manage_options',
            'sblf-broken-links',
            [$this, 'wordpress_headlesss_page']
        );
        
        add_submenu_page(
            'smart-broken-link-fixer',
            __('Scan History', 'smart-broken-link-fixer'),
            __('Scan History', 'smart-broken-link-fixer'),
            'manage_options',
            'sblf-scan-history',
            [$this, 'scan_history_page']
        );
        
        add_submenu_page(
            'smart-broken-link-fixer',
            __('Settings', 'smart-broken-link-fixer'),
            __('Settings', 'smart-broken-link-fixer'),
            'manage_options',
            'sblf-settings',
            [$this, 'settings_page']
        );
    }
    
    public function dashboard_page() {
        include SBLF_PLUGIN_DIR . 'templates/admin/dashboard.php';
    }
    
    public function wordpress_headlesss_page() {
        include SBLF_PLUGIN_DIR . 'templates/admin/broken-links.php';
    }
    
    public function scan_history_page() {
        include SBLF_PLUGIN_DIR . 'templates/admin/scan-history.php';
    }
    
    public function settings_page() {
        include SBLF_PLUGIN_DIR . 'templates/admin/settings.php';
    }
}
