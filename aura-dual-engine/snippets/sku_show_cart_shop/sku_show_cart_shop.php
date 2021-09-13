<?php
/**
 * Snippet Name: Show SKU on Cart and Shop Pages (Trade only)
 * Version: 1.0.0
 * Description: Shows the SKU on cart and shop pages but only for trade customers
 * Dependency: WP Memberships
 *
 * @link              https://auracreativemedia.co.uk
 * @since             1.2.4
 * @package           Aura_Supercommerce
 * @requires          WP Memberships
 *
**/


if ( is_plugin_active( 'woocommerce-memberships/woocommerce-memberships.php' ) ) {

		$user_id = get_current_user_id();
	
	  	if(! empty($user_id)){
		  	$memberships = wc_memberships_get_user_active_memberships( $user_id );
		}
	  
		if ( ! empty( $memberships ) ) :

			add_action( 'woocommerce_after_cart_item_name', '_sku_below_cart_item_name', 11, 2 );
			 
			function _sku_below_cart_item_name( $cart_item, $cart_item_key ) {
			   $_product = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
			   $sku = $_product->get_sku();
			   if ( ! $sku ) return;
			   echo '<p class="sku-sml-text"><small>SKU: ' . $sku . '</small></p>';
			}

			add_filter( 'woocommerce_get_price_html', '_sku_after_price_loop' );
			
			function _sku_after_price_loop( $price ) { 
			    global $product;
			    if ( $product->get_sku() && !is_admin() && !is_product() ) {
			        $sku = $product->get_sku();
			        return $price . '<br /><p class="product-sku sku-sml-text"><span>SKU:</span> ' . $sku . '</p>';
			    } else { 
			        return $price; 
			    } 
			}
	

		endif;

}