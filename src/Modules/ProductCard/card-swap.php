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

/**
 * Feed the active card Element's block markup into GenerateBlocks' CSS scan, so its block
 * CSS is compiled into wp_head — exactly as GeneratePress Premium does for its own block
 * Elements (gp-premium/elements/class-block.php). We render the Element ourselves inside
 * the product loop (after wp_head), where GB would otherwise fall back to injecting each
 * block's <style> inline on first render. That render-time path breaks under GB Pro block
 * conditions: if the first product hides a block (e.g. regular price on a sale item), GB
 * has already marked that uniqueId "done" but its <style> was discarded with the hidden
 * block, so later products show the block unstyled. Generating the CSS from parsed content
 * in the head sidesteps conditions entirely.
 */
add_filter('generateblocks_do_content', function ($content) {
    $element_id = gpwc_active_card_element_id();
    if (!$element_id) {
        return $content;
    }

    $element = get_post($element_id);
    if (
        $element instanceof WP_Post
        && 'publish' === $element->post_status
        && empty($element->post_password)
    ) {
        $content .= $element->post_content;
    }

    return $content;
});
