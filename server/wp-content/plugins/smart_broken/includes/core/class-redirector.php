<?php
namespace SBLF\Core;

if (!defined('ABSPATH')) exit;

class Redirector {
    public function __construct() {
        add_action('template_redirect', [$this, 'handle_redirects'], 1);
        add_action('wp_ajax_sblf_apply_redirect', [$this, 'ajax_apply_redirect']);
        add_action('wp_ajax_sblf_auto_fix', [$this, 'ajax_auto_fix']);
        add_action('wp_ajax_sblf_ignore_link', [$this, 'ajax_ignore_link']);
    }
    
    public function handle_redirects() {
        global $wpdb;
        
        $current_url = home_url($_SERVER['REQUEST_URI']);
        
        $redirect = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}sblf_wordpress_headlesss 
            WHERE url = %s AND redirect_url IS NOT NULL AND status = 'fixed'",
            $current_url
        ));
        
        if ($redirect && !empty($redirect->redirect_url)) {
            $redirect_type = $redirect->redirect_type === '302' ? 302 : 301;
            wp_redirect($redirect->redirect_url, $redirect_type);
            exit;
        }
    }
    
    public function ajax_apply_redirect() {
        check_ajax_referer('sblf_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }
        
        global $wpdb;
        
        $link_id = intval($_POST['link_id'] ?? 0);
        $redirect_url = sanitize_text_field($_POST['redirect_url'] ?? '');
        $redirect_type = sanitize_text_field($_POST['redirect_type'] ?? '301');
        
        if (!$link_id || !$redirect_url) {
            wp_send_json_error(['message' => 'Invalid data']);
        }
        
        $updated = $wpdb->update(
            $wpdb->prefix . 'sblf_wordpress_headlesss',
            [
                'redirect_url' => $redirect_url,
                'redirect_type' => $redirect_type,
                'status' => 'fixed',
                'fixed_at' => current_time('mysql')
            ],
            ['id' => $link_id],
            ['%s', '%s', '%s', '%s'],
            ['%d']
        );
        
        if ($updated) {
            wp_send_json_success(['message' => 'Redirect applied successfully']);
        } else {
            wp_send_json_error(['message' => 'Failed to apply redirect']);
        }
    }
    
    public function ajax_ignore_link() {
        check_ajax_referer('sblf_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }
        
        global $wpdb;
        $link_id = intval($_POST['link_id'] ?? 0);
        
        $updated = $wpdb->update(
            $wpdb->prefix . 'sblf_wordpress_headlesss',
            ['status' => 'ignored'],
            ['id' => $link_id],
            ['%s'],
            ['%d']
        );
        
        if ($updated) {
            wp_send_json_success(['message' => 'Link ignored']);
        } else {
            wp_send_json_error(['message' => 'Failed to ignore link']);
        }
    }
    
    public function ajax_auto_fix() {
        check_ajax_referer('sblf_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }
        
        $link_id = intval($_POST['link_id'] ?? 0);
        
        if (!$link_id) {
            wp_send_json_error(['message' => 'Invalid link ID']);
        }
        
        global $wpdb;
        $link = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}sblf_wordpress_headlesss WHERE id = %d",
            $link_id
        ));
        
        if (!$link) {
            wp_send_json_error(['message' => 'Link not found']);
        }
        
        $suggestion = $this->generate_ai_suggestion($link);
        
        if ($suggestion) {
            $wpdb->update(
                $wpdb->prefix . 'sblf_wordpress_headlesss',
                ['suggested_url' => $suggestion],
                ['id' => $link_id],
                ['%s'],
                ['%d']
            );
            
            wp_send_json_success([
                'message' => 'AI suggestion generated',
                'suggested_url' => $suggestion
            ]);
        } else {
            wp_send_json_error(['message' => 'Could not generate suggestion']);
        }
    }
    
    private function generate_ai_suggestion($link) {
        $broken_url = $link->url;
        $path = parse_url($broken_url, PHP_URL_PATH);
        
        if (!$path) return null;
        
        $slug = basename($path);
        $slug = preg_replace('/\.(html|htm|php)$/', '', $slug);
        
        $posts = get_posts([
            'name' => $slug,
            'post_type' => 'any',
            'post_status' => 'publish',
            'numberposts' => 1
        ]);
        
        if (!empty($posts)) {
            return get_permalink($posts[0]->ID);
        }
        
        $search_query = str_replace(['-', '_'], ' ', $slug);
        $search_posts = get_posts([
            's' => $search_query,
            'post_type' => 'any',
            'post_status' => 'publish',
            'numberposts' => 1
        ]);
        
        if (!empty($search_posts)) {
            return get_permalink($search_posts[0]->ID);
        }
        
        return home_url();
    }
}
