<?php

/**
 * Snippet Name: On approval auto assign membership plan
 * Version: 1.0.0
 * Description: When a user is approved, they get auto assigned a membership plan (default setting in WooCommerce)
 *
 * @link              https://auracreativemedia.co.uk
 * @since             1.1.1
 * @package           Aura_Supercommerce
 *
**/



// Dependency = WP Memberships & NUA
if ( (is_plugin_active( 'new-user-approve/new-user-approve.php' ) || is_plugin_active( 'new-user-approve-premium/new-user-approve.php' )) && is_plugin_active( 'woocommerce-memberships/woocommerce-memberships.php' ) ) {


	/**
	 * Create the section beneath the products tab
	 **/
	add_filter( 'woocommerce_get_sections_memberships', 'memberships_add_section_custom' );

	function memberships_add_section_custom( $sections ) {

		$sections['auto-approve'] = __( 'Auto Approval', 'custom_memb' );
		return $sections;
	}

	/**
	 * Add settings to the specific section we created before
	 */
	add_filter( 'woocommerce_get_settings_memberships', 'custom_member_settings', 10, 2 );

	function custom_member_settings( $settings, $current_section ) {
		/**
		 * Check the current section is what we want
		 **/

		if ( $current_section == 'auto-approve') {
			$settings_custom_memb = array();
			// Add Title to the Settings
			
			$membership_obj = new WC_Memberships_Membership_Plans();
			$membership_all_plans = $membership_obj->get_available_membership_plans();
			$membership_options = array();
			
			foreach($membership_all_plans as $membership) {
				$membership_options[$membership->id] = $membership->name;
			} 
			
			$settings_custom_memb[] = array( 'name' => __( 'Auto Approval Settings', 'custom_memb' ), 'type' => 'title', 'desc' => __( 'Configure auto approval defaults', 'custom_memb' ), 'id' => 'custom_memb' );

			// Add second text field option
			$settings_custom_memb[] = array(
				'name'     => __( 'Default membership plan for Auto Approval', 'custom_memb' ),
				'desc_tip' => __( 'On approval, this will auto add the selected membership plan to whichever user was approved', 'custom_memb' ),
				'id'       => 'aa_def_membplan',
				'type'     => 'select',
				'options'  =>  $membership_options,
				'desc'     => __( 'On approval, this will auto add the selected membership plan to whichever user was approved. (Be sure to add at least one membership plan otherwise this field will be empty)', 'custom_memb' ),
			);
			
			
			$settings_custom_memb[] = array( 'type' => 'sectionend', 'id' => 'custom_memb' );
			return $settings_custom_memb;
		
		/**
		 * If not, return the standard settings
		 **/
		} else {
			return $settings;
		}
	}



	// When a user is approved...
	add_action( 'new_user_approve_user_approved', 'user_approved' );

	function user_approved($user_data) {

		$user_id = $user_data->data->ID;

		if ($user_data) :
			//$user = get_userdata( $user_id );
			$user_roles = $user_data->roles;
		
			// make sure our user is a trade customer before auto assigning plan
			if ( in_array( 'tradecust', $user_roles) ) {
				// get all memberships for this user, they should'nt have any but just in case ? ...
				$memberships = wc_memberships_get_user_active_memberships( $user_id );
				// get all plans from the current store (what if there aren't any?)
				$membership_plan = wc_memberships_get_membership_plans();
				// empty the plan variable?
				$plan_id = ''; 
				
				// loop all store plans to look for our chosen one then add it to the plan variable
				foreach($membership_plan as $plan){
					// need to deal with this magic number by creating an admin setting for default plan(s)

					$aa_opt_plan = get_option('aa_def_membplan');

					if($aa_opt_plan && $plan->id == $aa_opt_plan){
						$plan_id = $plan->id;
					} 
				}
				
				// so if our user has no memberships and magic plan was found...
				if(!empty($plan_id) && empty($memberships)){
					// ?
					$already_exist = 0;
					// there should be none i think? but they are checking again
					foreach($memberships as $active_membership){
						if($active_membership->plan_id == $plan_id){
							$already_exist=1;
							echo 'Duplicate memberships plan found. Please check with an admin.';
						} else {
							echo 'Please set a membership default plan in WooCommerce settings first';
						}
					}
					// ok now we are loading a var with the user details...
					if($already_exist == 0){ 
						$args = array(
							'plan_id' => $plan_id,
							'user_id' => $user_id,
						);
						// then creating a user membership fro that user with our plan id
						wc_memberships_create_user_membership( $args );
					}	
				}
				
			}
		
		endif;
		
	}
}