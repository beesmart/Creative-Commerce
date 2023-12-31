<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://auracreativemedia.co.uk
 * @since      1.0.0
 *
 * @package    Aura_dual_engine
 * @subpackage Aura_dual_engine/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Aura_dual_engine
 * @subpackage Aura_dual_engine/admin
 * @author     Paul Taylor <paul@auracreativemedia.co.uk>
 */
class Aura_dual_engine_Admin {

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
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Aura_dual_engine_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Aura_dual_engine_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/aura_dual_engine-admin.css', array(), $this->version, 'all' );

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
		 * defined in Aura_dual_engine_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Aura_dual_engine_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/aura_dual_engine-admin.js', array( 'jquery' ), $this->version, false );

	}


	/**
	 * Check Required Plugins - Our main Aura Supercommerce plugin needs to first be enabled. If not, output an admin notice as to inform the end user.
	 *
	 * @since    1.0.0
	 */


	public function check_required_plugins() {
		
		include_once(ABSPATH.'wp-admin/includes/plugin.php');
		
		if (!function_exists('is_plugin_active') || !is_plugin_active('aura-supercommerce/aura-supercommerce.php')) { 

			do_action( 'plugin_activate_admin_notice__fail' );
		    deactivate_plugins( '/aura-dual-engine/aura-dual-engine.php' );
		   
		}

		else {
			remove_action( 'admin_notices', array( $this, 'plugin_activate_admin_notice__fail' ) );
		}

	}

	/**
	 * The notice shown when the required plugin(s) are/is not enabled.
	 *
	 * @since    1.0.0
	 */


	public function plugin_activate_admin_notice__fail() {
		$class = 'notice notice-error';
		$message = __( 'Aura SuperCommerce must be activated to enable this plugin', 'aura_dual_engine' );

		 printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) ); 

	}


	/**
	 * Setup our admin page and a template for displaying admin HTML
	 *
	 * @since    1.0.0
	 */

	public function plugin_setup_menu_backend(){

		if ( class_exists('Aura_Supercommerce_Admin') ) {
		
			$aura_admin_functions = new Aura_Supercommerce_Admin( $this->plugin_name, $this->version );

			$show_admin_screens = false; 

			if($aura_admin_functions->check_current_is_privileged_user()) : $show_admin_screens = true; endif;

			if ( $show_admin_screens ) {
			
		    	add_submenu_page( 'aura-supercommerce', 'Dual Engine', 'Dual Engine', 'manage_options', 'aura-dual-engine', array( $this, 'admin_page_display' ), 999 );
	 		}
	 	}
	}
	
	 
	public function admin_page_display(){ 
	    require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/aura_dual_engine-admin-display.php';
	}


	/**
	 * Creates the Admin page for this child plugin, I should probably portion this out into templates.
	 *
	 * @since    1.0.0
	 * @see  <input value='ade_rebuild_snippets'>
	 */


	public function admin_display_content(){

		if ( class_exists('Aura_Supercommerce_Admin') ) {

			$aura_admin_functions = new Aura_Supercommerce_Admin( $this->plugin_name, $this->version );

			$show_admin_screens = false; 

			if($aura_admin_functions->check_current_is_privileged_user()) : $show_admin_screens = true; endif;


			if ( $show_admin_screens ) {

				?>
				<div class="forms-wrap">
				<form class="aura-form basic operations" action="<?php echo admin_url('admin-post.php'); ?>" method='post'>
					
					<input type='hidden' name='action' value='ade_refresh_snippets'>
						<i class="fa-refresh fa"></i>
					<input type='submit' value='Refresh Snippets' onclick="return confirm('This will scan again for New snippets, any new snippets will be turned on however the rest of the snippets statuses will be left alone');">
				</form>

				<form id="snippet-ops" class="aura-form basic operations" action="<?php echo admin_url('admin-post.php'); ?>" method='post'>
					<i class="fa-puzzle-piece fa"></i>
					<input type='hidden' name='action' value='ade_rebuild_snippets'>
					<input type='submit' value='Rebuild Snippets' onclick="return confirm('This will scan again for New snippets and switch all existing snippets to OFF');">
				</form>
			</div>

				<?php

				$aura_custom_functions = new Aura_dual_engine_Custom( $this->plugin_name, $this->version, AURA_DUAL_ENGINE_SLUG );
				echo $aura_custom_functions->output_snippet_switches();
			}
		}

		


	}

	/**
	 * This function runs when WordPress completes its upgrade process
	 * It iterates through each plugin updated to see if ours is included
	 *
	 * @param $upgrader_object Array
	 * @param $options Array
	 * @see  _refresh_snippets
	 * @see https://codex.wordpress.org/Plugin_API/Action_Reference/upgrader_process_complete
	 */

	public function aura_upgrade_completed( $upgrader_object, $options ) {

	 // The path to our plugin's main file
	// $our_plugin = plugin_basename( __FILE__ );
 	 $dir = AURA_DUAL_ENGINE_DIR;
	 // If an update has taken place and the updated type is plugins and the plugins element exists
 	 // Set a transient to record that our plugin has just been updated
 	 set_transient( 'aura_dual_engine_updated', 1 );

	 if( $options['action'] == 'update' && $options['type'] == 'plugin' && isset( $options['plugins'] ) ) {

	  // Iterate through the plugins being updated and check if ours is there
	  foreach( $options['plugins'] as $plugin ) {

	   if( $plugin == $dir ) {

	   	$aura_de_custom = new Aura_dual_engine_Custom( 'aura-dual-engine', '1.0.0', AURA_DUAL_ENGINE_SLUG );
	   	$aura_de_custom->ade_refresh_snippets();

	    

	   }

	  }

	  delete_transient( 'aura_dual_engine_updated' );

	 }

	}
	


}





