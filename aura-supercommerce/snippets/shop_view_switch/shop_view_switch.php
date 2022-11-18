<?php
/**
 * Snippet Name: Shop View Switch
 * Version: 1.0.1
 * Description: UI feature that shows 2 icons which allow user to switch between products listed in rows or columns
 *
 * @link              https://auracreativemedia.co.uk
 * @since             1.0.0
 * @package           Aura_Supercommerce
 * 
 *
**/


add_action( 'wp_enqueue_scripts', 'shop_view_switch_scripts_styles' );

function shop_view_switch_scripts_styles() {
    
    global $post;

    if ( is_object( $post ) && (is_shop() || is_product_category() || is_tax( 'range' ) || is_tax( 'theme' ) || is_tax( 'occasion' ) ) ) {

        wp_register_style( 'shop_view_switch_css', plugins_url( '/css/switch.css' , __FILE__ ), true, '1.0.1', 'all' );
        wp_register_script( 'shop_view_switch_script', plugins_url( '/js/switch.js' , __FILE__ ), array( 'jquery' ), '1.0.1', true );
        
        wp_enqueue_style( 'shop_view_switch_css' );
        wp_enqueue_script( 'shop_view_switch_script' );

    }
        
}

// This action hook is found in the snippet show_cat_products.php 
add_action( 'creative_commerce_fluid_view', 'cc_switch_shop_layout', 30 );

function cc_switch_shop_layout(){

	$html = '<i id="cc-fluid-grid" class="cc-fluid-switch fa-solid fas fa-grip-horizontal fa-grip"></i><i id="cc-fluid-rows" class="cc-fluid-switch fa-solid fas fa-grip-lines"></i>';
	echo $html;

}


