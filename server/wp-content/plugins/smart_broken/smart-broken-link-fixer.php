<?php
/**
 * Plugin Name: Smart Broken Link Fixer
 * Plugin URI: https://github.com/yourusername/smart-broken-link-fixer
 * Description: AI-powered broken link detection and automatic fixing with smart redirects and suggestions.
 * Version: 1.0.0
 * Author: Tejas Waghmode
 * Author URI: https://muktamedia.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: smart-broken-link-fixer
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH'))
    exit;

define('SBLF_VERSION', '1.0.0');
define('SBLF_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SBLF_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SBLF_PLUGIN_BASENAME', plugin_basename(__FILE__));

spl_autoload_register(function ($class) {
    $prefix   = 'SBLF\\';
    $base_dir = SBLF_PLUGIN_DIR . 'includes/';
    $len      = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0)
        return;

    $relative_class = substr($class, $len);
    $parts          = explode('\\', $relative_class);

    if (count($parts) > 1)
    {
        $class_name = array_pop($parts);
        $sub_path   = strtolower(implode('/', $parts)) . '/';
        $file       = $base_dir . $sub_path . 'class-' . strtolower(str_replace('_', '-', $class_name)) . '.php';
    } else
    {
        $file = $base_dir . 'class-' . strtolower(str_replace('_', '-', $relative_class)) . '.php';
    }

    if (file_exists($file))
        require $file;
});

final class Smart_wordpress_headless_Fixer
{
    private static $instance = null;

    public static function instance()
    {
        if (is_null(self::$instance))
        {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);
        add_action('plugins_loaded', [$this, 'init']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }

    public function activate()
    {
        require_once SBLF_PLUGIN_DIR . 'includes/class-activator.php';
        SBLF\Activator::activate();
    }

    public function deactivate()
    {
        require_once SBLF_PLUGIN_DIR . 'includes/class-deactivator.php';
        SBLF\Deactivator::deactivate();
    }

    public function init()
    {
        load_plugin_textdomain('smart-broken-link-fixer', false, dirname(SBLF_PLUGIN_BASENAME) . '/languages');
        new SBLF\Admin\Menu();
        new SBLF\Core\Scanner();
        new SBLF\Core\Redirector();
        new SBLF\Core\Notifier();
        new SBLF\API\Routes();
    }

    public function enqueue_admin_assets($hook)
    {
        if (strpos($hook, 'smart-broken-link-fixer') === false && strpos($hook, 'sblf-') === false)
            return;

        wp_enqueue_style('bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css', [], '5.3.0');
        wp_enqueue_style('sblf-admin', SBLF_PLUGIN_URL . 'assets/css/admin.css', ['bootstrap'], SBLF_VERSION);
        wp_enqueue_script('bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js', ['jquery'], '5.3.0', true);
        wp_enqueue_script('sblf-admin', SBLF_PLUGIN_URL . 'assets/js/admin.js', ['jquery', 'bootstrap'], SBLF_VERSION, true);

        wp_localize_script('sblf-admin', 'sblf_ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('sblf_nonce'),
            'rest_url' => rest_url('sblf/v1/'),
            'rest_nonce' => wp_create_nonce('wp_rest')
        ]);
    }
}

function sblf()
{
    return Smart_wordpress_headless_Fixer::instance();
}

sblf();
