<?php
/**
 * Custom PWA Scenario Manager UI
 *
 * Handles the admin interface for managing custom push notification scenarios.
 *
 * @package Custom_PWA
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Scenario Manager UI Class
 */
class Custom_PWA_Scenario_Manager_UI {

	/**
	 * Render the scenario manager interface.
	 */
	public static function render() {
		require_once plugin_dir_path( __FILE__ ) . 'class-custom-scenarios.php';
		
		// Handle actions (add, edit, delete)
		$action = isset( $_GET['action'] ) ? sanitize_key( $_GET['action'] ) : '';
		$scenario_id = isset( $_GET['scenario_id'] ) ? sanitize_key( $_GET['scenario_id'] ) : '';
		
		// Handle delete action
		if ( $action === 'delete' && $scenario_id && check_admin_referer( 'delete_custom_scenario' ) ) {
			$deleted = Custom_PWA_Custom_Scenarios::delete( $scenario_id );
			if ( $deleted ) {
				echo '<div class="notice notice-success is-dismissible"><p>' 
					. esc_html__( 'Scenario deleted successfully.', 'custom-pwa' ) 
					. '</p></div>';
			} else {
				echo '<div class="notice notice-error is-dismissible"><p>' 
					. esc_html__( 'Failed to delete scenario.', 'custom-pwa' ) 
					. '</p></div>';
			}
			$action = ''; // Reset action to show list
		}
		
		if ( $action === 'edit' && $scenario_id ) {
			self::render_scenario_form( 'edit', $scenario_id );
		} elseif ( $action === 'add' ) {
			self::render_scenario_form( 'add' );
		} else {
			self::render_scenario_list();
		}
	}

