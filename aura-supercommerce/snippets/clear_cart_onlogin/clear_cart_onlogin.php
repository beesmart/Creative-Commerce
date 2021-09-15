<?php

/**
 * Snippet Name: Clear Cart on Login
 * Version: 1.0.0
 * Description: Clears the Cart on login. This prevents an unwanted feature whereby a trade user can add e.g. 1 card (quantity 1) to their cart, then login and retain the item (trade users aren't allowed to purchase under x amount of cards)
 *
 * @link              https://auracreativemedia.co.uk
 * @since             1.0.0
 * @package           Aura_Supercommerce
 *
**/


add_action( 'wp_login', 'woocommerce_clear_cart_url' );

function woocommerce_clear_cart_url() {
  global $woocommerce;
  WC()->cart->empty_cart();
}


// FOR FUTURE:
// Add the below function to the membership_deleted action, so that the cart is cleared on a mebership deletion not just update.

add_action('wc_memberships_user_membership_saved','av_wc_memberships_user_membership_saved',10,2);

function av_wc_memberships_user_membership_saved($plan, $data){

	global $wpdb;
	
	$saved_cart_meta = get_user_meta( $data['user_id'], '_woocommerce_persistent_cart_' . get_current_blog_id(), true );
	if($saved_cart_meta != ''){
		delete_user_meta($data['user_id'],'_woocommerce_persistent_cart_' . get_current_blog_id());
	}
	$check_user_session = $wpdb->get_var('SELECT session_id from '.$wpdb->prefix.'woocommerce_sessions where session_key='.$data['user_id']);
	if($check_user_session != ''){
		$wpdb->query('DELETE FROM '.$wpdb->prefix.'woocommerce_sessions where session_key='.$data['user_id']);
	}
}