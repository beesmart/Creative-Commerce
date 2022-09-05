<?php

/**
 * Snippet Name: Additional Account Registration Fields
 * Version: 1.1.0
 * Description: Adds a number of additional account registration fields. The What type of Business & Message
 *
 * @link              https://auracreativemedia.co.uk
 * @since             1.1.1
 * @package           Aura_Supercommerce
 *
**/


/* Users get auto created as a 'customer' role */


	add_action( 'woocommerce_register_form_start', 'aura_add_name_woo_account_registration' );
	 
	function aura_add_name_woo_account_registration() {
	    ?>
	 
	    <p class="form-row form-row-first">
	    	<input type="text" class="input-text" name="billing_first_name" id="reg_billing_first_name" value="<?php if ( ! empty( $_POST['billing_first_name'] ) ) esc_attr_e( $_POST['billing_first_name'] ); ?>" placeholder="First name*" />
	    </p>
	 
	    <p class="form-row form-row-last">
	    	<input type="text" class="input-text" name="billing_last_name" id="reg_billing_last_name" value="<?php if ( ! empty( $_POST['billing_last_name'] ) ) esc_attr_e( $_POST['billing_last_name'] ); ?>" placeholder="Last name*" />
	    </p>
		  
	    <p class="form-row form-row">
	    	<input type="text" class="input-text" name="billing_company" id="reg_billing_company" value="<?php if ( ! empty( $_POST['billing_company'] ) ) esc_attr_e( $_POST['billing_company'] ); ?>" placeholder="Company name*" />
	    </p>
		<p class="form-row form-row">
	    	<input type="text" class="input-text" name="billing_phone" id="reg_billing_phone" value="<?php if ( ! empty( $_POST['billing_phone'] ) ) esc_attr_e( $_POST['billing_phone'] ); ?>" placeholder="Phone*" />
	    </p>
	 
	    <p class="form-row form-row">
	    	<input type="text" class="input-text" name="billing_address_1" id="reg_billing_address_1" value="<?php if ( ! empty( $_POST['billing_address_1'] ) ) esc_attr_e( $_POST['billing_address_1'] ); ?>" placeholder="Address Line 1*" />
	    </p>
	 
	    <p class="form-row form-row">
	    	<input type="text" class="input-text" name="billing_address_2" id="reg_billing_address_2" value="<?php if ( ! empty( $_POST['billing_address_2'] ) ) esc_attr_e( $_POST['billing_address_2'] ); ?>" placeholder="Address Line 2" />
	    </p>
	 
		<p class="form-row form-row">
	    	<input type="text" class="input-text" name="billing_city" id="reg_billing_city" value="<?php if ( ! empty( $_POST['billing_city'] ) ) esc_attr_e( $_POST['billing_city'] ); ?>" placeholder="City*" />
	    </p>
	 
	    <p class="form-row form-row">
	    	<input type="text" class="input-text" name="billing_postcode" id="reg_billing_postcode" value="<?php if ( ! empty( $_POST['billing_postcode'] ) ) esc_attr_e( $_POST['billing_postcode'] ); ?>" placeholder="Postcode*" />
	    </p>

		<p style="margin: 25px 0 15px;">
			<input type="checkbox" class="input-checkbox" name="b2b_role" id="b2b_role" value="1" checked />
			<label for="b2b_role" class="checkbox-label">Are you a Trade Customer?</label>

	    </p>

	    <input type="hidden" id="account-form-check" name="account-form-check" value="account-form-check">
		  
	    <div class="clear"></div>
	 
	    <?php
	}
	 
	///////////////////////////////
	// 2. VALIDATE FIELDS
	 
	add_filter( 'woocommerce_registration_errors', 'aura_validate_name_fields', 10, 3 );
	 
	function aura_validate_name_fields( $errors, $username, $email ) {
	    if ( isset( $_POST['billing_first_name'] ) && empty( $_POST['billing_first_name'] ) ) {
	        $errors->add( 'billing_first_name_error', __( '<strong>Error</strong>: First name is required!', 'woocommerce' ) );
	    }
	    if ( isset( $_POST['billing_last_name'] ) && empty( $_POST['billing_last_name'] ) ) {
	        $errors->add( 'billing_last_name_error', __( '<strong>Error</strong>: Last name is required!.', 'woocommerce' ) );
	    }
	  if ( isset( $_POST['billing_company'] ) && empty( $_POST['billing_company'] ) && !is_checkout() ) {
	        $errors->add( 'billing_company_error', __( '<strong>Error</strong>: Company name is required!', 'woocommerce' ) );
	    }
	    if ( isset( $_POST['billing_phone'] ) && empty( $_POST['billing_phone'] ) ) {
	        $errors->add( 'billing_phone_error', __( '<strong>Error</strong>: Phone is required!', 'woocommerce' ) );
	    }
	    if ( isset( $_POST['billing_address_1'] ) && empty( $_POST['billing_address_1'] ) ) {
	        $errors->add( 'billing_address_1_error', __( '<strong>Error</strong>: Address Line 1 is required!.', 'woocommerce' ) );
	    }
	    if ( isset( $_POST['billing_address_2'] ) && empty( $_POST['billing_address_2'] ) ) {
	       $errors->add( 'billing_address_2_error', __( '<strong>Error</strong>: address_2 is required!.', 'woocommerce' ) );
	   }
	    if ( isset( $_POST['billing_city'] ) && empty( $_POST['billing_city'] ) ) {
	        $errors->add( 'billing_city_error', __( '<strong>Error</strong>: city is required!', 'woocommerce' ) );
	    }
	    if ( isset( $_POST['billing_postcode'] ) && empty( $_POST['billing_postcode'] ) ) {
	        $errors->add( 'billing_postcode_error', __( '<strong>Error</strong>: Address Line 1 is required!.', 'woocommerce' ) );
	    }

	    return $errors;
	}
	 
	///////////////////////////////
	// 3. SAVE FIELDS
	 
	add_action( 'woocommerce_created_customer', 'aura_save_name_fields' );
	 
	function aura_save_name_fields( $customer_id ) {
	    if ( isset( $_POST['billing_first_name'] ) ) {
	        update_user_meta( $customer_id, 'billing_first_name', sanitize_text_field( $_POST['billing_first_name'] ) );
	    }
	    if ( isset( $_POST['billing_last_name'] ) ) {
	        update_user_meta( $customer_id, 'billing_last_name', sanitize_text_field( $_POST['billing_last_name'] ) );
	    }
		if ( isset( $_POST['billing_company'] ) ) {
	        update_user_meta( $customer_id, 'billing_company', sanitize_text_field( $_POST['billing_company'] ) );
	    }
		if ( isset( $_POST['billing_phone'] ) ) {
	        update_user_meta( $customer_id, 'billing_phone', sanitize_text_field( $_POST['billing_phone'] ) );
	    }
	    if ( isset( $_POST['billing_address_1'] ) ) {
	        update_user_meta( $customer_id, 'billing_address_1', sanitize_text_field( $_POST['billing_address_1'] ) );
	    }
	    if ( isset( $_POST['billing_address_2'] ) ) {
	        update_user_meta( $customer_id, 'billing_address_2', sanitize_text_field( $_POST['billing_address_2'] ) );
	    }
		if ( isset( $_POST['billing_city'] ) ) {
	        update_user_meta( $customer_id, 'billing_city', sanitize_text_field( $_POST['billing_city'] ) );
	    }
	    if ( isset( $_POST['billing_postcode'] ) ) {
	        update_user_meta( $customer_id, 'billing_postcode', sanitize_text_field( $_POST['billing_postcode'] ) );
	    }

	}



	/**
	 * This function auto assigns the user role based on their checkbox selection. We use a different hook because the regitsration process needs to have actually finished to reassign their roles 
	 *
	 * @param    $user_id - contains the user id from the newly created user
	 */


	/* For B2B Only sites this will need some revision, probably will need to hide the checkbox but make it always 'on' so the auto switch to trade is enforced */
	/* We ,may also need to disable for sites which do not use auto approval plugin since otherwise you could get lots of unauth. trade customers */

	function add_user_additional_details_frontend_reg( $user_id ){

	    $registered_user = get_user_by('ID', $user_id);

	    if($registered_user) {

	        $user_role = $registered_user->roles;

	        if((in_array('customer', (array) $user_role))){
	            /* The field below "front_end_cust_form" is just a hidden field I added to check and make sure that this is coming from the Front end Reg form where I added the additional fields */
	            if($_POST['account-form-check'] == 'account-form-check'){

	            	if (isset($_POST['b2b_role'])) {
	                
		                $b2b_accept = $_POST['b2b_role'];

		                if($b2b_accept) :
		                	$registered_user->add_role('tradecust');
		                	$registered_user->remove_role('customer');
		                endif;
		            }
	            }

	        }
	    }
	}

	add_action( 'user_register', 'add_user_additional_details_frontend_reg', 10, 1 );