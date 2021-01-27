<?php
/**
 * Created by PhpStorm.
 * User: gaupoit
 * Date: 4/8/20
 * Time: 10:50
 */
if ( class_exists( 'Ppwp_Woo_Message_Manager' ) ) {
	return;
}

/**
 * Messages manager
 *
 * Class Ppwp_Woo_Message_Manager
 */
class Ppwp_Woo_Message_Manager {

	const SCREENS = array(
		'INSTALLED_PLUGINS' => 'plugins',
		'EDIT_PRODUCT'      => 'edit-product',
		'PRODUCT'           => 'product',
	);


	/**
	 * Get plugin text.
	 *
	 * @return array
	 */
	public static function get_text() {
		return array(
			'TAB_NAME'                => __( 'PPWP Access Link', 'ppwp-woo' ),
			'TAB_DESC'                => sprintf(
			// translators: %s Documentation link.
				__( 'Select which password protected content will be sent to customers via the %s once they purchase this product. You can restrict link access based on clicks or time.
				', 'ppwp-woo' ),
				self::generate_link( 'quick access links', 'https://passwordprotectwp.com/docs/send-quick-access-links-after-woocommerce-purchase/' )
			),
			'PROTECTION_TYPE_LABEL'   => __( 'Protection type', 'ppwp-woo' ),
			'PROTECTED_POST_LABEL'    => __( 'Protected page', 'ppwp-woo' ),
			'DEFAULT_OPTION_LABEL'    => __( '— Select a password protected page —', 'ppwp-woo' ),
			'USAGE_LIMIT_TOOLTIP'     => __( 'Leave blank for unlimited usage', 'ppwp-woo' ),
			'EXPIRY_DATE_LABEL'       => __( 'Expiration (minutes)', 'ppwp-woo' ),
			'EXPIRY_DATE_TOOLTIP'     => __( 'Enter the number of minutes before the quick access link expires, or leave blank for no expiry', 'ppwp-woo' ),
			'USE_FIRST_EXPIRY_DATE'   => __( 'Set expiration date after the first click or usage', 'ppwp-woo' ),
			'CUSTOM_TEXT_LABEL'       => __( 'Custom message', 'ppwp-woo' ),
			'CUSTOM_TEXT_TOOLTIP'     => __( 'Insert any text that you want to include in the product order details.', 'ppwp-woo' ),
			'CUSTOM_TEXT_PLACEHOLDER' => __( 'This is the %product page%. The link will auto-expire after {usage_limit} clicks or at {expiration_time}.', 'ppwp-woo' ),
		);
	}

	/**
	 * Message for plugin compatibility check.
	 */
	public static function get_plugin_compatibility_msg() {
		$pricing_url = self::generate_link(
			__( 'Password Protect WordPress Pro', 'ppwp-woo' ),
			'https://passwordprotectwp.com/pricing/'
		);

		$pro_do_it_now = self::generate_link(
			__( 'do it now', 'ppwp-woo' ),
			'https://passwordprotectwp.com/extensions/woocommerce-integration/'
		);

		$free_url = self::generate_link(
			__( 'Password Protect WordPress Free', 'ppwp-woo' ),
			'https://wordpress.org/plugins/password-protect-page/'
		);

		$email_url = self::generate_link(
			'hello@PreventDirectAccess.com',
			'mailto:hello@PreventDirectAccess.com'
		);

		$ppwp_free_message = sprintf(
			// translators: %s Plugin link.
			__( 'Please install and activate %s plugin', 'ppwp-woo' ),
			$free_url
		);

		$ppwp_pro_message = sprintf(
			// translators: %s Plugin link.
			__( 'Please install and activate %s plugin', 'ppwp-woo' ),
			$pricing_url
		);

		$invalid_license_messsage = sprintf(
			// translators: %1$s Plugin name, %2$s call to action, %3$s email.
			__( 'You didn\'t purchase this add-on with your %1$s plugin. Please %2$s or drop us an email at %3$s', 'ppwp-woo' ),
			$pricing_url,
			$pro_do_it_now,
			$email_url
		);

		$latest_plugin_message = sprintf(
			// translators: %s Extension name.
			__( 'Please update Password Protect WordPress Lite and Pro to the latest versions for %s extension to work properly.', 'ppwp-woo' ),
			PPWP_WOO_PLUGIN_NAME
		);

		return array(
			'activate_ppwp_free'    => $ppwp_free_message,
			'activate_ppwp_pro'     => $ppwp_pro_message,
			'check_license_ppwp'    => $invalid_license_messsage,
			'require_latest_plugin' => $latest_plugin_message,
		);
	}

	/**
	 * Generate the a href link for reusing.
	 *
	 * @param string $message Link text.
	 * @param string $url     Link url.
	 *
	 * @return string
	 */
	public static function generate_link( $message, $url ) {
		return sprintf( '<a target="_blank" rel="noopener" href=%1$s>%2$s</a>', $url, $message );
	}

	/**
	 * Show admin notices.
	 *
	 * @param bool $should_check_valid_addon Should check add-on valid with main plugin.
	 *
	 * @return string|bool
	 */
	public static function show_admin_notices( $should_check_valid_addon = false ) {
		if ( ! ppwp_woo_is_ppwp_pro_active() ) {
			return self::get_plugin_compatibility_msg()['activate_ppwp_pro'];
		}

		if ( ! ppwp_woo_is_ppwp_free_active() ) {
			if ( ! $should_check_valid_addon ) {
				return false;
			}

			return self::get_plugin_compatibility_msg()['activate_ppwp_free'];
		}

		if ( ! ppwp_woo_is_ppwp_pro_license_valid() ) {
			return self::get_plugin_compatibility_msg()['check_license_ppwp'];
		}

		if ( $should_check_valid_addon && ! ppwp_woo_is_valid_addon_purchase() ) {
			return self::get_plugin_compatibility_msg()['check_license_ppwp'];
		}

		// Since PPWP_PRO_VERSION 1.2.2, we fired a hook that can allow WooCommerce type can work with bypass URL.
		if ( ! ppwp_woo_is_required_plugin_version( 'PPW_PRO_VERSION', '1.2.2' ) ) {
			return self::get_plugin_compatibility_msg()['require_latest_plugin'];
		}

		return false;
	}


}
