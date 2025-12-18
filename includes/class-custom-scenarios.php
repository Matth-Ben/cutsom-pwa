<?php
/**
 * Custom Scenarios Management Class
 * 
 * Manages user-defined push notification scenarios that can be:
 * - GLOBAL: Available for all enabled post types
 * - POST-TYPE SPECIFIC: Available only for selected post types
 * 
 * User-defined scenarios are stored in the custom_pwa_custom_scenarios option
 * and merged with built-in scenarios from class-push-scenarios.php.
 * 
 * Data structure:
 * array(
 *     'scenario_id' => array(
 *         'id'          => 'unique_scenario_id',
 *         'label'       => 'Human-readable name',
 *         'description' => 'What this scenario does',
 *         'scope'       => 'global' | 'post_type',
 *         'post_types'  => array( 'post', 'product' ), // if scope = post_type
 *         'trigger'     => array(
 *             'type'      => 'on_publish' | 'on_update' | 'on_meta_change' | 'on_status_change',
 *             'meta_key'  => 'event_date', // for meta-based triggers
 *             'old_value' => 'draft', // optional condition
 *             'new_value' => 'publish', // optional condition
 *         ),
 *         'templates'   => array(
 *             'title_template' => 'Default title: {post_title}',
 *             'body_template'  => 'Default body: {excerpt}',
 *             'url_template'   => '{permalink}',
 *         ),
 *         'fields_used' => array(
 *             'core_fields' => array( 'post_title', 'post_status', 'post_date' ),
 *             'meta_keys'   => array( 'event_date', '_stock', 'concert_status' ),
 *         ),
 *     ),
 * )
 *
 * @package Custom_PWA
 * @since 1.1.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Custom_PWA_Custom_Scenarios class.
 * 
 * Provides CRUD operations for user-defined scenarios.
 */
class Custom_PWA_Custom_Scenarios {

	/**
	 * Option name for storing custom scenarios.
	 *
	 * @var string
	 */
	const OPTION_NAME = 'custom_pwa_custom_scenarios';

	/**
	 * Get all user-defined scenarios.
	 *
	 * @return array Array of scenario definitions indexed by scenario ID.
	 */
	public static function get_all() {
		$scenarios = get_option( self::OPTION_NAME, array() );
		
		/**
		 * Filter user-defined scenarios.
		 * 
		 * Allows modification of custom scenarios before they are used.
		 *
		 * @since 1.1.0
		 *
		 * @param array $scenarios Custom scenarios array.
		 */
		return apply_filters( 'custom_pwa_custom_scenarios', is_array( $scenarios ) ? $scenarios : array() );
	}

	/**
	 * Get a single scenario by ID.
	 *
	 * @param string $scenario_id Scenario ID.
	 * @return array|null Scenario definition or null if not found.
	 */
	public static function get( $scenario_id ) {
		$scenarios = self::get_all();
		return isset( $scenarios[ $scenario_id ] ) ? $scenarios[ $scenario_id ] : null;
	}

	/**
	 * Get all global scenarios (scope = global).
	 *
	 * @return array Array of global scenario definitions.
	 */
	public static function get_global_scenarios() {
		$all_scenarios = self::get_all();
		$global = array();

		foreach ( $all_scenarios as $id => $scenario ) {
			if ( isset( $scenario['scope'] ) && $scenario['scope'] === 'global' ) {
				$global[ $id ] = $scenario;
			}
		}

		return $global;
	}

	/**
	 * Get scenarios for a specific post type.
	 * 
	 * Returns scenarios where:
	 * - scope = global (available for all post types)
	 * - OR scope = post_type AND this post type is in the post_types array
	 *
	 * @param string $post_type Post type name.
	 * @return array Array of scenario definitions.
	 */
	public static function get_for_post_type( $post_type ) {
		$all_scenarios = self::get_all();
		$applicable = array();

		foreach ( $all_scenarios as $id => $scenario ) {
			// Include global scenarios.
			if ( isset( $scenario['scope'] ) && $scenario['scope'] === 'global' ) {
				$applicable[ $id ] = $scenario;
				continue;
			}

			// Include post-type-specific scenarios where this post type is included.
			if ( isset( $scenario['scope'] ) && $scenario['scope'] === 'post_type' ) {
				if ( isset( $scenario['post_types'] ) && in_array( $post_type, $scenario['post_types'], true ) ) {
					$applicable[ $id ] = $scenario;
				}
			}
		}

		return $applicable;
	}

