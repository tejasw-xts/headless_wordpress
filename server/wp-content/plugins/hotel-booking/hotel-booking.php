<?php
/**
 * Plugin Name: Hotel Booking
 * Description: Booking workflow (status + emails) and a dedicated bookings database table synced with the `booking` custom post type.
 * Version: 1.0.1
 * Author: Hotel Booking
 * Text Domain: hotel-booking
 */

if (!defined('ABSPATH'))
{
    exit;
}

define('HOTEL_BOOKING_BOOKINGS_TABLE_VERSION', '1.0.0');

function hotel_booking_bookings_transient_set_limited($key, $value, $max_len = 4000)
{
    $value = (string) $value;

    if (strlen($value) > $max_len)
    {
        $value = substr($value, -1 * $max_len);
    }

    set_transient($key, $value, 6 * HOUR_IN_SECONDS);
}

function hotel_booking_bookings_transient_set($key, $value)
{
    set_transient($key, $value, 6 * HOUR_IN_SECONDS);
}

function hotel_booking_bookings_table_name()
{
    global $wpdb;
    return $wpdb->prefix . 'hotel_bookings';
}

function hotel_booking_bookings_get_option($key, $default = '')
{
    $value = get_option($key, null);
    return null === $value ? $default : $value;
}

function hotel_booking_bookings_get_defined($name, $default = '')
{
    return defined($name) ? constant($name) : $default;
}

function hotel_booking_bookings_smtp_config()
{
    $host = (string) hotel_booking_bookings_get_defined('HOTEL_BOOKING_SMTP_HOST', '');
    $port = (int) hotel_booking_bookings_get_defined('HOTEL_BOOKING_SMTP_PORT', 0);
    $encryption = (string) hotel_booking_bookings_get_defined('HOTEL_BOOKING_SMTP_ENCRYPTION', '');
    $username = (string) hotel_booking_bookings_get_defined('HOTEL_BOOKING_SMTP_USERNAME', '');
    $password = (string) hotel_booking_bookings_get_defined('HOTEL_BOOKING_SMTP_PASSWORD', '');

    if (!$host || !$port || !$username || !$password)
    {
        return null;
    }

    $encryption = strtolower(trim($encryption));
    if (!in_array($encryption, array('tls', 'ssl', 'none', ''), true))
    {
        $encryption = '';
    }

    return array(
        'host' => $host,
        'port' => $port,
        'encryption' => $encryption,
        'username' => $username,
        'password' => $password,
        'from_address' => (string) hotel_booking_bookings_get_defined('HOTEL_BOOKING_SMTP_FROM_ADDRESS', ''),
        'from_name' => (string) hotel_booking_bookings_get_defined('HOTEL_BOOKING_SMTP_FROM_NAME', ''),
    );
}

function hotel_booking_bookings_configure_phpmailer($phpmailer)
{
    $config = hotel_booking_bookings_smtp_config();
    if (!$config)
    {
        return;
    }

    // Optional SMTP debug: enable by adding `define('HOTEL_BOOKING_SMTP_DEBUG', true);` in wp-config.php.
    if (hotel_booking_bookings_get_defined('HOTEL_BOOKING_SMTP_DEBUG', false))
    {
        $phpmailer->SMTPDebug = 2;
        $phpmailer->Debugoutput = function ($str, $level) {
            $existing = (string) get_transient('hotel_booking_last_smtp_debug');
            $line = '[' . current_time('mysql') . '] ' . trim((string) $str) . "\n";
            hotel_booking_bookings_transient_set_limited('hotel_booking_last_smtp_debug', $existing . $line, 6000);
        };
    }

    $phpmailer->isSMTP();
    $phpmailer->Host = $config['host'];
    $phpmailer->Port = (int) $config['port'];
    $phpmailer->SMTPAuth = true;
    $phpmailer->Username = $config['username'];
    $phpmailer->Password = $config['password'];

    if (!empty($config['encryption']) && 'none' !== $config['encryption'])
    {
        $phpmailer->SMTPSecure = $config['encryption'];
        $phpmailer->SMTPAutoTLS = true;
    }
    else
    {
        $phpmailer->SMTPSecure = '';
        $phpmailer->SMTPAutoTLS = false;
    }

    $from_address = $config['from_address'] ? $config['from_address'] : $config['username'];
    $from_name = $config['from_name'] ? $config['from_name'] : wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES);

    if ($from_address)
    {
        $phpmailer->setFrom($from_address, $from_name, false);
    }
}
add_action('phpmailer_init', 'hotel_booking_bookings_configure_phpmailer', 5);

function hotel_booking_auth_secret()
{
    $defined = (string) hotel_booking_bookings_get_defined('HOTEL_BOOKING_AUTH_SECRET', '');
    if ($defined)
    {
        return $defined;
    }

    // Fallback to WordPress salts so tokens are unique per installation.
    $salt = wp_salt('auth');
    return $salt ? $salt : (string) AUTH_KEY;
}

function hotel_booking_auth_base64url_encode($data)
{
    return rtrim(strtr(base64_encode((string) $data), '+/', '-_'), '=');
}

function hotel_booking_auth_base64url_decode($data)
{
    $data = (string) $data;
    $data = strtr($data, '-_', '+/');
    $pad  = strlen($data) % 4;
    if ($pad)
    {
        $data .= str_repeat('=', 4 - $pad);
    }

    $decoded = base64_decode($data, true);
    return false === $decoded ? '' : $decoded;
}

function hotel_booking_auth_issue_token($user_id, $ttl_seconds = 1209600)
{
    $user_id = absint($user_id);
    if (!$user_id)
    {
        return '';
    }

    $iat = time();
    $exp = $iat + (int) $ttl_seconds;

    $header  = hotel_booking_auth_base64url_encode(wp_json_encode(array('alg' => 'HS256', 'typ' => 'JWT')));
    $payload = hotel_booking_auth_base64url_encode(wp_json_encode(array('uid' => $user_id, 'iat' => $iat, 'exp' => $exp)));

    $input = $header . '.' . $payload;
    $sig   = hash_hmac('sha256', $input, hotel_booking_auth_secret(), true);
    $sign  = hotel_booking_auth_base64url_encode($sig);

    return $input . '.' . $sign;
}

function hotel_booking_auth_validate_token($token)
{
    $token = is_string($token) ? trim($token) : '';
    if (!$token)
    {
        return new WP_Error('hotel_booking_auth_missing', __('Missing token.', 'hotel-booking'), array('status' => 401));
    }

    $parts = explode('.', $token);
    if (3 !== count($parts))
    {
        return new WP_Error('hotel_booking_auth_invalid', __('Invalid token format.', 'hotel-booking'), array('status' => 401));
    }

    list($header_b64, $payload_b64, $sig_b64) = $parts;

    $input = $header_b64 . '.' . $payload_b64;
    $calc  = hotel_booking_auth_base64url_encode(hash_hmac('sha256', $input, hotel_booking_auth_secret(), true));

    if (!hash_equals($calc, (string) $sig_b64))
    {
        return new WP_Error('hotel_booking_auth_bad_sig', __('Invalid token signature.', 'hotel-booking'), array('status' => 401));
    }

    $payload_json = hotel_booking_auth_base64url_decode($payload_b64);
    $payload      = json_decode($payload_json, true);
    if (!is_array($payload) || empty($payload['uid']) || empty($payload['exp']))
    {
        return new WP_Error('hotel_booking_auth_bad_payload', __('Invalid token payload.', 'hotel-booking'), array('status' => 401));
    }

    $exp = (int) $payload['exp'];
    if ($exp < time())
    {
        return new WP_Error('hotel_booking_auth_expired', __('Token expired.', 'hotel-booking'), array('status' => 401));
    }

    $user_id = absint($payload['uid']);
    if (!$user_id || !get_user_by('id', $user_id))
    {
        return new WP_Error('hotel_booking_auth_no_user', __('User not found.', 'hotel-booking'), array('status' => 401));
    }

    return $user_id;
}

function hotel_booking_auth_get_bearer_token_from_request($request)
{
    if (!$request || !method_exists($request, 'get_header'))
    {
        return '';
    }

    $auth = (string) $request->get_header('authorization');
    if (!$auth)
    {
        return '';
    }

    if (preg_match('/^Bearer\\s+(.+)$/i', trim($auth), $m))
    {
        return trim((string) $m[1]);
    }

    return '';
}

function hotel_booking_auth_maybe_set_current_user($result)
{
    // If another auth method already produced an error, keep it.
    if (is_wp_error($result))
    {
        return $result;
    }

    // If already logged in (cookie auth), do nothing.
    if (is_user_logged_in())
    {
        return $result;
    }

    // Only act if an Authorization header is present.
    $request = null;
    if (function_exists('rest_get_server'))
    {
        $server = rest_get_server();
        if ($server && method_exists($server, 'get_raw_request'))
        {
            // Not reliable; ignore.
        }
    }

    // The REST API passes the request object into permission callbacks, so we also provide a helper there.
    // Here, we try a best-effort token read from PHP globals.
    $auth = '';
    if (isset($_SERVER['HTTP_AUTHORIZATION']))
    {
        $auth = (string) $_SERVER['HTTP_AUTHORIZATION'];
    }
    elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION']))
    {
        $auth = (string) $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
    }

    if (!$auth || !preg_match('/^Bearer\\s+(.+)$/i', trim($auth), $m))
    {
        return $result;
    }

    $token  = trim((string) $m[1]);
    $user_id = hotel_booking_auth_validate_token($token);
    if (is_wp_error($user_id))
    {
        return $user_id;
    }

    wp_set_current_user((int) $user_id);
    return $result;
}
add_filter('rest_authentication_errors', 'hotel_booking_auth_maybe_set_current_user', 20);

function hotel_booking_bookings_table_install()
{
    global $wpdb;
    $table_name = hotel_booking_bookings_table_name();

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE {$table_name} (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        wp_post_id BIGINT UNSIGNED NOT NULL,
        room_id BIGINT UNSIGNED NULL,
        check_in DATE NULL,
        check_out DATE NULL,
        guests INT UNSIGNED NULL,
        name VARCHAR(190) NOT NULL DEFAULT '',
        email VARCHAR(190) NOT NULL DEFAULT '',
        phone VARCHAR(50) NOT NULL DEFAULT '',
        status VARCHAR(50) NOT NULL DEFAULT 'new',
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY wp_post_id (wp_post_id),
        KEY room_id (room_id),
        KEY email (email)
    ) {$charset_collate};";

    dbDelta($sql);
}

register_activation_hook(__FILE__, 'hotel_booking_bookings_table_install');

function hotel_booking_bookings_table_register_settings()
{
    register_setting('hotel_booking', 'hotel_booking_email_from_name', array(
        'type' => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default' => '',
    ));

    register_setting('hotel_booking', 'hotel_booking_email_from_address', array(
        'type' => 'string',
        'sanitize_callback' => 'sanitize_email',
        'default' => '',
    ));
}
add_action('admin_init', 'hotel_booking_bookings_table_register_settings');

function hotel_booking_bookings_admin_menu()
{
    add_submenu_page(
        'edit.php?post_type=booking',
        __('Hotel Booking Settings', 'hotel-booking'),
        __('Settings', 'hotel-booking'),
        'manage_options',
        'hotel-booking-settings',
        'hotel_booking_bookings_settings_page'
    );
}
add_action('admin_menu', 'hotel_booking_bookings_admin_menu');

