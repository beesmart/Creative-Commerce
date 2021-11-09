<?php
/**
 * Snippet Name: Woo Bulk Order Input
 * Version: 1.0.0
 * Description: Use [woo-bulk-order-input] to echo out the bulk order form, also check settings page
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
 * Add quantity field on the archive page.
 */

// function wooboi_custom_quantity_field_archive() {
// 	$product = wc_get_product( get_the_ID() );
//     $user_access = wooboi_check_current_user_access();
// 	if ($user_access &&  ! $product->is_sold_individually() && 'variable' != $product->get_type() && $product->is_purchasable() ) {
// 		woocommerce_quantity_input( array( 'min_value' => 1, 'max_value' => $product->backorders_allowed() ? '' : $product->get_stock_quantity() ) );
// 	}
// }

//add_action( 'woocommerce_after_shop_loop_item', 'wooboi_custom_quantity_field_archive', 0, 9 );

/**
 * Add requires JavaScript.
 */

// function wooboi_custom_add_to_cart_quantity_handler() {
//       $user_access = wooboi_check_current_user_access();
//       if($user_access) {
//         wc_enqueue_js( '
            
//             jQuery( ".products" ).on( "change input", ".quantity .qty", function() {
				
//                 var add_to_cart_button = jQuery( this ).parents( ".product" ).find( ".add_to_cart_button" );
//                 // For AJAX add-to-cart actions
//                 add_to_cart_button.attr( "data-quantity", jQuery( this ).val() );
//                 // For non-AJAX add-to-cart actions
//                 add_to_cart_button.attr( "href", "?add-to-cart=" + add_to_cart_button.attr( "data-product_id" ) + "&quantity=" + jQuery( this ).val() );
//             });
//         ' );
//     }
// }
//add_action( 'init', 'wooboi_custom_add_to_cart_quantity_handler' );

function wooboi_check_current_user_access(){
	$user = wp_get_current_user();
    $user_id = get_current_user_id();

	$allowed_for_bulk_order = false;
	if(isset($user_id)) {

        // get allowed members for bulk
		$wooboi_get_allowed_memberships = get_option( 'wooboi_get_allowed_memberships' );
        // get memberships for this user 
		$active_memberships = wc_memberships_get_user_active_memberships($user_id);

		foreach($active_memberships as $membership) {

	 	if(is_array($wooboi_get_allowed_memberships) && in_array($membership->plan_id, $wooboi_get_allowed_memberships)) {

		 		$allowed_for_bulk_order = true;
		 	}
	    }
	}
	return $allowed_for_bulk_order;
}

