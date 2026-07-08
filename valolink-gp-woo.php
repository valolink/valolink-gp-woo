<?php
/**
 * Plugin Name:       Valolink GP Woo
 * Description:       Modular WooCommerce / GeneratePress toolkit. Each feature is a toggleable module with zero footprint when disabled. First module: Product Card (render a GP Element as the WooCommerce product-loop card).
 * Version:           0.1.9
 * Requires at least: 6.5
 * Requires PHP:      8.2
 * Author:            Valolink
 * Text Domain:       valolink-gp-woo
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

define('VALOLINK_GP_WOO_VERSION', '0.1.9');
define('VALOLINK_GP_WOO_FILE', __FILE__);
define('VALOLINK_GP_WOO_DIR', plugin_dir_path(__FILE__));
define('VALOLINK_GP_WOO_URL', plugin_dir_url(__FILE__));
define('VALOLINK_GP_WOO_BASENAME', plugin_basename(__FILE__));

// GitHub repo for wp-admin updates, as 'owner/repo'. Set to 'OWNER/REPO' to disable the updater.
define('VALOLINK_GP_WOO_GITHUB_REPO', 'valolink/valolink-gp-woo');

require_once VALOLINK_GP_WOO_DIR . 'src/Autoloader.php';
\Valolink\GpWoo\Autoloader::register();

register_activation_hook(__FILE__, [\Valolink\GpWoo\Plugin::class, 'on_activate']);

// Boot on init (not plugins_loaded): all module hooks are init-or-later, and booting at
// init avoids the WP 6.7+ "textdomain loaded too early" notice from translated module labels.
// Priority 5 keeps us ahead of GenerateBlocks' condition registration at init:10.
add_action('init', [\Valolink\GpWoo\Plugin::class, 'boot'], 5);
