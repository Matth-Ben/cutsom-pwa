<?php
/**
 * Push Rules Data Handler Class
 * 
 * Manages storage, retrieval, and manipulation of push notification rules.
 * Rules are stored in a single option with this structure:
 * 
 * array(
 *     'post_type_name' => array(
 *         'config' => array(
 *             'enabled' => bool,
 *             'custom_fields' => array( ... ),
 *             // other general settings
 *         ),
 *         'scenarios' => array(
 *             'scenario_key' => array(
 *                 'enabled' => bool,
 *                 'title_template' => string,
 *                 'body_template' => string,
 *                 'url_template' => string,
 *                 'threshold' => int (optional),
 *                 'meta_key' => string (optional),
 *                 // other scenario-specific fields
 *             ),
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
 * Custom_PWA_Push_Rules class.
 * 
 * Provides helper functions for push notification rules data management.
 */
class Custom_PWA_Push_Rules {

	/**
	 * Option name for storing push rules.
	 *
	 * @var string
	 */
	const OPTION_NAME = 'custom_pwa_push_rules';

	/**
	 * Get all push notification rules.
	 * 
	 * Returns the complete rules array from the database,
	 * or an empty array if no rules are saved.
	 * 
	 * Automatically migrates old format to new format if needed.
	 *
	 * @return array Complete rules array.
	 */
	public static function get_all_rules() {
		$rules = get_option( self::OPTION_NAME, array() );
		
		if ( ! is_array( $rules ) ) {
			return array();
		}
		
		// Check if migration is needed (old format detection)
		$needs_migration = false;
		foreach ( $rules as $post_type => $data ) {
			// Old format has 'enabled', 'title', 'body', 'url' directly
			// New format has 'config' and 'scenarios'
			if ( isset( $data['enabled'] ) || isset( $data['title'] ) || isset( $data['body'] ) ) {
				$needs_migration = true;
				break;
			}
		}
		
		// Migrate if needed
		if ( $needs_migration ) {
			$rules = self::migrate_old_format( $rules );
		}
		
		return $rules;
	}
	
	/**
	 * Migrate old format rules to new format.
	 * 
	 * Old format: array('post' => array('enabled' => true, 'title' => '...', 'body' => '...', 'url' => '...'))
	 * New format: array('post' => array('config' => array(...), 'scenarios' => array(...)))
	 *
	 * @param array $old_rules Old format rules.
	 * @return array New format rules.
	 */
	private static function migrate_old_format( $old_rules ) {
		$new_rules = array();
		
		foreach ( $old_rules as $post_type => $old_data ) {
			// Skip if already in new format
			if ( isset( $old_data['config'] ) && isset( $old_data['scenarios'] ) ) {
				$new_rules[ $post_type ] = $old_data;
				continue;
			}
			
			// Get default rules for this post type
			$defaults = self::get_default_post_type_rules( $post_type );
			
			// If old data has custom templates, apply them to first scenario
			if ( isset( $old_data['title'] ) || isset( $old_data['body'] ) || isset( $old_data['url'] ) ) {
				$scenario_keys = array_keys( $defaults['scenarios'] );
				if ( ! empty( $scenario_keys ) ) {
					$first_scenario = $scenario_keys[0];
					$defaults['scenarios'][ $first_scenario ]['enabled'] = isset( $old_data['enabled'] ) ? $old_data['enabled'] : false;
					if ( isset( $old_data['title'] ) ) {
						$defaults['scenarios'][ $first_scenario ]['title_template'] = $old_data['title'];
					}
					if ( isset( $old_data['body'] ) ) {
						$defaults['scenarios'][ $first_scenario ]['body_template'] = $old_data['body'];
					}
					if ( isset( $old_data['url'] ) ) {
						$defaults['scenarios'][ $first_scenario ]['url_template'] = $old_data['url'];
					}
				}
			}
			
			$new_rules[ $post_type ] = $defaults;
		}
		
		// Save migrated rules
		update_option( self::OPTION_NAME, $new_rules );
		
		return $new_rules;
	}

	/**
	 * Get rules for a specific post type.
	 * 
	 * Returns both 'config' and 'scenarios' for the post type,
	 * merged with defaults if not already saved.
	 *
	 * @param string $post_type Post type name.
	 * @return array|null Rules array or null if post type not found.
	 */
	public static function get_post_type_rules( $post_type ) {
		$all_rules = self::get_all_rules();

		if ( ! isset( $all_rules[ $post_type ] ) ) {
			// Return defaults merged with role scenarios.
			return self::get_default_post_type_rules( $post_type );
		}

		// Merge saved rules with defaults to ensure all fields exist.
		$defaults = self::get_default_post_type_rules( $post_type );
		return self::merge_rules( $defaults, $all_rules[ $post_type ] );
	}

