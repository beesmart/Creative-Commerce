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


	/**
	 * Setup our Debug and Status pages for displaying HTML that outputs useful warnings and notices for admins and web managers.
	 *
	 * @since    1.0.0
	*/

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
	 * Add dashboard widgets for status and a link to a helpdesk.
	 * @since    1.0.0
	*/

	public function aurasc_add_dashboard_status_widgets() {
	    wp_add_dashboard_widget( 'dashboard_widget_status', 'Creative Commerce Status', array( $this, 'dashboard_widget_status_callback') );
	}

	public function aurasc_add_dashboard_helpdesk_widgets() {
	    wp_add_dashboard_widget( 'dashboard_widget_helpdesk', 'Helpdesk', array( $this, 'dashboard_widget_helpdesk_callback') );
	}



	/**
	 * Output the dashboard widgets HTML and content
	 *
	 * @since    1.0.0
	*/

	public function dashboard_widget_status_callback( $post, $callback_args ) {

		$aura_sc_admin = new Aura_Supercommerce_Admin($this->plugin_name, $this->version);
		$trade_status = $aura_sc_admin->get_trade_status();
	    $issue_count = $this::display_counted_status_issues();

	    if($issue_count > 0) : 

	    	echo '<h3 style="margin-bottom: 20px;"><span class="dashicons dashicons-warning" style="color: orange;"></span> System reports: <strong style="color: red;">' . $issue_count . '</strong> potential issues</h3>';
	    	echo '<a href="' . admin_url( 'admin.php?page=aura-status' ) . '"><button class="button-primary">Click here to check and resolve</button></a>'; 

	    else:

	    	echo '<h4><span class="dashicons dashicons-yes-alt" style="color: green;"></span> Great! System reports no potential issues</h4>';

	    endif;

	    echo '<hr style="margin: 15px 0;">';

	    if ($trade_status === "TRUE" && $trade_status != 'FALSE') : echo '<h5>Trade Only Platform</h5>'; endif;

	 	echo '<hr style="margin: 20px 0;">';

	    esc_html_e( "Creative Commerce will periodically check the system for Status issues", "aura-supercommerce" );
	}

	public function dashboard_widget_helpdesk_callback( $post, $callback_args ) {
		echo '<h2><span class="dashicons dashicons-editor-help" style="font-size: 34px;
    display: inline-block;
    width: 37px;
    color: #41a8d0;
    padding-right: 4px;"></span>Looking for Help?</h2>';
	 	echo '<hr style="margin: 20px 0;">';
	    echo '<a href="https://helpdesk.digitalzest.co.uk/home-cc/" target="_Blank"><button class="button-primary">Click here for  Help Desk</button></a>';
	}



	/**
	 * The following functions are used by the status page, they define whether a check or test is passed. These checks will only run if the required dependency is installed.
	 *
	 * @since    1.0.0
	*/

	/**
	 * If a required Attribute 'pa_pack-size' is not found, remind the user to create this as it's key to product bundles functioning.
	 *
	 * @since    1.0.0
	 * @return   Boolean
	*/

	public function status_req_attr_exist(){
		$taxonomy_exist = taxonomy_exists( 'pa_pack-size' );
		if ($taxonomy_exist) {
			return true;
		} else {
			return false;
		}
	}



	/**
	 * Check that the option value for auto assigned memberships is assigned, it shoudl be set to a membership plan id
	 *
	 * @since    1.4.6
	 * @return   Int
	*/

	public function get_auto_assigned_plan_id(){
		$aa_def_membplan = get_option( 'aa_def_membplan' );
		if ($aa_def_membplan) {
			return $aa_def_membplan;
		} else {
			return false;
		}
	}


	/**
	 * Similar to status_req_attr_exist, this function checks if the relevant terms within the req. attribute exist as it's key to product bundles functioning.
	 *
	 * @since    1.0.0
	 * @return   Boolean
	*/

	public function status_req_terms_exist(){
		$term_single = term_exists( 'single-item', 'pa_pack-size' );
		$term_multi = term_exists( 'multi-pack', 'pa_pack-size' );
		if ($term_single && $term_multi) {
			return true;
		} else {
			return false;
		}
	}



	/**
	 * If an admin user has created a product bundle version of a product, which means you now have 2 versions of a product, a pack and a single. We need to make sure they have
	 * assigned a term to the singkle version called 'single-item'. This is to ensure visibility
	 *
	 * @since    1.0.0
	 * @return   Array | Boolean - Array contains product data of products which failed the check, else return False because there are no problem products
	*/

	public function status_unassigned_attr_exist(){
		$args = array(
		    'posts_per_page'   => -1,
		    'post_type'        => 'product',
		    'post_status' => array('publish', 'pending'),
		);
		$the_query = get_posts($args);
		if(class_exists('WC_PB_DB')) :
			$results = WC_PB_DB::query_bundled_items( array(
			    'return'    => 'id=>product_id',
			) );
		endif;
		$taxonomy_exist = $this::status_req_attr_exist();
		$terms_exist =  $this::status_req_terms_exist();
		if ($taxonomy_exist && $terms_exist ) :
		$warning_products = array();
			foreach ($results as $product) {
				if( !has_term( 'single-item', 'pa_pack-size', $product ) ) {
					if ( get_post_status ( $product ) ) {
					   $warning_products[] = $product;
					}
				}
			}
			if ($warning_products) :
				return $warning_products;
			else: return false;	
			endif;
		endif;
	}

	/**
	 * A problem identified previosuly is that Global price settings in WooCommerce Memberships can be unexpected/unintended, applying sweeping rules to all products. Therefore we check to see if there are any 
	 * global plans. If so provide a warning. 
	 * It may be intentional so it may be the warning can be disregarded.
	 *
	 * @since    1.0.0
	 * @return   Array | Boolean - Array contains membership plan data of plans which are flagged as having globally applying rules, else return False
	*/

	public function global_membership_price_tier_exists(){
		$wc_mem_discounts = get_option( 'wc_memberships_rules' );
		$none_found = true;
		$mem_plans = array();
		if ($wc_mem_discounts) :
		foreach ($wc_mem_discounts as $plan) {
			if(!$plan['object_ids'] && $plan['active'] === 'yes') {
				$none_found = false;
				$mem_plans[] = $plan;
			}
		}
		endif;
		if ($none_found) : return false; endif;
		return $mem_plans;
	}

	/**
	 * Query the database postmeta for any products with missing images
	 *
	 * @since    1.0.0
	 * @return   Array | Boolean - Array contains post data which are flagged as having a missing featured image, else return False because there are no offending products
	*/

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
		$my_posts = "";
		if ($product_ids) :
				$args = array(
			  'post__in'  => $product_ids, // ID of a page, post, or custom type
		  	  'post_type' => 'product'
			);
			$my_posts = new WP_Query($args);
		endif;
        if(!empty($my_posts)) : return $my_posts; else : return false; endif;
	}

	/**
	 * Checks if any trade customers are without a membership plan assigned. In almost all cases they should have. We iterate the trade customer role users and check if they have a membership.
	 *
	 * @since    1.0.0
	 * @return   Array | Boolean - Array contains user data which are flagged as having a no membership plan, else return False because there are no offending users
	*/

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

	/**
	 * Checks if any trade customers are without an agent assigned. Not all customers should have so this is a notice not a warning.
	 * @since    1.0.0
	 * @return   Array - Array contains user data which are flagged as having a no agent
	*/
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



	/**
	 * Checks if agents are without ANY customers attached.
	 *
	 * @since    1.0.0
	 * @return   Array - Agent User Id's - those who don't have any customers on their profile.
	*/

	public function agents_no_attached_customers(){
		$agent_ids = array();
		$agent_query = new WP_User_Query( array( 'role' => 'store_agent' ) );
		$agents = $agent_query->get_results();
		if ( !empty( $agents ) ) :
			foreach($agents as $agent){
				$agent_ids[] = $agent->ID;
			}
		return $agent_ids;
		endif;
	}


	/**
	 * Checks tax exclusion is set
	 *
	 * @since    1.0.0
	 * @return   
	*/

	public function exclude_tax_options_check(){
		
		$exclude_tax_options = get_option('woocommerce_exclude_tax_options');

		if($exclude_tax_options) : 
			return true;

		else :
			
			return false;

		endif;


	}






	/**
	 * Counts the various status flags, splitting them into positive and negative flags. Used by the dashboard widget
	 *
	 * @since    1.0.0
	 * @return   Int - A number which shows how many status warnings/notices a user has.
	*/

	public function display_counted_status_issues(){

		$counter = 0;
		$required = array();
		$status_flags = array();
		$aura_sc_admin = new Aura_Supercommerce_Admin($this->plugin_name, $this->version);
		$dualeng_plugin_exists = $aura_sc_admin->check_child_plugin_exists( 'aura-dual-engine' );
	    $trade_status = $aura_sc_admin->get_trade_status();

		if ($dualeng_plugin_exists && (!$trade_status || $trade_status == "FALSE")) :

			// Positive Flags
			$required[] = $this::status_req_attr_exist();
			$required[] = $this::status_req_terms_exist();
			$required[] = $this::exclude_tax_options_check();

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


	/**
	 * Use our dependency data from our Licence website - supercommerce.auracreativemedia.co.uk - REST JSON API - check which dependecies are activated or missing. Then output this as HTML feedback on the status page. Special shoutout to ACF + friends which use a slightly different naming format.
	 *
	 * @since    1.0.0
	 * @return   HTML - Echo HTML feedback based on whether a dep. is or isn't installed/activated.
	*/

	public function check_plugin_dependencies(){


		/* TRANSIENTS FOR CACHING THE JSON DATA */

		$prod_url = 'https://supercommerce.auracreativemedia.co.uk/wp-json/aura_product/v1/post';
		$plug_url = 'https://supercommerce.auracreativemedia.co.uk/wp-json/aura_plugin/v1/post';
		// Namespace in case of collision, since transients don't support groups like object caching.
		$cache_key = md5( 'remote_request_prod2|' . $prod_url );
		$cache_key2 = md5( 'remote_request_plug2|' . $plug_url );
		$request = get_transient( $cache_key );
		$request2 = get_transient( $cache_key2 );
	
		if ( false == $request ) {

			$request = wp_remote_get( $prod_url );
		
			if ( is_wp_error( $request ) ) {
				// Cache failures for a short time, will speed up page rendering in the event of remote failure.
				set_transient( $cache_key, $request, MINUTE_IN_SECONDS * 30 );
				return false;
			}

			// Success, cache for a longer time.
			set_transient( $cache_key, $request, HOUR_IN_SECONDS * 2 );

		}

		if ( is_wp_error( $request ) ) {
			return false;
		}

		if ( false == $request2 ) {

			$request2 = wp_remote_get( $plug_url );
		
			if ( is_wp_error( $request2 ) ) {
				// Cache failures for a short time, will speed up page rendering in the event of remote failure.
				set_transient( $cache_key2, $request2, MINUTE_IN_SECONDS * 30 );
				return false;
			}

			// Success, cache for a longer time.
			set_transient( $cache_key2, $request2, HOUR_IN_SECONDS * 2 );

		}

		if ( is_wp_error( $request2 ) ) {
			return false;
		}


		/* END TRANSIENTS */

		$product_resp = wp_remote_retrieve_body( $request );
		$plug_resp = wp_remote_retrieve_body( $request2 );

		$product_resp = json_decode( $product_resp ); 
		$plug_resp = json_decode( $plug_resp ); 

	 	$dependencies = array();

	 	echo "<h3>Plugins with a <span style='color: gold;font-weight:bold;'>Gold</span> border are <strong>Premium</strong> Plugins</h3>";

		foreach (AURA_SUPERCOMMERCE_PLUGINS as $plugin_name => $data ) {

			$aura_sc_admin = new Aura_Supercommerce_Admin( $this->plugin_name, $this->version );
			$_plugin_exists = $aura_sc_admin->check_child_plugin_exists( $plugin_name );

			if($_plugin_exists && $product_resp) :

		 	echo "<p style='margin-top: 15px;'><strong style='text-transform: capitalize;text-decoration: underline;font-size: 16px;'>" . $plugin_name . "</strong>: ";

			 	foreach ((array) $product_resp as $item) {
			 		if(property_exists($item, 'acf')) {
			 		// Check if the existing plugin HTML element matches what is in JSON:products
			 		if($item->acf->product_slug === $plugin_name && property_exists($item->acf, 'plugins_attached')){
			 			// If so loop over the plugins attached to the JSON:product
			 			foreach($item->acf->plugins_attached as $plugin){

			 				// and for the JSON:plugin we match what is in JSON:product to grab some meta
			 				foreach((array) $plug_resp as $plugin_json){

			 					if($plugin_json->post_name === $plugin->post_name){

			 						$slug = $plugin_json->acf->plugin_slug;
			 						$paid_for = $plugin_json->acf->plugin_premium;
			 						$paid_for_css = "";

			 						if($paid_for === true){
			 							$paid_for_css = "border: 2px solid gold;";
			 						}

			 						if ( !function_exists('is_plugin_active') || 
			 							( !is_plugin_active( $slug . '/' . $slug . '.php') && 
			 								(!is_plugin_active( $slug . '/init.php') && 
			 								 !is_plugin_active( $slug . '/acf.php')  && 
			 								 !is_plugin_active( $slug . '/fl-builder.php')  && 
			 								 !is_plugin_active( $slug . '/wp-contact-form-7.php') 
			 								)
			 							 )
			 							) 
			 						{
			 					
			 						 	echo "<a href='" . get_site_url() . "/wp-admin/plugin-install.php?s=" . $slug . "&tab=search&type=term'><span class='disabled-red' style='" . $paid_for_css . "color: red; text-transform: capitalize; padding: 1px 6px;margin: 3px;display:inline-block;'><i class='fa fa-times'></i> " . $plugin->post_title . '</span></a>';

			 						 } else {
			 						 	echo "<span class='active-green' style='" . $paid_for_css . " color: green; text-transform: capitalize; padding: 1px 6px;margin: 4px;display:inline-block;'><i class='fa fa-check'></i> " . $plugin->post_title . '</span>';
			 						}

			 					}
			 				}
			 			}
			 				

				 			

			 			}
			 		}

			 		
			 	}
			 
			 echo "</p>";
		 	endif;
		 } 
	}
}
