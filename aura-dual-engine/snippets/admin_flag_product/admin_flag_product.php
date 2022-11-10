<?php
/**
 * Snippet Name: B2B/B2C Flag on Products (Admin only)
 * Version: 1.0.0
 * Description: Shows a flag on the product to disntigush between B2B/B2C in shop/archive (Shows for Admin only)
 *
 * @link              https://auracreativemedia.co.uk
 * @since             1.0.0
 * @package           Aura_Supercommerce
 * 
 *
**/

add_action ( 'woocommerce_before_shop_loop_item', 'admin_flag_product_helper');

function admin_flag_product_helper(){
	
	 if( current_user_can('administrator') ) { 
		 
		 global $product;
	     $id = $product->get_id();
		 
		 $product_type = $product->get_type();
		 $packsize = $product->get_attribute( 'pa_pack-size' );
      	 
		 
		 
		 if($product_type === 'bundle' && $packsize === 'Multi Pack') :
		 	echo '<div id="admin-flag-trade" class="admin-flag">Trade</div>';
		 
		 elseif($product_type === 'simple' && $packsize === 'Single Item') :
		 	echo '<div id="admin-flag-retail" class="admin-flag">Retail</div>';
		 
		 else:
		 	$link = get_edit_post_link($id);
		 	echo '<div id="admin-flag-other" class="admin-flag">Unknown</div>';
		 
		 endif;
		
 	} 
	
}


add_action( 'wp_enqueue_scripts', 'admin_flag_product_styles' );

function admin_flag_product_styles() {
    
    global $post;

    if ( is_object( $post ) && (is_shop() || is_product_category()) ) {

        wp_register_style( 'admin_flag_product_css', plugins_url( '/css/flag.css' , __FILE__ ), true, '1.0.0', 'all' );
        
        wp_enqueue_style( 'admin_flag_product_css' );


    }
        
}