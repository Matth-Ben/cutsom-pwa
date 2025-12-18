<?php
/**
 * Plugin Name: Custom PWA
 * Plugin URI: https://example.com/custom-pwa
 * Description: Complete PWA configuration and Web Push notifications plugin for WordPress. Supports manifest generation, service worker examples, and push notifications for all post types including custom post types.
 * Version: 1.0.3
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: custom-pwa
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 8.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main plugin constants.
 */
define( 'CUSTOM_PWA_VERSION', '1.0.3' );
define( 'CUSTOM_PWA_PLUGIN_FILE', __FILE__ );
define( 'CUSTOM_PWA_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CUSTOM_PWA_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'CUSTOM_PWA_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Main plugin class.
 * 
 * Bootstraps the Custom PWA plugin, loads dependencies, and initializes all components.
 */
class Custom_PWA_Plugin {

	/**
	 * Single instance of the class.
	 *
	 * @var Custom_PWA_Plugin|null
	 */
	private static $instance = null;

	/**
	 * Admin menu instance.
	 *
	 * @var Custom_PWA_Admin_Menu|null
	 */
	private $admin_menu = null;

	/**
	 * PWA settings instance.
	 *
	 * @var Custom_PWA_Settings|null
	 */
	private $pwa_settings = null;

	/**
	 * Push settings instance.
	 *
	 * @var Custom_PWA_Push_Settings|null
	 */
	private $push_settings = null;

	/**
	 * Config settings instance.
	 *
	 * @var Custom_PWA_Config_Settings|null
	 */
	private $config_settings = null;

	/**
	 * Subscriptions instance.
	 *
	 * @var Custom_PWA_Subscriptions|null
	 */
	private $subscriptions = null;

	/**
	 * Dispatcher instance.
	 *
	 * @var Custom_PWA_Dispatcher|null
	 */
	private $dispatcher = null;

	/**
	 * Get the singleton instance.
	 *
	 * @return Custom_PWA_Plugin
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor to enforce singleton pattern.
	 */
	private function __construct() {
		$this->load_dependencies();
		$this->init_hooks();
	}

	/**
	 * Load required files and dependencies.
	 */
	private function load_dependencies() {
		require_once CUSTOM_PWA_PLUGIN_DIR . 'includes/class-admin-menu.php';
		require_once CUSTOM_PWA_PLUGIN_DIR . 'includes/class-config-settings.php';
		require_once CUSTOM_PWA_PLUGIN_DIR . 'includes/class-pwa-settings.php';
		require_once CUSTOM_PWA_PLUGIN_DIR . 'includes/class-push-settings.php';
		require_once CUSTOM_PWA_PLUGIN_DIR . 'includes/class-push-scenarios.php';
		require_once CUSTOM_PWA_PLUGIN_DIR . 'includes/class-push-rules.php';
		require_once CUSTOM_PWA_PLUGIN_DIR . 'includes/class-custom-scenarios.php';
		require_once CUSTOM_PWA_PLUGIN_DIR . 'includes/class-scenario-handlers.php';
		require_once CUSTOM_PWA_PLUGIN_DIR . 'includes/class-subscriptions.php';
		require_once CUSTOM_PWA_PLUGIN_DIR . 'includes/class-dispatcher.php';
		require_once CUSTOM_PWA_PLUGIN_DIR . 'includes/class-ssl-helper.php';
	}

	/**
	 * Initialize WordPress hooks.
	 */
	private function init_hooks() {
		add_action( 'plugins_loaded', array( $this, 'init' ) );
		add_action( 'init', array( $this, 'load_textdomain' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_scripts' ) );
		add_action( 'admin_notices', array( $this, 'activation_notice' ) );
		
		// Register activation and deactivation hooks.
		register_activation_hook( CUSTOM_PWA_PLUGIN_FILE, array( $this, 'activate' ) );
		register_deactivation_hook( CUSTOM_PWA_PLUGIN_FILE, array( $this, 'deactivate' ) );
	}

	/**
	 * Initialize plugin components.
	 */
	public function init() {
		// Initialize components.
		$this->config_settings = new Custom_PWA_Config_Settings();
		$this->pwa_settings    = new Custom_PWA_Settings();
		$this->push_settings   = new Custom_PWA_Push_Settings();
		$this->subscriptions   = new Custom_PWA_Subscriptions();
		$this->dispatcher      = new Custom_PWA_Dispatcher();
		$this->admin_menu      = new Custom_PWA_Admin_Menu(
			$this->pwa_settings,
			$this->push_settings,
			$this->config_settings
		);

		// Initialize SSL Helper (admin only).
		if ( is_admin() ) {
			new Custom_PWA_SSL_Helper();
		}

		// Allow other plugins to hook into our initialization.
		do_action( 'custom_pwa_init', $this );
	}

	/**
	 * Load plugin text domain for translations.
	 */
	public function load_textdomain() {
		load_plugin_textdomain(
			'custom-pwa',
			false,
			dirname( CUSTOM_PWA_PLUGIN_BASENAME ) . '/languages'
		);
	}

	/**
	 * Display activation notice.
	 */
	public function activation_notice() {
		if ( get_transient( 'custom_pwa_activation_notice' ) ) {
			?>
			<div class="notice notice-success is-dismissible">
				<p>
					<strong><?php esc_html_e( 'Custom PWA', 'custom-pwa' ); ?></strong>: 
					<?php esc_html_e( 'Plugin activated successfully! Permalinks have been flushed to register the manifest endpoint.', 'custom-pwa' ); ?>
				</p>
			</div>
			<?php
			delete_transient( 'custom_pwa_activation_notice' );
		}
	}

	/**
	 * Enqueue frontend scripts.
	 */
	public function enqueue_frontend_scripts() {
		// Check if PWA and/or Push is enabled.
		$config = get_option( 'custom_pwa_config', array() );
		$pwa_enabled = ! empty( $config['enable_pwa'] );
		$push_enabled = ! empty( $config['enable_push'] );

		// If PWA is enabled, we need to register the Service Worker
		// even if Push is disabled, for the app to be installable
		if ( $pwa_enabled ) {
			// Enqueue SW registration script
			wp_enqueue_script(
				'custom-pwa-sw-register',
				CUSTOM_PWA_PLUGIN_URL . 'assets/js/sw-register.js',
				array(),
				CUSTOM_PWA_VERSION,
				true
			);

			$sw_data = array(
				'swPath'       => '/sw.js',
				'pwaEnabled'   => '1',
				'pushEnabled'  => $push_enabled ? '1' : '0',
				'localDevMode' => ! empty( $config['local_dev_mode'] ) ? '1' : '0',
			);

			wp_localize_script( 'custom-pwa-sw-register', 'customPwaData', $sw_data );
		}

		// Enqueue notification popup styles and script if push is enabled
		if ( $push_enabled ) {
			// Enqueue popup CSS
			wp_enqueue_style(
				'custom-pwa-notification-popup',
				CUSTOM_PWA_PLUGIN_URL . 'assets/css/notification-popup.css',
				array(),
				CUSTOM_PWA_VERSION
			);

			// Enqueue popup JS
			wp_enqueue_script(
				'custom-pwa-notification-popup',
				CUSTOM_PWA_PLUGIN_URL . 'assets/js/notification-popup.js',
				array(),
				CUSTOM_PWA_VERSION,
				true
			);

			// Enqueue subscribe script (depends on SW being registered)
			wp_enqueue_script(
				'custom-pwa-subscribe',
				CUSTOM_PWA_PLUGIN_URL . 'assets/js/frontend-subscribe.js',
				array( 'custom-pwa-sw-register' ),
				CUSTOM_PWA_VERSION,
				true
			);

			// Localize script with data for both scripts
			$push_settings = get_option( 'custom_pwa_push', array() );
			$vapid_public_key = ! empty( $push_settings['public_key'] ) ? $push_settings['public_key'] : '';

			$localize_data = array(
				'restUrl'        => rest_url(),
				'lang'           => get_bloginfo( 'language' ),
				'swPath'         => '/sw.js',
				'pwaEnabled'     => '1',
				'pushEnabled'    => '1',
				'localDevMode'   => ! empty( $config['local_dev_mode'] ) ? '1' : '0',
				'vapidPublicKey' => $vapid_public_key,
			);

			wp_localize_script( 'custom-pwa-notification-popup', 'customPwaData', $localize_data );
			wp_localize_script( 'custom-pwa-subscribe', 'customPwaData', $localize_data );
		}
	}

	/**
	 * Plugin activation callback.
	 * 
	 * Creates database tables and sets default options.
	 */
	public function activate() {
		// Ensure dependencies are loaded.
		if ( ! class_exists( 'Custom_PWA_Subscriptions' ) ) {
			require_once CUSTOM_PWA_PLUGIN_DIR . 'includes/class-subscriptions.php';
		}

		// Create subscriptions table.
		Custom_PWA_Subscriptions::create_table();

		// Set default options if they don't exist.
		$this->set_default_options();

		// Flush rewrite rules to register manifest endpoint.
		flush_rewrite_rules();

		// Set activation notice.
		set_transient( 'custom_pwa_activation_notice', true, 5 );

		do_action( 'custom_pwa_activated' );
	}

	/**
	 * Plugin deactivation callback.
	 */
	public function deactivate() {
		// Flush rewrite rules.
		flush_rewrite_rules();

		do_action( 'custom_pwa_deactivated' );
	}

	/**
	 * Set default plugin options on activation.
	 */
	private function set_default_options() {
		// Config defaults.
		if ( false === get_option( 'custom_pwa_config' ) ) {
			// Detect if running on localhost or local domain
			$is_local = in_array( $_SERVER['HTTP_HOST'] ?? '', array( 'localhost', '127.0.0.1' ), true ) 
			         || strpos( $_SERVER['HTTP_HOST'] ?? '', '.local' ) !== false
			         || strpos( $_SERVER['HTTP_HOST'] ?? '', '.test' ) !== false
			         || strpos( $_SERVER['HTTP_HOST'] ?? '', '.dev' ) !== false;
			
			$config_defaults = array(
				'enable_pwa'         => false,
				'enable_push'        => false,
				'site_type'          => 'generic',
				'enabled_post_types' => array( 'post' ),
				'debug_mode'         => false,
				'local_dev_mode'     => $is_local, // Auto-enable for local environments
			);
			add_option( 'custom_pwa_config', $config_defaults );
		}

		// PWA defaults.
		if ( false === get_option( 'custom_pwa_settings' ) ) {
			$pwa_defaults = array(
				'app_name'         => get_bloginfo( 'name' ),
				'short_name'       => get_bloginfo( 'name' ),
				'description'      => get_bloginfo( 'description' ),
				'start_url'        => home_url( '/' ),
				'theme_color'      => '#000000',
				'background_color' => '#ffffff',
				'display'          => 'standalone',
				'icon_id'          => 0,
			);
			add_option( 'custom_pwa_settings', $pwa_defaults );
		}

		// Push defaults.
		if ( false === get_option( 'custom_pwa_push_rules' ) ) {
			$push_defaults = array();
			add_option( 'custom_pwa_push_rules', $push_defaults );
		}

		// Generate VAPID keys if they don't exist.
		if ( false === get_option( 'custom_pwa_push' ) ) {
			$vapid_keys = $this->generate_vapid_keys();
			add_option( 'custom_pwa_push', $vapid_keys );
		}
	}

	/**
	 * Generate VAPID keys for Web Push.
	 * 
	 * @return array Array with 'public_key' and 'private_key'.
	 */
	private function generate_vapid_keys() {
		// Generate a secure random key pair for VAPID.
		// Web Push requires raw EC P-256 public key (65 bytes uncompressed).
		
		// Check if OpenSSL is available.
		if ( ! function_exists( 'openssl_pkey_new' ) ) {
			error_log( 'Custom PWA: OpenSSL is not available. Cannot generate VAPID keys.' );
			return array(
				'public_key'  => '',
				'private_key' => '',
			);
		}

		// Generate EC key pair (prime256v1 curve, which is P-256).
		$config = array(
			'private_key_type' => OPENSSL_KEYTYPE_EC,
			'curve_name'       => 'prime256v1',
		);

		$key_resource = openssl_pkey_new( $config );
		if ( ! $key_resource ) {
			error_log( 'Custom PWA: Failed to generate VAPID key pair.' );
			return array(
				'public_key'  => '',
				'private_key' => '',
			);
		}

		// Export private key in PEM format.
		$private_key_pem = '';
		openssl_pkey_export( $key_resource, $private_key_pem );

		// Get public key details and extract raw EC point.
		$key_details = openssl_pkey_get_details( $key_resource );
		$ec_key      = $key_details['ec'];
		
		// Extract X and Y coordinates (32 bytes each for P-256).
		$x = $ec_key['x'];
		$y = $ec_key['y'];
		
		// Pad to 32 bytes if needed.
		$x = str_pad( $x, 32, "\0", STR_PAD_LEFT );
		$y = str_pad( $y, 32, "\0", STR_PAD_LEFT );
		
		// Create uncompressed public key: 0x04 + X (32 bytes) + Y (32 bytes) = 65 bytes.
		$public_key_raw = "\x04" . $x . $y;
		
		// Base64url encode both keys.
		$public_key_base64url  = $this->base64url_encode( $public_key_raw );
		$private_key_base64url = $this->base64url_encode( $private_key_pem );

		return array(
			'public_key'  => $public_key_base64url,
			'private_key' => $private_key_base64url,
		);
	}

	/**
	 * Base64url encode a string.
	 *
	 * @param string $data Data to encode.
	 * @return string Base64url encoded string.
	 */
	private function base64url_encode( $data ) {
		return rtrim( strtr( base64_encode( $data ), '+/', '-_' ), '=' );
	}

	/**
	 * Get config settings instance.
	 *
	 * @return Custom_PWA_Config_Settings|null
	 */
	public function get_config_settings() {
		return $this->config_settings;
	}

	/**
	 * Get PWA settings instance.
	 *
	 * @return Custom_PWA_Settings|null
	 */
	public function get_pwa_settings() {
		return $this->pwa_settings;
	}

	/**
	 * Get push settings instance.
	 *
	 * @return Custom_PWA_Push_Settings|null
	 */
	public function get_push_settings() {
		return $this->push_settings;
	}

	/**
	 * Get subscriptions instance.
	 *
	 * @return Custom_PWA_Subscriptions|null
	 */
	public function get_subscriptions() {
		return $this->subscriptions;
	}

	/**
	 * Get dispatcher instance.
	 *
	 * @return Custom_PWA_Dispatcher|null
	 */
	public function get_dispatcher() {
		return $this->dispatcher;
	}
}

/**
 * Get the main plugin instance.
 *
 * @return Custom_PWA_Plugin
 */
function custom_pwa() {
	return Custom_PWA_Plugin::get_instance();
}

// Initialize the plugin.
custom_pwa();
