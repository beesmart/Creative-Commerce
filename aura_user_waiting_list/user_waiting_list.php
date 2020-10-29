<?php
/*
  Plugin Name: Aura  users waiting list
  Description: This would enable you to create 'waiting customers' these customers will be prevented from ordering until you approve them, you will be notified via email should the nearest stockist change colour eg from Orange to Red so the nearest stockist ordered over 12 months ago. Will show waiting list of all waiting customers and their nearest 'live' customer and if they order 6 months / 12 months or longer
  Version: 1.0
  Author: Paul Taylor
  License: GPL2
 */

/** REgister menu */
 
session_start();

function process_offline_order( $post_id, $post, $update ) {
    $order_date=$_POST['order_date'];
    $customer_id=$_POST['customer_user'];
	update_user_meta($customer_id, 'user_last_order_date', $order_date);
}
add_action( 'save_post_shop_order', 'process_offline_order',100,3 );


/*add_filter( 'authenticate', 'myplugin_auth_signon', 30, 3 );
function myplugin_auth_signon( $user, $username, $password ) {
   $user_status = $user->data->user_status;
  if($user_status == 3 ) {
    $error = new WP_Error();
    $error->add( 403, 'Your account is currently inactive. please contact an administrator for access.' );
    return $error;
  }
  return $user;
} */

if (isset($_GET['update_usermeta']) && !empty($_GET['update_usermeta'])) {
    add_action('init', 'updateusermeta');
	function updateusermeta() {
    $user_id=538;
    update_user_meta($user_id,'user_last_order_date','2019-01-15');
    
    }
}

if (isset($_GET['get_usermeta']) && !empty($_GET['get_usermeta'])) {
    add_action('init', 'getusermeta');
	function getusermeta() {
    $user_id=535;
    $check_address=aslc_check_address();
    $latitude= get_user_meta($user_id,$check_address . '_latitude',true);
    $longitude= get_user_meta($user_id,$check_address . '_longitude',true);
    echo $latitude;
    exit;
    }
}

