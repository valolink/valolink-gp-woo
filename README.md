# Valolink GP Woo

A **modular** WooCommerce / GeneratePress toolkit. Each feature is a module you toggle under
**Valolink GP Woo** in wp-admin; a disabled module loads no code (zero footprint). The framework
mirrors the Valolink Plugin toolkit (Module contract, Registry, Loader, single non-autoloaded
`valolink_gp_woo_settings` option). More Woo/GP modules can be added over time.

## Modules

### Product Card
Render a **GeneratePress Block Element** as the **WooCommerce product-loop card**, plus a set of
WooCommerce **dynamic tags**, a **block condition**, and an **add-to-cart shortcode** for
GenerateBlocks. Build your product card visually with GenerateBlocks, flag it, and it replaces
WooCommerce's default `content-product.php` on shop/archive loops.

Requires: WooCommerce, GeneratePress + GP Premium (Elements). GenerateBlocks (+ Pro for the
condition) for building the card.

## Usage

1. Create a GeneratePress **Block Element**, design the card with GenerateBlocks.
2. On the Element edit screen, tick **“Use this Element as the WooCommerce product-loop card”**
   (the *WooCommerce Product Card* box).
3. Optional: set the Element's **GeneratePress display rules** to scope it to specific archives —
   the plugin respects them. No rules set = applies to all product loops. Flag several Elements
   with different rules to show different cards on different archives.

### Building the card

- **Dynamic tags** (GenerateBlocks → Dynamic Data, or type them):
  `{{wc_price}}`, `{{wc_regular_price}}`, `{{wc_sale_price}}`, `{{wc_sale_percentage}}`,
  `{{wc_sku}}`, `{{wc_short_description}}`, `{{wc_categories}}`, `{{wc_weight}}`,
  `{{wc_dimensions}}`, `{{wc_stock_status}}`, `{{wc_stock_quantity}}`, `{{wc_availability}}`,
  `{{wc_rating}}`, `{{wc_review_count}}`, `{{wc_add_to_cart_url}}`, `{{wc_permalink}}`.
  Product image: GenerateBlocks' built-in `{{featured_image}}` (a product image *is* the
  WordPress featured image). Empty tags hide their block unless you add `required:false`.
- **Condition** (GenerateBlocks Pro → block conditions): a **WooCommerce Product** type with
  rules `is on sale / featured / in stock / on backorder / purchasable / has reviews`.
- **Add to cart**: a core *Shortcode* block with `[wc_loop_cart]` (WooCommerce's ajax loop button).

## Building a release

```sh
./build.sh        # -> dist/valolink-gp-woo-<version>.zip
```

The zip contains a top-level `valolink-gp-woo/` folder, ready to install in WordPress.

## Updates from wp-admin

This plugin self-updates from this repo's GitHub **Releases**.

1. Set the repo in `valolink-gp-woo.php` (already set to `valolink/valolink-gp-woo`):
   ```php
   define('VALOLINK_GP_WOO_GITHUB_REPO', 'valolink/valolink-gp-woo');
   ```
   (Set to `OWNER/REPO`, the updater stays disabled.)
2. Bump `Version:` in the header (and `VALOLINK_GP_WOO_VERSION`).
3. Push a tag `vX.Y.Z`. The included GitHub Action builds the zip and publishes a Release with it
   attached. WordPress then shows the update on the Plugins screen.
