<?php

/**
 * Snippet Name: Add Custom Columns to Admin Orders
 * Version: 1.0.0
 * Description: Adds columns to the Admin Orders page that helps the admin see the user role and payment methods for each order
 * 
 *
 * @link              https://auracreativemedia.co.uk
 * @since             1.0.0
 * @package           Aura_Supercommerce
 *
**/


add_filter( 'manage_edit-shop_order_columns', 'add_payment_method_column', 20 );

function add_payment_method_column( $columns ) {
  $new_columns = array();
 	foreach ( $columns as $column_name => $column_info ) {
 	$new_columns[ $column_name ] = $column_info;
		 if ( 'order_total' === $column_name ) {
 			$new_columns['order_payment'] = __( 'Payment Method', 'my-textdomain' );
		    $new_columns['order_membership'] = __( 'Membership', 'my-textdomain' );
 		}
	  
 }
  
 return $new_columns;
  
}

add_action( 'manage_shop_order_posts_custom_column', 'add_payment_method_column_content' );

function add_payment_method_column_content( $column ) {
 global $post;
	 if ( 'order_payment' === $column ) {
 		 $order = wc_get_order( $post->ID );
		 echo $order->get_payment_method_title();
 	}
  	if ( 'order_membership' === $column ) {
	     $order = wc_get_order( $post->ID );
	     $user = $order->get_user();
	     if($user){
			 $user_id = $user->ID;
		     $user_info = get_userdata($user_id);
		     if(!empty($user_info)){
                $user_roles = $user_info->roles;

			   foreach($user_roles as $user_role) {
	
				 if ($user_role == "tradecust"){
					  echo "<span class='dashicons dashicons-awards'></span>&nbsp; Trade";
				 } elseif ($user_role == "customer") {
					  echo "<span class='dashicons dashicons-businessperson'></span>&nbsp; Retail - Logged In";
				 } 
				 elseif ($user_role == "administrator") {
					  echo "<span class='dashicons dashicons-shield-alt'></span>&nbsp; Admin";
				 } 
				 elseif (empty($user_role)) {
					  echo "<span class='dashicons dashicons-groups'></span>&nbsp; Guest";
				 }
				 else {
					  echo $user_role;
				 }

			   }
              } else {
			  echo "<span class='dashicons dashicons-groups'></span>&nbsp; Guest";
			}
		   
		 }
	     else {
			  echo "<span class='dashicons dashicons-groups'></span>&nbsp; Guest";
			}
    	
      	
	  	
          
	     
	  
 	}
  
    
  

}