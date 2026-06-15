<?php

declare(strict_types=1);

namespace Valolink\GpWoo\Modules\ProductCard;

use Valolink\GpWoo\Context;
use Valolink\GpWoo\Module;
use Valolink\GpWoo\Settings;

/**
 * Product Card module — renders a GeneratePress Element as the WooCommerce product-loop card,
 * plus WooCommerce dynamic tags, a GenerateBlocks Pro condition, and an add-to-cart shortcode.
 *
 * The feature's implementation lives in the sibling procedural files (each registers its own
 * hooks on require). They're only required here, so a disabled module loads none of them.
 */
final class ProductCardModule implements Module
{
    public const MODULE_ID = 'product_card';

    public function __construct(private readonly Settings $settings) {}

    public function should_load(Context $context): bool
    {
        // Needed for the shop loop (frontend), the block editor / tag + condition REST
        // (rest/ajax), and the Element metabox (admin). Irrelevant to cron/CLI.
        return !$context->is_cron && !$context->is_cli;
    }

    public function register(): void
    {
        $dir = __DIR__;
        require_once $dir . '/element.php';       // gpwc_active_card_element_id() + flag meta
        require_once $dir . '/element-meta.php';  // metabox flag on gp_elements
        require_once $dir . '/display-rules.php';  // honor GP display rules among flagged Elements
        require_once $dir . '/card-swap.php';      // swap content-product.php
        require_once $dir . '/dynamic-tags.php';   // {{wc_*}} GenerateBlocks tags
        require_once $dir . '/conditions.php';     // "WooCommerce Product" GB Pro condition
        require_once $dir . '/shortcode.php';      // [wc_loop_cart]
    }

    public function uninstall(): void
    {
        // Remove the product-card flag from every GP Element + this module's settings.
        if (function_exists('delete_post_meta_by_key')) {
            delete_post_meta_by_key('_gpwc_product_card');
        }
        $this->settings->forget_module(self::MODULE_ID);
    }
}
