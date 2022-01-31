<?php


/**
 * 
 * Snippet Name: New User Approve - Registration Fix
 * Version: 1.0.1
 * Description: Fields and Fixes for NUA plugin
 * Dependency: new-user-approve 
 *
 * @link              https://auracreativemedia.co.uk
 * @since             1.0.1
 * @package           Aura_Supercommerce
 *
**/


if ( is_plugin_active( 'new-user-approve/new-user-approve.php' ) || is_plugin_active( 'new-user-approve-premium/new-user-approve.php' ) ) {
	add_filter('nua_email_tags', 'mytheme_extras_nua_add_email_tags');
	function mytheme_extras_nua_add_email_tags($email_tags) {
	    
	    // $email_tags[] = array(
	    //     'tag' => 'first_last_name',
	    //     'description' => __('The users first and last name.', 'mytheme'),
	    //     'function' => 'mytheme_extras_nua_email_tag_user_first_last_name',
	    //     'context' => array('email'),
	    // );
	    // $email_tags[] = array(
	    //     'tag' => 'company_name',
	    //     'description' => __('The users Company name.', 'mytheme'),
	    //     'function' => 'mytheme_extras_nua_email_tag_user_company_name',
	    //     'context' => array('email'),
	    // );
	    // $email_tags[] = array(
	    //     'tag' => 'user_type',
	    //     'description' => __('The users type.', 'mytheme'),
	    //     'function' => 'mytheme_extras_nua_email_tag_user_type',
	    //     'context' => array('email'),
	    // );
	    //var_dump($email_tags);
	    return $email_tags;
	}

	// function mytheme_extras_nua_email_tag_user_company_name($attributes) {   
	//     return '' . isset($_POST['billing_company'])?$_POST['billing_company']:'';
	// }
	// function mytheme_extras_nua_email_tag_user_type($attributes) {   
	//     return '' . isset($_POST['pm_customer_type'])?ucfirst($_POST['pm_customer_type']).' Customer':'';
	// }
	// function mytheme_extras_nua_email_tag_user_first_last_name($attributes) {
	   
	//   $full_name=isset($_POST['billing_first_name'])?$_POST['billing_first_name']:'' . ' ';
	//   $full_name .=isset($_POST['billing_last_name'])? ' '.$_POST['billing_last_name']:'';  
	//     return  $full_name;
	// }

	add_filter('new_user_approve_notification_message_default', 'mytheme_extras_nua_approval_notification_message', 10, 2);

	function mytheme_extras_nua_approval_notification_message($message) {

	    $approve_url = get_site_url() . '/wp-admin/users.php?page=new-user-approve-admin';
		$message='';
	    $message .= '{username}';
	    $message .="\n\n";
	    // $message .= "Company name: {company_name}\n\n";
	    // $message .= "User type: {user_type}\n\n";
	    // $message .= "{username} \n\n";
	    $message .= "User Email: ({user_email}) has requested a username at {sitename} \n\n";
	    
	    $message .= "{site_url}\n\n";
	    $message .= __('To approve or deny this user access to {sitename} go to', 'new-user-approve') . "\n\n";
	    $message .= $approve_url . "\n\n";
	    return $message;
	}
    
} else {

	function nua_not_installed() {
    ?>
	    <div class="notice notice-success is-dismissible">
	        <p><?php _e( 'Required Plugin Dependency Missing: New User Approve', 'Aura_Supercommerce' ); ?></p>
	    </div>
	    <?php
	}
	add_action( 'admin_notices', 'nua_not_installed' );
}