function hotel_booking_bookings_settings_page()
{
    if (!current_user_can('manage_options'))
    {
        return;
    }

    $from_name    = (string) hotel_booking_bookings_get_option('hotel_booking_email_from_name', '');
    $from_address = (string) hotel_booking_bookings_get_option('hotel_booking_email_from_address', '');

    $last_error      = get_transient('hotel_booking_last_mail_error');
    $last_error_time = get_transient('hotel_booking_last_mail_error_time');
    $smtp_config     = hotel_booking_bookings_smtp_config();
    $smtp_debug      = get_transient('hotel_booking_last_smtp_debug');
    $last_success    = get_transient('hotel_booking_last_mail_success');

    echo '<div class="wrap">';
    echo '<h1>' . esc_html__('Hotel Booking Settings', 'hotel-booking') . '</h1>';

    echo '<h2>' . esc_html__('Email', 'hotel-booking') . '</h2>';
    echo '<p>' . esc_html__('If emails are not delivered, you likely need SMTP configuration (recommended). This page helps you test and diagnose.', 'hotel-booking') . '</p>';

    echo '<p><strong>' . esc_html__('SMTP status:', 'hotel-booking') . '</strong> ' . esc_html($smtp_config ? 'Enabled (wp-config.php)' : 'Disabled') . '</p>';
    if ($smtp_config)
    {
        echo '<ul style="list-style:disc;margin-left:18px;">';
        echo '<li><strong>' . esc_html__('Host:', 'hotel-booking') . '</strong> ' . esc_html($smtp_config['host']) . '</li>';
        echo '<li><strong>' . esc_html__('Port:', 'hotel-booking') . '</strong> ' . esc_html((string) $smtp_config['port']) . '</li>';
        echo '<li><strong>' . esc_html__('Encryption:', 'hotel-booking') . '</strong> ' . esc_html($smtp_config['encryption'] ? $smtp_config['encryption'] : 'none') . '</li>';
        echo '</ul>';
    }
    else
    {
        echo '<p style="margin-top:0;color:#666;">' . esc_html__('To enable SMTP, add HOTEL_BOOKING_SMTP_* constants in wp-config.php.', 'hotel-booking') . '</p>';
    }

    echo '<form method="post" action="options.php">';
    settings_fields('hotel_booking');
    echo '<table class="form-table" role="presentation">';

    echo '<tr>';
    echo '<th scope="row"><label for="hotel_booking_email_from_name">' . esc_html__('From name', 'hotel-booking') . '</label></th>';
    echo '<td><input name="hotel_booking_email_from_name" id="hotel_booking_email_from_name" type="text" class="regular-text" value="' . esc_attr($from_name) . '" />';
    echo '<p class="description">' . esc_html__('Example: Mukta Boutique Hotel', 'hotel-booking') . '</p></td>';
    echo '</tr>';

    echo '<tr>';
    echo '<th scope="row"><label for="hotel_booking_email_from_address">' . esc_html__('From email', 'hotel-booking') . '</label></th>';
    echo '<td><input name="hotel_booking_email_from_address" id="hotel_booking_email_from_address" type="email" class="regular-text" value="' . esc_attr($from_address) . '" />';
    echo '<p class="description">' . esc_html__('Use an address on your domain for best delivery (SMTP recommended).', 'hotel-booking') . '</p></td>';
    echo '</tr>';

    echo '</table>';
    submit_button(__('Save settings', 'hotel-booking'));
    echo '</form>';

    echo '<hr />';
    echo '<h2>' . esc_html__('Send Test Email', 'hotel-booking') . '</h2>';
    echo '<p>' . esc_html__('This sends a test email to the site admin email. If it fails, configure SMTP (WP Mail SMTP plugin or server SMTP).', 'hotel-booking') . '</p>';

    echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
    echo '<input type="hidden" name="action" value="hotel_booking_send_test_email" />';
    wp_nonce_field('hotel_booking_send_test_email', 'hotel_booking_send_test_email_nonce');
    echo '<p><label for="hotel_booking_test_to"><strong>' . esc_html__('Send test to', 'hotel-booking') . '</strong></label><br>';
    echo '<input id="hotel_booking_test_to" name="test_to" type="email" class="regular-text" value="' . esc_attr((string) get_option('admin_email')) . '" />';
    echo '<p class="description">' . esc_html__('Try sending to a different mailbox (example: Gmail) to confirm real delivery.', 'hotel-booking') . '</p></p>';
    submit_button(__('Send test email', 'hotel-booking'), 'secondary');
    echo '</form>';

    echo '<h3>' . esc_html__('Diagnostics', 'hotel-booking') . '</h3>';
    echo '<ul style="list-style:disc;margin-left:18px;">';
    echo '<li><strong>' . esc_html__('Admin email:', 'hotel-booking') . '</strong> ' . esc_html((string) get_option('admin_email')) . '</li>';
    echo '<li><strong>' . esc_html__('WP_DEBUG:', 'hotel-booking') . '</strong> ' . esc_html(defined('WP_DEBUG') && WP_DEBUG ? 'true' : 'false') . '</li>';
    echo '<li><strong>' . esc_html__('WP_DEBUG_LOG:', 'hotel-booking') . '</strong> ' . esc_html(defined('WP_DEBUG_LOG') && WP_DEBUG_LOG ? 'true' : 'false') . '</li>';
    if (is_array($last_success))
    {
        echo '<li><strong>' . esc_html__('Last mail success:', 'hotel-booking') . '</strong> ' . esc_html(($last_success['time'] ?? '') . ' → ' . ($last_success['to'] ?? '') . ' (' . ($last_success['subject'] ?? '') . ')') . '</li>';
    }
    echo '<li><strong>' . esc_html__('Last mail error:', 'hotel-booking') . '</strong> ' . ($last_error ? esc_html($last_error) : '—') . '</li>';
    if ($last_error && $last_error_time)
    {
        echo '<li><strong>' . esc_html__('Last mail error time:', 'hotel-booking') . '</strong> ' . esc_html($last_error_time) . '</li>';
    }
    echo '</ul>';

    if (hotel_booking_bookings_get_defined('HOTEL_BOOKING_SMTP_DEBUG', false))
    {
        echo '<h4 style="margin:16px 0 8px;">' . esc_html__('SMTP debug (latest)', 'hotel-booking') . '</h4>';
        echo '<pre style="max-height:260px;overflow:auto;background:#0b1020;color:#e6edf3;padding:12px;border-radius:8px;white-space:pre-wrap;">'
            . esc_html($smtp_debug ? $smtp_debug : '—') . '</pre>';
        echo '<p style="margin:0;color:#666;">' . esc_html__('Disable by setting HOTEL_BOOKING_SMTP_DEBUG to false in wp-config.php.', 'hotel-booking') . '</p>';
    }

    echo '</div>';
}

function hotel_booking_bookings_test_email_handler()
{
    if (!current_user_can('manage_options'))
    {
        wp_die(__('Not allowed.', 'hotel-booking'));
    }

    check_admin_referer('hotel_booking_send_test_email', 'hotel_booking_send_test_email_nonce');

    delete_transient('hotel_booking_last_mail_error');
    delete_transient('hotel_booking_last_mail_error_time');
    delete_transient('hotel_booking_last_smtp_debug');
    delete_transient('hotel_booking_last_mail_success');

    $to = isset($_POST['test_to']) ? sanitize_email(wp_unslash($_POST['test_to'])) : '';
    if (!$to)
    {
        $to = (string) get_option('admin_email');
    }
    $subject = sprintf('[%s] %s', wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES), __('Test email', 'hotel-booking'));
    $html    = '<div style="font-family:Segoe UI,Arial,sans-serif;line-height:1.6;"><h2>' . esc_html__('Test email sent successfully.', 'hotel-booking') . '</h2><p>' . esc_html__('If you can read this, WordPress email sending works. For reliable delivery, configure SMTP.', 'hotel-booking') . '</p></div>';

    $success = hotel_booking_send_booking_email($to, $subject, $html);

    $redirect = add_query_arg(
        array(
            'post_type' => 'booking',
            'page' => 'hotel-booking-settings',
            'hotel_booking_test_mail' => $success ? 'success' : 'fail',
        ),
        admin_url('edit.php')
    );

    wp_safe_redirect($redirect);
    exit;
}
add_action('admin_post_hotel_booking_send_test_email', 'hotel_booking_bookings_test_email_handler');

function hotel_booking_bookings_settings_notices()
{
    if (!is_admin())
    {
        return;
    }

    if (!isset($_GET['page']) || 'hotel-booking-settings' !== $_GET['page'])
    {
        return;
    }

    $result = isset($_GET['hotel_booking_test_mail']) ? sanitize_text_field(wp_unslash($_GET['hotel_booking_test_mail'])) : '';
    if ('success' === $result)
    {
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Test email sent. Check inbox/spam.', 'hotel-booking') . '</p></div>';
    } elseif ('fail' === $result)
    {
        echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__('Test email failed. Configure SMTP (recommended) and check the diagnostics below.', 'hotel-booking') . '</p></div>';
    }
}
add_action('admin_notices', 'hotel_booking_bookings_settings_notices');

function hotel_booking_bookings_capture_mail_failed($wp_error)
{
    if (!$wp_error instanceof WP_Error)
    {
        return;
    }

    $message = $wp_error->get_error_message();
    if (!$message)
    {
        $message = 'wp_mail_failed';
    }

    $data = $wp_error->get_error_data();
    if (is_array($data) && isset($data['phpmailer_exception']) && $data['phpmailer_exception'] instanceof Exception)
    {
        $message .= ' | ' . $data['phpmailer_exception']->getMessage();
    }

    set_transient('hotel_booking_last_mail_error', $message, 6 * HOUR_IN_SECONDS);
    set_transient('hotel_booking_last_mail_error_time', current_time('mysql'), 6 * HOUR_IN_SECONDS);
}
add_action('wp_mail_failed', 'hotel_booking_bookings_capture_mail_failed');

function hotel_booking_bookings_capture_mail_succeeded($mail_data)
{
    $to = '';
    if (is_array($mail_data) && isset($mail_data['to']))
    {
        if (is_array($mail_data['to']))
        {
            $to = implode(', ', array_map('sanitize_email', $mail_data['to']));
        }
        else
        {
            $to = sanitize_email((string) $mail_data['to']);
        }
    }

    $subject = is_array($mail_data) && isset($mail_data['subject']) ? (string) $mail_data['subject'] : '';

    hotel_booking_bookings_transient_set('hotel_booking_last_mail_success', array(
        'time' => current_time('mysql'),
        'to' => $to,
        'subject' => $subject,
    ));
}
add_action('wp_mail_succeeded', 'hotel_booking_bookings_capture_mail_succeeded', 10, 1);

function hotel_booking_booking_statuses()
{
    return array(
        'new' => __('New', 'hotel-booking'),
        'confirmed' => __('Confirmed', 'hotel-booking'),
        'cancelled' => __('Cancelled', 'hotel-booking'),
    );
}

function hotel_booking_auth_require_user($request)
{
    if (is_user_logged_in())
    {
        return true;
    }

    $token = hotel_booking_auth_get_bearer_token_from_request($request);
    if (!$token)
    {
        return new WP_Error('hotel_booking_auth_required', __('Authentication required.', 'hotel-booking'), array('status' => 401));
    }

    $user_id = hotel_booking_auth_validate_token($token);
    if (is_wp_error($user_id))
    {
        return $user_id;
    }

    wp_set_current_user((int) $user_id);
    return true;
}

