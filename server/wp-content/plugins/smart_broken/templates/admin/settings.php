<?php
if (!defined('ABSPATH')) exit;

if (isset($_POST['sblf_save_settings'])) {
    check_admin_referer('sblf_settings_nonce');
    
    $settings = [
        'auto_scan' => isset($_POST['auto_scan']),
        'auto_fix' => isset($_POST['auto_fix']),
        'scan_frequency' => sanitize_text_field($_POST['scan_frequency'] ?? 'daily'),
        'notification_email' => sanitize_email($_POST['notification_email'] ?? ''),
        'redirect_type' => sanitize_text_field($_POST['redirect_type'] ?? '301'),
        'ai_suggestions' => isset($_POST['ai_suggestions']),
        'scan_external' => isset($_POST['scan_external']),
        'timeout' => intval($_POST['timeout'] ?? 30)
    ];
    
    update_option('sblf_settings', $settings);
    echo '<div class="alert alert-success">' . __('Settings saved successfully!', 'smart-broken-link-fixer') . '</div>';
}

$settings = get_option('sblf_settings', []);
?>

<div class="wrap sblf-settings">
    <h1><?php _e('Settings', 'smart-broken-link-fixer'); ?></h1>
    
    <div class="card mt-4">
        <div class="card-body">
            <form method="post" action="">
                <?php wp_nonce_field('sblf_settings_nonce'); ?>
                
                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="auto_scan" name="auto_scan" <?php checked(!empty($settings['auto_scan'])); ?>>
                        <label class="form-check-label" for="auto_scan">
                            <?php _e('Enable Automatic Scanning', 'smart-broken-link-fixer'); ?>
                        </label>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label"><?php _e('Scan Frequency', 'smart-broken-link-fixer'); ?></label>
                    <select class="form-select" name="scan_frequency">
                        <option value="daily" <?php selected($settings['scan_frequency'] ?? 'daily', 'daily'); ?>><?php _e('Daily', 'smart-broken-link-fixer'); ?></option>
                        <option value="weekly" <?php selected($settings['scan_frequency'] ?? 'daily', 'weekly'); ?>><?php _e('Weekly', 'smart-broken-link-fixer'); ?></option>
                    </select>
                </div>
                
                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="auto_fix" name="auto_fix" <?php checked(!empty($settings['auto_fix'])); ?>>
                        <label class="form-check-label" for="auto_fix">
                            <?php _e('Enable Auto-Fix with AI Suggestions', 'smart-broken-link-fixer'); ?>
                        </label>
                    </div>
                </div>
                
                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="ai_suggestions" name="ai_suggestions" <?php checked(!empty($settings['ai_suggestions'])); ?>>
                        <label class="form-check-label" for="ai_suggestions">
                            <?php _e('Enable AI Suggestions', 'smart-broken-link-fixer'); ?>
                        </label>
                    </div>
                </div>
                
                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="scan_external" name="scan_external" <?php checked(!empty($settings['scan_external'])); ?>>
                        <label class="form-check-label" for="scan_external">
                            <?php _e('Scan External Links', 'smart-broken-link-fixer'); ?>
                        </label>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label"><?php _e('Notification Email', 'smart-broken-link-fixer'); ?></label>
                    <input type="email" class="form-control" name="notification_email" value="<?php echo esc_attr($settings['notification_email'] ?? get_option('admin_email')); ?>">
                </div>
                
                <div class="mb-3">
                    <label class="form-label"><?php _e('Default Redirect Type', 'smart-broken-link-fixer'); ?></label>
                    <select class="form-select" name="redirect_type">
                        <option value="301" <?php selected($settings['redirect_type'] ?? '301', '301'); ?>><?php _e('301 Permanent', 'smart-broken-link-fixer'); ?></option>
                        <option value="302" <?php selected($settings['redirect_type'] ?? '301', '302'); ?>><?php _e('302 Temporary', 'smart-broken-link-fixer'); ?></option>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label class="form-label"><?php _e('Request Timeout (seconds)', 'smart-broken-link-fixer'); ?></label>
                    <input type="number" class="form-control" name="timeout" value="<?php echo esc_attr($settings['timeout'] ?? 30); ?>" min="5" max="120">
                </div>
                
                <button type="submit" name="sblf_save_settings" class="btn btn-primary">
                    <?php _e('Save Settings', 'smart-broken-link-fixer'); ?>
                </button>
            </form>
        </div>
    </div>
</div>
