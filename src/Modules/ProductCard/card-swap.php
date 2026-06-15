<?php
/**
 * Swap WooCommerce's content-product.php for the Element-backed card,
 * but only when a card Element is configured.
 */

defined('ABSPATH') || exit;

add_filter('wc_get_template_part', function ($template, $slug, $name) {
    if ('content' !== $slug || 'product' !== $name) {
        return $template;
    }
    if (!gpwc_active_card_element_id()) {
        return $template;
    }
    return __DIR__ . '/templates/content-product.php';
}, 10, 3);
