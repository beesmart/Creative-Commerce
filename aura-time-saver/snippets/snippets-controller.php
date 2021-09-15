<?php

/**
 * The custom-specific functionality of the plugin.
 *
 *
 * @link       https://auracreativemedia.co.uk
 * @since      1.0.0
 *
 * @package    Aura_time_saver
 * @subpackage Aura_time_saver/includes
 */

/**
 * The custom-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 *
 * @since             1.0.0
 * @package    Aura_time_saver
 * @subpackage Aura_time_saver/includes
 */




class Aura_time_saver_Custom {

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
	 * The Slug of this plugin, relates to the main plugin SLUG.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $slug    The slug of this plugin.
	 */
	private $slug;


	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 * @param      string    $slug    The slug of this plugin.
	 */
	public function __construct( $plugin_name="aura-time-saver", $version="1.0.0", $slug=AURA_TIME_SAVER_SLUG ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->slug = $slug;

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

		//wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/plugin-name-admin.css', array(), $this->version, 'all' );

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

		//wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/plugin-name-admin.js', array( 'jquery' ), $this->version, false );

	}

	/**
	 * This is a function which calls it's parent in aura-supercommerce\snippets-controller.php. There you can find more details for what this function does. We included this because it makes a clearer definition between the 2 plugins and stops the snippets cross contaminating.
	 *
	 * @since 1.0.0
	 * @see aura-supercommerce\snippets-controller.php
	 */

	public function check_existing_snippets_callback() {

		if (class_exists('Aura_Supercommerce_Custom')) :

		$aura_admin_functions = new Aura_Supercommerce_Custom( $this->plugin_name, $this->version, AURA_TIME_SAVER_SLUG );

		$aura_admin_functions->check_existing_snippets(plugin_dir_path(dirname(__FILE__)));

		endif;

	}

	/**
	 * Display switches for our priv. admins to turn snippets on and off. This is included - rather than calling the parent aura-supercommerces class method, as the ats_ prefix stops the plugins cross contaminating.
	 *
	 * @since 1.0.0
	 * @used-by $this::snippet_form_submission() - this handles the form submission
	 * @see - snippet_form_submit AND associated action - ats_snippet_form_submit
	 * @see   <input name="ats_snippets[]" />
	 * @FLAG - Migration, migrate
	 */

	public function output_snippet_switches(){



		$existing_snippets = get_option( $this->slug . "_snippets" );

		$snippet_add_meta_form_nonce = wp_create_nonce( 'snippet_add_meta_form_nonce' ); 

		if ($existing_snippets) : ?>

			<form class="aura-form basic" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="GET">
				<div class="selectAll-container"><input type="checkbox" id="aura-snipppets-selectAll" value="selectAll"> Select / Deselect All</div>

			<?php
			foreach ($existing_snippets as $value) { 

				// Grab the Plugin headers from the Snippet core file for some additional data
				$default_headers = array(
				    'Snippet Name' => 'Snippet Name',
				    'Version' => 'Version',
				    'Description' => 'Description'
				    );

				$file_path = dirname(__file__) . '/' . $value->name;
				$file_data = get_file_data($file_path . '/' . $value->name . '.php', $default_headers);

			?>
				
			
			<div class="snippet-switch row__switch">
				<div class="col__switch">
					<label class="container" for="<?php echo $value->name; ?>" ><?php echo $file_data['Snippet Name']; ?>
					<input type="checkbox" id="<?php echo $value->name; ?>" name="ats_snippets[]" value="<?php echo $value->name; ?>" <?php checked( 1 == $value->status ); ?>><span class="checkmark"></span>
					  </label>
				</div>
				<div class="col__switch">
					<strong>Description:</strong> <p><?php echo $file_data['Description']; ?></p>
					<h5>File Path: <?php echo $file_path; ?></h5>
				</div>
				<div class="col__switch">
					<strong>Version:</strong> <?php echo $file_data['Version']; ?>
				</div>
			</div>

	

		<?php } ?>

			<input type="hidden" name="action" value="ats_snippet_form_submit">
			<input type="submit" value="Submit">
			</form>

		<?php

		else : return false;
		endif;

	}