	/**
	 * Render list of all scenarios (built-in and custom).
	 */
	private static function render_scenario_list() {
		require_once plugin_dir_path( __FILE__ ) . 'class-custom-scenarios.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-push-scenarios.php';
		
		// Get all built-in scenarios
		$built_in_scenarios = Custom_PWA_Push_Scenarios::get_scenarios();
		
		// Get custom scenarios
		$custom_scenarios = Custom_PWA_Custom_Scenarios::get_all();
		
		?>
		<div class="custom-pwa-scenario-manager">
			<div style="margin-bottom:24px; display:flex; justify-content:space-between; align-items:center;">
				<div>
					<h2 style="margin:0; font-size:18px;"><?php esc_html_e( 'Scenario Manager', 'custom-pwa' ); ?></h2>
					<p style="margin:8px 0 0 0; color:var(--cp-muted);">
						<?php esc_html_e( 'Manage notification scenarios. Built-in scenarios are pre-configured by role. You can create custom scenarios for more flexibility.', 'custom-pwa' ); ?>
					</p>
				</div>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=custom-pwa-push&tab=scenarios&action=add' ) ); ?>" 
				   class="button cp-btn primary">
					<span class="dashicons dashicons-plus-alt" style="margin-top:3px;"></span>
					<?php esc_html_e( 'Add Custom Scenario', 'custom-pwa' ); ?>
				</a>
			</div>

			<!-- Built-in Scenarios -->
			<div class="custom-pwa-card" style="margin-bottom:20px;">
				<h3 style="margin:0 0 16px 0; font-size:16px; color:#0f172a; padding-bottom:12px; border-bottom:2px solid var(--cp-accent-light);">
					<?php esc_html_e( 'Built-in Scenarios (by Role)', 'custom-pwa' ); ?>
				</h3>
				<p style="color:var(--cp-muted); font-size:13px; margin-bottom:16px;">
					<?php esc_html_e( 'These scenarios are pre-configured and available based on the role assigned to each post type. You can customize templates per post type but cannot delete these scenarios.', 'custom-pwa' ); ?>
				</p>
				
				<?php foreach ( $built_in_scenarios as $role_key => $role_data ) : ?>
					<div style="margin-bottom:20px;">
						<h4 style="margin:0 0 12px 0; font-size:15px; color:#374151;">
							<span class="custom-pwa-badge" style="background:var(--cp-accent-light); color:var(--cp-accent);">
								<?php echo esc_html( $role_data['label'] ); ?>
							</span>
						</h4>
						<table class="widefat" style="margin-top:8px;">
							<thead>
								<tr>
									<th style="padding:8px;"><?php esc_html_e( 'Scenario', 'custom-pwa' ); ?></th>
									<th style="padding:8px;"><?php esc_html_e( 'Description', 'custom-pwa' ); ?></th>
									<th style="padding:8px; width:100px;"><?php esc_html_e( 'Type', 'custom-pwa' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $role_data['scenarios'] as $scenario_key => $scenario ) : ?>
									<tr>
										<td style="padding:8px;">
											<strong><?php echo esc_html( $scenario['label'] ); ?></strong>
											<br><small style="color:var(--cp-muted);"><?php echo esc_html( $scenario_key ); ?></small>
										</td>
										<td style="padding:8px; color:var(--cp-muted); font-size:13px;">
											<?php echo esc_html( $scenario['description'] ); ?>
										</td>
										<td style="padding:8px;">
											<span class="custom-pwa-badge" style="background:#f1f5f9; color:#64748b;">
												<?php esc_html_e( 'Built-in', 'custom-pwa' ); ?>
											</span>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				<?php endforeach; ?>
			</div>

			<!-- Custom Scenarios -->
			<div class="custom-pwa-card">
				<h3 style="margin:0 0 16px 0; font-size:16px; color:#0f172a; padding-bottom:12px; border-bottom:2px solid var(--cp-accent-light);">
					<?php esc_html_e( 'Custom Scenarios', 'custom-pwa' ); ?>
					<span class="custom-pwa-badge" style="margin-left:8px;">
						<?php echo esc_html( count( $custom_scenarios ) ); ?>
					</span>
				</h3>
				
				<?php if ( empty( $custom_scenarios ) ) : ?>
					<div style="padding:40px; text-align:center; color:var(--cp-muted);">
						<span class="dashicons dashicons-info" style="font-size:48px; opacity:0.3; display:block; margin-bottom:16px;"></span>
						<p style="font-size:15px; margin:0;"><?php esc_html_e( 'No custom scenarios yet.', 'custom-pwa' ); ?></p>
						<p style="margin:8px 0 16px 0;"><?php esc_html_e( 'Create custom scenarios for more flexibility beyond role-based defaults.', 'custom-pwa' ); ?></p>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=custom-pwa-push&tab=scenarios&action=add' ) ); ?>" 
						   class="button cp-btn primary">
							<?php esc_html_e( 'Create Your First Scenario', 'custom-pwa' ); ?>
						</a>
					</div>
				<?php else : ?>
					<table class="widefat">
						<thead>
							<tr>
								<th style="padding:8px;"><?php esc_html_e( 'Scenario', 'custom-pwa' ); ?></th>
								<th style="padding:8px;"><?php esc_html_e( 'Scope', 'custom-pwa' ); ?></th>
								<th style="padding:8px;"><?php esc_html_e( 'Trigger', 'custom-pwa' ); ?></th>
								<th style="padding:8px; width:150px;"><?php esc_html_e( 'Actions', 'custom-pwa' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $custom_scenarios as $scenario_id => $scenario ) : ?>
								<tr>
									<td style="padding:8px;">
										<strong><?php echo esc_html( $scenario['label'] ); ?></strong>
										<br><small style="color:var(--cp-muted);"><?php echo esc_html( $scenario['description'] ); ?></small>
									</td>
									<td style="padding:8px;">
										<?php if ( $scenario['scope'] === 'global' ) : ?>
											<span class="custom-pwa-badge" style="background:#d1fae5; color:#065f46;">
												<?php esc_html_e( 'Global', 'custom-pwa' ); ?>
											</span>
										<?php else : ?>
											<span class="custom-pwa-badge" style="background:#fef3c7; color:#92400e;">
												<?php 
												echo esc_html( 
													sprintf( 
														/* translators: %d: number of post types */
														_n( '%d post type', '%d post types', count( $scenario['post_types'] ), 'custom-pwa' ), 
														count( $scenario['post_types'] ) 
													) 
												); 
												?>
											</span>
										<?php endif; ?>
									</td>
									<td style="padding:8px; font-size:13px; color:var(--cp-muted);">
										<?php 
										$trigger_types = Custom_PWA_Custom_Scenarios::get_trigger_types();
										echo esc_html( $trigger_types[ $scenario['trigger']['type'] ] ?? $scenario['trigger']['type'] );
										?>
									</td>
									<td style="padding:8px;">
										<a href="<?php echo esc_url( admin_url( 'admin.php?page=custom-pwa-push&tab=scenarios&action=edit&scenario_id=' . $scenario_id ) ); ?>" 
										   class="button button-small">
											<?php esc_html_e( 'Edit', 'custom-pwa' ); ?>
										</a>
										<a href="#" class="button button-small custom-pwa-delete-scenario" 
										   data-scenario-id="<?php echo esc_attr( $scenario_id ); ?>"
										   data-scenario-name="<?php echo esc_attr( $scenario['label'] ); ?>"
										   style="color:#dc2626;">
											<?php esc_html_e( 'Delete', 'custom-pwa' ); ?>
										</a>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
			</div>
		</div>

		<script type="text/javascript">
		jQuery(document).ready(function($) {
			$('.custom-pwa-delete-scenario').on('click', function(e) {
				e.preventDefault();
				var scenarioId = $(this).data('scenario-id');
				var scenarioName = $(this).data('scenario-name');
				
				if (confirm('<?php echo esc_js( __( 'Are you sure you want to delete the scenario', 'custom-pwa' ) ); ?> "' + scenarioName + '"?\n\n<?php echo esc_js( __( 'This will remove the scenario from all post types.', 'custom-pwa' ) ); ?>')) {
					// Submit delete request
					window.location.href = '<?php echo esc_url( admin_url( 'admin.php?page=custom-pwa-push&tab=scenarios' ) ); ?>&action=delete&scenario_id=' + scenarioId + '&_wpnonce=<?php echo wp_create_nonce( 'delete_custom_scenario' ); ?>';
				}
			});
		});
		</script>
		<?php
	}

