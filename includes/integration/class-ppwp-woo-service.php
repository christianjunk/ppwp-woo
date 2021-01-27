<?php
/**
 * Created by PhpStorm.
 * User: gaupoit
 * Date: 4/7/20
 * Time: 18:20
 */

if ( class_exists( 'PPWP_Woo_Service' ) ) {
	return;
}

/**
 * Woo services class.
 * Class Ppwp_Woo_Service
 */
class PPWP_Woo_Service {

	/**
	 * Service instance.
	 *
	 * @var PPWP_Woo_Service
	 */
	private static $instance;

	/**
	 * @var bool
	 */
	private $is_protected = null;

	/**
	 * @var integer
	 */
	private $shop_page_id = null;

	/**
	 * @var null|bool
	 */
	private $is_shop = null;

	/**
	 * @var null|bool
	 */
	private $is_product = null;

	/**
	 * @var null|bool
	 */
	private $is_product_category = null;

	/**
	 * @var null|PPW_Pro_Password_Services
	 */
	private $ppwp_password_services = null;


	/**
	 * Get service instance
	 *
	 * @return PPWP_Woo_Service
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			// Use static instead of self due to the inheritance later.
			// For example: ChildSC extends this class, when we call get_instance
			// it will return the object of child class. On the other hand, self function
			// will return the object of base class.
			self::$instance = new static();
		}

		return self::$instance;
	}

	/**
	 * Register hooks for shop
	 *
	 * @since 1.1.0 Init function.
	 */
	public function register_hooks() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}
		if ( ! class_exists( 'PPW_Pro_Password_Services' ) ) {
			return;
		}

		$this->ppwp_password_services = new PPW_Pro_Password_Services();

		add_action( 'template_redirect', array( $this, 'handle_bypass_url_for_shop' ), 8 );
		add_action( 'wp_head', array( $this, 'check_shop_is_protected' ) );
		add_action( 'woocommerce_before_shop_loop', array( $this, 'display_password_form_before_shop_loop' ), 10 );
		add_action( 'woocommerce_before_single_product', array( $this, 'display_password_form_on_product' ), 10 );
	}

	/**
	 * Check shop page is protected.
	 *
	 * @param integer $shop_page_id Shop page ID.
	 *
	 * @return bool
	 * @since 1.1.0 Init function.
	 */
	private function is_access_shop_page( $shop_page_id ) {
		/**
		 * Has user created shop page ?
		 */
		if ( $shop_page_id <= 0 ) {
			return false;
		}

		if ( is_null( $this->is_protected ) ) {
			$this->is_protected = apply_filters( 'ppwp_woo_is_access_shop_page', post_password_required( $shop_page_id ), $shop_page_id );
		}

		return $this->is_protected;
	}

	/**
	 * Is WooCommerce shop.
	 *
	 * @since 1.1.0 Init function.
	 */
	private function get_is_shop() {
		if ( is_null( $this->is_shop ) ) {
			$this->is_shop = call_user_func( 'is_shop' );
		}

		return $this->is_shop;
	}

	/**
	 * Is WooCommerce product.
	 *
	 * @since 1.1.0 Init function.
	 */
	private function get_is_product() {
		if ( is_null( $this->is_product ) ) {
			$this->is_product = call_user_func( 'is_product' );
		}

		return $this->is_product;
	}

	/**
	 * Get shop page ID.
	 *
	 * @since 1.1.0 Init function.
	 */
	private function get_shop_page_id() {
		// Try to get shop page ID because user can use shortcode to display product.
		if ( is_null( $this->shop_page_id ) ) {
			$this->shop_page_id = call_user_func( 'wc_get_page_id', 'shop' );
		}

		return $this->shop_page_id;
	}

	/**
	 * Is product category
	 *
	 * @since 1.1.0 Init function.
	 */
	private function get_is_product_category() {
		if ( is_null( $this->is_product_category ) ) {
			$this->is_product_category = call_user_func( 'is_product_category' );
		}

		return $this->is_product_category;
	}

	/**
	 * Check is WooCommerce page.
	 *
	 * @return bool
	 *
	 * @since 1.1.0 Init function.
	 */
	private function is_woo_page() {
		return $this->get_is_shop() || $this->get_is_product() || $this->get_is_product_category();
	}

	/**
	 * Get current URL from request.
	 *
	 * @param integer $shop_page_id Shop page ID.
	 *
	 * @return string
	 *
	 * @since 1.1.0 Init function.
	 */
	private function get_current_url( $shop_page_id ) {
		if ( $this->get_is_shop() ) {
			return get_permalink( $shop_page_id );
		} elseif ( $this->get_is_product() ) {
			return get_permalink();
		}

		global $wp;

		return home_url( $wp->request );
	}

	/**
	 * Get bypass parameter from Access Link.
	 *
	 * @return string
	 *
	 * @since 1.1.0 Init function.
	 */
	private function get_bypass_param() {
		if ( defined( 'PPW_Pro_Constants::BYPASS_PARAM' ) ) {
			return PPW_Pro_Constants::BYPASS_PARAM;
		}

		return 'ppwp_ac';
	}

	/**
	 * Handle Bypass URL for Woo page protected by shop.
	 *
	 * @since 1.1.0 Init function.
	 */
	public function handle_bypass_url_for_shop() {
		$bypass_param = $this->get_bypass_param();
		if ( ! isset( $_GET[ $bypass_param ] ) ) {
			return;
		}
		$token = $_GET[ $bypass_param ];

		if ( ! $this->is_woo_page() ) {
			return;
		}

		$shop_page_id = $this->get_shop_page_id();

		// Get request URL of user sent.
		$current_url = $this->get_current_url( $shop_page_id );

		if ( false === $current_url ) {
			return;
		}

		if ( $this->ppwp_password_services->is_protected_content( $shop_page_id ) ) {
			// Decode bypass url to get password. If it is valid then save cookie.
			$this->ppwp_password_services->check_valid_bypass_url( $shop_page_id, $token );
		}

		wp_safe_redirect( $current_url );
		exit();
	}


	/**
	 * Check shop is protected.
	 *
	 * @since 1.1.0 Init function.
	 */
	public function check_shop_is_protected() {
		// Current page is shop page or product page.
		if ( ! $this->is_woo_page() ) {
			return;
		}

		$shop_page_id = $this->get_shop_page_id();
		if ( ! $this->ppwp_password_services->is_protected_content( $shop_page_id ) ) {
			return;
		}

		// Wrapper for nocache_headers which also disables page caching from WooCommerce function.
		call_user_func( 'wc_nocache_headers' );

		$is_using_noindex = call_user_func( 'ppw_core_get_setting_type_bool', 'ppwp_remove_search_engine' );
		if ( $is_using_noindex ) {
			wp_no_robots();
		}
	}

	/**
	 * Display password form.
	 *
	 * @return bool
	 */
	private function display_password_form() {
		if ( ! function_exists( 'ppw_core_render_login_form' ) ) {
			return false;
		}

		if ( ! $this->is_woo_page() ) {
			return false;
		}

		$shop_page_id = $this->get_shop_page_id();

		if ( ! $this->is_access_shop_page( $shop_page_id ) ) {
			return false;
		}

		/**
		 * When shop page loads, global post ID is the first post ID in the loop.
		 * So we must change it to shop page ID.
		 */
		global $post;
		$current_post_id = $post->ID;
		$post->ID        = $shop_page_id;
		$form            = call_user_func( 'ppw_core_render_login_form' );
		$post->ID        = $current_post_id;

		/**
		 * Because when user access to shop page then current page id is first product page.
		 * So we must replace it to get protection information from Shop Page.
		 */
		echo $form;

		return true;
	}

	/**
	 * Protect WooCommerce shop.
	 *
	 * @since 1.1.0 Init function.
	 */
	public function display_password_form_before_shop_loop() {
		if ( ! $this->display_password_form() ) {
			return;
		}
		do_action( 'woocommerce_after_main_content' );
		echo get_footer( 'shop' );

		exit();
	}

	/**
	 * Protect WooCommerce product.
	 *
	 * @since 1.1.0 Init function.
	 */
	public function display_password_form_on_product() {
		if ( ! $this->display_password_form() ) {
			return;
		}
		do_action( 'woocommerce_after_single_product' );
		echo get_footer( 'shop' );

		exit();
	}

	/**
	 * Render by pass URL short code.
	 *
	 * @param int    $item_id Order Item ID.
	 * @param object $item    Item object.
	 * @param object $order   Order object.
	 *
	 * @return string
	 */
	public function render_bypass_url_sc( $item_id, $item, $order ) {

		do_action( 'ppwp_woo_before_render_bypass_url_shortcode', $item_id, $item_id, $order );

		$product = $item->get_product();
		// Only support for virtual (no shipping) product. Can extend the condition by using hook.
		$supported_product = apply_filters( 'ppwp_woo_supported_product_condition', $product->is_virtual() );
		if ( ! $supported_product ) {
			return '';
		}

		$product_id = $product->get_id();

		$post_id     = get_post_meta( $product_id, '_ppwp_woo_protected_post', true );
		$usage_limit = get_post_meta( $product_id, '_ppwp_woo_usage_limit', true );
		$expiration  = get_post_meta( $product_id, '_ppwp_woo_expiration', true );
		$custom_text = $this->escape_shortcode_attrs( get_post_meta( $product_id, '_ppwp_woo_custom_text', true ) );
		$attributes  = sprintf(
			'id=%1$d product_id=%2$d usage_limit=%3$d expiry_time=%4$d order_id=%5$d text="%6$s"',
			$post_id,
			$product_id,
			$usage_limit,
			$expiration,
			$order->get_id(),
			$custom_text
		);

		// Fire hook here than can change the shortcode attributes.
		$attributes = apply_filters( 'ppwp_woo_bypass_shortcode_attr', $attributes, $item_id, $product_id, $item, $order );

		$sc = sprintf( '[ppwp-woo %s]', $attributes );
		// Fire hook here than can change the shortcode.
		$sc = apply_filters( 'ppwp_woo_bypass_shortcode', $sc, $item_id, $item, $order );

		do_action( 'ppwp_woo_before_return_bypass_url_shortcode', $item_id, $item_id, $order );

		return wp_kses_post( wpautop( do_shortcode( $sc ) ) );
	}

	/**
	 * Use HTML AScii to escape [, ] character (http://www.ascii.cl/htmlcodes.htm)
	 *
	 * @param string $value Shortcode value.
	 *
	 * @return string
	 */
	private function escape_shortcode_attrs( $value ) {
		return str_replace( [ '[', ']' ], [ '&#91;', '&#93;' ], $value );
	}

	/**
	 * Add custom protected tab.
	 *
	 * @param array $tabs Product tabs.
	 *
	 * @return array
	 */
	public function add_restricted_tab( $tabs ) {
		$tabs['ppwp_woo'] = array(
			'label'  => Ppwp_Woo_Message_Manager::get_text()['TAB_NAME'],
			'target' => 'ppwp_woo_options',
			'class'  => array( 'show_if_virtual' ),
		);

		return $tabs;
	}

	/**
	 * Tab content.
	 */
	public function add_restricted_tab_contents() {
		global $woocommerce, $post;
		$common_inline_style = PPWP_Woo_Constants::WOO_TAB_FIELD_COMMON_INLINE_STYLE;

		// Fire hook that can replace or extend common inline style in field Woo tab.
		$common_inline_style = apply_filters( 'ppwp_woo_field_tab_common_inline_style', $common_inline_style );
		?>
		<!-- id below must match target registered in above add_restricted_tab function -->
		<div id="ppwp_woo_options" class="panel woocommerce_options_panel">
			<p>
				<?php echo Ppwp_Woo_Message_Manager::get_text()['TAB_DESC']; // phpcs:ignore ?>
			</p>
			<?php
			$this->woocommerce_wp_select_protection_type( $common_inline_style );
			$this->woocommerce_wp_select_protected_post( $common_inline_style );
			$fields = $this->get_tab_fields( $common_inline_style );
			foreach ( $fields as $field ) {
				if ( isset( $field['visible'] ) && false === $field['visible'] ) {
					?>
					<div style="display: none" data-type="ppwp-woo-type-<?php echo esc_attr( $field['type'] ); ?>">
						<?php call_user_func( $field['callback'], $field['data'] ); ?>
					</div>
					<?php
				} else {
					call_user_func( $field['callback'], $field['data'] );
				}
			}
			?>
		</div>
		<?php
	}

	/**
	 * Get Woo tab fields
	 *
	 * @param string $common_inline_style Common inline style.
	 *
	 * @return array
	 */
	private function get_tab_fields( $common_inline_style ) {
		$fields = array(
			array(
				'data'     => array(
					'id'                => '_ppwp_woo_usage_limit',
					'label'             => __( 'Usage limit', 'ppwp-woo' ),
					'desc_tip'          => false,
					'description'       => Ppwp_Woo_Message_Manager::get_text()['USAGE_LIMIT_TOOLTIP'],
					'type'              => 'number',
					'custom_attributes' => array(
						'min'  => '1',
						'step' => '1',
					),
					'class'             => 'ppwp-woo-custom-input',
					'wrapper_class'     => 'ppwp-woo-field',
					'style'             => $common_inline_style,
				),
				'callback' => 'woocommerce_wp_text_input',
				'visible'  => true,
			),
			array(
				'data'     => array(
					'id'                => '_ppwp_woo_expiration',
					// translators: %s Expiration unit.
					'label'             => Ppwp_Woo_Message_Manager::get_text()['EXPIRY_DATE_LABEL'],
					'desc_tip'          => false,
					'description'       => Ppwp_Woo_Message_Manager::get_text()['EXPIRY_DATE_TOOLTIP'],
					'type'              => 'number',
					'custom_attributes' => array(
						'min'  => '1',
						'step' => '1',
					),
					'class'             => 'ppwp-woo-custom-input',
					'wrapper_class'     => 'ppwp-woo-field',
					'style'             => $common_inline_style,
				),
				'callback' => 'woocommerce_wp_text_input',
				'visible'  => true,
			),
			array(
				'data'     => array(
					'id'                => '_ppwp_woo_custom_text',
					'label'             => Ppwp_Woo_Message_Manager::get_text()['CUSTOM_TEXT_LABEL'],
					'placeholder'       => Ppwp_Woo_Message_Manager::get_text()['CUSTOM_TEXT_PLACEHOLDER'],
					'description'       => Ppwp_Woo_Message_Manager::get_text()['CUSTOM_TEXT_TOOLTIP'],
					'custom_attributes' => array(
						'cols' => 50,
					),
					'class'             => 'ppwp-woo-custom-textarea',
					'wrapper_class'     => 'ppwp-woo-field',
					'style'             => $common_inline_style . ';height:5.5em',
				),
				'callback' => 'woocommerce_wp_textarea_input',
				'visible'  => true,
			),
		);

		return apply_filters( 'ppwp_woo_tab_fields', $fields );
	}


	/**
	 * Render the protected post Woo select.
	 *
	 * @param string $common_inline_style Common inline style.
	 */
	private function woocommerce_wp_select_protected_post( $common_inline_style ) {
		$options = [
			'0' => Ppwp_Woo_Message_Manager::get_text()['DEFAULT_OPTION_LABEL'],
		];

		if ( function_exists( 'ppw_core_get_setting_type_array' ) ) {
			$types = array( 'page', 'post' );
			$types = array_merge( $types, ppw_core_get_setting_type_array( PPW_Pro_Constants::WPP_WHITELIST_COLUMN_PROTECTIONS ) );
			$posts = $this->get_all_protected_posts( $types );
			foreach ( $posts as $post ) {
				$options[ $post->post_id ] = empty( $post->post_title ) ? '(no title)' : $post->post_title;
			}
		}

		// Fire the hook here that can change the password post options in another extensions.
		$options = apply_filters( 'ppwp_woo_password_protect_post_options', $options );

		?>
		<div class="ppwp-woo-protection-content" style="display: none" data-type="ppwp-woo-type-ppf">
			<?php
			woocommerce_wp_select(
				array(
					'id'      => '_ppwp_woo_protected_post',
					'label'   => Ppwp_Woo_Message_Manager::get_text()['PROTECTED_POST_LABEL'],
					'options' => $options,
					'style'   => $common_inline_style,
				)
			);
			?>
		</div>
		<?php
	}

	/**
	 * Render the protection types select box.
	 *
	 * @param string $common_inline_style Common inline style.
	 */
	private function woocommerce_wp_select_protection_type( $common_inline_style ) {
		$options = [
			'ppf' => __( 'Individual pages', 'ppwp-woo' ),
		];

		$options = apply_filters( 'ppwp_woo_protection_type', $options );

		woocommerce_wp_select(
			array(
				'id'      => '_ppwp_woo_protection_type',
				'label'   => Ppwp_Woo_Message_Manager::get_text()['PROTECTION_TYPE_LABEL'],
				'options' => $options,
				'style'   => $common_inline_style,
			)
		);
	}

	/**
	 * Copy from https://stackoverflow.com/questions/23287358/woocommerce-multi-select-for-single-product-field.
	 *
	 * @param array $post Options.
	 */
	private function woocommerce_wp_select_multiple( $post ) {
		?>
		<p class="form-field">
		<label for="grouped_products"><?php esc_html_e( 'Protected page', 'woocommerce' ); ?></label>
		<select class="wc-product-search" multiple="multiple" style="width: 50%;" id="ppwp_protected_pages"
		        name="ppwp_protected_pages[]" data-sortable="true"
		        data-placeholder="<?php esc_attr_e( 'Search for a post&hellip;', 'woocommerce' ); ?>"
		        data-action="woocommerce_json_search_products" data-exclude="<?php echo intval( $post->ID ); ?>">
		</select> <?php echo wc_help_tip( __( 'This lets you choose which products are part of this group.', 'woocommerce' ) ); // WPCS: XSS ok. ?>
		</p><?php
	}

	/**
	 * Saved options.
	 *
	 * @param int $post_id The product ID.
	 */
	public function saved_options( $post_id ) {
		$keys = array(
			'_ppwp_woo_protected_post',
			'_ppwp_woo_usage_limit',
			'_ppwp_woo_expiration',
			'_ppwp_woo_custom_text',
			'_ppwp_woo_protection_type',
		);

		foreach ( $keys as $key ) {
			if ( isset( $_POST[ $key ] ) ) { // phpcs:ignore
				update_post_meta( $post_id, $key, $_POST[ $key ] ); // phpcs:ignore
			}
		}

		// Fire hook that can update more tab options.
		do_action( 'ppwp_woo_save_restricted_tab_options', $post_id, $_POST ); // phpcs:ignore
	}

	/**
	 * Create bypass params.
	 *
	 * @param array $attrs Including
	 *                     label (combine order_id and product_id)
	 *                     usage_limit
	 *                     expired_date.
	 *
	 * @return array
	 */
	public function create_bypass_param( $attrs ) {
		$params = [
			'label' => sprintf( PPWP_Woo_Constants::WOO_PWD_LABEL_FORMAT, $attrs['order_id'], $attrs['product_id'] ),
		];

		if ( isset( $attrs['usage_limit'] ) && ! empty( $attrs['usage_limit'] ) ) {
			$params['usage_limit'] = $attrs['usage_limit'];
		}

		// In DB we are using expired_date, should pass the valid attribute.
		if ( isset( $attrs['expiry_time'] ) && ! empty( $attrs['expiry_time'] ) ) {
			$params['expired_date'] = $attrs['expiry_time'];
		}

		return $params;
	}

	/**
	 * Copy from this post: https://passwordprotectwp.com/rest-api/generate-password-bypass-url/
	 * TODO: need to bring this function to PPWP Pro.
	 *
	 * @param int   $post_id    The protected post ID.
	 * @param array $parameters The password metadata.
	 *
	 * @return bool|array
	 */
	public function generate_bypass_url( $post_id, $parameters = array() ) {
		if ( ! function_exists( 'ppw_encrypt_decrypt' ) || ! method_exists( 'PPW_Pro_Repository', 'add_new_password' ) || ! function_exists( 'generate_pwd' ) ) {
			return false;
		}
		if ( ! $post_id ) {
			return false;
		}

		$ppwp_repo = new PPW_Pro_Repository();
		// Do nothing if it's not protected post.
		if ( ! $ppwp_repo->is_protected_item( $post_id ) ) {
			return false;
		}

		$post_url = get_permalink( $post_id );
		if ( ! $post_url ) {
			return false;
		}

		// Try to find the password with label (containing product ID and order ID) and type is WooCommerce.
		$existing_passwords = $this->get_passwords_by_post_id_and_label( $post_id, $parameters['label'] );
		if ( empty( $existing_passwords ) ) {
			// Do nothing if not in white list post types.
			$post_type = get_post_type( $post_id );
			if ( ! in_array( $post_type, $this->get_selected_post_types(), true ) ) {
				return false;
			}

			$password     = generate_pwd();
			$expired_date = isset( $parameters['expired_date'] ) ? $this->get_expiration_timestamp( $parameters['expired_date'], PPWP_Woo_Constants::get_expiration_unit() ) : null;
			$usage_limit  = isset( $parameters['usage_limit'] ) ? $parameters['usage_limit'] : null;

			$result = $ppwp_repo->add_new_password(
				array(
					'password'          => $password,
					'created_time'      => time(),
					'campaign_app_type' => PPWP_Woo_Constants::get_password_type(),
					'usage_limit'       => $usage_limit,
					'expired_date'      => $expired_date,
					'label'             => isset( $parameters['label'] ) ? $parameters['label'] : '',
					'post_id'           => $post_id,
				)
			);
			if ( ! $result ) {
				return false;
			}
		} else {
			// Get lasted passwords if they are duplicated.
			$item         = array_pop( $existing_passwords );
			$password     = $item->password;
			$expired_date = $item->expired_date;
			$usage_limit  = $item->usage_limit;
		}

		$token = ppw_encrypt_decrypt( 'encrypt', array( 'password' => $password ) );
		if ( strpos( $post_url, '?' ) ) {
			$bypass_url = $post_url . '&' . PPW_Pro_Constants::BYPASS_PARAM . '=' . $token;
		} else {
			$bypass_url = $post_url . '?' . PPW_Pro_Constants::BYPASS_PARAM . '=' . $token;
		}

		return array(
			'url'          => $bypass_url,
			'usage_limit'  => isset( $usage_limit ) ? $usage_limit : 'Unlimited',
			'expired_date' => isset( $expired_date ) ? $expired_date : 'Never',
		);
	}

	/**
	 * Get passwords by post_id and label.
	 * TODO: need to bring this function into PPWP Pro to improve the query performance.
	 *
	 * @param int    $post_id The post ID.
	 * @param string $label   The password label.
	 *
	 * @return array
	 */
	private function get_passwords_by_post_id_and_label( $post_id, $label ) {
		$ppwp_repo = new PPW_Pro_Repository();
		$passwords = $ppwp_repo->get_password_info_by_post_id( $post_id );

		return array_filter(
			$passwords,
			function ( $password ) use ( $label ) {
				return $password->label === $label;
			}
		);
	}

	/**
	 * Get expiration timestamp.
	 *
	 * @param int    $expiration Number to expire.
	 * @param string $unit       The timestamp unit day, minutes, hours.
	 *
	 * @return int
	 */
	private function get_expiration_timestamp( $expiration, $unit = 'day' ) {
		$curr_date    = new DateTime();
		$expired_date = $curr_date->modify( intval( $expiration ) . " $unit" );

		return $expired_date->getTimestamp();
	}

	/**
	 * Get protected posts with chosen post type protecton.
	 *
	 * @return mixed
	 */
	public function get_all_protected_posts( $post_types ) {
		global $wpdb;

		$meta_table = $wpdb->prefix . 'postmeta';
		$post_table = $wpdb->prefix . 'posts';


		$query_include = "'" . implode( "','", $post_types ) . "'";
		$sql           = "SELECT m.post_id, p.post_title FROM $meta_table AS m
					JOIN $post_table AS p ON m.post_id = p.ID
					AND m.meta_key = %s
					AND m.meta_value = %s AND p.post_type IN ($query_include)";

		$query  = $wpdb->prepare(
			$sql,
			PPW_Pro_Constants::AUTO_GENERATE_PWD_META_DATA,
			'true'
		); // phpcs:ignore
		$result = $wpdb->get_results( $query ); // phpcs:ignore

		return $result;

	}

	/**
	 * Get selected post types in settings.
	 *
	 * @return array
	 */
	private function get_selected_post_types() {
		$types = array( 'page', 'post' );
		$types = array_merge( $types, ppw_core_get_setting_type_array( PPW_Pro_Constants::WPP_WHITELIST_COLUMN_PROTECTIONS ) );

		return $types;
	}
}
