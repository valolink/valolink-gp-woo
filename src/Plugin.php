<?php

declare(strict_types=1);

namespace Valolink\GpWoo;

use Valolink\GpWoo\Admin\SettingsPage;
use Valolink\GpWoo\Modules\ProductCard\ProductCardModule;
use Valolink\GpWoo\Modules\ProductCarousel\ProductCarouselModule;

final class Plugin
{
    public static function boot(): void
    {
        $settings = new Settings();
        $registry = new Registry();
        self::register_modules($registry, $settings);

        // Self-updater (core infrastructure; inert unless a real repo is configured).
        if (VALOLINK_GP_WOO_GITHUB_REPO !== 'OWNER/REPO') {
            (new Updater(VALOLINK_GP_WOO_FILE, VALOLINK_GP_WOO_GITHUB_REPO, VALOLINK_GP_WOO_VERSION))->register();
        }

        if (is_admin()) {
            (new SettingsPage($settings, $registry))->register();
        }

        (new Loader($settings, $registry, Context::detect()))->load();
    }

    public static function register_modules(Registry $registry, Settings $settings): void
    {
        $registry->register(new ModuleManifest(
            id: ProductCardModule::MODULE_ID,
            label: __('Product Card', 'valolink-gp-woo'),
            description: __('Render a GeneratePress Element as the WooCommerce product-loop card, with WooCommerce dynamic tags, a block condition, and an add-to-cart shortcode for GenerateBlocks. Flag an Element on its edit screen to use it as the card.', 'valolink-gp-woo'),
            class: ProductCardModule::class,
            default_enabled: true,
            constructor_args: [$settings],
        ));

        $registry->register(new ModuleManifest(
            id: ProductCarouselModule::MODULE_ID,
            label: __('Product Carousel', 'valolink-gp-woo'),
            description: __('A scrollable product-listing block (newest, on sale, category, …) that renders each product through the shared product-card template, so custom listings match the shop loop.', 'valolink-gp-woo'),
            class: ProductCarouselModule::class,
            default_enabled: true,
            constructor_args: [$settings],
        ));
    }

    public static function on_activate(): void
    {
        if (get_option(Settings::OPTION_KEY) === false) {
            add_option(Settings::OPTION_KEY, ['modules' => []], '', false);
        }
    }

    public static function uninstall(): void
    {
        $settings = new Settings();
        $registry = new Registry();
        self::register_modules($registry, $settings);

        foreach ($registry->all() as $manifest) {
            if (class_exists($manifest->class)) {
                $module = new ($manifest->class)(...$manifest->constructor_args);
                if ($module instanceof Module) {
                    $module->uninstall();
                }
            }
        }

        delete_option(Settings::OPTION_KEY);
    }
}
