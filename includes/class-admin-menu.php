<?php
/**
 * Admin Menu Class
 * 
 * Registers the "Custom PWA" top-level menu with three submenus:
 * - PWA: PWA configuration
 * - Push: Web Push notifications rules and templates
 * - Config: Global plugin configuration
 *
 * @package Custom_PWA
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Custom_PWA_Admin_Menu class.
 * 
 * Handles admin menu registration and page rendering.
 */
class Custom_PWA_Admin_Menu {

	/**
	 * PWA settings instance.
	 *
	 * @var Custom_PWA_Settings
	 */
	private $pwa_settings;

	/**
	 * Push settings instance.
	 *
	 * @var Custom_PWA_Push_Settings
	 */
	private $push_settings;

	/**
	 * Config settings instance.
	 *
	 * @var Custom_PWA_Config_Settings
	 */
	private $config_settings;

	/**
	 * Constructor.
	 *
	 * @param Custom_PWA_Settings        $pwa_settings    PWA settings instance.
	 * @param Custom_PWA_Push_Settings   $push_settings   Push settings instance.
	 * @param Custom_PWA_Config_Settings $config_settings Config settings instance.
	 */
	public function __construct( $pwa_settings, $push_settings, $config_settings ) {
		$this->pwa_settings    = $pwa_settings;
		$this->push_settings   = $push_settings;
		$this->config_settings = $config_settings;

		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	/**
	 * Register admin menu and submenus.
	 */
	public function register_menu() {
		// Add top-level menu.
		add_menu_page(
			__( 'Custom PWA', 'custom-pwa' ),
			__( 'Custom PWA', 'custom-pwa' ),
			'manage_options',
			'custom-pwa',
			array( $this, 'render_pwa_page' ),
			'dashicons-smartphone',
			80
		);

		// Add "PWA" submenu (replaces the first item).
		add_submenu_page(
			'custom-pwa',
			__( 'PWA Settings', 'custom-pwa' ),
			__( 'PWA', 'custom-pwa' ),
			'manage_options',
			'custom-pwa',
			array( $this, 'render_pwa_page' )
		);

		// Add "Push" submenu.
		add_submenu_page(
			'custom-pwa',
			__( 'Push Notifications', 'custom-pwa' ),
			__( 'Push', 'custom-pwa' ),
			'manage_options',
			'custom-pwa-push',
			array( $this, 'render_push_page' )
		);

		// Add "Config" submenu.
		add_submenu_page(
			'custom-pwa',
			__( 'Global Configuration', 'custom-pwa' ),
			__( 'Config', 'custom-pwa' ),
			'manage_options',
			'custom-pwa-config',
			array( $this, 'render_config_page' )
		);

		/**
		 * Fires after Custom PWA menu items are registered.
		 * 
		 * Allows other plugins/themes to add additional submenu pages.
		 *
		 * @since 1.0.0
		 */
		do_action( 'custom_pwa_admin_menu_registered' );
	}

	/**
	 * Enqueue admin assets (CSS/JS).
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_admin_assets( $hook ) {
		// Only load on our plugin pages.
		if ( strpos( $hook, 'custom-pwa' ) === false ) {
			return;
		}

		// Enqueue WordPress media uploader (for PWA icon selection).
		wp_enqueue_media();

		// Enqueue custom admin styles (if needed in the future).
		// wp_enqueue_style( 'custom-pwa-admin', CUSTOM_PWA_PLUGIN_URL . 'assets/css/admin.css', array(), CUSTOM_PWA_VERSION );

		// Enqueue custom admin scripts (if needed in the future).
		// wp_enqueue_script( 'custom-pwa-admin', CUSTOM_PWA_PLUGIN_URL . 'assets/js/admin.js', array( 'jquery' ), CUSTOM_PWA_VERSION, true );
	}

	/**
	 * Render the PWA settings page.
	 */
	public function render_pwa_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'custom-pwa' ) );
		}

		echo '<div class="wrap">';
		echo '<h1>' . esc_html__( 'PWA Settings', 'custom-pwa' ) . '</h1>';
		
		// Check if PWA is enabled globally.
		$config = get_option( 'custom_pwa_config', array() );
		if ( empty( $config['enable_pwa'] ) ) {
			echo '<div class="notice notice-warning"><p>';
			echo esc_html__( 'PWA features are currently disabled. Please enable them in the Config page.', 'custom-pwa' );
			echo ' <a href="' . esc_url( admin_url( 'admin.php?page=custom-pwa-config' ) ) . '">';
			echo esc_html__( 'Go to Config', 'custom-pwa' );
			echo '</a></p></div>';
		}

		$this->pwa_settings->render_settings_page();
		echo '</div>';
	}

	/**
	 * Render the Push notifications page.
	 */
	public function render_push_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'custom-pwa' ) );
		}

		echo '<div class="wrap">';
		echo '<h1>' . esc_html__( 'Push Notifications', 'custom-pwa' ) . '</h1>';
		
		// Check if Push is enabled globally.
		$config = get_option( 'custom_pwa_config', array() );
		if ( empty( $config['enable_push'] ) ) {
			echo '<div class="notice notice-warning"><p>';
			echo esc_html__( 'Web Push notifications are currently disabled. Please enable them in the Config page.', 'custom-pwa' );
			echo ' <a href="' . esc_url( admin_url( 'admin.php?page=custom-pwa-config' ) ) . '">';
			echo esc_html__( 'Go to Config', 'custom-pwa' );
			echo '</a></p></div>';
		}

		$this->push_settings->render_settings_page();
		echo '</div>';
	}

	/**
	 * Render the Config page.
	 */
	public function render_config_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'custom-pwa' ) );
		}

		echo '<div class="wrap">';
		echo '<h1>' . esc_html__( 'Global Configuration', 'custom-pwa' ) . '</h1>';
		$this->config_settings->render_settings_page();
		echo '</div>';
	}
}
