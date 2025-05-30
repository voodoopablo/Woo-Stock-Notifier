<?php
if (!defined('ABSPATH')) exit;

function wsn_is_enabled() {
    return get_option('wsn_enabled', 'yes') === 'yes';
}

add_action('woocommerce_single_product_summary', 'wsn_stock_notifier_form', 35);
function wsn_stock_notifier_form() {
    if (!wsn_is_enabled()) return;

    global $product;
    if (!$product->is_in_stock()) {
        echo '<div id="wsn-notifier">
            <p><strong>This product is out of stock. Do you want to be notified when it is available again?</strong></p>
            <form method="post">
                <input type="email" name="wsn_email" placeholder="Your email" required>
                <input type="hidden" name="wsn_product_id" value="' . esc_attr($product->get_id()) . '">
                <button type="submit">Notify me</button>
            </form>
        </div>';
    }
}

add_action('init', 'wsn_handle_form');
function wsn_handle_form() {
    if (!wsn_is_enabled()) return;

    if (isset($_POST['wsn_email']) && isset($_POST['wsn_product_id'])) {
        wsn_process_subscription(sanitize_email($_POST['wsn_email']), intval($_POST['wsn_product_id']));
    }
}
