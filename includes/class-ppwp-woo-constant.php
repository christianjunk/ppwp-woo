<?php
/**
 * Created by PhpStorm.
 * User: gaupoit
 * Date: 4/8/20
 * Time: 16:04
 */

if ( class_exists( 'PPWP_Woo_Constants' ) ) {
	return;
}

/**
 * Class to define constants.
 *
 * Class PPWP_Woo_Constants
 */
class PPWP_Woo_Constants {

	/**
	 * Get bypass URL expiry_time unit.
	 *
	 * @return  string
	 */
	public static function get_expiration_unit() {
		return defined( 'PPWP_WOO_EXPIRATION_UNIT' ) ? PPWP_WOO_EXPIRATION_UNIT : 'minutes';
	}

	/**
	 * Get password type.
	 *
	 * @return string
	 */
	public static function get_password_type() {
		return defined( 'PPWP_WOO_PASSWORD_TYPE' ) ? PPWP_WOO_PASSWORD_TYPE : 'WooCommerce';
	}

	const WOO_PWD_TYPE = 'WooCommerce';
	const WOO_PWD_LABEL_FORMAT = '%1$d-%2$d';
	const WOO_PWD_LABEL_FORMAT_IN_UI = 'Order %1$s - Product %2$s';
	const WOO_TAB_FIELD_COMMON_INLINE_STYLE = 'width:20em';
}
