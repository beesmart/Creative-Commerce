<?php

/**
 * The admin-specific functionality of the plugin.
 *
 *
 * @link              https://auracreativemedia.co.uk
 * @since             1.0.0
 * @package           Aura_Supercommerce
 * @subpackage 	      Aura_Supercommerce/includes
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 *
 * @since             1.0.0
 * @package           Aura_Supercommerce
 * @subpackage 	      Aura_Supercommerce/includes
 */
class Aura_Supercommerce_Admin {

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
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Plugin_Name_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Plugin_Name_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/admin.css', array(), $this->version, 'all' );
		wp_enqueue_style( $this->plugin_name . '_gfont', 'https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;700&display=swap', array(), 'all' );
		wp_enqueue_style( $this->plugin_name . 'fawesome', 'https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css', array(), 'all' );
		

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Plugin_Name_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Plugin_Name_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/admin.js', array( 'jquery' ), $this->version, false );

	}

	/**
	 * Check Required Plugins - e.g. WooCommerce, are active. If not deactivate this plugin.
	 *
	 * @since    1.0.0
	 */

	public function check_required_plugins() {
		
		include_once(ABSPATH.'wp-admin/includes/plugin.php');
		
		if (!function_exists('is_plugin_active') || !is_plugin_active('woocommerce/woocommerce.php')) { 

		    deactivate_plugins( '/aura-supercommerce/aura-supercommerce.php' );
		    // wp_die(sprintf(__('Please Install Woocommerce to Continue')));
		}


	}

	/**
	 * Setup our admin page and a template for displaying admin HTML
	 *
	 * @since    1.0.0
	 */

	 
	public function plugin_setup_menu_backend(){
	    add_menu_page( 'Creative Commerce Page', 'Creative Commerce', 'manage_options', 'aura-supercommerce', array( $this, 'admin_page_display' ) );  
	}

	 
	public function admin_page_display(){ 
	    require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/admin-display.php';
	}



	/**
	 * When called delete Transient Licence Data, this prevents the API from putting too much strain on the remote server
	 *
	 * @since    1.0.0
	 */

	public function delete_transients() {

		  delete_transient( 'licence_transient_data' );
		  delete_transient( 'licence_transient_status' );
		  wp_redirect(admin_url('?page=aura-supercommerce'));

	}


	/**
	 * Returns an Object containing all exisiting plugins on the website. This Data is then compared to the Constant AURA_SUPERCOMMERCE_PLUGINS to see which plugins are Aura and those that are from other Authors.
	 *
	 * @since    1.0.0
	 * @return   Array/Object - Contains title and version of all plugins active on the website
	 */

	private function list_active_plugins( ){

		// Get plugin list from the database
		$apl = get_option('active_plugins');
		$plugins = get_plugins();
		$activated_plugins = array();
		$plugin_list = array();


		// Loop through active plugins and assign them to an empty array
		foreach ($apl as $p){           
		    if(isset($plugins[$p])){
		         array_push($activated_plugins, $plugins[$p]);
		    }           
		}

		// assign the active plugins to an object
		foreach ($activated_plugins as $value) {
			$plugin_list[] = (object) [
				"title" => $value['TextDomain'],
				"version" => $value['Version']
			];
		}

		return $plugin_list;
		
	}

	/**
	 * The plugin description and details are contained within /partials/files/some-plugin/
	 * - details.txt
	 * - features.txt
	 *
	 * This function will grab one of those files for displaying to the end user.
	 *
	 * @since    1.0.0
	 * @param    $text - Whether to grab the details or features file for display
	 * @param    $slug - $this plugin slug to find the $this plugin directory 
	 * @return   string - The Output from the file contents (HTML or plaintext)
	 */

	private function get_plugin_text( $text = 'details', $slug ){

		$file = plugin_dir_path( __FILE__ ) . 'partials/files/' . $slug . '/' . $text . '.txt';

		$fileExists = file_exists($file);

		$text = "";

		if ($fileExists){

		    $text = file_get_contents($file);
		}

		return $text;

	}

	public function assign_debug_capability(){

		$users_to_add_cap = $this->get_privileged_users();


		foreach ($users_to_add_cap as $user) {

			$user_obj = get_user_by( 'login', $user );

			$priv_user = new WP_User( $user_obj->ID );
			$priv_user->add_cap( 'manage_debug');

		}


	}



	/**
	 * Calls the remote API to find our list of privileged admins (i.e. those who can see Super admin screens)
	 *
	 * @since    1.0.0
	 * @return   array - a list of all priviliged admins, assigned to the licence
	 */

	private function get_privileged_users(){

		$aura_licence_checker = new aura_licence_checker;
		$admins = $aura_licence_checker->check_licence_admins();
	
		if (is_object($admins)) :
	    	$privileged_users = unserialize($admins->priv_users);

	    	return $privileged_users;
	    	
	    endif;

	}


	/**
	 * Checks that the current user is one of the privilged admins
	 *
	 * @since    1.0.0
	 * @return   bool - Returns true if the current user matches the priv. admin list, if not false.
	 */


	public function check_current_is_privileged_user(){

		$current_user = wp_get_current_user();
		$users_to_check = $this->get_privileged_users();
		$array_contains_admin = false;
		
		if($users_to_check) :
			foreach ($users_to_check as $user) {
		 	  if ($user === $current_user->user_login) : $array_contains_admin = true; endif;
			}
		endif;

		return $array_contains_admin;

	}



	/**
	 * Creates the Admin page for the main plugin
	 *
	 * @since    1.0.0
	 */

	public function admin_display_tabs(){

		$show_admin_screens = false;

		if($this->check_current_is_privileged_user()) : $show_admin_screens = true; endif;

		?>
	
		<ul class="aura-tabs wp-tab-bar">

			<?php 
				if ($show_admin_screens) { ?>
					<li class="wp-tab-active"><a href="#tabs-1">Snippets</a></li>
					<li><a href="#tabs-2">Products</a></li>
					<li><a href="#tabs-3">Licence</a></li>
				<?php } else { ?>

					<li class="wp-tab-active"><a href="#tabs-2">Products</a></li>
					<li><a href="#tabs-3">Licence</a></li> 
				<?php

				}
			?>
			
			

		</ul>

	
		<?php

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/admin-display-snippets.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/admin-display-plugins.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/admin-display-licence.php'; 
	
	}

	

	public function check_child_plugin_exists( $plugin_slug ){

		$slug_param = $plugin_slug . '/' . $plugin_slug . '.php';
		
		if ( is_plugin_active( $slug_param ) ) {

		    return true;

		} else {

			return false;
		}

	}


	/**
	 * This function runs when WordPress completes its upgrade process
	 * It iterates through each plugin updated to see if ours is included
	 *
	 * @param $upgrader_object Array
	 * @param $options Array
	 * @see https://codex.wordpress.org/Plugin_API/Action_Reference/upgrader_process_complete
	 */

	public function aura_upgrade_completed( $upgrader_object, $options ) {

	 // The path to our plugin's main file
	 $our_plugin = plugin_basename( __FILE__ );

	 // If an update has taken place and the updated type is plugins and the plugins element exists

	 if( $options['action'] == 'update' && $options['type'] == 'plugin' && isset( $options['plugins'] ) ) {

	  // Iterate through the plugins being updated and check if ours is there
	  foreach( $options['plugins'] as $plugin ) {

	   if( $plugin == $our_plugin ) {

	    // Set a transient to record that our plugin has just been updated
	    set_transient( 'aura_supercommerce_updated', 1 );

	    $aura_sc_custom = new Aura_Supercommerce_Custom;
	    $aura_sc_custom->_refresh_snippets();


	   }

	  }

	 }

	}
	


}



