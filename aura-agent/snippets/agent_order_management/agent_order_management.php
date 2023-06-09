<?php

/*
* Snippet Name: Agent Order Management
* Version: 1.0.1
* Description: Agent Management - Includes existing legacy fucntionality
*
* @link              https://digitalzest.co.uk
* @since             1.0.1
* @package           Aura_Supercommerce
*
*/


add_action('admin_init', 'create_agent_role', 1000);
add_action('pre_get_posts', 'agent_orders', 1000);

add_filter('wp_count_posts', 'agent_count_posts', 100 ,3);

add_filter('user_has_cap', 'restrict_editing_old_posts', 100 ,3);	
add_filter('pre_get_users', 'filter_users_by_agent_access');

//Filters the query arguments used to retrieve users for the current users list table.
add_filter('users_list_table_query_args', 'users_list_table_query_args');

// editable_roles is a filter applied by the function get_editable_roles() to the list of roles that one user can assign to others
add_filter( 'editable_roles', 'filter_editable_roles' );

// Agent assignment on user profile pages
add_action( 'profile_update', 'save_user_agent_fields', 10, 1);
add_action( 'user_register', 'save_user_agent_fields', 10, 1 );
add_action( 'show_user_profile', 'user_agent_fields' );
add_action( 'edit_user_profile', 'user_agent_fields' );

//Filters the list of available list table views
add_action( 'views_users', 'views_users' );

// Hide NUA plugin from other users
add_action( 'admin_head', 'admin_styles_scripts' );
add_action( 'new_user_approve_show_admin_page', 'new_user_approve_show_admin_page' );

// Agent Meta
add_action( 'woocommerce_email_order_meta', 'email_order_meta', 10, 3 );
add_action( 'woocommerce_saved_order_items', 'woocommerce_saved_order_items', 10, 2 ); 	

add_action( 'user_profile_update_errors', 'user_profile_update_errors', 10, 3 );
add_action( 'admin_menu', 'waom_add_user_sub_menu' );
add_action( 'admin_footer', 'active_stockist_menu' );

//add_filter( 'new_user_approve_filter_button', 'agent_filter', 10, 1 );
add_action( 'manage_users_extra_tablenav', 'agent_filter' );	
add_action( 'pre_get_users', 'filter_users_by_agent', 99, 1 );
add_filter( 'manage_users_columns', 'register_user_agent_column' );
add_filter( 'manage_users_custom_column', 'render_user_user_agent', 10, 3 );



/**
 * Add agent to order meta
 *
 * @since 1.0.0
 *
*/

function woocommerce_saved_order_items( $order_id, $items ) { 
	$is_store_manager = is_current_store_agent(); 
	if($is_store_manager) {
		update_post_meta($order_id, '_order_agent_user', get_current_user_id());
	}
}

/**
 * Add agent to EMAIL order meta and display on email
 *
 * @since 1.0.0
 *
*/

function email_order_meta($order_obj, $sent_to_admin, $plain_text) {

	$customer_user_id = get_post_meta( $order_obj->get_order_number(), '_customer_user', true );
	$agent_user = (int)get_user_meta( $customer_user_id, '_agent_user', true );
	$agent_user_id = (int)get_post_meta($order_obj->get_id(), '_order_agent_user', true);

	if($agent_user > 0 && $agent_user == $agent_user_id) {
		$author_obj = get_user_by('id', $agent_user_id);
		$user_phone = get_user_meta($agent_user_id, 'billing_phone', true);
		if ( $plain_text === false ) { 
			echo '<h2>Agent Information</h2>
			<ul>
			<li><strong>Name: </strong> '.$author_obj->first_name .' '.$author_obj->last_name.'</li>
			<li><strong>Email: </strong> '.$author_obj->user_email.'</li>
			<li><strong>Phone: </strong> '.$user_phone.'</li>
			</ul>';

		} else {

			echo "Agent Information\n
			Name: $author_obj->first_name $author_obj->last_name
			Email: $author_obj->user_email
			Phone: $user_phone";	

		}

	}


}


/**
 * If the following 2 checks fail it hides New User Approve plugin - presumbly to hide from lower authority users
 *
 * @since 1.0.0
 *
*/

function new_user_approve_show_admin_page($status) {
	$is_store_manager = is_current_store_agent(); 

	if($is_store_manager) {
		$status = false;
	}

	return $status;
}

