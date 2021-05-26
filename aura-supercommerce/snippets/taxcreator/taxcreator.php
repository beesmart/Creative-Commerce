<?php

/**
 * Snippet Name: Theme Taxonomy Setup
 * Version: 1.0.0
 * Description: Create a custom 'Range' Taxonomy for products and add columns to admin screen
 *
 * @link              https://auracreativemedia.co.uk
 * @since             1.0.0
 * @package           Aura_Supercommerce
 *
**/



add_action( 'init', 'custom_taxonomy_Item', 999 );

function custom_taxonomy_Item()  {

	$labels = array(
	    'name'                       => 'Range',
	    'singular_name'              => 'Range',
	    'menu_name'                  => 'Range',
	    'all_items'                  => 'All Ranges',
	    'parent_item'                => 'Parent Range',
	    'parent_item_colon'          => 'Parent Range:',
	    'new_item_name'              => 'New Range Name',
	    'add_new_item'               => 'Add New Range',
	    'edit_item'                  => 'Edit Range',
	    'update_item'                => 'Update Range',
	    'separate_items_with_commas' => 'Separate Range with commas',
	    'search_items'               => 'Search Ranges',
	    'add_or_remove_items'        => 'Add or remove Range',
	    'choose_from_most_used'      => 'Choose from the most used Range',
	);
	$args = array(
	    'labels'                     => $labels,
	    'hierarchical'               => true,
	    'public'                     => true,
	    'show_ui'                    => true,
	    'show_admin_column'          => true,
	    'show_in_nav_menus'          => true,
	    'show_tagcloud'              => true,
	);

	register_taxonomy( 'range', 'product', $args );

	register_taxonomy_for_object_type( 'range', 'product' );



	$labels = array(
	    'name'                       => 'Occasion',
	    'singular_name'              => 'Occasion',
	    'menu_name'                  => 'Occasion',
	    'all_items'                  => 'All Ranges',
	    'parent_item'                => 'Parent Occasion',
	    'parent_item_colon'          => 'Parent Occasion:',
	    'new_item_name'              => 'New Occasion Name',
	    'add_new_item'               => 'Add New Occasion',
	    'edit_item'                  => 'Edit Occasion',
	    'update_item'                => 'Update Occasion',
	    'separate_items_with_commas' => 'Separate Occasion with commas',
	    'search_items'               => 'Search Occasions',
	    'add_or_remove_items'        => 'Add or remove Occasion',
	    'choose_from_most_used'      => 'Choose from the most used Occasion',
	);
	$args = array(
	    'labels'                     => $labels,
	    'hierarchical'               => true,
	    'public'                     => true,
	    'show_ui'                    => true,
	    'show_admin_column'          => true,
	    'show_in_nav_menus'          => true,
	    'show_tagcloud'              => true,
	);

	register_taxonomy( 'occasion', 'product', $args );

	register_taxonomy_for_object_type( 'occasion', 'product' );

	$labels = array(
	    'name'                       => 'Theme',
	    'singular_name'              => 'Theme',
	    'menu_name'                  => 'Theme',
	    'all_items'                  => 'All Ranges',
	    'parent_item'                => 'Parent Theme',
	    'parent_item_colon'          => 'Parent Theme:',
	    'new_item_name'              => 'New Theme Name',
	    'add_new_item'               => 'Add New Theme',
	    'edit_item'                  => 'Edit Theme',
	    'update_item'                => 'Update Theme',
	    'separate_items_with_commas' => 'Separate Theme with commas',
	    'search_items'               => 'Search Ranges',
	    'add_or_remove_items'        => 'Add or remove Theme',
	    'choose_from_most_used'      => 'Choose from the most used Theme',
	);
	$args = array(
	    'labels'                     => $labels,
	    'hierarchical'               => true,
	    'public'                     => true,
	    'show_ui'                    => true,
	    'show_admin_column'          => true,
	    'show_in_nav_menus'          => true,
	    'show_tagcloud'              => true,
	);

	register_taxonomy( 'theme', 'product', $args );

	register_taxonomy_for_object_type( 'theme', 'product' );


}



// Thanks to : https://rudrastyh.com/woocommerce/columns.html - for the code 


add_filter( 'manage_edit-product_columns', 'add_customtax_range_column', 20 );
function add_customtax_range_column( $columns_array ) {
 
	return array_slice( $columns_array, 0, 8, true )
	+ array( 'range' => 'Range' )
	+ array_slice( $columns_array, 8, NULL, true );

}


add_filter( 'manage_edit-product_columns', 'add_customtax_occasion_column', 20 );
function add_customtax_occasion_column( $columns_array ) {
 
	return array_slice( $columns_array, 0, 9, true )
	+ array( 'occasion' => 'Occasion' )
	+ array_slice( $columns_array, 9, NULL, true );

}

 
add_action( 'manage_posts_custom_column', 'populate_customtax_range_column' );
function populate_customtax_range_column( $column_name ) {
 
	if( $column_name  == 'range' ) {
		// if you suppose to display multiple brands, use foreach();
		$x = get_the_terms( get_the_ID(), 'range'); // taxonomy name
		if($x) {
			foreach ($x as $value) {
				echo $value->name;
			}
		}
		
	}

 
}

add_action( 'manage_posts_custom_column', 'populate_customtax_occasion_column' );
function populate_customtax_occasion_column( $column_name ) {

	if( $column_name  == 'occasion' ) {
		// if you suppose to display multiple brands, use foreach();
		$x = get_the_terms( get_the_ID(), 'occasion'); // taxonomy name
		if($x) {
			foreach ($x as $value) {
				echo $value->name;
			}
		}
		
	}
 
}


add_filter( 'manage_edit-product_columns', 'arrange_product_columns_after_name' );
function arrange_product_columns_after_name( $product_columns ) {
 
	// the best way in this case – manually redefine array order
	return array(
		'cb' => '<input type="checkbox" />', // checkbox for bulk actions
 		'thumb' => '<span class="wc-image tips" data-tip="Image">Image</span>',
		'name' => 'Name',
		'sku' => 'SKU',
		'is_in_stock' => 'Stock',
		'price' => 'Price',
		'product_cat' => 'Categories',
		'range' => 'Range',
		'occasion' => 'Occasion',
		'product_tag' => 'Tags',
		'featured' => '<span class="wc-featured parent-tips" data-tip="Featured">Featured</span>',
		'product_type' => '<span class="wc-type parent-tips" data-tip="Type">Type</span>',
		'date' => 'Date', // it is the last element by default, I inserted it third!
	);
	// Tip: Just "forget" to add some elements to array if you want to remove associated columns
 
}



add_action( 'woocommerce_before_shop_loop_item_title', 'occasions_display_before_shop_loop_title', 20, 0 ); 

function occasions_display_before_shop_loop_title() {
	global $product;

	$occasions = get_the_terms( $product->get_id(), 'occasion');

	if ($occasions) : 

		echo '<h3 class="woo-tax-title">';

		foreach ($occasions as $value) {
			echo sprintf( '<span class="tax-title">%s</span>', $value->name );
		}

		echo '</h3>';
		
	endif;
}