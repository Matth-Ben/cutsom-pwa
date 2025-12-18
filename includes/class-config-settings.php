<?php
/**
 * Config Settings Class
 * 
 * Handles global plugin configuration:
 * - Enable PWA features
 * - Enable Web Push notifications
 * - Site type selection
 * - Post type selection for Web Push
 * - Debug mode
 *
 * @package Custom_PWA
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Custom_PWA_Config_Settings class.
 * 
 * Manages global configuration options using WordPress Settings API.
 */
class Custom_PWA_Config_Settings {

	/**
	 * Option name for storing config settings.
	 *
	 * @var string
	 */
	private $option_name = 'custom_pwa_config';

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Register settings using WordPress Settings API.
	 */
	public function register_settings() {
		register_setting(
			'custom_pwa_config_group',
			$this->option_name,
			array(
				'sanitize_callback' => array( $this, 'sanitize_config' ),
			)
		);

		// General section.
		add_settings_section(
			'custom_pwa_config_general',
			__( 'General Settings', 'custom-pwa' ),
			array( $this, 'render_general_section' ),
			'custom_pwa_config'
		);

		// Enable PWA.
		add_settings_field(
			'enable_pwa',
			__( 'Enable PWA Features', 'custom-pwa' ),
			array( $this, 'render_enable_pwa_field' ),
			'custom_pwa_config',
			'custom_pwa_config_general'
		);

		// Enable Push.
		add_settings_field(
			'enable_push',
			__( 'Enable Web Push Notifications', 'custom-pwa' ),
			array( $this, 'render_enable_push_field' ),
			'custom_pwa_config',
			'custom_pwa_config_general'
		);

		// Site type.
		add_settings_field(
			'site_type',
			__( 'Site Type', 'custom-pwa' ),
			array( $this, 'render_site_type_field' ),
			'custom_pwa_config',
			'custom_pwa_config_general'
		);

		// Enabled post types.
		add_settings_field(
			'enabled_post_types',
			__( 'Post Types for Web Push', 'custom-pwa' ),
			array( $this, 'render_enabled_post_types_field' ),
			'custom_pwa_config',
			'custom_pwa_config_general'
		);

		// Debug mode.
		add_settings_field(
			'debug_mode',
			__( 'Enable Debug Mode', 'custom-pwa' ),
			array( $this, 'render_debug_mode_field' ),
			'custom_pwa_config',
			'custom_pwa_config_general'
		);

		// Local development mode.
		add_settings_field(
			'local_dev_mode',
			__( 'Local Development Mode', 'custom-pwa' ),
			array( $this, 'render_local_dev_mode_field' ),
			'custom_pwa_config',
			'custom_pwa_config_general'
		);

		/**
		 * Fires after config settings fields are registered.
		 * 
		 * Allows other plugins to add custom configuration fields.
		 *
		 * @since 1.0.0
		 */
		do_action( 'custom_pwa_config_fields_registered' );
	}

	/**
	 * Render the settings page.
	 */
	public function render_settings_page() {
		?>
		<form method="post" action="options.php">
			<?php
			settings_fields( 'custom_pwa_config_group' );
			do_settings_sections( 'custom_pwa_config' );
			submit_button();
			?>
		</form>
		<?php
	}

	/**
	 * Render general section description.
	 */
	public function render_general_section() {
		echo '<p>' . esc_html__( 'Configure global settings for the Custom PWA plugin.', 'custom-pwa' ) . '</p>';
	}

	/**
	 * Render enable PWA field.
	 */
	public function render_enable_pwa_field() {
		$options = get_option( $this->option_name, array() );
		$checked = ! empty( $options['enable_pwa'] ) ? 'checked' : '';
		?>
		<label>
			<input type="checkbox" name="<?php echo esc_attr( $this->option_name ); ?>[enable_pwa]" value="1" <?php echo esc_attr( $checked ); ?> />
			<?php esc_html_e( 'Enable PWA features (manifest, icons, meta tags)', 'custom-pwa' ); ?>
		</label>
		<?php
	}