function admin_styles_scripts( ) {
	$is_store_manager =  is_current_store_agent(); 
	if($is_store_manager) { 
	?>
		<style>
			#menu-dashboard {
				display: none;
			}
			.notice-warning, .woocommerce-message {
				display: none;
			}
		</style>
	<?php 
	}
}
	

/**
 * I believe this shows the relevant users that belong to the agent when viewing 'users stockists' page.
 *
 * @since 1.0.0
 *
*/

function views_users( $views ) {
	global $wpdb;

	$is_store_manager =  is_current_store_agent();   

	if($is_store_manager) { 
		$sql = "SELECT COUNT(user_id) FROM ".$wpdb->prefix."usermeta WHERE meta_key='_agent_user' AND meta_value=".get_current_user_id();
		$total_user = $wpdb->get_var($sql);
		$views = array("all"=>'All ('.$total_user.')');			
	}

	else if(is_admin() && isset($_REQUEST['user_stockist']) && $_REQUEST['user_stockist'] == 1){
		if(isset($_REQUEST['user_agent']) && $_REQUEST['user_agent'] !='' && $_REQUEST['user_agent'] !=0){

				$sql = "SELECT COUNT(user_id) FROM ".$wpdb->prefix."usermeta WHERE meta_key='_agent_user' AND meta_value='".$_REQUEST['user_agent']."'";
				$total_user = $wpdb->get_var($sql);
				$views = array("all"=>'All ('.$total_user.')');
		}

		else{

			$sql = "SELECT COUNT(user_id) FROM ".$wpdb->prefix."usermeta WHERE meta_key='_agent_user' AND meta_value>0";
			$total_user = $wpdb->get_var($sql);
			$views = array("all"=>'All ('.$total_user.')');	
		}	
	}

	return $views;
}


/**
 * Low Auth users get some extra fields to assign agent. I'm unsure why this is needed but my guess is that they did not want admins to have agents assigned to them only lower auth users can have agents?
 *
 * @since 1.0.0
 *
*/

function user_agent_fields($user) {
	$is_store_manager =  is_current_store_agent();   
	if(!$is_store_manager) { 
		// Allows agent assignment from user profile
		include_once "includes/user-extra-fields.php";
	}
}



/**
 * Process agent submission from user profile page - select assign user agent is the submission.
 *
 * @since 1.0.0
 *
*/

function save_user_agent_fields( $user_id ) {

	if(is_admin()) {

		$is_store_manager =  is_current_store_agent();   

		if($is_store_manager) { 
			update_user_meta($user_id, '_agent_user', get_current_user_id());
		} else if(isset($_POST['_assign_user'])) {
			update_user_meta($user_id, '_agent_user', $_POST['_assign_user']);
		}
		/* Assign membership trade customer to trade customer user */
		//   New Code
		if (isset($_POST) && !empty($_POST)) {
			$user = get_userdata($user_id);
			//Get current user role
			$current_user = get_userdata(get_current_user_id());

			if ($current_user->roles[0] == 'store_agent') {
				$role_of_user = $_POST['role'];
				$u = new WP_User($user_id);
				// Replace the current role with 'editor' role
				$u->set_role($role_of_user);
			}
			
		}

		//End
		$user = get_userdata( $user_id );
		$user_roles = $user->roles;

		if ( in_array( 'tradecust', $user_roles) ) {
			$memberships = wc_memberships_get_user_active_memberships( $user_id );
			$membership_plan = wc_memberships_get_membership_plans();
			$plan_id = '';

			foreach($membership_plan as $plan){
				if($plan->slug == 'trade-customer'){
					$plan_id=$plan->id;
				}
			}

			if(!empty($plan_id) && empty($memberships)){
				$already_exist=0;
				foreach($memberships as $active_membership){
					if($active_membership->plan_id == $plan_id){
						$already_exist=1;
					}
				}
				if($already_exist == 0){ 
					$args = array(
						'plan_id' => $plan_id,
						'user_id' => $user_id,
					);
					wc_memberships_create_user_membership( $args );
				}	
			}

			$current_add_user = new WP_User( $user_id );
			$current_add_user->add_role( 'tradecust' );
		}
		/* END Assign membership trade customer to trade customer user */
	}
}



/**
 * Forces 'change role to' on users.php to include more user roles
 *
 * @since 1.0.0
 *
*/

function filter_editable_roles( $all_roles ) {

	$is_store_manager =  is_current_store_agent();  

	if($is_store_manager) { 	

		$custom_all_roles['customer'] =  $all_roles['customer'];
		$custom_all_roles['tradecust'] =  $all_roles['tradecust'];
		$all_roles = $custom_all_roles;

	}
	return $all_roles;
}


