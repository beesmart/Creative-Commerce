<?php

/**
 * Snippet Name: Force Category Cart Minimum
 * Version: 1.0.0
 * Description: Renders notices and prevents checkout if the cart does not contain a minimum number of products in a category. Use shortcode '[category-products-required]' to display a notice for required amounts for the currently viewed product.
 * Dependency: WP Memberships
 *
 * @link              https://auracreativemedia.co.uk
 * @since             1.0.0
 * @package           Aura_Supercommerce
 *
**/



if ( is_plugin_active( 'woocommerce-memberships/woocommerce-memberships.php' ) ) {



	function fccm_check_category_for_minimum() {
	  
		$user_id = get_current_user_id();
	  
	  	if(! empty($user_id)){
		  	$memberships = wc_memberships_get_user_active_memberships( $user_id );
		}
	  
		if ( ! empty( $memberships ) ) {
	  		
	  		$terms = get_terms( 'product_cat' );
	  		$category_array = array();

	  		// Grab the meta fields which contain the min. data for each category
	  		foreach ($terms as $value) {
	  			
	  			$meta_min = get_term_meta($value->term_id, 'fccm_meta_minimum', true);
	  			if ($meta_min) :
	  				$category_array[] = [$value->term_id => $meta_min];
	  			
	  			endif;
	  		}

	  		// Compare the meta fields to whats in the cart
	  		foreach( $category_array as $min_quantity_array ) {
	  			foreach( $min_quantity_array as $category_id => $min_quantity ) {

	  				 $product_cat = get_term( $category_id, 'product_cat' );

	  				 if($product_cat) {
	  				  $category_name = '<a href="' . get_term_link( $category_id, 'product_cat' ) . '">' . $product_cat->name . '</a>';

	  				  // get the quantity category in the cart
	  				  $category_quantity = fccm_get_category_quantity_in_cart( $category_id );


	  				  if ( $category_quantity < $min_quantity && $category_quantity > 0 ) {
	  					  // render a notice to explain the minimum
	  					  wc_add_notice( sprintf( 'Sorry, you must purchase at least %1$s products from the %2$s category to check out. You currently have %3$s.', $min_quantity, $category_name, $category_quantity ), 'error' );
	  				  }
	  				 }
	  			}


			 }

		}

	}

	add_action( 'woocommerce_check_cart_items', 'fccm_check_category_for_minimum' );




	/**
	 * Returns the quantity of products from a given category in the WC cart
	 * 
	 * @param int $category_id the ID of the product category
	 * @return in $category_quantity the quantity of products in the cart belonging to this category
	 */
	function fccm_get_category_quantity_in_cart( $category_id ) {

		// get the quantities of cart items to check against
		$quantities = WC()->cart->get_cart_item_quantities();
		
		// start a counter for the quantity of items from this category
		$category_quantity = 0;


		// loop through cart items to check the product categories
		foreach ( $quantities as $product_id => $quantity ) {

			$_product = wc_get_product( $product_id );	

			// we need to eliminate Bundles from the calculation as they will distort the figures. If a bundle is found skip over and iterate the next one
			if ($_product->is_type( 'bundle' )) : continue; endif;
		   
			$product_categories = get_the_terms( $product_id, 'product_cat');
	    	//check if it works else its a variation
	    	if(!$product_categories){
				$variation = wc_get_product($product_id);
			//get variation parent id number
				$parent_product_id = $variation->get_parent_id();
			//get categories of parent id number
				$product_categories = get_the_terms( $parent_product_id, 'product_cat');
			}
			
			// check the categories for our desired one
			foreach ( $product_categories as $category ) {

				// if we find it, add the line item quantity to the category total
				if ( $category_id === $category->term_id ) {
					$category_quantity += $quantity;
				}
			}
		}

		return $category_quantity;
	}

	/**
	 * Edit the notice of the 'add to cart message' that pops up when a user clicks Add to cart. The message should now include the category minimum requirement.
	 * 
	 * @since 1.1.0
	 * 
	*/

	add_filter( 'wc_add_to_cart_message', 'override_cart_message_cat_req_included', 10, 2 );

	function override_cart_message_cat_req_included($message, $product_id) {

		$user_id = get_current_user_id();
	
	  	if(! empty($user_id)){
		  	$memberships = wc_memberships_get_user_active_memberships( $user_id );
		}
	  
		if ( ! empty( $memberships ) ) :

			$product = wc_get_product( $product_id );
			$product_title = $product->get_title();

			$terms = get_the_terms( $product_id, 'product_cat' );

			$term_id = $terms[0]->term_id;
			$term_name = $terms[0]->name;
			$meta_min = get_term_meta($term_id, 'fccm_meta_minimum', true);

			if ($meta_min) :
				$check_amount_status = False;
				$current_basket_total = fccm_get_category_quantity_in_cart( $term_id );

				if ($meta_min > $current_basket_total) : $check_amount_pass = "amount_fail"; else : $check_amount_pass = "amount_pass"; endif;

				return $product_title . " has been added to your basket. Please note products in the " . $term_name . " category, require a minimum purchase of " . $meta_min . " items. 
				<span class='".$check_amount_pass."'>You currently have: " . $current_basket_total . " items in your basket.</span>";

			else : return $message;

			endif;

		 else : return $message;

		endif;

	}

	/**
	 * Returns the category requirement of the currently viewed product, location of output defined by shortcode. Trade memberships Only.
	 * 
	 * @since 1.1.0
	 * 
	*/

	function fccm_get_current_product_cat_requirement() {

		$user_id = get_current_user_id();
	
	  	if(! empty($user_id)){
		  	$memberships = wc_memberships_get_user_active_memberships( $user_id );
		}
	  
		if ( ! empty( $memberships ) ) {
	  			
	  		global $post;
	  		$terms = get_the_terms( $post->ID, 'product_cat' );

	  		$term_id = $terms[0]->term_id;
	  		$term_name = $terms[0]->name;
	  		$meta_min = get_term_meta($term_id, 'fccm_meta_minimum', true);

	  		if($meta_min) :
	  			$current_cat_html = "Minimum order for the " . $term_name . " category is " . $meta_min . " items.";
	  			return $current_cat_html;
	  		endif;
	  		
	  	}

		

	}

	add_shortcode( 'category-products-required', 'fccm_get_current_product_cat_requirement' );


	/**
	 * Add our Forced Minimum fields to the Category pages
	 * 
	 */

	function fccm_taxonomy_add_new_meta_field() {
	    ?>

	    <div class="form-field">
	        <label for="fccm_meta_minimum"><?php _e('Force Category Cart Minimum', 'aura-dual-engine'); ?></label>
	        <input type="number" name="fccm_meta_minimum" id="fccm_meta_minimum">
	        <p class="description"><?php _e('Trade Only. How many products in this category does a trade user need to have in their cart before you allow them to purchase. e.g. Trade Users must buy at least 8 of anything in the Coaster category.', 'aura-dual-engine'); ?></p>
	    </div>
	   
	    <?php

	}


	
	/**
	 * Add our Forced Minimum fields to the Edit Category pages
	 * 
	 */
	function fccm_taxonomy_edit_meta_field($term) {

	    //getting term ID
	    $term_id = $term->term_id;

	    // retrieve the existing value(s) for this meta field.
	    $fccm_meta_minimum = get_term_meta($term_id, 'fccm_meta_minimum', true);

	    ?>
	    <tr class="form-field">
	        <th scope="row" valign="top"><label for="fccm_meta_minimum"><?php _e('Force Category Cart Minimum', 'aura-dual-engine'); ?></label></th>
	        <td>
	            <input type="number" name="fccm_meta_minimum" id="fccm_meta_minimum" value="<?php echo esc_attr($fccm_meta_minimum) ? esc_attr($fccm_meta_minimum) : ''; ?>">
	            <p class="description"><?php _e('Trade Only. How many products in this category does a trade user need to have in their cart before you allow them to purchase. e.g. Trade Users must buy at least 8 of anything in the Coaster category.', 'aura-dual-engine'); ?></p>
	        </td>
	    </tr>
	   
	    <?php
	}

	add_action('product_cat_add_form_fields', 'fccm_taxonomy_add_new_meta_field', 10, 1);
	add_action('product_cat_edit_form_fields', 'fccm_taxonomy_edit_meta_field', 10, 1);

     /**
	 * Save our Forced Minimum fields submission data to the Database
	 * 
	 */
	function fccm_save_taxonomy_custom_meta($term_id) {

	    $fccm_meta_minimum = filter_input(INPUT_POST, 'fccm_meta_minimum');

	    update_term_meta($term_id, 'fccm_meta_minimum', $fccm_meta_minimum);
	
	}

	add_action('edited_product_cat', 'fccm_save_taxonomy_custom_meta', 10, 1);
	add_action('create_product_cat', 'fccm_save_taxonomy_custom_meta', 10, 1);

	


}