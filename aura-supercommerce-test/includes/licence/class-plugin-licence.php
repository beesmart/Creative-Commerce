<?php

/**
 * The licence-specific functionality of the plugin.
 *
 *
 * @link              https://auracreativemedia.co.uk
 * @since             1.0.0
 * @package           Aura_Supercommerce
 * @subpackage        Aura_Supercommerce/includes/licence
 */



// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;



class aura_licence_checker {

	/*
	* @FLAG : Migration, migrate 
	*/

	// This is the secret key for API authentication. You configured it in the settings menu of the licence manager plugin.
	private const YOUR_SPECIAL_SECRET_KEY = '5f4387977ab2f2.77705274'; //Rename this constant name so it is specific to your plugin or theme.
	// This is the URL where API query request will be sent to. This should be the URL of the site where you have installed the main licence manager plugin. Get this value from the integration help page.
	protected const YOUR_licence_SERVER_URL = 'https://supercommerce.auracreativemedia.co.uk/'; //Rename this constant name so it is specific to your plugin or theme.

	// This is a value that will be recorded in the licence manager data so you can identify licences for this item/product.
	protected const YOUR_ITEM_REFERENCE = 'Aura Supercommerce'; //Rename this constant name so it is specific to your plugin or theme.

	public function __construct() {

		$this->api_request     = "";

	}


	/**
	 * Makes the remote API request to parent server and returns Licence data based on licence key
	 *
	 * @link https://www.tipsandtricks-hq.com/software-license-manager-plugin-for-wordpress
	 * @param _action $_action defines the action to run on  the parent server, e.g. check licence, add new etc. We'll use slm_check almost always
	 * @param _key $_key is the licence key entered into the client site
	 *
	 * @return array/object The licence data, various fields including the expiry, associated products, almost everything
	 */

	private function api_request( $_action, $_key ) {


		$api_params = array(
		    'slm_action' => $_action,
		    'secret_key' => self::YOUR_SPECIAL_SECRET_KEY,
		    'license_key' => $_key, // Licence is spelt in US English! Yanks eh!
		    'registered_domain' => $_SERVER['SERVER_NAME'],
		    'item_reference' => urlencode(self::YOUR_ITEM_REFERENCE),
		);

		// Send query to the licence manager server
		$query = esc_url_raw(add_query_arg($api_params, self::YOUR_licence_SERVER_URL));
		;
		// Do we have this information in our transients already?
		$transient_data = get_transient( 'licence_transient_data');
		$transient_status = get_transient( 'licence_transient_status');

		$response = false;

		// split transients based on action because if not when this fires on an activate action it only updates with status and never retrieves the rest of the useful data.
		
		if($_action === 'slm_activate' || $_action === 'slm_deactivate') :

			 if( ! empty( $transient_status ) ) {
			// The function will return here every time after the first time it is run, until the transient expires.
			    $licence_data = $transient_status;
				
			 // Nope!  We gotta make a call.
			 } else {
			 	$response = wp_remote_get($query, array('timeout' => 20, 'sslverify' => false));
			 	// licence data.
			 	$licence_data = json_decode(wp_remote_retrieve_body($response));
			 
			 	set_transient( 'licence_transient_status', $licence_data, HOUR_IN_SECONDS );
			 	
			}

			if (is_wp_error($response)){
	            echo "Unexpected Error! The query returned with an error.";
	        }

		endif;

		// split transients based on action
		if($_action === 'slm_check') :

			 if( ! empty( $transient_data ) ) {
			// The function will return here every time after the first time it is run, until the transient expires.
			    $licence_data = $transient_data;
				
			 // Nope!  We gotta make a call.
			 } else {
			 	$response = wp_remote_get($query, array('timeout' => 20, 'sslverify' => false));
			 	// licence data.
			 	$licence_data = json_decode(wp_remote_retrieve_body($response));
			 
			 	set_transient( 'licence_transient_data', $licence_data, HOUR_IN_SECONDS );
			 	
			}

			if (is_wp_error($response)){
	            echo "Unexpected Error! The query returned with an error.";
	        }

		endif;
		

      
        return $licence_data;


	}

	/**
	 *
	 * Listens for a licence activation submission, (from button) makes the API Request and either activates by adding the key to the options table or returns an error
	 * 
	*/

	public function activate_licence(){
		
		if (isset($_REQUEST['activate_licence'])) {

			// Delete Transidents in case they are switching to a new licence code
			$aura_sc_admin  = new Aura_Supercommerce_Admin( $this->plugin_name, $this->version );
			$aura_sc_admin->delete_transients();

	        $licence_key = $_REQUEST['aura_licence_key'];
	        
	      	$licence_data = $this->api_request( 'slm_activate', $licence_key );

	      	//I'm checking error code 40 - already in use - since it should only fire if they are trying to reenable on the same site.
	        if($licence_data->result == 'success' || $licence_data->error_code == 40){//Success was returned for the licence activation
	            
	            //Uncomment the followng line to see the message that returned from the licence server
	            echo '<br />The following message was returned from the server: '. $licence_data->message;
	            
	            //Save the licence key in the options table
	            update_option('aura_licence_key', $licence_key); 
	        }
	        else{
	            //Show error to the user. Probably entered incorrect licence key.
	            
	            //Uncomment the followng line to see the message that returned from the licence server
	            echo '<br />The following message was returned from the server: '. $licence_data->message;
	        }

  	    }

	} 

