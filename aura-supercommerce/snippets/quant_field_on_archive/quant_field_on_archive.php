<?php
/**
 * Snippet Name: Show Quantity Field on Archive Pages (Shop)
 * Version: 1.0.0
 * Description: WooCommerce normally shows quantity fields only on single.php templates. This shows them on archive pages as well.
 *
 * @link              https://auracreativemedia.co.uk
 * @since             1.0.0
 * @package           Aura_Supercommerce
 * 
 *
**/



add_action( 'wp_enqueue_scripts', 'quant_field_archive_scripts' );

function quant_field_archive_scripts() {
    
    global $post;

    if ( is_object( $post ) && (is_shop() || is_product_category()) ) {

        wp_register_script( 'quant_field_script', plugins_url( '/js/quantity.js' , __FILE__ ), array( 'jquery' ), '1.0.0', true );
        
        wp_enqueue_script( 'quant_field_script' );

    }
        
}

add_action( 'woocommerce_after_shop_loop_item', 'custom_quantity_field_archive', 0, 9 );

function custom_quantity_field_archive() {

	$product = wc_get_product( get_the_ID() );

	if ( ! $product->is_sold_individually() && 'variable' != $product->get_type() && $product->is_purchasable() ) {
		woocommerce_quantity_input( array( 'min_value' => 1, 'max_value' => $product->backorders_allowed() ? '' : $product->get_stock_quantity() ) );
	}

}
