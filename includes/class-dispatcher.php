<?php
/**
 * Dispatcher Class
 * 
 * Handles push notification dispatch:
 * - Hooks into post publish events
 * - Renders notification templates with placeholders
 * - Prepares notification payloads
 * - Stub for sending notifications (requires Web Push library)
 *
 * @package Custom_PWA
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Custom_PWA_Dispatcher class.
 * 
 * Manages notification dispatch when posts are published.
 */
class Custom_PWA_Dispatcher {

	/**
	 * Config settings instance.
	 *
	 * @var Custom_PWA_Config_Settings|null
	 */
	private $config = null;

	/**
	 * Push settings instance.
	 *
	 * @var Custom_PWA_Push_Settings|null
	 */
	private $push_settings = null;

	/**
	 * Subscriptions instance.
	 *
	 * @var Custom_PWA_Subscriptions|null
	 */
	private $subscriptions = null;

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'transition_post_status', array( $this, 'handle_post_publish' ), 10, 3 );
	}

	/**
	 * Handle post status transition.
	 * 
	 * Triggers notification when a post is published.
	 *
	 * @param string  $new_status New post status.
	 * @param string  $old_status Old post status.
	 * @param WP_Post $post       Post object.
	 */
	public function handle_post_publish( $new_status, $old_status, $post ) {
		// Only proceed if transitioning to publish.
		if ( 'publish' !== $new_status || 'publish' === $old_status ) {
			return;
		}

		// Check if push is enabled globally.
		$config = $this->get_config();
		if ( ! $config->is_push_enabled() ) {
			return;
		}

		// Check if this post type is enabled.
		$enabled_post_types = $config->get_enabled_post_types();
		if ( ! in_array( $post->post_type, $enabled_post_types, true ) ) {
			return;
		}

		// Check if push is enabled for this specific post type.
		$push_settings = $this->get_push_settings();
		if ( ! $push_settings->is_enabled_for_post_type( $post->post_type ) ) {
			return;
		}

		// Get the rule for this post type.
		$rule = $push_settings->get_rule( $post->post_type );
		if ( ! $rule ) {
			return;
		}

		// Build notification context.
		$context = $this->build_context( $post );

		// Render templates.
		$title = $this->render_template( $rule['title'], $context );
		$body  = $this->render_template( $rule['body'], $context );
		$url   = $this->render_template( $rule['url'], $context );

		// Dispatch notification.
		$this->dispatch_notification( $title, $body, $url, $post );
	}

	/**
	 * Build context array for template rendering.
	 *
	 * @param WP_Post $post Post object.
	 * @return array Context array.
	 */
	private function build_context( $post ) {
		$context = array(
			'post_title' => $post->post_title,
			'permalink'  => get_permalink( $post ),
			'excerpt'    => $this->get_excerpt( $post ),
			'post_type'  => $post->post_type,
		);

		// Add event-specific placeholders if site type is "events".
		$config = $this->get_config();
		if ( 'events' === $config->get_site_type() ) {
			$context['event_date']   = get_post_meta( $post->ID, '_event_date', true ) ?: __( 'TBA', 'custom-pwa' );
			$context['venue']        = get_post_meta( $post->ID, '_venue', true ) ?: __( 'Unknown venue', 'custom-pwa' );
			$context['status_label'] = get_post_meta( $post->ID, '_status_label', true ) ?: __( 'Confirmed', 'custom-pwa' );
		}

		/**
		 * Filter notification context before rendering.
		 *
		 * @since 1.0.0
		 *
		 * @param array   $context Context array.
		 * @param WP_Post $post    Post object.
		 */
		return apply_filters( 'custom_pwa_notification_context', $context, $post );
	}

	/**
	 * Get excerpt for a post.
	 *
	 * @param WP_Post $post Post object.
	 * @return string Excerpt.
	 */
	private function get_excerpt( $post ) {
		if ( ! empty( $post->post_excerpt ) ) {
			return wp_trim_words( $post->post_excerpt, 20 );
		}

		return wp_trim_words( strip_shortcodes( $post->post_content ), 20 );
	}

	/**
	 * Render template with context placeholders.
	 *
	 * @param string $template Template string with placeholders.
	 * @param array  $context  Context array.
	 * @return string Rendered template.
	 */
	private function render_template( $template, $context ) {
		foreach ( $context as $key => $value ) {
			$template = str_replace( '{' . $key . '}', $value, $template );
		}

		return $template;
	}

	/**
	 * Dispatch notification to all active subscriptions.
	 *
	 * @param string  $title Notification title.
	 * @param string  $body  Notification body.
	 * @param string  $url   Notification URL.
	 * @param WP_Post $post  Post object.
	 */
	private function dispatch_notification( $title, $body, $url, $post ) {
		$subscriptions_instance = $this->get_subscriptions();
		$subscriptions = $subscriptions_instance->get_active_subscriptions();

		if ( empty( $subscriptions ) ) {
			$this->log( 'No active subscriptions found.' );
			return;
		}

		// Get icon URL.
		$options = get_option( 'custom_pwa_settings', array() );
		$icon_id = isset( $options['icon_id'] ) ? absint( $options['icon_id'] ) : 0;
		$icon_url = $icon_id ? wp_get_attachment_url( $icon_id ) : get_site_icon_url( 192 );

		foreach ( $subscriptions as $subscription ) {
			// Adapt payload by platform if needed.
			$platform_body = $this->adapt_body_for_platform( $body, $subscription->platform );

			$payload = array(
				'title' => $title,
				'body'  => $platform_body,
				'icon'  => $icon_url,
				'url'   => $url,
				'tag'   => 'post-' . $post->ID,
				'data'  => array(
					'url'     => $url,
					'post_id' => $post->ID,
				),
			);

			/**
			 * Filter push notification payload before sending.
			 *
			 * @since 1.0.0
			 *
			 * @param array  $payload      Notification payload.
			 * @param object $subscription Subscription object.
			 * @param WP_Post $post        Post object.
			 */
			$payload = apply_filters( 'custom_pwa_push_payload', $payload, $subscription, $post );

			// Send notification (stub implementation).
			$this->send_notification( $subscription, $payload );
		}
	}

	/**
	 * Adapt notification body for specific platforms.
	 *
	 * @param string $body     Notification body.
	 * @param string $platform Platform name.
	 * @return string Adapted body.
	 */
	private function adapt_body_for_platform( $body, $platform ) {
		// iOS has stricter limits on notification body length.
		if ( 'ios' === $platform ) {
			return wp_trim_words( $body, 15 );
		}

		return $body;
	}

	/**
	 * Send notification to a subscription endpoint.
	 * 
	 * TODO: Integrate a Web Push library like Minishlink/web-push.
	 * This is a stub implementation that logs the notification instead of sending.
	 *
	 * @param object $subscription Subscription object.
	 * @param array  $payload      Notification payload.
	 */
	private function send_notification( $subscription, $payload ) {
		// Get VAPID keys from options.
		$push_config = get_option( 'custom_pwa_push', array() );
		$vapid_public_key  = ! empty( $push_config['public_key'] ) ? $push_config['public_key'] : '';
		$vapid_private_key = ! empty( $push_config['private_key'] ) ? $push_config['private_key'] : '';

		if ( empty( $vapid_public_key ) || empty( $vapid_private_key ) ) {
			$this->log( 'VAPID keys not configured. Cannot send notification.' );
			return;
		}

		// Prepare the notification data.
		$notification_data = wp_json_encode( $payload );

		// Send the push notification using Web Push protocol.
		$result = $this->send_web_push(
			$subscription->endpoint,
			$notification_data,
			$subscription->p256dh,
			$subscription->auth,
			$vapid_public_key,
			$vapid_private_key
		);

		if ( $result ) {
			$this->log( sprintf(
				'Notification sent successfully to: %s',
				substr( $subscription->endpoint, 0, 50 ) . '...'
			) );
		} else {
			$this->log( sprintf(
				'Failed to send notification to: %s',
				substr( $subscription->endpoint, 0, 50 ) . '...'
			) );
		}
	}

	/**
	 * Send Web Push notification using cURL.
	 *
	 * @param string $endpoint Push endpoint URL.
	 * @param string $payload  Notification payload (JSON string).
	 * @param string $p256dh   User public key (base64url).
	 * @param string $auth     User auth secret (base64url).
	 * @param string $vapid_public_key  VAPID public key (base64url).
	 * @param string $vapid_private_key VAPID private key (base64url PEM).
	 * @return bool True on success, false on failure.
	 */
	private function send_web_push( $endpoint, $payload, $p256dh, $auth, $vapid_public_key, $vapid_private_key ) {
		// For now, use a simplified approach without full encryption.
		// In production, you should use a proper Web Push library like Minishlink/web-push.
		// This is a basic implementation that sends unencrypted notifications.
		
		// Parse the endpoint to get the audience (origin).
		$url_parts = wp_parse_url( $endpoint );
		$audience  = sprintf( '%s://%s', $url_parts['scheme'], $url_parts['host'] );

		// Create JWT token for VAPID authentication.
		$jwt = $this->create_vapid_jwt( $audience, $vapid_private_key );
		if ( ! $jwt ) {
			return false;
		}

		// Send the notification using cURL.
		$ch = curl_init( $endpoint );
		curl_setopt( $ch, CURLOPT_POST, true );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $payload );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
			'Content-Type: application/json',
			'Content-Length: ' . strlen( $payload ),
			'TTL: 86400', // 24 hours.
			'Authorization: vapid t=' . $jwt . ', k=' . $vapid_public_key,
		) );

		$response = curl_exec( $ch );
		$http_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
		curl_close( $ch );

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
			$this->log( 'Failed to load VAPID private key for JWT signing.' );
			return false;
		}

		$signature = '';
		$success   = openssl_sign( $signature_input, $signature, $private_key, OPENSSL_ALGO_SHA256 );
		openssl_free_key( $private_key );

		if ( ! $success ) {
			$this->log( 'Failed to sign JWT with VAPID private key.' );
			return false;
		}

		// Convert DER signature to raw signature (required for JWT ES256).
		$signature_raw = $this->der_to_raw_signature( $signature );
		if ( ! $signature_raw ) {
			$this->log( 'Failed to convert DER signature to raw format.' );
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
		// DER signature format: 0x30 [total-length] 0x02 [r-length] [r-bytes] 0x02 [s-length] [s-bytes]
		// We need to extract R and S as 32-byte values each for P-256.
		
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

		// Remove leading zero byte if present (for positive integers).
		if ( strlen( $r ) === 33 && ord( $r[0] ) === 0x00 ) {
			$r = substr( $r, 1 );
		}

		// Pad to 32 bytes if needed.
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

		// Pad to 32 bytes if needed.
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
	 * Log a message if debug mode is enabled.
	 *
	 * @param string $message Log message.
	 */
	private function log( $message ) {
		$config = $this->get_config();
		if ( $config->is_debug_mode() ) {
			error_log( '[Custom PWA Dispatcher] ' . $message );
		}
	}

	/**
	 * Get config settings instance.
	 *
	 * @return Custom_PWA_Config_Settings
	 */
	private function get_config() {
		if ( null === $this->config ) {
			$this->config = custom_pwa()->get_config_settings();
		}
		return $this->config;
	}

	/**
	 * Get push settings instance.
	 *
	 * @return Custom_PWA_Push_Settings
	 */
	private function get_push_settings() {
		if ( null === $this->push_settings ) {
			$this->push_settings = custom_pwa()->get_push_settings();
		}
		return $this->push_settings;
	}

	/**
	 * Get subscriptions instance.
	 *
	 * @return Custom_PWA_Subscriptions
	 */
	private function get_subscriptions() {
		if ( null === $this->subscriptions ) {
			$this->subscriptions = custom_pwa()->get_subscriptions();
		}
		return $this->subscriptions;
	}
}
