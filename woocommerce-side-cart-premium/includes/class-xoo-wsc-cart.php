<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


class Xoo_Wsc_Cart{

	protected static $_instance = null;

	public $notices = array();
	public $glSettings;
	public $coupons = array();
	public $addedToCart = false;
	public $bundleItems = array();


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

		add_action( 'wc_ajax_xoo_wsc_update_item_quantity', array( $this, 'update_item_quantity' ) );

		add_action( 'wc_ajax_xoo_wsc_undo_item', array( $this, 'undo_item' ) );

		add_action( 'wc_ajax_xoo_wsc_refresh_fragments', array( $this, 'get_refreshed_fragments' ) );

		add_filter( 'woocommerce_add_to_cart_fragments', array( $this, 'set_ajax_fragments' ) );
		
		add_filter( 'woocommerce_update_order_review_fragments', array( $this, 'set_ajax_fragments' ) );

		add_action( 'wc_ajax_xoo_wsc_calculate_shipping', array( $this, 'calculate_shipping' ) );

		add_action( 'wc_ajax_xoo_wsc_update_shipping_method', array( $this, 'update_shipping_method' ) );

		add_action( 'wc_ajax_xoo_wsc_apply_coupon', array( $this, 'apply_coupon' ) );

		add_action( 'wc_ajax_xoo_wsc_remove_coupon', array( $this, 'remove_coupon' ) );

		add_action( 'wc_ajax_xoo_wsc_add_to_cart', array( $this, 'add_to_cart' ) );

		add_action( 'wc_ajax_xoo_wsc_empty_cart', array( $this, 'empty_cart' ) );

		add_action( 'woocommerce_add_to_cart', array( $this, 'added_to_cart' ), 10, 6 );