function hotel_booking_auth_register_routes()
{
    register_rest_route(
        'hotel-booking/v1',
        '/auth/register',
        array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => 'hotel_booking_auth_register_callback',
            'permission_callback' => '__return_true',
        )
    );

    register_rest_route(
        'hotel-booking/v1',
        '/auth/login',
        array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => 'hotel_booking_auth_login_callback',
            'permission_callback' => '__return_true',
        )
    );

    register_rest_route(
        'hotel-booking/v1',
        '/auth/me',
        array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => 'hotel_booking_auth_me_callback',
            'permission_callback' => 'hotel_booking_auth_require_user',
        )
    );

    register_rest_route(
        'hotel-booking/v1',
        '/my-bookings',
        array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => 'hotel_booking_auth_my_bookings_callback',
            'permission_callback' => 'hotel_booking_auth_require_user',
        )
    );

    register_rest_route(
        'hotel-booking/v1',
        '/bookings/(?P<id>\\d+)/cancel',
        array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => 'hotel_booking_auth_cancel_booking_callback',
            'permission_callback' => 'hotel_booking_auth_require_user',
            'args' => array(
                'id' => array(
                    'validate_callback' => 'is_numeric',
                ),
            ),
        )
    );

    register_rest_route(
        'hotel-booking/v1',
        '/bookings/(?P<id>\\d+)/change-dates',
        array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => 'hotel_booking_auth_change_dates_callback',
            'permission_callback' => 'hotel_booking_auth_require_user',
            'args' => array(
                'id' => array(
                    'validate_callback' => 'is_numeric',
                ),
            ),
        )
    );
}
add_action('rest_api_init', 'hotel_booking_auth_register_routes');

function hotel_booking_auth_register_callback($request)
{
    $params = $request->get_json_params();
    if (!is_array($params))
    {
        $params = $request->get_params();
    }

    $name     = isset($params['name']) ? sanitize_text_field((string) $params['name']) : '';
    $email    = isset($params['email']) ? sanitize_email((string) $params['email']) : '';
    $password = isset($params['password']) ? (string) $params['password'] : '';

    if (!$email || !$password)
    {
        return new WP_Error('hotel_booking_register_invalid', __('Email and password are required.', 'hotel-booking'), array('status' => 400));
    }

    if (email_exists($email))
    {
        return new WP_Error('hotel_booking_register_exists', __('An account with this email already exists. Please log in.', 'hotel-booking'), array('status' => 409));
    }

    $base = sanitize_user(current(explode('@', $email)), true);
    $base = $base ? $base : 'guest';
    $username = $base;
    $i = 0;
    while (username_exists($username))
    {
        $i++;
        $username = $base . $i;
        if ($i > 999)
        {
            break;
        }
    }

    $user_id = wp_insert_user(array(
        'user_login' => $username,
        'user_email' => $email,
        'user_pass' => $password,
        'display_name' => $name ? $name : $username,
        'role' => 'subscriber',
    ));

    if (is_wp_error($user_id))
    {
        return new WP_Error('hotel_booking_register_failed', __('Could not create account.', 'hotel-booking'), array('status' => 500));
    }

    // Send a welcome email (do not include the password).
    $brand = hotel_booking_brand_name();
    $subject = sprintf(__('Welcome to %s', 'hotel-booking'), $brand);
    $html = '<div style="font-family:Segoe UI,Arial,sans-serif;line-height:1.6;">'
        . '<h2>' . esc_html__('Your account is ready', 'hotel-booking') . '</h2>'
        . '<p>' . sprintf(esc_html__('Username: %s', 'hotel-booking'), esc_html($username)) . '</p>'
        . '<p>' . esc_html__('Use the password you set during registration to log in.', 'hotel-booking') . '</p>'
        . '</div>';
    hotel_booking_send_booking_email($email, $subject, $html);

    $token = hotel_booking_auth_issue_token((int) $user_id);

    return rest_ensure_response(array(
        'success' => true,
        'token' => $token,
        'user' => array(
            'id' => (int) $user_id,
            'username' => $username,
            'email' => $email,
            'name' => $name ? $name : $username,
        ),
    ));
}

function hotel_booking_auth_login_callback($request)
{
    $params = $request->get_json_params();
    if (!is_array($params))
    {
        $params = $request->get_params();
    }

    $identifier = isset($params['identifier']) ? sanitize_text_field((string) $params['identifier']) : '';
    $email      = isset($params['email']) ? sanitize_email((string) $params['email']) : '';
    $password   = isset($params['password']) ? (string) $params['password'] : '';

    if (!$password || (!$identifier && !$email))
    {
        return new WP_Error('hotel_booking_login_invalid', __('Email/username and password are required.', 'hotel-booking'), array('status' => 400));
    }

    $login = $identifier ? $identifier : $email;

    $user = wp_authenticate($login, $password);
    if (is_wp_error($user))
    {
        return new WP_Error('hotel_booking_login_failed', __('Invalid credentials.', 'hotel-booking'), array('status' => 401));
    }

    $token = hotel_booking_auth_issue_token((int) $user->ID);

    return rest_ensure_response(array(
        'success' => true,
        'token' => $token,
        'user' => array(
            'id' => (int) $user->ID,
            'username' => (string) $user->user_login,
            'email' => (string) $user->user_email,
            'name' => (string) $user->display_name,
        ),
    ));
}

function hotel_booking_auth_me_callback($request)
{
    $user = wp_get_current_user();
    if (!$user || empty($user->ID))
    {
        return new WP_Error('hotel_booking_auth_required', __('Authentication required.', 'hotel-booking'), array('status' => 401));
    }

    return rest_ensure_response(array(
        'success' => true,
        'user' => array(
            'id' => (int) $user->ID,
            'username' => (string) $user->user_login,
            'email' => (string) $user->user_email,
            'name' => (string) $user->display_name,
        ),
    ));
}

function hotel_booking_auth_my_bookings_callback($request)
{
    $user = wp_get_current_user();
    if (!$user || empty($user->ID))
    {
        return new WP_Error('hotel_booking_auth_required', __('Authentication required.', 'hotel-booking'), array('status' => 401));
    }

    $ids = get_posts(array(
        'post_type' => 'booking',
        'post_status' => array('publish', 'trash'),
        'fields' => 'ids',
        'numberposts' => 200,
        'meta_query' => array(
            array(
                'key' => '_booking_user_id',
                'value' => (string) (int) $user->ID,
                'compare' => '=',
            ),
        ),
    ));

    // Fallback by email (helps for bookings created before linking).
    if (empty($ids) && !empty($user->user_email))
    {
        $ids = get_posts(array(
            'post_type' => 'booking',
            'post_status' => array('publish', 'trash'),
            'fields' => 'ids',
            'numberposts' => 200,
            'meta_query' => array(
                array(
                    'key' => '_booking_email',
                    'value' => (string) $user->user_email,
                    'compare' => '=',
                ),
            ),
        ));
    }

    $bookings = array();
    foreach (($ids ?: array()) as $booking_id)
    {
        $booking_id = absint($booking_id);
        if (!$booking_id)
        {
            continue;
        }

        // Ensure ownership is cached.
        hotel_booking_booking_belongs_to_current_user($booking_id);

        $status = hotel_booking_get_booking_status($booking_id);
        $room_id = (int) get_post_meta($booking_id, '_booking_room_id', true);
        $bookings[] = array(
            'id' => $booking_id,
            'status' => $status,
            'room_id' => $room_id,
            'room_title' => $room_id ? get_the_title($room_id) : '',
            'check_in' => (string) get_post_meta($booking_id, '_booking_check_in', true),
            'check_out' => (string) get_post_meta($booking_id, '_booking_check_out', true),
            'guests' => (int) get_post_meta($booking_id, '_booking_guests', true),
            'name' => (string) get_post_meta($booking_id, '_booking_name', true),
            'email' => (string) get_post_meta($booking_id, '_booking_email', true),
            'phone' => (string) get_post_meta($booking_id, '_booking_phone', true),
        );
    }

    return rest_ensure_response(array(
        'success' => true,
        'bookings' => $bookings,
    ));
}

function hotel_booking_auth_cancel_booking_callback($request)
{
    $booking_id = absint($request['id']);
    if (!$booking_id)
    {
        return new WP_Error('hotel_booking_invalid', __('Invalid booking.', 'hotel-booking'), array('status' => 400));
    }

    if (!hotel_booking_booking_belongs_to_current_user($booking_id))
    {
        return new WP_Error('hotel_booking_forbidden', __('Not allowed.', 'hotel-booking'), array('status' => 403));
    }

    $status = hotel_booking_get_booking_status($booking_id);
    if ('cancelled' !== $status)
    {
        hotel_booking_set_booking_status($booking_id, 'cancelled', 'customer_api_cancel');
    }

    return rest_ensure_response(array('success' => true));
}

function hotel_booking_auth_change_dates_callback($request)
{
    $booking_id = absint($request['id']);
    if (!$booking_id)
    {
        return new WP_Error('hotel_booking_invalid', __('Invalid booking.', 'hotel-booking'), array('status' => 400));
    }

    if (!hotel_booking_booking_belongs_to_current_user($booking_id))
    {
        return new WP_Error('hotel_booking_forbidden', __('Not allowed.', 'hotel-booking'), array('status' => 403));
    }

    $params = $request->get_json_params();
    if (!is_array($params))
    {
        $params = $request->get_params();
    }

    $new_check_in  = isset($params['new_check_in']) ? hotel_booking_bookings_table_parse_date((string) $params['new_check_in']) : null;
    $new_check_out = isset($params['new_check_out']) ? hotel_booking_bookings_table_parse_date((string) $params['new_check_out']) : null;

    if (!$new_check_in || !$new_check_out || $new_check_out <= $new_check_in)
    {
        return new WP_Error('hotel_booking_invalid_dates', __('Invalid dates.', 'hotel-booking'), array('status' => 400));
    }

    $request_meta = array(
        'new_check_in' => $new_check_in,
        'new_check_out' => $new_check_out,
        'requested_at' => current_time('mysql'),
        'requested_by_user_id' => (int) get_current_user_id(),
    );
    update_post_meta($booking_id, '_booking_change_request', $request_meta);

    $customer_email = (string) get_post_meta($booking_id, '_booking_email', true);
    $admin_email    = (string) get_option('admin_email');
    $name           = (string) get_post_meta($booking_id, '_booking_name', true);

    hotel_booking_send_booking_email(
        $admin_email,
        $name
            ? sprintf(__('Date change requested: %s #%d', 'hotel-booking'), $name, (int) $booking_id)
            : sprintf(__('Date change requested #%d', 'hotel-booking'), (int) $booking_id),
        hotel_booking_build_change_request_email_html($booking_id, $new_check_in, $new_check_out, 'admin')
    );

    hotel_booking_send_booking_email(
        $customer_email,
        $name
            ? sprintf(__('Hi %s, we received your date change request (#%d)', 'hotel-booking'), $name, (int) $booking_id)
            : sprintf(__('We received your date change request (#%d)', 'hotel-booking'), (int) $booking_id),
        hotel_booking_build_change_request_email_html($booking_id, $new_check_in, $new_check_out, 'customer')
    );

    return rest_ensure_response(array('success' => true));
}

function hotel_booking_brand_name()
{
    $smtp = hotel_booking_bookings_smtp_config();
    if ($smtp && !empty($smtp['from_name']))
    {
        return (string) $smtp['from_name'];
    }

    $from_name = (string) hotel_booking_bookings_get_option('hotel_booking_email_from_name', '');
    if ($from_name)
    {
        return $from_name;
    }

    return wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES);
}

function hotel_booking_get_booking_status($booking_id)
{
    $status   = (string) get_post_meta($booking_id, '_booking_status', true);
    $statuses = hotel_booking_booking_statuses();

    if (!$status || !isset($statuses[$status]))
    {
        return 'new';
    }

    return $status;
}