	/**
	 * Create a new custom scenario.
	 *
	 * @param array $data Scenario data (without ID - will be auto-generated).
	 * @return string|WP_Error Scenario ID on success, WP_Error on failure.
	 */
	public static function create( $data ) {
		// Generate unique ID.
		$id = 'custom_' . sanitize_key( $data['label'] ?? 'scenario' ) . '_' . time();
		
		// Ensure ID is unique.
		$scenarios = self::get_all();
		$counter = 1;
		$original_id = $id;
		while ( isset( $scenarios[ $id ] ) ) {
			$id = $original_id . '_' . $counter;
			$counter++;
		}

		$data['id'] = $id;

		// Sanitize and validate.
		$sanitized = self::sanitize_scenario( $data );
		if ( is_wp_error( $sanitized ) ) {
			return $sanitized;
		}

		// Save.
		$scenarios[ $id ] = $sanitized;
		update_option( self::OPTION_NAME, $scenarios );

		return $id;
	}

	/**
	 * Update an existing custom scenario.
	 *
	 * @param string $scenario_id Scenario ID.
	 * @param array  $data        Updated scenario data.
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	public static function update( $scenario_id, $data ) {
		$scenarios = self::get_all();

		if ( ! isset( $scenarios[ $scenario_id ] ) ) {
			return new WP_Error( 'scenario_not_found', __( 'Scenario not found.', 'custom-pwa' ) );
		}

		// Keep the original ID.
		$data['id'] = $scenario_id;

		// Sanitize and validate.
		$sanitized = self::sanitize_scenario( $data );
		if ( is_wp_error( $sanitized ) ) {
			return $sanitized;
		}

		// Update.
		$scenarios[ $scenario_id ] = $sanitized;
		update_option( self::OPTION_NAME, $scenarios );

		return true;
	}

	/**
	 * Delete a custom scenario.
	 *
	 * @param string $scenario_id Scenario ID.
	 * @return bool True on success, false on failure.
	 */
	public static function delete( $scenario_id ) {
		$scenarios = self::get_all();

		if ( ! isset( $scenarios[ $scenario_id ] ) ) {
			return false;
		}

		unset( $scenarios[ $scenario_id ] );
		update_option( self::OPTION_NAME, $scenarios );

		// Also remove this scenario from any post type rules.
		self::remove_from_all_rules( $scenario_id );

		return true;
	}

	/**
	 * Remove a scenario from all post type rules.
	 * 
	 * Called when a scenario is deleted.
	 *
	 * @param string $scenario_id Scenario ID.
	 */
	private static function remove_from_all_rules( $scenario_id ) {
		require_once plugin_dir_path( __FILE__ ) . 'class-push-rules.php';
		
		$all_rules = Custom_PWA_Push_Rules::get_all_rules();
		$modified = false;

		foreach ( $all_rules as $post_type => $rules ) {
			if ( isset( $rules['scenarios'][ $scenario_id ] ) ) {
				unset( $all_rules[ $post_type ]['scenarios'][ $scenario_id ] );
				$modified = true;
			}
		}

		if ( $modified ) {
			Custom_PWA_Push_Rules::save_rules( $all_rules );
		}
	}

	/**
	 * Sanitize and validate scenario data.
	 *
	 * @param array $data Raw scenario data.
	 * @return array|WP_Error Sanitized data or WP_Error on validation failure.
	 */
	private static function sanitize_scenario( $data ) {
		$sanitized = array();

		// ID (required).
		if ( empty( $data['id'] ) ) {
			return new WP_Error( 'missing_id', __( 'Scenario ID is required.', 'custom-pwa' ) );
		}
		$sanitized['id'] = sanitize_key( $data['id'] );

		// Label (required).
		if ( empty( $data['label'] ) ) {
			return new WP_Error( 'missing_label', __( 'Scenario label is required.', 'custom-pwa' ) );
		}
		$sanitized['label'] = sanitize_text_field( $data['label'] );

		// Description.
		$sanitized['description'] = isset( $data['description'] ) ? sanitize_textarea_field( $data['description'] ) : '';

		// Scope (required: global or post_type).
		$valid_scopes = array( 'global', 'post_type' );
		if ( empty( $data['scope'] ) || ! in_array( $data['scope'], $valid_scopes, true ) ) {
			return new WP_Error( 'invalid_scope', __( 'Scenario scope must be "global" or "post_type".', 'custom-pwa' ) );
		}
		$sanitized['scope'] = $data['scope'];

		// Post types (required if scope = post_type).
		if ( $sanitized['scope'] === 'post_type' ) {
			if ( empty( $data['post_types'] ) || ! is_array( $data['post_types'] ) ) {
				return new WP_Error( 'missing_post_types', __( 'Post types are required for post-type-specific scenarios.', 'custom-pwa' ) );
			}
			$sanitized['post_types'] = array_map( 'sanitize_key', $data['post_types'] );
		} else {
			$sanitized['post_types'] = array();
		}

		// Trigger configuration.
		$valid_trigger_types = array( 'on_publish', 'on_update', 'on_meta_change', 'on_status_change' );
		$trigger_type = isset( $data['trigger']['type'] ) ? $data['trigger']['type'] : 'on_publish';
		
		if ( ! in_array( $trigger_type, $valid_trigger_types, true ) ) {
			$trigger_type = 'on_publish';
		}

		$sanitized['trigger'] = array(
			'type'      => $trigger_type,
			'meta_key'  => isset( $data['trigger']['meta_key'] ) ? sanitize_text_field( $data['trigger']['meta_key'] ) : '',
			'old_value' => isset( $data['trigger']['old_value'] ) ? sanitize_text_field( $data['trigger']['old_value'] ) : '',
			'new_value' => isset( $data['trigger']['new_value'] ) ? sanitize_text_field( $data['trigger']['new_value'] ) : '',
		);

		// Templates.
		$sanitized['templates'] = array(
			'title_template' => isset( $data['templates']['title_template'] ) ? sanitize_text_field( $data['templates']['title_template'] ) : '{post_title}',
			'body_template'  => isset( $data['templates']['body_template'] ) ? sanitize_textarea_field( $data['templates']['body_template'] ) : '{excerpt}',
			'url_template'   => isset( $data['templates']['url_template'] ) ? sanitize_text_field( $data['templates']['url_template'] ) : '{permalink}',
		);

		// Fields used.
		$core_fields = isset( $data['fields_used']['core_fields'] ) && is_array( $data['fields_used']['core_fields'] )
			? array_map( 'sanitize_text_field', $data['fields_used']['core_fields'] )
			: array( 'post_title', 'post_status' );

		$meta_keys = isset( $data['fields_used']['meta_keys'] ) && is_array( $data['fields_used']['meta_keys'] )
			? array_map( 'sanitize_text_field', $data['fields_used']['meta_keys'] )
			: array();

		$sanitized['fields_used'] = array(
			'core_fields' => $core_fields,
			'meta_keys'   => $meta_keys,
		);

		/**
		 * Filter sanitized custom scenario before saving.
		 *
		 * @since 1.1.0
		 *
		 * @param array $sanitized Sanitized scenario data.
		 * @param array $data      Raw input data.
		 */
		return apply_filters( 'custom_pwa_sanitize_custom_scenario', $sanitized, $data );
	}

