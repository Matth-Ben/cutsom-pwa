<?php
/**
 * PWA Settings Class
 * 
 * Handles PWA configuration:
 * - App name, short name, description
 * - Start URL, theme color, background color
 * - Display mode, icon selection
 * - Dynamic manifest generation
 * - Head meta tag injection
 *
 * @package Custom_PWA
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Custom_PWA_Settings class.
 * 
 * Manages PWA settings and manifest generation.
 */
class Custom_PWA_Settings {

	/**
	 * Option name for storing PWA settings.
	 *
	 * @var string
	 */
	private $option_name = 'custom_pwa_settings';

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'init', array( $this, 'add_rewrite_rules' ) );
		add_action( 'query_vars', array( $this, 'add_query_vars' ) );
		add_action( 'template_redirect', array( $this, 'serve_manifest' ) );
		add_action( 'wp_head', array( $this, 'inject_head_tags' ), 1 );
	}

	/**
	 * Register settings using WordPress Settings API.
	 */
	public function register_settings() {
		register_setting(
			'custom_pwa_settings_group',
			$this->option_name,
			array(
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
			)
		);

		// PWA configuration section.
		add_settings_section(
			'custom_pwa_settings_general',
			__( 'PWA Configuration', 'custom-pwa' ),
			array( $this, 'render_general_section' ),
			'custom_pwa_settings'
		);

		// App name.
		add_settings_field(
			'app_name',
			__( 'App Name', 'custom-pwa' ),
			array( $this, 'render_app_name_field' ),
			'custom_pwa_settings',
			'custom_pwa_settings_general'
		);

		// Short name.
		add_settings_field(
			'short_name',
			__( 'Short Name', 'custom-pwa' ),
			array( $this, 'render_short_name_field' ),
			'custom_pwa_settings',
			'custom_pwa_settings_general'
		);

		// Description.
		add_settings_field(
			'description',
			__( 'Description', 'custom-pwa' ),
			array( $this, 'render_description_field' ),
			'custom_pwa_settings',
			'custom_pwa_settings_general'
		);

		// Start URL.
		add_settings_field(
			'start_url',
			__( 'Start URL', 'custom-pwa' ),
			array( $this, 'render_start_url_field' ),
			'custom_pwa_settings',
			'custom_pwa_settings_general'
		);

		// Theme color.
		add_settings_field(
			'theme_color',
			__( 'Theme Color', 'custom-pwa' ),
			array( $this, 'render_theme_color_field' ),
			'custom_pwa_settings',
			'custom_pwa_settings_general'
		);

		// Background color.
		add_settings_field(
			'background_color',
			__( 'Background Color', 'custom-pwa' ),
			array( $this, 'render_background_color_field' ),
			'custom_pwa_settings',
			'custom_pwa_settings_general'
		);

		// Display mode.
		add_settings_field(
			'display',
			__( 'Display Mode', 'custom-pwa' ),
			array( $this, 'render_display_field' ),
			'custom_pwa_settings',
			'custom_pwa_settings_general'
		);

		// Icon.
		add_settings_field(
			'icon_id',
			__( 'App Icon', 'custom-pwa' ),
			array( $this, 'render_icon_field' ),
			'custom_pwa_settings',
			'custom_pwa_settings_general'
		);
	}

	/**
	 * Render the settings page.
	 */
	public function render_settings_page() {
		?>
		<form method="post" action="options.php">
			<?php
			settings_fields( 'custom_pwa_settings_group' );
			do_settings_sections( 'custom_pwa_settings' );
			submit_button();
			?>
		</form>

		<hr />

		<h2><?php esc_html_e( 'Manifest Preview', 'custom-pwa' ); ?></h2>
		<p>
			<?php esc_html_e( 'Manifest URL:', 'custom-pwa' ); ?> 
			<a href="<?php echo esc_url( home_url( '/manifest.webmanifest' ) ); ?>" target="_blank">
				<?php echo esc_html( home_url( '/manifest.webmanifest' ) ); ?>
			</a>
		</p>
		<?php
	}

	/**
	 * Render general section description.
	 */
	public function render_general_section() {
		echo '<p>' . esc_html__( 'Configure your Progressive Web App manifest settings.', 'custom-pwa' ) . '</p>';
	}

	/**
	 * Render app name field.
	 */
	public function render_app_name_field() {
		$options  = get_option( $this->option_name, array() );
		$app_name = isset( $options['app_name'] ) ? $options['app_name'] : get_bloginfo( 'name' );
		?>
		<input 
			type="text" 
			name="<?php echo esc_attr( $this->option_name ); ?>[app_name]" 
			value="<?php echo esc_attr( $app_name ); ?>" 
			class="regular-text"
		/>
		<p class="description">
			<?php esc_html_e( 'The name of your Progressive Web App as it will appear to users.', 'custom-pwa' ); ?>
		</p>
		<?php
	}

	/**
	 * Render short name field.
	 */
	public function render_short_name_field() {
		$options    = get_option( $this->option_name, array() );
		$short_name = isset( $options['short_name'] ) ? $options['short_name'] : get_bloginfo( 'name' );
		?>
		<input 
			type="text" 
			name="<?php echo esc_attr( $this->option_name ); ?>[short_name]" 
			value="<?php echo esc_attr( $short_name ); ?>" 
			class="regular-text"
		/>
		<p class="description">
			<?php esc_html_e( 'A shorter name used when space is limited (e.g., home screen).', 'custom-pwa' ); ?>
		</p>
		<?php
	}

	/**
	 * Render description field.
	 */
	public function render_description_field() {
		$options     = get_option( $this->option_name, array() );
		$description = isset( $options['description'] ) ? $options['description'] : get_bloginfo( 'description' );
		?>
		<textarea 
			name="<?php echo esc_attr( $this->option_name ); ?>[description]" 
			rows="3" 
			class="large-text"
		><?php echo esc_textarea( $description ); ?></textarea>
		<p class="description">
			<?php esc_html_e( 'A brief description of your app.', 'custom-pwa' ); ?>
		</p>
		<?php
	}

	/**
	 * Render start URL field.
	 */
	public function render_start_url_field() {
		$options   = get_option( $this->option_name, array() );
		$start_url = isset( $options['start_url'] ) ? $options['start_url'] : home_url( '/' );
		?>
		<input 
			type="url" 
			name="<?php echo esc_attr( $this->option_name ); ?>[start_url]" 
			value="<?php echo esc_attr( $start_url ); ?>" 
			class="regular-text"
		/>
		<p class="description">
			<?php esc_html_e( 'The URL that loads when a user launches your app.', 'custom-pwa' ); ?>
		</p>
		<?php
	}

	/**
	 * Render theme color field.
	 */
	public function render_theme_color_field() {
		$options     = get_option( $this->option_name, array() );
		$theme_color = isset( $options['theme_color'] ) ? $options['theme_color'] : '#000000';
		?>
		<input 
			type="text" 
			name="<?php echo esc_attr( $this->option_name ); ?>[theme_color]" 
			value="<?php echo esc_attr( $theme_color ); ?>" 
			class="color-picker"
		/>
		<p class="description">
			<?php esc_html_e( 'The theme color for the browser UI (hex format, e.g., #000000).', 'custom-pwa' ); ?>
		</p>
		<?php
	}

	/**
	 * Render background color field.
	 */
	public function render_background_color_field() {
		$options          = get_option( $this->option_name, array() );
		$background_color = isset( $options['background_color'] ) ? $options['background_color'] : '#ffffff';
		?>
		<input 
			type="text" 
			name="<?php echo esc_attr( $this->option_name ); ?>[background_color]" 
			value="<?php echo esc_attr( $background_color ); ?>" 
			class="color-picker"
		/>
		<p class="description">
			<?php esc_html_e( 'The background color for the splash screen (hex format, e.g., #ffffff).', 'custom-pwa' ); ?>
		</p>
		<?php
	}

	/**
	 * Render display field.
	 */
	public function render_display_field() {
		$options = get_option( $this->option_name, array() );
		$display = isset( $options['display'] ) ? $options['display'] : 'standalone';

		$modes = array(
			'fullscreen'  => __( 'Fullscreen', 'custom-pwa' ),
			'standalone'  => __( 'Standalone', 'custom-pwa' ),
			'minimal-ui'  => __( 'Minimal UI', 'custom-pwa' ),
			'browser'     => __( 'Browser', 'custom-pwa' ),
		);
		?>
		<select name="<?php echo esc_attr( $this->option_name ); ?>[display]">
			<?php foreach ( $modes as $mode => $label ) : ?>
				<option value="<?php echo esc_attr( $mode ); ?>" <?php selected( $display, $mode ); ?>>
					<?php echo esc_html( $label ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<p class="description">
			<?php esc_html_e( 'How the app should be displayed when launched.', 'custom-pwa' ); ?>
		</p>
		<?php
	}

	/**
	 * Render icon field.
	 */
	public function render_icon_field() {
		$options = get_option( $this->option_name, array() );
		$icon_id = isset( $options['icon_id'] ) ? absint( $options['icon_id'] ) : 0;
		$icon_url = $icon_id ? wp_get_attachment_url( $icon_id ) : '';
		?>
		<div class="custom-pwa-icon-upload">
			<input 
				type="hidden" 
				id="custom_pwa_icon_id" 
				name="<?php echo esc_attr( $this->option_name ); ?>[icon_id]" 
				value="<?php echo esc_attr( $icon_id ); ?>" 
			/>
			<div id="custom_pwa_icon_preview" style="margin-bottom: 10px;">
				<?php if ( $icon_url ) : ?>
					<img src="<?php echo esc_url( $icon_url ); ?>" alt="App Icon" style="max-width: 150px; height: auto; display: block;" />
				<?php endif; ?>
			</div>
			<button type="button" class="button" id="custom_pwa_upload_icon_button">
				<?php esc_html_e( 'Upload Icon', 'custom-pwa' ); ?>
			</button>
			<button type="button" class="button" id="custom_pwa_remove_icon_button" style="<?php echo $icon_id ? '' : 'display:none;'; ?>">
				<?php esc_html_e( 'Remove Icon', 'custom-pwa' ); ?>
			</button>
			<p class="description">
				<?php esc_html_e( 'Upload a square icon (at least 512x512px). Falls back to Site Icon if not set.', 'custom-pwa' ); ?>
			</p>
		</div>

		<script type="text/javascript">
		jQuery(document).ready(function($) {
			var mediaUploader;

			$('#custom_pwa_upload_icon_button').on('click', function(e) {
				e.preventDefault();
				
				if (mediaUploader) {
					mediaUploader.open();
					return;
				}

				mediaUploader = wp.media({
					title: '<?php esc_html_e( 'Select App Icon', 'custom-pwa' ); ?>',
					button: {
						text: '<?php esc_html_e( 'Use this icon', 'custom-pwa' ); ?>'
					},
					multiple: false,
					library: {
						type: 'image'
					}
				});

				mediaUploader.on('select', function() {
					var attachment = mediaUploader.state().get('selection').first().toJSON();
					$('#custom_pwa_icon_id').val(attachment.id);
					$('#custom_pwa_icon_preview').html('<img src="' + attachment.url + '" alt="App Icon" style="max-width: 150px; height: auto; display: block;" />');
					$('#custom_pwa_remove_icon_button').show();
				});

				mediaUploader.open();
			});

			$('#custom_pwa_remove_icon_button').on('click', function(e) {
				e.preventDefault();
				$('#custom_pwa_icon_id').val('');
				$('#custom_pwa_icon_preview').html('');
				$(this).hide();
			});
		});
		</script>
		<?php
	}

	/**
	 * Sanitize settings.
	 *
	 * @param array $input Raw input values.
	 * @return array Sanitized values.
	 */
	public function sanitize_settings( $input ) {
		$sanitized = array();

		$sanitized['app_name']         = isset( $input['app_name'] ) ? sanitize_text_field( $input['app_name'] ) : get_bloginfo( 'name' );
		$sanitized['short_name']       = isset( $input['short_name'] ) ? sanitize_text_field( $input['short_name'] ) : get_bloginfo( 'name' );
		$sanitized['description']      = isset( $input['description'] ) ? sanitize_textarea_field( $input['description'] ) : get_bloginfo( 'description' );
		$sanitized['start_url']        = isset( $input['start_url'] ) ? esc_url_raw( $input['start_url'] ) : home_url( '/' );
		$sanitized['theme_color']      = isset( $input['theme_color'] ) ? sanitize_hex_color( $input['theme_color'] ) : '#000000';
		$sanitized['background_color'] = isset( $input['background_color'] ) ? sanitize_hex_color( $input['background_color'] ) : '#ffffff';
		
		$valid_displays = array( 'fullscreen', 'standalone', 'minimal-ui', 'browser' );
		$display = isset( $input['display'] ) ? sanitize_text_field( $input['display'] ) : 'standalone';
		$sanitized['display'] = in_array( $display, $valid_displays, true ) ? $display : 'standalone';

		$sanitized['icon_id'] = isset( $input['icon_id'] ) ? absint( $input['icon_id'] ) : 0;

		return $sanitized;
	}

	/**
	 * Add rewrite rules for manifest.
	 */
	public function add_rewrite_rules() {
		add_rewrite_rule( '^manifest\.webmanifest$', 'index.php?custom_pwa_manifest=1', 'top' );
	}

	/**
	 * Add query vars.
	 *
	 * @param array $vars Query vars.
	 * @return array Modified query vars.
	 */
	public function add_query_vars( $vars ) {
		$vars[] = 'custom_pwa_manifest';
		return $vars;
	}

	/**
	 * Serve the manifest file.
	 */
	public function serve_manifest() {
		if ( ! get_query_var( 'custom_pwa_manifest' ) ) {
			return;
		}

		// Check if PWA is enabled.
		$config = get_option( 'custom_pwa_config', array() );
		if ( empty( $config['enable_pwa'] ) ) {
			status_header( 404 );
			exit;
		}

		$manifest = $this->build_manifest();

		/**
		 * Filter the manifest data before output.
		 *
		 * @since 1.0.0
		 *
		 * @param array $manifest Manifest data array.
		 */
		$manifest = apply_filters( 'custom_pwa_manifest_data', $manifest );

		header( 'Content-Type: application/manifest+json; charset=utf-8' );
		echo wp_json_encode( $manifest, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT );
		exit;
	}

	/**
	 * Build manifest array.
	 *
	 * @return array Manifest data.
	 */
	private function build_manifest() {
		$options = get_option( $this->option_name, array() );

		$manifest = array(
			'name'             => isset( $options['app_name'] ) ? $options['app_name'] : get_bloginfo( 'name' ),
			'short_name'       => isset( $options['short_name'] ) ? $options['short_name'] : get_bloginfo( 'name' ),
			'description'      => isset( $options['description'] ) ? $options['description'] : get_bloginfo( 'description' ),
			'start_url'        => isset( $options['start_url'] ) ? $options['start_url'] : home_url( '/' ),
			'display'          => isset( $options['display'] ) ? $options['display'] : 'standalone',
			'background_color' => isset( $options['background_color'] ) ? $options['background_color'] : '#ffffff',
			'theme_color'      => isset( $options['theme_color'] ) ? $options['theme_color'] : '#000000',
			'icons'            => $this->get_icons(),
		);

		return $manifest;
	}

	/**
	 * Get icons array for manifest.
	 *
	 * @return array Icons array.
	 */
	private function get_icons() {
		$options = get_option( $this->option_name, array() );
		$icon_id = isset( $options['icon_id'] ) ? absint( $options['icon_id'] ) : 0;

		$icons = array();

		// Use custom icon if set.
		if ( $icon_id ) {
			$icon_url = wp_get_attachment_url( $icon_id );
			if ( $icon_url ) {
				$icons[] = array(
					'src'   => $icon_url,
					'sizes' => '512x512',
					'type'  => 'image/png',
				);
				$icons[] = array(
					'src'   => $icon_url,
					'sizes' => '192x192',
					'type'  => 'image/png',
				);
			}
		}

		// Fallback to Site Icon.
		if ( empty( $icons ) ) {
			$site_icon_url = get_site_icon_url( 512 );
			if ( $site_icon_url ) {
				$icons[] = array(
					'src'   => $site_icon_url,
					'sizes' => '512x512',
					'type'  => 'image/png',
				);
			}

			$site_icon_url_192 = get_site_icon_url( 192 );
			if ( $site_icon_url_192 ) {
				$icons[] = array(
					'src'   => $site_icon_url_192,
					'sizes' => '192x192',
					'type'  => 'image/png',
				);
			}
		}

		return $icons;
	}

	/**
	 * Inject PWA meta tags into <head>.
	 */
	public function inject_head_tags() {
		// Check if PWA is enabled.
		$config = get_option( 'custom_pwa_config', array() );
		if ( empty( $config['enable_pwa'] ) ) {
			return;
		}

		$options = get_option( $this->option_name, array() );

		// Manifest link.
		echo '<link rel="manifest" href="' . esc_url( home_url( '/manifest.webmanifest' ) ) . '" />' . "\n";

		// Theme color.
		$theme_color = isset( $options['theme_color'] ) ? $options['theme_color'] : '#000000';
		echo '<meta name="theme-color" content="' . esc_attr( $theme_color ) . '" />' . "\n";

		// Apple touch icon.
		$icon_id = isset( $options['icon_id'] ) ? absint( $options['icon_id'] ) : 0;
		$icon_url = $icon_id ? wp_get_attachment_url( $icon_id ) : get_site_icon_url( 180 );
		if ( $icon_url ) {
			echo '<link rel="apple-touch-icon" href="' . esc_url( $icon_url ) . '" />' . "\n";
		}

		// Apple meta tags.
		echo '<meta name="apple-mobile-web-app-capable" content="yes" />' . "\n";
		echo '<meta name="apple-mobile-web-app-status-bar-style" content="default" />' . "\n";
		
		$app_name = isset( $options['app_name'] ) ? $options['app_name'] : get_bloginfo( 'name' );
		echo '<meta name="apple-mobile-web-app-title" content="' . esc_attr( $app_name ) . '" />' . "\n";

		/**
		 * Fires after PWA head tags are injected.
		 *
		 * @since 1.0.0
		 */
		do_action( 'custom_pwa_head_tags_injected' );
	}
}
