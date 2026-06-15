<?php

declare(strict_types=1);

namespace Valolink\GpWoo\Modules\ProductCarousel;

use Valolink\GpWoo\Context;
use Valolink\GpWoo\Module;
use Valolink\GpWoo\Settings;

/**
 * Product Carousel module — a horizontally-scrollable product listing block.
 *
 * The block runs a WooCommerce product query and renders each product through
 * wc_get_template_part('content', 'product'), i.e. the same card the Product Card module
 * swaps in — so a carousel on the front page uses the exact same card as the shop loop
 * (single source of truth). The block itself only owns the carousel chrome: the
 * [arrow] [scrollable track] [arrow] grid and the scroll behavior.
 */
final class ProductCarouselModule implements Module
{
    public const MODULE_ID = 'product_carousel';

    public function __construct(private readonly Settings $settings) {}

    public function should_load(Context $context): bool
    {
        // Frontend render, the block editor (register + inspector), and REST (ServerSideRender
        // preview hits the block-renderer endpoint). Never needed for cron/CLI.
        return !$context->is_cron && !$context->is_cli;
    }

    public function register(): void
    {
        require_once __DIR__ . '/block.php';
    }

    public function uninstall(): void
    {
        $this->settings->forget_module(self::MODULE_ID);
    }
}
