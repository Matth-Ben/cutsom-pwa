<?php
/**
 * Push Scenarios Configuration Class
 * 
 * Defines the central scenario configuration mapping roles to notification scenarios.
 * Each scenario has a key, label, description, and default templates.
 * 
 * This configuration is filterable, allowing developers to add custom roles and scenarios.
 *
 * @package Custom_PWA
 * @since 1.1.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Custom_PWA_Push_Scenarios class.
 * 
 * Provides scenario configuration for different post type roles.
 */
class Custom_PWA_Push_Scenarios {

	/**
	 * Get all available scenarios grouped by role.
	 * 
	 * Returns an array of roles, each containing scenarios with:
	 * - key: unique identifier (used in database)
	 * - label: human-readable name (for admin UI)
	 * - description: what this scenario does (shown to admins)
	 * - default_title: default notification title template
	 * - default_body: default notification body template
	 * - default_url: default notification URL template
	 * - fields: optional array of extra config fields (thresholds, meta keys, etc.)
	 *
	 * @return array Scenarios configuration array.
	 */
	public static function get_scenarios() {
		$scenarios = array(
			'blog' => array(
				'label'     => __( 'Blog/Articles', 'custom-pwa' ),
				'scenarios' => array(
					'publication' => array(
						'key'           => 'publication',
						'label'         => __( 'Publication', 'custom-pwa' ),
						'description'   => __( 'Triggered when a new article is published.', 'custom-pwa' ),
						'default_title' => __( 'New article: {post_title}', 'custom-pwa' ),
						'default_body'  => __( '{excerpt}', 'custom-pwa' ),
						'default_url'   => '{permalink}',
						'fields'        => array(),
					),
					'major_update' => array(
						'key'           => 'major_update',
						'label'         => __( 'Major Update', 'custom-pwa' ),
						'description'   => __( 'Triggered when an important update is made to an existing article.', 'custom-pwa' ),
						'default_title' => __( 'Updated: {post_title}', 'custom-pwa' ),
						'default_body'  => __( 'This article has been updated with new information.', 'custom-pwa' ),
						'default_url'   => '{permalink}',
						'fields'        => array(
							'meta_key' => array(
								'type'        => 'text',
								'label'       => __( 'Major Update Meta Key', 'custom-pwa' ),
								'description' => __( 'Custom field name that triggers this notification (e.g., "major_update").', 'custom-pwa' ),
								'default'     => 'major_update',
							),
						),
					),
					'featured' => array(
						'key'           => 'featured',
						'label'         => __( 'Featured Article', 'custom-pwa' ),
						'description'   => __( 'Triggered when an article is marked as featured.', 'custom-pwa' ),
						'default_title' => __( '⭐ Featured: {post_title}', 'custom-pwa' ),
						'default_body'  => __( 'Check out our featured article!', 'custom-pwa' ),
						'default_url'   => '{permalink}',
						'fields'        => array(
							'meta_key' => array(
								'type'        => 'text',
								'label'       => __( 'Featured Meta Key', 'custom-pwa' ),
								'description' => __( 'Custom field name for featured status (e.g., "is_featured").', 'custom-pwa' ),
								'default'     => 'is_featured',
							),
						),
					),
				),
			),
			
			'events' => array(
				'label'     => __( 'Events/Concerts', 'custom-pwa' ),
				'scenarios' => array(
					'publication' => array(
						'key'           => 'publication',
						'label'         => __( 'New Event Published', 'custom-pwa' ),
						'description'   => __( 'Triggered when a new event is published.', 'custom-pwa' ),
						'default_title' => __( 'New event: {post_title}', 'custom-pwa' ),
						'default_body'  => __( '{event_date} at {venue}', 'custom-pwa' ),
						'default_url'   => '{permalink}',
						'fields'        => array(),
					),
					'sales_open' => array(
						'key'           => 'sales_open',
						'label'         => __( 'Ticket Sales Open', 'custom-pwa' ),
						'description'   => __( 'Triggered when ticket sales open for an event.', 'custom-pwa' ),
						'default_title' => __( '🎟️ Tickets now available: {post_title}', 'custom-pwa' ),
						'default_body'  => __( 'Get your tickets now for {event_date}!', 'custom-pwa' ),
						'default_url'   => '{permalink}',
						'fields'        => array(
							'meta_key' => array(
								'type'        => 'text',
								'label'       => __( 'Sales Status Meta Key', 'custom-pwa' ),
								'description' => __( 'Custom field name for sales status (e.g., "sales_status").', 'custom-pwa' ),
								'default'     => 'sales_status',
							),
						),
					),
					'last_tickets' => array(
						'key'           => 'last_tickets',
						'label'         => __( 'Last Tickets Available', 'custom-pwa' ),
						'description'   => __( 'Triggered when remaining tickets fall below a threshold.', 'custom-pwa' ),
						'default_title' => __( '⚠️ Last tickets: {post_title}', 'custom-pwa' ),
						'default_body'  => __( 'Hurry! Only a few tickets left for {event_date}!', 'custom-pwa' ),
						'default_url'   => '{permalink}',
						'fields'        => array(
							'threshold' => array(
								'type'        => 'number',
								'label'       => __( 'Ticket Threshold', 'custom-pwa' ),
								'description' => __( 'Send notification when tickets remaining fall below this number.', 'custom-pwa' ),
								'default'     => 50,
							),
							'meta_key' => array(
								'type'        => 'text',
								'label'       => __( 'Tickets Remaining Meta Key', 'custom-pwa' ),
								'description' => __( 'Custom field name for remaining tickets count.', 'custom-pwa' ),
								'default'     => 'tickets_remaining',
							),
						),
					),
					'sold_out' => array(
						'key'           => 'sold_out',
						'label'         => __( 'Event Sold Out', 'custom-pwa' ),
						'description'   => __( 'Triggered when an event is sold out.', 'custom-pwa' ),
						'default_title' => __( '🔴 SOLD OUT: {post_title}', 'custom-pwa' ),
						'default_body'  => __( 'This event is now sold out. Check our other events!', 'custom-pwa' ),
						'default_url'   => '{permalink}',
						'fields'        => array(
							'meta_key' => array(
								'type'        => 'text',
								'label'       => __( 'Sold Out Status Meta Key', 'custom-pwa' ),
								'description' => __( 'Custom field name for sold out status.', 'custom-pwa' ),
								'default'     => 'is_sold_out',
							),
						),
					),
					'cancelled' => array(
						'key'           => 'cancelled',
						'label'         => __( 'Event Cancelled', 'custom-pwa' ),
						'description'   => __( 'Triggered when an event is cancelled.', 'custom-pwa' ),
						'default_title' => __( '❌ Cancelled: {post_title}', 'custom-pwa' ),
						'default_body'  => __( 'This event has been cancelled. Refunds will be processed.', 'custom-pwa' ),
						'default_url'   => '{permalink}',
						'fields'        => array(
							'meta_key' => array(
								'type'        => 'text',
								'label'       => __( 'Cancelled Status Meta Key', 'custom-pwa' ),
								'description' => __( 'Custom field name for cancelled status.', 'custom-pwa' ),
								'default'     => 'is_cancelled',
							),
						),
					),
					'rescheduled' => array(
						'key'           => 'rescheduled',
						'label'         => __( 'Event Rescheduled', 'custom-pwa' ),
						'description'   => __( 'Triggered when an event date is changed.', 'custom-pwa' ),
						'default_title' => __( '📅 Date changed: {post_title}', 'custom-pwa' ),
						'default_body'  => __( 'New date: {event_date}', 'custom-pwa' ),
						'default_url'   => '{permalink}',
						'fields'        => array(
							'meta_key' => array(
								'type'        => 'text',
								'label'       => __( 'Event Date Meta Key', 'custom-pwa' ),
								'description' => __( 'Custom field name for event date.', 'custom-pwa' ),
								'default'     => 'event_date',
							),
						),
					),
					'reminder' => array(
						'key'           => 'reminder',
						'label'         => __( 'Event Reminder', 'custom-pwa' ),
						'description'   => __( 'Scheduled reminder before the event (e.g., D-7 or D-1).', 'custom-pwa' ),
						'default_title' => __( '🔔 Reminder: {post_title}', 'custom-pwa' ),
						'default_body'  => __( 'Event coming up: {event_date}', 'custom-pwa' ),
						'default_url'   => '{permalink}',
						'fields'        => array(
							'days_before' => array(
								'type'        => 'number',
								'label'       => __( 'Days Before Event', 'custom-pwa' ),
								'description' => __( 'Send reminder this many days before the event.', 'custom-pwa' ),
								'default'     => 1,
							),
						),
					),
				),
			),
			
			'ecommerce' => array(
				'label'     => __( 'E-commerce/Products', 'custom-pwa' ),
				'scenarios' => array(
					'new_product' => array(
						'key'           => 'new_product',
						'label'         => __( 'New Product', 'custom-pwa' ),
						'description'   => __( 'Triggered when a new product is published.', 'custom-pwa' ),
						'default_title' => __( 'New product: {post_title}', 'custom-pwa' ),
						'default_body'  => __( '{excerpt}', 'custom-pwa' ),
						'default_url'   => '{permalink}',
						'fields'        => array(),
					),
					'price_drop' => array(
						'key'           => 'price_drop',
						'label'         => __( 'Price Drop / Promotion', 'custom-pwa' ),
						'description'   => __( 'Triggered when product price drops or a promotion starts.', 'custom-pwa' ),
						'default_title' => __( '💰 Price drop: {post_title}', 'custom-pwa' ),
						'default_body'  => __( 'Now only {price}! Limited time offer.', 'custom-pwa' ),
						'default_url'   => '{permalink}',
						'fields'        => array(
							'meta_key' => array(
								'type'        => 'text',
								'label'       => __( 'Price Meta Key', 'custom-pwa' ),
								'description' => __( 'Custom field name for product price.', 'custom-pwa' ),
								'default'     => '_price',
							),
						),
					),
					'back_in_stock' => array(
						'key'           => 'back_in_stock',
						'label'         => __( 'Back in Stock', 'custom-pwa' ),
						'description'   => __( 'Triggered when a product is back in stock.', 'custom-pwa' ),
						'default_title' => __( '✅ Back in stock: {post_title}', 'custom-pwa' ),
						'default_body'  => __( 'Get it while supplies last!', 'custom-pwa' ),
						'default_url'   => '{permalink}',
						'fields'        => array(
							'meta_key' => array(
								'type'        => 'text',
								'label'       => __( 'Stock Status Meta Key', 'custom-pwa' ),
								'description' => __( 'Custom field name for stock status.', 'custom-pwa' ),
								'default'     => '_stock_status',
							),
						),
					),
					'out_of_stock' => array(
						'key'           => 'out_of_stock',
						'label'         => __( 'Out of Stock', 'custom-pwa' ),
						'description'   => __( 'Triggered when a product runs out of stock.', 'custom-pwa' ),
						'default_title' => __( '🔴 Out of stock: {post_title}', 'custom-pwa' ),
						'default_body'  => __( 'This item is currently unavailable.', 'custom-pwa' ),
						'default_url'   => '{permalink}',
						'fields'        => array(
							'meta_key' => array(
								'type'        => 'text',
								'label'       => __( 'Stock Status Meta Key', 'custom-pwa' ),
								'description' => __( 'Custom field name for stock status.', 'custom-pwa' ),
								'default'     => '_stock_status',
							),
						),
					),
					'low_stock' => array(
						'key'           => 'low_stock',
						'label'         => __( 'Low Stock Alert', 'custom-pwa' ),
						'description'   => __( 'Triggered when stock falls below a threshold.', 'custom-pwa' ),
						'default_title' => __( '⚠️ Low stock: {post_title}', 'custom-pwa' ),
						'default_body'  => __( 'Hurry! Only {stock} left!', 'custom-pwa' ),
						'default_url'   => '{permalink}',
						'fields'        => array(
							'threshold' => array(
								'type'        => 'number',
								'label'       => __( 'Low Stock Threshold', 'custom-pwa' ),
								'description' => __( 'Send notification when stock falls below this number.', 'custom-pwa' ),
								'default'     => 5,
							),
							'meta_key' => array(
								'type'        => 'text',
								'label'       => __( 'Stock Quantity Meta Key', 'custom-pwa' ),
								'description' => __( 'Custom field name for stock quantity.', 'custom-pwa' ),
								'default'     => '_stock',
							),
						),
					),
					'end_of_life' => array(
						'key'           => 'end_of_life',
						'label'         => __( 'End of Life / Discontinued', 'custom-pwa' ),
						'description'   => __( 'Triggered when a product is being retired or discontinued.', 'custom-pwa' ),
						'default_title' => __( '⚠️ Last chance: {post_title}', 'custom-pwa' ),
						'default_body'  => __( 'This product will soon be discontinued.', 'custom-pwa' ),
						'default_url'   => '{permalink}',
						'fields'        => array(
							'meta_key' => array(
								'type'        => 'text',
								'label'       => __( 'EOL Status Meta Key', 'custom-pwa' ),
								'description' => __( 'Custom field name for end-of-life status.', 'custom-pwa' ),
								'default'     => 'is_discontinued',
							),
						),
					),
				),
			),
			
			'generic' => array(
				'label'     => __( 'Generic/Other', 'custom-pwa' ),
				'scenarios' => array(
					'publication' => array(
						'key'           => 'publication',
						'label'         => __( 'Publication', 'custom-pwa' ),
						'description'   => __( 'Triggered when a new item is published.', 'custom-pwa' ),
						'default_title' => __( 'New: {post_title}', 'custom-pwa' ),
						'default_body'  => __( '{excerpt}', 'custom-pwa' ),
						'default_url'   => '{permalink}',
						'fields'        => array(),
					),
					'major_update' => array(
						'key'           => 'major_update',
						'label'         => __( 'Major Update', 'custom-pwa' ),
						'description'   => __( 'Triggered when an important update is made.', 'custom-pwa' ),
						'default_title' => __( 'Updated: {post_title}', 'custom-pwa' ),
						'default_body'  => __( 'This item has been updated.', 'custom-pwa' ),
						'default_url'   => '{permalink}',
						'fields'        => array(
							'meta_key' => array(
								'type'        => 'text',
								'label'       => __( 'Update Trigger Meta Key', 'custom-pwa' ),
								'description' => __( 'Custom field name that triggers this notification.', 'custom-pwa' ),
								'default'     => 'major_update',
							),
						),
					),
					'status_change' => array(
						'key'           => 'status_change',
						'label'         => __( 'Status Change', 'custom-pwa' ),
						'description'   => __( 'Triggered when a custom status field changes.', 'custom-pwa' ),
						'default_title' => __( '{status_label}: {post_title}', 'custom-pwa' ),
						'default_body'  => __( 'Status has been updated.', 'custom-pwa' ),
						'default_url'   => '{permalink}',
						'fields'        => array(
							'meta_key' => array(
								'type'        => 'text',
								'label'       => __( 'Status Meta Key', 'custom-pwa' ),
								'description' => __( 'Custom field name for status.', 'custom-pwa' ),
								'default'     => 'status',
							),
						),
					),
				),
			),
		);

		/**
		 * Filter the push notification scenarios configuration.
		 * 
		 * Developers can use this filter to add custom roles and scenarios.
		 * 
		 * Example:
		 * 
		 * add_filter( 'custom_pwa_push_scenarios', function( $scenarios ) {
		 *     $scenarios['courses'] = array(
		 *         'label' => 'Online Courses',
		 *         'scenarios' => array(
		 *             'new_course' => array(
		 *                 'key' => 'new_course',
		 *                 'label' => 'New Course Available',
		 *                 'description' => 'Triggered when a new course is published.',
		 *                 'default_title' => 'New course: {post_title}',
		 *                 'default_body' => 'Enroll now!',
		 *                 'default_url' => '{permalink}',
		 *                 'fields' => array(),
		 *             ),
		 *         ),
		 *     );
		 *     return $scenarios;
		 * } );
		 *
		 * @since 1.1.0
		 *
		 * @param array $scenarios Scenarios configuration array.
		 */
		return apply_filters( 'custom_pwa_push_scenarios', $scenarios );
	}

