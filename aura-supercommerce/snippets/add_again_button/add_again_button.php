<?php

/**
 * Snippet Name: 'Add Again' Button to State Product is in Cart
 * Version: 1.0.0
 * Description: Works for Simple products & Variable and adds the Already in Cart - Add again? to the Archive page & Single Product Page.
 * 
 *
 * @link              https://auracreativemedia.co.uk
 * @since             1.0.0
 * @package           Aura_Supercommerce
 *
**/


/**
 *
 * @link              http://hookr.io/filters/woocommerce_product_single_add_to_cart_text/
 * @param             $label - String - Add to cart
 * @return            $label - String 
 *
**/


add_filter( 'woocommerce_product_single_add_to_cart_text', 'connor_custom_add_cart_button_single_product' );
 
function connor_custom_add_cart_button_single_product( $label ) {
  global $product;
  $children_ids = $product->get_children();
   foreach( WC()->cart->get_cart() as $cart_item_key => $values ) {
      $compare_product = $values['data'];
	  if ( $product->get_type() == 'simple'){
		if( get_the_ID() == $compare_product->get_id() ) {
		   $label = __('Already in Cart. Add again?', 'woocommerce');
		}
	  }
	 elseif($product->get_type() == 'variable'){
		  foreach($children_ids as $child)
		    if( $child == $compare_product->get_id() ) {
            $label = __('Already in Cart. Add again?', 'woocommerce');
         }
		}
	  
   }
    
   return $label;
 
}
 
add_filter( 'woocommerce_product_add_to_cart_text', 'connor_custom_add_cart_button_loop', 99, 2 );
 
function connor_custom_add_cart_button_loop( $label, $product ) {

   if ( ($product->get_type() == 'simple' || $product->get_type() == 'variable') && $product->is_purchasable() && $product->is_in_stock() ) {
	  
	 $children_ids = $product->get_children();
	 
      foreach( WC()->cart->get_cart() as $cart_item_key => $values ) {
         $_product = $values['data'];
		
		if($product->get_type() == 'simple'){
		    if( get_the_ID() == $_product->get_id() ) {
            $label = __('Already in Cart. Add again?', 'woocommerce');
         }
		}
		elseif($product->get_type() == 'variable'){
		  foreach($children_ids as $child)
		    if( $child == $_product->get_id() ) {
            $label = __('Already in Cart. Add again?', 'woocommerce');
         }
		}
      }
       
   }
    
   return $label;
    
}
    