function hotel_booking_send_booking_email($to, $subject, $html)
{
    $to = sanitize_email($to);
    if (!$to)
    {
        return false;
    }

    $headers = array('Content-Type: text/html; charset=UTF-8');

    $from_name    = (string) hotel_booking_bookings_get_option('hotel_booking_email_from_name', '');
    $from_address = (string) hotel_booking_bookings_get_option('hotel_booking_email_from_address', '');

    if ($from_address)
    {
        $headers[] = sprintf('From: %s <%s>', $from_name ? $from_name : wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES), $from_address);
    }

    $brand   = hotel_booking_brand_name();
    $subject = preg_match('/^\\[.*\\]/', $subject) ? $subject : sprintf('[%s] %s', $brand, $subject);

    $sent = wp_mail($to, $subject, $html, $headers);
    return (bool) $sent;
}

function hotel_booking_build_booking_email_html($booking_id, $headline, $recipient = 'customer')
{
    $booking_id   = absint($booking_id);
    $room_id      = (int) get_post_meta($booking_id, '_booking_room_id', true);
    $room_title   = $room_id ? get_the_title($room_id) : '';
    $check_in     = (string) get_post_meta($booking_id, '_booking_check_in', true);
    $check_out    = (string) get_post_meta($booking_id, '_booking_check_out', true);
    $guests       = (string) get_post_meta($booking_id, '_booking_guests', true);
    $name         = (string) get_post_meta($booking_id, '_booking_name', true);
    $email        = (string) get_post_meta($booking_id, '_booking_email', true);
    $phone        = (string) get_post_meta($booking_id, '_booking_phone', true);
    $status       = hotel_booking_get_booking_status($booking_id);
    $status_label = hotel_booking_booking_statuses()[$status] ?? $status;

    $brand = hotel_booking_brand_name();

    $manage_link_html = '';
    if ('customer' === $recipient)
    {
        $token = hotel_booking_booking_get_or_create_manage_token($booking_id);
        if ($token)
        {
            $url = add_query_arg(
                array(
                    'hotel_booking_manage' => 1,
                    'booking_id' => (int) $booking_id,
                    'token' => rawurlencode($token),
                ),
                home_url('/')
            );

            $manage_link_html = '<p style="margin:14px 0 0;">'
                . esc_html__('Need to cancel or change dates?', 'hotel-booking') . '<br>'
                . '<a href="' . esc_url($url) . '">' . esc_html__('Manage your booking', 'hotel-booking') . '</a>'
                . '</p>';
        }
    }

    $lines = array(
        array(__('Booking ID', 'hotel-booking'), '#' . $booking_id),
        array(__('Status', 'hotel-booking'), $status_label),
        array(__('Room', 'hotel-booking'), $room_title ? $room_title : '—'),
        array(__('Check-in', 'hotel-booking'), $check_in ? $check_in : '—'),
        array(__('Check-out', 'hotel-booking'), $check_out ? $check_out : '—'),
        array(__('Guests', 'hotel-booking'), $guests ? $guests : '—'),
        array(__('Name', 'hotel-booking'), $name ? $name : '—'),
        array(__('Email', 'hotel-booking'), $email ? $email : '—'),
        array(__('Phone', 'hotel-booking'), $phone ? $phone : '—'),
    );

    $rows = '';
    foreach ($lines as $line)
    {
        $rows .= '<tr><td style="padding:6px 10px;border:1px solid #e5e5e5;"><strong>' . esc_html($line[0]) . '</strong></td><td style="padding:6px 10px;border:1px solid #e5e5e5;">' . esc_html($line[1]) . '</td></tr>';
    }

    $greeting = '';
    if ('customer' === $recipient && $name)
    {
        $greeting = '<p style="margin:0 0 14px;">' . sprintf(esc_html__('Hi %s,', 'hotel-booking'), esc_html($name)) . '</p>';
    }
    elseif ('admin' === $recipient)
    {
        $greeting = '<p style="margin:0 0 14px;">' . esc_html__('New booking details:', 'hotel-booking') . '</p>';
    }

    return '<div style="font-family:Segoe UI,Arial,sans-serif;color:#1e1a16;line-height:1.6;">'
        . '<div style="margin:0 0 14px;"><strong style="font-size:16px;">' . esc_html($brand) . '</strong></div>'
        . '<h2 style="margin:0 0 12px;">' . esc_html($headline) . '</h2>'
        . $greeting
        . '<table style="border-collapse:collapse;width:100%;max-width:620px;">' . $rows . '</table>'
        . $manage_link_html
        . '</div>';
}

function hotel_booking_booking_get_or_create_manage_token($booking_id)
{
    $booking_id = absint($booking_id);
    if (!$booking_id)
    {
        return '';
    }

    $existing = (string) get_post_meta($booking_id, '_booking_manage_token', true);
    if ($existing)
    {
        return $existing;
    }

    try
    {
        $token = bin2hex(random_bytes(16));
    }
    catch (Exception $e)
    {
        $token = wp_generate_password(32, false, false);
    }

    update_post_meta($booking_id, '_booking_manage_token', $token);
    update_post_meta($booking_id, '_booking_manage_token_created_at', current_time('mysql'));

    return (string) $token;
}

function hotel_booking_set_booking_status($booking_id, $new_status, $source = '')
{
    $booking_id = absint($booking_id);
    $new_status = is_string($new_status) ? strtolower(trim($new_status)) : '';
    $statuses   = hotel_booking_booking_statuses();

    if (!$booking_id || !isset($statuses[$new_status]))
    {
        return false;
    }

    $old_status = hotel_booking_get_booking_status($booking_id);
    if ($old_status === $new_status)
    {
        return true;
    }

    update_post_meta($booking_id, '_booking_status', $new_status);
    if (is_user_logged_in())
    {
        $user_id = get_current_user_id();
        if ($user_id)
        {
            update_post_meta($booking_id, '_booking_status_changed_by', (int) $user_id);
        }
    }

    // Keep the custom table in sync (if installed).
    if (function_exists('hotel_booking_bookings_table_upsert_for_post'))
    {
        hotel_booking_bookings_table_upsert_for_post($booking_id);
    }

    do_action('hotel_booking_booking_status_changed', $booking_id, $old_status, $new_status, $source);

    return true;
}

function hotel_booking_bookings_table_parse_date($value)
{
    $value = is_string($value) ? trim($value) : '';

    if (!$value)
    {
        return null;
    }

    // Expect YYYY-MM-DD. Anything else becomes NULL so the sync doesn't break.
    if (!preg_match('/^\\d{4}-\\d{2}-\\d{2}$/', $value))
    {
        return null;
    }

    return $value;
}

function hotel_booking_bookings_table_upsert_for_post($post_id)
{
    global $wpdb;

    $post_id = absint($post_id);
    if (!$post_id)
    {
        return;
    }

    $post = get_post($post_id);
    if (!$post || 'booking' !== $post->post_type)
    {
        return;
    }

    $table_name = hotel_booking_bookings_table_name();

    $room_id   = (int) get_post_meta($post_id, '_booking_room_id', true);
    $check_in  = hotel_booking_bookings_table_parse_date(get_post_meta($post_id, '_booking_check_in', true));
    $check_out = hotel_booking_bookings_table_parse_date(get_post_meta($post_id, '_booking_check_out', true));
    $guests    = get_post_meta($post_id, '_booking_guests', true);
    $guests    = is_numeric($guests) ? (int) $guests : null;
    $name      = (string) get_post_meta($post_id, '_booking_name', true);
    $email     = (string) get_post_meta($post_id, '_booking_email', true);
    $phone     = (string) get_post_meta($post_id, '_booking_phone', true);
    $status    = (string) get_post_meta($post_id, '_booking_status', true);

    $created_at = get_post_time('Y-m-d H:i:s', true, $post);
    $created_at = $created_at ? $created_at : current_time('mysql');
    $updated_at = current_time('mysql');

    $status = $status ? sanitize_text_field($status) : 'new';

    // Insert or update (unique key on wp_post_id).
    $wpdb->query(
        $wpdb->prepare(
            "INSERT INTO {$table_name}
                (wp_post_id, room_id, check_in, check_out, guests, name, email, phone, status, created_at, updated_at)
             VALUES
                (%d, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)
             ON DUPLICATE KEY UPDATE
                room_id = VALUES(room_id),
                check_in = VALUES(check_in),
                check_out = VALUES(check_out),
                guests = VALUES(guests),
                name = VALUES(name),
                email = VALUES(email),
                phone = VALUES(phone),
                status = VALUES(status),
                updated_at = VALUES(updated_at)",
            $post_id,
            $room_id ? (string) $room_id : null,
            $check_in,
            $check_out,
            is_null($guests) ? null : (string) $guests,
            $name,
            $email,
            $phone,
            $status,
            $created_at,
            $updated_at
        )
    );

    $row_id = (int) $wpdb->get_var(
        $wpdb->prepare("SELECT id FROM {$table_name} WHERE wp_post_id = %d LIMIT 1", $post_id)
    );

    if ($row_id)
    {
        update_post_meta($post_id, '_booking_table_id', $row_id);
    }
}

function hotel_booking_bookings_table_sync_on_save($post_id, $post, $update)
{
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
    {
        return;
    }

    if (!$post || 'booking' !== $post->post_type)
    {
        return;
    }

    if (!current_user_can('edit_post', $post_id))
    {
        return;
    }

    hotel_booking_bookings_table_upsert_for_post($post_id);
}
add_action('save_post_booking', 'hotel_booking_bookings_table_sync_on_save', 20, 3);

function hotel_booking_booking_default_status_on_create($post_id, $post, $update)
{
    if ($update || !$post || 'booking' !== $post->post_type)
    {
        return;
    }

    if (!get_post_meta($post_id, '_booking_status', true))
    {
        update_post_meta($post_id, '_booking_status', 'new');
    }
}
add_action('wp_after_insert_post', 'hotel_booking_booking_default_status_on_create', 10, 3);

function hotel_booking_booking_schedule_initial_emails($post_id, $post, $update)
{
    if ($update || !$post || 'booking' !== $post->post_type)
    {
        return;
    }

    if (get_post_meta($post_id, '_booking_email_sent', true))
    {
        return;
    }

    if (wp_next_scheduled('hotel_booking_send_initial_booking_emails', array($post_id)))
    {
        return;
    }

    // Booking meta is often written AFTER the booking post is created. Schedule a short delay so the email has full details.
    wp_schedule_single_event(time() + 3, 'hotel_booking_send_initial_booking_emails', array($post_id));
}
add_action('wp_after_insert_post', 'hotel_booking_booking_schedule_initial_emails', 20, 3);

function hotel_booking_booking_has_minimum_details($post_id)
{
    $required_keys = array(
        '_booking_check_in',
        '_booking_check_out',
        '_booking_guests',
        '_booking_room_id',
        '_booking_name',
        '_booking_email',
    );

    foreach ($required_keys as $key)
    {
        $value = get_post_meta($post_id, $key, true);
        if ('' === $value || null === $value)
        {
            return false;
        }
    }

    return true;
}

