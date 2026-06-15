<?php
/**
 * Admin UI — a checkbox on the GP Element edit screen to flag it as the product card.
 *
 * Classic meta box (no JS build needed); renders as a panel in the block editor too.
 */

defined('ABSPATH') || exit;

add_action('add_meta_boxes', function () {
    add_meta_box(
        'gpwc_product_card',
        __('WooCommerce Product Card', 'valolink-gp-woo'),
        'gpwc_render_card_metabox',
        'gp_elements',
        'side',
        'high'
    );
});

/**
 * Render the metabox checkbox.
 *
 * @param WP_Post $post The Element being edited.
 */
function gpwc_render_card_metabox($post) {
    wp_nonce_field('gpwc_card_metabox', 'gpwc_card_nonce');
    $enabled = '1' === get_post_meta($post->ID, GPWC_CARD_META, true);
    ?>
    <label>
        <input type="checkbox" name="gpwc_product_card" value="1" <?php checked($enabled); ?> />
        <?php esc_html_e('Use this Element as the WooCommerce product-loop card.', 'valolink-gp-woo'); ?>
    </label>
    <p class="description">
        <?php esc_html_e('Replaces WooCommerce\'s default product card on shop/archive loops.', 'valolink-gp-woo'); ?>
    </p>
    <?php
}

add_action('save_post_gp_elements', function ($post_id) {
    if (!isset($_POST['gpwc_card_nonce']) || !wp_verify_nonce(sanitize_key($_POST['gpwc_card_nonce']), 'gpwc_card_metabox')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    if (isset($_POST['gpwc_product_card'])) {
        update_post_meta($post_id, GPWC_CARD_META, '1');
    } else {
        delete_post_meta($post_id, GPWC_CARD_META);
    }
});