	/**
	 * Render the scenario form (add or edit).
	 *
	 * @param string $mode        'add' or 'edit'.
	 * @param string $scenario_id Scenario ID (for edit mode).
	 */
	private static function render_scenario_form( $mode, $scenario_id = '' ) {
		require_once plugin_dir_path( __FILE__ ) . 'class-custom-scenarios.php';
		
		$scenario = $mode === 'edit' && $scenario_id 
			? Custom_PWA_Custom_Scenarios::get( $scenario_id )
			: Custom_PWA_Custom_Scenarios::get_default_structure();
		
		if ( $mode === 'edit' && ! $scenario ) {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'Scenario not found.', 'custom-pwa' ) . '</p></div>';
			return;
		}
		
		$form_title = $mode === 'add' 
			? __( 'Add Custom Scenario', 'custom-pwa' ) 
			: __( 'Edit Custom Scenario', 'custom-pwa' );
		
		?>
		<div class="custom-pwa-scenario-form">
			<div style="margin-bottom:20px;">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=custom-pwa-push&tab=scenarios' ) ); ?>" 
				   class="button">
					<span class="dashicons dashicons-arrow-left-alt2" style="margin-top:3px;"></span>
					<?php esc_html_e( 'Back to Scenarios', 'custom-pwa' ); ?>
				</a>
			</div>

			<div class="custom-pwa-card">
				<h2 style="margin:0 0 24px 0; font-size:20px; color:#0f172a; padding-bottom:16px; border-bottom:2px solid var(--cp-accent-light);">
					<?php echo esc_html( $form_title ); ?>
				</h2>

				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="custom_pwa_save_scenario">
					<input type="hidden" name="mode" value="<?php echo esc_attr( $mode ); ?>">
					<?php if ( $mode === 'edit' ) : ?>
						<input type="hidden" name="scenario_id" value="<?php echo esc_attr( $scenario_id ); ?>">
					<?php endif; ?>
					<?php wp_nonce_field( 'custom_pwa_save_scenario', 'custom_pwa_scenario_nonce' ); ?>

					<table class="form-table" role="presentation">
						<!-- Scenario Label -->
						<tr>
							<th scope="row">
								<label for="scenario_label"><?php esc_html_e( 'Scenario Name', 'custom-pwa' ); ?> *</label>
							</th>
							<td>
								<input type="text" id="scenario_label" name="scenario[label]" 
									   value="<?php echo esc_attr( $scenario['label'] ); ?>" 
									   class="regular-text" required>
								<p class="description">
									<?php esc_html_e( 'A short, descriptive name for this scenario (e.g., "Flash Sale Alert", "Event Reminder").', 'custom-pwa' ); ?>
								</p>
							</td>
						</tr>

