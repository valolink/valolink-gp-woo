<?php
/**
 * Respect GeneratePress Element display rules when choosing the card.
 *
 * Among the flagged card Elements, pick the first whose GP location / exclude / user
 * conditions match the current request. A location rule is required: an Element with no
 * location set matches nowhere (mirroring GeneratePress's own Element behavior). If none
 * match, return 0 so the default WooCommerce card renders.
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

    // A location rule is required: show_data() starts from "hide" and only matches when a
    // location conditional fires, so an Element with no location set matches nowhere.
    return (bool) GeneratePress_Conditions::show_data($display, $exclude, $users);
}