	/**
	 * Get scenarios for a specific role.
	 *
	 * @param string $role Role key (blog, events, ecommerce, generic).
	 * @return array|null Scenarios array or null if role not found.
	 */
	public static function get_scenarios_for_role( $role ) {
		$all_scenarios = self::get_scenarios();
		return isset( $all_scenarios[ $role ] ) ? $all_scenarios[ $role ]['scenarios'] : null;
	}

	/**
	 * Get combined scenarios for a post type.
	 * 
	 * Returns BUILT-IN scenarios (based on role) PLUS user-defined scenarios
	 * (global or post-type-specific) that apply to this post type.
	 * 
	 * The result is formatted consistently with built-in scenario structure,
	 * converting custom scenario data to match the expected format.
	 *
	 * @param string $post_type Post type name.
	 * @param string $role      Role assigned to this post type.
	 * @return array Combined scenarios array.
	 */
	public static function get_combined_scenarios_for_post_type( $post_type, $role ) {
		$scenarios = array();

		// 1. Get built-in scenarios for this role.
		$built_in = self::get_scenarios_for_role( $role );
		if ( $built_in ) {
			$scenarios = $built_in;
		}

		// 2. Get user-defined scenarios (requires class-custom-scenarios.php).
		if ( class_exists( 'Custom_PWA_Custom_Scenarios' ) ) {
			$custom_scenarios = Custom_PWA_Custom_Scenarios::get_for_post_type( $post_type );

			// Convert custom scenarios to built-in format.
			foreach ( $custom_scenarios as $scenario_id => $custom_scenario ) {
				$scenarios[ $scenario_id ] = array(
					'key'           => $scenario_id,
					'label'         => $custom_scenario['label'],
					'description'   => $custom_scenario['description'],
					'default_title' => $custom_scenario['templates']['title_template'],
					'default_body'  => $custom_scenario['templates']['body_template'],
					'default_url'   => $custom_scenario['templates']['url_template'],
					'fields'        => array(), // Custom scenarios don't use the "fields" config like built-ins
					'is_custom'     => true, // Flag to identify custom scenarios
					'trigger'       => $custom_scenario['trigger'],
					'fields_used'   => $custom_scenario['fields_used'],
					'scope'         => $custom_scenario['scope'],
				);
			}
		}

		return $scenarios;
	}