	/**
	 * Render enable push field.
	 */
	public function render_enable_push_field() {
		$options = get_option( $this->option_name, array() );
		$checked = ! empty( $options['enable_push'] ) ? 'checked' : '';
		?>
		<label>
			<input type="checkbox" name="<?php echo esc_attr( $this->option_name ); ?>[enable_push]" value="1" <?php echo esc_attr( $checked ); ?> />
			<?php esc_html_e( 'Enable Web Push notifications for post types', 'custom-pwa' ); ?>
		</label>
		<?php
	}

	/**
	 * Render site type field.
	 */
	public function render_site_type_field() {
		$options   = get_option( $this->option_name, array() );
		$site_type = isset( $options['site_type'] ) ? $options['site_type'] : 'generic';

		$types = array(
			'generic'    => __( 'Generic', 'custom-pwa' ),
			'ecommerce'  => __( 'E-commerce', 'custom-pwa' ),
			'events'     => __( 'Events (Concerts)', 'custom-pwa' ),
			'custom'     => __( 'Custom', 'custom-pwa' ),
		);

		/**
		 * Filter available site types.
		 *
		 * @since 1.0.0
		 *
		 * @param array $types Site types array (key => label).
		 */
		$types = apply_filters( 'custom_pwa_site_types', $types );
		?>
		<select name="<?php echo esc_attr( $this->option_name ); ?>[site_type]">
			<?php foreach ( $types as $key => $label ) : ?>
				<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $site_type, $key ); ?>>
					<?php echo esc_html( $label ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<p class="description">
			<?php esc_html_e( 'Select the type of site to customize push notification templates and placeholders.', 'custom-pwa' ); ?>
		</p>
		<?php
	}

	/**
	 * Render enabled post types field.
	 */
	public function render_enabled_post_types_field() {
		$options       = get_option( $this->option_name, array() );
		$enabled_types = isset( $options['enabled_post_types'] ) ? $options['enabled_post_types'] : array( 'post' );

		// Get all public post types.
		$post_types = get_post_types( array( 'public' => true ), 'objects' );

		// Exclude attachments and nav_menu_item.
		unset( $post_types['attachment'] );
		unset( $post_types['nav_menu_item'] );

		if ( empty( $post_types ) ) {
			echo '<p>' . esc_html__( 'No public post types found.', 'custom-pwa' ) . '</p>';
			return;
		}

		echo '<fieldset>';
		foreach ( $post_types as $post_type ) {
			$checked = in_array( $post_type->name, $enabled_types, true ) ? 'checked' : '';
			?>
			<label style="display: block; margin-bottom: 8px;">
				<input 
					type="checkbox" 
					name="<?php echo esc_attr( $this->option_name ); ?>[enabled_post_types][]" 
					value="<?php echo esc_attr( $post_type->name ); ?>" 
					<?php echo esc_attr( $checked ); ?>
				/>
				<?php echo esc_html( $post_type->labels->name ); ?> 
				<small>(<?php echo esc_html( $post_type->name ); ?>)</small>
			</label>
			<?php
		}
		echo '</fieldset>';
		?>
		<p class="description">
			<?php esc_html_e( 'Select post types that will trigger Web Push notifications when published.', 'custom-pwa' ); ?>
		</p>
		<?php
	}

	/**
	 * Render debug mode field.
	 */
	public function render_debug_mode_field() {
		$options = get_option( $this->option_name, array() );
		$checked = ! empty( $options['debug_mode'] ) ? 'checked' : '';
		?>
		<label>
			<input type="checkbox" name="<?php echo esc_attr( $this->option_name ); ?>[debug_mode]" value="1" <?php echo esc_attr( $checked ); ?> />
			<?php esc_html_e( 'Enable verbose logging for debugging purposes', 'custom-pwa' ); ?>
		</label>
		<?php
	}

