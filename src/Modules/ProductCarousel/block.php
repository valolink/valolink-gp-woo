<?php
/**
 * The `valolink-gp-woo/product-carousel` block: a scrollable row of product cards.
 *
 * Server-rendered. The product loop is produced by WooCommerce's own product shortcodes, so
 * every card goes through wc_get_template_part('content', 'product') — the shared card the
 * Product Card module swaps. This block only wraps that loop in the carousel chrome
 * (prev arrow / scrollable track / next arrow) and the scroll script.
 */

defined('ABSPATH') || exit;

const GPWC_CAROUSEL_BLOCK = 'valolink-gp-woo/product-carousel';

/**
 * Block attribute schema (shared by PHP render + the editor script via ServerSideRender).
 *
 * @return array<string, array<string, mixed>>
 */
function gpwc_carousel_attributes(): array
{
    return [
        'source'     => ['type' => 'string', 'default' => 'newest'],
        'category'   => ['type' => 'string', 'default' => ''],
        'count'      => ['type' => 'number', 'default' => 10],
        'columns'    => ['type' => 'number', 'default' => 4],
        'cardMinWidth' => ['type' => 'number', 'default' => 220],
        'showArrows' => ['type' => 'boolean', 'default' => true],
        'arrowSize'  => ['type' => 'number', 'default' => 40],
        'arrowColor' => ['type' => 'string', 'default' => ''],
    ];
}

add_action('init', function () {
    $url = VALOLINK_GP_WOO_URL . 'src/Modules/ProductCarousel/assets/';
    $ver = VALOLINK_GP_WOO_VERSION;

    wp_register_style('gpwc-carousel', $url . 'style.css', [], $ver);
    wp_register_script('gpwc-carousel-view', $url . 'view.js', [], $ver, true);

    wp_register_script(
        'gpwc-carousel-editor',
        $url . 'editor.js',
        ['wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-server-side-render', 'wp-i18n'],
        $ver,
        true
    );

    register_block_type(GPWC_CAROUSEL_BLOCK, [
        'api_version'     => 3,
        'attributes'      => gpwc_carousel_attributes(),
        'render_callback' => 'gpwc_carousel_render',
        'editor_script'   => 'gpwc-carousel-editor',
        'style'           => 'gpwc-carousel',
    ]);
});

/**
 * Render the carousel block.
 *
 * @param array<string, mixed> $attributes
 */
function gpwc_carousel_render(array $attributes): string
{
    if (!function_exists('do_shortcode')) {
        return '';
    }

    $a       = wp_parse_args($attributes, wp_list_pluck(gpwc_carousel_attributes(), 'default'));
    $count   = max(1, (int) $a['count']);
    $columns = max(1, (int) $a['columns']);

    $loop = do_shortcode(gpwc_carousel_shortcode((string) $a['source'], (string) $a['category'], $count, $columns));
    if (trim($loop) === '') {
        return '';
    }

    // Assets only load on pages that actually use the block.
    wp_enqueue_style('gpwc-carousel');
    wp_enqueue_script('gpwc-carousel-view');

    $show_arrows = !empty($a['showArrows']);

    // Drive layout + arrow appearance through CSS custom properties.
    $vars = sprintf(
        '--gpwc-cols:%d;--gpwc-card-min:%dpx;--gpwc-arrow-size:%dpx;',
        $columns,
        max(80, (int) $a['cardMinWidth']),
        max(8, (int) $a['arrowSize'])
    );
    $color = trim((string) $a['arrowColor']);
    if ($color !== '' && preg_match('/^(#[0-9a-fA-F]{3,8}|rgba?\([0-9.,%\s]+\)|[a-zA-Z-]+|var\(--[a-zA-Z0-9-]+\))$/', $color)) {
        $vars .= '--gpwc-arrow-color:' . $color . ';';
    }

    $wrapper = function_exists('get_block_wrapper_attributes')
        ? get_block_wrapper_attributes(['class' => 'gpwc-carousel', 'style' => $vars])
        : 'class="gpwc-carousel" style="' . esc_attr($vars) . '"';

    ob_start();
    ?>
    <div <?php echo $wrapper; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
        <?php if ($show_arrows) : ?>
            <button type="button" class="gpwc-carousel__arrow gpwc-carousel__arrow--prev" aria-label="<?php esc_attr_e('Previous products', 'valolink-gp-woo'); ?>">
                <?php echo gpwc_carousel_arrow_svg(true); // phpcs:ignore WordPress.Security.EscapeOutput ?>
            </button>
        <?php endif; ?>

        <div class="gpwc-carousel__track">
            <?php echo $loop; // phpcs:ignore WordPress.Security.EscapeOutput — WC shortcode output ?>
        </div>

        <?php if ($show_arrows) : ?>
            <button type="button" class="gpwc-carousel__arrow gpwc-carousel__arrow--next" aria-label="<?php esc_attr_e('Next products', 'valolink-gp-woo'); ?>">
                <?php echo gpwc_carousel_arrow_svg(false); // phpcs:ignore WordPress.Security.EscapeOutput ?>
            </button>
        <?php endif; ?>
    </div>
    <?php
    return (string) ob_get_clean();
}

/**
 * Map the block's source setting to a WooCommerce product shortcode. All of these render via
 * wc_get_template_part('content','product'), so the card swap applies uniformly.
 */
function gpwc_carousel_shortcode(string $source, string $category, int $count, int $columns): string
{
    switch ($source) {
        case 'sale':
            return sprintf('[sale_products limit="%d" columns="%d"]', $count, $columns);
        case 'featured':
            return sprintf('[featured_products limit="%d" columns="%d"]', $count, $columns);
        case 'best_selling':
            return sprintf('[best_selling_products limit="%d" columns="%d"]', $count, $columns);
        case 'top_rated':
            return sprintf('[top_rated_products limit="%d" columns="%d"]', $count, $columns);
        case 'category':
            $slug = sanitize_title($category);
            if ($slug !== '') {
                return sprintf('[product_category category="%s" limit="%d" columns="%d"]', esc_attr($slug), $count, $columns);
            }
            // No category chosen yet — fall through to newest.
        case 'newest':
        default:
            return sprintf('[products limit="%d" columns="%d" orderby="date" order="DESC"]', $count, $columns);
    }
}

/**
 * Inline arrow SVG. Mirrors the circular arrow used on the existing front-page carousel.
 *
 * @param bool $point_left Render the left-pointing (previous) variant.
 */
function gpwc_carousel_arrow_svg(bool $point_left): string
{
    $path = 'M256 8c137 0 248 111 248 248S393 504 256 504S8 393 8 256S119 8 256 8zm-28.9 143.6l75.5 72.4H120c-13.3 0-24 10.7-24 24v16c0 13.3 10.7 24 24 24h182.6l-75.5 72.4c-9.7 9.3-9.9 24.8-.4 34.3l11 10.9c9.4 9.4 24.6 9.4 33.9 0L404.3 273c9.4-9.4 9.4-24.6 0-33.9L271.6 106.3c-9.4-9.4-24.6-9.4-33.9 0l-11 10.9c-9.5 9.6-9.3 25.1.4 34.4z';
    $transform = $point_left ? ' transform="translate(512, 0) scale(-1, 1)"' : '';

    return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" aria-hidden="true" focusable="false">'
        . '<path fill="currentColor" d="' . $path . '"' . $transform . '></path></svg>';
}
