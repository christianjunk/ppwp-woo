<?php
/**
 * Created by PhpStorm.
 * User: gaupoit
 * Date: 4/7/20
 * Time: 17:58
 */
/**
 * Check ppwp pro is active.
 *
 * @return bool Is pro active.
 */
function ppwp_woo_is_ppwp_pro_active() {
	return defined( 'PPW_PRO_VERSION' );
}

/**
 * Check ppwp free is active.
 *
 * @return bool Is free active.
 */
function ppwp_woo_is_ppwp_free_active() {
	return defined( 'PPW_VERSION' );
}

/**
 * Is ppwp pro's license valid?
 *
 * @return bool Is ppwp pro's license valid.
 */
function ppwp_woo_is_ppwp_pro_license_valid() {
	return function_exists( 'is_pro_active_and_valid_license' ) && is_pro_active_and_valid_license();
}

/**
 * Check whether this addon is valid purchase with PPWP Pro license.
 *
 * @return True|False False add-on does not belong to current PPWP Pro license.
 */
function ppwp_woo_is_valid_addon_purchase() {
	$configs = require plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-ppwp-woo-configs.php';
	$license = get_option( 'wp_protect_password_license_key', '' );
	if ( empty( $license ) || ! class_exists( 'YME_Addon' ) ) {
		return false;
	}
	$yme_addon = new YME_Addon( 'ppwp-woo' );
	$data      = $yme_addon->isValidPurchased( $configs->addonProductId, $license ); // phpcs:ignore

	return isset( $data['isValid'] ) && $data['isValid'];
}

/**
 * Helper function to check whether the specific plugin version is equal or larger than required version.
 *
 * @param string $plugin_version   Current plugin version constant by string.
 * @param string $required_version Required version.
 *
 * @return bool
 */
function ppwp_woo_is_required_plugin_version( $plugin_version, $required_version ) {
	return defined( $plugin_version ) && version_compare(
		constant( $plugin_version ), // Get the constant by name.
		$required_version,
		'>='
	);
}

/**
 * Get product quantity by order id and product id.
 *
 * @param integer $order_id   Order ID.
 * @param integer $product_id Product ID.
 *
 * @return int Product quantity.
 */
function ppwp_woo_get_product_quantity( $order_id, $product_id ) {
	// wc_get_order function is available in version 2.2.
	if ( ! function_exists( 'wc_get_order' ) ) {
		return 1;
	}

	$order = wc_get_order( $order_id );
	if ( ! $order ) {
		return 1;
	}
	foreach ( $order->get_items() as $item_id => $item_data ) {
		$product = $item_data->get_product();
		if ( $product->get_id() === $product_id ) {
			return $item_data->get_quantity();
		}
	}

	return 1;
}
