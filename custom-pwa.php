<?php
/**
 * Plugin Name: Custom PWA
 * Plugin URI: https://example.com/custom-pwa
 * Description: Complete PWA configuration and Web Push notifications plugin for WordPress. Supports manifest generation, service worker examples, and push notifications for all post types including custom post types.
 * Version: 1.0.1
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
define( 'CUSTOM_PWA_VERSION', '1.0.1' );
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
		require_once CUSTOM_PWA_PLUGIN_DIR . 'includes/class-subscriptions.php';
		require_once CUSTOM_PWA_PLUGIN_DIR . 'includes/class-dispatcher.php';
	}

	/**
	 * Initialize WordPress hooks.
	 */
	private function init_hooks() {
		add_action( 'plugins_loaded', array( $this, 'init' ) );
		add_action( 'init', array( $this, 'load_textdomain' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_scripts' ) );
		
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
	 * Enqueue frontend scripts.
	 */
	public function enqueue_frontend_scripts() {
		// Only enqueue if Push is enabled.
		$config = get_option( 'custom_pwa_config', array() );
		if ( empty( $config['enable_push'] ) ) {
			return;
		}

		wp_enqueue_script(
			'custom-pwa-subscribe',
			CUSTOM_PWA_PLUGIN_URL . 'assets/js/frontend-subscribe.js',
			array(),
			CUSTOM_PWA_VERSION,
			true
		);

		// Localize script with data.
		wp_localize_script(
			'custom-pwa-subscribe',
			'customPwaData',
			array(
				'restUrl'     => rest_url(),
				'lang'        => get_bloginfo( 'language' ),
				'swPath'      => '/sw.js', // Developer can adjust this.
				'pushEnabled' => ! empty( $config['enable_push'] ),
			)
		);
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
			$config_defaults = array(
				'enable_pwa'        => false,
				'enable_push'       => false,
				'site_type'         => 'generic',
				'enabled_post_types' => array( 'post' ),
				'debug_mode'        => false,
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
