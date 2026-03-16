<?php
namespace SBLF\Core;

if (!defined('ABSPATH')) exit;

class Notifier {
    private $settings;
    
    public function __construct() {
        $this->settings = get_option('sblf_settings', []);
        add_action('sblf_send_notification', [$this, 'send_notification'], 10, 2);
    }
    
    public function send_notification($scan_id, $broken_count) {
        if (empty($this->settings['notification_email'])) return;
        
        $to = $this->settings['notification_email'];
        $subject = sprintf(__('[%s] Broken Links Detected', 'smart-broken-link-fixer'), get_bloginfo('name'));
        
        $message = $this->get_email_template($scan_id, $broken_count);
        
        $headers = ['Content-Type: text/html; charset=UTF-8'];
        
        wp_mail($to, $subject, $message, $headers);
        
        global $wpdb;
        $wpdb->query($wpdb->prepare(
            "UPDATE {$wpdb->prefix}sblf_wordpress_headlesss SET notified = 1 
            WHERE id IN (SELECT id FROM (SELECT id FROM {$wpdb->prefix}sblf_wordpress_headlesss WHERE notified = 0 LIMIT %d) AS tmp)",
            $broken_count
        ));
    }
    
    private function get_email_template($scan_id, $broken_count) {
        $admin_url = admin_url('admin.php?page=sblf-broken-links');
        
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #0073aa; color: white; padding: 20px; text-align: center; }
                .content { background: #f9f9f9; padding: 20px; }
                .button { display: inline-block; padding: 10px 20px; background: #0073aa; color: white; text-decoration: none; border-radius: 5px; }
                .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1><?php _e('Broken Links Detected', 'smart-broken-link-fixer'); ?></h1>
                </div>
                <div class="content">
                    <p><?php printf(__('Hello, we found %d broken links on your website.', 'smart-broken-link-fixer'), $broken_count); ?></p>
                    <p><?php _e('Please review and fix these links to maintain your website\'s quality and SEO.', 'smart-broken-link-fixer'); ?></p>
                    <p style="text-align: center; margin: 30px 0;">
                        <a href="<?php echo esc_url($admin_url); ?>" class="button">
                            <?php _e('View Broken Links', 'smart-broken-link-fixer'); ?>
                        </a>
                    </p>
                </div>
                <div class="footer">
                    <p><?php printf(__('This email was sent by Smart Broken Link Fixer plugin on %s', 'smart-broken-link-fixer'), get_bloginfo('name')); ?></p>
                </div>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
}
