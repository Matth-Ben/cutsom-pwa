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
		// TODO: Replace this stub with actual Web Push sending logic.
		// 
		// Example using Minishlink/web-push (requires composer installation):
		// 
		// use Minishlink\WebPush\WebPush;
		// use Minishlink\WebPush\Subscription as WebPushSubscription;
		// 
		// $auth = [
		//     'VAPID' => [
		//         'subject' => get_bloginfo('url'),
		//         'publicKey' => VAPID_PUBLIC_KEY,
		//         'privateKey' => VAPID_PRIVATE_KEY,
		//     ]
		// ];
		// 
		// $webPush = new WebPush($auth);
		// 
		// $pushSubscription = WebPushSubscription::create([
		//     'endpoint' => $subscription->endpoint,
		//     'keys' => [
		//         'p256dh' => $subscription->p256dh,
		//         'auth' => $subscription->auth,
		//     ],
		// ]);
		// 
		// $webPush->sendOneNotification(
		//     $pushSubscription,
		//     json_encode($payload)
		// );

		$this->log( sprintf(
			'[STUB] Sending notification to endpoint: %s | Payload: %s',
			substr( $subscription->endpoint, 0, 50 ) . '...',
			wp_json_encode( $payload )
		) );
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
