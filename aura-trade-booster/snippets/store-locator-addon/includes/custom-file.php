<?php


add_filter( 'wpsl_settings_tab','aslc_add_custom_settings');

function aslc_add_custom_settings($tabs)

{

	$tabs['address_settings'] =  __( 'Address Settings', 'wpsl' );

	return $tabs;

}


add_filter( 'wpsl_settings_tab','aslc_add_refresh_settings');

function aslc_add_refresh_settings($tabs)

{

	$tabs['refresh_settings'] =  __( 'Refresh', 'wpsl' );

	return $tabs;

}

add_action( 'wpsl_settings_section', 'add_refresh_settings');

function add_refresh_settings($current_tab){
    	if($current_tab == 'refresh_settings'){
    	    ?>
    	    <form action="<?php echo admin_url( 'admin-post.php' ); ?>">
              <input type="hidden" name="action" value="refresh_all_stockist_data">
              <input type="submit" value="Refresh All Stockist Data">
            </form>
    	    <?php
    	}
}

add_action( 'wpsl_settings_section', 'add_address_settings');

function add_address_settings($current_tab)

{

	if($current_tab == 'address_settings')

	{

		$address_type = $_REQUEST['address_type'];

		if(isset($address_type))

		{

			 update_option( 'wpsl_address_type', $address_type );

		}

		$get_address_type = get_option('wpsl_address_type');

		?>

		<div id="address_settings">

	        <form id="wpsl-settings-form" method="post" action="" autocomplete="off" accept-charset="utf-8">

	            <div class="postbox-container">

	                <div class="metabox-holder">

	                    <div id="wpsl-api-settings" class="postbox">

	                        <h3 class="hndle"><span><?php _e( 'Address Settings', 'wpsl' ); ?></span></h3>

	                        <div class="inside">

	                            <p>

	                                <label for="wpsl-api-browser-key"><?php _e( 'Address Type', 'wpsl' ); ?>:</label>

	                                <input type="radio" value="billing" name="address_type" class="textinput" id="wpsl-api-browser-key" <?php echo ($get_address_type == 'billing') ?  "checked" : "" ;  ?>/> Billing Address 



	                                &nbsp;<input type="radio" value="shipping" name="address_type" class="textinput" id="wpsl-api-browser-key" <?php echo ($get_address_type == 'shipping') ?  "checked" : "" ;  ?> />Shipping Address

	                            </p>

	                            <p class="submit">

	                                <input type="submit" value="<?php _e( 'Save Changes', 'wpsl' ); ?>" class="button-primary">

	                            </p>

	                        </div>

	                    </div>

	                </div>

	            </div>

	        </form>

	    </div>

		<?php

	}

}




/**
 * Register a custom menu page.
 */
function wpsl_duplicates_sub_menu_page() {

    add_submenu_page(
    	'edit.php?post_type=wpsl_stores',
        __( 'Duplicates', 'wpsl_stockists' ),
        'Duplicates',
        'manage_options',
        'wpsl-duplicates',
        'wpsl_duplicates_page_callback',
        2

    );

}


add_action( 'admin_menu', 'wpsl_duplicates_sub_menu_page' );



function wpsl_duplicates_page_callback() {
    echo '<div class="wrap"><div id="icon-tools" class="icon32"></div>';
        echo '<h2>Duplicates</h2>';
    echo '</div>';

    // Run a wordpress query on WPSL

    // Check post code, name and first line of address all match if so display if not skip

    function get_duplicates() {

    $duplicates = array();
    $dupe_ids = array();

    echo '<br><table class="widefat fixed" cellspacing="0">';

    $query_all = new WP_Query( array(

	    'post_type' => 'wpsl_stores',
	    'posts_per_page' => -1,
   	    'fields' => 'ids',
    ) );

    $all_posts = $query_all->posts;
   
    foreach ( $all_posts as $post_id ) {

       echo '<tr class="alternate">';

    if ( in_array( $post_id, $duplicates ) ) {

    continue;


    }
	    $args = array(
		    'post_type' => 'wpsl_stores',
		    'posts_per_page' => -1,

		    'meta_query' => array(

	    		array(
	   				 'key' => 'wpsl_zip',
	   				 'value' => get_post_meta( $post_id, 'wpsl_zip', true ),
	   				 'compare' => '=',
	   			 ),
	   		 
	   		 ),

	   		 'post__not_in' => array( $post_id ),
	   		 'fields' => 'ids',
	    );

	    $query = new WP_Query( $args );
	   


	     if($query->posts) {

	     	$dupe_ids[] = $post_id;
	     	
	     	// Let's put all the ID's into their own array so we can sort by ASC/DESC order
	     	foreach( $query->posts as $post ) {
 	     		//echo get_the_title( $post );
 	     		$dupe_ids[] = $post;
 	     	}

 	     
 	     	sort($dupe_ids);
 	     	$original = min($dupe_ids);
 	     	$dupes_remove = array_slice($dupe_ids, 1);



			$html = '<td class="column-columnname">';
	     		$html .= '<h3>' . get_the_title( $original ) . '</h3>';
	     	$html .= '</td>';

 			$html .= '<td class="column-columnname">';

 	     		foreach( $dupes_remove as $dupe ) {
 	     			$html .= '<p>' . get_the_title( $dupe ) . ' - <a href="' . get_delete_post_link( $dupe ) . '">Delete</a></p>';
 	     		}

 	     	$html .= '</td>';
	     	

	     	echo $html;
	     	
	     }



	    $duplicates = array_merge( $duplicates, $query->posts );
	   // // $this_id = $query->posts;

	   // $address_post_id = get_the_ID();
	  // var_dump($address_post_id);

	    
	       echo '</tr>';
    }

    	array_unique( $duplicates );

    
    	echo '</table>';

    	return $duplicates;

    }

   // get_duplicates();









function aj_get_duplicates() {

global $wpdb;

	$args = array( 'post_type' => 'wpsl_stores', 'posts_per_page' => -1 );

	$posts = get_posts( $args );

	$post_ids_processed = array();

	foreach( $posts as $post_data ){
		$post_id = $post_data->ID;

	if ( in_array( $post_id, $post_ids_processed ) ) {
			continue;
	}

	$wpsl_zip = get_post_meta( $post_id, 'wpsl_zip', true );
	$wpsl_address = get_post_meta( $post_id, 'wpsl_address', true );

	$post_ids_processed[] = $post_id;

	if( $wpsl_zip ){
		$result = $wpdb->get_results( "SELECT post_id FROM ".$wpdb->prefix."postmeta WHERE meta_key = 'wpsl_zip' AND meta_value = '".$wpsl_zip."' " );
		if( $result ){
			foreach( $result as $row ){
				$duplicate_ids[] = $row->post_id;
			}
		}
	}



	if( !empty( $duplicate_ids ) ){
		$duplicate_arr[ $post_id ] = $duplicate_ids;
		$post_ids_processed[] = $duplicate_ids;
	}

	array_unique( $duplicate_arr );
	var_dump($duplicate_arr);
	return $duplicate_arr;


	}

}



aj_get_duplicates();



}



/*

DELETE p, pm1
FROM
   sdc_posts p,
   sdc_postmeta pm1,
   sdc_postmeta pm2
WHERE 
    p.ID = pm1.post_id
    AND p.post_type = 'wpsl_stores'
    AND pm1.post_id > pm2.post_id 
    AND pm1.meta_key = 'wpsl_zip' 
    AND pm1.meta_key = pm2.meta_key 
    AND pm1.meta_value = pm2.meta_value



   */







?>