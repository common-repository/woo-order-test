<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
// Register the WooCommerce Order Test Gateway
function wpfi_add_woo_order_test_gateway($methods) {
    $methods[] = 'WC_Woo_Order_Test_Gateway';
    return $methods;
}
add_filter('woocommerce_payment_gateways', 'wpfi_add_woo_order_test_gateway');
// Define the WooCommerce Order Test Gateway class
add_action('plugins_loaded', 'wpfi_init_woo_order_test_gateway_class');
function wpfi_init_woo_order_test_gateway_class() {
    class WC_Woo_Order_Test_Gateway extends WC_Payment_Gateway {
    
        public $custom_message; // Declare the property to avoid the deprecation notice
        public function __construct() {
            $this->id                 = 'wpfi_woo_order_test';
            $this->has_fields         = false;
            $this->method_title       = esc_html__('WooCommerce Order Test', 'woo-order-test');
            if (isset($_GET['section']) && $_GET['section'] === 'wpfi_woo_order_test') {
            $this->method_description = wp_kses_post(
    __('A test gateway for admins to bypass payment methods. Created and managed by <a href="https://www.wpfixit.com" target="_blank"><strong>WP Fix It - WordPress Experts</strong></a>. <a href="https://www.wpfixit.com" target="_blank" style="float: right; margin-left: 10px;"><img src="' . esc_url(plugins_url('/assets/desktop.webp', __FILE__)) . '" alt="WP Fix It" style="max-width: 150px;float: right;"></a>', 
        'woo-order-test'
    )
);
} else {
    // Default description for general WooCommerce Checkout page
    $this->method_description = wp_kses_post(
        __(
            'A test gateway for admins to bypass payment methods.',
            'woo-order-test'
        )
    );
}
            // Load the settings
            $this->init_form_fields();
            $this->init_settings();
            // Get setting values
            $this->enabled            = $this->get_option('enabled');
            $this->custom_message     = $this->get_option('custom_message', esc_html__('Payment gateways are disabled for testing purposes.', 'woocommerce-order-test-wp-fix-it'));
            // Save admin options
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
            // Frontend message handling
            add_action('wp_body_open', array($this, 'display_custom_above_header_notice'));
        }
        // Initialize gateway settings form fields
        public function init_form_fields() {
            $this->form_fields = array(
                'enabled' => array(
                    'title'   => esc_html__('Enable/Disable', 'woo-order-test'),
                    'type'    => 'checkbox',
                    'label'   => esc_html__('Enable WooCommerce Order Test Gateway', 'woo-order-test'),
                    'default' => 'no',
                ),
                'custom_message' => array(
                    'title'       => esc_html__('Custom Testing Message', 'woo-order-test'),
                    'type'        => 'textarea',
                    'description' => esc_html__('This message will be displayed during the checkout for admin users.', 'woo-order-test'),
                    'default'     => esc_html__('Payment gateways are disabled for testing purposes.', 'woocommerce-order-test-wp-fix-it'),
                    'desc_tip'    => true,
                    'css'         => 'width: 100%; max-width: 600px;',
                ),
            );
        }
        // Process payment for admin users (bypass payment step)
        public function process_payment($order_id) {
            $order = wc_get_order($order_id);
            if (current_user_can('administrator')) {
                // Mark order as completed
                $order->payment_complete();
                $order->add_order_note(esc_html__('Order test completed by admin user.', 'woo-order-test'));
                // Return success and redirect
                return array(
                    'result'   => 'success',
                    'redirect' => $this->get_return_url($order),
                );
            } else {
                wc_add_notice(esc_html__('This payment method is only available for admin users.', 'woo-order-test'), 'error');
                return;
            }
        }
        // Display a custom message on the checkout page
        public function display_custom_above_header_notice() {
            if (is_checkout() && $this->enabled === 'yes' && wpfi_is_payment_bypass_enabled_for_admin()) {
                if (!empty($this->custom_message)) {
                    echo '<div class="woocommerce-notices-wrapper" style="position: fixed; top: 0; left: 0; width: 100%; z-index: 9999;">';
echo '<div style="background-color: #efe; border-bottom: 1px solid #ccc; padding: 30px 0px 0px 0px; margin-bottom: 0; display: flex; justify-content: space-between; align-items: center;">';
// Left-hand side image
echo '<div style="flex: 0 0 auto;">';
echo '<img src="' . esc_url(plugins_url('/assets/woo-circel.png', __FILE__)) . '" alt="WooCommerce" style="max-width: 40px;padding: 10px 0px 0px 10px;">';
echo '</div>';
// Center content (message and button)
echo '<div style="flex-grow: 1; text-align: center;">';
echo '<p>' . esc_html($this->custom_message) . '</p>';
echo '</div>';
// Right-hand side image
echo '<div style="flex: 0 0 auto;">';
echo '<a href="' . esc_url(admin_url('admin.php?page=wc-settings&tab=checkout&section=wpfi_woo_order_test')) . '" class="woo_order_test_button">SETTINGS</a>';
echo '</div>';
echo '</div>';
echo '</div>';
// Add additional space to ensure the content is pushed down to avoid being overlapped by the fixed element
echo '<style>.woo_order_test_button {
    background-color: #f99568;
    color: #fff;
    padding: 10px 20px;
    text-decoration: none;
    border-radius: 5px;
    font-size: 14px;
    font-weight: bold;
    margin-right:23px;
}
.woo_order_test_button:hover {
    background-color: #00D78B;
}</style>';
                }
            }
        }
    }
}
// Check if payment bypass is enabled for admin users
function wpfi_is_payment_bypass_enabled_for_admin() {
    return current_user_can('administrator') && 'yes' === get_option('admin_payment_bypass_enabled', 'no');
}
// Automatically complete orders for admin users
function wpfi_admin_auto_complete_order($order_id) {
    if (!$order_id) return;
    if (current_user_can('administrator')) {
        $order = wc_get_order($order_id);
        if ($order) {
            $order->update_status('completed');
        }
    }
}
add_action('woocommerce_thankyou', 'wpfi_admin_auto_complete_order');
// Check if the test gateway is enabled
function wpfi_is_test_gateway_enabled() {
    $gateway_settings = get_option('woocommerce_wpfi_woo_order_test_settings');
    return isset($gateway_settings['enabled']) && $gateway_settings['enabled'] === 'yes';
}
// Disable payment gateway requirement for admin users if test gateway is enabled
function wpfi_admin_cart_needs_payment($needs_payment) {
    if (current_user_can('administrator') && wpfi_is_test_gateway_enabled() && wpfi_is_payment_bypass_enabled_for_admin()) {
        return false; // Disable the need for payment
    }
    return $needs_payment;
}
add_filter('woocommerce_cart_needs_payment', 'wpfi_admin_cart_needs_payment');
// Hide payment methods on checkout for admin users if test gateway is enabled
function wpfi_admin_disable_payment_gateways($available_gateways) {
    if (is_checkout() && wpfi_is_test_gateway_enabled() && wpfi_is_payment_bypass_enabled_for_admin()) {
        return array(); // Disable all gateways for admin users
    }
    return $available_gateways;
}
add_filter('woocommerce_available_payment_gateways', 'wpfi_admin_disable_payment_gateways');
// Prevent validation error for no payment method for admin users
function wpfi_admin_skip_payment_method_validation($data, $errors) {
    if (wpfi_is_payment_bypass_enabled_for_admin()) {
        $errors->remove('no_payment_method');
    }
}
add_action('woocommerce_after_checkout_validation', 'wpfi_admin_skip_payment_method_validation', 10, 2);
// Skip payment step in order creation for admin users
function wpfi_admin_order_needs_payment($needs_payment, $order) {
    if (wpfi_is_payment_bypass_enabled_for_admin()) {
        return false;
    }
    return $needs_payment;
}
add_filter('woocommerce_order_needs_payment', 'wpfi_admin_order_needs_payment', 10, 2);
function wpfi_modify_gateway_title($title, $gateway_id) {
    if ($gateway_id === 'wpfi_woo_order_test') {
        // Return just the title without the "&#8211;"
        return esc_html__('WooCommerce Order Test', 'woo-order-test');
    }
    return $title;
}
// Disable admin email when a payment gateway is activated
add_filter('wp_mail', 'wpfi_disable_gateway_activation_email', 10, 1);
function wpfi_disable_gateway_activation_email($args) {
    // Check if the email subject contains specific text related to payment gateway activation
    if (isset($args['subject']) && strpos($args['subject'], 'Payment gateway') !== false && wpfi_is_test_gateway_enabled()) {
        // Prevent the email from being sent
        $args['to'] = [];
    }
    return $args;
}