if (isset($_GET['cust']) && !empty($_GET['cust'])) {
    add_action('init', 'get_users_list');

    function get_users_list() {


        global $wpdb;
        $table_name = $wpdb->users;
        $current_date = date("Y-m-d");
        $user_array = array();
        $admin_email = get_option('admin_email');
        $meaasge = "<table style='width:70%'><tr style='text-align:left'><th>User Id</th><th style='text-align:left'>User Name </th></tr>";
        
        
        /********* start Update all users last order date ************/
		 $user_list2 = $wpdb->get_results("SELECT user_login,ID,user_registered FROM `$table_name` where 1");
         
        foreach ($user_list2 as $val) 
        {
            $user_id = $val->ID;
            $get_last_order = wc_get_customer_last_order($user_id);
            if(!empty($get_last_order))
            {
                $order_key = $get_last_order->order_key;
                $customer_id = $get_last_order->customer_id;
                $order_id = wc_get_order_id_by_order_key($order_key);
                $order = wc_get_order( $order_id );
                $order_date = $order->order_date;
                $last_order = get_user_meta($value->ID, 'user_last_order_date', true);
                if(empty($last_order))
                {   
                    update_user_meta($val->ID, 'user_last_order_date', $order_date);
                }
				/************* Start Update Nearest Stockist***************/
				$order = new WC_Order( $order_id );
				$order_address = $order->get_billing_address_1();
				$order_address2 = $order->get_billing_address_2();
				$order_city = $order->get_billing_city();
				$order_postcode = $order->get_billing_postcode();
				$order_country = $order->get_billing_country();
				$order_state = $order->get_billing_state();
				$args = array(
					'author' => $val->ID,
					'post_type' => 'wpsl_stores',
				);
				$current_user_posts = get_posts($args);
				$count = 0; 
				if (!empty($current_user_posts)) {
					foreach ($current_user_posts as $store) {
						$store_id = $store->ID;
						$address = get_post_meta($store_id, 'wpsl_address', true);
						$address2 = get_post_meta($store_id, 'wpsl_address2', true);
						$city = get_post_meta($store_id, 'wpsl_city', true);
						$state = get_post_meta($store_id, 'wpsl_state', true);
						$zip = get_post_meta($store_id, 'wpsl_zip', true);
						$country = get_post_meta($store_id, 'wpsl_country', true);
						if ($order_address == $address && $order_address2 == $address2 && $order_city == $city && $order_state == $state && $order_postcode == $zip && $order_country == $country) {
							$count++;
						}
					}
				}
				
				if ($count <= 0) {       
					$store_title = $order->get_billing_company();
					$post = array(
						'post_title' => $store_title,
                        'post_author' => $val->ID,
						'post_status' => 'publish',
						'post_name' => $store_title,
						'post_type' => 'wpsl_stores',
					);
					$post_id = wp_insert_post($post);

					update_post_meta($post_id, 'wpsl_address', $order_address);
					update_post_meta($post_id, 'wpsl_address2', $order_address2);
					update_post_meta($post_id, 'wpsl_city', $order_city);
					update_post_meta($post_id, 'wpsl_state', $order_state);
					update_post_meta($post_id, 'wpsl_zip', $order_postcode);
					update_post_meta($post_id, 'wpsl_country', $order_country);

					$address = $order_address . ' ' . $order_address2 . ' ' . $order_city . ' ' . $order_state . ' ' . $order_postcode . ' ' . $order_country;
					$response = wpsl_call_geocode_api($address);
					$coordinates = json_decode($response['body'], true);
					$latitude = $coordinates['results'][0]['geometry']['location']['lat'];
					$longitude = $coordinates['results'][0]['geometry']['location']['lng'];
					update_post_meta($post_id, 'wpsl_lat', $latitude);
					update_post_meta($post_id, 'wpsl_lng', $longitude);
					update_post_meta($post_id, 'wpsl_users', $val->ID);
					$address_used = aslc_check_address();
					update_user_meta($val->ID, $address_used . '_latitude', $latitude);
					update_user_meta($val->ID, $address_used . '_longitude', $longitude);
				  
				}
				update_user_meta($val->ID, 'last_order_id', $order_id);
    
				
				/************* End Update Nearest Stockist***************/
				
				
            }
        }
		
		/*********** End Update all users last order date*************/
        
        
        
        
        
        
        
        $user_list = $wpdb->get_results("SELECT user_login,ID,user_registered FROM `$table_name` where user_status!='3'");
		
        $dat = 0;
        foreach ($user_list as $val) {
			$user_meta=get_userdata($val->ID);
			$user_roles=$user_meta->roles;
			if(!in_array('store_agent',$user_roles) && !in_array('shop_manager',$user_roles) && !in_array('administrator',$user_roles)){
            $last_order = get_user_meta($val->ID, 'user_last_order_date', true);
            if (!empty($last_order) && trim($last_order) != '') {//If user have orders    
//                echo $current_date . '---' . $last_order;
                $diff = abs(strtotime($current_date) - strtotime($last_order));
                $months = floor(($diff) / (30 * 60 * 60 * 24)); //Get diffrence in MONTHS
                if ($months > 12) {//If he was not order since one year.
                    $user_array[] = $val;
                    //Update user status  
                   $wpdb->query("UPDATE $table_name SET user_status='3' WHERE ID='$val->ID'");
                    //Prepare mail message
                    $name = ucfirst(str_replace('_', ' ', $val->user_login));
                    $name = ucfirst(str_replace('-', ' ', $name));
                    $meaasge .= "<tr><td>" . $val->ID . "</td><td>" . $name . " </td></tr>";
                    $dat++;
                }
            } else {//If user only account created since 1 year and not ordered yet.
                $diff = abs(strtotime($current_date) - strtotime($val->user_registered));

                $months = floor(($diff) / (30 * 60 * 60 * 24)); //Get diffrence in MONTHS

                if ($months > 12) {
                    $user_array[] = $val;
                    //Update user status
                    $wpdb->query("UPDATE $table_name SET user_status='3' WHERE ID='$val->ID'");
                    //Prepare mail message
                    $name = ucfirst(str_replace('_', ' ', $val->user_login));
                    $name = ucfirst(str_replace('-', ' ', $name));
                    $meaasge .= "<tr><td>" . $val->ID . "</td><td>" . $name . " </td></tr>";
                    $dat++;
                }
            }
        }
		}
        $meaasge .= "</table>";
        $message = get_templete_mail_expiry($meaasge);
        $subject = "User waiting mail";
        $headers = array('Content-Type: text/html; charset=UTF-8');
        $admin_email = get_option( 'admin_email' );
        wp_mail($admin_email, $subject, $message, $headers); 
		
    }

}

