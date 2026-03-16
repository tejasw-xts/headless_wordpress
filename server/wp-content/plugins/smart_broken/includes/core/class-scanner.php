<?php
namespace SBLF\Core;

if (!defined('ABSPATH')) exit;

class Scanner {
    private $settings;
    
    public function __construct() {
        $this->settings = get_option('sblf_settings', []);
        add_action('sblf_daily_scan', [$this, 'run_scheduled_scan']);
        add_action('sblf_process_scan', [$this, 'process_scan']);
        add_action('wp_ajax_sblf_start_scan', [$this, 'ajax_start_scan']);
        add_action('wp_ajax_sblf_scan_progress', [$this, 'ajax_scan_progress']);
    }
    
    public function ajax_start_scan() {
        check_ajax_referer('sblf_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }
        
        $scan_id = $this->start_scan('manual');
        wp_send_json_success(['scan_id' => $scan_id, 'message' => 'Scan started']);
    }
    
    public function ajax_scan_progress() {
        check_ajax_referer('sblf_nonce', 'nonce');
        
        global $wpdb;
        $scan_id = intval($_POST['scan_id'] ?? 0);
        
        $scan = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}sblf_scan_history WHERE id = %d",
            $scan_id
        ));
        
        wp_send_json_success(['scan' => $scan]);
    }
    
    public function start_scan($type = 'manual') {
        global $wpdb;
        
        $wpdb->insert(
            $wpdb->prefix . 'sblf_scan_history',
            ['scan_type' => $type, 'status' => 'running'],
            ['%s', '%s']
        );
        
        $scan_id = $wpdb->insert_id;
        
        wp_schedule_single_event(time(), 'sblf_process_scan', [$scan_id]);
        
        return $scan_id;
    }
    
    public function run_scheduled_scan() {
        if (!empty($this->settings['auto_scan'])) {
            $this->start_scan('scheduled');
        }
    }
    
    public function process_scan($scan_id) {
        global $wpdb;
        
        $links = $this->extract_all_links();
        $total = count($links);
        $broken = 0;
        
        foreach ($links as $link_data) {
            $status = $this->check_link($link_data['url']);
            
            if ($status['is_broken']) {
                $broken++;
                $this->save_wordpress_headless($link_data, $status);
            }
        }
        
        $wpdb->update(
            $wpdb->prefix . 'sblf_scan_history',
            [
                'total_links' => $total,
                'wordpress_headlesss' => $broken,
                'status' => 'completed',
                'completed_at' => current_time('mysql')
            ],
            ['id' => $scan_id],
            ['%d', '%d', '%s', '%s'],
            ['%d']
        );
        
        if ($broken > 0) {
            do_action('sblf_send_notification', $scan_id, $broken);
        }
    }
    
    private function extract_all_links() {
        $links = [];
        
        $posts = get_posts([
            'post_type' => 'any',
            'post_status' => 'publish',
            'numberposts' => -1
        ]);
        
        foreach ($posts as $post) {
            $content = $post->post_content;
            preg_match_all('/<a\s+(?:[^>]*?\s+)?href="([^"]*)"/i', $content, $matches);
            
            if (!empty($matches[1])) {
                foreach ($matches[1] as $url) {
                    if ($this->should_check_url($url)) {
                        $links[] = [
                            'url' => $url,
                            'source_url' => get_permalink($post->ID),
                            'source_type' => 'post',
                            'source_id' => $post->ID
                        ];
                    }
                }
            }
        }
        
        return $links;
    }
    
    private function should_check_url($url) {
        if (empty($url) || $url === '#') return false;
        if (strpos($url, 'mailto:') === 0) return false;
        if (strpos($url, 'tel:') === 0) return false;
        if (strpos($url, 'javascript:') === 0) return false;
        
        if (empty($this->settings['scan_external']) && strpos($url, home_url()) !== 0) {
            return false;
        }
        
        return true;
    }
    
    private function check_link($url) {
        $timeout = $this->settings['timeout'] ?? 30;
        
        $response = wp_remote_head($url, [
            'timeout' => $timeout,
            'redirection' => 5,
            'sslverify' => false
        ]);
        
        if (is_wp_error($response)) {
            return [
                'is_broken' => true,
                'status_code' => 0,
                'message' => $response->get_error_message()
            ];
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        
        return [
            'is_broken' => $status_code >= 400 || $status_code === 0,
            'status_code' => $status_code,
            'message' => wp_remote_retrieve_response_message($response)
        ];
    }
    
    private function save_wordpress_headless($link_data, $status) {
        global $wpdb;
        
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}sblf_wordpress_headlesss WHERE url = %s AND source_id = %d",
            $link_data['url'],
            $link_data['source_id']
        ));
        
        if ($existing) {
            $wpdb->update(
                $wpdb->prefix . 'sblf_wordpress_headlesss',
                [
                    'status_code' => $status['status_code'],
                    'last_checked' => current_time('mysql')
                ],
                ['id' => $existing],
                ['%d', '%s'],
                ['%d']
            );
        } else {
            $wpdb->insert(
                $wpdb->prefix . 'sblf_wordpress_headlesss',
                array_merge($link_data, [
                    'status_code' => $status['status_code'],
                    'status' => 'pending'
                ]),
                ['%s', '%s', '%s', '%d', '%d', '%s']
            );
        }
    }
}
