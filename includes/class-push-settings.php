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

		// TODO: Actually send push notifications.
		// For now, just log the attempt.
		if ( get_option( 'custom_pwa_config' )['debug_mode'] ?? false ) {
			error_log( sprintf(
				'[Custom PWA] Test push notification: title=%s, body=%s, url=%s, subscriptions=%d',
				$title,
				$body,
				$url,
				$count
			) );
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'message' => sprintf(
					/* translators: %d: number of subscriptions */
					__( 'Test notification prepared for %d subscriptions. (Real sending requires Web Push library integration.)', 'custom-pwa' ),
					$count
				),
			),
			200
		);
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