function hotel_booking_booking_send_initial_emails_scheduled($post_id)
{
    $post_id = absint($post_id);
    if (!$post_id)
    {
        return;
    }

    $post = get_post($post_id);
    if (!$post || 'booking' !== $post->post_type)
    {
        return;
    }

    if (get_post_meta($post_id, '_booking_email_sent', true))
    {
        return;
    }

    // If we can already send from an immediate hook (REST/meta already present), do it now.
    if (hotel_booking_booking_has_minimum_details($post_id))
    {
        hotel_booking_booking_send_initial_emails_now($post_id, 'scheduled');
        return;
    }

    if (!hotel_booking_booking_has_minimum_details($post_id))
    {
        $attempts = (int) get_post_meta($post_id, '_booking_email_attempts', true);
        $attempts++;
        update_post_meta($post_id, '_booking_email_attempts', $attempts);

        // Retry a few times in case meta is saved slightly later.
        if ($attempts <= 6)
        {
            wp_schedule_single_event(time() + 3, 'hotel_booking_send_initial_booking_emails', array($post_id));
        }

        return;
    }
}
add_action('hotel_booking_send_initial_booking_emails', 'hotel_booking_booking_send_initial_emails_scheduled', 10, 1);

function hotel_booking_booking_send_initial_emails_now($booking_id, $source = '')
{
    $booking_id = absint($booking_id);
    if (!$booking_id)
    {
        return false;
    }

    $post = get_post($booking_id);
    if (!$post || 'booking' !== $post->post_type)
    {
        return false;
    }

    if (get_post_meta($booking_id, '_booking_email_sent', true))
    {
        return true;
    }

    if (!hotel_booking_booking_has_minimum_details($booking_id))
    {
        return false;
    }

    // Avoid duplicates if a scheduled event already exists.
    wp_clear_scheduled_hook('hotel_booking_send_initial_booking_emails', array($booking_id));

    $customer_email = (string) get_post_meta($booking_id, '_booking_email', true);
    $admin_email    = (string) get_option('admin_email');

    $name       = (string) get_post_meta($booking_id, '_booking_name', true);
    $room_id    = (int) get_post_meta($booking_id, '_booking_room_id', true);
    $room_title = $room_id ? get_the_title($room_id) : '';

    $subject_customer = $name
        ? sprintf(__('Thank you %s! Booking request received (#%d)', 'hotel-booking'), $name, (int) $booking_id)
        : sprintf(__('Booking request received (#%d)', 'hotel-booking'), (int) $booking_id);

    $subject_admin = $room_title && $name
        ? sprintf(__('New booking: %s (%s) #%d', 'hotel-booking'), $name, $room_title, (int) $booking_id)
        : sprintf(__('New booking request #%d', 'hotel-booking'), (int) $booking_id);

    hotel_booking_send_booking_email(
        $customer_email,
        $subject_customer,
        hotel_booking_build_booking_email_html($booking_id, __('Thank you for booking. We received your request.', 'hotel-booking'), 'customer')
    );

    $admin_html = hotel_booking_build_booking_email_html($booking_id, __('New booking request received', 'hotel-booking'), 'admin');
    $edit_link  = get_edit_post_link($booking_id, '');
    $list_link  = admin_url('edit.php?post_type=booking');
    $admin_html .= '<p style="margin:14px 0 0;">'
        . '<a href="' . esc_url($edit_link) . '" style="display:inline-block;margin-right:10px;">' . esc_html__('Open booking', 'hotel-booking') . '</a>'
        . '<a href="' . esc_url($list_link) . '" style="display:inline-block;">' . esc_html__('View all bookings', 'hotel-booking') . '</a>'
        . '</p>';

    hotel_booking_send_booking_email(
        $admin_email,
        $subject_admin,
        $admin_html
    );

    update_post_meta($booking_id, '_booking_email_sent', 1);
    do_action('hotel_booking_booking_sms_notification', 'booking_created', $booking_id, $source);

    return true;
}

function hotel_booking_booking_send_initial_emails_on_created_action($booking_id, $source = '')
{
    hotel_booking_booking_send_initial_emails_now($booking_id, $source);
}
add_action('hotel_booking_booking_created', 'hotel_booking_booking_send_initial_emails_on_created_action', 10, 2);

function hotel_booking_manage_booking_render($booking_id, $token, $message = '')
{
    $booking_id = absint($booking_id);
    $token      = is_string($token) ? trim($token) : '';

    if (!$booking_id || !$token)
    {
        return '<div style="font-family:Segoe UI,Arial,sans-serif;max-width:720px;margin:40px auto;padding:0 16px;">'
            . '<h2>' . esc_html__('Manage booking', 'hotel-booking') . '</h2>'
            . '<p>' . esc_html__('Invalid booking link. Please use the link from your email.', 'hotel-booking') . '</p>'
            . '</div>';
    }

    $post = get_post($booking_id);
    if (!$post || 'booking' !== $post->post_type)
    {
        return '<div style="font-family:Segoe UI,Arial,sans-serif;max-width:720px;margin:40px auto;padding:0 16px;">'
            . '<h2>' . esc_html__('Manage booking', 'hotel-booking') . '</h2>'
            . '<p>' . esc_html__('Booking not found.', 'hotel-booking') . '</p>'
            . '</div>';
    }

    $stored = (string) get_post_meta($booking_id, '_booking_manage_token', true);
    if (!$stored || !hash_equals($stored, $token))
    {
        return '<div style="font-family:Segoe UI,Arial,sans-serif;max-width:720px;margin:40px auto;padding:0 16px;">'
            . '<h2>' . esc_html__('Manage booking', 'hotel-booking') . '</h2>'
            . '<p>' . esc_html__('This link is invalid or expired. Please contact the hotel.', 'hotel-booking') . '</p>'
            . '</div>';
    }

    $status       = hotel_booking_get_booking_status($booking_id);
    $status_label = hotel_booking_booking_statuses()[$status] ?? $status;

    $room_id    = (int) get_post_meta($booking_id, '_booking_room_id', true);
    $room_title = $room_id ? get_the_title($room_id) : '—';
    $check_in   = (string) get_post_meta($booking_id, '_booking_check_in', true);
    $check_out  = (string) get_post_meta($booking_id, '_booking_check_out', true);
    $guests     = (string) get_post_meta($booking_id, '_booking_guests', true);
    $name       = (string) get_post_meta($booking_id, '_booking_name', true);
    $email      = (string) get_post_meta($booking_id, '_booking_email', true);

    $notice_html = '';
    if ($message)
    {
        $notice_html = '<div style="background:#f0f7ff;border:1px solid #cfe4ff;color:#0b2e59;padding:10px 12px;border-radius:8px;margin:16px 0;">'
            . esc_html($message) . '</div>';
    }

    $cancel_disabled = ('cancelled' === $status);

    $html  = '<div style="font-family:Segoe UI,Arial,sans-serif;max-width:860px;margin:40px auto;padding:0 16px;">';
    $html .= '<h1 style="margin:0 0 8px;">' . esc_html__('Manage your booking', 'hotel-booking') . '</h1>';
    $html .= '<p style="margin:0 0 16px;color:#444;">' . sprintf(esc_html__('Booking #%d', 'hotel-booking'), (int) $booking_id) . '</p>';
    $html .= $notice_html;

    $html .= '<table style="border-collapse:collapse;width:100%;max-width:720px;margin:0 0 18px;">';
    $rows = array(
        array(__('Status', 'hotel-booking'), $status_label),
        array(__('Room', 'hotel-booking'), $room_title),
        array(__('Check-in', 'hotel-booking'), $check_in ? $check_in : '—'),
        array(__('Check-out', 'hotel-booking'), $check_out ? $check_out : '—'),
        array(__('Guests', 'hotel-booking'), $guests ? $guests : '—'),
        array(__('Name', 'hotel-booking'), $name ? $name : '—'),
        array(__('Email', 'hotel-booking'), $email ? $email : '—'),
    );
    foreach ($rows as $row)
    {
        $html .= '<tr>'
            . '<td style="padding:8px 10px;border:1px solid #e5e5e5;background:#fafafa;"><strong>' . esc_html($row[0]) . '</strong></td>'
            . '<td style="padding:8px 10px;border:1px solid #e5e5e5;">' . esc_html($row[1]) . '</td>'
            . '</tr>';
    }
    $html .= '</table>';

    $html .= '<h2 style="margin:22px 0 10px;font-size:18px;">' . esc_html__('Cancel booking', 'hotel-booking') . '</h2>';
    $html .= '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
    $html .= '<input type="hidden" name="action" value="hotel_booking_manage_cancel" />';
    $html .= '<input type="hidden" name="booking_id" value="' . esc_attr((string) $booking_id) . '" />';
    $html .= '<input type="hidden" name="token" value="' . esc_attr($token) . '" />';
    $html .= wp_nonce_field('hotel_booking_manage_' . $booking_id, 'hotel_booking_manage_nonce', true, false);
    $html .= '<button type="submit" ' . ($cancel_disabled ? 'disabled' : '') . ' style="padding:10px 14px;border-radius:10px;border:1px solid #b33;background:#c0392b;color:#fff;cursor:pointer;">'
        . esc_html__('Cancel booking', 'hotel-booking') . '</button>';
    if ($cancel_disabled)
    {
        $html .= '<p style="margin:8px 0 0;color:#666;">' . esc_html__('This booking is already cancelled.', 'hotel-booking') . '</p>';
    }
    $html .= '</form>';

    $html .= '<h2 style="margin:26px 0 10px;font-size:18px;">' . esc_html__('Request date change', 'hotel-booking') . '</h2>';
    $html .= '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
    $html .= '<input type="hidden" name="action" value="hotel_booking_manage_change_dates" />';
    $html .= '<input type="hidden" name="booking_id" value="' . esc_attr((string) $booking_id) . '" />';
    $html .= '<input type="hidden" name="token" value="' . esc_attr($token) . '" />';
    $html .= wp_nonce_field('hotel_booking_manage_' . $booking_id, 'hotel_booking_manage_nonce', true, false);
    $html .= '<p style="margin:0 0 10px;color:#444;">' . esc_html__('Submit new dates. The hotel will review and confirm.', 'hotel-booking') . '</p>';
    $html .= '<div style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;max-width:720px;">';
    $html .= '<label style="display:block;">' . esc_html__('New check-in', 'hotel-booking') . '<br>'
        . '<input type="date" name="new_check_in" value="" required style="padding:9px 10px;border:1px solid #ddd;border-radius:10px;" /></label>';
    $html .= '<label style="display:block;">' . esc_html__('New check-out', 'hotel-booking') . '<br>'
        . '<input type="date" name="new_check_out" value="" required style="padding:9px 10px;border:1px solid #ddd;border-radius:10px;" /></label>';
    $html .= '<button type="submit" style="padding:10px 14px;border-radius:10px;border:1px solid #333;background:#222;color:#fff;cursor:pointer;">'
        . esc_html__('Request change', 'hotel-booking') . '</button>';
    $html .= '</div>';
    $html .= '</form>';

    $html .= '</div>';

    return $html;
}

function hotel_booking_manage_booking_shortcode()
{
    $booking_id = isset($_GET['booking_id']) ? absint($_GET['booking_id']) : 0;
    $token      = isset($_GET['token']) ? sanitize_text_field(wp_unslash($_GET['token'])) : '';
    $msg_key    = isset($_GET['hotel_booking_msg']) ? sanitize_text_field(wp_unslash($_GET['hotel_booking_msg'])) : '';

    $messages = array(
        'cancelled' => __('Your booking was cancelled.', 'hotel-booking'),
        'change_requested' => __('Your date change request was sent. We will contact you soon.', 'hotel-booking'),
        'error' => __('Something went wrong. Please try again.', 'hotel-booking'),
    );

    $message = $messages[$msg_key] ?? '';

    return hotel_booking_manage_booking_render($booking_id, $token, $message);
}
add_shortcode('hotel_booking_manage', 'hotel_booking_manage_booking_shortcode');

