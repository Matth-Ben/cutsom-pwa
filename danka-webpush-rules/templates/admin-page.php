<?php
if (!defined('ABSPATH')) {
    exit;
}

$site_type = get_option('danka_webpush_site_type', 'generic');
$enabled_post_types = get_option('danka_webpush_enabled_post_types', []);
$templates = get_option('danka_webpush_templates', []);
$extra_fields = get_option('danka_webpush_extra_fields', []);

// Get all post types (including custom)
$post_types = get_post_types(['public' => true], 'objects');
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <form method="post" action="">
        <?php wp_nonce_field('danka_webpush_settings', 'danka_webpush_nonce'); ?>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="site_type"><?php _e('Site Type', 'danka-webpush-rules'); ?></label>
                </th>
                <td>
                    <select name="site_type" id="site_type" class="regular-text">
                        <option value="generic" <?php selected($site_type, 'generic'); ?>><?php _e('Generic', 'danka-webpush-rules'); ?></option>
                        <option value="ecommerce" <?php selected($site_type, 'ecommerce'); ?>><?php _e('E-commerce', 'danka-webpush-rules'); ?></option>
                        <option value="events" <?php selected($site_type, 'events'); ?>><?php _e('Events', 'danka-webpush-rules'); ?></option>
                    </select>
                    <p class="description"><?php _e('Select your site type to enable specific fields.', 'danka-webpush-rules'); ?></p>
                </td>
            </tr>
        </table>
        
        <h2><?php _e('Post Type Settings', 'danka-webpush-rules'); ?></h2>
        <p><?php _e('Enable notifications and configure templates for each post type.', 'danka-webpush-rules'); ?></p>
        
        <div id="post-types-settings">
            <?php foreach ($post_types as $post_type_key => $post_type_obj): ?>
                <?php 
                $is_enabled = in_array($post_type_key, $enabled_post_types);
                $template = isset($templates[$post_type_key]) ? $templates[$post_type_key] : ['title' => '', 'body' => '', 'url' => ''];
                ?>
                <div class="postbox post-type-box" data-post-type="<?php echo esc_attr($post_type_key); ?>">
                    <div class="postbox-header">
                        <h2 class="hndle">
                            <label>
                                <input type="checkbox" name="enabled_post_types[]" value="<?php echo esc_attr($post_type_key); ?>" <?php checked($is_enabled); ?> class="post-type-toggle">
                                <?php echo esc_html($post_type_obj->labels->name); ?> (<?php echo esc_html($post_type_key); ?>)
                            </label>
                        </h2>
                    </div>
                    <div class="inside" style="<?php echo $is_enabled ? '' : 'display:none;'; ?>">
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="template_title_<?php echo esc_attr($post_type_key); ?>">
                                        <?php _e('Notification Title Template', 'danka-webpush-rules'); ?>
                                    </label>
                                </th>
                                <td>
                                    <input type="text" 
                                           name="templates[<?php echo esc_attr($post_type_key); ?>][title]" 
                                           id="template_title_<?php echo esc_attr($post_type_key); ?>"
                                           value="<?php echo esc_attr($template['title']); ?>" 
                                           class="large-text"
                                           placeholder="e.g., {{post_title}}">
                                    <p class="description">
                                        <?php _e('Available placeholders: {{post_title}}, {{post_author}}, {{site_name}}', 'danka-webpush-rules'); ?>
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="template_body_<?php echo esc_attr($post_type_key); ?>">
                                        <?php _e('Notification Body Template', 'danka-webpush-rules'); ?>
                                    </label>
                                </th>
                                <td>
                                    <textarea name="templates[<?php echo esc_attr($post_type_key); ?>][body]" 
                                              id="template_body_<?php echo esc_attr($post_type_key); ?>"
                                              rows="3" 
                                              class="large-text"
                                              placeholder="e.g., {{post_excerpt}}"><?php echo esc_textarea($template['body']); ?></textarea>
                                    <p class="description">
                                        <?php _e('Available placeholders: {{post_excerpt}}, {{post_content}}, {{post_date}}', 'danka-webpush-rules'); ?>
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="template_url_<?php echo esc_attr($post_type_key); ?>">
                                        <?php _e('Notification URL Template', 'danka-webpush-rules'); ?>
                                    </label>
                                </th>
                                <td>
                                    <input type="text" 
                                           name="templates[<?php echo esc_attr($post_type_key); ?>][url]" 
                                           id="template_url_<?php echo esc_attr($post_type_key); ?>"
                                           value="<?php echo esc_attr($template['url']); ?>" 
                                           class="large-text"
                                           placeholder="e.g., {{post_url}}">
                                    <p class="description">
                                        <?php _e('Available placeholders: {{post_url}}, {{home_url}}', 'danka-webpush-rules'); ?>
                                    </p>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div id="extra-fields-section" style="<?php echo $site_type !== 'generic' ? '' : 'display:none;'; ?>">
            <h2><?php _e('Extra Fields', 'danka-webpush-rules'); ?></h2>
            
            <div id="ecommerce-fields" style="<?php echo $site_type === 'ecommerce' ? '' : 'display:none;'; ?>">
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="currency"><?php _e('Currency', 'danka-webpush-rules'); ?></label>
                        </th>
                        <td>
                            <input type="text" 
                                   name="extra_fields[currency]" 
                                   id="currency"
                                   value="<?php echo esc_attr($extra_fields['currency'] ?? 'USD'); ?>" 
                                   class="regular-text">
                            <p class="description"><?php _e('Default currency for e-commerce notifications.', 'danka-webpush-rules'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="show_price"><?php _e('Show Price in Notifications', 'danka-webpush-rules'); ?></label>
                        </th>
                        <td>
                            <input type="checkbox" 
                                   name="extra_fields[show_price]" 
                                   id="show_price"
                                   value="1" 
                                   <?php checked(($extra_fields['show_price'] ?? '0'), '1'); ?>>
                            <label for="show_price"><?php _e('Include product price in notification body', 'danka-webpush-rules'); ?></label>
                        </td>
                    </tr>
                </table>
            </div>
            
            <div id="events-fields" style="<?php echo $site_type === 'events' ? '' : 'display:none;'; ?>">
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="date_format"><?php _e('Date Format', 'danka-webpush-rules'); ?></label>
                        </th>
                        <td>
                            <input type="text" 
                                   name="extra_fields[date_format]" 
                                   id="date_format"
                                   value="<?php echo esc_attr($extra_fields['date_format'] ?? 'F j, Y'); ?>" 
                                   class="regular-text">
                            <p class="description"><?php _e('PHP date format for event dates (e.g., F j, Y for "January 15, 2024").', 'danka-webpush-rules'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="show_location"><?php _e('Show Location', 'danka-webpush-rules'); ?></label>
                        </th>
                        <td>
                            <input type="checkbox" 
                                   name="extra_fields[show_location]" 
                                   id="show_location"
                                   value="1" 
                                   <?php checked(($extra_fields['show_location'] ?? '0'), '1'); ?>>
                            <label for="show_location"><?php _e('Include event location in notifications', 'danka-webpush-rules'); ?></label>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        
        <?php submit_button(__('Save Settings', 'danka-webpush-rules'), 'primary', 'danka_webpush_save'); ?>
    </form>
    
    <hr>
    
    <h2><?php _e('Subscription Statistics', 'danka-webpush-rules'); ?></h2>
    <?php
    global $wpdb;
    $table_name = $wpdb->prefix . 'danka_webpush_subscriptions';
    $total_subscriptions = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table_name}"));
    ?>
    <p><?php printf(__('Total active subscriptions: %d', 'danka-webpush-rules'), intval($total_subscriptions)); ?></p>
</div>
