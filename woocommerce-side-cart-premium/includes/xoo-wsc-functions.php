<?php

function xoo_wsc_quantity_input( $args = array(), $product = null, $echo = true ) {

	if ( is_null( $product ) ) {
		return;
	}

	$defaults = array(
		'input_value'  	=> '1',
		'max_value'    	=> apply_filters( 'woocommerce_quantity_input_max', -1, $product ),
		'min_value'    	=> apply_filters( 'woocommerce_quantity_input_min', 0, $product ),
		'step'         	=> apply_filters( 'woocommerce_quantity_input_step', 1, $product ),
		'pattern'      	=> apply_filters( 'woocommerce_quantity_input_pattern', has_filter( 'woocommerce_stock_amount', 'intval' ) ? '[0-9]*' : '' ),
		'inputmode'    	=> apply_filters( 'woocommerce_quantity_input_inputmode', has_filter( 'woocommerce_stock_amount', 'intval' ) ? 'numeric' : '' ),
		'placeholder'  	=> apply_filters( 'woocommerce_quantity_input_placeholder', '', $product ),
		'wsc_classes'  	=> apply_filters( 'xoo_wsc_quantity_input_classes', array( 'xoo-wsc-qty' ), $product ),
		'qtyDesign' 	=> xoo_wsc_helper()->get_style_option('scbq-style')
	);

	$args = apply_filters( 'woocommerce_quantity_input_args', wp_parse_args( $args, $defaults ), $product );

	// Apply sanity to min/max args - min cannot be lower than 0.
	$args['min_value'] = max( $args['min_value'], 0 );
	$args['max_value'] = 0 < $args['max_value'] ? $args['max_value'] : '';

	// Max cannot be lower than min if defined.
	if ( '' !== $args['max_value'] && $args['max_value'] < $args['min_value'] ) {
		$args['max_value'] = $args['min_value'];
	}

	ob_start();

	xoo_wsc_helper()->get_template( 'global/body/qty-input.php', $args );

	if ( $echo ) {
		echo ob_get_clean(); // WPCS: XSS ok.
	} else {
		return ob_get_clean();
	}
}

function xoo_wsc_notice_html( $message, $notice_type = 'success' ){
	
	$classes = $notice_type === 'error' ? 'xoo-wsc-notice-error' : 'xoo-wsc-notice-success';

	$icon = $notice_type === 'error' ? 'xoo-wsc-icon-cross' : 'xoo-wsc-icon-check_circle';
	
	$html = '<li class="'.$classes.'"><span class="'.$icon.'"></span>'.$message.'</li>';
	
	return apply_filters( 'xoo_wsc_notice_html', $html, $message, $notice_type );
}



function xoo_wsc_suggested_product_addtocart_link( $link, $product, $args ){

	if( !isset( $args['is_xoo_wsc_sp'] ) ) return $link;

	return sprintf(
		'<a href="%s" data-quantity="%s" class="%s" %s>%s</a>',
		esc_url( $product->add_to_cart_url() ),
		esc_attr( isset( $args['quantity'] ) ? $args['quantity'] : 1 ),
		esc_attr( isset( $args['class'] ) ? $args['class'] : 'button' ),
		isset( $args['attributes'] ) ? wc_implode_html_attributes( $args['attributes'] ) : '',
		'<span>+</span>'. __( 'Add', 'side-cart-woocommerce' )
	);
}
add_filter( 'woocommerce_loop_add_to_cart_link', 'xoo_wsc_suggested_product_addtocart_link', 999, 3 );


function xoo_wsc_add_flytocart_img_attr( $attr, $attachment, $size ){
	global $product;
	if( $product ){
		$attr['data-xooWscFly'] = 'fly';
	}
	return $attr;
}
add_filter( 'wp_get_attachment_image_attributes', 'xoo_wsc_add_flytocart_img_attr', 999, 3 );

function xoo_wsc_basket_shortcode( $atts ){

	if( is_admin() ) return;

	$atts = shortcode_atts( array(), $atts, 'xoo_wsc_cart');

	return xoo_wsc_helper()->get_template( 'xoo-wsc-shortcode.php', $atts, '', true );
}
add_shortcode( 'xoo_wsc_cart', 'xoo_wsc_basket_shortcode' );


function xoo_wsc_display_suggested_products(){
	xoo_wsc_helper()->get_template( 'global/footer/suggested-products.php' );
}


function xoo_wsc_add_sp(){
	$location 	= xoo_wsc_helper()->get_style_option('scsp-location');
	$hook 		=  $location === 'before' ? 'xoo_wsc_body_end' : 'xoo_wsc_footer_end';
	add_action( $hook, 'xoo_wsc_display_suggested_products' );

}
add_action( 'xoo_wsc_header_start', 'xoo_wsc_add_sp' );

?>