<?php

/**
 * Snippet Name: Create Barcodes page and output all barcodes
 * Version: 1.0.0
 * Description: 
 * Dependency: yith-woocommerce-barcodes-premium 
 *
 * @link              https://auracreativemedia.co.uk
 * @since             1.0.0
 * @package           Aura_Time_Saver
 *
**/


function setup_barcodes_page(){
	if( get_page_by_title( 'Product Barcodes' ) == NULL ) {
	    create_pages_tsp( 'Product Barcodes' );
	}

}

function create_pages_tsp($pageName) {

	$barcode_content = '<div class="timesave-bcode-admin">[yith_product_barcode]</div> <br><br> <div class="timesave-bcode-all">[show_all_barcodes]</div>';

    $createPage = array(
      'post_title'    => $pageName,
      'post_content'  => $barcode_content,
      'post_status'   => 'publish',
      'post_author'   => 1,
      'post_type'     => 'page',
      'post_name'     => $pageName
    );

    // Insert the post into the database
    wp_insert_post( $createPage );
}

add_action('admin_init', 'setup_barcodes_page');



function convert_url_to_path( $url ) {
  return str_replace( 
      wp_get_upload_dir()['basedir'], 
      wp_get_upload_dir()['baseurl'], 
      $url
  );
}

function get_products_barcodes( $atts ){
	
	if(function_exists('YITH_YWBC')){

		$product_list = '<h2>All Barcodes</h2>
		<button onclick="window.print()">Print this page</button>
		<ul class="timesave simple-prod-list">';

		$args = array(
				'post_type' => 'product',
				'posts_per_page' => -1
				);
			
			$loop = new WP_Query( $args );

			if ( $loop->have_posts() ) {
				while ( $loop->have_posts() ) : $loop->the_post();
					
					$data = get_post_meta( get_the_id(), '_ywbc_barcode_display_value' );
					$img = get_post_meta( get_the_id(), '_ywbc_barcode_filename' );
					$sku = get_post_meta( get_the_id(), '_sku' );

					$i = 0;

					while($i <= 10 ) :

					if ($data && $img) :

						$product_list .= '<li><div class="bcode-title">' . get_the_title() . ' - SKU: ' . $sku[0] . ' </div> <div class="bcode-img"><img src="' . convert_url_to_path($img[0]) . '"><p> ' . $data[0] . '</p></div></li>';

					endif;

					$i++;

					endwhile;

				endwhile;
			} else {

				echo __( 'No products found' );
			}

			$product_list .= '</ul>';

			wp_reset_postdata();

		return $product_list;

	 

	   
	}

}
add_shortcode( 'show_all_barcodes', 'get_products_barcodes' );

?>