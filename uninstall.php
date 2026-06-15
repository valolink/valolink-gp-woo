<?php
/**
 * Uninstall: let each module remove its own footprint, then drop the settings option.
 *
 * Only runs when the plugin is deleted from wp-admin. Leaves the GP Elements themselves
 * (and their GenerateBlocks content / GB Pro conditions) untouched.
 */

defined('WP_UNINSTALL_PLUGIN') || exit;

require_once __DIR__ . '/src/Autoloader.php';

if (!defined('VALOLINK_GP_WOO_DIR')) {
    define('VALOLINK_GP_WOO_DIR', plugin_dir_path(__FILE__));
}

\Valolink\GpWoo\Autoloader::register();
\Valolink\GpWoo\Plugin::uninstall();