	/**
	 * Get available trigger types.
	 *
	 * @return array Trigger types with labels.
	 */
	public static function get_trigger_types() {
		$types = array(
			'on_publish'       => __( 'On Publish (new post)', 'custom-pwa' ),
			'on_update'        => __( 'On Update (existing post edited)', 'custom-pwa' ),
			'on_meta_change'   => __( 'On Meta Field Change', 'custom-pwa' ),
			'on_status_change' => __( 'On Status Change', 'custom-pwa' ),
		);

		/**
		 * Filter available trigger types.
		 *
		 * @since 1.1.0
		 *
		 * @param array $types Trigger types array.
		 */
		return apply_filters( 'custom_pwa_trigger_types', $types );
	}

	/**
	 * Get common core fields that can be used in scenarios.
	 *
	 * @return array Core field names with labels.
	 */
	public static function get_core_fields() {
		$fields = array(
			'post_title'   => __( 'Post Title', 'custom-pwa' ),
			'post_content' => __( 'Post Content', 'custom-pwa' ),
			'post_excerpt' => __( 'Post Excerpt', 'custom-pwa' ),
			'post_status'  => __( 'Post Status', 'custom-pwa' ),
			'post_date'    => __( 'Post Date', 'custom-pwa' ),
			'post_author'  => __( 'Post Author', 'custom-pwa' ),
			'post_type'    => __( 'Post Type', 'custom-pwa' ),
		);

		/**
		 * Filter available core fields.
		 *
		 * @since 1.1.0
		 *
		 * @param array $fields Core fields array.
		 */
		return apply_filters( 'custom_pwa_core_fields', $fields );
	}

	/**
	 * Check if a scenario is user-defined (vs built-in).
	 *
	 * @param string $scenario_id Scenario ID.
	 * @return bool True if custom/user-defined, false if built-in.
	 */
	public static function is_custom( $scenario_id ) {
		// Custom scenarios have IDs starting with "custom_".
		return strpos( $scenario_id, 'custom_' ) === 0;
	}

	/**
	 * Get default scenario structure.
	 * 
	 * Useful for creating new scenarios with pre-filled defaults.
	 *
	 * @return array Default scenario structure.
	 */
	public static function get_default_structure() {
		return array(
			'id'          => '',
			'label'       => '',
			'description' => '',
			'scope'       => 'global',
			'post_types'  => array(),
			'trigger'     => array(
				'type'      => 'on_publish',
				'meta_key'  => '',
				'old_value' => '',
				'new_value' => '',
			),
			'templates'   => array(
				'title_template' => '{post_title}',
				'body_template'  => '{excerpt}',
				'url_template'   => '{permalink}',
			),
			'fields_used' => array(
				'core_fields' => array( 'post_title', 'post_status' ),
				'meta_keys'   => array(),
			),
		);
	}
}
