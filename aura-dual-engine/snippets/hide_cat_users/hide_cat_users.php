<?php 

/**
 * Snippet Name: Hide Category From User Type / Shop functions
 * Version: 1.1.1
 * Description: These multiple functions exclude categories from users (except admin, trade, agent) when viewing front end archive, search and single templates. 
 * Dependency: WP Memberships
 *
 * @link              https://auracreativemedia.co.uk
 * @since             1.0.0
 * @package           Aura_Supercommerce
 * @requires          Advanced Custom Fields
 *
**/


if ( is_plugin_active( 'woocommerce-memberships/woocommerce-memberships.php' ) ) {

/**
 * Get term slugs for Global use by other functions.
 *
 * @return array - of slugs to exclude
 */





add_action( 'wp_loaded', 'get_term_slugs', 0, 9 );

function get_term_slugs() {

  global $exclude_slugs;
 
  ob_start();
  

    $taxonomy_terms = get_terms(array(
      'order'   => 'ASC',
	    'taxonomy' => 'product_cat',
      'hide_empty' => false,
	    'parent' => 0
	  
    ));


  
    // get the taxonomy we are going to loop through.
   // $taxonomy = array();
	  $taxonomy = new WP_Term_Query( array( 
					'taxonomy' => 'product_cat', 
					'number'  => '',
      		'posts_per_page' => -1,
					'hide_empty' => false, ) );

   // $taxonomy = get_terms('product_cat', $getArgs);
    $exclude_slugs_retail = array();
    $exclude_slugs_trade = array();

    // Start the loop through the terms
    foreach ($taxonomy->get_terms() as $term) { 
    //foreach ($taxonomy_terms as $term) { 
	
      // Get acf field Name
      $hide_field_retail = get_term_meta($term->term_id, 'hcu_hidden_user_retail', true);
	    $hide_field_trade = get_term_meta($term->term_id, 'hcu_hidden_user_trade', true);
      
      if($hide_field_retail){
            array_push($exclude_slugs_retail, $term->slug);
        }
	   	if($hide_field_trade){
            array_push($exclude_slugs_trade, $term->slug);
        }
    }

    $exclude_slugs = array('retail' => $exclude_slugs_retail, 'trade' => $exclude_slugs_trade);

   
   ob_end_flush();

  return $exclude_slugs;


}



/**
 * Get term slugs for Global use by other functions.
 *
 * @param list of terms
 * @param list of taxonomies
 * @param default args
 * @return array - of terms to exclude from every main query. This fires after get_term_slugs - init but before all other functions.
 */

add_filter( 'get_terms', 'get_subcategory_terms', 10, 3 );

function get_subcategory_terms( $terms, $taxonomies, $args ) {
      
   global $exclude_slugs;
//   var_dump($exclude_slugs);
   if ( is_admin() || current_user_can('administrator') ) return $terms;
  
    $new_terms = array();
    // if a product category and on the shop page
    // to hide from shop page, replace is_page('YOUR_PAGE_SLUG') with is_shop()
     
      
       // Get the current user
    $current_user = wp_get_current_user();
 
     if ( ( in_array( 'product_cat', $taxonomies ) || is_shop() || is_tax('product_cat') || is_tag() ) && ( (! in_array( 'tradecust', $current_user->roles ) ) && (! in_array( 'store_agent', $current_user->roles ) ) ) ) {
        foreach ( $terms as $key => $term ) {
          if($term && isset($term->slug)){
  		      	if (isset( $exclude_slugs['retail'] )){
        			  if ( ! in_array( $term->slug, $exclude_slugs['retail'] ) ) {
        		  		  $new_terms[] = $term;
  			        }
  			      }
         }
      }

        $terms = $new_terms;
    }

    if ( ( in_array( 'product_cat', $taxonomies ) || is_shop() || is_tax('product_cat') || is_tag() ) && ( (in_array( 'tradecust', $current_user->roles ) ) || (in_array( 'store_agent', $current_user->roles ) ) ) ) {
        foreach ( $terms as $key => $term ) {
		  	 if($term && isset($term->slug)){
              if (isset( $exclude_slugs['trade'] )){
      			    if ( ! in_array( $term->slug, $exclude_slugs['trade'] ) ) {
                      	$new_terms[] = $term;
                }
      			  }
           }
        }
	
        $terms = $new_terms;
    }
  
 
//   var_dump($terms);
    return $terms;
}


/**
 *  Hide our excluded products from the Related Products Area
 *
 * @param $related_posts - a list of ids of related posts WCOMM has already chosen 
 * @return array - a filtered array of product ID's minus those excluded products
 */

add_filter( 'woocommerce_related_products', 'exclude_related_products', 10, 3 );

function exclude_related_products( $related_posts, $product_id, $args ){

    global $exclude_slugs;
    $current_user = wp_get_current_user();

    if ( (! in_array( 'tradecust', $current_user->roles ) ) && (! in_array( 'administrator', $current_user->roles ) ) && (! in_array( 'store_agent', $current_user->roles ) ) ) {
    $exclude_ids = $exclude_slugs['retail'];
  }
  
    elseif ( ( in_array( 'tradecust', $current_user->roles ) ) || ( in_array( 'administrator', $current_user->roles ) ) || ( in_array( 'store_agent', $current_user->roles ) ) ) {
    $exclude_ids = $exclude_slugs['trade'];
    }

    $exclude_term_array = array();

    if($exclude_ids) :

      foreach ( $exclude_ids as $slug ){
       $term = get_term_by( 'slug',  $slug,  'product_cat'  );
         $exclude_term_array[] = $term->term_id;    
       
    }

    $all_ids = get_posts( array(
      'post_type' => 'product',
      'numberposts' => -1,
      'post_status' => 'publish',
      'fields' => 'ids',
      'tax_query' => array(
         array(
            'taxonomy' => 'product_cat',
            'terms' => $exclude_term_array, /*category name*/
            'operator' => 'IN',
            )
         ),
      ));

    $exclude_these_products = array();

      if ($all_ids) :
        foreach ( $all_ids as $id ) {
           $exclude_these_products[] = $id;
        }
      endif;
    endif;
  
    if(!empty($exclude_these_products)) :
      
        return array_diff( $related_posts, $exclude_these_products );

    else : return $related_posts;
    
    endif;

}



/**
 * Return a filtered array of terms to show the user, having removed private terms
 *
 * @param list of post results from the initated search query
 * @see REQUIRES : Smart Search Plugin
 * @return array - a filtered array of acceptable terms, categories to show the user given their role
 */

// add_filter('smart_search_query_results', 'tc_smart_search_query_results');
function tc_smart_search_query_results($post_results) {
    global $wpdb;
    global $exclude_slugs;
     $current_user = wp_get_current_user();

    if ( (! in_array( 'tradecust', $current_user->roles ) ) && (! in_array( 'store_agent', $current_user->roles ) ) ) {
    

        foreach($exclude_slugs['retail'] as $ex_slug) {
            $product_cat = get_term_by('slug', $ex_slug, 'product_cat'); 
            $exclude_terms[] =  $product_cat->term_id;
            $children_terms = get_term_children( $product_cat->term_id, 'product_cat' );
            if ( ! is_wp_error( $children_terms ) && is_array( $children_terms ) && $children_terms ) {
                foreach ( $children_terms as $children_term ) {
                    $exclude_terms[] = $children_term;
                }
            }
        }
        

        
        if(count($exclude_terms) > 0) {
            foreach($post_results as $result) { 
                $sql_query = "SELECT COUNT(object_id)
                                FROM {$wpdb->term_relationships}
                                WHERE term_taxonomy_id IN (".implode( ",", $exclude_terms ).") AND object_id = ".$result->ID;
                $total_count = $wpdb->get_var($sql_query);
                if($total_count == 0) {
                    $post_allowed[] = $result;
                }

            }
        }else {
            $post_allowed = $post_results;
        }
        
        //echo implode(" AND ", $where['and']);
    } 
  	elseif ( ( in_array( 'tradecust', $current_user->roles ) ) || ( in_array( 'store_agent', $current_user->roles ) ) ) {
    

        foreach($exclude_slugs['trade'] as $ex_slug) {
            $product_cat = get_term_by('slug', $ex_slug, 'product_cat'); 
            $exclude_terms[] =  $product_cat->term_id;
            $children_terms = get_term_children( $product_cat->term_id, 'product_cat' );
            if ( ! is_wp_error( $children_terms ) && is_array( $children_terms ) && $children_terms ) {
                foreach ( $children_terms as $children_term ) {
                    $exclude_terms[] = $children_term;
                }
            }
        }
        

        
        if(count($exclude_terms) > 0) {
            foreach($post_results as $result) { 
                $sql_query = "SELECT COUNT(object_id)
                                FROM {$wpdb->term_relationships}
                                WHERE term_taxonomy_id IN (".implode( ",", $exclude_terms ).") AND object_id = ".$result->ID;
                $total_count = $wpdb->get_var($sql_query);
                if($total_count == 0) {
                    $post_allowed[] = $result;
                }

            }
        }else {
            $post_allowed = $post_results;
        }
        
        //echo implode(" AND ", $where['and']);
    }
  
  else {
        $post_allowed = $post_results;
    }
    
    return $post_allowed;
}

/**
 * A helper function that runs a quick check to see whether the user is in a privileged role
 *
 * @return boolean - true if user is privileged
 */


function returns_true_if_user_is_trade() {
  $user = wp_get_current_user();
    if ( (! in_array( 'tradecust', $user->roles ) ) && (! in_array( 'store_agent', $user->roles ) ) ) {
    // user is not logged or is a subscriber
	
      return false;
    }
  
  // user is a trade customer or admin
  return true;
}

function returns_true_if_user_is_retail() {
  $user = wp_get_current_user();
    if ( (! in_array( 'tradecust', $user->roles ) ) && (! in_array( 'store_agent', $user->roles ) )  ) {
    // user is not logged or is a subscriber
	
      return true;
    }

  // user is a trade customer or admin
  return false;
}


/**
 * Users who do not have a privileged role are given a 404 template
 *
 * @param the template PATH that WordPress has selected by default to show the user
 * @return string - a URL for the template to show the user
 */


function restict_by_category( $template ) {
  global $exclude_slugs;
  global $wp_query;

  //var_dump($exclude_slugs);
  if ( ( is_admin() && ! is_main_query()) || current_user_can('administrator')  ) return $template; // only affect main query.
  
   
  $true_trade = true;
  $true_retail = true;
  $private_categories_retail = $exclude_slugs['retail']; // categories subscribers cannot see
  $private_categories_trade = $exclude_slugs['trade']; // categories trades cannot see


  if ( is_single() ) {
    $cats = wp_get_object_terms( get_queried_object()->ID, 'product_cat', array('fields' => 'slugs') ); // get the categories associated to the required post
    if ( array_intersect( $private_categories_retail, $cats ) ) {
      // post has a reserved category, let's check if user is retail
      if(!empty($private_categories_retail)){ 	
	  	$true_trade = returns_true_if_user_is_trade();
		//var_dump( "3:" . $true_trade );
	  }
    }
	if ( array_intersect( $private_categories_trade, $cats ) ) {
      // post has a reserved category, let's check user
     	 if(!empty($private_categories_trade)){
		    $true_retail = returns_true_if_user_is_retail();
		 // var_dump( "1:" . $true_retail );
		 }
	 
    }
   } elseif ( is_tax('product_cat', $private_categories_retail) ) {
    // the archive for one of private categories is required, let's check user
    	if(!empty($private_categories_retail)){ 
			$true_trade = returns_true_if_user_is_trade();
			//var_dump( "1:" . $true_trade );
		}
   }
    elseif ( is_tax('product_cat', $private_categories_trade) ) {
    // the archive for one of private categories is required, let's check user
	  if(!empty($private_categories_trade)){
		  $true_retail = returns_true_if_user_is_retail();
		//var_dump( "2:" . $true_retail );
	  }
   
   }

  // if allowed include the required template, otherwise include the 'not-allowed' one
  if($true_trade && $true_retail){
	
    $template = $template;
  } else {

      $wp_query->set_404();
  	  status_header( 404 );
  	  get_template_part( 404 ); exit();
  }

  return $template;
}


  /**
   * Add our Hidden User fields to the Category pages
   * 
   */

  function hcu_taxonomy_add_new_meta_field() {
      ?>
      <hr>
      <h4>Category Visibility Options</h4>
      The following 2 fields hide the category products <strong>completely</strong> from the user even if they have a direct link they are shown 0 content relating to the product. (Useful when hiding say Trade products from Retail users)
      <hr>
      <div class="form-field">
          <label for="hcu_hidden_user_retail"><?php _e('Hide This Categories Products From Retail/Guests', 'aura-dual-engine'); ?></label>
          <input type="checkbox" name="hcu_hidden_user_retail" id="hcu_hidden_user_retail" value="retail">
          <p class="description"><?php _e('', 'aura-dual-engine'); ?></p>
      </div>
      <hr>
      <div class="form-field">
          <label for="hcu_hidden_user_trade"><?php _e('Hide This Categories Products From Trade', 'aura-dual-engine'); ?></label>
          <input type="checkbox" name="hcu_hidden_user_trade" id="hcu_hidden_user_trade" value="trade">
          <p class="description"><?php _e('', 'aura-dual-engine'); ?></p>
      </div>
      <hr>
      The following field only hides the category from the archive/shop pages, meaning the user could still find the product with a direct link. (Useful if you want to have a Clearance line of products but don't want it clogging up the shop page)
      <div class="form-field">
          <label for="hcu_not_archive_visible"><?php _e('Exclude This Categories Products from the Shop page', 'aura-dual-engine'); ?></label>
          <input type="checkbox" name="hcu_not_archive_visible" id="hcu_not_archive_visible" value="false">
          <p class="description"><?php _e('', 'aura-dual-engine'); ?></p>
      </div>
      
     
      <?php

  }

  
  /**
   * Add our Forced Minimum fields to the Edit Category pages
   * 
   */
  function hcu_taxonomy_edit_meta_field($term) {

      //getting term ID
      $term_id = $term->term_id;

      // retrieve the existing value(s) for this meta field.
      $hcu_hidden_user_retail = get_term_meta($term_id, 'hcu_hidden_user_retail', true);
      $hcu_hidden_user_trade = get_term_meta($term_id, 'hcu_hidden_user_trade', true);
      $hcu_not_archive_visible = get_term_meta($term_id, 'hcu_not_archive_visible', true);

      ?>

      <tr class="form-field">
          <th scope="row" valign="top"><label for="hcu_hidden_user_retail"><?php _e('Hide This Categories Products From Retail/Guests', 'aura-dual-engine'); ?></label></th>
          <td>
              <input type="checkbox" name="hcu_hidden_user_retail" id="hcu_hidden_user_retail" value="<?php echo esc_attr($hcu_hidden_user_retail) ? esc_attr($hcu_hidden_user_retail) : 'retail'; ?>" <?php if ($hcu_hidden_user_retail) : echo 'checked'; endif; ?>>
              <p class="description"><?php _e('Hidden From Retail', 'aura-dual-engine'); ?></p>
          </td>
      </tr>
      <hr>
      <tr class="form-field">
          <th scope="row" valign="top"><label for="hcu_hidden_user_trade"><?php _e('Hide This Categories Products From Trade', 'aura-dual-engine'); ?></label></th>
           <td>
              <input type="checkbox" name="hcu_hidden_user_trade" id="hcu_hidden_user_trade" value="<?php echo esc_attr($hcu_hidden_user_trade) ? esc_attr($hcu_hidden_user_trade) : 'trade'; ?>" <?php if ($hcu_hidden_user_trade) : echo 'checked'; endif; ?>>
              <p class="description"><?php _e('Hidden From Trade', 'aura-dual-engine'); ?></p>
          </td>
      </tr>
      <hr>
     <tr class="form-field">
          <th scope="row" valign="top"><label for="hcu_not_archive_visible"><?php _e('Exclude This Categories Products from the Shop page', 'aura-dual-engine'); ?></label></th>
           <td>

              <input type="checkbox" name="hcu_not_archive_visible" id="hcu_not_archive_visible" value="<?php echo esc_attr($hcu_not_archive_visible) ? esc_attr($hcu_not_archive_visible) : 'trade'; ?>" <?php if ($hcu_not_archive_visible) : echo 'checked'; endif; ?>>
              <p class="description"><?php _e(' The following field only hides the category from the archive/shop pages, meaning the user could still find the product with a direct link. (Useful if you want to have a Clearance line of products but don\'t want it clogging up the shop page)', 'aura-dual-engine'); ?></p>
          </td>
      </tr>
      <?php
  }

  add_action('product_cat_add_form_fields', 'hcu_taxonomy_add_new_meta_field', 10, 1);
  add_action('product_cat_edit_form_fields', 'hcu_taxonomy_edit_meta_field', 10, 1);

     /**
   * Save our Forced Minimum fields submission data to the Database
   * 
   */
  function hcu_save_taxonomy_custom_meta($term_id) {

      $hcu_hidden_user_retail = filter_input(INPUT_POST, 'hcu_hidden_user_retail');
      $hcu_hidden_user_trade = filter_input(INPUT_POST, 'hcu_hidden_user_trade');
      $hcu_not_archive_visible = filter_input(INPUT_POST, 'hcu_not_archive_visible');

      update_term_meta($term_id, 'hcu_hidden_user_retail', $hcu_hidden_user_retail);
      update_term_meta($term_id, 'hcu_hidden_user_trade', $hcu_hidden_user_trade);
      update_term_meta($term_id, 'hcu_not_archive_visible', $hcu_not_archive_visible);
      

  }

  add_action('edited_product_cat', 'hcu_save_taxonomy_custom_meta', 10, 1);
  add_action('create_product_cat', 'hcu_save_taxonomy_custom_meta', 10, 1);



/**
 * To correctly queue up the filter 'restict_by_category', we need to pop it into an init action that fires earlier in the chain.
 *
 */

    add_action('wp_loaded', 'load_custom_template_woo', 999);

    function load_custom_template_woo(){
      add_filter('template_include', 'restict_by_category');

    }


    // SHOP Exclusion functions

    /**
     * Exclude products from a particular category on the shop page
     */

    function custom_pre_get_posts_query( $q ) {

      $taxonomy = new WP_Term_Query( array( 
                    'taxonomy' => 'product_cat', 
                    'number'  => '',
                    'posts_per_page' => -1,
                    'hide_empty' => false, ) );

        $exclude_slugs_shop = array();

        // Start the loop through the terms
        foreach ($taxonomy->get_terms() as $term) { 
        //foreach ($taxonomy_terms as $term) { 
                    
              // Get acf field Name
              $not_archive_visible = get_term_meta($term->term_id, 'hcu_not_archive_visible', true);

              if($not_archive_visible){
                  array_push($exclude_slugs_shop, $term->slug);
               }

        }



        $tax_query = (array) $q->get( 'tax_query' );

        $tax_query[] = array(
               'taxonomy' => 'product_cat',
               'field' => 'slug',
               'terms' => $exclude_slugs_shop,
               'operator' => 'NOT IN'
        );
        

       if(is_shop()) :
         $q->set( 'tax_query', $tax_query );
       endif;

    }

    add_action( 'woocommerce_product_query', 'custom_pre_get_posts_query' );  



// https://www.businessbloomer.com/woocommerce-exclude-category-from-products-shortcode/

// add_filter( 'woocommerce_shortcode_products_query' , 'bbloomer_exclude_cat_shortcodes');
     
    // function bbloomer_exclude_cat_shortcodes($query_args){
     
    //     $query_args['tax_query'] =  array(array( 
    //             'taxonomy' => 'product_cat', 
    //             'field' => 'slug', 
    //             'terms' => array('MAGIC'), // Don't display products from this category
    //             'operator' => 'NOT IN'
    //         )); 
     
    //     return $query_args;
    // }

  
}
 
 ?>