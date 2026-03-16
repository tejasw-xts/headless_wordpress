<?php
namespace SBLF\API;

if (!defined('ABSPATH')) exit;

class Routes {
    public function __construct() {
        add_action('rest_api_init', [$this, 'register_routes']);
    }
    
    public function register_routes() {
        register_rest_route('sblf/v1', '/links', [
            'methods' => 'GET',
            'callback' => [$this, 'get_links'],
            'permission_callback' => [$this, 'check_permission']
        ]);
        
        register_rest_route('sblf/v1', '/links/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_link'],
            'permission_callback' => [$this, 'check_permission']
        ]);
        
        register_rest_route('sblf/v1', '/links/(?P<id>\d+)', [
            'methods' => 'DELETE',
            'callback' => [$this, 'delete_link'],
            'permission_callback' => [$this, 'check_permission']
        ]);
        
        register_rest_route('sblf/v1', '/stats', [
            'methods' => 'GET',
            'callback' => [$this, 'get_stats'],
            'permission_callback' => [$this, 'check_permission']
        ]);
    }
    
    public function check_permission() {
        return current_user_can('manage_options');
    }
    
    public function get_links($request) {
        global $wpdb;
        
        $page = $request->get_param('page') ?? 1;
        $per_page = $request->get_param('per_page') ?? 20;
        $status = $request->get_param('status') ?? '';
        
        $offset = ($page - 1) * $per_page;
        
        $where = '';
        if ($status) {
            $where = $wpdb->prepare(' WHERE status = %s', $status);
        }
        
        $links = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}sblf_wordpress_headlesss $where ORDER BY created_at DESC LIMIT %d OFFSET %d",
            $per_page,
            $offset
        ));
        
        $total = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sblf_wordpress_headlesss $where");
        
        return rest_ensure_response([
            'links' => $links,
            'total' => (int)$total,
            'pages' => ceil($total / $per_page)
        ]);
    }
    
    public function get_link($request) {
        global $wpdb;
        
        $id = $request->get_param('id');
        
        $link = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}sblf_wordpress_headlesss WHERE id = %d",
            $id
        ));
        
        if (!$link) {
            return new \WP_Error('not_found', 'Link not found', ['status' => 404]);
        }
        
        return rest_ensure_response($link);
    }
    
    public function delete_link($request) {
        global $wpdb;
        
        $id = $request->get_param('id');
        
        $deleted = $wpdb->delete(
            $wpdb->prefix . 'sblf_wordpress_headlesss',
            ['id' => $id],
            ['%d']
        );
        
        if ($deleted) {
            return rest_ensure_response(['message' => 'Link deleted successfully']);
        }
        
        return new \WP_Error('delete_failed', 'Failed to delete link', ['status' => 500]);
    }
    
    public function get_stats() {
        global $wpdb;
        
        $total = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sblf_wordpress_headlesss");
        $pending = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sblf_wordpress_headlesss WHERE status = 'pending'");
        $fixed = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sblf_wordpress_headlesss WHERE status = 'fixed'");
        $ignored = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sblf_wordpress_headlesss WHERE status = 'ignored'");
        
        return rest_ensure_response([
            'total' => (int)$total,
            'pending' => (int)$pending,
            'fixed' => (int)$fixed,
            'ignored' => (int)$ignored
        ]);
    }
}