/**
 * Include agent meta on the filter query args for users.php
 *
 * @since 1.0.0
 *
*/

function users_list_table_query_args($args ) {
	$is_store_manager =  is_current_store_agent();   
	if($is_store_manager) {  
		$args['meta_key'] =  '_agent_user';
		$args['meta_value'] =  get_current_user_id();
	}

	return $args;
}

/**
 * I believe this stops the agent editing old/existing orders
 *
 * @since 1.0.0
 *
*/

function restrict_editing_old_posts( $allcaps, $cap, $args ) {

	$store_agent_role = is_current_store_agent(); 

	if(isset($_REQUEST['post_ID'])) {
		$_REQUEST['post'] = $_REQUEST['post_ID'];
	}

	if($store_agent_role && isset($_GET['user_id']) && $_GET['user_id'] > 0) {
		$agent_id  = (int)get_user_meta($_GET['user_id'], '_agent_user', true);			

		if(get_current_user_id() != $agent_id) {
			wp_die( __( "Sorry, You don't have permission to this order." ) );
		}

	} else
		if($store_agent_role && isset($_REQUEST['post']) && $_REQUEST['post'] > 0) {
			$post_id = $_REQUEST['post'];
			$post_type = get_post_type($post_id);
			if($post_type == 'shop_order') {
				if(isset($_REQUEST['customer_user'])) {
					$order_customer_id  = $_REQUEST['customer_user'];
				}else {
					$order_customer_id  = get_post_meta($post_id, '_customer_user', true);	
				}

				$customerIds = get_agent_customer_ids();

				/* print_r($allcaps); exit;*/
				if(!in_array($order_customer_id, $customerIds)) {
					wp_die( __( "Sorry, You don't have permission to this order." ) );
				}else {
					$allcaps['edit_others_shop_orders'] = 1;
				}
				/* print_r($cap);
			echo $cap[0]; exit;*/
			}
		}

	return $allcaps;
}



/**
 * Filters agent users on the stockists screen - i think - the author of these functions seems to make strange decisions!
 *
 * @since 1.0.0
 *
*/

function filter_users_by_agent_access($query)	{
	global $pagenow;
	$is_store_agent =  is_current_store_agent();

	if (is_admin() && $is_store_agent) {
		$query->set('meta_key', '_agent_user');
		$query->set('meta_value', get_current_user_id());				
	}

	else if(is_admin() && isset($_REQUEST['user_stockist']) && $_REQUEST['user_stockist'] == 1 && !isset($_REQUEST['skip_meta_key'])){
		if(isset($_REQUEST['user_agent']) && $_REQUEST['user_agent'] !='' && $_REQUEST['user_agent'] !=0){
			$query->set( 'meta_query',array(
				array(
					'key'     => '_agent_user',
					'value'=>$_REQUEST['user_agent'],
					'compare' => '=',
				)));	
		}
		else{
	//	$query->set('meta_key', '_agent_user');
		$query->set( 'meta_query',array(
			array(
				'key'     => '_agent_user',
				'value'=>0,
				'compare' => '>',
			)));	
		}
			
	}

//	print_r($query); exit;

}


/**
 * Check a role exists? Maybe used to see if agent role is created?
 *
 * @since 1.0.0
 *
*/

function  role_exists( $role ) {
	if( ! empty( $role ) ) {
		return $GLOBALS['wp_roles']->is_role( $role );
	}
	return false;
}
	
/**
 * Create the agent role, foundational since without this you can't have agent users
 *
 * @since 1.0.0
 *
*/

function create_agent_role() {
	$agent_manager_role = 'store_agent';
	if( role_exists( $agent_manager_role ) ) {
		$capabilities = array('read'=> true, 'edit_published_shop_orders'=> true, 'edit_shop_order'=> true, 'edit_shop_orders'=> true, 'level_0'=>true, 'publish_shop_orders'=> true, 'read_private_shop_orders'=> true, 'view_admin_dashboard'=> true, 'create_users'=> true, 'edit_users'=> true, 'list_users'=> true, $agent_manager_role => true);
		add_role( $agent_manager_role, "Agent", $capabilities );
	}
	return $agent_manager_role;
}


/**
 * A widely used helper function to check whether the user has authority i.e. whether they are an admin/agent
 *
 * @since 1.0.0
 *
*/

