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

		// Enqueue custom admin styles (clean, minimal UI)
		wp_enqueue_style( 'custom-pwa-admin', CUSTOM_PWA_PLUGIN_URL . 'assets/css/admin.css', array(), CUSTOM_PWA_VERSION );

		// Enqueue custom admin script placeholder (optional enhancements)
		// wp_enqueue_script( 'custom-pwa-admin', CUSTOM_PWA_PLUGIN_URL . 'assets/js/admin.js', array( 'jquery' ), CUSTOM_PWA_VERSION, true );
	}

	/**
	 * Render the PWA settings page.
	 */
	public function render_pwa_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'custom-pwa' ) );
		}

		echo '<div class="wrap custom-pwa-admin-container">';
		echo '<h1 class="custom-pwa-title">' . esc_html__( 'PWA Settings', 'custom-pwa' ) . '</h1>';
		
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

		// Load required classes
		require_once plugin_dir_path( __FILE__ ) . 'class-push-scenarios.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-push-rules.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-custom-scenarios.php';

		echo '<div class="wrap custom-pwa-admin-container">';
		
		// Check if Push is enabled globally.
		$config = get_option( 'custom_pwa_config', array() );
		if ( empty( $config['enable_push'] ) ) {
			echo '<div class="notice notice-warning"><p>';
			echo esc_html__( 'Web Push notifications are currently disabled. Please enable them in the Config page.', 'custom-pwa' );
			echo ' <a href="' . esc_url( admin_url( 'admin.php?page=custom-pwa-config' ) ) . '">';
			echo esc_html__( 'Go to Config', 'custom-pwa' );
			echo '</a></p></div>';
		}

		// Tabs for switching between post type config and scenario manager
		$current_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'post-types';
		
		// Section title and description
		echo '<div style="margin-bottom:24px;">';
		echo '<h1 class="custom-pwa-title" style="margin-bottom:8px;">' . esc_html__( 'Notification Templates', 'custom-pwa' ) . '</h1>';
		echo '<p style="color:var(--cp-muted); margin:0 0 20px 0;">' . esc_html__( 'Configure push notification scenarios and templates for each post type.', 'custom-pwa' ) . '</p>';
		?>
		<div class="custom-pwa-tabs" style="margin-bottom:20px; border-bottom:2px solid var(--cp-border);">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=custom-pwa-push&tab=post-types' ) ); ?>" 
			   class="custom-pwa-tab <?php echo $current_tab === 'post-types' ? 'active' : ''; ?>">
				<span class="dashicons dashicons-admin-post"></span>
				<?php esc_html_e( 'Post Type Configuration', 'custom-pwa' ); ?>
			</a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=custom-pwa-push&tab=scenarios' ) ); ?>" 
			   class="custom-pwa-tab <?php echo $current_tab === 'scenarios' ? 'active' : ''; ?>">
				<span class="dashicons dashicons-admin-settings"></span>
				<?php esc_html_e( 'Manage Scenarios', 'custom-pwa' ); ?>
			</a>
		</div>
		<?php
		echo '</div>';
		
		if ( $current_tab === 'scenarios' ) {
			// Show scenario manager
			require_once plugin_dir_path( __FILE__ ) . 'class-scenario-manager-ui.php';
			Custom_PWA_Scenario_Manager_UI::render();
		} else {
			// Show post type configuration (existing functionality)
			$this->render_post_type_configuration();
		}
		
		echo '</div>'; // End wrap
	}

	/**
	 * Render the post type configuration (existing functionality).
	 */
	private function render_post_type_configuration() {
		echo '<h3 style="font-size:16px; font-weight:600; margin:0 0 8px 0;">' . esc_html__( 'Push Notification Rules', 'custom-pwa' ) . '</h3>';
		echo '<p style="color:var(--cp-muted); margin:0.">' . esc_html__( 'Configure notification scenarios based on the role assigned to each post type.', 'custom-pwa' ) . '</p>';
		
		// Show available placeholders
		$placeholders = Custom_PWA_Push_Scenarios::get_placeholders();
		$common_placeholders = isset( $placeholders['common']['placeholders'] ) ? array_keys( $placeholders['common']['placeholders'] ) : array();
		if ( ! empty( $common_placeholders ) ) {
			echo '<p style="color:var(--cp-muted); font-size:13px; margin:8px 0 0 0;">' 
				. esc_html__( 'Available placeholders: ', 'custom-pwa' ) 
				. esc_html( implode( ', ', $common_placeholders ) ) 
				. '</p>';
		}

		// Start grid layout
		echo '<div class="custom-pwa-grid">';
		
		// Sidebar with selected CPTs (left side)
		echo '<aside class="custom-pwa-sidebar">';
		$this->render_push_sidebar();
		echo '</aside>';
		
		// Main content (right side)
		echo '<div class="custom-pwa-main">';
		$this->push_settings->render_settings_page();
		echo '</div>';
		
		echo '</div>'; // End grid
		
		// Add JavaScript for sidebar navigation AFTER content is rendered
		$this->render_sidebar_navigation_script();
	}
	
	/**
	 * Render JavaScript for sidebar navigation.
	 */
	private function render_sidebar_navigation_script() {
		?>
		<script type="text/javascript">
		jQuery(document).ready(function($) {
			// Get selected post type from URL or use first CPT
			var urlParams = new URLSearchParams(window.location.search);
			var selectedCpt = urlParams.get('cpt');
			
			// If no post type in URL, use first CPT
			if (!selectedCpt || selectedCpt === '') {
				var firstItem = $('.custom-pwa-sidebar-item[data-cpt!="test-notification"]').first();
				if (firstItem.length) {
					selectedCpt = firstItem.data('cpt');
				}
			}
			
			// Remove all active classes first, then add to selected item
			$('.custom-pwa-sidebar-item').removeClass('active');
			if (selectedCpt) {
				$('.custom-pwa-sidebar-item[data-cpt="' + selectedCpt + '"]').addClass('active');
			}
			
			// Show the corresponding content (initially all are hidden by CSS)
			if (selectedCpt) {
				$('.custom-pwa-push-main[data-cpt="' + selectedCpt + '"]').show();
			}
			
			// Handle sidebar item clicks
			$('.custom-pwa-sidebar-item').on('click', function() {
				var cpt = $(this).data('cpt');
				
				// Update active state in sidebar
				$('.custom-pwa-sidebar-item').removeClass('active');
				$(this).addClass('active');
				
				// Hide all content
				$('.custom-pwa-push-main').hide();
				$('#test-notification-card').hide();
				
				// Show selected content
				if (cpt === 'test-notification') {
					$('#test-notification-card').show();
				} else {
					$('.custom-pwa-push-main[data-cpt="' + cpt + '"]').show();
					
					// Update URL without reload (for better UX and bookmarking)
					var newUrl = window.location.pathname + '?page=custom-pwa-push&tab=post-types&cpt=' + cpt;
					window.history.pushState({path: newUrl}, '', newUrl);
				}
			});
		});
		</script>
		<?php
	}

	/**
	 * Render sidebar for Push page with selected CPTs.
	 */
	private function render_push_sidebar() {
		$config = get_option( 'custom_pwa_config', array() );
		$enabled_post_types = isset( $config['enabled_post_types'] ) && is_array( $config['enabled_post_types'] ) 
			? $config['enabled_post_types'] 
			: array();

		echo '<h3>' . esc_html__( 'Post Types', 'custom-pwa' ) . '</h3>';
		
		if ( empty( $enabled_post_types ) ) {
			echo '<div class="custom-pwa-sidebar-empty">';
			echo esc_html__( 'No post types enabled.', 'custom-pwa' );
			echo '<br><br>';
			echo '<a href="' . esc_url( admin_url( 'admin.php?page=custom-pwa-config' ) ) . '" class="button button-small cp-btn secondary">' . esc_html__( 'Configure', 'custom-pwa' ) . '</a>';
			echo '</div>';
		} else {
			// Get all registered post types
			$post_types = get_post_types( array( 'public' => true ), 'objects' );
			
			echo '<ul class="custom-pwa-sidebar-list">';
			$first = true;
			foreach ( $enabled_post_types as $cpt ) {
				if ( isset( $post_types[ $cpt ] ) ) {
					$post_type_obj = $post_types[ $cpt ];
					$active_class = $first ? ' active' : '';
					echo '<li class="custom-pwa-sidebar-item' . $active_class . '" data-cpt="' . esc_attr( $cpt ) . '" style="cursor:pointer;">';
					echo '<span class="dashicons dashicons-admin-post"></span>';
					echo '<span style="flex:1;">' . esc_html( $post_type_obj->labels->name ) . '</span>';
					echo '<span class="dashicons dashicons-arrow-right-alt2" style="color:var(--cp-accent); font-size:18px;"></span>';
					echo '</li>';
					$first = false;
				}
			}
			echo '</ul>';
			
			// Add Test item
			echo '<div style="margin-top:16px; padding-top:16px; border-top:1px solid var(--cp-border);">';
			echo '<ul class="custom-pwa-sidebar-list">';
			echo '<li class="custom-pwa-sidebar-item" data-cpt="test-notification" style="cursor:pointer;">';
			echo '<span class="dashicons dashicons-email-alt"></span>';
			echo '<span style="flex:1;">' . esc_html__( 'Test', 'custom-pwa' ) . '</span>';
			echo '<span class="dashicons dashicons-arrow-right-alt2" style="color:var(--cp-accent); font-size:18px;"></span>';
			echo '</li>';
			echo '</ul>';
			echo '</div>';
			
			// Add summary
			echo '<div style="margin-top:16px; padding-top:16px; border-top:1px solid var(--cp-border);">';
			echo '<p style="margin:0; font-size:13px; color:var(--cp-muted);">';
			echo sprintf( 
				esc_html__( 'Click on a post type to configure its notification template.', 'custom-pwa' )
			);
			echo '</p>';
			echo '</div>';
		}
	}

	/**
	 * Render the Config page.
	 */
	public function render_config_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'custom-pwa' ) );
		}

		echo '<div class="wrap custom-pwa-admin-container">';
		echo '<h1 class="custom-pwa-title">' . esc_html__( 'Global Configuration', 'custom-pwa' ) . '</h1>';
		$this->config_settings->render_settings_page();
		echo '</div>';
	}
}
