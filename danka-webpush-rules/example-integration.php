<?php
/**
 * Example Integration for Danka WebPush Rules Plugin
 * 
 * This file shows examples of how to integrate the plugin
 * with your WordPress theme or other plugins.
 * 
 * Add this code to your theme's functions.php or create a custom plugin.
 */

/**
 * Example 1: Add subscribe button to header
 * Add this to your theme's header.php or use a hook
 */
function danka_add_subscribe_button() {
    if (!function_exists('Danka_WebPush_Rules')) {
        return;
    }
    ?>
    <button id="danka-subscribe-btn" class="subscribe-notifications" style="display:none;">
        🔔 <span class="btn-text"><?php _e('Enable Notifications', 'danka-webpush-rules'); ?></span>
    </button>
    
    <script>
    (function() {
        const btn = document.getElementById('danka-subscribe-btn');
        if (!btn) return;
        
        // Show button only if supported
        if ('serviceWorker' in navigator && 'PushManager' in window) {
            btn.style.display = 'inline-block';
            
            // Update button state
            window.dankaWebPushAPI.checkSubscription().then(function(subscription) {
                const textEl = btn.querySelector('.btn-text');
                if (subscription) {
                    textEl.textContent = '<?php _e('Disable Notifications', 'danka-webpush-rules'); ?>';
                    btn.classList.add('subscribed');
                }
            });
            
            // Handle click
            btn.addEventListener('click', function() {
                const textEl = btn.querySelector('.btn-text');
                
                window.dankaWebPushAPI.checkSubscription().then(function(subscription) {
                    if (subscription) {
                        // Unsubscribe
                        window.dankaWebPushAPI.unsubscribe().then(function() {
                            textEl.textContent = '<?php _e('Enable Notifications', 'danka-webpush-rules'); ?>';
                            btn.classList.remove('subscribed');
                        });
                    } else {
                        // Subscribe
                        window.dankaWebPushAPI.subscribe().then(function() {
                            textEl.textContent = '<?php _e('Disable Notifications', 'danka-webpush-rules'); ?>';
                            btn.classList.add('subscribed');
                        });
                    }
                });
            });
        }
    })();
    </script>
    
    <style>
    .subscribe-notifications {
        padding: 8px 16px;
        background: #0073aa;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 14px;
    }
    .subscribe-notifications:hover {
        background: #005177;
    }
    .subscribe-notifications.subscribed {
        background: #46b450;
    }
    </style>
    <?php
}
add_action('wp_footer', 'danka_add_subscribe_button');

/**
 * Example 2: Send notification when new post is published
 * This automatically sends a push notification to all subscribers
 */
function danka_send_notification_on_publish($new_status, $old_status, $post) {
    // Only send on status change from non-publish to publish
    if ($new_status !== 'publish' || $old_status === 'publish') {
        return;
    }
    
    // Check if this post type is enabled for notifications
    $enabled_post_types = get_option('danka_webpush_enabled_post_types', []);
    if (!in_array($post->post_type, $enabled_post_types)) {
        return;
    }
    
    // Get template for this post type
    $templates = get_option('danka_webpush_templates', []);
    if (!isset($templates[$post->post_type])) {
        return;
    }
    
    $template = $templates[$post->post_type];
    
    // Replace placeholders
    $title = danka_replace_placeholders($template['title'], $post);
    $body = danka_replace_placeholders($template['body'], $post);
    $url = danka_replace_placeholders($template['url'], $post);
    
    // Send notification
    danka_send_push_notification([
        'title' => $title,
        'body' => $body,
        'url' => $url,
        'icon' => get_site_icon_url(),
        'badge' => get_site_icon_url(),
    ]);
}
add_action('transition_post_status', 'danka_send_notification_on_publish', 10, 3);

/**
 * Replace template placeholders with actual post data
 */
function danka_replace_placeholders($template, $post) {
    $replacements = [
        '{{post_title}}' => get_the_title($post),
        '{{post_author}}' => get_the_author_meta('display_name', $post->post_author),
        '{{post_excerpt}}' => get_the_excerpt($post),
        '{{post_content}}' => wp_trim_words(get_the_content(null, false, $post), 30),
        '{{post_date}}' => get_the_date('', $post),
        '{{post_url}}' => get_permalink($post),
        '{{site_name}}' => get_bloginfo('name'),
        '{{home_url}}' => home_url(),
    ];
    
    // Add custom field support
    $site_type = get_option('danka_webpush_site_type', 'generic');
    $extra_fields = get_option('danka_webpush_extra_fields', []);
    
    if ($site_type === 'ecommerce') {
        // Add e-commerce specific placeholders
        $price = get_post_meta($post->ID, '_price', true);
        $currency = $extra_fields['currency'] ?? 'USD';
        $replacements['{{product_price}}'] = $price ? $currency . ' ' . $price : '';
    } elseif ($site_type === 'events') {
        // Add events specific placeholders
        $event_date = get_post_meta($post->ID, '_event_date', true);
        $event_location = get_post_meta($post->ID, '_event_location', true);
        $date_format = $extra_fields['date_format'] ?? 'F j, Y';
        
        $replacements['{{event_date}}'] = $event_date ? date($date_format, strtotime($event_date)) : '';
        $replacements['{{event_location}}'] = $event_location ?: '';
    }
    
    return str_replace(array_keys($replacements), array_values($replacements), $template);
}

