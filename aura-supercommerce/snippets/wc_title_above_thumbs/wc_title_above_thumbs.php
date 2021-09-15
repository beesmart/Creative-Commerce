<?php

/**
 * Snippet Name: Move Product Titles Above Thumbnail
 * Version: 1.0.0
 * Description: 
 * 
 *
 * @link              https://auracreativemedia.co.uk
 * @since             1.0.0
 * @package           Aura_Supercommerce
 *
**/


function product_change_title_position(){

	remove_action('woocommerce_shop_loop_item_title','woocommerce_template_loop_product_title');
	add_action('woocommerce_before_shop_loop_item_title','woocommerce_template_loop_product_title', 5);
	
}

add_action('init','product_change_title_position');