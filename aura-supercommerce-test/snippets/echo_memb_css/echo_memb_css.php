<?php

/**
 * Snippet Name: Echo Membership in Body Class
 * Version: 1.0.0
 * Description: Adds a CSS class to the body HTML tag - Facilitates the use of Trade/Retail specific CSS
 * Dependency: WP Memberships
 *
 * @link              https://auracreativemedia.co.uk
 * @since             1.0.0
 * @package           Aura_Supercommerce
 *
**/

if ( is_plugin_active( 'woocommerce-memberships/woocommerce-memberships.php' ) ) {

	function membership_body_class($classes) {

	  	$user_id = get_current_user_id();
	  	if(! empty($user_id)){
		  	$memberships = wc_memberships_get_user_active_memberships( $user_id );
		}
	  
		if ( ! empty( $memberships ) ) {
	   	   $classes[] = 'trade-logged-in';
		}
	  
	    return $classes;
	}

	add_filter('body_class', 'membership_body_class');

} 
