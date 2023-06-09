<?php
/**

 * Snippet Name: Show Category Products - Shortcode
 * Version: 1.1.0
 * Description: UNDER REVIEW: Possible issues with Pagination etc. Recommend using Posts module per Fay's Studio - A little workaround to deal with Beaver Builders Themer system not outputting category loops
 *
 * @link              https://auracreativemedia.co.uk
 * @since             1.0.1
 * @package           Aura_Supercommerce
 *

**/

// SHORTCODE: [woocommerce_show_this_cat_products]

function this_cat_products(){

  ob_start();

  do_action( 'woocommerce_before_main_content' );

    if ( woocommerce_product_loop() ) {

	/**

	 * Hook: woocommerce_before_shop_loop.
	 * @hooked woocommerce_output_all_notices - 10
	 * @hooked woocommerce_result_count - 20
	 * @hooked woocommerce_catalog_ordering - 30
	 */

	do_action( 'woocommerce_before_shop_loop' );
	do_action( 'creative_commerce_fluid_view' );
		
	woocommerce_product_loop_start();

	if ( wc_get_loop_prop( 'total' ) ) {

		while ( have_posts() ) {

			the_post();

			/**
			 * Hook: woocommerce_shop_loop.
			 */

			do_action( 'woocommerce_shop_loop' );
			wc_get_template_part( 'content', 'product' );

		}

	}

	woocommerce_product_loop_end();

	/**
	 * Hook: woocommerce_after_shop_loop.
	 *
	 * @hooked woocommerce_pagination - 10
	 */

$total   = isset( $total ) ? $total : wc_get_loop_prop( 'total_pages' );
$current = isset( $current ) ? $current : wc_get_loop_prop( 'current_page' );
$base    = isset( $base ) ? $base : esc_url_raw( str_replace( 999999999, '%#%', remove_query_arg( 'add-to-cart', get_pagenum_link( 999999999, false ) ) ) );
$format  = isset( $format ) ? $format : '';

	do_action( 'woocommerce_after_shop_loop' );

} else {

	/**

	 * Hook: woocommerce_no_products_found.
	 *
	 * @hooked wc_no_products_found - 10
	 */

	do_action( 'woocommerce_no_products_found' );

}

  do_action( 'woocommerce_after_main_content' );

  return ob_get_clean();

}

add_shortcode( 'woocommerce_show_this_cat_products', 'this_cat_products' );