function wooboi_bulk_order_input_form(){
    global $wpdb;
    ob_start();
	$allowed_for_bulk_order = wooboi_check_current_user_access();
    if(!$allowed_for_bulk_order){        
        $wooboi_access_denied_message = get_option( 'wooboi_access_denied_message' );
        if( $wooboi_access_denied_message === false ){
            return '<p class="wooboi_access_denied_msg">'.__( 'You have no sufficient permission to access this content.', 'woo-bulk-order-input' ).'</p>';
        }
        return '<p class="wooboi_access_denied_msg">'.$wooboi_access_denied_message.'</p>';    
    }
	
	
	global $current_user;
	
	
// 		$args = array(
// 		'taxonomy'     => 'product_cat',
// 		'orderby'      => 'name',
// 		'hide_empty'   => false,
// 		'meta_query' => array(
// 			array(
// 				'key'       => 'woouhc_is_hidden',
// 				'value'     => '1',
// 				'compare'   => '='
// 			)
// 		)
// 	);
	
	$args = array(
		'taxonomy'     => 'product_cat',
		'orderby'      => 'name',
		'hide_empty'   => false,
		
	);

	$all_categories = get_categories( $args );

	$exclude_categories = array();
	
	if( !empty($all_categories) ){
		
		$woouhc_hidden_categories = array();
		
		if( is_user_logged_in() ){
			
			$woouhc_hidden_categories = get_user_meta( $current_user->ID, 'woouhc_hidden_categories', true );
			
		}
		
		foreach( $all_categories as $cats ){
			
			if( is_array($woouhc_hidden_categories) && !empty($woouhc_hidden_categories) && in_array($cats->term_id, $woouhc_hidden_categories) ){
				
				continue;
			}
			
			$child_cats = (array) get_term_children( $cats->term_id, 'product_cat' );
			
			$exclude_categories[] = $cats->term_id;
			if( !empty( $child_cats ) ){
				$exclude_categories = array_merge( $exclude_categories, $child_cats );
			}
			
		}
		$exclude_categories = array_unique($exclude_categories);
	}

    global $exclude_slugs;
    $exclude_slugs_trade = $exclude_slugs['trade'];

      // Define HERE the product attribute and the terms
    $taxonomy = 'pa_pack-size';
    $terms = array( 'single-item' ); // Term names


    // Admin gets the full query whereas everyone else has to get filtered products
    if (current_user_can('administrator')){

        $query = new WC_Product_Query( array(
            'limit' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
            'status' => 'publish',
            
         ) );
    } else {

        $query = new WC_Product_Query( array(
            'limit' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
            'status' => 'publish',
            'tax_query'  =>  array(
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
                        ),

              ),
            )
          );
    }

    
    $products = $query->get_products();

 

	// if(isset($_REQUEST['ttd_test'])) {
	// 		echo '<pre>';  print_r($exclude_categories); echo '</pre>';exit;
	// }
    $product_options = '';
    if( !empty( $products ) ){
        
        foreach( $products as $key => $data ){
            
            $id = $data->get_id();
            $title = $data->get_title();
            $price = $data->get_price();
            $regular_price = $data->get_regular_price();
            $sale_price = $data->get_sale_price();
            
            $sale_price = (!$sale_price || !$sale_price == 0 ) ? $price : $sale_price;
            
            $product_options .= '<option value="'.$id.'_'.$sale_price.'">'.$title.'</option>';
               
        }
        
    }
 ?>
    
    <textarea name="wooboi_add_more_textarea" style="display:none;">
        <div class="t-row">
            <div class="wooboi_select">
                <select name="wooboi_product_options[]" data-placeholder="Choose a Product..." class="wooboi-chzn-select" width="300px">
                    <option></option><?php echo $product_options; ?>
                </select>
                <a class="wooboi_remove_rows" href="javascript:void(0);">[Delete]</a>
            </div>
            <div class="wooboi_quantity_input">
                <input name="wooboi_product_quantity[]" type="number" value="1" min="1" style="width:100px;" />
            </div>
            <div class="wooboi_sub_total"><?php echo get_woocommerce_currency_symbol();?><span>0.00</span>
            </div>
        </div>
    </textarea>
    
    <form method="post" action="<?php echo admin_url('admin-ajax.php?action=wooboi_ajax_action'); ?>">
        
        <div class="wooboi_table_container">
            
            <div class="t-row row-header">                
                <div class="wooboi_product">Product</div>
                <div class="wooboi_quantity">Quantity</div>
                <div class="wooboi_price">Price</div>
            </div>
            
            <div class="t-row">
                
                <div class="wooboi_select">
                    <select name="wooboi_product_options[]" data-placeholder="Choose a Product..." class="wooboi-chzn-select" width="300px">
                        <option></option>
                        <?php echo $product_options; ?>
                    </select>
                </div>
                <div class="wooboi_quantity_input">
                    <!-- prevent negaitve values oninput -->
                    <input type="number" name="wooboi_product_quantity[]" min="0" style="width:100px;" oninput="this.value = 
 !!this.value && Math.abs(this.value) >= 0 ? Math.abs(this.value) : null" />
                </div>
                <div class="wooboi_sub_total"><?php echo get_woocommerce_currency_symbol();?><span>0.00</span></div>
                
            </div>
            
            <div class="wooboi_add_more_row t-row">
                <div colspan="3" class="wooboi_add_more">
                    <a class="wooboi_add_more_link" href="javascript:void(0);">[Add More]</a>
                </div>
            </div>
            
            <div class="wooboi_records_total t-row">
                <div class="wooboi_add_cart_button"><button type="submit" name="wooboi-add-to-cart" value="34" class="single_add_to_cart_button button alt">Add to cart</button>
               &nbsp;<br><br> <input type="checkbox" name="wooboi_empty_cart" value="1"> Empty existing Cart before adding new items?
                </div>
                <div class="wooboi_add_more_total">Total</div>
                <div class="wooboi_add_more_total_amt"><?php echo get_woocommerce_currency_symbol();?><span>0.00</span></div>
            </div>
            
        </div>
        
    </form>
    
    <?php
    
    return ob_get_clean();
}

