<?php
/**
 * Plugin Name: WooCommerce Order Test - WP Fix It
 * Plugin URI:  https://www.wpfixit.com
 * Description: A testing WooCommerce payment gateway for WooCommerce to see if your checkout works like it should. This will be for admin users only.
 * Author:      WP Fix It
 * Author URI:  https://www.wpfixit.com
 * Version:     3.4
 * Text Domain: woo-order-test
 */
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
require_once __DIR__ . '/admin/functions.php';
// Enqueue plugin CSS
function wpfi_order_test_css() {
    wp_enqueue_style('wpfi_order_test_css', plugins_url('/admin/assets/wcot.css', __FILE__), array(), '3.0');
}
add_action('admin_enqueue_scripts', 'wpfi_order_test_css');
// Add links to plugin settings and support page
function wpfi_plugin_action_links($links) {
    $settings_link = '<a href="' . esc_url(admin_url('admin.php?page=wc-settings&tab=checkout&section=wpfi_woo_order_test')) . '">' . esc_html__('Settings', 'woo-order-test') . '</a>';
    $support_link  = '<a href="https://www.wpfixit.com/" target="_blank"><b><span class="ticket-link">' . esc_html__('Get Help', 'woo-order-test') . '</span></b></a>';
    array_unshift($links, $settings_link);
    array_unshift($links, $support_link);
    return $links;
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'wpfi_plugin_action_links');
// Activation hook
register_activation_hook(__FILE__, 'wpfi_plugin_activate');
function wpfi_plugin_activate() {
    // Set default options
    update_option('admin_payment_bypass_enabled', 'yes');
}
add_filter('woocommerce_gateway_title', 'wpfi_modify_gateway_title', 10, 2);