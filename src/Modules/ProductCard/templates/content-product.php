<?php
/**
 * Product-loop card: render the configured GeneratePress Element in place of the
 * default WooCommerce content-product.php.
 */

defined('ABSPATH') || exit;

$gpwc_element = get_post(gpwc_active_card_element_id());
if (!$gpwc_element) {
    return;
}

global $product;
?>
<li <?php wc_product_class('product', $product); ?>>
  <?php
    // do_shortcode so shortcode blocks (e.g. [wc_loop_cart]) resolve — do_blocks alone won't,
    // since shortcodes are normally run by the the_content filter we bypass here.
    echo do_shortcode(do_blocks($gpwc_element->post_content));
  ?>
</li>