	/**
	 * handles the submission event for the snippet switches form. This is included - rather than calling the parent aura-supercommerces class method, as the ats_ prefix stops the plugins cross contaminating.
	 *
	 * @since 1.0.0
	 * @FLAG - Migration, migrate
	 * @property wp_redirect - admin.php?page=aura_time_saver
	 * 
	 */

	public function ats_snippet_form_submission(){
		
		$aura_admin_functions = new Aura_Supercommerce_Custom( $this->plugin_name, $this->version, AURA_TIME_SAVER_SLUG );

		$submitted_snippets = false;
		// sanitize the input
		if (isset($_GET['ats_snippets'])) {
			$submitted_snippets = $_GET['ats_snippets'];
		}

		$update_snippets = array();


		$dirs = $aura_admin_functions->scan_dir_snippet_folders( plugin_dir_path(dirname(__FILE__)) );

		$existing_snippets = get_option( $this->slug . "_snippets" );
	

		foreach ($dirs as $ex_snippet) {
		
			$found_snippet = false;
			
			if($submitted_snippets) :

				foreach ($submitted_snippets as $sub_snippet) {
		
					if($sub_snippet === $ex_snippet) : $found_snippet = true; endif;

				}

			endif;

			if ($found_snippet === true) {

				$update_snippets[] = (object) [
						"name" => $ex_snippet,
						"status" => 1
				];

			} else {
				$update_snippets[] = (object) [
						"name" => $ex_snippet,
						"status" => 0
				];
			}

	
		}


		// do the processing
		update_option( $this->slug . "_snippets", $update_snippets);

		// redirect the user to the appropriate page
		wp_redirect(admin_url('admin.php?page=aura_time_saver'));

		exit;	

	}


	/**
	 * We call this when we want to REFRESH our snippets option value. Useful for when a new snippet is added or removed from the snippets/ dir.
	 *
	 * @since 1.0.0
	 * @FLAG : Migration, migrate
	 * @property wp_redirect - admin.php?page=aura-supercommerce
	 *
	*/

	public function ats_refresh_snippets() {

		$aura_admin_functions = new Aura_Supercommerce_Custom( $this->plugin_name, $this->version, AURA_TIME_SAVER_SLUG );

		$aura_admin_functions->check_existing_snippets(plugin_dir_path(dirname(__FILE__)), FALSE, TRUE);
		$existing_snippets = get_option( $this->slug . "_snippets" );

		wp_redirect(admin_url('admin.php?page=aura_time_saver'));

	}


	/**
	 * We call this when we want to refresh our snippets option value. Useful for when a new snippet is added or removed from the snippets/ dir. Also switches all snippets to OFF.
	 *
	 * @since    1.0.0
	 * @FLAG - Migration, migrate
	 * @property wp_redirect - admin.php?page=aura_time_saver
	 * @property AURA_TIME_SAVER_SLUG
	 */


	public function ats_rebuild_snippets() {

		$aura_admin_functions = new Aura_Supercommerce_Custom( $this->plugin_name, $this->version, AURA_TIME_SAVER_SLUG );

		$aura_admin_functions->check_existing_snippets(plugin_dir_path(dirname(__FILE__)), TRUE);

		wp_redirect(admin_url('admin.php?page=aura_time_saver'));

	}


	/**
	 * This function first checks if a snippet is switched ON, if so, run the associated snippet file.
	 *
	 * It's essential snippet folder name and snippet main file match. (e.g. /snippet-example/snippet-example.php)
	 *
	 * @since 1.0.0
	 * @property AURA_TIME_SAVER_SLUG
	 * 
	*/
	

	public function run_snippets() {



		if ( class_exists('Aura_Supercommerce_Custom') ) {

		  	$aura_admin_functions = new Aura_Supercommerce_Custom( $this->plugin_name, $this->version, AURA_TIME_SAVER_SLUG );
		  	$dirs = $aura_admin_functions->scan_dir_snippet_folders( plugin_dir_path(dirname(__FILE__)) );

		  	foreach ($dirs as $snippet) {
		  		
		  		$status = $aura_admin_functions->check_snippet_status( $snippet );

		  		if ($status == 1) : 

		  		   require_once plugin_dir_path( dirname( __FILE__ ) ) . 'snippets/' . $snippet . '/' . $snippet . '.php';

		  		endif;

		  		
		  	}
		  	
		  }

	}





}



