<?php
/**
 * Custom GenerateBlocks dynamic tags for WooCommerce.
 *
 * Each callback receives the resolved WC_Product; on non-products / when WC is absent the
 * tag renders '' (never fatals). GenerateBlocks hides the whole block when a tag returns ''
 * unless you add `required:false`, e.g. {{wc_sku required:false}}. Amount/percentage tags
 * target simple products; variable products have no single regular/sale amount (price is a
 * range) so those return ''.
 */

defined('ABSPATH') || exit;

add_action('init', function () {
    if (!class_exists('GenerateBlocks_Register_Dynamic_Tag')) {
        return;
    }

    $get_product = function () {
        global $product;
        return $product instanceof WC_Product ? $product : wc_get_product(get_the_ID());
    };

    // Register a tag whose callback gets the WC_Product (guaranteed non-null) + parsed options.
    $tag = function ($name, $title, $cb) use ($get_product) {
        new GenerateBlocks_Register_Dynamic_Tag([
            'title'    => $title,
            'tag'      => $name,
            'type'     => 'post',
            'supports' => ['source'],
            'return'   => function ($options, $block, $instance) use ($get_product, $cb) {
                $p = $get_product();
                return $p ? (string) $cb($p, $options) : '';
            },
        ]);
    };

    // Format an amount with the store's decimals/separators/currency symbol+position, but WITHOUT
    // WooCommerce's <span class="woocommerce-Price-*"> markup — so the price is plain text that the
    // Element editor's own styling controls. Entities (e.g. the currency symbol, nbsp) are decoded
    // to real characters. Returns '' for an empty amount.
    $plain_price = function ($amount) {
        if ('' === (string) $amount) {
            return '';
        }
        return html_entity_decode(wp_strip_all_tags(wc_price($amount)), ENT_QUOTES, 'UTF-8');
    };

    // --- Pricing ---
    // wc_price keeps WooCommerce's full markup (incl. sale strikethrough); the amount tags below
    // are plain text for styling in the Element. wc_sale_percentage is already plain.
    $tag('wc_price', 'WC Price', fn($p) => $p->get_price_html());
    $tag('wc_regular_price', 'WC Regular Price', fn($p) => $plain_price($p->get_regular_price()));
    $tag('wc_sale_price', 'WC Sale Price', fn($p) => $plain_price($p->get_sale_price()));
    $tag('wc_sale_percentage', 'WC Sale Percentage', function ($p) {
        $reg = (float) $p->get_regular_price();
        if (!$p->is_on_sale() || $reg <= 0) {
            return '';
        }
        return round(($reg - (float) $p->get_price()) / $reg * 100) . '%';
    });

    // Scheduled-sale dates ('' when no schedule is set). Site date format by default; override
    // per-tag with a format option, e.g. {{wc_sale_end format:"j.n.Y"}}.
    $sale_date = function ($dt, $options) {
        if (!$dt instanceof \WC_DateTime) {
            return '';
        }
        $format = !empty($options['format']) && is_string($options['format'])
            ? $options['format']
            : get_option('date_format');
        return wp_date($format, $dt->getTimestamp());
    };
    $tag('wc_sale_start', 'WC Sale Start Date', fn($p, $options) => $sale_date($p->get_date_on_sale_from(), $options));
    $tag('wc_sale_end', 'WC Sale End Date', fn($p, $options) => $sale_date($p->get_date_on_sale_to(), $options));

    // Whole days remaining until a scheduled sale ends (rounded up). Plain integer so you can add
    // your own label, e.g. "{{wc_sale_days_left}} days left". '' when the product isn't on sale or
    // the sale has no end date (open-ended sales can't count down).
    $tag('wc_sale_days_left', 'WC Sale Days Left', function ($p) {
        if (!$p->is_on_sale()) {
            return '';
        }
        $end = $p->get_date_on_sale_to();
        if (!$end instanceof \WC_DateTime) {
            return '';
        }
        $seconds = $end->getTimestamp() - time();
        return $seconds > 0 ? (string) (int) ceil($seconds / DAY_IN_SECONDS) : '';
    });

    // --- Identity / details ---
    $tag('wc_sku', 'WC SKU', fn($p) => $p->get_sku());
    $tag('wc_short_description', 'WC Short Description', fn($p) => $p->get_short_description());
    $tag('wc_categories', 'WC Categories', fn($p) => wc_get_product_category_list($p->get_id()));
    $tag('wc_weight', 'WC Weight', fn($p) => $p->get_weight() ? $p->get_weight() . ' ' . get_option('woocommerce_weight_unit') : '');
    $tag('wc_dimensions', 'WC Dimensions', function ($p) {
        $dims = $p->get_dimensions(false);
        return array_filter($dims) ? wc_format_dimensions($dims) : '';
    });

    // --- Stock ---
    $stock_labels = ['instock' => 'In stock', 'outofstock' => 'Out of stock', 'onbackorder' => 'On backorder'];
    $tag('wc_stock_status', 'WC Stock Status', fn($p) => $stock_labels[$p->get_stock_status()] ?? $p->get_stock_status());
    $tag('wc_stock_quantity', 'WC Stock Quantity', fn($p) => null === $p->get_stock_quantity() ? '' : $p->get_stock_quantity());
    $tag('wc_availability', 'WC Availability', fn($p) => $p->get_availability()['availability'] ?? '');

    // --- Reviews ---
    $tag('wc_rating', 'WC Rating', fn($p) => $p->get_average_rating());
    $tag('wc_review_count', 'WC Review Count', fn($p) => $p->get_review_count());

    // --- Links ---
    $tag('wc_add_to_cart_url', 'WC Add to Cart URL', fn($p) => $p->add_to_cart_url());
    $tag('wc_permalink', 'WC Permalink', fn($p) => $p->get_permalink());
}, 20);
