<?php
/**
 * Element selection — which GeneratePress Element(s) act as the WooCommerce product card.
 */

defined('ABSPATH') || exit;

/** Post meta key that flags a gp_elements post as the product card. */
const GPWC_CARD_META = '_gpwc_product_card';

/**
 * IDs of all published GP Elements flagged as the product card.
 *
 * @return int[]
 */
function gpwc_card_element_ids() {
    if (!post_type_exists('gp_elements')) {
        return [];
    }

    $q = new WP_Query([
        'post_type'      => 'gp_elements',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'no_found_rows'  => true,
        'meta_query'     => [[
            'key'   => GPWC_CARD_META,
            'value' => '1',
        ]],
    ]);

    return array_map('intval', $q->posts);
}

/**
 * The Element to render as the current product card, or 0 if none is configured.
 *
 * For now this is simply the first flagged Element. GeneratePress display-rule
 * selection (choosing among several flagged Elements by context) is layered on
 * later via the 'gpwc_active_card_element_id' filter.
 *
 * @return int
 */
function gpwc_active_card_element_id() {
    // The resolved Element is stable within a single request (same archive context),
    // and the selector runs once per product in the loop — so cache it per request.
    static $cache = null;
    if (null !== $cache) {
        return $cache;
    }

    $ids = gpwc_card_element_ids();
    $id  = $ids[0] ?? 0;

    /**
     * Filter the resolved product-card Element ID.
     *
     * @param int   $id  Selected Element ID (0 for none).
     * @param int[] $ids All flagged Element IDs.
     */
    $cache = (int) apply_filters('gpwc_active_card_element_id', $id, $ids);
    return $cache;
}