function hotel_booking_manage_booking_template_redirect()
{
    if (!isset($_GET['hotel_booking_manage']))
    {
        return;
    }

    $booking_id = isset($_GET['booking_id']) ? absint($_GET['booking_id']) : 0;
    $token      = isset($_GET['token']) ? sanitize_text_field(wp_unslash($_GET['token'])) : '';
    $msg_key    = isset($_GET['hotel_booking_msg']) ? sanitize_text_field(wp_unslash($_GET['hotel_booking_msg'])) : '';

    $messages = array(
        'cancelled' => __('Your booking was cancelled.', 'hotel-booking'),
        'change_requested' => __('Your date change request was sent. We will contact you soon.', 'hotel-booking'),
        'error' => __('Something went wrong. Please try again.', 'hotel-booking'),
    );

    $message = $messages[$msg_key] ?? '';

    status_header(200);
    nocache_headers();

    echo '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
    echo '<title>' . esc_html__('Manage booking', 'hotel-booking') . '</title></head><body style="margin:0;background:#fff;">';
    echo hotel_booking_manage_booking_render($booking_id, $token, $message);
    echo '</body></html>';
    exit;
}
add_action('template_redirect', 'hotel_booking_manage_booking_template_redirect', 1);

function hotel_booking_booking_belongs_to_current_user($booking_id)
{
    $booking_id = absint($booking_id);
    if (!$booking_id || !is_user_logged_in())
    {
        return false;
    }

    $user = wp_get_current_user();
    if (!$user || empty($user->user_email))
    {
        return false;
    }

    $post = get_post($booking_id);
    if (!$post || 'booking' !== $post->post_type)
    {
        return false;
    }

    // Prefer explicit ownership if set.
    $owner_id = (int) get_post_meta($booking_id, '_booking_user_id', true);
    if ($owner_id)
    {
        return $owner_id === (int) $user->ID;
    }

    // Fallback: match by customer email used during booking.
    $booking_email = (string) get_post_meta($booking_id, '_booking_email', true);
    if ($booking_email && strtolower(trim($booking_email)) === strtolower(trim((string) $user->user_email)))
    {
        // Cache ownership for future checks.
        update_post_meta($booking_id, '_booking_user_id', (int) $user->ID);
        return true;
    }

    // Last resort: post author.
    if ((int) $post->post_author && (int) $post->post_author === (int) $user->ID)
    {
        update_post_meta($booking_id, '_booking_user_id', (int) $user->ID);
        return true;
    }

    return false;
}

