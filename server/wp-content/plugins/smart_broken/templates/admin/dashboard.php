<?php
if (!defined('ABSPATH')) exit;

global $wpdb;

$stats = [
    'total' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sblf_wordpress_headlesss"),
    'pending' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sblf_wordpress_headlesss WHERE status = 'pending'"),
    'fixed' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sblf_wordpress_headlesss WHERE status = 'fixed'"),
    'ignored' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sblf_wordpress_headlesss WHERE status = 'ignored'")
];

$recent_scans = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}sblf_scan_history ORDER BY started_at DESC LIMIT 5");
?>

<div class="wrap sblf-dashboard">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-admin-links"></span>
        <?php _e('Smart Broken Link Fixer Dashboard', 'smart-broken-link-fixer'); ?>
    </h1>
    
    <div class="sblf-actions mt-3 mb-4">
        <button id="sblf-start-scan" class="btn btn-primary">
            <span class="dashicons dashicons-update"></span>
            <?php _e('Start New Scan', 'smart-broken-link-fixer'); ?>
        </button>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card text-white bg-primary">
                <div class="card-body">
                    <h5 class="card-title"><?php _e('Total Broken Links', 'smart-broken-link-fixer'); ?></h5>
                    <h2 class="display-4"><?php echo esc_html($stats['total']); ?></h2>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card text-white bg-warning">
                <div class="card-body">
                    <h5 class="card-title"><?php _e('Pending', 'smart-broken-link-fixer'); ?></h5>
                    <h2 class="display-4"><?php echo esc_html($stats['pending']); ?></h2>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card text-white bg-success">
                <div class="card-body">
                    <h5 class="card-title"><?php _e('Fixed', 'smart-broken-link-fixer'); ?></h5>
                    <h2 class="display-4"><?php echo esc_html($stats['fixed']); ?></h2>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card text-white bg-secondary">
                <div class="card-body">
                    <h5 class="card-title"><?php _e('Ignored', 'smart-broken-link-fixer'); ?></h5>
                    <h2 class="display-4"><?php echo esc_html($stats['ignored']); ?></h2>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3><?php _e('Recent Scans', 'smart-broken-link-fixer'); ?></h3>
        </div>
        <div class="card-body">
            <?php if (!empty($recent_scans)): ?>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th><?php _e('Scan Type', 'smart-broken-link-fixer'); ?></th>
                            <th><?php _e('Total Links', 'smart-broken-link-fixer'); ?></th>
                            <th><?php _e('Broken Links', 'smart-broken-link-fixer'); ?></th>
                            <th><?php _e('Status', 'smart-broken-link-fixer'); ?></th>
                            <th><?php _e('Started At', 'smart-broken-link-fixer'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_scans as $scan): ?>
                            <tr>
                                <td><span class="badge bg-info"><?php echo esc_html(ucfirst($scan->scan_type)); ?></span></td>
                                <td><?php echo esc_html($scan->total_links); ?></td>
                                <td><?php echo esc_html($scan->wordpress_headlesss); ?></td>
                                <td>
                                    <?php if ($scan->status === 'completed'): ?>
                                        <span class="badge bg-success"><?php _e('Completed', 'smart-broken-link-fixer'); ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-warning"><?php _e('Running', 'smart-broken-link-fixer'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html($scan->started_at); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="text-muted"><?php _e('No scans found. Start your first scan!', 'smart-broken-link-fixer'); ?></p>
            <?php endif; ?>
        </div>
    </div>
</div>
