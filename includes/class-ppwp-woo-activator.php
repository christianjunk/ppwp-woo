<?php

/**
 * Fired during plugin activation
 *
 * @link       https://passwordprotectwp.com/extensions/
 * @since      1.0.0
 *
 * @package    Ppwp_Woo
 * @subpackage Ppwp_Woo/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Ppwp_Woo
 * @subpackage Ppwp_Woo/includes
 * @author     BWPS <hello@preventdirectaccess.com>
 */
class Ppwp_Woo_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		require_once plugin_dir_path( __FILE__ ) . '/class-ppwp-woo-functions.php';
		require_once plugin_dir_path( __FILE__ ) . '/class-ppwp-woo-message.php';
		$message = Ppwp_Woo_Message_Manager::show_admin_notices( true );
		if ( false === $message ) {
			return;
		}

		wp_die( $message );
	}

}
