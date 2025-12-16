<?php
/**
 * Subscriptions Class
 * 
 * Handles Web Push subscriptions:
 * - Database table creation and management
 * - REST API endpoints for subscribe/unsubscribe
 * - Subscription CRUD operations
 *
 * @package Custom_PWA
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Custom_PWA_Subscriptions class.
 * 
 * Manages Web Push subscriptions storage and REST API.
 */
class Custom_PWA_Subscriptions {

	/**
	 * Database version for tracking schema changes.
	 *
	 * @var string
	 */
	const DB_VERSION = '1.0';

	/**
	 * VAPID public key (placeholder - should be generated and stored securely).
	 * 
	 * In production, generate real VAPID keys and store them securely.
	 * Example: openssl ecparam -name prime256v1 -genkey -noout -out vapid_private.pem
	 *
	 * @var string
	 */
	const VAPID_PUBLIC_KEY = 'BEl62iUYgUivxIkv69yViEuiBIa-Ib9-SkvMeAtA3LFgDzkrxZJjSgSnfckjBJuBkr3qBUYIHBQFLXYp5Nksh8U';

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
	}

	/**
	 * Create subscriptions table.
	 * 
	 * Called on plugin activation.
	 */
	public static function create_table() {
		global $wpdb;

		$table_name      = $wpdb->prefix . 'custom_pwa_subscriptions';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table_name} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			blog_id bigint(20) unsigned NOT NULL DEFAULT 1,
			endpoint text NOT NULL,
			p256dh text NOT NULL,
			auth text NOT NULL,
			lang varchar(10) DEFAULT 'en',
			platform varchar(20) DEFAULT 'other',
			user_agent varchar(255) DEFAULT '',
			active tinyint(1) NOT NULL DEFAULT 1,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY blog_id (blog_id),
			KEY active (active),
			KEY endpoint_hash (endpoint(100))
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		// Store DB version.
		update_option( 'custom_pwa_db_version', self::DB_VERSION );
	}

	/**
	 * Register REST API routes.
	 */
	public function register_rest_routes() {
		// Public key endpoint.
		register_rest_route(
			'custom-pwa/v1',
			'/public-key',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_public_key' ),
				'permission_callback' => '__return_true',
			)
		);

		// Subscribe endpoint.
		register_rest_route(
			'custom-pwa/v1',
			'/subscribe',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'handle_subscribe' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'endpoint' => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'esc_url_raw',
					),
					'keys' => array(
						'required' => true,
						'type'     => 'object',
					),
					'lang' => array(
						'type'              => 'string',
						'default'           => 'en',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'platform' => array(
						'type'              => 'string',
						'default'           => 'other',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'userAgent' => array(
						'type'              => 'string',
						'default'           => '',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);

		// Unsubscribe endpoint.
		register_rest_route(
			'custom-pwa/v1',
			'/unsubscribe',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'handle_unsubscribe' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'endpoint' => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'esc_url_raw',
					),
				),
			)
		);
	}

	/**
	 * Get VAPID public key.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response.
	 */
	public function get_public_key( $request ) {
		return new WP_REST_Response(
			array(
				'publicKey' => self::VAPID_PUBLIC_KEY,
			),
			200
		);
	}

	/**
	 * Handle subscribe request.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response.
	 */
	public function handle_subscribe( $request ) {
		global $wpdb;

		$endpoint   = $request->get_param( 'endpoint' );
		$keys       = $request->get_param( 'keys' );
		$lang       = $request->get_param( 'lang' );
		$platform   = $request->get_param( 'platform' );
		$user_agent = $request->get_param( 'userAgent' );

		// Validate keys.
		if ( empty( $keys['p256dh'] ) || empty( $keys['auth'] ) ) {
			return new WP_Error(
				'invalid_keys',
				__( 'Invalid subscription keys.', 'custom-pwa' ),
				array( 'status' => 400 )
			);
		}

		// Validate platform.
		$valid_platforms = array( 'android', 'ios', 'mac', 'windows', 'other' );
		if ( ! in_array( $platform, $valid_platforms, true ) ) {
			$platform = 'other';
		}

		$table_name = $wpdb->prefix . 'custom_pwa_subscriptions';
		$blog_id    = get_current_blog_id();

		// Check if subscription already exists.
		$existing = $wpdb->get_row( $wpdb->prepare(
			"SELECT id FROM {$table_name} WHERE endpoint = %s AND blog_id = %d",
			$endpoint,
			$blog_id
		) );

		if ( $existing ) {
			// Update existing subscription.
			$updated = $wpdb->update(
				$table_name,
				array(
					'p256dh'     => sanitize_text_field( $keys['p256dh'] ),
					'auth'       => sanitize_text_field( $keys['auth'] ),
					'lang'       => $lang,
					'platform'   => $platform,
					'user_agent' => $user_agent,
					'active'     => 1,
				),
				array(
					'id' => $existing->id,
				),
				array( '%s', '%s', '%s', '%s', '%s', '%d' ),
				array( '%d' )
			);

			if ( false === $updated ) {
				return new WP_Error(
					'update_failed',
					__( 'Failed to update subscription.', 'custom-pwa' ),
					array( 'status' => 500 )
				);
			}

			return new WP_REST_Response(
				array(
					'success' => true,
					'message' => __( 'Subscription updated successfully.', 'custom-pwa' ),
				),
				200
			);
		}

		// Insert new subscription.
		$inserted = $wpdb->insert(
			$table_name,
			array(
				'blog_id'    => $blog_id,
				'endpoint'   => $endpoint,
				'p256dh'     => sanitize_text_field( $keys['p256dh'] ),
				'auth'       => sanitize_text_field( $keys['auth'] ),
				'lang'       => $lang,
				'platform'   => $platform,
				'user_agent' => $user_agent,
				'active'     => 1,
			),
			array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%d' )
		);

		if ( false === $inserted ) {
			return new WP_Error(
				'insert_failed',
				__( 'Failed to create subscription.', 'custom-pwa' ),
				array( 'status' => 500 )
			);
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'message' => __( 'Subscription created successfully.', 'custom-pwa' ),
			),
			201
		);
	}

	/**
	 * Handle unsubscribe request.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response.
	 */
	public function handle_unsubscribe( $request ) {
		global $wpdb;

		$endpoint = $request->get_param( 'endpoint' );

		$table_name = $wpdb->prefix . 'custom_pwa_subscriptions';
		$blog_id    = get_current_blog_id();

		// Mark subscription as inactive.
		$updated = $wpdb->update(
			$table_name,
			array( 'active' => 0 ),
			array(
				'endpoint' => $endpoint,
				'blog_id'  => $blog_id,
			),
			array( '%d' ),
			array( '%s', '%d' )
		);

		if ( false === $updated ) {
			return new WP_Error(
				'unsubscribe_failed',
				__( 'Failed to unsubscribe.', 'custom-pwa' ),
				array( 'status' => 500 )
			);
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'message' => __( 'Unsubscribed successfully.', 'custom-pwa' ),
			),
			200
		);
	}

	/**
	 * Get all active subscriptions.
	 *
	 * @param int|null $blog_id Blog ID (null for current blog).
	 * @return array Array of subscription objects.
	 */
	public function get_active_subscriptions( $blog_id = null ) {
		global $wpdb;

		if ( null === $blog_id ) {
			$blog_id = get_current_blog_id();
		}

		$table_name = $wpdb->prefix . 'custom_pwa_subscriptions';

		$subscriptions = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$table_name} WHERE active = %d AND blog_id = %d",
			1,
			$blog_id
		) );

		return $subscriptions ? $subscriptions : array();
	}

	/**
	 * Get subscriptions filtered by platform.
	 *
	 * @param string   $platform Platform name.
	 * @param int|null $blog_id  Blog ID (null for current blog).
	 * @return array Array of subscription objects.
	 */
	public function get_subscriptions_by_platform( $platform, $blog_id = null ) {
		global $wpdb;

		if ( null === $blog_id ) {
			$blog_id = get_current_blog_id();
		}

		$table_name = $wpdb->prefix . 'custom_pwa_subscriptions';

		$subscriptions = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$table_name} WHERE active = %d AND blog_id = %d AND platform = %s",
			1,
			$blog_id,
			$platform
		) );

		return $subscriptions ? $subscriptions : array();
	}

	/**
	 * Delete a subscription by endpoint.
	 *
	 * @param string   $endpoint Subscription endpoint.
	 * @param int|null $blog_id  Blog ID (null for current blog).
	 * @return bool Success status.
	 */
	public function delete_subscription( $endpoint, $blog_id = null ) {
		global $wpdb;

		if ( null === $blog_id ) {
			$blog_id = get_current_blog_id();
		}

		$table_name = $wpdb->prefix . 'custom_pwa_subscriptions';

		$deleted = $wpdb->delete(
			$table_name,
			array(
				'endpoint' => $endpoint,
				'blog_id'  => $blog_id,
			),
			array( '%s', '%d' )
		);

		return false !== $deleted;
	}

	/**
	 * Get subscription count.
	 *
	 * @param int|null $blog_id Blog ID (null for current blog).
	 * @return int Subscription count.
	 */
	public function get_subscription_count( $blog_id = null ) {
		global $wpdb;

		if ( null === $blog_id ) {
			$blog_id = get_current_blog_id();
		}

		$table_name = $wpdb->prefix . 'custom_pwa_subscriptions';

		$count = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$table_name} WHERE active = %d AND blog_id = %d",
			1,
			$blog_id
		) );

		return absint( $count );
	}
}
