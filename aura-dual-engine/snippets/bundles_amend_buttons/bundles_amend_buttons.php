<?php
/**
 * Snippet Name: Bundle Amend Buttons
 * Version: 1.0.0
 * Description: Amends the Product Bundles so that the buttons for single products link to the bundle version
 * Dependency: WP Memberships, Product Bundles
 *
 * @link              https://auracreativemedia.co.uk
 * @since             1.0.0
 * @package           Aura_Supercommerce
 * @requires          WP Memberships, Product Bundles, 
 *
**/


if ( is_plugin_active( 'woocommerce-memberships/woocommerce-memberships.php' ) &&  is_plugin_active( 'woocommerce-product-bundles/woocommerce-product-bundles.php' ) ) {

/**
 * Get term slugs for Global use by other functions.
 *
 * @return array - of slugs to exclude
 */



// add_action( 'woocommerce_product_query', 'themelocation_product_query' );

// function themelocation_product_query( $q ) {
	
// 	$user_trade = false;
	
// 	$user = wp_get_current_user();
//     if ( ( in_array( 'tradecust', $user->roles ) ) || ( in_array( 'store_agent', $user->roles ) ) && (!current_user_can('administrator')) ) {
// 		$user_trade = true;
// 	}

//     if( is_admin() || !$user_trade ) return $q;
	
//    $taxonomy = 'pa_pack-size';
//    $terms = array( 'single-item' ); // Term names
	
//   $tax_query = (array) $q->get( 'tax_query' );
//   $tax_query[] = array(
//     'taxonomy' => $taxonomy,
// 	'field'    => 'slug', // Or 'slug' or 'term_id'
// 	'terms'    =>  $terms,
// 	'operator' => 'NOT IN'
//   );

// 	 //$q->set( 'posts_per_page', 1 );
//    $q->set( 'tax_query', $tax_query );
// }



// //add_filter( 'woocommerce_product_query_tax_query', 'custom_product_query_tax_query', 10, 2 );


// function custom_product_query_tax_query( $tax_query, $query ) {
	
// 	$user_trade = false;
	
// 	$user = wp_get_current_user();
//     if ( ( in_array( 'tradecust', $user->roles ) ) || ( in_array( 'store_agent', $user->roles ) ) && (!current_user_can('administrator')) ) {
// 		$user_trade = true;
// 	}

//     if( is_admin() || !$user_trade ) return $tax_query;
    
//     // Define HERE the product attribute and the terms
//     $taxonomy = 'pa_pack-size';
//     $terms = array( 'single-item' ); // Term names
// 	var_dump($terms);
	
//     // Add your criteria
//     $tax_query[] = array(
//         'taxonomy' => $taxonomy,
//         'field'    => 'slug', // Or 'slug' or 'term_id'
//         'terms'    => $terms,
// 		'operator' => 'NOT IN'
//     );
	
//     return $tax_query;
	
// }



// Shop and archives pages: we replace the button add to cart by a link to the product

	
	
	
	
	
// 2022 !!  I'm hiding this while trialling a new quick order screen, with this on the add to cart button is always 'View Product'
// I can't recall why it's needed but possibly due to the fact trade were seeing single versions of products?
//add_filter( 'woocommerce_loop_add_to_cart_link', 'custom_text_replace_button', 10, 2 );

//function custom_text_replace_button( $button, $product  ) {
   // $button_text = __("View product", "woocommerce");
	

     //return '<a class="button" href="' . $product->get_permalink() . '">' . $button_text . '</a>';
//}


// replacing add to cart button and quantities by a custom text
add_action( 'woocommerce_single_product_summary', 'replacing_template_single_add_to_cart', 1, 0 );

function replacing_template_single_add_to_cart() {
	 
	 $user_trade = false;
	 $not_bundle_type = false;
	 $linked_bundle_id = false;
	
    // If Customer is Trade...
    $user = wp_get_current_user();
    if ( ( in_array( 'tradecust', $user->roles ) ) || ( in_array( 'store_agent', $user->roles ) ) && (!current_user_can('administrator')) ) {
		$user_trade = true;
	}
	
    // and Product is a single item... 
	global $product;
	$id = $product->get_id();
	if( ($product->is_type( 'simple' )) || $product->is_type( 'variable' ) ) : $not_bundle_type = true; endif;
	
	// and Product is in an existing bundle
	$linked_bundle_id = wc_pb_get_bundled_product_map( $product );
	
	if ($user_trade && $not_bundle_type && $linked_bundle_id) {
		
		// Removing add to cart button and quantities
		remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30 );
		 
		// The text replacement
   		add_action( 'woocommerce_single_product_summary', function(){
		
		global $product;
		$linked_bundle_id = wc_pb_get_bundled_product_map( $product );
		
		foreach ($linked_bundle_id as $bundle) {
			$this_bundle_id = $bundle;
		}
		
        // set below your custom text
        $text = __("Click Here for Multi-Pack Version", "woocommerce");
		
		$link = get_permalink( $this_bundle_id );
			 
        // Temporary style CSS
        //$style_css = 'style="border: solid 1px red; padding: 0 6px; text-align: center;"';
	
        // Output your custom text
		echo '<a href="' . $link . '"><button type="submit" name="link-to-bundle" class="single_add_to_cart_button button alt">' . $text . '</button></a>';
		 
		 }, 30 );
	}


}

}