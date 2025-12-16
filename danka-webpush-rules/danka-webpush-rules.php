<?php
/**
 * Plugin Name: Danka WebPush Rules
 * Plugin URI: https://github.com/Matth-Ben/custom-pwa
 * Description: Manage Web Push notifications with customizable templates per post type and site-specific configurations
 * Version: 1.0.0
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * Author: Danka
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: danka-webpush-rules
 */

if (!defined('ABSPATH')) {
    exit;
}

define('DANKA_WEBPUSH_VERSION', '1.0.0');
define('DANKA_WEBPUSH_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('DANKA_WEBPUSH_PLUGIN_URL', plugin_dir_url(__FILE__));

class Danka_WebPush_Rules {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->init_hooks();
    }
    
    private function init_hooks() {
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);
        
        add_action('plugins_loaded', [$this, 'load_textdomain']);
        add_action('init', [$this, 'init']);
        add_action('rest_api_init', [$this, 'register_rest_routes']);
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_scripts']);
    }
    
    public function activate() {
        $this->create_tables();
        flush_rewrite_rules();
    }
    
    public function deactivate() {
        flush_rewrite_rules();
    }
    
    private function create_tables() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'danka_webpush_subscriptions';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            endpoint text NOT NULL,
            public_key varchar(255) DEFAULT NULL,
            auth_token varchar(255) DEFAULT NULL,
            user_agent text DEFAULT NULL,
            ip_address varchar(45) DEFAULT NULL,
            user_id bigint(20) unsigned DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY endpoint_index (endpoint(191)),
            KEY user_id_index (user_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    public function load_textdomain() {
        load_plugin_textdomain('danka-webpush-rules', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    public function init() {
        // Plugin initialization
    }
    
    public function register_rest_routes() {
        register_rest_route('danka-webpush/v1', '/subscribe', [
            'methods' => 'POST',
            'callback' => [$this, 'handle_subscribe'],
            'permission_callback' => '__return_true',
        ]);
        
        register_rest_route('danka-webpush/v1', '/unsubscribe', [
            'methods' => 'POST',
            'callback' => [$this, 'handle_unsubscribe'],
            'permission_callback' => '__return_true',
        ]);
    }
    
    public function handle_subscribe($request) {
        global $wpdb;
        
        $params = $request->get_json_params();
        
        if (empty($params['endpoint'])) {
            return new WP_Error('missing_endpoint', __('Endpoint is required', 'danka-webpush-rules'), ['status' => 400]);
        }
        
        $table_name = $wpdb->prefix . 'danka_webpush_subscriptions';
        
        // Check if subscription already exists
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_name WHERE endpoint = %s",
            $params['endpoint']
        ));
        
        if ($existing) {
            // Update existing subscription
            $wpdb->update(
                $table_name,
                [
                    'public_key' => isset($params['keys']['p256dh']) ? $params['keys']['p256dh'] : null,
                    'auth_token' => isset($params['keys']['auth']) ? $params['keys']['auth'] : null,
                    'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null,
                    'ip_address' => $this->get_client_ip(),
                    'user_id' => get_current_user_id() ?: null,
                ],
                ['id' => $existing],
                ['%s', '%s', '%s', '%s', '%d'],
                ['%d']
            );
            
            return new WP_REST_Response([
                'success' => true,
                'message' => __('Subscription updated successfully', 'danka-webpush-rules'),
                'id' => $existing
            ], 200);
        } else {
            // Insert new subscription
            $result = $wpdb->insert(
                $table_name,
                [
                    'endpoint' => $params['endpoint'],
                    'public_key' => isset($params['keys']['p256dh']) ? $params['keys']['p256dh'] : null,
                    'auth_token' => isset($params['keys']['auth']) ? $params['keys']['auth'] : null,
                    'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null,
                    'ip_address' => $this->get_client_ip(),
                    'user_id' => get_current_user_id() ?: null,
                ],
                ['%s', '%s', '%s', '%s', '%s', '%d']
            );
            
            if ($result) {
                return new WP_REST_Response([
                    'success' => true,
                    'message' => __('Subscription created successfully', 'danka-webpush-rules'),
                    'id' => $wpdb->insert_id
                ], 201);
            } else {
                return new WP_Error('db_error', __('Failed to save subscription', 'danka-webpush-rules'), ['status' => 500]);
            }
        }
    }
    
    public function handle_unsubscribe($request) {
        global $wpdb;
        
        $params = $request->get_json_params();
        
        if (empty($params['endpoint'])) {
            return new WP_Error('missing_endpoint', __('Endpoint is required', 'danka-webpush-rules'), ['status' => 400]);
        }
        
        $table_name = $wpdb->prefix . 'danka_webpush_subscriptions';
        
        $result = $wpdb->delete(
            $table_name,
            ['endpoint' => $params['endpoint']],
            ['%s']
        );
        
        if ($result) {
            return new WP_REST_Response([
                'success' => true,
                'message' => __('Subscription removed successfully', 'danka-webpush-rules')
            ], 200);
        } else {
            return new WP_REST_Response([
                'success' => false,
                'message' => __('Subscription not found', 'danka-webpush-rules')
            ], 404);
        }
    }
    
    private function get_client_ip() {
        $ip = '';
        
        // First check REMOTE_ADDR which is the most reliable
        if (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        
        // If behind a proxy, check forwarded headers (but validate them)
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $forwarded_ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $forwarded_ip = trim($forwarded_ips[0]);
            
            // Validate IP address format
            if (filter_var($forwarded_ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                $ip = $forwarded_ip;
            }
        }
        
        // Validate and sanitize the final IP
        $ip = filter_var($ip, FILTER_VALIDATE_IP);
        
        return $ip ?: '';
    }
    
    public function add_admin_menu() {
        add_menu_page(
            __('WebPush Rules', 'danka-webpush-rules'),
            __('WebPush Rules', 'danka-webpush-rules'),
            'manage_options',
            'danka-webpush-rules',
            [$this, 'render_admin_page'],
            'dashicons-megaphone',
            30
        );
    }
    
    public function render_admin_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Save settings
        if (isset($_POST['danka_webpush_save']) && check_admin_referer('danka_webpush_settings', 'danka_webpush_nonce')) {
            $this->save_settings($_POST);
            echo '<div class="notice notice-success is-dismissible"><p>' . __('Settings saved successfully.', 'danka-webpush-rules') . '</p></div>';
        }
        
        include DANKA_WEBPUSH_PLUGIN_DIR . 'templates/admin-page.php';
    }
    
    private function save_settings($post_data) {
        // Save site type
        if (isset($post_data['site_type'])) {
            update_option('danka_webpush_site_type', sanitize_text_field($post_data['site_type']));
        }
        
        // Save enabled post types
        $enabled_post_types = isset($post_data['enabled_post_types']) ? array_map('sanitize_text_field', $post_data['enabled_post_types']) : [];
        update_option('danka_webpush_enabled_post_types', $enabled_post_types);
        
        // Save templates for each post type
        if (isset($post_data['templates'])) {
            $templates = [];
            foreach ($post_data['templates'] as $post_type => $template_data) {
                $templates[sanitize_text_field($post_type)] = [
                    'title' => sanitize_text_field($template_data['title'] ?? ''),
                    'body' => sanitize_textarea_field($template_data['body'] ?? ''),
                    'url' => esc_url_raw($template_data['url'] ?? ''),
                ];
            }
            update_option('danka_webpush_templates', $templates);
        }
        
        // Save extra fields based on site type
        if (isset($post_data['extra_fields'])) {
            $extra_fields = [];
            foreach ($post_data['extra_fields'] as $key => $value) {
                $extra_fields[sanitize_text_field($key)] = sanitize_text_field($value);
            }
            update_option('danka_webpush_extra_fields', $extra_fields);
        }
    }
    
    public function enqueue_admin_scripts($hook) {
        if ('toplevel_page_danka-webpush-rules' !== $hook) {
            return;
        }
        
        wp_enqueue_style(
            'danka-webpush-admin',
            DANKA_WEBPUSH_PLUGIN_URL . 'assets/css/admin.css',
            [],
            DANKA_WEBPUSH_VERSION
        );
        
        wp_enqueue_script(
            'danka-webpush-admin',
            DANKA_WEBPUSH_PLUGIN_URL . 'assets/js/admin.js',
            ['jquery'],
            DANKA_WEBPUSH_VERSION,
            true
        );
    }
    
    public function enqueue_frontend_scripts() {
        wp_enqueue_script(
            'danka-webpush-frontend',
            DANKA_WEBPUSH_PLUGIN_URL . 'assets/js/frontend.js',
            [],
            DANKA_WEBPUSH_VERSION,
            true
        );
        
        wp_localize_script('danka-webpush-frontend', 'dankaWebPush', [
            'restUrl' => rest_url('danka-webpush/v1'),
            'swUrl' => DANKA_WEBPUSH_PLUGIN_URL . 'sw.js',
            'nonce' => wp_create_nonce('wp_rest'),
        ]);
    }
}

// Initialize the plugin
Danka_WebPush_Rules::get_instance();
