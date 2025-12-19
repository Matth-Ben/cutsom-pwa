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
		add_action( 'admin_init', array( $this, 'handle_vapid_regeneration' ) );
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

		// VAPID Keys section.
		add_settings_section(
			'custom_pwa_config_vapid',
			__( 'VAPID Keys', 'custom-pwa' ),
			array( $this, 'render_vapid_section' ),
			'custom_pwa_config'
		);

		// VAPID Keys display.
		add_settings_field(
			'vapid_keys',
			__( 'Push Notification Keys', 'custom-pwa' ),
			array( $this, 'render_vapid_keys_field' ),
			'custom_pwa_config',
			'custom_pwa_config_vapid'
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
		<div class="custom-pwa-card">
			<form method="post" action="options.php">
				<?php
				settings_fields( 'custom_pwa_config_group' );
				do_settings_sections( 'custom_pwa_config' );
				submit_button( null, 'cp-btn primary' );
				?>
			</form>
		</div>
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
		$post_type_roles = isset( $options['post_type_roles'] ) ? $options['post_type_roles'] : array();

		// Get all public post types.
		$post_types = get_post_types( array( 'public' => true ), 'objects' );

		// Exclude attachments and nav_menu_item.
		unset( $post_types['attachment'] );
		unset( $post_types['nav_menu_item'] );

		if ( empty( $post_types ) ) {
			echo '<p>' . esc_html__( 'No public post types found.', 'custom-pwa' ) . '</p>';
			return;
		}

		// Get available roles from scenarios.
		require_once plugin_dir_path( __FILE__ ) . 'class-push-scenarios.php';
		$available_roles = Custom_PWA_Push_Scenarios::get_roles();

		echo '<fieldset>';
		echo '<p class="description" style="margin-top:0;">' . esc_html__( 'Select post types and assign roles to customize push notification scenarios.', 'custom-pwa' ) . '</p>';
		echo '<table class="widefat" style="margin-top:12px; max-width:800px;">';
		echo '<thead>';
		echo '<tr>';
		echo '<th style="width:40px; padding:8px; text-align:center;">' . esc_html__( 'Enable', 'custom-pwa' ) . '</th>';
		echo '<th style="padding:8px;">' . esc_html__( 'Post Type', 'custom-pwa' ) . '</th>';
		echo '<th style="padding:8px;">' . esc_html__( 'Role', 'custom-pwa' ) . '</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';
		
		foreach ( $post_types as $post_type ) {
			$checked = in_array( $post_type->name, $enabled_types, true ) ? 'checked' : '';
			$selected_role = isset( $post_type_roles[ $post_type->name ] ) ? $post_type_roles[ $post_type->name ] : 'generic';
			
			echo '<tr>';
			echo '<td style="text-align:center; padding:8px;">';
			echo '<input type="checkbox" name="' . esc_attr( $this->option_name ) . '[enabled_post_types][]" value="' . esc_attr( $post_type->name ) . '" ' . esc_attr( $checked ) . ' />';
			echo '</td>';
			echo '<td style="padding:8px;">';
			echo '<strong>' . esc_html( $post_type->labels->name ) . '</strong> ';
			echo '<small style="color:#666;">(' . esc_html( $post_type->name ) . ')</small>';
			echo '</td>';
			echo '<td style="padding:8px;">';
			echo '<select name="' . esc_attr( $this->option_name ) . '[post_type_roles][' . esc_attr( $post_type->name ) . ']" style="width:auto;">';
			foreach ( $available_roles as $role_key => $role_label ) {
				echo '<option value="' . esc_attr( $role_key ) . '" ' . selected( $selected_role, $role_key, false ) . '>' . esc_html( $role_label ) . '</option>';
			}
			echo '</select>';
			echo '</td>';
			echo '</tr>';
		}
		
		echo '</tbody>';
		echo '</table>';
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
	 * Render VAPID section description.
	 */
	public function render_vapid_section() {
		?>
		<p>
			<?php esc_html_e( 'VAPID (Voluntary Application Server Identification) keys are cryptographic keys used to authenticate your server when sending push notifications.', 'custom-pwa' ); ?>
		</p>
		<p>
			<?php esc_html_e( 'These keys are automatically generated when you activate the plugin. You can regenerate them if needed (this will invalidate all existing subscriptions).', 'custom-pwa' ); ?>
		</p>
		<?php
	}

	/**
	 * Render VAPID keys field.
	 */
	public function render_vapid_keys_field() {
		$vapid_keys = get_option( 'custom_pwa_push', array() );
		$public_key = ! empty( $vapid_keys['public_key'] ) ? $vapid_keys['public_key'] : '';
		$private_key = ! empty( $vapid_keys['private_key'] ) ? $vapid_keys['private_key'] : '';

		// Check if regeneration was successful
		if ( isset( $_GET['vapid_regenerated'] ) && $_GET['vapid_regenerated'] === 'success' ) {
			echo '<div class="notice notice-success inline" style="margin: 10px 0;"><p>';
			echo '<strong>' . esc_html__( '✅ VAPID keys regenerated successfully!', 'custom-pwa' ) . '</strong><br>';
			echo esc_html__( 'All previous subscriptions have been invalidated. Users will need to resubscribe to receive notifications.', 'custom-pwa' );
			echo '</p></div>';
		}

		?>
		<div style="background: #f8f9fa; border: 1px solid #ddd; border-radius: 4px; padding: 15px; margin-bottom: 15px;">
			<div style="margin-bottom: 15px;">
				<strong><?php esc_html_e( 'Status:', 'custom-pwa' ); ?></strong>
				<?php if ( ! empty( $public_key ) && ! empty( $private_key ) ) : ?>
					<span style="color: #46b450; font-weight: bold;">✅ <?php esc_html_e( 'Keys Generated', 'custom-pwa' ); ?></span>
				<?php else : ?>
					<span style="color: #dc3232; font-weight: bold;">❌ <?php esc_html_e( 'Keys Missing', 'custom-pwa' ); ?></span>
				<?php endif; ?>
			</div>

			<table class="widefat" style="max-width: 900px; background: white;">
				<thead>
					<tr>
						<th style="width: 150px; padding: 10px;"><?php esc_html_e( 'Key Type', 'custom-pwa' ); ?></th>
						<th style="padding: 10px;"><?php esc_html_e( 'Value', 'custom-pwa' ); ?></th>
						<th style="width: 80px; text-align: center; padding: 10px;"><?php esc_html_e( 'Length', 'custom-pwa' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td style="padding: 10px; font-weight: bold;">
							<?php esc_html_e( 'Public Key', 'custom-pwa' ); ?>
							<br>
							<small style="color: #666; font-weight: normal;"><?php esc_html_e( '(Shared with browsers)', 'custom-pwa' ); ?></small>
						</td>
						<td style="padding: 10px; font-family: monospace; font-size: 12px; word-break: break-all;">
							<?php if ( ! empty( $public_key ) ) : ?>
								<code style="background: #f0f0f0; padding: 5px; display: block;"><?php echo esc_html( $public_key ); ?></code>
							<?php else : ?>
								<em style="color: #999;"><?php esc_html_e( 'Not generated', 'custom-pwa' ); ?></em>
							<?php endif; ?>
						</td>
						<td style="padding: 10px; text-align: center;">
							<?php echo ! empty( $public_key ) ? esc_html( strlen( $public_key ) ) : '—'; ?>
						</td>
					</tr>
					<tr style="background: #f9f9f9;">
						<td style="padding: 10px; font-weight: bold;">
							<?php esc_html_e( 'Private Key', 'custom-pwa' ); ?>
							<br>
							<small style="color: #666; font-weight: normal;"><?php esc_html_e( '(Keep secret)', 'custom-pwa' ); ?></small>
						</td>
						<td style="padding: 10px; font-family: monospace; font-size: 12px; word-break: break-all;">
							<?php if ( ! empty( $private_key ) ) : ?>
								<code style="background: #fff3cd; padding: 5px; display: block; border-left: 3px solid #ffc107;">
									<?php echo esc_html( substr( $private_key, 0, 50 ) . '...' . substr( $private_key, -20 ) ); ?>
								</code>
								<small style="color: #856404;">
									⚠️ <?php esc_html_e( 'Private key truncated for security. Never share this key publicly.', 'custom-pwa' ); ?>
								</small>
							<?php else : ?>
								<em style="color: #999;"><?php esc_html_e( 'Not generated', 'custom-pwa' ); ?></em>
							<?php endif; ?>
						</td>
						<td style="padding: 10px; text-align: center;">
							<?php echo ! empty( $private_key ) ? esc_html( strlen( $private_key ) ) : '—'; ?>
						</td>
					</tr>
				</tbody>
			</table>

			<div style="margin-top: 15px; padding: 12px; background: #fff3cd; border-left: 4px solid #ffc107; border-radius: 4px;">
				<strong style="color: #856404;">⚠️ <?php esc_html_e( 'Important:', 'custom-pwa' ); ?></strong>
				<ul style="margin: 8px 0 0 20px; color: #856404;">
					<li><?php esc_html_e( 'These keys are used to sign push notifications', 'custom-pwa' ); ?></li>
					<li><?php esc_html_e( 'Generated automatically using OpenSSL (EC P-256 curve)', 'custom-pwa' ); ?></li>
					<li><?php esc_html_e( 'Regenerating keys will invalidate ALL existing subscriptions', 'custom-pwa' ); ?></li>
					<li><?php esc_html_e( 'Never share your private key', 'custom-pwa' ); ?></li>
				</ul>
			</div>
		</div>

		<form method="post" action="" style="margin-top: 20px;">
			<?php wp_nonce_field( 'custom_pwa_regenerate_vapid', 'custom_pwa_vapid_nonce' ); ?>
			<button 
				type="submit" 
				name="custom_pwa_regenerate_vapid" 
				class="button button-secondary"
				onclick="return confirm('<?php echo esc_js( __( 'Are you sure you want to regenerate VAPID keys?\n\nThis will:\n• Invalidate all existing subscriptions\n• Users will need to resubscribe\n• Cannot be undone\n\nContinue?', 'custom-pwa' ) ); ?>');"
			>
				🔄 <?php esc_html_e( 'Regenerate VAPID Keys', 'custom-pwa' ); ?>
			</button>
			<p class="description">
				<?php esc_html_e( 'Only regenerate if you suspect the keys have been compromised or need to reset all subscriptions.', 'custom-pwa' ); ?>
			</p>
		</form>

		<div style="margin-top: 20px; padding: 12px; background: #e7f3ff; border-left: 4px solid #2196f3; border-radius: 4px;">
			<strong style="color: #0c5c9e;">ℹ️ <?php esc_html_e( 'Technical Details:', 'custom-pwa' ); ?></strong>
			<ul style="margin: 8px 0 0 20px; color: #0c5c9e;">
				<li><?php esc_html_e( 'Algorithm: Elliptic Curve P-256 (prime256v1)', 'custom-pwa' ); ?></li>
				<li><?php esc_html_e( 'Public Key: 65 bytes (uncompressed format, base64url encoded)', 'custom-pwa' ); ?></li>
				<li><?php esc_html_e( 'Private Key: PEM format (base64url encoded)', 'custom-pwa' ); ?></li>
				<li><?php esc_html_e( 'Standard: RFC 8292 (VAPID for Web Push)', 'custom-pwa' ); ?></li>
			</ul>
		</div>
		<?php
	}

	/**
	 * Handle VAPID key regeneration.
	 */
	public function handle_vapid_regeneration() {
		// Check if regeneration is requested
		if ( ! isset( $_POST['custom_pwa_regenerate_vapid'] ) ) {
			return;
		}

		// Verify nonce
		if ( ! isset( $_POST['custom_pwa_vapid_nonce'] ) || ! wp_verify_nonce( $_POST['custom_pwa_vapid_nonce'], 'custom_pwa_regenerate_vapid' ) ) {
			wp_die( esc_html__( 'Security check failed. Please try again.', 'custom-pwa' ) );
		}

		// Check user permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'custom-pwa' ) );
		}

		// Generate new VAPID keys
		$new_keys = $this->generate_vapid_keys();

		if ( empty( $new_keys['public_key'] ) || empty( $new_keys['private_key'] ) ) {
			wp_die( esc_html__( 'Failed to generate VAPID keys. Please check that OpenSSL is installed and enabled.', 'custom-pwa' ) );
		}

		// Save new keys
		update_option( 'custom_pwa_push', $new_keys );

		// Clear all subscriptions (they're now invalid)
		global $wpdb;
		$table_name = $wpdb->prefix . 'custom_pwa_subscriptions';
		$wpdb->query( "TRUNCATE TABLE {$table_name}" );

		// Log the regeneration
		if ( function_exists( 'error_log' ) ) {
			error_log( '[Custom PWA] VAPID keys regenerated. All subscriptions cleared.' );
		}

		// Redirect with success message
		$redirect_url = add_query_arg(
			array(
				'page'             => 'custom-pwa-config',
				'vapid_regenerated' => 'success',
			),
			admin_url( 'admin.php' )
		);
		wp_safe_redirect( $redirect_url );
		exit;
	}

	/**
	 * Generate VAPID keys for Web Push.
	 * 
	 * @return array Array with 'public_key' and 'private_key'.
	 */
	private function generate_vapid_keys() {
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
	 * Base64url encode.
	 *
	 * @param string $data Data to encode.
	 * @return string Base64url encoded string.
	 */
	private function base64url_encode( $data ) {
		return rtrim( strtr( base64_encode( $data ), '+/', '-_' ), '=' );
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

		// Post type roles.
		$sanitized['post_type_roles'] = array();
		if ( ! empty( $input['post_type_roles'] ) && is_array( $input['post_type_roles'] ) ) {
			$valid_roles = array( 'blog', 'events', 'ecommerce', 'generic' );
			
			// Allow filtering of valid roles.
			$valid_roles = array_keys( apply_filters( 'custom_pwa_valid_roles', array_combine( $valid_roles, $valid_roles ) ) );
			
			foreach ( $input['post_type_roles'] as $post_type => $role ) {
				$post_type = sanitize_text_field( $post_type );
				$role = sanitize_text_field( $role );
				
				if ( post_type_exists( $post_type ) && in_array( $role, $valid_roles, true ) ) {
					$sanitized['post_type_roles'][ $post_type ] = $role;
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

	/**
	 * Get post type roles configuration.
	 *
	 * @return array Associative array of post_type => role.
	 */
	public function get_post_type_roles() {
		return (array) $this->get( 'post_type_roles', array() );
	}

	/**
	 * Get the role assigned to a specific post type.
	 *
	 * @param string $post_type Post type name.
	 * @return string Role key (defaults to 'generic').
	 */
	public function get_post_type_role( $post_type ) {
		$roles = $this->get_post_type_roles();
		return isset( $roles[ $post_type ] ) ? $roles[ $post_type ] : 'generic';
	}
}
