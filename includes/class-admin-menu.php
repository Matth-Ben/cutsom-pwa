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
		
		// Add "Installation" submenu.
		add_submenu_page(
			'custom-pwa',
			__( 'Installation Guide', 'custom-pwa' ),
			__( 'Installation', 'custom-pwa' ),
			'manage_options',
			'custom-pwa-installation',
			array( $this, 'render_installation_page' )
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
	
	/**
	 * Render the installation guide page.
	 */
	public function render_installation_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'custom-pwa' ) );
		}

		// Get file copy status
		$copy_status = get_option( 'custom_pwa_file_copy_status', array(
			'copied' => array(),
			'errors' => array(),
			'timestamp' => '',
		) );
		
		$site_root = ABSPATH;
		$site_url = home_url();
		
		// Check current file status
		$files_status = array(
			'sw.js' => array(
				'path' => $site_root . 'sw.js',
				'url' => $site_url . '/sw.js',
				'exists' => file_exists( $site_root . 'sw.js' ),
				'writable' => file_exists( $site_root . 'sw.js' ) && is_writable( $site_root . 'sw.js' ),
				'required' => true,
			),
			'offline.html' => array(
				'path' => $site_root . 'offline.html',
				'url' => $site_url . '/offline.html',
				'exists' => file_exists( $site_root . 'offline.html' ),
				'writable' => file_exists( $site_root . 'offline.html' ) && is_writable( $site_root . 'offline.html' ),
				'required' => true,
			),
		);
		
		?>
		<div class="wrap custom-pwa-admin-container">
			<h1 class="custom-pwa-title"><?php esc_html_e( 'Installation Guide', 'custom-pwa' ); ?></h1>
			
			<!-- Automatic Installation Status -->
			<div class="custom-pwa-card">
				<h2><?php esc_html_e( '✅ Automatic Installation Status', 'custom-pwa' ); ?></h2>
				
				<?php if ( ! empty( $copy_status['copied'] ) || ! empty( $copy_status['errors'] ) ) : ?>
					<p><?php esc_html_e( 'Last installation attempt:', 'custom-pwa' ); ?> 
						<strong><?php echo esc_html( $copy_status['timestamp'] ); ?></strong>
					</p>
				<?php endif; ?>
				
				<h3><?php esc_html_e( 'Required Files Status:', 'custom-pwa' ); ?></h3>
				<table class="widefat fixed striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'File', 'custom-pwa' ); ?></th>
							<th><?php esc_html_e( 'Status', 'custom-pwa' ); ?></th>
							<th><?php esc_html_e( 'Location', 'custom-pwa' ); ?></th>
							<th><?php esc_html_e( 'Actions', 'custom-pwa' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $files_status as $filename => $status ) : ?>
							<tr>
								<td><code><?php echo esc_html( $filename ); ?></code></td>
								<td>
									<?php if ( $status['exists'] ) : ?>
										<span style="color: #46b450;">✅ <?php esc_html_e( 'Installed', 'custom-pwa' ); ?></span>
									<?php else : ?>
										<span style="color: #dc3232;">❌ <?php esc_html_e( 'Missing', 'custom-pwa' ); ?></span>
									<?php endif; ?>
								</td>
								<td><code><?php echo esc_html( $status['path'] ); ?></code></td>
								<td>
									<?php if ( $status['exists'] ) : ?>
										<a href="<?php echo esc_url( $status['url'] ); ?>" target="_blank" class="button button-small">
											<?php esc_html_e( 'View File', 'custom-pwa' ); ?>
										</a>
									<?php else : ?>
										<span style="color: #dc3232;"><?php esc_html_e( 'Not installed', 'custom-pwa' ); ?></span>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				
				<?php if ( ! empty( $copy_status['errors'] ) ) : ?>
					<div class="notice notice-error inline">
						<h4><?php esc_html_e( 'Installation Errors:', 'custom-pwa' ); ?></h4>
						<ul>
							<?php foreach ( $copy_status['errors'] as $error ) : ?>
								<li><?php echo esc_html( $error ); ?></li>
							<?php endforeach; ?>
						</ul>
					</div>
				<?php endif; ?>
				
				<?php if ( ! $files_status['sw.js']['exists'] || ! $files_status['offline.html']['exists'] ) : ?>
					<div class="notice notice-warning inline">
						<p>
							<strong><?php esc_html_e( 'Some files are missing!', 'custom-pwa' ); ?></strong><br>
							<?php esc_html_e( 'The automatic installation failed. Please follow the manual installation steps below.', 'custom-pwa' ); ?>
						</p>
					</div>
				<?php else : ?>
					<div class="notice notice-success inline">
						<p>
							<strong><?php esc_html_e( '✅ All required files are installed!', 'custom-pwa' ); ?></strong><br>
							<?php esc_html_e( 'Your PWA is ready to use. Enable it in the Config page.', 'custom-pwa' ); ?>
						</p>
					</div>
				<?php endif; ?>
			</div>
			
			<!-- Manual Installation Guide -->
			<div class="custom-pwa-card">
				<h2><?php esc_html_e( '📖 Manual Installation (if automatic failed)', 'custom-pwa' ); ?></h2>
				
				<p><?php esc_html_e( 'If the automatic installation failed, follow these steps to manually install the required files:', 'custom-pwa' ); ?></p>
				
				<h3><?php esc_html_e( 'Step 1: Locate the example files', 'custom-pwa' ); ?></h3>
				<p><?php esc_html_e( 'The example files are located in the plugin directory:', 'custom-pwa' ); ?></p>
				<pre><code><?php echo esc_html( CUSTOM_PWA_PLUGIN_DIR . 'assets/examples/' ); ?></code></pre>
				
				<h3><?php esc_html_e( 'Step 2: Copy files to your site root', 'custom-pwa' ); ?></h3>
				<p><?php esc_html_e( 'You need to copy these files to the root of your WordPress installation:', 'custom-pwa' ); ?></p>
				
				<table class="widefat">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Source File', 'custom-pwa' ); ?></th>
							<th><?php esc_html_e( 'Destination', 'custom-pwa' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td><code>assets/examples/sw-example.js</code></td>
							<td><code><?php echo esc_html( $site_root ); ?>sw.js</code></td>
						</tr>
						<tr>
							<td><code>assets/examples/offline-example.html</code></td>
							<td><code><?php echo esc_html( $site_root ); ?>offline.html</code></td>
						</tr>
					</tbody>
				</table>
				
				<h3><?php esc_html_e( 'Step 3: Via FTP/File Manager', 'custom-pwa' ); ?></h3>
				<ol>
					<li><?php esc_html_e( 'Connect to your server via FTP or use your hosting file manager', 'custom-pwa' ); ?></li>
					<li><?php esc_html_e( 'Navigate to:', 'custom-pwa' ); ?> <code><?php echo esc_html( CUSTOM_PWA_PLUGIN_DIR . 'assets/examples/' ); ?></code></li>
					<li><?php esc_html_e( 'Download', 'custom-pwa' ); ?> <code>sw-example.js</code> <?php esc_html_e( 'and', 'custom-pwa' ); ?> <code>offline-example.html</code></li>
					<li><?php esc_html_e( 'Navigate to your site root:', 'custom-pwa' ); ?> <code><?php echo esc_html( $site_root ); ?></code></li>
					<li><?php esc_html_e( 'Upload and rename:', 'custom-pwa' ); ?>
						<ul>
							<li><code>sw-example.js</code> → <code>sw.js</code></li>
							<li><code>offline-example.html</code> → <code>offline.html</code></li>
						</ul>
					</li>
					<li><?php esc_html_e( 'Set file permissions to 644', 'custom-pwa' ); ?></li>
				</ol>
				
				<h3><?php esc_html_e( 'Step 4: Via SSH/Terminal', 'custom-pwa' ); ?></h3>
				<p><?php esc_html_e( 'If you have SSH access, run these commands:', 'custom-pwa' ); ?></p>
				<pre><code>cd <?php echo esc_html( $site_root ); ?>

cp <?php echo esc_html( CUSTOM_PWA_PLUGIN_DIR ); ?>assets/examples/sw-example.js sw.js
cp <?php echo esc_html( CUSTOM_PWA_PLUGIN_DIR ); ?>assets/examples/offline-example.html offline.html

chmod 644 sw.js offline.html</code></pre>
				
				<h3><?php esc_html_e( 'Step 5: Verify Installation', 'custom-pwa' ); ?></h3>
				<p><?php esc_html_e( 'After copying the files, refresh this page to verify the installation status.', 'custom-pwa' ); ?></p>
				<p>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=custom-pwa-installation' ) ); ?>" class="button button-primary">
						<?php esc_html_e( 'Refresh Status', 'custom-pwa' ); ?>
					</a>
				</p>
			</div>
			
			<!-- Troubleshooting -->
			<div class="custom-pwa-card">
				<h2><?php esc_html_e( '🔧 Troubleshooting', 'custom-pwa' ); ?></h2>
				
				<h3><?php esc_html_e( 'Common Issues:', 'custom-pwa' ); ?></h3>
				
				<h4><?php esc_html_e( 'Permission Denied Error', 'custom-pwa' ); ?></h4>
				<p><?php esc_html_e( 'If you see "Failed to copy" errors, your server doesn\'t allow PHP to write files. Solutions:', 'custom-pwa' ); ?></p>
				<ul>
					<li><?php esc_html_e( 'Contact your hosting provider to adjust file permissions', 'custom-pwa' ); ?></li>
					<li><?php esc_html_e( 'Use FTP/File Manager to copy files manually (see above)', 'custom-pwa' ); ?></li>
					<li><?php esc_html_e( 'Ask your hosting to enable write permissions for:', 'custom-pwa' ); ?> <code><?php echo esc_html( $site_root ); ?></code></li>
				</ul>
				
				<h4><?php esc_html_e( 'Service Worker Not Loading', 'custom-pwa' ); ?></h4>
				<p><?php esc_html_e( 'Service workers require HTTPS. Check:', 'custom-pwa' ); ?></p>
				<ul>
					<li><?php esc_html_e( 'Your site is accessible via HTTPS', 'custom-pwa' ); ?></li>
					<li><?php esc_html_e( 'No mixed content warnings in browser console', 'custom-pwa' ); ?></li>
					<li><?php esc_html_e( 'The sw.js file is accessible at:', 'custom-pwa' ); ?> <a href="<?php echo esc_url( $site_url . '/sw.js' ); ?>" target="_blank"><?php echo esc_html( $site_url . '/sw.js' ); ?></a></li>
				</ul>
				
				<h4><?php esc_html_e( 'Files Disappear After Update', 'custom-pwa' ); ?></h4>
				<p><?php esc_html_e( 'Some hosting providers clean the site root during updates. If this happens:', 'custom-pwa' ); ?></p>
				<ul>
					<li><?php esc_html_e( 'Deactivate and reactivate the plugin to trigger automatic installation', 'custom-pwa' ); ?></li>
					<li><?php esc_html_e( 'Or copy the files manually again', 'custom-pwa' ); ?></li>
				</ul>
			</div>
			
			<!-- Documentation Links -->
			<div class="custom-pwa-card">
				<h2><?php esc_html_e( '📚 Additional Resources', 'custom-pwa' ); ?></h2>
				<ul>
					<li><a href="<?php echo esc_url( CUSTOM_PWA_PLUGIN_URL . 'INSTALLATION.md' ); ?>" target="_blank"><?php esc_html_e( 'Complete Installation Guide', 'custom-pwa' ); ?></a></li>
					<li><a href="<?php echo esc_url( CUSTOM_PWA_PLUGIN_URL . 'SCENARIOS-USAGE.md' ); ?>" target="_blank"><?php esc_html_e( 'Scenarios Usage Guide', 'custom-pwa' ); ?></a></li>
					<li><a href="<?php echo esc_url( CUSTOM_PWA_PLUGIN_URL . 'CHANGELOG.md' ); ?>" target="_blank"><?php esc_html_e( 'Changelog', 'custom-pwa' ); ?></a></li>
					<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=custom-pwa-config' ) ); ?>"><?php esc_html_e( 'Go to Configuration', 'custom-pwa' ); ?></a></li>
				</ul>
			</div>
		</div>
		
		<style>
			.custom-pwa-card {
				background: #fff;
				border: 1px solid #ccd0d4;
				box-shadow: 0 1px 1px rgba(0,0,0,.04);
				padding: 20px;
				margin-bottom: 20px;
			}
			.custom-pwa-card h2 {
				margin-top: 0;
				border-bottom: 1px solid #e5e5e5;
				padding-bottom: 10px;
			}
			.custom-pwa-card pre {
				background: #f6f7f7;
				border: 1px solid #dcdcde;
				padding: 15px;
				overflow-x: auto;
			}
			.custom-pwa-card code {
				background: #f6f7f7;
				padding: 2px 6px;
				border-radius: 3px;
			}
			.custom-pwa-card pre code {
				background: transparent;
				padding: 0;
			}
		</style>
		<?php
	}
}