add_shortcode( 'woo-bulk-order-input', 'wooboi_bulk_order_input_form' );


add_action( 'wp_enqueue_scripts', 'wooboi_load_bulk_order_input_script' );

function wooboi_load_bulk_order_input_script() {
    
    global $post;
    
    if( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'woo-bulk-order-input') ) {
        
        wp_register_style( 'wooboi_chosencss', plugins_url( '/css/wooboi.chosen.css' , __FILE__ ), true, '', 'all' );
        wp_register_style( 'wooboi_customcss', plugins_url( '/css/wooboi.custom.style.css' , __FILE__ ), true, '', 'all' );
        
        wp_register_script( 'wooboi_chosenjs', plugins_url( '/js/wooboi.chosen.jquery.js' , __FILE__ ), array( 'jquery' ), '', true );
        wp_register_script( 'wooboi_customjs', plugins_url( '/js/wooboi.custom.script.js' , __FILE__ ), array( 'jquery' ), '', true );
        
        wp_enqueue_style( 'wooboi_chosencss' );
        wp_enqueue_style( 'wooboi_customcss' );
        wp_enqueue_script( 'wooboi_chosenjs' );
        wp_enqueue_script( 'wooboi_customjs' );
        
    }
    
}


add_action( 'wp_ajax_wooboi_ajax_action', 'wooboi_ajax_action_apply' );

function wooboi_ajax_action_apply() {
    $allowed_for_bulk_order = wooboi_check_current_user_access();
	if(!$allowed_for_bulk_order) {
		return '';
	}
	
    $wooboi_product_options = isset($_REQUEST['wooboi_product_options']) ? $_REQUEST['wooboi_product_options'] : '';
    $wooboi_product_quantity = isset($_REQUEST['wooboi_product_quantity']) ? $_REQUEST['wooboi_product_quantity'] : '';
    
    if( isset( $_REQUEST['wooboi_empty_cart'] ) && $_REQUEST['wooboi_empty_cart'] == '1' ){
		WC()->cart->empty_cart();
	}
    if( is_array( $wooboi_product_options ) && !empty( $wooboi_product_options ) ){
        
        foreach( $wooboi_product_options as $key => $data ){
            
            $product_data = explode('_', $data);
            
            $product_id = isset($product_data[0]) ? $product_data[0] : '';
            
            if( isset( $wooboi_product_quantity[$key] ) && $wooboi_product_quantity[$key] > 0 && $product_id > 0 ){
                
                WC()->cart->add_to_cart( $product_id, $wooboi_product_quantity[$key] );
                
            }
            
        }
        
    }
    
    $cart_url = WC()->cart->get_cart_url();
    
    wp_redirect( $cart_url );
    
    exit;
}

/* ---------------------------------------------------- */

add_action( 'woocommerce_settings_tabs_array', 'wooboi_add_woocommerce_settings_tab', 51 );

function wooboi_add_woocommerce_settings_tab( $settings_tabs ) {
    
    $settings_tabs[ 'woo_bulk_order_input' ] = __( 'Bulk Order Input', 'woo-bulk-order-input' );
    return $settings_tabs;
    
}


add_action( 'woocommerce_settings_tabs_woo_bulk_order_input', 'wooboi_settings_tab_action', 10 );