	/**
	 *
	 * Inverse to the activate_licence function
	 * 
	*/

	public function deactivate_licence(){


		/*** licence activate button was clicked ***/
		if (isset($_REQUEST['deactivate_licence'])) {

			$aura_sc_admin  = new Aura_Supercommerce_Admin( $this->plugin_name, $this->version );
			$aura_sc_admin->delete_transients();

		    $licence_key = $_REQUEST['aura_licence_key'];

		    $licence_data = $this->api_request( 'slm_deactivate', $licence_key );
		    
		    //I'm checking error code 40 - already in use - since it should only fire if they are trying to reenable on the same site.
	        if($licence_data->result == 'success' || $licence_data->error_code == 40) {//Success was returned for the licence activation
		        
		        //Uncomment the followng line to see the message that returned from the licence server
		        echo '<br />The following message was returned from the server: '. $licence_data->message;
		        
		        //Remove the licensse key from the options table. It will need to be activated again.
		        update_option('aura_licence_key', '');
		    }
		    else{
		        //Show error to the user. Probably entered incorrect licence key.
		        
		        //Uncomment the followng line to see the message that returned from the licence server
		        echo '<br />The following message was returned from the server: '. $licence_data->message;
		    }
		    
		}
		/*** End of sample licence deactivation ***/
	}


	/**
	 * A GET function which grabs the licence key Status by calling the api_request.
	 *
	 *
	 * @return string Will return 'active' or 'inactive' based on the API response data
	 */


	public function check_has_licence(){


		$licence_key = get_option('aura_licence_key');

		if($licence_key) {

			$licence_data = $this->api_request( 'slm_check', $licence_key );
	 
	        if($licence_data){

	        	$licence_check_response = $licence_data->status;

	        	return $licence_check_response;
	        }

		} else {

			return "Key is either invalid, disabled or not entered";
		}

	}


	/**
	 * A GET function that checks which products $this licence has attached to it.
	 *
	 * @used-by Primarily the updater class to check which plugins are allowed to update
	 * @return array Will return an array of slugs
	*/


	public function check_licence_products(){


		$licence_key = get_option('aura_licence_key');

		if($licence_key) {

			$licence_data = $this->api_request( 'slm_check', $licence_key );

	        if($licence_data){

	        	$licence_check_products = $licence_data->attached_products;
	        	$products = unserialize($licence_check_products->attached_products);

	        	return $products;
	        }

		} else {

			return "Key is either invalid, disabled or not entered";
		}

	}

	/**
	 * A GET function 
	 * @return array Will return an array of slugs
	*/


	public function check_licence_data(){


		$licence_key = get_option('aura_licence_key');

		if($licence_key) {

			$licence_data = $this->api_request( 'slm_check', $licence_key );

	        if($licence_data){

	        	return $licence_data;
	        }

		} else {

			return "Key is either invalid, disabled or not entered";
		}

	}


	/**
	 * A function that compares the queried parameter with the licence key data, primarily to assess whether the end-user is entitled to this product
	 *
	 * @used-by Primarily the admin class to check what to display to the end user on the admin screen
	 * @param $query - the queried product Slug
	 * @return Bool - Will return True if the licence contains the queried product
	*/

	public function compare_licence_products( $query ){

			$licence_key = get_option('aura_licence_key');

			if($licence_key) {

				$licence_data = $this->api_request( 'slm_check', $licence_key );
		 
		        if($licence_data){

		        	$licence_check_products = $licence_data->attached_products;
		        	$products = unserialize($licence_check_products->attached_products);

		        	foreach ($products as $value) {
		        		$products_to_compare = $this->explode_on_product_title($value);
		    
		        		if($products_to_compare === $query) : return true; endif;
		        	}
		        
		        }

			} else {

				return false;
			}
	}

	/**
	 * A minor helper function that splits up the licence key response data (the products). They arrive as a string e.g. Product4|Product5 etc. and this needs to be seperated into an array
	 *
	 * @used-by Primarily the admin class to check what to display to the end user on the admin screen
	 * @param $products - the queried product Slug
	*/

	public function explode_on_product_title( $products ){

		$exploded_value = explode('|', $products);
		$id = $exploded_value[0];
		$expl_slug = $exploded_value[1];

		return $expl_slug;
	}

	/**
	 * A minor helper function that splits up the licence key response data (the ID's). They arrive as a string e.g. 3|4 etc. and this needs to be seperated into an array
	 *
	 * @used-by Primarily the admin class to check what to display to the end user on the admin screen
	 * @param $products - the queried product Slug
	*/

	public function explode_on_product_id( $products ){

		$exploded_value = explode('|', $products);
		$id = $exploded_value[0];
		$expl_slug = $exploded_value[0];

		return $expl_slug;
	}


	/**
	 *
	 * A GET function that checks which Administrators $this licence has attached to it. We use this to stop other site admins i.e. the client from having broader control.
	 *
	 * @return array/obj Serialized data containing Admin usernames
	*/

	public function check_licence_admins(){


		$licence_key = get_option('aura_licence_key');

		if($licence_key) {

			$licence_data = $this->api_request( 'slm_check', $licence_key );
	 
	        if($licence_data){

	        	$licence_check_admins = $licence_data->priv_users;

	        	return $licence_check_admins;
	        }

		} else {

			return "Key is either invalid, disabled or not entered";
		}

	}





}