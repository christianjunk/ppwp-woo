<?php
/**
 * Created by PhpStorm.
 * User: gaupoit
 * Date: 3/17/20
 * Time: 16:20
 */

if ( class_exists( 'PPWP_Woo_SC' ) ) {
	return;
}

if ( ! class_exists( 'PPWP_Pro_Abstract_Shortcode' ) ) {
	return;
}

/**
 * Pro SideWide form shortcode.
 *
 * Class PPWP_Woo_SC
 */
class PPWP_Woo_SC extends PPWP_Pro_Abstract_Shortcode {

	/**
	 * Short code attributes.
	 *
	 * @var array
	 */
	private static $instance;

	/**
	 * Get service instance.
	 *
	 * @return PPWP_PS_General_SC|static
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
	 * Constructor
	 */
	public function __construct() {
		parent::__construct();
		add_shortcode( 'ppwp-woo', array( $this, 'render_shortcode' ) );
		$this->attributes = [
			'id'          => 0,
			'usage_limit' => null,
			'expiry_time' => null,
			'product_id'  => 0,
			'order_id'    => 0,
			'text'        => '',
			'quantity'    => 1,
		];
		$this->attributes = apply_filters( 'ppwp_woo_add_more_shortcode_attr', $this->attributes );
	}

	/**
	 * Render shortcode main function
	 *
	 * @param array  $attrs   list of attributes including password.
	 * @param string $content the content inside short code.
	 *
	 * @return string
	 */
	public function render_shortcode( $attrs, $content = null ) {

		$this->attributes = shortcode_atts(
			$this->attributes,
			$attrs
		);

		$attrs = $this->attributes;

		if ( $this->should_render_sc_content() ) {
			return $content;
		}

		$params = PPWP_Woo_Service::get_instance()->create_bypass_param( $attrs );

		$result = apply_filters( 'ppwp_woo_generate_bypass_url', false, $attrs );
		if ( false === $result ) {
			$result = PPWP_Woo_Service::get_instance()->generate_bypass_url( $attrs['id'], $params );
		}

		if ( false === $result ) {
			return $content;
		}

		$bypass_text = $this->massage_custom_text( $attrs, $result );
		do_action( 'ppwp_woo_before_render_shortcode', $params, $result, $bypass_text );
		ob_start();
		?>
		<div class="ppw-woo-link-info">
			<p><?php echo wp_kses_post( $bypass_text ); ?></p>
		</div>
		<?php
		$html = ob_get_clean();
		do_action( 'ppwp_woo_after_render_shortcode', $params, $result, $bypass_text );

		// Customize HTML hook.
		return $html;
	}

	/**
	 * Massage custom text.
	 *
	 * @param array $attrs  The shortcode attributes.
	 * @param array $result The bypass creation result.
	 *
	 * @return string
	 */
	private function massage_custom_text( $attrs, $result ) {
		$text     = trim( $attrs['text'] );
		$post_id  = $attrs['id'];
		$page_url = get_permalink( $post_id );

		$usage_limit = $result['usage_limit'];
		// Convert timestamp to string date time based on the WordPress configuration.
		$expired_date = is_numeric( $result['expired_date'] )
			? get_date_from_gmt( date( 'Y-m-d H:i:s', $result['expired_date'] ), 'F j, Y H:i:s' )
			: $result['expired_date'];
		$bypass_url   = $result['url'];
		$password     = $result['password'];

		if ( '' === $text ) {
			ob_start();
			?>
			<p>Click <a target="_blank" rel="noopener" href='<?php echo esc_html( $bypass_url ); ?>'>here</a> to view
				the restricted content</p>
			<p>Usage limit: <?php echo esc_html( $usage_limit ); ?></p>
			<p>Expiry date: <?php echo esc_html( $expired_date ); ?></p>
			<?php
			$text = ob_get_contents();
			ob_end_clean();

			return $text;
		}

		// Find all the words wrap by %. Example %abc%.
		preg_match_all( '/\%+[\w\s]+\%/', $text, $matches );
		foreach ( $matches as $match ) {
			if ( empty( $match ) ) {
				continue;
			}
			$val  = preg_replace( '/%/', '', $match[0] );
			$link = sprintf(
				'<a target=\'_blank\' ref="noopener" href="%1$s">%2$s</a>',
				$bypass_url,
				$val
			);
			$text = str_replace( $match[0], $link, $text );
		}

		$text = preg_replace( '/{usage_limit}/', $usage_limit, $text );
		$text = preg_replace( '/{expiration_time}/', $expired_date, $text );
		$text = preg_replace( '/{access_link}/', $bypass_url, $text );
		$text = preg_replace( '/{password}/', $password, $text );
		$text = preg_replace( '/{page_url}/', $page_url, $text );

		return $text;
	}


	/**
	 * Check whether the shortcode content should render.
	 *
	 * @return bool
	 */
	public function should_render_sc_content() {
		$attrs = $this->attributes;

		$conditions    = empty( $attrs['id'] ) || empty( $attrs['product_id'] ) || empty( $attrs['order_id'] );
		$should_render = apply_filters( 'ppwp_woo_should_render_sc_content', $conditions, $attrs );
		if ( $should_render ) {
			return true;
		}

		return false;
	}

}