		add_filter( 'pre_option_woocommerce_cart_redirect_after_add', array( $this, 'prevent_cart_redirect' ), 20 );

	}


	public function empty_cart(){
		WC()->cart->empty_cart();
		$this->set_notice( __( 'Cart Emptied', 'side-cart-woocommerce' ), 'sucess' );
		$this->get_refreshed_fragments();
	}

	public function prevent_cart_redirect( $value ){
		if( $this->glSettings['m-ajax-atc'] === "yes" ) return 'no';
		return $value;
	}

	/* Add to cart is performed by woocommerce as 'add-to-cart' is passed */
	public function add_to_cart(){

		if( !isset( $_POST['add-to-cart'] ) ) return;
		
		if( empty( wc_get_notices( 'error' ) ) ){
			// trigger action for added to cart in ajax
			do_action( 'woocommerce_ajax_added_to_cart', intval( $_POST['add-to-cart'] ) );
		}

		$this->get_refreshed_fragments();

	}


	public function added_to_cart( $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data ){
		$this->set_notice( __( 'Item added to cart', 'side-cart-woocommerce' ), 'sucess' );
		$this->addedToCart = 'yes';
	}


	public function set_notice( $notice, $type = 'success' ){
		$this->notices[] = xoo_wsc_notice_html( $notice, $type );
	}



	public function update_shipping_method(){

		$chosen_shipping_methods = WC()->session->get( 'chosen_shipping_methods' );
		$posted_shipping_methods = isset( $_POST['shipping_method'] ) ? wc_clean( wp_unslash( $_POST['shipping_method'] ) ) : array();

		if ( is_array( $posted_shipping_methods ) ) {
			foreach ( $posted_shipping_methods as $i => $value ) {
				$chosen_shipping_methods[ $i ] = $value;
			}
		}

		WC()->session->set( 'chosen_shipping_methods', $chosen_shipping_methods );

		$this->set_notice( __( 'Shipping updated', 'side-cart-woocommerce' ), 'success' );

		$this->get_refreshed_fragments();
	}


	public function print_notices_html( $section = 'cart', $wc_cart_notices = true ){

		if( isset( $_POST['noticeSection'] ) && $_POST['noticeSection'] !== $section ) return;

		if( $wc_cart_notices ){

			do_action( 'woocommerce_check_cart_items' );

			//Add WC notices
			$wc_notices = wc_get_notices( 'error' );

			foreach ( $wc_notices as $wc_notice ) {
				$this->set_notice( $wc_notice['notice'], 'error' );
			}

			wc_clear_notices();

		}

		$notices = apply_filters( 'xoo_wsc_notices_before_print', $this->notices, $section );

		$notices_html = sprintf( '<div class="xoo-wsc-notice-container" data-section="%1$s"><ul class="xoo-wsc-notices">%2$s</ul></div>', $section, implode( '' , $notices )  );

		echo apply_filters( 'xoo_wsc_print_notices_html', $notices_html, $notices, $section );
		
		$this->notices = array();

	}




	public function update_item_quantity(){


		$cart_key 	= sanitize_text_field( $_POST['cart_key'] );
		$new_qty 	= (float) $_POST['qty'];

		if( !is_numeric( $new_qty ) || $new_qty < 0 || !$cart_key ){
			$this->set_notice( __( 'Something went wrong', 'side-cart-woocommerce' ) );
		}
		
		$validated = apply_filters( 'xoo_wsc_update_quantity', true, $cart_key, $new_qty );

		if( $validated && !empty( WC()->cart->get_cart_item( $cart_key ) ) ){

			$updated = $new_qty == 0 ? WC()->cart->remove_cart_item( $cart_key ) : WC()->cart->set_quantity( $cart_key, $new_qty );

			if( $updated ){

				if( $new_qty == 0 ){

					$notice = __( 'Item removed', 'side-cart-woocommerce' );

					$notice .= '<span class="xoo-wsc-undo-item" data-key="'.$cart_key.'">'.__('Undo?','side-cart-woocommerce').'</span>';  

				}
				else{
					$notice = __( 'Item updated', 'side-cart-woocommerce' );
				}

				$this->set_notice( $notice, 'success' );
				
			}
		}


		$this->get_refreshed_fragments();

		die();
	}


	public function undo_item(){

		$cart_key 	= sanitize_text_field( $_POST['cart_key'] );

		if( !$cart_key ) return;

		$validated = apply_filters( 'xoo_wsc_undo_item', true, $cart_key );

		if( $validated ){

			$updated = WC()->cart->restore_cart_item( $cart_key );

			if( $updated ){
				$this->set_notice( __( 'Item added back', 'side-cart-woocommerce' ), 'success' );
			}
		}

		$this->get_refreshed_fragments();

		die();
	}


	public function calculate_shipping(){
		WC_Shortcode_Cart::calculate_shipping();
		if( !wc_notice_count('error') ){
			$this->set_notice( __( 'Address updated', 'side-cart-woocommerce' ) );
		}
		$this->get_refreshed_fragments();
	}


	public function set_ajax_fragments($fragments){

		WC()->cart->calculate_totals();
		
		ob_start();
		xoo_wsc_helper()->get_template( 'xoo-wsc-container.php' );
		$container = ob_get_clean();

		ob_start();
		xoo_wsc_helper()->get_template( 'xoo-wsc-slider.php' );
		$slider = ob_get_clean();

		ob_start();
		xoo_wsc_helper()->get_template( 'xoo-wsc-shortcode.php' );
		$shortcode = ob_get_clean();

		$fragments['div.xoo-wsc-container'] = $container; //Cart content
		$fragments['div.xoo-wsc-slider'] 	= $slider;// Slider
		$fragments['div.xoo-wsc-sc-cont'] 	= $shortcode;
		
		return $fragments;

	}

	public function get_refreshed_fragments(){
		WC_AJAX::get_refreshed_fragments();
	}


	public function get_cart_count(){
		if( $this->glSettings['m-bk-count'] === 'items' ){
			return count( WC()->cart->get_cart() );
		}
		else{
			return WC()->cart->get_cart_contents_count();
		}
	}


	public function get_coupons(){

		if( !empty( $this->coupons ) ){
			return $this->coupons;
		}


		$showCoupon = $this->glSettings['m-cp-list'];

		if( $showCoupon === 'hide' ) return array();

		if( !trim($this->glSettings['m-cp-custom']) ){
			$includes = array();
		}
		else{
			$includes = array_map( 'trim', explode( ',', $this->glSettings['m-cp-custom'] ) );
		}


		$args = array(
		    'posts_per_page'   	=> (int) $this->glSettings['m-cp-count'],
		    'orderby'          	=> 'title',
		    'order'            	=> 'asc',
		    'post_type'        	=> 'shop_coupon',
		    'post_status'      	=> 'publish',
		    'include'			=> $includes
		);
		    
		$coupons_post = get_posts( $args );

		if( empty( $coupons_post ) ) return array();

		$coupons = array( 'valid' => array(), 'invalid' => array() );

		$hide_for_error_codes = array(
			105, //Not exists.
			107, //Expired
		);

		$hide_for_error_codes = apply_filters( 'xoo_wsc_coupon_hide_invalid_codes', $hide_for_error_codes );

		foreach ( $coupons_post as $coupon_post ) {

			$coupon = new WC_Coupon( $coupon_post->ID );

			$discounts 	= new WC_Discounts( WC()->cart );
			$valid     	= $discounts->is_coupon_valid( $coupon );
			$code 		= $coupon->get_code();

			$off_amount = $coupon->get_amount();

			$off_value 	= 'percent' === $coupon->get_discount_type() ? $off_amount.'%' : wc_price( $off_amount ); 

			$data = array(
				'code' 		=> $code,
				'coupon' 	=> $coupon,
				'notice' 	=> '',
				'off_value' => $off_value
			);

			if( is_wp_error( $valid ) ){

				if( $showCoupon !== 'all' ) continue;

				$error_code = $valid->get_error_code();

				if( in_array( $error_code , $hide_for_error_codes ) ) continue;

				$data['notice'] = $valid->get_error_message();

			}

			$coupons[ is_wp_error( $valid ) ? 'invalid' : 'valid' ][] = $data;

		}


		$coupons = $this->coupons = apply_filters( 'xoo_wsc_coupons_list', $coupons );

		return $coupons;
	}


	public function apply_coupon(){

		if( !isset( $_POST['coupon'] ) || !$_POST['coupon'] ) return;

		if( WC()->cart->apply_coupon( $_POST['coupon'] ) ){
			$this->set_notice( sprintf( __( '%s applied successfully', 'side-cart-woocommerce' ), strtoupper( esc_attr( $_POST['coupon'] ) ) ) );
		}

		$this->get_refreshed_fragments();
	}


	public function remove_coupon(){
		WC()->cart->remove_coupon( $_POST['coupon'] );
		$this->set_notice( __( 'Coupon has been removed', 'side-cart-woocommerce' ) );
		$this->get_refreshed_fragments();
	}



	public function get_totals(){

		$totals = array();

		if( WC()->cart->is_empty() ) return $totals;

		$sy 			= xoo_wsc_helper()->get_style_option();
		$gl 			= xoo_wsc_helper()->get_general_option();

		$show 			= $gl['scf-show'];
		$showSubtotal 	= in_array( 'subtotal', $show );
		$showCoupon 	= in_array( 'coupon', $show );
		$showShipping 	= in_array( 'shipping', $show );
		$showCalculator = in_array( 'shipping_calc' , $show );
		$showDiscount 	= in_array( 'discount' , $show );
		$showFee 		= in_array( 'fee', $show );
		$showTax 		= in_array( 'tax', $show );
		$showTotal 		= in_array( 'total', $show );

		if( $showSubtotal ){
			$totals['subtotal'] = array(
				'label' 	=> xoo_wsc_helper()->get_general_option('sct-subtotal'),
				'value' 	=> WC()->cart->get_cart_subtotal(),
			);
		}

		if( $showShipping ){
			
			$label 				= __( 'Shipping', 'side-cart-woocommerce' );
			$calculatorToggle 	= '<span class="xoo-wsc-toggle-slider" data-slider="shipping">%s</span>';
			$value 				= '';

			$packages = WC()->shipping()->get_packages();

			if( !empty( $packages ) ){

				//Support for 1 package only
				$package = $packages[0];

				$available_methods = $package['rates'];


				if( $available_methods ){
					$value = WC()->cart->get_cart_shipping_total();
				}
				else{

					$formatted_destination    = WC()->countries->get_formatted_address( $package['destination'], ', ' );

					if ( !$formatted_destination ) {
						$value = wp_kses_post( apply_filters( 'woocommerce_shipping_not_enabled_on_cart_html', __( 'Shipping costs are calculated during checkout.', 'woocommerce' ) ) );
					} else {
						$value = wp_kses_post( apply_filters( 'woocommerce_cart_no_shipping_available_html', sprintf( esc_html__( 'No shipping options were found for %s.', 'woocommerce' ) . ' ', '<strong>' . esc_html( $formatted_destination ) . '</strong>' ) ) );
					}
				}
			}

			if( $showCalculator ){

				$label = sprintf( $calculatorToggle, $label.'<span class="xoo-wsc-icon-pencil"></span>' );
				$value = sprintf( $calculatorToggle, $value ? $value : __( 'Calculate', 'side-cart-woocommerce' ) );
			}


			$totals['shipping'] = array(
				'label' 	=> $label,
				'value' 	=> $value,
				'action' 	=> 'add'
			);
		}


		if( $showFee ){
			foreach ( WC()->cart->get_fees() as $fee ){

				ob_start();
				wc_cart_totals_fee_html( $fee );
				$feeHTML = ob_get_clean();

				$totals[ 'fee_'.$fee->id ] = array(
					'label' 	=> $fee->name,
					'value' 	=> $feeHTML,
					'action' 	=> 'add'
				);
			}
		}

		if( $showTax && wc_tax_enabled() && WC()->cart->get_cart_tax() !== '' ){
			$totals['tax'] = array(
				'label' 	=> __( 'Tax', 'side-cart-woocommerce' ),
				'value' 	=> WC()->cart->get_cart_tax(),
				'action' 	=> 'add'
			);
		}

		if( $showDiscount && WC()->cart->has_discount() ){

			$discount 	= WC()->cart->get_discount_total();
			$discount 	= get_option( 'woocommerce_tax_display_cart' ) === 'incl' ? $discount + WC()->cart->get_discount_tax() : $discount;

			$totals['discount'] = array(
				'label' 	=> __( 'Discount', 'side-cart-woocommerce' ),
				'value' 	=> wc_price( $discount ),
				'action' 	=> 'less'
			);

		}

		if( $showTotal ){
			$totals['total'] = array(
				'label' 	=> __( 'Total', 'side-cart-woocommerce' ),
				'value' 	=> WC()->cart->get_total(),
			);
		}

		return apply_filters( 'xoo_wsc_cart_totals', $totals );

	}


	public function get_shipping_bar_data(){

		$data = array();

		$hasFreeShipping 	= false;
		$amountLeft 		= $fillPercentage = null;
		$subtotal 			= WC()->cart->get_subtotal() + WC()->cart->get_subtotal_tax();

		$packages = WC()->shipping()->get_packages();

		if( empty( $packages ) ) return $data;

		//Support for 1 package only
		$package = $packages[0];

		$available_methods = $package['rates'];

		foreach ( $available_methods as $id => $obj ) {
			if( $obj instanceof WC_Shipping_Free_Shipping ){
				$hasFreeShipping = true;
				break;
			}
		}

		if( !$hasFreeShipping ){
			$shipping_zone 		= WC_Shipping_Zones::get_zone_matching_package( $package );
			$shipping_methods 	= $shipping_zone->get_shipping_methods(true);

			foreach ( $shipping_methods as $id => $obj ) {
				if( $obj instanceof WC_Shipping_Free_Shipping && ( $obj->requires === 'min_amount' || $obj->requires === 'either' ) ){
					
					if( $obj->ignore_discounts === "no" && !empty( WC()->cart->get_coupon_discount_totals() ) ){
						foreach ( WC()->cart->get_coupon_discount_totals() as $coupon_code => $coupon_value ) {
							$subtotal -= $coupon_value;
						}
					}

					if( $subtotal >= $obj->min_amount ){
						$hasFreeShipping = true;
					}
					else{
						$amountLeft 	= $obj->min_amount - $subtotal;
						$fillPercentage =  ceil( ($subtotal/$obj->min_amount) * 100 );
					}
					break;
				}
			}
		}

		if( !$hasFreeShipping && is_null( $amountLeft ) ) return $data;

		$data = array(
			'free' 				=> $hasFreeShipping,
			'amount_left' 		=> $amountLeft,
			'fill_percentage' 	=> $hasFreeShipping ? 100 : $fillPercentage
		);

		return apply_filters( 'xoo_wsc_shipping_bar_data', $data );

	}


	//Get suggested products
	public function get_suggested_products( $type = '' ){

		$customIDS 		= array_filter( explode(',', $this->glSettings['scsp-ids']) );
		$type 			= $type ? $type : $this->glSettings['scsp-type'];
		$count 			= (int) $this->glSettings['scsp-count'];

		$exclude_ids = apply_filters( 'xoo_wsc_suggested_product_exclude', array(), $type );

		if( !$count ) return;


		$product_ids = !empty( $customIDS ) ? $customIDS : array();

		if( empty( $product_ids ) ){
			if( $type === 'cross_sells' ){
				$product_ids = WC()->cart->get_cross_sells();
			}

			else if( $type === 'up_sells' ){

				foreach ( array_reverse( WC()->cart->get_cart() ) as $cart_item ) {

					$product_ids = array_merge( $product_ids, $cart_item['data']->get_upsell_ids() );

					if( count( $product_ids ) >= $count ) break;

				}
			}

			else{

				foreach ( array_reverse( WC()->cart->get_cart() ) as $cart_item ) {

					$product_id 	= $cart_item['variation_id'] ? $cart_item['variation_id'] : $cart_item['product_id'];

					$product_ids 	= array_merge( $product_ids, wc_get_related_products( $product_id ) );

					if( count( $product_ids ) >= $count ) break;

				}
			}

		}

		//Remove already added product ids
		$addedIDs = array();
		foreach ( WC()->cart->get_cart() as $cart_item ) {
			$addedIDs[] = isset( $cart_item['variation_id'] ) && $cart_item['variation_id'] ? $cart_item['variation_id'] : $cart_item['product_id'];
		}


		$exclude_ids = array_merge( $addedIDs, $exclude_ids );

		foreach ($product_ids as $key => $product_id) {
			if( in_array( $product_id, $exclude_ids ) ){
				unset( $product_ids[ $key ] );
			}
		}

	
		//Force random products
		$items_count = $this->glSettings['scsp-random'] === 'yes' ? $count  : count( $product_ids );

		$products = array();
		
		$args = array(
			'post_type'            	=> array( 'product', 'product_variation'),
			'post_status'    		=> 'publish',
			'ignore_sticky_posts'  	=> 1,
			'no_found_rows'       	=> 1,
			'posts_per_page'       	=> $items_count,
			'orderby'             	=> 'rand',
			'meta_query'			=> array(
					array(
			        'key' => '_stock_status',
			        'value' => 'instock',
			        'compare' => '=',
			    )
			)
		);

		if( !empty( $product_ids ) ){
			$args['post__in'] = $product_ids;
		}
		else{
			$args['post__not_in'] = $exclude_ids;
		}


		$args = apply_filters( 'xoo_wsc_suggested_product_args', $args );

		if( $args['posts_per_page'] !== 0 ){
			$products = new WP_Query( $args );
		}

		return $products;

	}


	public function get_bundle_items(){

		if( !empty( $this->bundleItems ) ){
			return $this->bundleItems;
		}

		$data = array(

			'bundled_items' => array(
				'key' 		=> 'bundled_items',
				'type' 		=> 'parent',
				'delete' 	=> true,
				'qtyUpdate' => true,
				'image' 	=> true,
				'link' 		=> true	
			),

			'bundled_by' => array(
				'key' 		=> 'bundled_by',
				'type' 		=> 'child',
				'delete' 	=> false,
				'qtyUpdate' => false,
				'image' 	=> true,
				'link' 		=> true
			),


			'mnm_contents' => array(
				'key' 		=> 'mnm_contents',
				'type' 		=> 'parent',
				'delete' 	=> true,
				'qtyUpdate' => true,
				'image' 	=> true,
				'link' 		=> true
			),


			'mnm_container' => array(
				'key' 		=> 'mnm_container',
				'type' 		=> 'child',
				'delete' 	=> false,
				'qtyUpdate' => false,
				'image' 	=> true,
				'link' 		=> true
			),

			'composite_children' => array(
				'key' 		=> 'composite_children',
				'type' 		=> 'parent',
				'delete' 	=> true,
				'qtyUpdate' => true,
				'image' 	=> true,
				'link' 		=> true
			),


			'composite_parent' => array(
				'key' 		=> 'composite_parent',
				'type' 		=> 'child',
				'delete' 	=> false,
				'qtyUpdate' => false,
				'image' 	=> true,
				'link' 		=> true
			),

			'woosb_ids' => array(
				'key' 		=> 'woosb_ids',
				'type' 		=> 'parent',
				'delete' 	=> true,
				'qtyUpdate' => true,
				'image' 	=> true,
				'link' 		=> true
			),

			'woosb_parent_id' => array(
				'key' 		=> 'woosb_parent_id',
				'type' 		=> 'child',
				'delete' 	=> false,
				'qtyUpdate' => false,
				'image' 	=> true,
				'link' 		=> true
			),
			
		);

		$this->bundleItems = apply_filters( 'xoo_wsc_product_bundle_items', $data );

		return $this->bundleItems;

	}


	public function is_bundle_item( $cart_item ){

		$bundleItems = $this->get_bundle_items();
		$isBundle = array_intersect_key( $bundleItems , $cart_item );
		return !empty( $isBundle ) ? array_values( array_intersect_key( $bundleItems , $cart_item ) )[0] : $isBundle;

	}

}

function xoo_wsc_cart(){
	return Xoo_Wsc_Cart::get_instance();
}
xoo_wsc_cart();