						<!-- Description -->
						<tr>
							<th scope="row">
								<label for="scenario_description"><?php esc_html_e( 'Description', 'custom-pwa' ); ?></label>
							</th>
							<td>
								<textarea id="scenario_description" name="scenario[description]" 
										  rows="3" class="large-text"><?php echo esc_textarea( $scenario['description'] ); ?></textarea>
								<p class="description">
									<?php esc_html_e( 'Explain what triggers this notification and when it should be sent.', 'custom-pwa' ); ?>
								</p>
							</td>
						</tr>

						<!-- Scope -->
						<tr>
							<th scope="row"><?php esc_html_e( 'Scope', 'custom-pwa' ); ?> *</th>
							<td>
								<fieldset>
									<label style="display:block; margin-bottom:12px;">
										<input type="radio" name="scenario[scope]" value="global" 
											   <?php checked( $scenario['scope'], 'global' ); ?> 
											   class="scenario-scope-radio">
										<strong><?php esc_html_e( 'Global', 'custom-pwa' ); ?></strong>
										<p style="margin:4px 0 0 24px; color:var(--cp-muted); font-size:13px;">
											<?php esc_html_e( 'Available for all enabled post types.', 'custom-pwa' ); ?>
										</p>
									</label>
									<label style="display:block;">
										<input type="radio" name="scenario[scope]" value="post_type" 
											   <?php checked( $scenario['scope'], 'post_type' ); ?> 
											   class="scenario-scope-radio">
										<strong><?php esc_html_e( 'Post-Type Specific', 'custom-pwa' ); ?></strong>
										<p style="margin:4px 0 0 24px; color:var(--cp-muted); font-size:13px;">
											<?php esc_html_e( 'Available only for selected post types.', 'custom-pwa' ); ?>
										</p>
									</label>
								</fieldset>
								
								<!-- Post Type Selection (shown when post_type scope is selected) -->
								<div id="post-type-selection" style="margin-top:16px; padding:16px; background:#f9fafb; border-radius:8px; <?php echo $scenario['scope'] === 'global' ? 'display:none;' : ''; ?>">
									<label style="font-weight:600; margin-bottom:8px; display:block;">
										<?php esc_html_e( 'Select Post Types:', 'custom-pwa' ); ?>
									</label>
									<?php
									$config = get_option( 'custom_pwa_config', array() );
									$enabled_post_types = isset( $config['enabled_post_types'] ) ? $config['enabled_post_types'] : array();
									$post_types = get_post_types( array( 'public' => true ), 'objects' );
									
									foreach ( $enabled_post_types as $pt ) {
										if ( isset( $post_types[ $pt ] ) ) {
											$checked = in_array( $pt, $scenario['post_types'], true ) ? 'checked' : '';
											?>
											<label style="display:block; margin-bottom:8px;">
												<input type="checkbox" name="scenario[post_types][]" 
													   value="<?php echo esc_attr( $pt ); ?>" 
													   <?php echo $checked; ?>>
												<?php echo esc_html( $post_types[ $pt ]->labels->name ); ?>
												<small style="color:var(--cp-muted);">(<?php echo esc_html( $pt ); ?>)</small>
											</label>
											<?php
										}
									}
									?>
								</div>
							</td>
						</tr>

						<!-- Trigger Type -->
						<tr>
							<th scope="row">
								<label for="trigger_type"><?php esc_html_e( 'Trigger Type', 'custom-pwa' ); ?> *</label>
							</th>
							<td>
								<select id="trigger_type" name="scenario[trigger][type]" class="regular-text">
									<?php
									$trigger_types = Custom_PWA_Custom_Scenarios::get_trigger_types();
									foreach ( $trigger_types as $type_key => $type_label ) {
										$selected = $scenario['trigger']['type'] === $type_key ? 'selected' : '';
										echo '<option value="' . esc_attr( $type_key ) . '" ' . $selected . '>' . esc_html( $type_label ) . '</option>';
									}
									?>
								</select>
								<p class="description">
									<?php esc_html_e( 'When should this notification be triggered?', 'custom-pwa' ); ?>
								</p>
							</td>
						</tr>

