<?php

/**
 * Snippet Name: Echo Membership in Body Class
 * Version: 1.0.0
 * Description: Adds a CSS class to the body HTML tag on Front end and Admin - Facilitates the use of Trade/Retail specific CSS
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



add_filter( 'admin_body_class', 'aura_admin_body_classes' );

function aura_admin_body_classes( $classes ) {



	$aura_sc_admin = new Aura_Supercommerce_Admin('aura-supercommerce', '1.0.0');
	$is_admin = $aura_sc_admin->check_current_is_privileged_user();
    
    if ($is_admin) :
   	
   		return "$classes priv_admin";

   	else :

   		return $classes;

   	endif;


}

