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
		add_action( 'parse_request', array( $this, 'serve_manifest' ) );
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

		// Screenshots.
		add_settings_field(
			'screenshots',
			__( 'Screenshots', 'custom-pwa' ),
			array( $this, 'render_screenshots_field' ),
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
	 * Render screenshots field.
	 */
	public function render_screenshots_field() {
		$options = get_option( $this->option_name, array() );
		$screenshots = isset( $options['screenshots'] ) ? $options['screenshots'] : array();
		?>
		<div class="custom-pwa-screenshots-upload">
			<input 
				type="hidden" 
				id="custom_pwa_screenshots" 
				name="<?php echo esc_attr( $this->option_name ); ?>[screenshots]" 
				value="<?php echo esc_attr( wp_json_encode( $screenshots ) ); ?>" 
			/>
			<div id="custom_pwa_screenshots_preview" style="margin-bottom: 10px; display: flex; flex-wrap: wrap; gap: 10px;">
				<?php if ( ! empty( $screenshots ) ) : ?>
					<?php foreach ( $screenshots as $screenshot ) : ?>
						<?php
						$img_id = isset( $screenshot['id'] ) ? $screenshot['id'] : 0;
						$img_url = $img_id ? wp_get_attachment_url( $img_id ) : '';
						$form_factor = isset( $screenshot['form_factor'] ) ? $screenshot['form_factor'] : 'narrow';
						?>
						<?php if ( $img_url ) : ?>
							<div class="screenshot-item" data-id="<?php echo esc_attr( $img_id ); ?>" style="position: relative; border: 1px solid #ddd; padding: 5px;">
								<img src="<?php echo esc_url( $img_url ); ?>" alt="Screenshot" style="max-width: 150px; height: auto; display: block;" />
								<div style="margin-top: 5px; font-size: 11px;">
									<strong><?php echo esc_html( ucfirst( $form_factor ) ); ?></strong>
								</div>
								<button type="button" class="button button-small custom_pwa_remove_screenshot" style="margin-top: 5px;">
									<?php esc_html_e( 'Remove', 'custom-pwa' ); ?>
								</button>
							</div>
						<?php endif; ?>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>
			<button type="button" class="button" id="custom_pwa_upload_screenshot_mobile_button">
				<?php esc_html_e( 'Add Mobile Screenshot', 'custom-pwa' ); ?>
			</button>
			<button type="button" class="button" id="custom_pwa_upload_screenshot_desktop_button">
				<?php esc_html_e( 'Add Desktop Screenshot', 'custom-pwa' ); ?>
			</button>
			<p class="description">
				<?php esc_html_e( 'Add screenshots for richer PWA install UI. Mobile: portrait (narrow), Desktop: landscape (wide). Recommended sizes: 540x720px (mobile), 1280x720px (desktop).', 'custom-pwa' ); ?>
			</p>
		</div>

		<script type="text/javascript">
		jQuery(document).ready(function($) {
			var screenshotsData = <?php echo wp_json_encode( $screenshots ); ?>;

			function updateScreenshotsField() {
				$('#custom_pwa_screenshots').val(JSON.stringify(screenshotsData));
			}

			function addScreenshot(formFactor) {
				var mediaUploader = wp.media({
					title: formFactor === 'wide' ? '<?php esc_html_e( 'Select Desktop Screenshot', 'custom-pwa' ); ?>' : '<?php esc_html_e( 'Select Mobile Screenshot', 'custom-pwa' ); ?>',
					button: {
						text: '<?php esc_html_e( 'Use this screenshot', 'custom-pwa' ); ?>'
					},
					multiple: false,
					library: {
						type: 'image'
					}
				});

				mediaUploader.on('select', function() {
					var attachment = mediaUploader.state().get('selection').first().toJSON();
					var screenshot = {
						id: attachment.id,
						form_factor: formFactor
					};
					screenshotsData.push(screenshot);
					
					var html = '<div class="screenshot-item" data-id="' + attachment.id + '" style="position: relative; border: 1px solid #ddd; padding: 5px;">' +
						'<img src="' + attachment.url + '" alt="Screenshot" style="max-width: 150px; height: auto; display: block;" />' +
						'<div style="margin-top: 5px; font-size: 11px;"><strong>' + (formFactor === 'wide' ? 'Wide' : 'Narrow') + '</strong></div>' +
						'<button type="button" class="button button-small custom_pwa_remove_screenshot" style="margin-top: 5px;"><?php esc_html_e( 'Remove', 'custom-pwa' ); ?></button>' +
						'</div>';
					$('#custom_pwa_screenshots_preview').append(html);
					updateScreenshotsField();
				});

				mediaUploader.open();
			}

			$('#custom_pwa_upload_screenshot_mobile_button').on('click', function(e) {
				e.preventDefault();
				addScreenshot('narrow');
			});

			$('#custom_pwa_upload_screenshot_desktop_button').on('click', function(e) {
				e.preventDefault();
				addScreenshot('wide');
			});

			$(document).on('click', '.custom_pwa_remove_screenshot', function(e) {
				e.preventDefault();
				var $item = $(this).closest('.screenshot-item');
				var imgId = $item.data('id');
				screenshotsData = screenshotsData.filter(function(s) { return s.id != imgId; });
				$item.remove();
				updateScreenshotsField();
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

		// Sanitize screenshots.
		$sanitized['screenshots'] = array();
		if ( isset( $input['screenshots'] ) && ! empty( $input['screenshots'] ) ) {
			$screenshots = json_decode( $input['screenshots'], true );
			if ( is_array( $screenshots ) ) {
				foreach ( $screenshots as $screenshot ) {
					if ( isset( $screenshot['id'] ) && isset( $screenshot['form_factor'] ) ) {
						$sanitized['screenshots'][] = array(
							'id'          => absint( $screenshot['id'] ),
							'form_factor' => sanitize_text_field( $screenshot['form_factor'] ),
						);
					}
				}
			}
		}

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
	 * 
	 * @param WP $wp Current WordPress environment instance.
	 */
	public function serve_manifest( $wp ) {
		if ( ! isset( $wp->query_vars['custom_pwa_manifest'] ) || ! $wp->query_vars['custom_pwa_manifest'] ) {
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

		// Clear any output buffers
		while ( ob_get_level() > 0 ) {
			ob_end_clean();
		}

		// Prevent WordPress from continuing
		status_header( 200 );
		header( 'Content-Type: application/manifest+json; charset=utf-8' );
		header( 'X-Robots-Tag: noindex' );
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

		$display = isset( $options['display'] ) ? $options['display'] : 'standalone';

		$manifest = array(
			'id'               => '/',
			'name'             => isset( $options['app_name'] ) ? $options['app_name'] : get_bloginfo( 'name' ),
			'short_name'       => isset( $options['short_name'] ) ? $options['short_name'] : get_bloginfo( 'name' ),
			'description'      => isset( $options['description'] ) ? $options['description'] : substr( get_bloginfo( 'description' ), 0, 300 ),
			'start_url'        => isset( $options['start_url'] ) ? $options['start_url'] : home_url( '/' ),
			'scope'            => '/',
			'display'          => $display,
			'display_override' => array( 'window-controls-overlay', $display ),
			'background_color' => isset( $options['background_color'] ) ? $options['background_color'] : '#ffffff',
			'theme_color'      => isset( $options['theme_color'] ) ? $options['theme_color'] : '#000000',
			'icons'            => $this->get_icons(),
			'screenshots'      => $this->get_screenshots(),
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
				// Get the actual image size
				$icon_path = get_attached_file( $icon_id );
				if ( $icon_path && file_exists( $icon_path ) ) {
					$image_size = getimagesize( $icon_path );
					if ( $image_size ) {
						$width = $image_size[0];
						$height = $image_size[1];
						$mime_type = $image_size['mime'];
						
						// Add the actual size of the icon
						// Chrome requires at least 192x192, so check that
						if ( $width >= 192 && $height >= 192 ) {
							// Add icon with actual size and 'any' purpose
							$icons[] = array(
								'src'     => $icon_url,
								'sizes'   => $width . 'x' . $height,
								'type'    => $mime_type,
								'purpose' => 'any',
							);
							
							// Also add a maskable version for better Android support
							$icons[] = array(
								'src'     => $icon_url,
								'sizes'   => $width . 'x' . $height,
								'type'    => $mime_type,
								'purpose' => 'maskable',
							);
						}
					}
				}
			}
		}

		// Fallback to Site Icon.
		if ( empty( $icons ) ) {
			$site_icon_id = get_option( 'site_icon' );
			if ( $site_icon_id ) {
				$site_icon_url = wp_get_attachment_url( $site_icon_id );
				$site_icon_path = get_attached_file( $site_icon_id );
				
				if ( $site_icon_path && file_exists( $site_icon_path ) ) {
					$image_size = getimagesize( $site_icon_path );
					if ( $image_size ) {
						$actual_size = $image_size[0] . 'x' . $image_size[1];
						$mime_type = $image_size['mime'];
						
						$icons[] = array(
							'src'     => $site_icon_url,
							'sizes'   => $actual_size,
							'type'    => $mime_type,
							'purpose' => 'any maskable',
						);
					}
				}
			}
			
			// Additional fallback sizes
			$sizes = array( 512, 192 );
			foreach ( $sizes as $size ) {
				$site_icon_url = get_site_icon_url( $size );
				if ( $site_icon_url && ! $this->icon_exists( $icons, $site_icon_url ) ) {
					$icons[] = array(
						'src'     => $site_icon_url,
						'sizes'   => $size . 'x' . $size,
						'type'    => 'image/png',
						'purpose' => 'any',
					);
				}
			}
		}

		return $icons;
	}

	/**
	 * Check if icon already exists in array.
	 *
	 * @param array  $icons Icon array.
	 * @param string $url   Icon URL to check.
	 * @return bool True if exists.
	 */
	private function icon_exists( $icons, $url ) {
		foreach ( $icons as $icon ) {
			if ( isset( $icon['src'] ) && $icon['src'] === $url ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Get screenshots array for manifest.
	 *
	 * @return array Screenshots array.
	 */
	private function get_screenshots() {
		$options = get_option( $this->option_name, array() );
		$screenshots_data = isset( $options['screenshots'] ) ? $options['screenshots'] : array();
		
		$screenshots = array();
		
		foreach ( $screenshots_data as $screenshot ) {
			$img_id = isset( $screenshot['id'] ) ? absint( $screenshot['id'] ) : 0;
			$form_factor = isset( $screenshot['form_factor'] ) ? $screenshot['form_factor'] : 'narrow';
			
			if ( $img_id ) {
				$img_url = wp_get_attachment_url( $img_id );
				$img_path = get_attached_file( $img_id );
				
				if ( $img_url && $img_path && file_exists( $img_path ) ) {
					$image_size = getimagesize( $img_path );
					if ( $image_size ) {
						$screenshot_item = array(
							'src'   => $img_url,
							'sizes' => $image_size[0] . 'x' . $image_size[1],
							'type'  => $image_size['mime'],
						);
						
						// Add form_factor only if it's 'wide', otherwise omit for narrow (default)
						if ( $form_factor === 'wide' ) {
							$screenshot_item['form_factor'] = 'wide';
						}
						
						$screenshots[] = $screenshot_item;
					}
				}
			}
		}
		
		/**
		 * Filter the screenshots data for PWA manifest.
		 *
		 * @since 1.0.1
		 *
		 * @param array $screenshots Screenshots array.
		 */
		return apply_filters( 'custom_pwa_manifest_screenshots', $screenshots );
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