function wooboi_settings_tab_action(){
    
    woocommerce_admin_fields( wooboi_get_settings() );
    
}


add_action( 'woocommerce_update_options_woo_bulk_order_input', 'wooboi_settings_save', 10 );

function wooboi_settings_save(){
    
    woocommerce_update_options( wooboi_get_settings() );
    
}

function wooboi_get_settings(){
    
	$membership_obj = new WC_Memberships_Membership_Plans();
	$membership_all_plans = $membership_obj->get_available_membership_plans();
	$membership_options = array();
	foreach($membership_all_plans as $membership) {
		$membership_options[$membership->id] = $membership->name;
	} 
	/*echo '<pre>'; print_r($membership_plans);echo '</pre>';exit;*/
    return $settings = array(
        
        array(
            'name' => __( 'Set Permissions', 'woo-bulk-order-input' ),
            'type' => 'title',
            'desc' => __( 'The Bulk Order Input form will only appears for selected user roles. You can use this form on any places by using shortcode <b><i>[woo-bulk-order-input]</i></b>.', 'woo-bulk-order-input' ),
            'id'   => 'wooboi_set_permissions'
        ),
        
        array(
            'title'    => __( 'Allow for memberships', 'woo-bulk-order-input' ),
            'id'       => 'wooboi_get_allowed_memberships',
            'type'     => 'multiselect',
            'default'  => 'administrator',
            'class'    => 'wooboi_user_roles_select',
            'css'      => 'min-width:300px;min-height:100px;',
            'options'  =>  $membership_options
        ),
        
        array(
            'title'    => __( 'Access Denied Message', 'woo-bulk-order-input' ),
            'id'       => 'wooboi_access_denied_message',
            'type'     => 'textarea',
            'default'  => __( 'You have no sufficient permission to access this content.', 'woo-bulk-order-input' ),
            'class'    => 'wooboi_access_denied_message',
            'css'      => 'min-width:300px;min-height:100px;',
        ),
        
        array( 'type' => 'sectionend', 'id' => 'wooboi_set_permissions' ),
        
    );
    
}


/* ------- change quantity box as text field ------- */
add_action('wp_footer', 'wooboi_change_quantity_box_as_textbox');

function wooboi_change_quantity_box_as_textbox(){
    
    if( is_user_logged_in() ){
        
        $user_access = wooboi_check_current_user_access();
        $user_access = 1;
        if($user_access){
        
        
            echo '<script type="text/javascript">

                var wooboi_remove_readonly = "";

                jQuery(document).ready(function(){

                    if( jQuery( \'input[type="number"].qty\' ). length > 0 ){
                        var wooboi_remove_readonly = setInterval(wooboi_remove_readonlyTimer, 1000);
                    }

                    function wooboi_remove_readonlyTimer() {
                        jQuery( \'.wooboi_table_container input[type="number"].qty\' ).removeAttr(\'readonly\');
                        jQuery( \'.wooboi_table_container input[type="number"].qty\' ).removeAttr(\'style\');
                        jQuery( \'.wooboi_table_container input[type="number"].qty\' ).attr("style", "background-color:#ffffff!important");
                        jQuery( \'.wooboi_table_container input[type="number"].qty\' ).attr("type", "text");

                        clearInterval(wooboi_remove_readonly);
                    }
                    
                    
                    jQuery(\'form.woocommerce-cart-form\').bind("DOMSubtreeModified",function(){
                        var wooboi_remove_readonly = setInterval(wooboi_remove_readonlyTimer, 1000);
                    });
                    
                });

            </script>';

            
        }
        
        echo '<script type="text/javascript">

                jQuery(document).ready(function(){

                    if(jQuery(\'a div.quantity\').length > 0 ){
                    
                        jQuery(\'a div.quantity\').each(function(){
                            
                            var close_a = jQuery(this).closest(\'a\');
                            
                            close_a.after(close_a.find(\'div.quantity\'));
                        
                        });
                    }

                });

            </script>';
        
    }
    
}
}