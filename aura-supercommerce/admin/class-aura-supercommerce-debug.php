<?php

/**
 * The Debug-specific functionality of the plugin.
 *
 *
 * @link              https://auracreativemedia.co.uk
 * @since             1.0.0
 * @package           Aura_Supercommerce
 * @subpackage 	      Aura_Supercommerce/includes
 */

 
class Aura_Supercommerce_Debug {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}


	/**
	 * Returns plugin for settings page
	 *
	 * @since    	1.0.0
	 * @return 		string    $Filter_WP_Api       The name of this plugin
	 * @link        https://github.com/ogulcan/filter-wp-api/blob/master/filter-wp-api/admin/class-filter-wp-api-admin.php
	 */

	public function get_plugin() {
		return $this->Aura_Supercommerce;
	}



	public function admin_menu_debug_page(){
	    add_submenu_page( 'aura-supercommerce', 'Debug', 'Debug', 'manage_debug', 'debug', array( $this, 'admin_show_debug_page' ) );
	}

	public function user_menu_status_page(){
	    add_submenu_page( 'aura-supercommerce', 'Status', 'Status', 'manage_options', 'aura-status', array( $this, 'user_show_status_page' ) );
	}


	public function admin_show_debug_page(){ 

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/admin-display-debug.php';
			
	}


	public function user_show_status_page(){
	
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/admin-display-status.php'; 
			
	}

	/**
	 * Add a new dashboard widget.
	 */
	public function aurasc_add_dashboard_status_widgets() {
	    wp_add_dashboard_widget( 'dashboard_widget_status', 'Creative Commerce Status', array( $this, 'dashboard_widget_status_callback') );
	}

	public function aurasc_add_dashboard_helpdesk_widgets() {
	    wp_add_dashboard_widget( 'dashboard_widget_helpdesk', 'Helpdesk', array( $this, 'dashboard_widget_helpdesk_callback') );
	}


	 

	/**
	 * Output the contents of the dashboard widget
	 */
	public function dashboard_widget_status_callback( $post, $callback_args ) {



	    $issue_count = $this::display_counted_status_issues();

	   
	    if($issue_count > 0) : 
	    	echo '<h3 style="margin-bottom: 20px;"><span class="dashicons dashicons-warning" style="color: orange;"></span> System reports: <strong style="color: red;">' . $issue_count . '</strong> potential issues</h3>';
	    	echo '<a href="' . admin_url( 'admin.php?page=aura-status' ) . '"><button class="button-primary">Click here to check and resolve</button></a>'; 
	    else:
	    	echo '<h4><span class="dashicons dashicons-yes-alt" style="color: green;"></span> Great! System reports no potential issues</h4>';
	    endif;


	 	echo '<hr style="margin: 20px 0;">';

	    esc_html_e( "Creative Commerce will periodically check the system for Status issues", "aura-supercommerce" );

	}

	/**
	 * Output the contents of the dashboard widget
	 */
	public function dashboard_widget_helpdesk_callback( $post, $callback_args ) {
		echo '<h2><span class="dashicons dashicons-editor-help" style="font-size: 34px;
    display: inline-block;
    width: 37px;
    color: #41a8d0;
    padding-right: 4px;"></span>Looking for Help?</h2>';

	 	echo '<hr style="margin: 20px 0;">';

	    echo '<a href="https://helpdesk.digitalzest.co.uk/" target="_Blank"><button class="button-primary">Click here for  Help Desk</button></a>';
	}

	// functions that deal with the nags one by one

	// function that runs all the nags to check if they return true
	public function status_req_attr_exist(){

		$taxonomy_exist = taxonomy_exists( 'pa_pack-size' );

		if ($taxonomy_exist) {

			return true;

		} else {

			return false;
		}
	}

	public function status_req_terms_exist(){

		$term_single = term_exists( 'single-item', 'pa_pack-size' );
		$term_multi = term_exists( 'multi-pack', 'pa_pack-size' );

		if ($term_single && $term_multi) {

			return true;

		} else {

			return false;
		}
	}



	public function status_unassigned_attr_exist(){

		$args = array(
		    'posts_per_page'   => -1,
		    'post_type'        => 'product'
		);

		$the_query = get_posts($args);

		$results = WC_PB_DB::query_bundled_items( array(
		    'return'    => 'id=>product_id',
		   
		) );

		$taxonomy_exist = $this::status_req_attr_exist();
		$terms_exist =  $this::status_req_terms_exist();

		if ($taxonomy_exist && $terms_exist ) :

		$warning_products = array();

			foreach ($results as $product) {
				if( !has_term( 'single-item', 'pa_pack-size', $product ) ) {
					$warning_products[] = $product;
				}
			}

			if ($warning_products) :

				return $warning_products;
			
			else: return false;	
			
			endif;
		endif;

	}

	public function global_membership_price_tier_exists(){


		$wc_mem_discounts = get_option( 'wc_memberships_rules' );

		$none_found = true;
		$mem_plans = array();

		foreach ($wc_mem_discounts as $plan) {
			
			if(!$plan['object_ids'] && $plan['active'] === 'yes') {

				$none_found = false;

				$mem_plans[] = $plan;
			}

		}

		if ($none_found) : return false; endif;

		return $mem_plans;

	}

	public function missing_product_images_exist(){

		global $wpdb;

		$product_ids = $wpdb->get_col( "
		    SELECT ID
		    FROM {$wpdb->prefix}posts p
		    INNER JOIN  {$wpdb->prefix}postmeta pm ON p.ID = pm.post_id
		    WHERE ID NOT IN (SELECT post_id FROM {$wpdb->prefix}postmeta WHERE meta_key = '_thumbnail_id')
		    AND p.post_type = 'product'
		    AND p.post_status = 'publish'
		    AND pm.meta_key = '_stock_status'
		    AND pm.meta_value = 'instock'
		");

		// Raw output
		

		$args = array(
		  'post__in'  => $product_ids, // ID of a page, post, or custom type
		  'post_type' => 'product'
		);

		$my_posts = new WP_Query($args);
        
        if($my_posts) : return $my_posts; else : return false; endif;

	}


	public function tradecust_no_attached_membership(){

		$user_query = new WP_User_Query( array( 'role' => 'tradecust' ) );
		$trade_customers = $user_query->get_results();

		if ( ! empty( $trade_customers ) ) {

			$members_flagged = array();
		   
		    foreach ( $trade_customers as $user ) {

		        $customer = get_userdata( $user->ID );
		        $user_id = $customer->ID;
				$user_name = $customer->user_nicename;

		        $mem_data = wc_memberships_get_user_active_memberships($user_id);

		    	if(!$mem_data) : $members_flagged[] = $user_id; endif;

		    }

		    if($members_flagged) : return $members_flagged; else : return false; endif;
		  
		}

	}

	public function tradecust_no_attached_agents(){

		$user_data = array();

		$user_query = new WP_User_Query( array( 'role' => 'tradecust' ) );
		$trade_customers = $user_query->get_results();

		if ( !empty( $trade_customers ) ) :

			foreach($trade_customers as $user){

				$user_meta = get_user_meta ( $user->ID, '_agent_user');

				if(empty($user_meta[0]) || $user_meta[0] === "") :

			    	$user_data[] = $user->ID;

			    endif;
			}

			return $user_data;

		endif;
	}

	public function agents_no_attached_customers(){

		$agent_ids = array();

		$agent_query = new WP_User_Query( array( 'role' => 'store_agent' ) );
		$agents = $agent_query->get_results();

		if ( !empty( $agents ) ) :

			foreach($agents as $agent){

				$agent_ids[] = $agent->ID;

			}

		return $agent_ids;

		// now that we have all agent ID's loop over users to see if there are any agent ids which never appear in the user list

		//loop trade custs, stick in an array: all unique _agent_user ids, map on array to see if the value agent is exists


		endif;


	}

	public function display_counted_status_issues(){

		$counter = 0;
		$required = array();
		$status_flags = array();


		$aura_sc_admin = new Aura_Supercommerce_Admin($this->plugin_name, $this->version);
		$dualeng_plugin_exists = $aura_sc_admin->check_child_plugin_exists( 'aura-dual-engine' );
		
		if ($dualeng_plugin_exists) :

			// Positive Flags
			$required[] = $this::status_req_attr_exist();
			$required[] = $this::status_req_terms_exist();

			// Negative flags
			$status_flags[] = $this::status_unassigned_attr_exist();
			$status_flags[] = $this::global_membership_price_tier_exists();
			$status_flags[] = $this::missing_product_images_exist();
			$status_flags[] = $this::tradecust_no_attached_membership();

		endif;

		foreach ($required as $req) {
			if ($req == false) : $counter++; endif;
		}

		foreach ($status_flags as $value) {
			if ($value == true) : $counter++; endif;
		}

		return $counter;

	}

	public function check_plugin_dependencies(){

		foreach (AURA_SUPERCOMMERCE_PLUGINS as $plugin_name => $data ) {

			$aura_sc_admin = new Aura_Supercommerce_Admin( $this->plugin_name, $this->version );
			$_plugin_exists = $aura_sc_admin->check_child_plugin_exists( $plugin_name );

			if($_plugin_exists && $data['dependencies']) :

		 	echo "<p><strong style='text-transform: capitalize;'>" . $plugin_name . "</strong>: ";

			 	if($data['dependencies']) :

			 	foreach ($data['dependencies'] as $value) {
			 		
			 		if ( !function_exists('is_plugin_active') || ( !is_plugin_active( $value . '/' . $value . '.php') && !is_plugin_active( $value . '/init.php')) ) {

			 			echo "<a href='" . get_site_url() . "/wp-admin/plugin-install.php?s=" . $value . "&tab=search&type=term'><span class='disabled-red' style='color: red; text-transform: capitalize; padding-left: 6px;'><i class='fa fa-times'></i> " . $value . '</span></a>';

			 		} else {
			 			echo "<span class='active-green'  style='color: green; text-transform: capitalize; padding-left: 6px;'><i class='fa fa-check'></i> " . $value . '</span>';
			 		}
			 	}

			 	endif;

			 echo "</p>";

		 	endif;

		 } 

	}


}



