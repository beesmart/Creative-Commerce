<?php

/**
 * Snippet Name: Create Trade Customer Role 
 * Version: 1.0.0
 * Description: creates the Trade Customer role
 * 
 *
 * @link              https://auracreativemedia.co.uk
 * @since             1.0.0
 * @package           Aura_Supercommerce
 *
**/


add_action('init', 'cloneRole', 999);

function cloneRole() {

	$check_role = get_role('tradecust');

	if(!$check_role) :

		 $customer = get_role('customer');
		 $customer_cap= array_keys( $customer->capabilities ); //get administator capabilities
		 add_role('tradecust', 'Trade Customer'); //create new role
		 $new_role = get_role('tradecust');

		  foreach ( $customer_cap as $cap ) {
		   $new_role->add_cap( $cap ); //clone administrator capabilities to new role

		  }
	endif;
}