	/**
	 * Get default rules for a post type based on its assigned role.
	 * 
	 * Includes BOTH built-in scenarios (from role) AND user-defined scenarios
	 * (global or post-type-specific).
	 *
	 * @param string $post_type Post type name.
	 * @return array Default rules array.
	 */
	public static function get_default_post_type_rules( $post_type ) {
		// Get the role assigned to this post type.
		$role = self::get_post_type_role( $post_type );

		// Get COMBINED scenarios (built-in + custom) for this post type.
		require_once plugin_dir_path( __FILE__ ) . 'class-push-scenarios.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-custom-scenarios.php';
		$combined_scenarios = Custom_PWA_Push_Scenarios::get_combined_scenarios_for_post_type( $post_type, $role );

		$default_rules = array(
			'config'    => array(
				'enabled' => false,
			),
			'scenarios' => array(),
		);

		// Build default scenario rules from combined list.
		if ( $combined_scenarios ) {
			foreach ( $combined_scenarios as $key => $scenario ) {
				$default_rules['scenarios'][ $key ] = array(
					'enabled'        => false,
					'title_template' => $scenario['default_title'],
					'body_template'  => $scenario['default_body'],
					'url_template'   => $scenario['default_url'],
				);

				// Add default values for extra fields (for built-in scenarios).
				if ( ! empty( $scenario['fields'] ) ) {
					foreach ( $scenario['fields'] as $field_key => $field_config ) {
						$default_rules['scenarios'][ $key ][ $field_key ] = isset( $field_config['default'] ) 
							? $field_config['default'] 
							: '';
					}
				}
			}
		}

		return $default_rules;
	}

	/**
	 * Get the role assigned to a post type.
	 * 
	 * Retrieves from config option, defaults to 'generic' if not set.
	 *
	 * @param string $post_type Post type name.
	 * @return string Role key (blog, events, ecommerce, generic).
	 */
	public static function get_post_type_role( $post_type ) {
		$config = get_option( 'custom_pwa_config', array() );
		
		if ( isset( $config['post_type_roles'][ $post_type ] ) ) {
			return $config['post_type_roles'][ $post_type ];
		}

		// Default to generic role.
		return 'generic';
	}

	/**
	 * Get rules for a specific scenario of a post type.
	 *
	 * @param string $post_type    Post type name.
	 * @param string $scenario_key Scenario key.
	 * @return array|null Scenario rules or null if not found.
	 */
	public static function get_scenario_rules( $post_type, $scenario_key ) {
		$post_type_rules = self::get_post_type_rules( $post_type );

		if ( ! $post_type_rules || ! isset( $post_type_rules['scenarios'][ $scenario_key ] ) ) {
			return null;
		}

		return $post_type_rules['scenarios'][ $scenario_key ];
	}

	/**
	 * Check if push notifications are enabled for a post type.
	 *
	 * @param string $post_type Post type name.
	 * @return bool True if enabled, false otherwise.
	 */
	public static function is_enabled_for_post_type( $post_type ) {
		$rules = self::get_post_type_rules( $post_type );
		return $rules && ! empty( $rules['config']['enabled'] );
	}

	/**
	 * Check if a specific scenario is enabled for a post type.
	 *
	 * @param string $post_type    Post type name.
	 * @param string $scenario_key Scenario key.
	 * @return bool True if enabled, false otherwise.
	 */
	public static function is_scenario_enabled( $post_type, $scenario_key ) {
		$scenario_rules = self::get_scenario_rules( $post_type, $scenario_key );
		return $scenario_rules && ! empty( $scenario_rules['enabled'] );
	}

	/**
	 * Save all push notification rules.
	 *
	 * @param array $rules Complete rules array to save.
	 * @return bool True on success, false on failure.
	 */
	public static function save_rules( $rules ) {
		return update_option( self::OPTION_NAME, $rules );
	}

	/**
	 * Save rules for a specific post type.
	 *
	 * @param string $post_type Post type name.
	 * @param array  $rules     Rules array for this post type.
	 * @return bool True on success, false on failure.
	 */
	public static function save_post_type_rules( $post_type, $rules ) {
		$all_rules = self::get_all_rules();
		$all_rules[ $post_type ] = $rules;
		return self::save_rules( $all_rules );
	}

	/**
	 * Delete rules for a specific post type.
	 *
	 * @param string $post_type Post type name.
	 * @return bool True on success, false on failure.
	 */
	public static function delete_post_type_rules( $post_type ) {
		$all_rules = self::get_all_rules();
		unset( $all_rules[ $post_type ] );
		return self::save_rules( $all_rules );
	}

