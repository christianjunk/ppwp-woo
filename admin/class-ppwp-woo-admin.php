<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://passwordprotectwp.com/extensions/
 * @since      1.0.0
 *
 * @package    Ppwp_Woo
 * @subpackage Ppwp_Woo/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Ppwp_Woo
 * @subpackage Ppwp_Woo/admin
 * @author     BWPS <hello@preventdirectaccess.com>
 */
class Ppwp_Woo_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $plugin_name The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $version The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 *
	 * @param      string $plugin_name The name of this plugin.
	 * @param      string $version     The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Ppwp_Woo_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Ppwp_Woo_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Ppwp_Woo_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Ppwp_Woo_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */
		if ( $this->should_load_assets() ) {
			wp_enqueue_script(
				'ppwp-woo',
				PPWP_WOO_URL . '/admin/dist/ppwp-woo-tab.js',
				array(
					'jquery',
					'selectWoo',
				),
				PPWP_WOO_VERSION,
				true
			);
		}
	}

	/**
	 * Save protected item option ppwp_woo_protected_post
	 *
	 * @param int $post_id Product ID.
	 */
	public function save_protected_item_option( $post_id ) {
		PPWP_Woo_Service::get_instance()->saved_options( $post_id );
	}

	/**
	 * Handle plugins loaded hook.
	 */
	public function handle_plugins_loaded() {
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/shortcode/class-service-ppwp-woo-shortcode.php';
		if ( class_exists( 'PPWP_Woo_SC' ) ) {
			// PPWP_Woo_SC and PPWP_Pro_SideWide both inherit from base class PPWP_Pro_Abstract_Shortcode.
			// Use instance object will override the PPWP_Pro_SideWide instance.
			PPWP_Woo_SC::get_instance();
		}

		$configs = require PPWP_WOO_PATH . 'includes/class-ppwp-woo-configs.php';
		$this->setup_plugin_update( $configs );
	}

	/**
	 * Setup plugin update.
	 *
	 * @param array $configs Plugin configuration.
	 */
	private function setup_plugin_update( $configs ) {
		if ( method_exists( 'Puc_v4p8_Factory', 'buildUpdateChecker' ) ) {
			Puc_v4p8_Factory::buildUpdateChecker(
				$configs->url,
				PPWP_WOO_MAIN_FILE,
				'ppwp-woo'
			);
		} elseif ( method_exists( 'Puc_v4_Factory', 'buildUpdateChecker' ) ) {
			Puc_v4_Factory::buildUpdateChecker(
				$configs->url,
				PPWP_WOO_MAIN_FILE,
				'ppwp-woo'
			);
		} else {
			add_filter( 'site_transient_update_plugins', array( $this, 'ppwp_woo_filter_plugin_updates' ) );
		}
	}

	/**
	 * Filter plugin updates.
	 *
	 * @param object $value Data update cache.
	 *
	 * @return object $value Data update cache.
	 */
	private function ppwp_woo_filter_plugin_updates( $value ) {
		if ( ! isset( $value->response['ppwp-woo/ppwp-woo.php'] ) ) {
			return $value;
		}
		unset( $value->response['ppwp-woo/ppwp-woo.php'] );

		return $value;
	}

	/**
	 * Show by pass URL in order details
	 *
	 * @param int    $item_id Product ID.
	 * @param object $item    Product.
	 * @param object $order   Order.
	 */
	public function show_bypass_url( $item_id, $item, $order ) {
		$content = PPWP_Woo_Service::get_instance()->render_bypass_url_sc( $item_id, $item, $order );
		echo $content; // phpcs:ignore
	}

	/**
	 * Add custom protected tab.
	 *
	 * @param array $tabs Product tabs.
	 *
	 * @return array
	 */
	public function add_restricted_tab( $tabs ) {
		return PPWP_Woo_Service::get_instance()->add_restricted_tab( $tabs );
	}

	/**
	 * Render the restricted tab.
	 */
	public function ppwp_options_product_tab_content() {
		PPWP_Woo_Service::get_instance()->add_restricted_tab_contents();
	}

	/**
	 * Manage admin notices for plugin
	 */
	public function manage_notices() {
		$current_screen = get_current_screen();
		if ( null === $current_screen ) {
			return;
		}

		// Only show notice in plugin installation and Woo product screens.
		$screens_is_show = array(
			Ppwp_Woo_Message_Manager::SCREENS['INSTALLED_PLUGINS'],
			Ppwp_Woo_Message_Manager::SCREENS['EDIT_PRODUCT'],
			Ppwp_Woo_Message_Manager::SCREENS['PRODUCT'],
		);

		if ( ! in_array( $current_screen->id, $screens_is_show, true ) ) {
			return;
		}

		$class   = 'notice notice-error is-dismissible';
		$message = Ppwp_Woo_Message_Manager::show_admin_notices();
		if ( false === $message ) {
			return;
		}

		printf(
			'<div class="%1$s"><p><b>%2$s</b> %3$s</p></div>',
			esc_attr( $class ),
			PPWP_WOO_PLUGIN_NAME . ':', // phpcs:ignore
			$message // phpcs:ignore
		);
	}

	/**
	 * Handle before set post cookie hook. It helps to override the bypass URL's cookie expiry time.
	 *
	 * @param int    $post_id  The pwd protected post ID.
	 * @param string $password The input pwd.
	 * @param string $type     The pwd type.
	 */
	public function handle_before_set_post_cookie( $post_id, $password, $type ) {
		// Only apply for bypass type.
		if ( PPW_Pro_Constants::BYPASS_TYPE !== $type ) {
			return;
		}
		add_filter( 'post_password_expires', array( $this, 'set_custom_cookie_expiry_time' ), 20, 1 );
	}

	/**
	 * Set the custom cookie expriy time.
	 *
	 * @param int $expiry_time Cookie expiry time.
	 *
	 * @return int
	 */
	public function set_custom_cookie_expiry_time( $expiry_time ) {
		if ( ! defined( 'PPWP_WOO_COOKIE_EXPIRY_TIME' ) ) {
			return $expiry_time;
		}

		$expiry_time = time() + PPWP_WOO_COOKIE_EXPIRY_TIME;

		return $expiry_time;
	}

	/**
	 * Remove filter post_password_expires after finishing to set cookie.
	 *
	 * @param int    $post_id  The pwd protected post ID.
	 * @param string $password The input pwd.
	 * @param string $type     The pwd type.
	 */
	public function handle_after_set_post_cookie( $post_id, $password, $type ) {
		// Only apply for bypass type.
		if ( PPW_Pro_Constants::BYPASS_TYPE !== $type ) {
			return;
		}

		remove_filter( 'post_password_expires', array( $this, 'set_custom_cookie_expiry_time' ) );
	}

	/**
	 * Add WooCommerce pwd type.
	 *
	 * @param array $types Pwd types collection.
	 *
	 * @return array
	 */
	public function add_woo_pwd_type( $types ) {
		if ( ! is_array( $types ) ) {
			return $types;
		}

		$types[] = PPWP_Woo_Constants::WOO_PWD_TYPE;

		return $types;
	}

	/**
	 * Add WooCommerce pwd key and label.
	 *
	 * @param array $type_map The key, value map with key is pwd type and value is its label.
	 *
	 * @return array
	 */
	public function add_woo_pwd_type_label( $type_map ) {
		if ( ! is_array( $type_map ) ) {
			return $type_map;
		}
		$type_map[ PPWP_Woo_Constants::WOO_PWD_TYPE ] = PPWP_Woo_Constants::WOO_PWD_TYPE;

		return $type_map;
	}

	/**
	 * Allow WooCommerce type to show in Stats extension.
	 *
	 * @param string $types Allowed types wrap by ''. Eg: ["'Global'"].
	 *
	 * @return string|array String Do nothing if the $types is not an array.
	 */
	public function add_woo_pwd_type_in_stats( $types ) {
		if ( ! is_array( $types ) ) {
			return $types;
		}

		$types[] = sprintf( "'%s'", PPWP_Woo_Constants::WOO_PWD_TYPE );

		return $types;
	}

	/**
	 * Function to check whether to load the plugin asserts.
	 *
	 * @return bool
	 */
	public function should_load_assets() {
		if ( ! function_exists( 'get_current_screen' ) ) {
			return false;
		}

		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';

		// Only load the assets in product details page.
		return in_array( $screen_id, array( 'product' ), true );
	}

	/**
	 * Massage WooCommerce password label. By adding Order and Product text.
	 * Example: 123-345 will become Order 123 - Product 345
	 *
	 * @param string $label The current label.
	 * @param object $data  The password data include id, password, type.
	 *
	 * @return string
	 */
	public function massage_woo_password_label( $label, $data ) {
		if ( ! is_object( $data ) ) {
			return $label;
		}

		if ( isset( $data->campaign_app_type ) && PPWP_Woo_Constants::WOO_PWD_TYPE === $data->campaign_app_type ) {
			$tmp = explode( '-', $label );
			if ( count( $tmp ) < 2 ) { // Do nothing if the label is wrong format.
				return $label;
			}
			$order_idx   = 0;
			$product_idx = 1;

			return sprintf( PPWP_Woo_Constants::WOO_PWD_LABEL_FORMAT_IN_UI, $tmp[ $order_idx ], $tmp[ $product_idx ] );
		}

		return $label;
	}

	/**
	 * Massage all passwords in Stats.
	 *
	 * @param array $passwords The passwords collection.
	 *
	 * @return array
	 */
	public function massage_all_passwords( $passwords ) {
		return array_map(
			function ( $item ) {
				if ( isset( $item->label ) ) {
					$item->label = apply_filters( 'ppwp_pro_password_label', $item->label, $item );

				}

				return $item;
			},
			$passwords
		);
	}

}