function is_current_store_agent() {

	$status = false;
	$currentUserRoles = wp_get_current_user()->roles;
	$user_id = get_current_user_id();
	$store_agent_role = create_agent_role();

	if (in_array($store_agent_role, $currentUserRoles)) {
		$status = true;
	}

	return $status;
}


/**
 * Presumbly it shows only orders on the admin orders screen that relate to this agent
 *
 * @since 1.0.0
 *
*/

function  agent_orders($query) {
	global $pagenow;
	$qv = &$query->query_vars;

	$is_store_agent =  is_current_store_agent();

	if ($is_store_agent) {

		if ( $pagenow == 'edit.php' && 
			isset($qv['post_type']) && $qv['post_type'] == 'shop_order' ) {

			// I use the meta key from step 1 as a second parameter here
			$query->set('meta_key', '_customer_user');
			// The value we want to find is the $user_id defined above
			$customerIds = get_agent_customer_ids();
			$query->set('meta_value', $customerIds);
		}
	}

	return $query;
}

/**
 * Presumbly it shows only orders on the admin orders screen that relate to this agent
 *
 * @since 1.0.0
 *
*/

function get_agent_customer_ids() {
	global $wpdb;
	$sql = "SELECT user_id FROM ".$wpdb->prefix."usermeta WHERE meta_key='_agent_user' AND meta_value=".get_current_user_id();
	$users = $wpdb->get_results($sql);
	$customerIds = array();
	if(count($users) > 0) {
		foreach($users as $user) {

			$customerIds[] = $user->user_id;
		}
	}

	if(empty($customerIds)) {
		$customerIds = array(-1);
	}
	return $customerIds;
}


/**
 * Not sure where this is used? But it counts the amount of orders an agent has had - historically. Returns an INT of amount of orders.
 *
 * @since 1.0.0
 *
*/

function agent_count_posts( $counts, $type, $perm ) {
	global $wpdb;

	$is_store_manager =  is_current_store_agent();   
	if($is_store_manager && $type == 'shop_order') {       

		$customerIds = get_agent_customer_ids();
		$query = "SELECT post_status, COUNT( * ) AS num_posts FROM {$wpdb->posts} WHERE post_type = %s AND ID IN (SELECT post_id FROM ".$wpdb->prefix."postmeta WHERE meta_key='_customer_user' AND meta_value IN(".implode(',', $customerIds)."))";
		if ( 'readable' == $perm && is_user_logged_in() ) {
			$post_type_object = get_post_type_object($type);
			if ( ! current_user_can( $post_type_object->cap->read_private_posts ) ) {
				$query .= $wpdb->prepare( " AND (post_status != 'private' OR ( post_author = %d AND post_status = 'private' ))",
											get_current_user_id()
										);
			}
		}
		$query .= ' GROUP BY post_status';

		$results = (array) $wpdb->get_results( $wpdb->prepare( $query, $type ), ARRAY_A );
		$counts = array();
		/*print_r($results);*/
		foreach ( $results as $row ) {
			$counts[ $row['post_status'] ] = $row['num_posts'];
		}

		$counts = (object) $counts;
	}
	return $counts;
}

/**
 * Show errors if an agent hasn't entered the billing details correctly on user profile
 *
 * @since 1.0.0
 *
*/

function user_profile_update_errors(&$errors, $update = null, &$user  = null ) {
	if(isset($user->ID)){
		
		if($user->role == 'store_agent'){
			if ( empty( $_POST['billing_company'] ) ) {
				$errors->add( 'billing_company_error', __( '<strong>ERROR</strong>: Please enter Billing Company.' ) );
			}
			if ( empty( $_POST['billing_address_1'] ) ) {
				$errors->add( 'billing_address_1_error', __( '<strong>ERROR</strong>: Please enter Billing Address1.' ) );
			}
			if ( empty( $_POST['billing_address_2'] ) ) {
				$errors->add( 'billing_address_2_error', __( '<strong>ERROR</strong>: Please enter Billing Address2.' ) );
			}
			if ( empty( $_POST['billing_city'] ) ) {
				$errors->add( 'billing_city_error', __( '<strong>ERROR</strong>: Please enter Billing City.' ) );
			}
			if ( empty( $_POST['billing_postcode'] ) ) {
				$errors->add( 'billing_postcode_error', __( '<strong>ERROR</strong>: Please enter Billing Postcode.' ) );
			}
			if ( empty( $_POST['billing_country'] ) ) {
				$errors->add( 'billing_country_error', __( '<strong>ERROR</strong>: Please Select Billing Country.' ) );
			}
			if ( empty( $_POST['billing_state'] ) ) {
				$errors->add( 'billing_state_error', __( '<strong>ERROR</strong>: Please enter Billing State.' ) );
			}
		}
	}
}
	