	/**
	 * Get a specific scenario configuration.
	 *
	 * @param string $role         Role key.
	 * @param string $scenario_key Scenario key.
	 * @return array|null Scenario configuration or null if not found.
	 */
	public static function get_scenario( $role, $scenario_key ) {
		$role_scenarios = self::get_scenarios_for_role( $role );
		return isset( $role_scenarios[ $scenario_key ] ) ? $role_scenarios[ $scenario_key ] : null;
	}

	/**
	 * Get all available roles.
	 *
	 * @return array Array of role keys and labels.
	 */
	public static function get_roles() {
		$scenarios = self::get_scenarios();
		$roles     = array();

		foreach ( $scenarios as $key => $config ) {
			$roles[ $key ] = $config['label'];
		}

		return $roles;
	}

	/**
	 * Get available placeholders for templates.
	 * 
	 * Returns placeholders grouped by category.
	 * These are informational only - the actual replacement logic
	 * will be implemented separately when notifications are sent.
	 *
	 * @return array Placeholders array.
	 */
	public static function get_placeholders() {
		$placeholders = array(
			'common' => array(
				'label'        => __( 'Common Placeholders', 'custom-pwa' ),
				'placeholders' => array(
					'{post_title}'   => __( 'Post title', 'custom-pwa' ),
					'{excerpt}'      => __( 'Post excerpt', 'custom-pwa' ),
					'{permalink}'    => __( 'Post URL', 'custom-pwa' ),
					'{post_type}'    => __( 'Post type name', 'custom-pwa' ),
					'{site_name}'    => __( 'Site name', 'custom-pwa' ),
				),
			),
			'events' => array(
				'label'        => __( 'Event Placeholders', 'custom-pwa' ),
				'placeholders' => array(
					'{event_date}'   => __( 'Event date', 'custom-pwa' ),
					'{venue}'        => __( 'Event venue/location', 'custom-pwa' ),
					'{status_label}' => __( 'Event status label', 'custom-pwa' ),
				),
			),
			'ecommerce' => array(
				'label'        => __( 'E-commerce Placeholders', 'custom-pwa' ),
				'placeholders' => array(
					'{price}'        => __( 'Product price', 'custom-pwa' ),
					'{stock}'        => __( 'Stock quantity', 'custom-pwa' ),
					'{status_label}' => __( 'Stock status label', 'custom-pwa' ),
				),
			),
		);

		/**
		 * Filter available placeholders.
		 *
		 * @since 1.1.0
		 *
		 * @param array $placeholders Placeholders array.
		 */
		return apply_filters( 'custom_pwa_push_placeholders', $placeholders );
	}
}
