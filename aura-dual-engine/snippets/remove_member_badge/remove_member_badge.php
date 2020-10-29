<?php
/**
 * Snippet Name: Remove Member Badge
 * Version: 1.0.0
 * Description: 
 * Dependency: WP Memberships
 *
 * @link              https://auracreativemedia.co.uk
 * @since             1.0.0
 * @package           Aura_Supercommerce
 * @requires          WP Memberships, Product Bundles, 
 *
**/


if ( is_plugin_active( 'woocommerce-memberships/woocommerce-memberships.php' ) ) {

	//The member discount badge’s HTML is filterable, and therefore it can be removed completely using this snippet:
	add_filter( 'wc_memberships_member_discount_badge', '__return_empty_string' );

}