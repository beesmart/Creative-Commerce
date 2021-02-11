<?php

/**
 * Snippet Name: Setup Stockists Page
 * Version: 1.0.0
 * Description: 
 * Dependency: WP Store Locator
 *
 * @link              https://digitalzest.co.uk
 * @since             1.0.0
 * @package           Aura_Trade_Booster
 *
**/



function setup_stockists_page(){
	if( get_page_by_title( 'Stockists' ) == NULL ) {
	    create_pages_stockists( 'Stockists' );
	}

}

function create_pages_stockists($pageName) {
    $createPage = array(
      'post_title'    => $pageName,
      'post_content'  => '[wpsl]',
      'post_status'   => 'publish',
      'post_author'   => 1,
      'post_type'     => 'page',
      'post_name'     => $pageName
    );

    // Insert the post into the database
    wp_insert_post( $createPage );
}

add_action('admin_init', 'setup_stockists_page');
