<?php
/**
 * Snippet Name: Minimum Order Amount
 * Version: 1.0.0
 * Description: This plugin enables a minimum order amount at checkout via /wp-admin/admin.php?page=wc-settings&tab=products&section=ordervalue
 * Dependency: WP Memberships, Product Bundles
 *
 * @link              https://auracreativemedia.co.uk
 * @since             1.0.0
 * @package           Aura_Supercommerce
 * @requires          WP Memberships, Product Bundles, 
 *
**/



// Code to Set Minimum Order Amount
add_filter( 'woocommerce_get_sections_products', 'ordervalue_add_section' );

function ordervalue_add_section( $sections ) {
	$sections['ordervalue'] = __( 'Order Value', 'text-domain' );
	return $sections;
} 

add_filter( 'woocommerce_get_settings_products', 'ordervalue_all_settings', 10, 2 );

function ordervalue_all_settings( $settings, $current_section ) {

	/**
	 * Check the current section is what we want
	 **/

	if ( $current_section == 'ordervalue' ) {

		$settings_ordervalue = array();

		// Add Title to the Settings
		$settings_ordervalue[] = array( 'name' => __( 'Order Value Settings', 'text-domain' ), 'type' => 'title', 'desc' => __( 'The following options are used to configure Order Value settings', 'text-domain' ), 'id' => 'ordervalue' );

		// Add first checkbox option
		$settings_ordervalue[] = array(

			'name'     => __( 'Minimum Value', 'text-domain' ),
			'id'       => 'ordervalue_enabled',
			'type'     => 'checkbox',
			'desc'     => __( 'Enable', 'text-domain' ),

		);
		
		// Add  text field option
		$settings_ordervalue[] = array(

			'name'     => __( 'Set Minimum Order Value', 'text-domain' ),
			'id'       => 'ordervalue_minimum',
			'type'     => 'number',
			'desc'     => __( 'Min order value exc VAT', 'text-domain' ),

		);
		
		$settings_ordervalue[] = array( 'type' => 'sectionend', 'id' => 'ordervalue' );

		return $settings_ordervalue;
	
	/**
	 * If not, return the standard settings
	 **/

	} else {

		return $settings;

	}

}


// Minimum Order value Function
// 
// 




add_action( 'woocommerce_checkout_process', 'tn_minimum_order_amount' );
add_action( 'woocommerce_review_order_before_submit' , 'tn_minimum_order_amount' );
add_action( 'woocommerce_before_cart' , 'tn_minimum_order_amount' );
//add_action( 'woocommerce_before_checkout_form' , 'tn_minimum_order_amount' );


function tn_hide_woocommerce_order_button_html($html) {
	$current_user = wp_get_current_user();
	if ( in_array( 'tradecust', $current_user->roles ) || in_array( 'store_agent', $current_user->roles ) ){
		$html = '';
		return $html;	
	}
	return $html;	
} 


function tn_minimum_order_amount() {
	$current_user = wp_get_current_user();
	if ( in_array( 'tradecust', $current_user->roles ) || in_array( 'store_agent', $current_user->roles ) ){
	$minimum = get_option( 'ordervalue_minimum' );
	$enabled = (get_option( 'ordervalue_enabled' ) == 'yes') ? TRUE : FALSE; 

    if ( ( WC()->cart->subtotal_ex_tax < $minimum ) && ($enabled == TRUE) ) {

        if( is_cart() || is_checkout() ) {

            wc_print_notice( 
                sprintf( 'There is a minimum purchase of %s + VAT for all accounts. Your current order total is %s.' , 
                    wc_price( $minimum ), 
                    wc_price( WC()->cart->subtotal_ex_tax )
                ), 'error' 
            );
            
            remove_action( 'woocommerce_proceed_to_checkout','woocommerce_button_proceed_to_checkout', 20);
			 add_filter( 'woocommerce_order_button_html', 'tn_hide_woocommerce_order_button_html()');
        
       } elseif( is_checkout() ) {

            wc_print_notice( 
                sprintf( 'There is a minimum purchase of %s + VAT for all accounts. Your current order total is %s.' , 
                    wc_price( $minimum ), 
                    wc_price( WC()->cart->subtotal_ex_tax )
                ), 'notice' 
            ); 
          
            add_filter( 'woocommerce_order_button_html', 'tn_hide_woocommerce_order_button_html()');

        } else {

            wc_add_notice( 
                sprintf( 'There is a minimum purchase of %s + VAT for all accounts. Your current order total is %s.' , 
                    wc_price( $minimum ), 
                    wc_price( WC()->cart->subtotal_ex_tax )
                ), 'error' 
            );
			
         }
    }
	}

}
/* Your code goes above here. */ 

?>