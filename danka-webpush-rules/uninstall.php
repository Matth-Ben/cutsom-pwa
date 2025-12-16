<?php
/**
 * Uninstall script for Danka WebPush Rules
 * Runs when plugin is deleted
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

// Delete custom database table
$table_name = $wpdb->prefix . 'danka_webpush_subscriptions';
$wpdb->query("DROP TABLE IF EXISTS $table_name");

// Delete plugin options
delete_option('danka_webpush_site_type');
delete_option('danka_webpush_enabled_post_types');
delete_option('danka_webpush_templates');
delete_option('danka_webpush_extra_fields');

// Clean up any transients
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_danka_webpush_%'");
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_danka_webpush_%'");