/**
 * Send push notification to all subscribers
 * 
 * This is a basic example. For production, you should:
 * - Use a queue system for large subscriber lists
 * - Implement proper error handling
 * - Add retry logic
 * - Remove invalid subscriptions
 * 
 * Requires: composer require minishlink/web-push
 */
function danka_send_push_notification($notification_data) {
    // Check if web-push library is available
    if (!class_exists('Minishlink\WebPush\WebPush')) {
        error_log('WebPush library not found. Install with: composer require minishlink/web-push');
        return false;
    }
    
    // Get VAPID keys from options (you should store these in wp-config.php or options)
    $vapid_public = get_option('danka_webpush_vapid_public', '');
    $vapid_private = get_option('danka_webpush_vapid_private', '');
    $vapid_subject = get_option('danka_webpush_vapid_subject', 'mailto:admin@' . parse_url(home_url(), PHP_URL_HOST));
    
    if (empty($vapid_public) || empty($vapid_private)) {
        error_log('VAPID keys not configured. Generate keys with: npx web-push generate-vapid-keys');
        return false;
    }
    
    // Configure WebPush
    $auth = [
        'VAPID' => [
            'subject' => $vapid_subject,
            'publicKey' => $vapid_public,
            'privateKey' => $vapid_private,
        ],
    ];
    
    $webPush = new \Minishlink\WebPush\WebPush($auth);
    
    // Get all subscriptions
    global $wpdb;
    $table_name = $wpdb->prefix . 'danka_webpush_subscriptions';
    $subscriptions = $wpdb->get_results("SELECT * FROM $table_name LIMIT 1000");
    
    if (empty($subscriptions)) {
        return false;
    }
    
    // Prepare payload
    $payload = json_encode($notification_data);
    
    // Queue notifications
    foreach ($subscriptions as $sub) {
        $subscription = \Minishlink\WebPush\Subscription::create([
            'endpoint' => $sub->endpoint,
            'keys' => [
                'p256dh' => $sub->public_key,
                'auth' => $sub->auth_token,
            ],
        ]);
        
        $webPush->queueNotification($subscription, $payload);
    }
    
    // Send all notifications
    $results = $webPush->flush();
    
    // Handle results and remove invalid subscriptions
    foreach ($results as $result) {
        $endpoint = $result->getEndpoint();
        
        if (!$result->isSuccess()) {
            $statusCode = $result->getResponse() ? $result->getResponse()->getStatusCode() : null;
            
            // Remove subscription if endpoint is no longer valid
            if (in_array($statusCode, [404, 410])) {
                $wpdb->delete($table_name, ['endpoint' => $endpoint], ['%s']);
            }
            
            error_log('WebPush error for endpoint ' . $endpoint . ': ' . $result->getReason());
        }
    }
    
    return true;
}

/**
 * Example 3: Add settings page for VAPID keys
 * This adds a submenu under WebPush Rules for VAPID configuration
 */
function danka_add_vapid_settings_page() {
    add_submenu_page(
        'danka-webpush-rules',
        __('VAPID Keys', 'danka-webpush-rules'),
        __('VAPID Keys', 'danka-webpush-rules'),
        'manage_options',
        'danka-webpush-vapid',
        'danka_render_vapid_settings_page'
    );
}
add_action('admin_menu', 'danka_add_vapid_settings_page', 20);

