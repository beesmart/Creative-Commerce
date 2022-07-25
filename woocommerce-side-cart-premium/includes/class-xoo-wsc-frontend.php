<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


class Xoo_Wsc_Frontend{

	protected static $_instance = null;
	public $glSettings;
	public $template_args = array();

	public static function get_instance(){
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	public function __construct(){
		$this->glSettings = xoo_wsc_helper()->get_general_option();
		$this->hooks();
	}

	public function hooks(){

		add_action( 'wp_enqueue_scripts' ,array( $this,'enqueue_styles' ) );
		add_action( 'wp_enqueue_scripts' , array( $this,'enqueue_scripts' ), 15 );
		add_action( 'wp_footer', array( $this, 'cart_markup' ) );
		add_action( 'xoo_wsc_payment_buttons', array( $this, 'paypal_button' ) );

	}


	//Enqueue stylesheets
	public function enqueue_styles(){

		if( !xoo_wsc()->isSideCartPage() ) return;

		if( !wp_style_is( 'select2' ) ){
			wp_enqueue_style( 'select2', "https://cdn.jsdelivr.net/npm/select2@4.1.0-beta.1/dist/css/select2.min.css" );
		}

		//Light slider
		if( $this->glSettings['scsp-enable'] === 'yes' && ( !wp_is_mobile() || $this->glSettings['scsp-mob-enable'] === 'yes' ) ){
			wp_enqueue_style( 'lightslider', "https://cdnjs.cloudflare.com/ajax/libs/lightslider/1.1.6/css/lightslider.css" );
		}

		//Fonts
		wp_enqueue_style( 'xoo-wsc-fonts', XOO_WSC_URL.'/assets/css/xoo-wsc-fonts.css', array(), XOO_WSC_VERSION );

		wp_enqueue_style( 'xoo-wsc-style', XOO_WSC_URL.'/assets/css/xoo-wsc-style.css', array(), XOO_WSC_VERSION );


		$inline_style =  xoo_wsc_helper()->get_template(
			'global/inline-style.php',
			array(
				'gl' => xoo_wsc_helper()->get_general_option(),
				'sy' => xoo_wsc_helper()->get_style_option(),
			),
			'',
			true
		);

		$customCSS = xoo_wsc_helper()->get_advanced_option('m-custom-css');

		wp_add_inline_style( 'xoo-wsc-style', $inline_style . $customCSS );

	}

	//Enqueue javascript
	public function enqueue_scripts(){

		if( !xoo_wsc()->isSideCartPage() ) return;

		$glSettings = $this->glSettings;

		//Shipping Calculator
		if( in_array( 'shipping_calc' , $glSettings['scf-show'] ) ){
			wp_enqueue_script( 'wc-country-select' );
			wp_enqueue_script( 'selectWoo' );
		}

		//Fly to cart
		if( $glSettings['m-flycart'] === "yes" ){
			wp_enqueue_script("jquery-effects-core");
			wp_enqueue_script('jquery-ui-core');
		}


		//Paypal express checkout
		if( $this->glSettings['scf-pec-enable'] === 'yes' ){
			wp_enqueue_script( 'wc-gateway-ppec-smart-payment-buttons' );
		}

		//Light slider for related products
		if( $glSettings['scsp-enable'] === 'yes' && ( !wp_is_mobile() || $glSettings['scsp-mob-enable'] === 'yes' ) ){
			wp_enqueue_script( 'lightslider', "https://cdnjs.cloudflare.com/ajax/libs/lightslider/1.1.6/js/lightslider.min.js", array('jquery'), XOO_WSC_VERSION, true );
		}

		wp_enqueue_script( 'xoo-wsc-main-js', XOO_WSC_URL.'/assets/js/xoo-wsc-main.js', array('jquery'), XOO_WSC_VERSION, true ); // Main JS

		$noticeMarkup = '<ul class="xoo-wsc-notices">%s</ul>';

		wp_localize_script( 'xoo-wsc-main-js', 'xoo_wsc_params', array(
			'adminurl'  			=> admin_url().'admin-ajax.php',
			'wc_ajax_url' 		  	=> WC_AJAX::get_endpoint( "%%endpoint%%" ),
			'qtyUpdateDelay' 		=> (int) $glSettings['scb-update-delay'],
			'notificationTime' 		=> (int) $glSettings['sch-notify-time'],
			'html' 					=> array(
				'successNotice' =>	sprintf( $noticeMarkup, xoo_wsc_notice_html( '%s%', 'success' ) ),
				'errorNotice'	=> 	sprintf( $noticeMarkup, xoo_wsc_notice_html( '%s%', 'error' ) ),
			),
			'strings'				=> array(
				'maxQtyError' 			=> __( 'Only %s% in stock', 'side-cart-woocommerce' ),
				'stepQtyError' 			=> __( 'Quantity can only be purchased in multiple of %s%', 'side-cart-woocommerce' ),
				'calculateCheckout' 	=> __( 'Please use checkout form to calculate shipping', 'side-cart-woocommerce' ),
				'couponEmpty' 			=> __( 'Please enter promo code', 'side-cart-woocommerce' )
			),
			'nonces' => array(
				'update_shipping_method_nonce' => wp_create_nonce( 'update-shipping-method' )
			),
			'isCheckout' 			=> is_checkout(),
			'isCart' 				=> is_cart(),
			'sliderAutoClose' 		=> true,
			'shippingEnabled' 		=> in_array( 'shipping' , $glSettings['scf-show'] ),
			'couponsEnabled' 		=> in_array( 'coupon' , $glSettings['scf-show'] ),
			'autoOpenCart' 			=> $glSettings['m-auto-open'],
			'addedToCart' 			=> xoo_wsc_cart()->addedToCart,
			'ajaxAddToCart' 		=> $glSettings['m-ajax-atc'],
			'showBasket' 			=> xoo_wsc_helper()->get_style_option('sck-enable'),
			'flyToCart' 			=> $glSettings['m-flycart'],
			'flyToCartTime' 		=> apply_filters( 'xoo_wsc_flycart_animation_time', 1500 ),
			'productFlyClass' 		=> apply_filters( 'xoo_wsc_product_fly_class', '' ),
			'refreshCart' 			=> xoo_wsc_helper()->get_advanced_option('m-refresh-cart'),
			'fetchDelay' 			=> apply_filters( 'xoo_wsc_cart_fetch_delay', 200 )
		) );
	}


	//Cart markup
	public function cart_markup(){

		if( !xoo_wsc()->isSideCartPage() ) return;

		xoo_wsc_helper()->get_template( 'xoo-wsc-markup.php' );

	}


	//Paypal button
	public function paypal_button(){
		if( $this->glSettings['scf-pec-enable'] !== 'yes' || !function_exists( 'wc_gateway_ppec' ) ) return;
		echo '<div class="widget_shopping_cart xoo-wsc-paypal-btn">';
		wc_gateway_ppec()->cart->display_mini_paypal_button();
		echo '</div>';
	}

}

function xoo_wsc_frontend(){
	return Xoo_Wsc_Frontend::get_instance();
}
xoo_wsc_frontend();