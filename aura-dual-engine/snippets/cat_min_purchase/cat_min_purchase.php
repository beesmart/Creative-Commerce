<?php

/**
 * Snippet Name: Force Category Cart Minimum
 * Version: 1.2.1
 * Description: Renders notices and prevents checkout if the cart does not contain a minimum number of products in a category. Use shortcode '[category-products-required]' to display a notice for required amounts for the currently viewed product.
 * Dependency: WP Memberships
 *
 * @link              https://auracreativemedia.co.uk
 * @since             1.1.3
 * @package           Aura_Supercommerce
 *
**/


if ( is_plugin_active( 'woocommerce-memberships/woocommerce-memberships.php' ) ) {


	function fccm_check_category_for_minimum() {
		$user_id = get_current_user_id();
		
		// Check for admin user first - if true, we don't need further checks
		if (current_user_can('manage_options')) {
			return;
		}

		// if user is NOT allowed to order (ignoring specific term checks for now)
		if (!should_restriction_apply($user_id)) {
			return;
		}
		
		$terms = get_terms('product_cat');
		
		// Grab the meta fields which contain the min. data for each category
		foreach ($terms as $term) {
			$term_id = $term->term_id;
			$meta_min = get_term_meta($term_id, 'fccm_meta_minimum', true);
			
			// Check if user allowed to order from this specific term
			if (!should_restriction_apply($user_id, $term_id)) {
				continue; // skip to the next iteration
			}
			
			// Check cart items if $meta_min is true
			if ($meta_min) {
				$category_name = '<a href="' . get_term_link($term_id, 'product_cat') . '">' . $term->name . '</a>';
				$category_quantity_in_cart = fccm_get_category_quantity_in_cart($term_id);
				
				// Check if cart items for the category meet the minimum requirement
				if ($category_quantity_in_cart < $meta_min && $category_quantity_in_cart > 0) {
					wc_add_notice(sprintf('Sorry, you must purchase at least %1$s products from the %2$s category to check out. You currently have %3$s.', $meta_min, $category_name, $category_quantity_in_cart), 'error');
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
	
		if (!should_restriction_apply($user_id, $term_id) || current_user_can('manage_options')) :
			return $message;
		endif;
	
		$product = wc_get_product($product_id);
		$product_title = $product->get_title();
	
		$terms = get_the_terms($product_id, 'product_cat');
	
		if(!$terms) :
			return $message;
		endif;
	
		$term_id = $terms[0]->term_id;
		$term_name = $terms[0]->name;
	
		$meta_min = get_term_meta($term_id, 'fccm_meta_minimum', true);
	
		if ($meta_min) :
			$current_basket_total = fccm_get_category_quantity_in_cart($term_id);
			$check_amount_pass = ($meta_min > $current_basket_total) ? "amount_fail" : "amount_pass";
	
			return $product_title . " has been added to your basket. Please note products in the " . $term_name . " category, require a minimum purchase of " . $meta_min . " items. 
			<span class='".$check_amount_pass."'>You currently have: " . $current_basket_total . " items in your basket.</span>";
	
		else :
			return $message;
		endif;
	}
	

	

	/**
	 * Show the cat cart min message next to the price on the single-product.php template
	 * 
	 * @since 1.1.2
	 * 
	*/

	function show_nextto_price_on_singleproduct($price) {

		$user_id = get_current_user_id();
	
		if (is_product()) :
	
			global $product;
			$product_id = $product->get_id();
	
			$this_product = wc_get_product($product_id);
			$product_title = $this_product->get_title();
	
			$terms = get_the_terms($product_id, 'product_cat');
	
			if ($terms) :
	
				$term_id = $terms[0]->term_id;
				$term_name = $terms[0]->name;
	
				if (should_restriction_apply($user_id, $term_id)) :
	
					$meta_min = get_term_meta($term_id, 'fccm_meta_minimum', true);
	
					if ($meta_min) :
	
						$price .= '<span class="min-cat-price_html"> minimum order for ' . $term_name . ': ' . $meta_min . '</span>';
						return $price;
	
					endif;
	
				endif;
	
			else:
				return false;
			endif;
	
		endif;
	
		return $price;
	}
	
	add_filter( 'woocommerce_get_price_html', 'show_nextto_price_on_singleproduct' );

	/**
	 * Show the cat cart min message on the archive term page(s)
	 * @since 1.1.2
	 * 
	*/


	function show_cat_min_notice_on_archives() {
		$user_id = get_current_user_id();
	
		if (is_tax()) :
			$term_id = get_queried_object()->term_id;
			$term_name = get_queried_object()->name;
	
			if (should_restriction_apply($user_id, $term_id)) {
	
				$meta_min = get_term_meta($term_id, 'fccm_meta_minimum', true);
	
				if ($meta_min) :
					$cat_min_notice = '<p class="min-cat-price_html">Minimum order for ' . $term_name . ': ' . $meta_min . '</p>';
					echo $cat_min_notice;
				endif;
	
			}

		endif;
	}

	         
	// add the action 
	add_action( 'woocommerce_before_shop_loop', 'show_cat_min_notice_on_archives', 10, 2 ); 



	/**
	 * Returns the category requirement of the currently viewed product, location of output defined by shortcode. Trade memberships Only.
	 * 
	 * @since 1.1.0
	 * 
	*/

	function fccm_get_current_product_cat_requirement() {

		global $post;
		$user_id = get_current_user_id();
	
		$terms = get_the_terms($post->ID, 'product_cat');
	
		if (isset($terms[0])) {
			$term_id = $terms[0]->term_id;
			$term_name = $terms[0]->name;
			$meta_min = get_term_meta($term_id, 'fccm_meta_minimum', true);
	
			if (should_restriction_apply($user_id, $term_id)) {
	
				if ($meta_min) {
					$current_cat_html = "Minimum order for the " . $term_name . " category is " . $meta_min . " items.";
					return $current_cat_html;
				}
			} else {
				// Logic if user is NOT allowed to order. This part can be omitted if not needed.
				return false;
			}
		}
	
		return false;
	}
	

	add_shortcode( 'category-products-required', 'fccm_get_current_product_cat_requirement' );


	/**
	 * Will check if a user should see the enforced restriction for the categories
	 * @since 1.5.0
	 * 
	*/


	function should_restriction_apply($user_id, $term_id = null) {
		$is_trade_user = false;
	
		// Assuming trade user if they have a membership
		if (!empty($user_id)) {
			$memberships = wc_memberships_get_user_active_memberships($user_id);
			$is_trade_user = !empty($memberships);
		}
	
		if($term_id !== null) {
			$trade_switch = get_term_meta($term_id, 'fccm_meta_min_trade', true);
			$retail_switch = get_term_meta($term_id, 'fccm_meta_min_retail', true);
	
			// If user is retail and retail switch is off or
			// if user is trade and trade switch is off, return false
			if (($is_trade_user && $trade_switch !== 'on') || (!$is_trade_user && $retail_switch !== 'on')) {
				return false;
			}
		}
	
		return true;
	}
	
	


	
	/**
	 * Add our Forced Minimum fields to the Category pages
	 * 
	 */

	function fccm_taxonomy_add_new_meta_field() {
	    ?>

	    <div class="form-field">
	        <label for="fccm_meta_minimum"><?php _e('Force Category Cart Minimum', 'aura-dual-engine'); ?></label>

			<table>
				<tr>
					<td>Select user roles to apply restriction to, and then select the value required in the cart for the user to checkout.</td>
				</tr>
					<tr>
						<td><input type="checkbox" name="fccm_meta_min_trade" id="fccm_meta_min_trade">
				<label for="fccm_meta_min_trade"><span class='dashicons dashicons-awards'></span> Trade - Apply restriction to Trade users</label></td>
					</tr>
					<tr>
						<td><input type="checkbox" name="fccm_meta_min_retail" id="fccm_meta_min_retail">
				<label for="fccm_meta_min_retail"><span class='dashicons dashicons-groups'></span> Retail - Apply restriction to retail/guest users</label></td>
					</tr>
			</table>
				
			<hr>
	        <input type="number" name="fccm_meta_minimum" id="fccm_meta_minimum">
	        <p class="description"><?php _e('Trade Only. How many products in this category does a trade user need to have in their cart before you allow them to purchase. e.g. Trade Users must buy at least 8 of anything in the Coaster category. (Admin Bypasses this requirement)', 'aura-dual-engine'); ?></p>
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
	    $fccm_meta_min_trade = get_term_meta($term_id, 'fccm_meta_min_trade', true);
		$fccm_meta_min_retail = get_term_meta($term_id, 'fccm_meta_min_retail', true);
		$fccm_meta_minimum = get_term_meta($term_id, 'fccm_meta_minimum', true);

	    ?>
	
	    <tr class="form-field" style="border-top: 1px solid grey;">
	        <th scope="row" valign="top"><label for="fccm_meta_minimum"><?php _e('Force Category Cart Minimum', 'aura-dual-engine'); ?></label></th>
	        <td>
				<table>
					<tr>
						<td>Select user roles to apply restriction to, and then select the value required in the cart for the user to checkout.</td>
					</tr>
					<tr>
						<td><input type="checkbox" name="fccm_meta_min_trade" id="fccm_meta_min_trade" <?php checked($fccm_meta_min_trade, 'on'); ?>>
				<label for="fccm_meta_min_trade"><span class='dashicons dashicons-awards'></span> Trade - Apply restriction to Trade users</label></td>
					</tr>
					<tr>
						<td><input type="checkbox" name="fccm_meta_min_retail" id="fccm_meta_min_retail" <?php checked($fccm_meta_min_retail, 'on'); ?>>
				<label for="fccm_meta_min_retail"><span class='dashicons dashicons-groups'></span> Retail - Apply restriction to retail/guest Users</label></td>
					</tr>
				</table>
				
				<hr>
	            <input type="number" name="fccm_meta_minimum" id="fccm_meta_minimum" value="<?php echo esc_attr($fccm_meta_minimum) ? esc_attr($fccm_meta_minimum) : ''; ?>">
	            <p class="description"><?php _e('How many products in this category does a user need to have in their cart before you allow them to purchase. e.g. Users must buy at least 8 of anything in the Coaster category.', 'aura-dual-engine'); ?></p>
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

	    $fccm_meta_min_trade = filter_input(INPUT_POST, 'fccm_meta_min_trade');
		$fccm_meta_min_retail = filter_input(INPUT_POST, 'fccm_meta_min_retail');
		$fccm_meta_minimum = filter_input(INPUT_POST, 'fccm_meta_minimum');

	    update_term_meta($term_id, 'fccm_meta_min_trade', $fccm_meta_min_trade);
		update_term_meta($term_id, 'fccm_meta_min_retail', $fccm_meta_min_retail);
		update_term_meta($term_id, 'fccm_meta_minimum', $fccm_meta_minimum);
	
	}

	add_action('edited_product_cat', 'fccm_save_taxonomy_custom_meta', 10, 1);
	add_action('create_product_cat', 'fccm_save_taxonomy_custom_meta', 10, 1);

	


}