function get_templete_mail_expiry($message_get) {

    return $message = "<div style='width:850px;height:auto;border:5px solid #000'>"
            . "<table>"
            . "<tr>"
            . "<td> <img src='" . site_url() . "/wp-content/uploads/2016/12/louise-tiler-logo.jpg' with='300' height='116' style='float:left;'>"
            . "</td>"
            . "</tr>"
            . "<tr>"
            . "<td style='padding-left: 5px;border: none;line-height: 35px;'><p style='margin-top:15px;'> Hello Admin,</p>"
            . "</td>"
            . "</tr>"
            . "<tr>"
            . "<td style='padding-left: 5px;border: none;line-height: 35px;'>Following users are in waiting list:"
            . "$message_get."
            . "</td>"
            . "</tr>"
            . "<tr>"
            . "<td style='border: none;line-height: 35px;padding-left: 5px;'> <b>Thank you</b>"
            . "</td>"
            . "</tr>"
            . "<tr>"
            . "<th> <p  style='padding:20px;background:#EFEFEF;    width: 806px;    text-align: center;'> Copyright ©" . date('Y') . " Louise Tiler. All Rights Reserved</p>"
            . "</th>"
            . "</tr>"
            . "</table>"
            . "</div>";
}

/** REgister menu */
add_action('admin_menu', 'waiting_user_admin_menu');

/** Function */
function waiting_user_admin_menu() {
    add_users_page('Waiting  Users', 'Waiting Users', 'manage_options', 'user_waiting', 'user_waiting_function');
}

/** function for Waiting List */
function user_waiting_function() {
	include_once 'includes/user_list.php';
}

function get_user_list($args=array()){
	
	global $wpdb;
    $table_name = $wpdb->users;
    $page_url = site_url() . "/wp-admin/admin.php?page=user_waiting";
    //For all record total
	if(isset($args['search']) && !empty($args['search'])) {
		$sql_total= " and (ID LIKE '%".$args['search']."%' OR user_login LIKE '%".$args['search']."%' OR user_email LIKE '%".$args['search']."%' OR display_name LIKE '%".$args['search']."%' OR user_registered LIKE '%".$args['search']."%')";
	} 
	 $total_users = $wpdb->get_var("SELECT COUNT(id) FROM " . $table_name . " WHERE user_status='3' ". $sql_total);
    $sql_get_list = "SELECT * FROM " . $table_name . " WHERE user_status='3' ";
	 
	 if(isset($args['search']) && !empty($args['search'])) {
		$sql_get_list .= " and (ID LIKE '%".$args['search']."%' OR user_login LIKE '%".$args['search']."%' OR user_email LIKE '%".$args['search']."%' OR display_name LIKE '%".$args['search']."%' OR user_registered LIKE '%".$args['search']."%')";
	} 
    if(isset($args['limit']) && isset($args['page'])) {
         $args['offset'] = (($args['page']-1) * $args['limit']);
         $results['page'] =  $args['page'];
     }
     if(isset($args['limit']) && isset($args['offset'])) {
         $sql_get_list .= ' LIMIT '.$args['offset'].', '.$args['limit'];
         $results['limit'] =  $args['limit'];
         $results['offset'] =  $args['offset'];
     }
	// echo $sql_get_list;  exit;
		$waiting_user = $wpdb->get_results($sql_get_list, ARRAY_A);
		$results['total'] = $total_users;
		$results['waiting_user'] = $waiting_user;
    return $results;
}

