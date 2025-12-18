<?php
/**
 * Push Settings Class
 * 
 * Handles Web Push notification configuration:
 * - Enable/disable push per post type
 * - Notification templates (title, body, URL)
 * - Template placeholders
 * - Test notification tool
 * - Optional log viewer
 *
 * @package Custom_PWA
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Custom_PWA_Push_Settings class.
 * 
 * Manages Web Push notification rules and templates.
 */
class Custom_PWA_Push_Settings {

	/**
	 * Option name for storing push rules.
	 *
	 * @var string
	 */
	private $option_name = 'custom_pwa_push_rules';

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
	}

	/**
	 * Register settings using WordPress Settings API.
	 */
	public function register_settings() {
		register_setting(
			'custom_pwa_push_group',
			$this->option_name,
			array(
				'sanitize_callback' => array( $this, 'sanitize_push_rules' ),
			)
		);

		// Push rules section.
		add_settings_section(
			'custom_pwa_push_rules_section',
			__( 'Push Notification Rules', 'custom-pwa' ),
			array( $this, 'render_rules_section' ),
			'custom_pwa_push'
		);

		// Test notification section.
		add_settings_section(
			'custom_pwa_push_test_section',
			__( 'Test Notifications', 'custom-pwa' ),
			array( $this, 'render_test_section' ),
			'custom_pwa_push'
		);
	}

	/**
	 * Render the settings page.
	 */
	public function render_settings_page() {
		?>
		<form method="post" action="options.php">
			<?php
			settings_fields( 'custom_pwa_push_group' );
			do_settings_sections( 'custom_pwa_push' );
			
			// Render post type rules.
			$this->render_post_type_rules();
			
			submit_button();
			?>
		</form>

		<?php $this->render_test_tool(); ?>
		<?php
	}

	/**
	 * Render rules section description.
	 */
	public function render_rules_section() {
		echo '<p>' . esc_html__( 'Configure push notification templates for each post type.', 'custom-pwa' ) . '</p>';
		echo '<p>' . esc_html__( 'Available placeholders: {post_title}, {permalink}, {excerpt}, {post_type}, {event_date}, {venue}, {status_label}', 'custom-pwa' ) . '</p>';
	}

	/**
	 * Render test section description.
	 */
	public function render_test_section() {
		// Empty - content is rendered in render_test_tool.
	}

	/**
	 * Render post type rules.
	 */
	private function render_post_type_rules() {
		$config = get_option( 'custom_pwa_config', array() );
		$enabled_post_types = isset( $config['enabled_post_types'] ) ? $config['enabled_post_types'] : array();

		if ( empty( $enabled_post_types ) ) {
			echo '<p>' . esc_html__( 'No post types are enabled for Web Push. Please configure them in the Config page.', 'custom-pwa' ) . '</p>';
			return;
		}

		$rules = get_option( $this->option_name, array() );

		echo '<table class="form-table" role="presentation">';
		
		foreach ( $enabled_post_types as $post_type ) {
			$post_type_object = get_post_type_object( $post_type );
			if ( ! $post_type_object ) {
				continue;
			}

			$rule = isset( $rules[ $post_type ] ) ? $rules[ $post_type ] : $this->get_default_rule( $post_type );

			echo '<tr>';
			echo '<th scope="row" colspan="2">';
			echo '<h3>' . esc_html( $post_type_object->labels->name ) . ' <small>(' . esc_html( $post_type ) . ')</small></h3>';
			echo '</th>';
			echo '</tr>';

			// Enable checkbox.
			echo '<tr>';
			echo '<th scope="row">' . esc_html__( 'Enable Notifications', 'custom-pwa' ) . '</th>';
			echo '<td>';
			echo '<label>';
			echo '<input type="checkbox" name="' . esc_attr( $this->option_name ) . '[' . esc_attr( $post_type ) . '][enabled]" value="1" ' . checked( ! empty( $rule['enabled'] ), true, false ) . ' />';
			echo ' ' . esc_html__( 'Send push notifications when this post type is published', 'custom-pwa' );
			echo '</label>';
			echo '</td>';
			echo '</tr>';

			// Title template.
			echo '<tr>';
			echo '<th scope="row">' . esc_html__( 'Title Template', 'custom-pwa' ) . '</th>';
			echo '<td>';
			echo '<input type="text" name="' . esc_attr( $this->option_name ) . '[' . esc_attr( $post_type ) . '][title]" value="' . esc_attr( $rule['title'] ) . '" class="large-text" />';
			echo '</td>';
			echo '</tr>';

			// Body template.
			echo '<tr>';
			echo '<th scope="row">' . esc_html__( 'Body Template', 'custom-pwa' ) . '</th>';
			echo '<td>';
			echo '<textarea name="' . esc_attr( $this->option_name ) . '[' . esc_attr( $post_type ) . '][body]" rows="3" class="large-text">' . esc_textarea( $rule['body'] ) . '</textarea>';
			echo '</td>';
			echo '</tr>';

			// URL template.
			echo '<tr>';
			echo '<th scope="row">' . esc_html__( 'URL Template', 'custom-pwa' ) . '</th>';
			echo '<td>';
			echo '<input type="text" name="' . esc_attr( $this->option_name ) . '[' . esc_attr( $post_type ) . '][url]" value="' . esc_attr( $rule['url'] ) . '" class="large-text" />';
			echo '</td>';
			echo '</tr>';

			echo '<tr><td colspan="2"><hr /></td></tr>';
		}

		echo '</table>';
	}

	/**
	 * Get default rule for a post type.
	 *
	 * @param string $post_type Post type name.
	 * @return array Default rule.
	 */
	private function get_default_rule( $post_type ) {
		$post_type_object = get_post_type_object( $post_type );
		$label = $post_type_object ? $post_type_object->labels->singular_name : ucfirst( $post_type );

		return array(
			'enabled' => false,
			'title'   => sprintf( __( 'New %s: {post_title}', 'custom-pwa' ), $label ),
			'body'    => '{excerpt}',
			'url'     => '{permalink}',
		);
	}

	/**
	 * Render test notification tool.
	 */
	private function render_test_tool() {
		?>
		<hr />
		<h2><?php esc_html_e( 'Send Test Notification', 'custom-pwa' ); ?></h2>
		<p><?php esc_html_e( 'Send a test push notification to all subscribed devices.', 'custom-pwa' ); ?></p>
		
		<table class="form-table">
			<tr>
				<th scope="row">
					<label for="test_title"><?php esc_html_e( 'Title', 'custom-pwa' ); ?></label>
				</th>
				<td>
					<input type="text" id="test_title" class="regular-text" value="<?php esc_attr_e( 'Test Notification', 'custom-pwa' ); ?>" />
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="test_body"><?php esc_html_e( 'Body', 'custom-pwa' ); ?></label>
				</th>
				<td>
					<textarea id="test_body" rows="3" class="large-text"><?php esc_html_e( 'This is a test push notification from Custom PWA plugin.', 'custom-pwa' ); ?></textarea>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="test_url"><?php esc_html_e( 'URL', 'custom-pwa' ); ?></label>
				</th>
				<td>
					<input type="text" id="test_url" class="regular-text" value="<?php echo esc_attr( home_url( '/' ) ); ?>" />
				</td>
			</tr>
		</table>

		<p>
			<button type="button" id="custom_pwa_send_test" class="button button-primary">
				<?php esc_html_e( 'Send Test Notification', 'custom-pwa' ); ?>
			</button>
			<span id="custom_pwa_test_result" style="margin-left: 10px;"></span>
		</p>

		<script type="text/javascript">
		jQuery(document).ready(function($) {
			$('#custom_pwa_send_test').on('click', function() {
				var button = $(this);
				var result = $('#custom_pwa_test_result');
				
				button.prop('disabled', true);
				result.html('<span style="color: #999;">Sending...</span>');

				$.ajax({
					url: '<?php echo esc_url( rest_url( 'custom-pwa/v1/test-push' ) ); ?>',
					method: 'POST',
					beforeSend: function(xhr) {
						xhr.setRequestHeader('X-WP-Nonce', '<?php echo esc_js( wp_create_nonce( 'wp_rest' ) ); ?>');
					},
					data: {
						title: $('#test_title').val(),
						body: $('#test_body').val(),
						url: $('#test_url').val()
					},
					success: function(response) {
						result.html('<span style="color: green;">✓ ' + response.message + '</span>');
						button.prop('disabled', false);
					},
					error: function(xhr) {
						var message = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Unknown error';
						result.html('<span style="color: red;">✗ ' + message + '</span>');
						button.prop('disabled', false);
					}
				});
			});
		});
		</script>
		<?php
	}

	/**
	 * Sanitize push rules.
	 *
	 * @param array $input Raw input values.
	 * @return array Sanitized values.
	 */
	public function sanitize_push_rules( $input ) {
		$sanitized = array();

		if ( ! is_array( $input ) ) {
			return $sanitized;
		}

		foreach ( $input as $post_type => $rule ) {
			$post_type = sanitize_key( $post_type );
			
			if ( ! post_type_exists( $post_type ) ) {
				continue;
			}

			$sanitized[ $post_type ] = array(
				'enabled' => ! empty( $rule['enabled'] ),
				'title'   => isset( $rule['title'] ) ? sanitize_text_field( $rule['title'] ) : '',
				'body'    => isset( $rule['body'] ) ? sanitize_textarea_field( $rule['body'] ) : '',
				'url'     => isset( $rule['url'] ) ? sanitize_text_field( $rule['url'] ) : '',
			);
		}

		/**
		 * Filter push rules before saving.
		 *
		 * @since 1.0.0
		 *
		 * @param array $sanitized Sanitized rules.
		 * @param array $input     Raw input.
		 */
		return apply_filters( 'custom_pwa_push_rules', $sanitized, $input );
	}

	/**
	 * Register REST routes for test notifications.
	 */
	public function register_rest_routes() {
		register_rest_route(
			'custom-pwa/v1',
			'/test-push',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'handle_test_push' ),
				'permission_callback' => function() {
					return current_user_can( 'manage_options' );
				},
			)
		);
	}

	/**
	 * Handle test push notification request.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response.
	 */
	public function handle_test_push( $request ) {
		$title = $request->get_param( 'title' );
		$body  = $request->get_param( 'body' );
		$url   = $request->get_param( 'url' );

		if ( empty( $title ) || empty( $body ) ) {
			return new WP_Error(
				'missing_params',
				__( 'Title and body are required.', 'custom-pwa' ),
				array( 'status' => 400 )
			);
		}

		// Get subscriptions count.
		global $wpdb;
		$table_name = $wpdb->prefix . 'custom_pwa_subscriptions';
		$count = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$table_name} WHERE active = %d AND blog_id = %d",
			1,
			get_current_blog_id()
		) );

		// Get all active subscriptions.
		$subscriptions = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$table_name} WHERE active = %d AND blog_id = %d",
			1,
			get_current_blog_id()
		) );

		if ( empty( $subscriptions ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'No active subscriptions found.', 'custom-pwa' ),
				),
				200
			);
		}

		// Send notification to all subscriptions.
		$sent_count = 0;
		$failed_count = 0;

		foreach ( $subscriptions as $subscription ) {
			$payload = array(
				'title' => $title,
				'body'  => $body,
				'icon'  => get_site_icon_url( 192 ),
				'badge' => get_site_icon_url( 96 ),
				'data'  => array(
					'url' => $url,
				),
			);

			$result = $this->send_push_notification( $subscription, $payload );
			if ( $result ) {
				$sent_count++;
			} else {
				$failed_count++;
			}
		}

		return new WP_REST_Response(
			array(
				'success' => $sent_count > 0,
				'message' => sprintf(
					/* translators: %1$d: sent count, %2$d: failed count */
					__( 'Sent: %1$d, Failed: %2$d', 'custom-pwa' ),
					$sent_count,
					$failed_count
				),
			),
			200
		);
	}

	/**
	 * Send push notification to a subscription.
	 *
	 * @param object $subscription Subscription object from database.
	 * @param array  $payload      Notification payload.
	 * @return bool True on success, false on failure.
	 */
	private function send_push_notification( $subscription, $payload ) {
		// Get VAPID keys from options.
		$push_config = get_option( 'custom_pwa_push', array() );
		$vapid_public_key  = ! empty( $push_config['public_key'] ) ? $push_config['public_key'] : '';
		$vapid_private_key = ! empty( $push_config['private_key'] ) ? $push_config['private_key'] : '';

		if ( empty( $vapid_public_key ) || empty( $vapid_private_key ) ) {
			error_log( '[Custom PWA] VAPID keys not configured' );
			return false;
		}

		// Prepare the notification data (must be encrypted for Web Push).
		// For now, we'll send an empty payload and let the service worker use default data.
		$notification_data = '';

		// Parse the endpoint to get the audience (origin).
		$url_parts = wp_parse_url( $subscription->endpoint );
		$audience  = sprintf( '%s://%s', $url_parts['scheme'], $url_parts['host'] );

		// Create JWT token for VAPID authentication.
		$jwt = $this->create_vapid_jwt( $audience, $vapid_private_key );
		if ( ! $jwt ) {
			return false;
		}

		// Send the notification using cURL.
		$ch = curl_init( $subscription->endpoint );
		curl_setopt( $ch, CURLOPT_POST, true );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $notification_data );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
			'Content-Length: ' . strlen( $notification_data ),
			'TTL: 86400', // 24 hours.
			'Crypto-Key: p256ecdsa=' . $vapid_public_key,
			'Authorization: WebPush ' . $jwt,
		) );

		$response = curl_exec( $ch );
		$http_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
		curl_close( $ch );

		// Log if debug mode is enabled.
		if ( get_option( 'custom_pwa_config' )['debug_mode'] ?? false ) {
			error_log( sprintf(
				'[Custom PWA] Push notification sent to %s... | HTTP %d | Response: %s',
				substr( $subscription->endpoint, 0, 50 ),
				$http_code,
				$response
			) );
		}

		// Check if the request was successful (2xx status code).
		return $http_code >= 200 && $http_code < 300;
	}

	/**
	 * Create VAPID JWT token.
	 *
	 * @param string $audience Audience (push service origin).
	 * @param string $private_key_base64url VAPID private key (base64url encoded PEM).
	 * @return string|false JWT token or false on failure.
	 */
	private function create_vapid_jwt( $audience, $private_key_base64url ) {
		// Decode the base64url private key.
		$private_key_pem = $this->base64url_decode( $private_key_base64url );

		// JWT header.
		$header = array(
			'typ' => 'JWT',
			'alg' => 'ES256',
		);

		// JWT payload.
		$payload = array(
			'aud' => $audience,
			'exp' => time() + 43200, // 12 hours.
			'sub' => 'mailto:' . get_bloginfo( 'admin_email' ),
		);

		// Encode header and payload.
		$header_encoded  = $this->base64url_encode( wp_json_encode( $header ) );
		$payload_encoded = $this->base64url_encode( wp_json_encode( $payload ) );

		// Create the signature input.
		$signature_input = $header_encoded . '.' . $payload_encoded;

		// Sign with private key using ES256 (ECDSA with SHA-256).
		$private_key = openssl_pkey_get_private( $private_key_pem );
		if ( ! $private_key ) {
			error_log( '[Custom PWA] Failed to load VAPID private key for JWT signing' );
			return false;
		}

		$signature = '';
		$success   = openssl_sign( $signature_input, $signature, $private_key, OPENSSL_ALGO_SHA256 );
		openssl_free_key( $private_key );

		if ( ! $success ) {
			error_log( '[Custom PWA] Failed to sign JWT with VAPID private key' );
			return false;
		}

		// Convert DER signature to raw signature (required for JWT ES256).
		$signature_raw = $this->der_to_raw_signature( $signature );
		if ( ! $signature_raw ) {
			error_log( '[Custom PWA] Failed to convert DER signature to raw format' );
			return false;
		}

		// Encode signature.
		$signature_encoded = $this->base64url_encode( $signature_raw );

		// Return the complete JWT.
		return $signature_input . '.' . $signature_encoded;
	}

	/**
	 * Convert DER-encoded ECDSA signature to raw format (R + S).
	 *
	 * @param string $der_signature DER-encoded signature.
	 * @return string|false Raw signature (64 bytes for P-256) or false on failure.
	 */
	private function der_to_raw_signature( $der_signature ) {
		$offset = 0;
		$length = strlen( $der_signature );

		// Check sequence marker (0x30).
		if ( $offset >= $length || ord( $der_signature[ $offset ] ) !== 0x30 ) {
			return false;
		}
		$offset++;

		// Skip sequence length.
		$sequence_length = ord( $der_signature[ $offset ] );
		$offset++;

		// Read R integer.
		if ( $offset >= $length || ord( $der_signature[ $offset ] ) !== 0x02 ) {
			return false;
		}
		$offset++;

		$r_length = ord( $der_signature[ $offset ] );
		$offset++;

		$r = substr( $der_signature, $offset, $r_length );
		$offset += $r_length;

		// Remove leading zero byte if present.
		if ( strlen( $r ) === 33 && ord( $r[0] ) === 0x00 ) {
			$r = substr( $r, 1 );
		}

		// Pad to 32 bytes.
		$r = str_pad( $r, 32, "\x00", STR_PAD_LEFT );

		// Read S integer.
		if ( $offset >= $length || ord( $der_signature[ $offset ] ) !== 0x02 ) {
			return false;
		}
		$offset++;

		$s_length = ord( $der_signature[ $offset ] );
		$offset++;

		$s = substr( $der_signature, $offset, $s_length );

		// Remove leading zero byte if present.
		if ( strlen( $s ) === 33 && ord( $s[0] ) === 0x00 ) {
			$s = substr( $s, 1 );
		}

		// Pad to 32 bytes.
		$s = str_pad( $s, 32, "\x00", STR_PAD_LEFT );

		// Return R + S (64 bytes total).
		return $r . $s;
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
	 * Base64url decode a string.
	 *
	 * @param string $data Base64url encoded string.
	 * @return string Decoded data.
	 */
	private function base64url_decode( $data ) {
		return base64_decode( strtr( $data, '-_', '+/' ) );
	}

	/**
	 * Get rule for a specific post type.
	 *
	 * @param string $post_type Post type name.
	 * @return array|null Rule array or null if not found.
	 */
	public function get_rule( $post_type ) {
		$rules = get_option( $this->option_name, array() );
		return isset( $rules[ $post_type ] ) ? $rules[ $post_type ] : null;
	}

	/**
	 * Check if push is enabled for a post type.
	 *
	 * @param string $post_type Post type name.
	 * @return bool
	 */
	public function is_enabled_for_post_type( $post_type ) {
		$rule = $this->get_rule( $post_type );
		return $rule && ! empty( $rule['enabled'] );
	}
}
