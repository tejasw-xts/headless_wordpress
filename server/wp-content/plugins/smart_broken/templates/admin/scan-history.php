<?php
if (!defined('ABSPATH')) exit;

global $wpdb;

$scans = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}sblf_scan_history ORDER BY started_at DESC LIMIT 50");
?>

<div class="wrap sblf-scan-history">
    <h1 class="wp-heading-inline">
        <?php _e('Scan History', 'smart-broken-link-fixer'); ?>
    </h1>

    <div class="card mt-4">
        <div class="card-header">
            <h3><?php _e('All Scans', 'smart-broken-link-fixer'); ?></h3>
        </div>
        <div class="card-body">
            <?php if (!empty($scans)): ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th><?php _e('ID', 'smart-broken-link-fixer'); ?></th>
                                <th><?php _e('Scan Type', 'smart-broken-link-fixer'); ?></th>
                                <th><?php _e('Total Links', 'smart-broken-link-fixer'); ?></th>
                                <th><?php _e('Broken Links', 'smart-broken-link-fixer'); ?></th>
                                <th><?php _e('Fixed Links', 'smart-broken-link-fixer'); ?></th>
                                <th><?php _e('Status', 'smart-broken-link-fixer'); ?></th>
                                <th><?php _e('Started At', 'smart-broken-link-fixer'); ?></th>
                                <th><?php _e('Completed At', 'smart-broken-link-fixer'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($scans as $scan): ?>
                                <tr>
                                    <td><?php echo esc_html($scan->id); ?></td>
                                    <td><span class="badge bg-info"><?php echo esc_html(ucfirst($scan->scan_type)); ?></span></td>
                                    <td><?php echo esc_html($scan->total_links); ?></td>
                                    <td><?php echo esc_html($scan->wordpress_headlesss); ?></td>
                                    <td><?php echo esc_html($scan->fixed_links); ?></td>
                                    <td>
                                        <?php if ($scan->status === 'completed'): ?>
                                            <span class="badge bg-success"><?php _e('Completed', 'smart-broken-link-fixer'); ?></span>
                                        <?php elseif ($scan->status === 'running'): ?>
                                            <span class="badge bg-warning"><?php _e('Running', 'smart-broken-link-fixer'); ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-danger"><?php _e('Failed', 'smart-broken-link-fixer'); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo esc_html($scan->started_at); ?></td>
                                    <td><?php echo esc_html($scan->completed_at ?? '-'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    <?php _e('No scan history available.', 'smart-broken-link-fixer'); ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