	/**
	 * Merge saved rules with defaults.
	 * 
	 * Ensures all expected fields exist in the saved rules.
	 * Saved values take precedence over defaults.
	 *
	 * @param array $defaults Default rules array.
	 * @param array $saved    Saved rules array.
	 * @return array Merged rules array.
	 */
	private static function merge_rules( $defaults, $saved ) {
		// Merge config section.
		if ( isset( $saved['config'] ) ) {
			$defaults['config'] = array_merge( $defaults['config'], $saved['config'] );
		}

		// Merge scenarios section.
		if ( isset( $saved['scenarios'] ) ) {
			foreach ( $saved['scenarios'] as $scenario_key => $scenario_data ) {
				if ( isset( $defaults['scenarios'][ $scenario_key ] ) ) {
					$defaults['scenarios'][ $scenario_key ] = array_merge(
						$defaults['scenarios'][ $scenario_key ],
						$scenario_data
					);
				} else {
					// Scenario exists in saved data but not in defaults (could be from a disabled role).
					$defaults['scenarios'][ $scenario_key ] = $scenario_data;
				}
			}
		}

		return $defaults;
	}

	/**
	 * Sanitize rules before saving.
	 * 
	 * Validates and cleans all rule data.
	 *
	 * @param array $rules Raw rules array from form input.
	 * @return array Sanitized rules array.
	 */
	public static function sanitize_rules( $rules ) {
		$sanitized = array();

		if ( ! is_array( $rules ) ) {
			return $sanitized;
		}

		foreach ( $rules as $post_type => $post_type_rules ) {
			$post_type = sanitize_key( $post_type );

			// Verify post type exists.
			if ( ! post_type_exists( $post_type ) ) {
				continue;
			}

			$sanitized[ $post_type ] = array(
				'config'    => array(),
				'scenarios' => array(),
			);

			// Sanitize config section.
			if ( isset( $post_type_rules['config'] ) && is_array( $post_type_rules['config'] ) ) {
				$config = $post_type_rules['config'];
				$sanitized[ $post_type ]['config']['enabled'] = ! empty( $config['enabled'] );

				// Sanitize any additional config fields.
				foreach ( $config as $key => $value ) {
					if ( $key === 'enabled' ) {
						continue; // Already handled.
					}
					
					$sanitized[ $post_type ]['config'][ sanitize_key( $key ) ] = self::sanitize_field( $value );
				}
			}

			// Sanitize scenarios section.
			if ( isset( $post_type_rules['scenarios'] ) && is_array( $post_type_rules['scenarios'] ) ) {
				foreach ( $post_type_rules['scenarios'] as $scenario_key => $scenario_data ) {
					$scenario_key = sanitize_key( $scenario_key );

					if ( ! is_array( $scenario_data ) ) {
						continue;
					}

					$sanitized[ $post_type ]['scenarios'][ $scenario_key ] = array(
						'enabled'        => ! empty( $scenario_data['enabled'] ),
						'title_template' => isset( $scenario_data['title_template'] ) 
							? sanitize_text_field( $scenario_data['title_template'] ) 
							: '',
						'body_template'  => isset( $scenario_data['body_template'] ) 
							? sanitize_textarea_field( $scenario_data['body_template'] ) 
							: '',
						'url_template'   => isset( $scenario_data['url_template'] ) 
							? esc_url_raw( $scenario_data['url_template'], array( 'http', 'https', '{' ) ) 
							: '',
					);

					// Sanitize any additional scenario fields (thresholds, meta keys, etc.).
					foreach ( $scenario_data as $key => $value ) {
						if ( in_array( $key, array( 'enabled', 'title_template', 'body_template', 'url_template' ), true ) ) {
							continue; // Already handled.
						}

						$sanitized[ $post_type ]['scenarios'][ $scenario_key ][ sanitize_key( $key ) ] = self::sanitize_field( $value );
					}
				}
			}
		}

		/**
		 * Filter sanitized push rules before saving.
		 *
		 * @since 1.1.0
		 *
		 * @param array $sanitized Sanitized rules.
		 * @param array $rules     Raw input rules.
		 */
		return apply_filters( 'custom_pwa_sanitize_push_rules', $sanitized, $rules );
	}

	/**
	 * Sanitize a single field value.
	 * 
	 * Handles different data types appropriately.
	 *
	 * @param mixed $value Field value.
	 * @return mixed Sanitized value.
	 */
	private static function sanitize_field( $value ) {
		if ( is_array( $value ) ) {
			return array_map( array( __CLASS__, 'sanitize_field' ), $value );
		}

		if ( is_numeric( $value ) ) {
			return is_float( $value ) ? (float) $value : (int) $value;
		}

		if ( is_bool( $value ) ) {
			return (bool) $value;
		}

		// Default to text sanitization.
		return sanitize_text_field( $value );
	}

	/**
	 * Get enabled post types from config.
	 *
	 * @return array Array of enabled post type names.
	 */
	public static function get_enabled_post_types() {
		$config = get_option( 'custom_pwa_config', array() );
		return isset( $config['enabled_post_types'] ) ? $config['enabled_post_types'] : array();
	}
}
