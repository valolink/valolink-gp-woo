<?php
/**
 * Respect GeneratePress Element display rules when choosing the card.
 *
 * Among the flagged card Elements, pick the first whose GP location / exclude / user
 * conditions match the current request. An Element with no location rules set matches by
 * default — the product-card flag alone qualifies it (and the swap only ever fires on the
 * product loop anyway). If none match, return 0 so the default WooCommerce card renders.
 */

defined('ABSPATH') || exit;

add_filter('gpwc_active_card_element_id', function ($id, $ids) {
    // GP Premium conditions unavailable: keep the default (first flagged) selection.
    if (!class_exists('GeneratePress_Conditions')) {
        return $id;
    }

    foreach ($ids as $element_id) {
        if (gpwc_element_rules_match($element_id)) {
            return $element_id;
        }
    }

    return 0;
}, 10, 2);

/**
 * Whether a GP Element's display rules match the current request context.
 *
 * @param int $element_id Element post ID.
 * @return bool
 */
function gpwc_element_rules_match($element_id) {
    $display = get_post_meta($element_id, '_generate_element_display_conditions', true) ?: [];
    $exclude = get_post_meta($element_id, '_generate_element_exclude_conditions', true) ?: [];
    $users   = get_post_meta($element_id, '_generate_element_user_conditions', true) ?: [];

    // No location rules set -> the card flag alone qualifies this Element.
    if (empty($display)) {
        return true;
    }

    return (bool) GeneratePress_Conditions::show_data($display, $exclude, $users);
}
