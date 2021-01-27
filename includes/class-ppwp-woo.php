<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://passwordprotectwp.com/extensions/
 * @since      1.0.0
 *
 * @package    Ppwp_Woo
 * @subpackage Ppwp_Woo/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Ppwp_Woo
 * @subpackage Ppwp_Woo/includes
 * @author     BWPS <hello@preventdirectaccess.com>
 */
class Ppwp_Woo {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Ppwp_Woo_Loader $loader Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string $plugin_name The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string $version The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'PPWP_WOO_VERSION' ) ) {
			$this->version = PPWP_WOO_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'ppwp-woo';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Ppwp_Woo_Loader. Orchestrates the hooks of the plugin.
	 * - Ppwp_Woo_i18n. Defines internationalization functionality.
	 * - Ppwp_Woo_Admin. Defines all hooks for the admin area.
	 * - Ppwp_Woo_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-ppwp-woo-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-ppwp-woo-i18n.php';

		/**
		 * The class responsible for defining the constants.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-ppwp-woo-constant.php';

		/**
		 * The class responsible for defining messages of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-ppwp-woo-message.php';

		/**
		 * The class responsible for defining helper functions.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-ppwp-woo-functions.php';

		/**
		 * The class responsible for Woo services.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/integration/class-ppwp-woo-service.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-ppwp-woo-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-ppwp-woo-public.php';


		$this->loader = new Ppwp_Woo_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Ppwp_Woo_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Ppwp_Woo_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Ppwp_Woo_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

		// Use this hook to show or hide the plugin notices.
		$this->loader->add_action( 'admin_notices', $plugin_admin, 'manage_notices' );

		if ( ppwp_woo_is_ppwp_pro_active() && ppwp_woo_is_ppwp_pro_license_valid() && ppwp_woo_is_required_plugin_version( 'PPW_PRO_VERSION', '1.2.2' ) ) {
			// Use these hooks to add product meta to store protected item.
			// Save option storing the shortcode.
			$this->loader->add_action( 'plugins_loaded', $plugin_admin, 'handle_plugins_loaded', 21 );

			$this->loader->add_action( 'woocommerce_process_product_meta_simple', $plugin_admin, 'save_protected_item_option' );
			$this->loader->add_action( 'woocommerce_process_product_meta_variable', $plugin_admin, 'save_protected_item_option' );

			$this->loader->add_action( 'woocommerce_order_item_meta_end', $plugin_admin, 'show_bypass_url', 10, 3 );

			// Add custom Woo Product tab.
			$this->loader->add_filter( 'woocommerce_product_data_tabs', $plugin_admin, 'add_restricted_tab' );


			// Next provide the corresponding tab content by hooking into the 'woocommerce_product_data_panels' action hook
			// See https://github.com/woothemes/woocommerce/blob/master/includes/admin/meta-boxes/class-wc-meta-box-product-data.php
			// for more examples of tab content
			// See https://github.com/woothemes/woocommerce/blob/master/includes/admin/wc-meta-box-functions.php for other built-in
			// functions you can call to output text boxes, select boxes, etc.
			$this->loader->add_filter( 'woocommerce_product_data_panels', $plugin_admin, 'ppwp_options_product_tab_content' );

			// These two hooks will allow Woo can override cookie expiry time of bypass URL.
			$this->loader->add_action( 'ppwp_pro_before_set_post_cookie', $plugin_admin, 'handle_before_set_post_cookie', 20, 3 );
			$this->loader->add_action( 'ppwp_pro_after_set_post_cookie', $plugin_admin, 'handle_after_set_post_cookie', 20, 3 );

			// These two hooks will allow new WooCommerce pwd type works like a charm.
			$this->loader->add_filter( 'ppwp_pro_password_types', $plugin_admin, 'add_woo_pwd_type', 20, 3 );
			$this->loader->add_filter( 'ppwp_pro_password_type_map', $plugin_admin, 'add_woo_pwd_type_label' );

			// These hooks allow to show Woo type in Stats.
			$this->loader->add_filter( 'ppwp_allowed_password_type', $plugin_admin, 'add_woo_pwd_type_in_stats' );
			$this->loader->add_filter( 'ppwp_pro_all_passwords', $plugin_admin, 'massage_all_passwords' );

			$this->loader->add_filter( 'ppwp_pro_password_label', $plugin_admin, 'massage_woo_password_label', 10, 2 );

			PPWP_Woo_Service::get_instance()->register_hooks();
		}
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new Ppwp_Woo_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Ppwp_Woo_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}