						<!-- Meta Key (for meta-based triggers) -->
						<tr id="meta-key-row" style="<?php echo in_array( $scenario['trigger']['type'], array( 'on_meta_change', 'on_status_change' ), true ) ? '' : 'display:none;'; ?>">
							<th scope="row">
								<label for="trigger_meta_key"><?php esc_html_e( 'Meta Key / Field Name', 'custom-pwa' ); ?></label>
							</th>
							<td>
								<input type="text" id="trigger_meta_key" name="scenario[trigger][meta_key]" 
									   value="<?php echo esc_attr( $scenario['trigger']['meta_key'] ); ?>" 
									   class="regular-text">
								<p class="description">
									<?php esc_html_e( 'The custom field or meta key to monitor (e.g., "event_date", "_stock", "concert_status").', 'custom-pwa' ); ?>
								</p>
							</td>
						</tr>

						<!-- Old/New Value (for condition-based triggers) -->
						<tr id="condition-row" style="<?php echo in_array( $scenario['trigger']['type'], array( 'on_meta_change', 'on_status_change' ), true ) ? '' : 'display:none;'; ?>">
							<th scope="row"><?php esc_html_e( 'Condition (Optional)', 'custom-pwa' ); ?></th>
							<td>
								<div style="display:flex; gap:12px; align-items:center;">
									<div style="flex:1;">
										<label for="trigger_old_value" style="font-size:13px; color:var(--cp-muted); display:block; margin-bottom:4px;">
											<?php esc_html_e( 'Old Value', 'custom-pwa' ); ?>
										</label>
										<input type="text" id="trigger_old_value" name="scenario[trigger][old_value]" 
											   value="<?php echo esc_attr( $scenario['trigger']['old_value'] ); ?>" 
											   class="regular-text" 
											   placeholder="<?php esc_attr_e( 'e.g., draft', 'custom-pwa' ); ?>">
									</div>
									<div style="margin-top:20px;">→</div>
									<div style="flex:1;">
										<label for="trigger_new_value" style="font-size:13px; color:var(--cp-muted); display:block; margin-bottom:4px;">
											<?php esc_html_e( 'New Value', 'custom-pwa' ); ?>
										</label>
										<input type="text" id="trigger_new_value" name="scenario[trigger][new_value]" 
											   value="<?php echo esc_attr( $scenario['trigger']['new_value'] ); ?>" 
											   class="regular-text" 
											   placeholder="<?php esc_attr_e( 'e.g., publish', 'custom-pwa' ); ?>">
									</div>
								</div>
								<p class="description">
									<?php esc_html_e( 'Optionally specify when to trigger (e.g., status changes from "draft" to "publish").', 'custom-pwa' ); ?>
								</p>
							</td>
						</tr>

						<!-- Core Fields Used -->
						<tr>
							<th scope="row">
								<label><?php esc_html_e( 'Core Fields Used', 'custom-pwa' ); ?></label>
							</th>
							<td>
								<fieldset>
									<?php
									$core_fields = Custom_PWA_Custom_Scenarios::get_core_fields();
									foreach ( $core_fields as $field_key => $field_label ) {
										$checked = in_array( $field_key, $scenario['fields_used']['core_fields'], true ) ? 'checked' : '';
										?>
										<label style="display:inline-block; margin-right:16px; margin-bottom:8px;">
											<input type="checkbox" name="scenario[fields_used][core_fields][]" 
												   value="<?php echo esc_attr( $field_key ); ?>" 
												   <?php echo $checked; ?>>
											<?php echo esc_html( $field_label ); ?>
										</label>
										<?php
									}
									?>
								</fieldset>
								<p class="description">
									<?php esc_html_e( 'Select which post fields will be used in templates and conditions.', 'custom-pwa' ); ?>
								</p>
							</td>
						</tr>