	/**
	 * Render local development mode field.
	 */
	public function render_local_dev_mode_field() {
		$options = get_option( $this->option_name, array() );
		$checked = ! empty( $options['local_dev_mode'] ) ? 'checked' : '';
		?>
		<label>
			<input type="checkbox" name="<?php echo esc_attr( $this->option_name ); ?>[local_dev_mode]" value="1" <?php echo esc_attr( $checked ); ?> />
			<?php esc_html_e( 'Bypass SSL certificate checks for Service Worker (localhost/development only)', 'custom-pwa' ); ?>
		</label>
		<p class="description">
			<?php esc_html_e( '⚠️ Only enable this in local development environments with self-signed SSL certificates. Never use in production!', 'custom-pwa' ); ?>
		</p>
		<?php
	}

	/**
	 * Sanitize config options.
	 *
	 * @param array $input Raw input values.
	 * @return array Sanitized values.
	 */
	public function sanitize_config( $input ) {
		$sanitized = array();

		// Enable PWA.
		$sanitized['enable_pwa'] = ! empty( $input['enable_pwa'] );

		// Enable Push.
		$sanitized['enable_push'] = ! empty( $input['enable_push'] );

		// Site type.
		$valid_types = array( 'generic', 'ecommerce', 'events', 'custom' );
		$site_type   = isset( $input['site_type'] ) ? sanitize_text_field( $input['site_type'] ) : 'generic';
		$sanitized['site_type'] = in_array( $site_type, $valid_types, true ) ? $site_type : 'generic';

		// Enabled post types.
		$sanitized['enabled_post_types'] = array();
		if ( ! empty( $input['enabled_post_types'] ) && is_array( $input['enabled_post_types'] ) ) {
			$post_types = get_post_types( array( 'public' => true ) );
			foreach ( $input['enabled_post_types'] as $post_type ) {
				$post_type = sanitize_text_field( $post_type );
				if ( isset( $post_types[ $post_type ] ) ) {
					$sanitized['enabled_post_types'][] = $post_type;
				}
			}
		}

		// Debug mode.
		$sanitized['debug_mode'] = ! empty( $input['debug_mode'] );

		// Local development mode.
		$sanitized['local_dev_mode'] = ! empty( $input['local_dev_mode'] );

		/**
		 * Filter sanitized config options before saving.
		 *
		 * @since 1.0.0
		 *
		 * @param array $sanitized Sanitized options.
		 * @param array $input     Raw input.
		 */
		return apply_filters( 'custom_pwa_config_options', $sanitized, $input );
	}

	/**
	 * Get a specific config value.
	 *
	 * @param string $key     Option key.
	 * @param mixed  $default Default value if key doesn't exist.
	 * @return mixed Option value.
	 */
	public function get( $key, $default = null ) {
		$options = get_option( $this->option_name, array() );
		return isset( $options[ $key ] ) ? $options[ $key ] : $default;
	}

	/**
	 * Check if PWA is enabled.
	 *
	 * @return bool
	 */
	public function is_pwa_enabled() {
		return (bool) $this->get( 'enable_pwa', false );
	}

	/**
	 * Check if Push is enabled.
	 *
	 * @return bool
	 */
	public function is_push_enabled() {
		return (bool) $this->get( 'enable_push', false );
	}

	/**
	 * Get enabled post types.
	 *
	 * @return array
	 */
	public function get_enabled_post_types() {
		return (array) $this->get( 'enabled_post_types', array( 'post' ) );
	}

	/**
	 * Get site type.
	 *
	 * @return string
	 */
	public function get_site_type() {
		return (string) $this->get( 'site_type', 'generic' );
	}

	/**
	 * Check if debug mode is enabled.
	 *
	 * @return bool
	 */
	public function is_debug_mode() {
		return (bool) $this->get( 'debug_mode', false );
	}

	/**
	 * Check if local development mode is enabled.
	 *
	 * @return bool
	 */
	public function is_local_dev_mode() {
		return (bool) $this->get( 'local_dev_mode', false );
	}
}