/**
 * Add the users stockists page to the menu system. I'm not 100% on why users stockists page is needed in addition to users.php?
 *
 * @since 1.0.0
 *
*/
	
function waom_add_user_sub_menu() {

    add_submenu_page(
        'users.php',
        'Users Stockist',
        'Users Stockist',
        'manage_options',
        'users.php?user_stockist=1'
         
    );
}


/**
 * I overhauled this from the original since Nand's code was breaking due to a NUA change. It allows you to filter a set of users base don the agent they belong to. It's not great since it allows a filter then you need to reload the page
 *
 * @since 1.0.1
 *
*/

function agent_filter(){

	global $wpdb;

	$unset_status = "";
	$agent = (isset($_REQUEST["user_agent"])? $_REQUEST["user_agent"] : "");
	$get_agent = (isset($_GET["user_agent"])? $_GET["user_agent"] : "");

	if( isset($_REQUEST['user_stockist'])){
    	?> 
			<input type="hidden" name="user_stockist" value="1"> <?php
    }

	?> <input type="hidden" name="user_agent" class="user_agent_hidden" value="<?php echo $get_agent; ?>"> <?php

	if( isset($_REQUEST['user_stockist'])){
		unset( $_REQUEST['user_stockist']);
		$unset_status=1;
	}

	$users = get_users( array( 'role__in' => array( 'store_agent' )) );
?>

<select id="pw-status-query-agent" name="agent" onchange="update_agent(this.value)">

	<option value="0"> Select Agent </option> <?php

foreach($users as $user){

	if($agent == $user->ID)
		{ $selected="selected"; }
	else { $selected=""; }

	?> <option <?php echo $selected ?> value="<?php echo $user->ID ?>"><?php echo $user->display_name ?></option> <?php
}

?> </select> 


<style>
			#pw-status-query-agent {
				margin: 2px 0 0 5px;
			}
			#new_user_approve_filter-top{
				float: right !important;
			}
</style>
<?php
		if($unset_status == 1){
			$_REQUEST['user_stockist']=1;
		}
?>
		<script>
		function update_agent(agent_val){
			jQuery(".user_agent_hidden").prop("value", agent_val);
		}</script>
		



	<?php
}


/**
 * I overhauled this from the original since Nand's code was breaking due to a NUA change. It allows you to filter a set of users base don the agent they belong to. It's not great since it allows a filter then you need to reload the page
 *
 * @since 1.0.1
 *
*/


function filter_users_by_agent( $query ) {
	// This condition allows us to make sure that we won't modify any query that came from the frontend
	if ( ! is_admin() ) {
      return;
    }
	
	global $pagenow;
	
	// This condition allows us to make sure that we're modifying a query that fires on the wp-admin/users.php page
	if ( 'users.php' === $pagenow ) {
		
		// Let's check if our filter has been used
		if ( isset( $_GET['user_agent'] ) && $_GET['user_agent'] !== '0' ) {
			$meta_query = array(
				array(
					'key' => '_agent_user',
					'value' => $_GET['user_agent'],
					'compare' => '='
				)
			);
			
			$query->set( 'meta_query', $meta_query );
		}
	}

	return;
}




function active_stockist_menu(){
	if(isset($_REQUEST['user_stockist']) && $_REQUEST['user_stockist'] == 1){
		echo '<script>
		jQuery(document).ready(function () {
			jQuery(".wp-submenu").find("li").removeClass("current");
			jQuery(".wp-submenu").find(\'a[href="users.php?user_stockist=1"]\').parents("li").addClass("current");
		}); </script>';
	}
}


/**
 * Adds an agent column to see who belongs to which agent on the users screen
 *
 * @since 1.0.1
 *
*/


function register_user_agent_column( $columns ) {
	$columns['user_agent'] = 'Agent';
	return $columns;
}


function render_user_user_agent( $output, $column_name, $user_id ) {

	$user_name = "";

	if ( 'user_agent' === $column_name ) {
		// Don't forget to escape your output
		$user_details = get_userdata( get_user_meta( $user_id, '_agent_user', true ) );
		if($user_details) : $user_name = $user_details->display_name; endif;
	}

	return $user_name;
}


?>