						<!-- Meta Keys Used -->
						<tr>
							<th scope="row">
								<label for="meta_keys_used"><?php esc_html_e( 'Meta Keys Used', 'custom-pwa' ); ?></label>
							</th>
							<td>
								<textarea id="meta_keys_used" name="scenario[fields_used][meta_keys_text]" 
										  rows="3" class="large-text" 
										  placeholder="event_date&#10;_stock&#10;concert_status"><?php 
									echo esc_textarea( implode( "\n", $scenario['fields_used']['meta_keys'] ) ); 
								?></textarea>
								<p class="description">
									<?php esc_html_e( 'Enter custom field/meta key names (one per line) that will be available as placeholders.', 'custom-pwa' ); ?>
								</p>
							</td>
						</tr>

						<!-- Default Templates -->
						<tr>
							<th colspan="2">
								<h3 style="margin:16px 0 8px 0; font-size:15px; color:#374151; border-top:1px solid var(--cp-border); padding-top:16px;">
									<?php esc_html_e( 'Default Templates', 'custom-pwa' ); ?>
								</h3>
								<p style="margin:0; font-weight:normal; color:var(--cp-muted); font-size:13px;">
									<?php esc_html_e( 'These are the default templates. They can be overridden per post type.', 'custom-pwa' ); ?>
								</p>
							</th>
						</tr>

						<!-- Title Template -->
						<tr>
							<th scope="row">
								<label for="template_title"><?php esc_html_e( 'Title Template', 'custom-pwa' ); ?></label>
							</th>
							<td>
								<input type="text" id="template_title" name="scenario[templates][title_template]" 
									   value="<?php echo esc_attr( $scenario['templates']['title_template'] ); ?>" 
									   class="large-text">
								<p class="description">
									<?php esc_html_e( 'Use placeholders like {post_title}, {post_date}, or custom field names.', 'custom-pwa' ); ?>
								</p>
							</td>
						</tr>

						<!-- Body Template -->
						<tr>
							<th scope="row">
								<label for="template_body"><?php esc_html_e( 'Body Template', 'custom-pwa' ); ?></label>
							</th>
							<td>
								<textarea id="template_body" name="scenario[templates][body_template]" 
										  rows="3" class="large-text"><?php echo esc_textarea( $scenario['templates']['body_template'] ); ?></textarea>
								<p class="description">
									<?php esc_html_e( 'Use placeholders like {excerpt}, {post_content}, or custom field names.', 'custom-pwa' ); ?>
								</p>
							</td>
						</tr>

						<!-- URL Template -->
						<tr>
							<th scope="row">
								<label for="template_url"><?php esc_html_e( 'URL Template', 'custom-pwa' ); ?></label>
							</th>
							<td>
								<input type="text" id="template_url" name="scenario[templates][url_template]" 
									   value="<?php echo esc_attr( $scenario['templates']['url_template'] ); ?>" 
									   class="large-text">
								<p class="description">
									<?php esc_html_e( 'Use {permalink} for post URL or customize as needed.', 'custom-pwa' ); ?>
								</p>
							</td>
						</tr>
					</table>

					<p class="submit" style="padding-top:16px; border-top:1px solid var(--cp-border);">
						<?php submit_button( $mode === 'add' ? __( 'Create Scenario', 'custom-pwa' ) : __( 'Update Scenario', 'custom-pwa' ), 'cp-btn primary', 'submit', false ); ?>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=custom-pwa-push&tab=scenarios' ) ); ?>" 
						   class="button" style="margin-left:8px;">
							<?php esc_html_e( 'Cancel', 'custom-pwa' ); ?>
						</a>
					</p>
				</form>
			</div>
		</div>

		<script type="text/javascript">
		jQuery(document).ready(function($) {
			// Show/hide post type selection based on scope
			$('.scenario-scope-radio').on('change', function() {
				if ($('input[name="scenario[scope]"]:checked').val() === 'post_type') {
					$('#post-type-selection').slideDown(200);
				} else {
					$('#post-type-selection').slideUp(200);
				}
			});
			
			// Show/hide meta key and condition fields based on trigger type
			$('#trigger_type').on('change', function() {
				var triggerType = $(this).val();
				if (triggerType === 'on_meta_change' || triggerType === 'on_status_change') {
					$('#meta-key-row, #condition-row').slideDown(200);
				} else {
					$('#meta-key-row, #condition-row').slideUp(200);
				}
			});
		});
		</script>
		<?php
	}
}