function hotel_booking_my_bookings_shortcode()
{
    if (!is_user_logged_in())
    {
        $redirect = (is_ssl() ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $login_url = wp_login_url($redirect);
        $register_url = wp_registration_url();

        return '<div style="font-family:Segoe UI,Arial,sans-serif;max-width:980px;margin:40px auto;padding:0 16px;">'
            . '<h2 style="margin:0 0 10px;">' . esc_html__('My bookings', 'hotel-booking') . '</h2>'
            . '<p style="margin:0 0 10px;color:#444;">' . esc_html__('Please log in to view and manage your bookings.', 'hotel-booking') . '</p>'
            . '<p style="margin:0;">'
            . '<a href="' . esc_url($login_url) . '" style="display:inline-block;margin-right:10px;">' . esc_html__('Log in', 'hotel-booking') . '</a>'
            . '<a href="' . esc_url($register_url) . '">' . esc_html__('Register', 'hotel-booking') . '</a>'
            . '</p>'
            . '</div>';
    }

    $user = wp_get_current_user();
    $email = (string) ($user->user_email ?? '');

    $booking_ids_by_email = array();
    if ($email)
    {
        $booking_ids_by_email = get_posts(array(
            'post_type' => 'booking',
            'post_status' => array('publish', 'trash'),
            'fields' => 'ids',
            'numberposts' => 200,
            'meta_query' => array(
                array(
                    'key' => '_booking_email',
                    'value' => $email,
                    'compare' => '=',
                ),
            ),
        ));
    }

    $booking_ids_by_owner = get_posts(array(
        'post_type' => 'booking',
        'post_status' => array('publish', 'trash'),
        'fields' => 'ids',
        'numberposts' => 200,
        'meta_query' => array(
            array(
                'key' => '_booking_user_id',
                'value' => (string) (int) $user->ID,
                'compare' => '=',
            ),
        ),
    ));

    $booking_ids = array_values(array_unique(array_merge($booking_ids_by_email ?: array(), $booking_ids_by_owner ?: array())));
    rsort($booking_ids);

    $html  = '<div style="font-family:Segoe UI,Arial,sans-serif;max-width:980px;margin:40px auto;padding:0 16px;">';
    $html .= '<h2 style="margin:0 0 12px;">' . esc_html__('My bookings', 'hotel-booking') . '</h2>';

    if (!$booking_ids)
    {
        $html .= '<p style="margin:0;color:#444;">' . esc_html__('No bookings found for your account email yet.', 'hotel-booking') . '</p>';
        $html .= '</div>';
        return $html;
    }

    $msg_key = isset($_GET['hotel_booking_account_msg']) ? sanitize_text_field(wp_unslash($_GET['hotel_booking_account_msg'])) : '';
    if ('cancelled' === $msg_key)
    {
        $html .= '<div style="background:#f0fff4;border:1px solid #b8f1c6;color:#064e1f;padding:10px 12px;border-radius:8px;margin:0 0 14px;">'
            . esc_html__('Booking cancelled successfully.', 'hotel-booking') . '</div>';
    }
    elseif ('change_requested' === $msg_key)
    {
        $html .= '<div style="background:#f0f7ff;border:1px solid #cfe4ff;color:#0b2e59;padding:10px 12px;border-radius:8px;margin:0 0 14px;">'
            . esc_html__('Date change request sent. The hotel will review and confirm.', 'hotel-booking') . '</div>';
    }
    elseif ('error' === $msg_key)
    {
        $html .= '<div style="background:#fff5f5;border:1px solid #ffc9c9;color:#7a0b0b;padding:10px 12px;border-radius:8px;margin:0 0 14px;">'
            . esc_html__('Something went wrong. Please try again.', 'hotel-booking') . '</div>';
    }

    $html .= '<table style="border-collapse:collapse;width:100%;max-width:980px;">';
    $html .= '<thead><tr>'
        . '<th style="text-align:left;padding:10px;border-bottom:1px solid #e5e5e5;">' . esc_html__('Booking', 'hotel-booking') . '</th>'
        . '<th style="text-align:left;padding:10px;border-bottom:1px solid #e5e5e5;">' . esc_html__('Room', 'hotel-booking') . '</th>'
        . '<th style="text-align:left;padding:10px;border-bottom:1px solid #e5e5e5;">' . esc_html__('Dates', 'hotel-booking') . '</th>'
        . '<th style="text-align:left;padding:10px;border-bottom:1px solid #e5e5e5;">' . esc_html__('Guests', 'hotel-booking') . '</th>'
        . '<th style="text-align:left;padding:10px;border-bottom:1px solid #e5e5e5;">' . esc_html__('Status', 'hotel-booking') . '</th>'
        . '<th style="text-align:left;padding:10px;border-bottom:1px solid #e5e5e5;">' . esc_html__('Actions', 'hotel-booking') . '</th>'
        . '</tr></thead><tbody>';

    foreach ($booking_ids as $booking_id)
    {
        $booking_id = absint($booking_id);
        if (!$booking_id)
        {
            continue;
        }

        // Ensure the current user can manage this booking.
        if (!hotel_booking_booking_belongs_to_current_user($booking_id))
        {
            continue;
        }

        $status = hotel_booking_get_booking_status($booking_id);
        $status_label = hotel_booking_booking_statuses()[$status] ?? $status;

        $room_id = (int) get_post_meta($booking_id, '_booking_room_id', true);
        $room_title = $room_id ? get_the_title($room_id) : '—';
        $check_in = (string) get_post_meta($booking_id, '_booking_check_in', true);
        $check_out = (string) get_post_meta($booking_id, '_booking_check_out', true);
        $guests = (string) get_post_meta($booking_id, '_booking_guests', true);

        $cancel_disabled = ('cancelled' === $status);

        $html .= '<tr>';
        $html .= '<td style="padding:10px;border-bottom:1px solid #f0f0f0;"><strong>#' . esc_html((string) $booking_id) . '</strong></td>';
        $html .= '<td style="padding:10px;border-bottom:1px solid #f0f0f0;">' . esc_html($room_title) . '</td>';
        $html .= '<td style="padding:10px;border-bottom:1px solid #f0f0f0;">' . esc_html(($check_in ? $check_in : '—') . ' → ' . ($check_out ? $check_out : '—')) . '</td>';
        $html .= '<td style="padding:10px;border-bottom:1px solid #f0f0f0;">' . esc_html($guests ? $guests : '—') . '</td>';
        $html .= '<td style="padding:10px;border-bottom:1px solid #f0f0f0;">' . esc_html($status_label) . '</td>';

        $html .= '<td style="padding:10px;border-bottom:1px solid #f0f0f0;">';
        // Cancel.
        $html .= '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" style="display:inline-block;margin-right:10px;">'
            . '<input type="hidden" name="action" value="hotel_booking_account_cancel" />'
            . '<input type="hidden" name="booking_id" value="' . esc_attr((string) $booking_id) . '" />'
            . wp_nonce_field('hotel_booking_account_' . $booking_id, 'hotel_booking_account_nonce', true, false)
            . '<button type="submit" ' . ($cancel_disabled ? 'disabled' : '') . ' style="padding:8px 10px;border-radius:10px;border:1px solid #b33;background:#c0392b;color:#fff;cursor:pointer;">'
            . esc_html__('Cancel', 'hotel-booking') . '</button>'
            . '</form>';

        // Date change.
        $html .= '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" style="display:inline-block;">'
            . '<input type="hidden" name="action" value="hotel_booking_account_change_dates" />'
            . '<input type="hidden" name="booking_id" value="' . esc_attr((string) $booking_id) . '" />'
            . wp_nonce_field('hotel_booking_account_' . $booking_id, 'hotel_booking_account_nonce', true, false)
            . '<input type="date" name="new_check_in" required style="padding:7px 8px;border:1px solid #ddd;border-radius:10px;margin-right:6px;" />'
            . '<input type="date" name="new_check_out" required style="padding:7px 8px;border:1px solid #ddd;border-radius:10px;margin-right:6px;" />'
            . '<button type="submit" style="padding:8px 10px;border-radius:10px;border:1px solid #333;background:#222;color:#fff;cursor:pointer;">'
            . esc_html__('Change dates', 'hotel-booking') . '</button>'
            . '</form>';

        $html .= '</td>';
        $html .= '</tr>';
    }

    $html .= '</tbody></table>';
    $html .= '</div>';

    return $html;
}
add_shortcode('hotel_booking_my_bookings', 'hotel_booking_my_bookings_shortcode');

function hotel_booking_manage_booking_validate($booking_id, $token, $nonce)
{
    $booking_id = absint($booking_id);
    $token      = is_string($token) ? trim($token) : '';
    $nonce      = is_string($nonce) ? trim($nonce) : '';

    if (!$booking_id || !$token || !$nonce)
    {
        return new WP_Error('hotel_booking_manage_invalid', __('Invalid request.', 'hotel-booking'));
    }

    if (!wp_verify_nonce($nonce, 'hotel_booking_manage_' . $booking_id))
    {
        return new WP_Error('hotel_booking_manage_nonce', __('Invalid request (nonce).', 'hotel-booking'));
    }

    $post = get_post($booking_id);
    if (!$post || 'booking' !== $post->post_type)
    {
        return new WP_Error('hotel_booking_manage_not_found', __('Booking not found.', 'hotel-booking'));
    }

    $stored = (string) get_post_meta($booking_id, '_booking_manage_token', true);
    if (!$stored || !hash_equals($stored, $token))
    {
        return new WP_Error('hotel_booking_manage_token', __('Invalid booking link.', 'hotel-booking'));
    }

    return true;
}

function hotel_booking_manage_booking_redirect($booking_id, $token, $msg_key)
{
    $url = add_query_arg(
        array(
            'hotel_booking_manage' => 1,
            'booking_id' => (int) $booking_id,
            'token' => rawurlencode((string) $token),
            'hotel_booking_msg' => sanitize_key((string) $msg_key),
        ),
        home_url('/')
    );

    wp_safe_redirect($url);
    exit;
}

function hotel_booking_manage_cancel_handler()
{
    $booking_id = isset($_POST['booking_id']) ? absint($_POST['booking_id']) : 0;
    $token      = isset($_POST['token']) ? sanitize_text_field(wp_unslash($_POST['token'])) : '';
    $nonce      = isset($_POST['hotel_booking_manage_nonce']) ? sanitize_text_field(wp_unslash($_POST['hotel_booking_manage_nonce'])) : '';

    $valid = hotel_booking_manage_booking_validate($booking_id, $token, $nonce);
    if (is_wp_error($valid))
    {
        hotel_booking_manage_booking_redirect($booking_id, $token, 'error');
    }

    $status = hotel_booking_get_booking_status($booking_id);
    if ('cancelled' !== $status)
    {
        hotel_booking_set_booking_status($booking_id, 'cancelled', 'customer_manage_cancel');
    }

    hotel_booking_manage_booking_redirect($booking_id, $token, 'cancelled');
}
add_action('admin_post_nopriv_hotel_booking_manage_cancel', 'hotel_booking_manage_cancel_handler');
add_action('admin_post_hotel_booking_manage_cancel', 'hotel_booking_manage_cancel_handler');

function hotel_booking_build_change_request_email_html($booking_id, $new_check_in, $new_check_out, $recipient = 'customer')
{
    $booking_id  = absint($booking_id);
    $brand       = hotel_booking_brand_name();
    $name        = (string) get_post_meta($booking_id, '_booking_name', true);
    $check_in    = (string) get_post_meta($booking_id, '_booking_check_in', true);
    $check_out   = (string) get_post_meta($booking_id, '_booking_check_out', true);
    $room_id     = (int) get_post_meta($booking_id, '_booking_room_id', true);
    $room_title  = $room_id ? get_the_title($room_id) : '—';

    $greeting = '';
    if ('customer' === $recipient && $name)
    {
        $greeting = '<p style="margin:0 0 14px;">' . sprintf(esc_html__('Hi %s,', 'hotel-booking'), esc_html($name)) . '</p>';
    }
    elseif ('admin' === $recipient)
    {
        $greeting = '<p style="margin:0 0 14px;">' . esc_html__('Customer requested a date change:', 'hotel-booking') . '</p>';
    }

    $rows = '';
    $lines = array(
        array(__('Booking ID', 'hotel-booking'), '#' . $booking_id),
        array(__('Room', 'hotel-booking'), $room_title),
        array(__('Current check-in', 'hotel-booking'), $check_in ? $check_in : '—'),
        array(__('Current check-out', 'hotel-booking'), $check_out ? $check_out : '—'),
        array(__('Requested check-in', 'hotel-booking'), $new_check_in),
        array(__('Requested check-out', 'hotel-booking'), $new_check_out),
    );

    foreach ($lines as $line)
    {
        $rows .= '<tr><td style="padding:6px 10px;border:1px solid #e5e5e5;"><strong>' . esc_html($line[0]) . '</strong></td><td style="padding:6px 10px;border:1px solid #e5e5e5;">' . esc_html($line[1]) . '</td></tr>';
    }

    return '<div style="font-family:Segoe UI,Arial,sans-serif;color:#1e1a16;line-height:1.6;">'
        . '<div style="margin:0 0 14px;"><strong style="font-size:16px;">' . esc_html($brand) . '</strong></div>'
        . '<h2 style="margin:0 0 12px;">' . esc_html__('Date change request', 'hotel-booking') . '</h2>'
        . $greeting
        . '<table style="border-collapse:collapse;width:100%;max-width:620px;">' . $rows . '</table>'
        . '</div>';
}

function hotel_booking_manage_change_dates_handler()
{
    $booking_id    = isset($_POST['booking_id']) ? absint($_POST['booking_id']) : 0;
    $token         = isset($_POST['token']) ? sanitize_text_field(wp_unslash($_POST['token'])) : '';
    $nonce         = isset($_POST['hotel_booking_manage_nonce']) ? sanitize_text_field(wp_unslash($_POST['hotel_booking_manage_nonce'])) : '';
    $new_check_in  = isset($_POST['new_check_in']) ? sanitize_text_field(wp_unslash($_POST['new_check_in'])) : '';
    $new_check_out = isset($_POST['new_check_out']) ? sanitize_text_field(wp_unslash($_POST['new_check_out'])) : '';

    $valid = hotel_booking_manage_booking_validate($booking_id, $token, $nonce);
    if (is_wp_error($valid))
    {
        hotel_booking_manage_booking_redirect($booking_id, $token, 'error');
    }

    $new_check_in  = hotel_booking_bookings_table_parse_date($new_check_in);
    $new_check_out = hotel_booking_bookings_table_parse_date($new_check_out);

    if (!$new_check_in || !$new_check_out || $new_check_out <= $new_check_in)
    {
        hotel_booking_manage_booking_redirect($booking_id, $token, 'error');
    }

    $request = array(
        'new_check_in' => $new_check_in,
        'new_check_out' => $new_check_out,
        'requested_at' => current_time('mysql'),
    );
    update_post_meta($booking_id, '_booking_change_request', $request);

    // Notify admin + acknowledge customer.
    $customer_email = (string) get_post_meta($booking_id, '_booking_email', true);
    $admin_email    = (string) get_option('admin_email');
    $name           = (string) get_post_meta($booking_id, '_booking_name', true);

    hotel_booking_send_booking_email(
        $admin_email,
        $name
            ? sprintf(__('Date change requested: %s #%d', 'hotel-booking'), $name, (int) $booking_id)
            : sprintf(__('Date change requested #%d', 'hotel-booking'), (int) $booking_id),
        hotel_booking_build_change_request_email_html($booking_id, $new_check_in, $new_check_out, 'admin')
    );

    hotel_booking_send_booking_email(
        $customer_email,
        $name
            ? sprintf(__('Hi %s, we received your date change request (#%d)', 'hotel-booking'), $name, (int) $booking_id)
            : sprintf(__('We received your date change request (#%d)', 'hotel-booking'), (int) $booking_id),
        hotel_booking_build_change_request_email_html($booking_id, $new_check_in, $new_check_out, 'customer')
    );

    hotel_booking_manage_booking_redirect($booking_id, $token, 'change_requested');
}
add_action('admin_post_nopriv_hotel_booking_manage_change_dates', 'hotel_booking_manage_change_dates_handler');
add_action('admin_post_hotel_booking_manage_change_dates', 'hotel_booking_manage_change_dates_handler');

function hotel_booking_account_redirect_with_msg($msg_key)
{
    $url = add_query_arg(
        array(
            'hotel_booking_account_msg' => sanitize_key((string) $msg_key),
        ),
        wp_get_referer() ? wp_get_referer() : home_url('/')
    );

    wp_safe_redirect($url);
    exit;
}

function hotel_booking_account_cancel_handler()
{
    if (!is_user_logged_in())
    {
        wp_die(__('Not allowed.', 'hotel-booking'));
    }

    $booking_id = isset($_POST['booking_id']) ? absint($_POST['booking_id']) : 0;
    $nonce      = isset($_POST['hotel_booking_account_nonce']) ? sanitize_text_field(wp_unslash($_POST['hotel_booking_account_nonce'])) : '';

    if (!$booking_id || !$nonce || !wp_verify_nonce($nonce, 'hotel_booking_account_' . $booking_id))
    {
        hotel_booking_account_redirect_with_msg('error');
    }

    if (!hotel_booking_booking_belongs_to_current_user($booking_id))
    {
        hotel_booking_account_redirect_with_msg('error');
    }

    $status = hotel_booking_get_booking_status($booking_id);
    if ('cancelled' !== $status)
    {
        hotel_booking_set_booking_status($booking_id, 'cancelled', 'customer_account_cancel');
    }

    hotel_booking_account_redirect_with_msg('cancelled');
}
add_action('admin_post_hotel_booking_account_cancel', 'hotel_booking_account_cancel_handler');

function hotel_booking_account_change_dates_handler()
{
    if (!is_user_logged_in())
    {
        wp_die(__('Not allowed.', 'hotel-booking'));
    }

    $booking_id    = isset($_POST['booking_id']) ? absint($_POST['booking_id']) : 0;
    $nonce         = isset($_POST['hotel_booking_account_nonce']) ? sanitize_text_field(wp_unslash($_POST['hotel_booking_account_nonce'])) : '';
    $new_check_in  = isset($_POST['new_check_in']) ? sanitize_text_field(wp_unslash($_POST['new_check_in'])) : '';
    $new_check_out = isset($_POST['new_check_out']) ? sanitize_text_field(wp_unslash($_POST['new_check_out'])) : '';

    if (!$booking_id || !$nonce || !wp_verify_nonce($nonce, 'hotel_booking_account_' . $booking_id))
    {
        hotel_booking_account_redirect_with_msg('error');
    }

    if (!hotel_booking_booking_belongs_to_current_user($booking_id))
    {
        hotel_booking_account_redirect_with_msg('error');
    }

    $new_check_in  = hotel_booking_bookings_table_parse_date($new_check_in);
    $new_check_out = hotel_booking_bookings_table_parse_date($new_check_out);

    if (!$new_check_in || !$new_check_out || $new_check_out <= $new_check_in)
    {
        hotel_booking_account_redirect_with_msg('error');
    }

    $request = array(
        'new_check_in' => $new_check_in,
        'new_check_out' => $new_check_out,
        'requested_at' => current_time('mysql'),
        'requested_by_user_id' => (int) get_current_user_id(),
    );
    update_post_meta($booking_id, '_booking_change_request', $request);

    $customer_email = (string) get_post_meta($booking_id, '_booking_email', true);
    $admin_email    = (string) get_option('admin_email');
    $name           = (string) get_post_meta($booking_id, '_booking_name', true);

    hotel_booking_send_booking_email(
        $admin_email,
        $name
            ? sprintf(__('Date change requested: %s #%d', 'hotel-booking'), $name, (int) $booking_id)
            : sprintf(__('Date change requested #%d', 'hotel-booking'), (int) $booking_id),
        hotel_booking_build_change_request_email_html($booking_id, $new_check_in, $new_check_out, 'admin')
    );

    hotel_booking_send_booking_email(
        $customer_email,
        $name
            ? sprintf(__('Hi %s, we received your date change request (#%d)', 'hotel-booking'), $name, (int) $booking_id)
            : sprintf(__('We received your date change request (#%d)', 'hotel-booking'), (int) $booking_id),
        hotel_booking_build_change_request_email_html($booking_id, $new_check_in, $new_check_out, 'customer')
    );

    hotel_booking_account_redirect_with_msg('change_requested');
}
add_action('admin_post_hotel_booking_account_change_dates', 'hotel_booking_account_change_dates_handler');

function hotel_booking_booking_send_status_change_emails($booking_id, $old_status, $new_status, $source)
{
    $customer_email = (string) get_post_meta($booking_id, '_booking_email', true);
    $admin_email    = (string) get_option('admin_email');
    $name           = (string) get_post_meta($booking_id, '_booking_name', true);
    $room_id        = (int) get_post_meta($booking_id, '_booking_room_id', true);
    $room_title     = $room_id ? get_the_title($room_id) : '';

    $actor_html = '';
    if ('customer_manage_cancel' === $source)
    {
        $actor_html = '<p style="margin:14px 0 0;color:#444;">' . esc_html__('Action: Cancelled by customer', 'hotel-booking') . '</p>';
    }
    elseif ('customer_account_cancel' === $source)
    {
        $actor_html = '<p style="margin:14px 0 0;color:#444;">' . esc_html__('Action: Cancelled by customer (account)', 'hotel-booking') . '</p>';
    }
    else
    {
        $changed_by = (int) get_post_meta($booking_id, '_booking_status_changed_by', true);
        if ($changed_by)
        {
            $user = get_user_by('id', $changed_by);
            if ($user && !empty($user->display_name))
            {
                $actor_html = '<p style="margin:14px 0 0;color:#444;">' . sprintf(
                    esc_html__('Updated by: %s', 'hotel-booking'),
                    esc_html($user->display_name)
                ) . '</p>';
            }
        }
    }

    if ('confirmed' === $new_status)
    {
        $customer_subject = $name
            ? sprintf(__('Hi %s, your booking is confirmed (#%d)', 'hotel-booking'), $name, (int) $booking_id)
            : sprintf(__('Your booking is confirmed (#%d)', 'hotel-booking'), (int) $booking_id);

        $admin_subject = $room_title && $name
            ? sprintf(__('Booking confirmed: %s (%s) #%d', 'hotel-booking'), $name, $room_title, (int) $booking_id)
            : sprintf(__('Booking confirmed #%d', 'hotel-booking'), (int) $booking_id);

        hotel_booking_send_booking_email(
            $customer_email,
            $customer_subject,
            hotel_booking_build_booking_email_html($booking_id, __('Booking confirmed', 'hotel-booking'), 'customer') . $actor_html
        );
        hotel_booking_send_booking_email(
            $admin_email,
            $admin_subject,
            hotel_booking_build_booking_email_html($booking_id, __('Booking confirmed', 'hotel-booking'), 'admin') . $actor_html
        );
        do_action('hotel_booking_booking_sms_notification', 'booking_confirmed', $booking_id);
        return;
    }

    if ('cancelled' === $new_status)
    {
        $customer_subject = $name
            ? sprintf(__('Hi %s, your booking was cancelled (#%d)', 'hotel-booking'), $name, (int) $booking_id)
            : sprintf(__('Your booking was cancelled (#%d)', 'hotel-booking'), (int) $booking_id);

        $admin_subject = $room_title && $name
            ? sprintf(__('Booking cancelled: %s (%s) #%d', 'hotel-booking'), $name, $room_title, (int) $booking_id)
            : sprintf(__('Booking cancelled #%d', 'hotel-booking'), (int) $booking_id);

        hotel_booking_send_booking_email(
            $customer_email,
            $customer_subject,
            hotel_booking_build_booking_email_html($booking_id, __('Booking cancelled', 'hotel-booking'), 'customer') . $actor_html
        );
        hotel_booking_send_booking_email(
            $admin_email,
            $admin_subject,
            hotel_booking_build_booking_email_html($booking_id, __('Booking cancelled', 'hotel-booking'), 'admin') . $actor_html
        );
        do_action('hotel_booking_booking_sms_notification', 'booking_cancelled', $booking_id);
    }
}
add_action('hotel_booking_booking_status_changed', 'hotel_booking_booking_send_status_change_emails', 10, 4);

function hotel_booking_booking_sync_status_on_trash($new_status, $old_status, $post)
{
    if (!$post || 'booking' !== $post->post_type)
    {
        return;
    }

    if ('trash' === $new_status && 'trash' !== $old_status)
    {
        hotel_booking_set_booking_status($post->ID, 'cancelled', 'trash');
    }
}
add_action('transition_post_status', 'hotel_booking_booking_sync_status_on_trash', 10, 3);

function hotel_booking_booking_add_status_column($columns)
{
    if (!isset($columns['booking_status']))
    {
        $columns['booking_status'] = __('Status', 'hotel-booking');
    }

    return $columns;
}
add_filter('manage_booking_posts_columns', 'hotel_booking_booking_add_status_column', 30);

function hotel_booking_booking_render_status_column($column, $post_id)
{
    if ('booking_status' !== $column)
    {
        return;
    }

    $status = hotel_booking_get_booking_status($post_id);
    $label  = hotel_booking_booking_statuses()[$status] ?? $status;
    echo esc_html($label);
}
add_action('manage_booking_posts_custom_column', 'hotel_booking_booking_render_status_column', 30, 2);

function hotel_booking_booking_row_actions($actions, $post)
{
    if (!$post || 'booking' !== $post->post_type || !current_user_can('edit_post', $post->ID))
    {
        return $actions;
    }

    $status = hotel_booking_get_booking_status($post->ID);
    $base   = admin_url('admin-post.php?action=hotel_booking_set_booking_status&booking_id=' . (int) $post->ID);

    $actions['booking_confirm'] = '<a href="' . esc_url(wp_nonce_url($base . '&status=confirmed', 'hotel_booking_set_status_' . $post->ID)) . '">' . esc_html__('Confirm', 'hotel-booking') . '</a>';
    $actions['booking_cancel']  = '<a href="' . esc_url(wp_nonce_url($base . '&status=cancelled', 'hotel_booking_set_status_' . $post->ID)) . '">' . esc_html__('Cancel', 'hotel-booking') . '</a>';

    if ('new' !== $status)
    {
        $actions['booking_new'] = '<a href="' . esc_url(wp_nonce_url($base . '&status=new', 'hotel_booking_set_status_' . $post->ID)) . '">' . esc_html__('Mark New', 'hotel-booking') . '</a>';
    }

    return $actions;
}
add_filter('post_row_actions', 'hotel_booking_booking_row_actions', 10, 2);

function hotel_booking_booking_handle_status_action()
{
    $booking_id = isset($_GET['booking_id']) ? absint($_GET['booking_id']) : 0;
    $status     = isset($_GET['status']) ? sanitize_text_field(wp_unslash($_GET['status'])) : '';

    if (!$booking_id || !current_user_can('edit_post', $booking_id))
    {
        wp_die(__('Not allowed.', 'hotel-booking'));
    }

    check_admin_referer('hotel_booking_set_status_' . $booking_id);

    hotel_booking_set_booking_status($booking_id, $status, 'admin_action');

    wp_safe_redirect(admin_url('edit.php?post_type=booking'));
    exit;
}
add_action('admin_post_hotel_booking_set_booking_status', 'hotel_booking_booking_handle_status_action');

function hotel_booking_booking_status_metabox()
{
    add_meta_box(
        'hotel_booking_status_box',
        __('Booking Status', 'hotel-booking'),
        'hotel_booking_booking_status_metabox_html',
        'booking',
        'side',
        'high'
    );
}
add_action('add_meta_boxes_booking', 'hotel_booking_booking_status_metabox', 5);

function hotel_booking_booking_request_metabox()
{
    add_meta_box(
        'hotel_booking_request_box',
        __('Customer Requests', 'hotel-booking'),
        'hotel_booking_booking_request_metabox_html',
        'booking',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes_booking', 'hotel_booking_booking_request_metabox', 6);

function hotel_booking_booking_request_metabox_html($post)
{
    $request = get_post_meta($post->ID, '_booking_change_request', true);

    if (!is_array($request) || empty($request['new_check_in']) || empty($request['new_check_out']))
    {
        echo '<p style="margin:0;color:#666;">' . esc_html__('No customer requests yet.', 'hotel-booking') . '</p>';
        return;
    }

    $requested_at = isset($request['requested_at']) ? (string) $request['requested_at'] : '';

    echo '<p style="margin:0 0 8px;">' . esc_html__('Date change requested:', 'hotel-booking') . '</p>';
    echo '<table class="widefat striped" style="max-width:720px;">';
    echo '<tbody>';
    echo '<tr><th style="width:220px;">' . esc_html__('Requested check-in', 'hotel-booking') . '</th><td>' . esc_html((string) $request['new_check_in']) . '</td></tr>';
    echo '<tr><th>' . esc_html__('Requested check-out', 'hotel-booking') . '</th><td>' . esc_html((string) $request['new_check_out']) . '</td></tr>';
    if ($requested_at)
    {
        echo '<tr><th>' . esc_html__('Requested at', 'hotel-booking') . '</th><td>' . esc_html($requested_at) . '</td></tr>';
    }
    echo '</tbody></table>';

    $clear_url = admin_url('admin-post.php?action=hotel_booking_clear_change_request&booking_id=' . (int) $post->ID);
    echo '<p style="margin:10px 0 0;">'
        . '<a class="button" href="' . esc_url(wp_nonce_url($clear_url, 'hotel_booking_clear_change_request_' . $post->ID)) . '">'
        . esc_html__('Clear request', 'hotel-booking') . '</a>'
        . '</p>';
}

function hotel_booking_clear_change_request_handler()
{
    $booking_id = isset($_GET['booking_id']) ? absint($_GET['booking_id']) : 0;

    if (!$booking_id || !current_user_can('edit_post', $booking_id))
    {
        wp_die(__('Not allowed.', 'hotel-booking'));
    }

    check_admin_referer('hotel_booking_clear_change_request_' . $booking_id);

    delete_post_meta($booking_id, '_booking_change_request');

    wp_safe_redirect(get_edit_post_link($booking_id, ''));
    exit;
}
add_action('admin_post_hotel_booking_clear_change_request', 'hotel_booking_clear_change_request_handler');

function hotel_booking_booking_status_metabox_html($post)
{
    $current  = hotel_booking_get_booking_status($post->ID);
    $statuses = hotel_booking_booking_statuses();

    wp_nonce_field('hotel_booking_status_save', 'hotel_booking_status_nonce');

    echo '<p><label for="hotel_booking_status_field" class="screen-reader-text">' . esc_html__('Status', 'hotel-booking') . '</label>';
    echo '<select id="hotel_booking_status_field" name="hotel_booking_status_field" style="width:100%;">';
    foreach ($statuses as $value => $label)
    {
        printf(
            '<option value="%s"%s>%s</option>',
            esc_attr($value),
            selected($current, $value, false),
            esc_html($label)
        );
    }
    echo '</select></p>';
    echo '<p style="margin:0;color:#666;">' . esc_html__('Changing status can trigger customer emails (confirm/cancel).', 'hotel-booking') . '</p>';
}

function hotel_booking_booking_save_status_metabox($post_id)
{
    if (!isset($_POST['hotel_booking_status_nonce']) || !wp_verify_nonce($_POST['hotel_booking_status_nonce'], 'hotel_booking_status_save'))
    {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
    {
        return;
    }

    if (!current_user_can('edit_post', $post_id))
    {
        return;
    }

    $new_status = isset($_POST['hotel_booking_status_field']) ? sanitize_text_field(wp_unslash($_POST['hotel_booking_status_field'])) : '';
    if ($new_status)
    {
        hotel_booking_set_booking_status($post_id, $new_status, 'metabox');
    }
}
add_action('save_post_booking', 'hotel_booking_booking_save_status_metabox', 15);