function danka_render_vapid_settings_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // Save settings
    if (isset($_POST['danka_vapid_save']) && check_admin_referer('danka_vapid_settings', 'danka_vapid_nonce')) {
        update_option('danka_webpush_vapid_public', sanitize_text_field($_POST['vapid_public']));
        update_option('danka_webpush_vapid_private', sanitize_text_field($_POST['vapid_private']));
        update_option('danka_webpush_vapid_subject', sanitize_email($_POST['vapid_subject']));
        echo '<div class="notice notice-success"><p>' . __('VAPID keys saved.', 'danka-webpush-rules') . '</p></div>';
    }
    
    $vapid_public = get_option('danka_webpush_vapid_public', '');
    $vapid_private = get_option('danka_webpush_vapid_private', '');
    $vapid_subject = get_option('danka_webpush_vapid_subject', 'mailto:admin@' . parse_url(home_url(), PHP_URL_HOST));
    ?>
    <div class="wrap">
        <h1><?php _e('VAPID Keys Configuration', 'danka-webpush-rules'); ?></h1>
        
        <div class="notice notice-info">
            <p>
                <strong><?php _e('Generate VAPID Keys:', 'danka-webpush-rules'); ?></strong><br>
                <code>npx web-push generate-vapid-keys</code>
            </p>
        </div>
        
        <form method="post">
            <?php wp_nonce_field('danka_vapid_settings', 'danka_vapid_nonce'); ?>
            
            <table class="form-table">
                <tr>
                    <th><label for="vapid_subject"><?php _e('Subject (Email)', 'danka-webpush-rules'); ?></label></th>
                    <td>
                        <input type="email" name="vapid_subject" id="vapid_subject" value="<?php echo esc_attr($vapid_subject); ?>" class="regular-text">
                        <p class="description"><?php _e('Contact email for push service (e.g., mailto:admin@yoursite.com)', 'danka-webpush-rules'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label for="vapid_public"><?php _e('Public Key', 'danka-webpush-rules'); ?></label></th>
                    <td>
                        <textarea name="vapid_public" id="vapid_public" rows="3" class="large-text code"><?php echo esc_textarea($vapid_public); ?></textarea>
                    </td>
                </tr>
                <tr>
                    <th><label for="vapid_private"><?php _e('Private Key', 'danka-webpush-rules'); ?></label></th>
                    <td>
                        <textarea name="vapid_private" id="vapid_private" rows="3" class="large-text code"><?php echo esc_textarea($vapid_private); ?></textarea>
                        <p class="description" style="color: red;"><?php _e('Keep this private! Never commit to version control.', 'danka-webpush-rules'); ?></p>
                    </td>
                </tr>
            </table>
            
            <?php submit_button(__('Save VAPID Keys', 'danka-webpush-rules'), 'primary', 'danka_vapid_save'); ?>
        </form>
    </div>
    <?php
}

/**
 * Example 4: Update VAPID key in frontend JavaScript dynamically
 */
function danka_update_frontend_vapid_key($script_url) {
    if (strpos($script_url, 'frontend.js') === false) {
        return $script_url;
    }
    
    $vapid_public = get_option('danka_webpush_vapid_public', '');
    if (!empty($vapid_public)) {
        wp_add_inline_script('danka-webpush-frontend', 
            'window.dankaWebPushVapidKey = ' . json_encode($vapid_public) . ';',
            'before'
        );
    }
    
    return $script_url;
}
add_filter('script_loader_src', 'danka_update_frontend_vapid_key', 10, 1);

/**
 * Example 5: Add notification preview in admin
 */
function danka_add_test_notification_button() {
    ?>
    <div class="wrap">
        <h2><?php _e('Test Notification', 'danka-webpush-rules'); ?></h2>
        <form method="post">
            <?php wp_nonce_field('danka_test_notification', 'danka_test_nonce'); ?>
            <table class="form-table">
                <tr>
                    <th><label for="test_title"><?php _e('Title', 'danka-webpush-rules'); ?></label></th>
                    <td><input type="text" name="test_title" id="test_title" class="regular-text" value="Test Notification"></td>
                </tr>
                <tr>
                    <th><label for="test_body"><?php _e('Body', 'danka-webpush-rules'); ?></label></th>
                    <td><textarea name="test_body" id="test_body" rows="3" class="regular-text">This is a test notification from Danka WebPush Rules.</textarea></td>
                </tr>
                <tr>
                    <th><label for="test_url"><?php _e('URL', 'danka-webpush-rules'); ?></label></th>
                    <td><input type="url" name="test_url" id="test_url" class="regular-text" value="<?php echo home_url(); ?>"></td>
                </tr>
            </table>
            <?php submit_button(__('Send Test Notification', 'danka-webpush-rules'), 'secondary', 'danka_send_test'); ?>
        </form>
    </div>
    <?php
    
    // Handle test send
    if (isset($_POST['danka_send_test']) && check_admin_referer('danka_test_notification', 'danka_test_nonce')) {
        $result = danka_send_push_notification([
            'title' => sanitize_text_field($_POST['test_title']),
            'body' => sanitize_textarea_field($_POST['test_body']),
            'url' => esc_url_raw($_POST['test_url']),
            'icon' => get_site_icon_url(),
        ]);
        
        if ($result) {
            echo '<div class="notice notice-success"><p>' . __('Test notification sent!', 'danka-webpush-rules') . '</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>' . __('Failed to send test notification. Check error log.', 'danka-webpush-rules') . '</p></div>';
        }
    }
}

// Add test notification section to the main settings page
add_action('danka_webpush_after_settings', 'danka_add_test_notification_button');
