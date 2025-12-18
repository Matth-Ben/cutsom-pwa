<?php
/**
 * Push Settings Class
 * 
 * Handles Web Push notification configuration with scenario-based system:
 * - Two-column layout (sidebar + main panel)
 * - Per-post-type configuration sections
 * - Multiple notification scenarios per role
 * - Extensible scenario definitions
 * - Template placeholders
 * - Test notification tool
 *
 * @package Custom_PWA
 * @since 1.0.0
 * @updated 1.1.0 - Added scenario-based system
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
		// Get enabled post types from config.
		$config = get_option( 'custom_pwa_config', array() );
		$enabled_post_types = isset( $config['enabled_post_types'] ) ? $config['enabled_post_types'] : array();

		if ( empty( $enabled_post_types ) ) {
			echo '<div style="padding:40px; text-align:center; color:var(--cp-muted);">';
			echo '<p style="font-size:15px;">' . esc_html__( 'No post types are enabled for Web Push.', 'custom-pwa' ) . '</p>';
			echo '<p><a href="' . esc_url( admin_url( 'admin.php?page=custom-pwa-config' ) ) . '" class="button cp-btn">' . esc_html__( 'Configure Post Types', 'custom-pwa' ) . '</a></p>';
			echo '</div>';
			return;
		}

		?>
		<form method="post" action="options.php" id="custom-pwa-push-form">
			<?php settings_fields( 'custom_pwa_push_group' ); ?>
			
			<!-- Render ALL enabled post types (they will be shown/hidden by JavaScript) -->
			<div class="custom-pwa-push-content">
				<?php 
				foreach ( $enabled_post_types as $post_type ) {
					try {
						$this->render_post_type_config( $post_type );
					} catch ( Exception $e ) {
						echo '<div class="notice notice-error"><p>';
						echo sprintf( 
							/* translators: %1$s: post type, %2$s: error message */
							esc_html__( 'Error rendering %1$s: %2$s', 'custom-pwa' ),
							esc_html( $post_type ),
							esc_html( $e->getMessage() )
						);
						echo '</p></div>';
					}
				}
				?>
			</div>
		</form>

		<?php 
		$this->render_test_tool(); 
		$this->render_accordion_script();
		?>
		<?php
	}

	/**
	 * Render JavaScript for accordion functionality.
	 */
	private function render_accordion_script() {
		?>
		<script type="text/javascript">
		jQuery(document).ready(function($) {
			// Handle accordion toggle
			$('.custom-pwa-scenario-header').on('click', function() {
				var panel = $(this).closest('.custom-pwa-scenario-panel');
				var body = panel.find('.custom-pwa-scenario-body');
				
				// Toggle expanded class
				panel.toggleClass('expanded');
				
				// Slide toggle the body
				body.slideToggle(250);
			});
			
			// Update status badge when enable checkbox is toggled
			$('.custom-pwa-scenario-enable').on('change', function() {
				var panel = $(this).closest('.custom-pwa-scenario-panel');
				var statusBadge = panel.find('.custom-pwa-scenario-status');
				
				if ($(this).is(':checked')) {
					statusBadge.removeClass('disabled').addClass('enabled');
					statusBadge.text('<?php echo esc_js( __( 'Enabled', 'custom-pwa' ) ); ?>');
				} else {
					statusBadge.removeClass('enabled').addClass('disabled');
					statusBadge.text('<?php echo esc_js( __( 'Disabled', 'custom-pwa' ) ); ?>');
				}
			});
		});
		</script>
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
	 * Render configuration for a specific post type.
	 * 
	 * Shows:
	 * - General config section (enable/disable, thresholds, etc.)
	 * - List of scenarios based on assigned role
	 * - Each scenario in an accordion panel with fields
	 *
	 * @param string $post_type Post type name.
	 */
	private function render_post_type_config( $post_type ) {
		require_once plugin_dir_path( __FILE__ ) . 'class-push-rules.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-push-scenarios.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-custom-scenarios.php';

		// Get post type object.
		$post_type_object = get_post_type_object( $post_type );
		if ( ! $post_type_object ) {
			return;
		}

		// Get role for this post type.
		$role = Custom_PWA_Push_Rules::get_post_type_role( $post_type );
		$role_label = Custom_PWA_Push_Scenarios::get_roles()[ $role ] ?? __( 'Generic', 'custom-pwa' );

		// Get rules for this post type.
		$rules = Custom_PWA_Push_Rules::get_post_type_rules( $post_type );
		$config = $rules['config'];
		$scenarios = $rules['scenarios'];

		// Get COMBINED scenario definitions (built-in + custom) for this role and post type.
		$scenario_definitions = Custom_PWA_Push_Scenarios::get_combined_scenarios_for_post_type( $post_type, $role );

		?>
		<div class="custom-pwa-cpt-card custom-pwa-push-main" data-cpt="<?php echo esc_attr( $post_type ); ?>">
			<!-- Header -->
			<div style="margin-bottom:24px;">
				<h2 style="margin:0 0 8px 0; font-size:20px; color:#0f172a;">
					<?php echo esc_html( $post_type_object->labels->name ); ?>
					<span class="custom-pwa-badge" style="margin-left:8px; background:#f1f5f9; color:#475569; padding:4px 10px; border-radius:6px; font-size:13px; font-weight:500;">
						<?php echo esc_html( $post_type ); ?>
					</span>
					<span class="custom-pwa-badge" style="margin-left:8px; background:var(--cp-accent-light); color:var(--cp-accent); padding:4px 10px; border-radius:6px; font-size:13px; font-weight:500;">
						<?php echo esc_html( $role_label ); ?>
					</span>
				</h2>
				<p style="margin:0; color:#64748b; font-size:14px;">
					<?php esc_html_e( 'Configure push notification scenarios and templates for this post type.', 'custom-pwa' ); ?>
				</p>
			</div>

			<!-- General Config Section -->
			<div class="custom-pwa-config-section" style="margin-bottom:32px;">
				<h3 style="margin:0 0 16px 0; font-size:16px; color:#1e293b; padding-bottom:12px; border-bottom:2px solid var(--cp-accent-light);">
					<?php esc_html_e( 'General Configuration', 'custom-pwa' ); ?>
				</h3>
				
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Enable Notifications', 'custom-pwa' ); ?></th>
						<td>
							<label>
								<input type="checkbox" 
									name="<?php echo esc_attr( $this->option_name ); ?>[<?php echo esc_attr( $post_type ); ?>][config][enabled]" 
									value="1" 
									<?php checked( ! empty( $config['enabled'] ) ); ?> 
								/>
								<?php esc_html_e( 'Enable push notifications for this post type', 'custom-pwa' ); ?>
							</label>
							<p class="description">
								<?php esc_html_e( 'Master switch: when disabled, no notifications will be sent regardless of scenario settings.', 'custom-pwa' ); ?>
							</p>
						</td>
					</tr>
				</table>
			</div>

			<!-- Scenarios Section -->
			<div class="custom-pwa-scenarios-section">
				<h3 style="margin:0 0 16px 0; font-size:16px; color:#1e293b; padding-bottom:12px; border-bottom:2px solid var(--cp-accent-light);">
					<?php esc_html_e( 'Notification Scenarios', 'custom-pwa' ); ?>
				</h3>
				<p style="margin:0 0 16px 0; color:#64748b; font-size:14px;">
					<?php esc_html_e( 'Configure different notification scenarios for various post events and triggers.', 'custom-pwa' ); ?>
				</p>

				<?php if ( empty( $scenario_definitions ) ) : ?>
					<p style="color:#dc2626;">
						<?php 
						echo esc_html( 
							sprintf( 
								/* translators: %s: role label */
								__( 'No scenarios found for role "%s". Please check your configuration.', 'custom-pwa' ), 
								$role_label 
							) 
						); 
						?>
					</p>
				<?php else : ?>
					<div class="custom-pwa-scenarios-accordion">
						<?php 
						foreach ( $scenario_definitions as $scenario_key => $scenario_def ) {
							$this->render_scenario_panel( $post_type, $scenario_key, $scenario_def, $scenarios[ $scenario_key ] ?? array() );
						}
						?>
					</div>
				<?php endif; ?>
			</div>

			<!-- Submit Button -->
			<div style="padding-top:24px; border-top:1px solid var(--cp-border); margin-top:32px;">
				<?php submit_button( __( 'Save Changes', 'custom-pwa' ), 'cp-btn primary', 'submit', false ); ?>
				<span class="spinner" style="float:none; margin:0 0 0 12px;"></span>
			</div>
		</div>
		<?php
	}

	/**
	 * Render a single scenario panel (accordion item).
	 *
	 * @param string $post_type    Post type name.
	 * @param string $scenario_key Scenario key.
	 * @param array  $scenario_def Scenario definition from configuration.
	 * @param array  $saved_data   Saved scenario data (or defaults).
	 */
	private function render_scenario_panel( $post_type, $scenario_key, $scenario_def, $saved_data ) {
		$enabled = ! empty( $saved_data['enabled'] );
		$title_template = isset( $saved_data['title_template'] ) ? $saved_data['title_template'] : $scenario_def['default_title'];
		$body_template = isset( $saved_data['body_template'] ) ? $saved_data['body_template'] : $scenario_def['default_body'];
		$url_template = isset( $saved_data['url_template'] ) ? $saved_data['url_template'] : $scenario_def['default_url'];
		
		// Check if this is a custom scenario
		$is_custom = isset( $scenario_def['is_custom'] ) && $scenario_def['is_custom'];

		$panel_id = 'scenario-' . $post_type . '-' . $scenario_key;
		?>
		<div class="custom-pwa-scenario-panel" data-scenario="<?php echo esc_attr( $scenario_key ); ?>">
			<div class="custom-pwa-scenario-header" data-toggle="<?php echo esc_attr( $panel_id ); ?>">
				<div class="custom-pwa-scenario-title">
					<span class="dashicons dashicons-arrow-right custom-pwa-scenario-icon"></span>
					<strong><?php echo esc_html( $scenario_def['label'] ); ?></strong>
					<?php if ( $is_custom ) : ?>
						<span class="custom-pwa-badge" style="background:#ddd6fe; color:var(--cp-accent); margin-left:8px;">
							<?php esc_html_e( 'Custom', 'custom-pwa' ); ?>
						</span>
					<?php endif; ?>
					<span class="custom-pwa-scenario-status <?php echo $enabled ? 'enabled' : 'disabled'; ?>">
						<?php echo $enabled ? esc_html__( 'Enabled', 'custom-pwa' ) : esc_html__( 'Disabled', 'custom-pwa' ); ?>
					</span>
				</div>
				<p class="custom-pwa-scenario-description">
					<?php echo esc_html( $scenario_def['description'] ); ?>
				</p>
			</div>

			<div class="custom-pwa-scenario-body" id="<?php echo esc_attr( $panel_id ); ?>" style="display:none;">
				<table class="form-table" role="presentation">
					<!-- Enable Checkbox -->
					<tr>
						<th scope="row"><?php esc_html_e( 'Enable Scenario', 'custom-pwa' ); ?></th>
						<td>
							<label>
								<input type="checkbox" 
									name="<?php echo esc_attr( $this->option_name ); ?>[<?php echo esc_attr( $post_type ); ?>][scenarios][<?php echo esc_attr( $scenario_key ); ?>][enabled]" 
									value="1" 
									<?php checked( $enabled ); ?>
									class="custom-pwa-scenario-enable" 
								/>
								<?php 
								echo esc_html( 
									sprintf( 
										/* translators: %s: scenario label */
										__( 'Enable "%s" notifications', 'custom-pwa' ), 
										$scenario_def['label'] 
									) 
								); 
								?>
							</label>
						</td>
					</tr>

					<!-- Title Template -->
					<tr>
						<th scope="row">
							<label for="<?php echo esc_attr( $panel_id ); ?>-title">
								<?php esc_html_e( 'Title Template', 'custom-pwa' ); ?>
							</label>
						</th>
						<td>
							<input type="text" 
								id="<?php echo esc_attr( $panel_id ); ?>-title" 
								name="<?php echo esc_attr( $this->option_name ); ?>[<?php echo esc_attr( $post_type ); ?>][scenarios][<?php echo esc_attr( $scenario_key ); ?>][title_template]" 
								value="<?php echo esc_attr( $title_template ); ?>" 
								class="large-text" 
							/>
							<p class="description">
								<?php $this->render_placeholder_hints(); ?>
							</p>
						</td>
					</tr>

					<!-- Body Template -->
					<tr>
						<th scope="row">
							<label for="<?php echo esc_attr( $panel_id ); ?>-body">
								<?php esc_html_e( 'Body Template', 'custom-pwa' ); ?>
							</label>
						</th>
						<td>
							<textarea 
								id="<?php echo esc_attr( $panel_id ); ?>-body" 
								name="<?php echo esc_attr( $this->option_name ); ?>[<?php echo esc_attr( $post_type ); ?>][scenarios][<?php echo esc_attr( $scenario_key ); ?>][body_template]" 
								rows="3" 
								class="large-text"
							><?php echo esc_textarea( $body_template ); ?></textarea>
							<p class="description">
								<?php $this->render_placeholder_hints(); ?>
							</p>
						</td>
					</tr>

					<!-- URL Template -->
					<tr>
						<th scope="row">
							<label for="<?php echo esc_attr( $panel_id ); ?>-url">
								<?php esc_html_e( 'URL Template', 'custom-pwa' ); ?>
							</label>
						</th>
						<td>
							<input type="text" 
								id="<?php echo esc_attr( $panel_id ); ?>-url" 
								name="<?php echo esc_attr( $this->option_name ); ?>[<?php echo esc_attr( $post_type ); ?>][scenarios][<?php echo esc_attr( $scenario_key ); ?>][url_template]" 
								value="<?php echo esc_attr( $url_template ); ?>" 
								class="large-text" 
							/>
							<p class="description">
								<?php esc_html_e( 'Available: {permalink}', 'custom-pwa' ); ?>
							</p>
						</td>
					</tr>

					<?php
					// Render extra fields defined in scenario configuration.
					if ( ! empty( $scenario_def['fields'] ) ) {
						foreach ( $scenario_def['fields'] as $field_key => $field_config ) {
							$this->render_scenario_field( $post_type, $scenario_key, $field_key, $field_config, $saved_data );
						}
					}
					?>
				</table>
			</div>
		</div>
		<?php
	}

	/**
	 * Render an extra scenario field (threshold, meta_key, etc.).
	 *
	 * @param string $post_type    Post type name.
	 * @param string $scenario_key Scenario key.
	 * @param string $field_key    Field key.
	 * @param array  $field_config Field configuration.
	 * @param array  $saved_data   Saved scenario data.
	 */
	private function render_scenario_field( $post_type, $scenario_key, $field_key, $field_config, $saved_data ) {
		$field_value = isset( $saved_data[ $field_key ] ) ? $saved_data[ $field_key ] : ( $field_config['default'] ?? '' );
		$field_id = 'scenario-' . $post_type . '-' . $scenario_key . '-' . $field_key;
		$field_name = $this->option_name . '[' . $post_type . '][scenarios][' . $scenario_key . '][' . $field_key . ']';

		?>
		<tr>
			<th scope="row">
				<label for="<?php echo esc_attr( $field_id ); ?>">
					<?php echo esc_html( $field_config['label'] ); ?>
				</label>
			</th>
			<td>
				<?php
				switch ( $field_config['type'] ) {
					case 'number':
						?>
						<input type="number" 
							id="<?php echo esc_attr( $field_id ); ?>" 
							name="<?php echo esc_attr( $field_name ); ?>" 
							value="<?php echo esc_attr( $field_value ); ?>" 
							class="small-text" 
							min="0"
						/>
						<?php
						break;

					case 'text':
					default:
						?>
						<input type="text" 
							id="<?php echo esc_attr( $field_id ); ?>" 
							name="<?php echo esc_attr( $field_name ); ?>" 
							value="<?php echo esc_attr( $field_value ); ?>" 
							class="regular-text" 
						/>
						<?php
						break;
				}

				if ( ! empty( $field_config['description'] ) ) {
					echo '<p class="description">' . esc_html( $field_config['description'] ) . '</p>';
				}
				?>
			</td>
		</tr>
		<?php
	}

	/**
	 * Render placeholder hints for templates.
	 */
	private function render_placeholder_hints() {
		$placeholders = Custom_PWA_Push_Scenarios::get_placeholders();
		$common = $placeholders['common']['placeholders'] ?? array();
		
		$hint_text = __( 'Available: ', 'custom-pwa' );
		$hint_text .= implode( ', ', array_keys( $common ) );
		
		echo esc_html( $hint_text );
	}

	/**
	 * Render test notification tool.
	 */
	private function render_test_tool() {
		?>
		<div class="custom-pwa-cpt-card" id="test-notification-card" style="display:none;">
			<h3 style="margin:0 0 16px 0; font-size:18px; color:#0f172a; padding-bottom:12px; border-bottom:2px solid var(--cp-accent-light);">
				<?php esc_html_e( 'Test Notification', 'custom-pwa' ); ?>
			</h3>
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
				<button type="button" id="custom_pwa_send_test" class="button cp-btn">
					<?php esc_html_e( 'Send Test Notification', 'custom-pwa' ); ?>
				</button>
				<span id="custom_pwa_test_result" style="margin-left: 10px;"></span>
			</p>
		</div>

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
		require_once plugin_dir_path( __FILE__ ) . 'class-push-rules.php';
		return Custom_PWA_Push_Rules::sanitize_rules( $input );
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
