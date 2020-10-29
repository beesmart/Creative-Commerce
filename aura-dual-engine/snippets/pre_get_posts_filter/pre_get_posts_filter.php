<?php
/**
 * Snippet Name: Pre Get Posts Filter
 * Version: 1.0.0
 * Description: Relies on PBundles and Product Vis snippets, will filter the main query to honour the functions of those snippets
 * Dependency: WP Memberships, Product Bundles, Bundles Amend Buttons, Hide Cat Users
 *
 * @link              https://auracreativemedia.co.uk
 * @since             1.0.0
 * @package           Aura_Supercommerce
 * @requires          WP Memberships, Product Bundles, Bundles Amend Buttons, Hide Cat Users
 *
**/


if ( is_plugin_active( 'woocommerce-memberships/woocommerce-memberships.php' ) &&  is_plugin_active( 'woocommerce-product-bundles/woocommerce-product-bundles.php' ) ) {

/**
 * Get term slugs for Global use by other functions.
 *
 * @return array - of slugs to exclude
 */



// Using Pre_Get_Posts to stop the products - supposed to be hidden - from appearing in Tags Archive 
function remove_items_from_tag_and_shop( $query ) {

    if (!current_user_can('administrator')){

    if ( ! is_admin() && ($query->is_search() || $query->is_main_query()) ) {
        // Not a query for an admin page.
        // It's the main query for a front end page of your site.
	  
	  global $exclude_slugs;
	  $current_user = wp_get_current_user();

	  if ( (! in_array( 'tradecust', $current_user->roles )) || (! in_array( 'store_agent', $current_user->roles ) )){
		
		 $exclude_slugs_retail = $exclude_slugs['retail'];
	
		   $query->set( 'tax_query',
			  array(

				  array(
					  'taxonomy' => 'product_cat',
					  'field' => 'slug',
					  'terms' => $exclude_slugs_retail,
					  'operator' => 'NOT IN', // Excluded
				  )

			  )
		  );

	  }  

	  if ( (in_array( 'tradecust', $current_user->roles )) || (in_array( 'store_agent', $current_user->roles ) )){
		
		  $exclude_slugs_trade = $exclude_slugs['trade'];

          // Define HERE the product attribute and the terms
      $taxonomy = 'pa_pack-size';
      $terms = array( 'single-item' ); // Term names

		  $query->set( 'tax_query',
			  array(
          'relation' => 'AND',
				  array(
					  'taxonomy' => 'product_cat',
					  'field' => 'slug',
					  'terms' => $exclude_slugs_trade,
					  'operator' => 'NOT IN', // Excluded
				  ),
          array(
            'taxonomy' => $taxonomy,
            'field'    => 'slug', // Or 'slug' or 'term_id'
            'terms'    => $terms,
            'operator' => 'NOT IN'
          )

			  )
		  );
	  }  
        
    }
	}
}

// I switched it off as it was preventing the archives from showing products to Trade even when hidden only to retail
add_action( 'pre_get_posts', 'remove_items_from_tag_and_shop' );

}