function get_nearest_stockist($latitude,$longitude){
	global $wpdb;
		if(!empty($latitude) && !empty($longitude)){
			$sql="SELECT *, ( 3959 * acos ( cos ( radians(".$latitude.") ) * cos( radians( dest.lat ) ) * cos( radians( dest.lng ) - radians(".$longitude.") ) + sin ( radians(".$latitude.") ) * sin( radians( dest.lat ) ) ) ) as distance FROM wp_posts as p INNER JOIN ( SELECT ID as store_id , (select IFNULL(meta_value,0) from wp_postmeta where wp_postmeta.post_id=wp_posts.ID and meta_key='wpsl_lat') as lat, (select IFNULL(meta_value,0) from wp_postmeta where wp_postmeta.post_id=wp_posts.ID and meta_key='wpsl_lng') as lng FROM wp_posts where post_type='wpsl_stores' ) as dest ON dest.store_id = p.ID where p.post_status='publish' having distance>0 ORDER BY distance asc limit 1";
			
			$sql_data_user = $wpdb->get_results($sql,ARRAY_A);
			$user_id=$sql_data_user[0]['post_author'];
            $distance_miles=$sql_data_user[0]['distance'];
			$distance_meters=(int)($distance_miles * 1609.344);
			$first_name=get_user_meta($user_id,'first_name',true);
			$last_name=get_user_meta($user_id,'last_name',true);
			$name=$first_name.' '.$last_name;
			//$name=$first_name.' '.$last_name;
			if(empty($name)){
				$user=get_userdata( $user_id );
				$name=$user->display_name;
				//$name=$user->display_name;
			}
		}
		if(!empty($name)){
		$name_return='<a href="'.admin_url().'user-edit.php?user_id='.$user_id.'" target="_blank">'.$name.'</a>'.' - '.$distance_meters.' Meters';
		}
		return $name_return;
}

function user_activate($user_id,$status){
	
	global $wpdb;
    $table_name = $wpdb->users;
	if(!empty($user_id) && $status == 'active'){
	$sql_user = "SELECT * FROM " . $table_name . " WHERE ID=".$user_id. " and  user_status='3' ";
	$user = $wpdb->get_row($sql_user, ARRAY_A);
	if(!empty($user)){
	 $wpdb->query("UPDATE ".$table_name." SET user_status='0' WHERE ID=".$user_id);
	 $_SESSION['message_active']="User Successfully Activated";
	 ?>
	
	<script type="text/javascript">
    jQuery(document).ready(function(){
		location.reload();
    });</script>
	<?php
	}
	}
	
	              
}


/* function paginate($item_per_page, $current_page, $total_records, $total_pages, $page_url) {
    $pagination = '';

    if ($total_pages > 0 && $total_pages != 1 && $current_page <= $total_pages) { //verify total pages and current page number
        $pagination .= '<ul class="pagination">';

        $right_links = $current_page + 3;
        $previous = $current_page - 3; //previous link 
        $next = $current_page + 1; //next link
        $first_link = true; //boolean var to decide our first link

        if ($current_page > 1) {
            $previous_link = ($previous == 0) ? 1 : $previous;
            $pagination .= '<li class="first"><a href="' . $page_url . '&page_paging=1" title="First">«</a></li>'; //first link
//            $pagination .= '<li><a href="' . $page_url . '&page_paging=' . $previous_link . '" title="Previous"><</a></li>'; //previous link
            for ($i = ($current_page - 2); $i < $current_page; $i++) { //Create left-hand side links
                if ($i > 0) {
                    $pagination .= '<li><a href="' . $page_url . '&page_paging=' . $i . '">' . $i . '</a></li>';
                }
            }
            $first_link = false; //set first link to false
        }

        if ($first_link) { //if current active page is first link
            $pagination .= '<li class="first active">' . $current_page . '</li>';
        } elseif ($current_page == $total_pages) { //if it's the last active link
            $pagination .= '<li class="last active">' . $current_page . '</li>';
        } else { //regular current link
            $pagination .= '<li class="active">' . $current_page . '</li>';
        }

        for ($i = $current_page + 1; $i < $right_links; $i++) { //create right-hand side links
            if ($i <= $total_pages) {
                $pagination .= '<li><a href="' . $page_url . '&page_paging=' . $i . '">' . $i . '</a></li>';
            }
        }
        if ($current_page < $total_pages) {
            $next_link = ($i > $total_pages) ? $total_pages : $i;
//            $pagination .= '<li><a href="' . $page_url . '&page_paging=' . $next_link . '" >></a></li>'; //next link
            $pagination .= '<li class="last"><a href="' . $page_url . '&page_paging=' . $total_pages . '" title="Last">»</a></li>'; //last link
        }

        $pagination .= '</ul>';
    }
    return $pagination; //return pagination links
}

 */