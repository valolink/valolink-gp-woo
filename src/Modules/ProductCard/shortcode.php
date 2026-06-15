<?php
/**
 * [wc_loop_cart] — WooCommerce's ajax loop add-to-cart button for the current product.
 *
 * Place it with a core "Shortcode" block inside the card Element. Safe on non-products
 * (returns ''). Note: the card template runs do_shortcode() on the rendered Element so
 * this resolves even though we bypass the the_content filter.
 */

defined('ABSPATH') || exit;

add_shortcode('wc_loop_cart', function () {
    if (!function_exists('woocommerce_template_loop_add_to_cart')) {
        return '';
    }
    global $product;
    if (!$product instanceof WC_Product) {
        $product = wc_get_product(get_the_ID());
    }
    if (!$product instanceof WC_Product) {
        return '';
    }
    ob_start();
    woocommerce_template_loop_add_to_cart();
    return ob_get_clean();
});
