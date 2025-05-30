<?php
if (!defined('ABSPATH')) exit;

function wsn_process_subscription($email, $product_id) {
    if (!is_email($email)) return;

    $existing = get_post_meta($product_id, '_wsn_emails', true);
    $emails = $existing ? $existing : [];
    $timestamps = get_post_meta($product_id, '_wsn_timestamps', true);
    $timestamps = is_array($timestamps) ? $timestamps : [];

    if (!in_array($email, $emails)) {
        $emails[] = $email;
        $timestamps[$email] = current_time('mysql');
        update_post_meta($product_id, '_wsn_emails', $emails);
        update_post_meta($product_id, '_wsn_timestamps', $timestamps);

        wp_mail(
            $email,
            'Subscription confirmation',
            'Hello, we will notify you when the product "' . get_the_title($product_id) . '" is back in stock.',
            ['Content-Type: text/html; charset=UTF-8']
        );
    }

    add_action('wp_footer', function() {
        echo "<script>alert('We will notify you when it is available!');</script>";
    });
}

add_action('woocommerce_product_set_stock_status', 'wsn_check_stock_change', 10, 3);
function wsn_check_stock_change($product_id, $stock_status, $product) {
    if (!wsn_is_enabled()) return;

    if ($stock_status === 'instock') {
        $emails = get_post_meta($product_id, '_wsn_emails', true);
        $timestamps = get_post_meta($product_id, '_wsn_timestamps', true);
        $sent = get_post_meta($product_id, '_wsn_sent', true);
        $sent = is_array($sent) ? $sent : [];

        if ($emails && is_array($emails)) {
            foreach ($emails as $email) {
                wp_mail(
                    $email,
                    'Product available again!',
                    'Hello, the product "' . get_the_title($product_id) . '" is now available in our store.<br><br><a href="' . get_permalink($product_id) . '">View product</a>',
                    ['Content-Type: text/html; charset=UTF-8']
                );
                $sent[$email] = current_time('mysql');
            }
            // No borramos los emails ni los timestamps, solo marcamos como enviados
            update_post_meta($product_id, '_wsn_sent', $sent);
        }
    }
}
