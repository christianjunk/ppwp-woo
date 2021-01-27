<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://passwordprotectwp.com/extensions/
 * @since             1.0.0
 * @package           Ppwp_Woo
 *
 * @wordpress-plugin
 * Plugin Name:       PPWP WooCommerce Integration
 * Plugin URI:        https://passwordprotectwp.com/extensions/woocommerce-integration/
 * Description:       Automatically generate and send quick access links in order to bypass WordPress password protection to WooCommerce customers after purchase.
 * Version:           1.1.0
 * Author:            BWPS
 * Author URI:        https://passwordprotectwp.com/extensions/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       ppwp-woo
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'PPWP_WOO_VERSION', '1.1.0' );
define( 'PPWP_WOO_PLUGIN_NAME', 'PPWP WooCommerce Integration' );

if ( ! defined( 'PPWP_WOO_MAIN_FILE' ) ) {
	define( 'PPWP_WOO_MAIN_FILE', __FILE__ );
}
if ( ! defined( 'PPWP_WOO_PATH' ) ) {
	define( 'PPWP_WOO_PATH', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'PPWP_WOO_URL' ) ) {
	define( 'PPWP_WOO_URL', plugin_dir_url( __FILE__ ) );
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-ppwp-woo-activator.php
 */
function activate_ppwp_woo() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-ppwp-woo-activator.php';
	Ppwp_Woo_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-ppwp-woo-deactivator.php
 */
function deactivate_ppwp_woo() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-ppwp-woo-deactivator.php';
	Ppwp_Woo_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_ppwp_woo' );
register_deactivation_hook( __FILE__, 'deactivate_ppwp_woo' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-ppwp-woo.php';

add_action( 'plugins_loaded', 'run_ppwp_woo', 20 );

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_ppwp_woo() {

	$plugin = new Ppwp_Woo();
	$plugin->run();

}

