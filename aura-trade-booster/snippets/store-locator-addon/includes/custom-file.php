<?php


include_once(plugin_dir_path(__FILE__) . "../aura-store-locator-customization.php");



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

?>