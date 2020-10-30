<?php

/**
 * The custom-specific functionality of the plugin.
 *
 *
 * @link              https://auracreativemedia.co.uk
 * @since             1.0.0
 * @package           Aura_Supercommerce
 * @subpackage 	      Aura_Supercommerce/snippets
 */


 
class Aura_Supercommerce_Custom {

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
	public function __construct( $plugin_name, $version, $slug ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->slug = $slug;
	}


	/**
	 * The snippets live in sub-folders, using this function we scan the /snippets/ dir for the snippet folders, if they exist we add them to an array then return the completed array. It's essential snippet folder name and snippet main file match. (e.g. /snippet-example/snippet-example.php)
	 *
	 * @param @this_dir - This was included because the function is/can be used by other plugins and it allows us to be sure we're looking into the correct plugin directory
	 * @return array - the snippet folder names
	 */


	public function scan_dir_snippet_folders( $this_dir ){

		// SCAN FOR SNIPPETS
		$snippets_dir = $this_dir . 'snippets'; 

		$dirs = array();

		// directory handle
		$dir = dir($snippets_dir);

		while (false !== ($entry = $dir->read())) {
		    if ($entry != '.' && $entry != '..') {
		       if (is_dir($snippets_dir . '/' .$entry)) {
		            $dirs[] = $entry; 
		       }
		    }
		}

		return $dirs;
	} 


	/**
	 * Checks the database option value for the snippets, checks the queried snippets status value.
	 *
	 * @param @dir_snippet - The queried snippet to check
	 * @return bool - returns true if the snippet is switched
	 */


	public function check_snippet_status( $dir_snippet ) {

		$existing_snippets = get_option( $this->slug . "_snippets" );

		if ($existing_snippets) : 

			foreach ($existing_snippets as $value) {

				$found_snippet = false;

				if ( $value->name === $dir_snippet ) {

					$found_snippet = $value->status;

					return $found_snippet;
				}

			}

		endif;
	}


	/**
	 * The snippets live in sub-folders, using this function we scan the /snippets/ dir for the snippet folders. If no snippets already exist, add all snippets found in the directory to the database option value and set status to off (int 0).
	 *
	 * If the dB option value exists we don't run. If the Rebuild flag is set to TRUE, then update the option value as if we were running on an empty option value.
	 * It's essential snippet folder name and snippet main file match. (e.g. /snippet-example/snippet-example.php)
	 *
	 * @since 1.0.0
	 * @param @this_dir - This was included because the function is/can be used by other plugins and it allows us to be sure we're looking into the correct plugin directory
	 * @param @rebuild - default set to FALSE. Will force the snippets scan and build to happen even if data already exists there.
	 */


	public function check_existing_snippets($this_dir = "", $rebuild = FALSE, $refresh = FALSE){

		if (!$this_dir) : $this_dir = plugin_dir_path(dirname(__FILE__)); endif;

		

		$existing_snippets = get_option( $this->slug . "_snippets" );
		$update_snippet_option = $existing_snippets;

		$dirs = $this::scan_dir_snippet_folders( $this_dir );

		// This fires on a button press, the purpose = if we want to empty the snippet record and rebuild, posibly for debugging or to clear out to assist a migration.
		if ( !$existing_snippets || ($rebuild === TRUE) ) :

			// IF SNIPPETS DONT EXIST IN DB
			// SCAN DIR
			// UPDATE DB ARRAY - SWITCH ALL off
			// OUTPUT SWITCHES BASED ON DB DATA

			
			$db_snippets = array();
			
			foreach ($dirs as $snippet) {
				// write snippet to an array with a value for switched OFF

				$status = 0;

				if ($refresh === TRUE) : $status = 1; endif;

				$db_snippets[] = (object) [
					"name" => $snippet,
					"status" => $status
				];

			}
	
			update_option( $this->slug . "_snippets", $db_snippets);
		
		// This fires on Update and also button press. The purpose being that we want to either add to the existing snippets record. This is useful if we update the plugin with some new snippets, users should automatically get those snippets and have them switched on.
		elseif ($existing_snippets && $refresh === TRUE) :

			foreach ($dirs as $snippet_name) {

				$snippet_found = false;
		
				foreach ($existing_snippets as $snippet_db) {
	
					if ($snippet_name === $snippet_db->name) : $snippet_found = true; endif;

				}

				if ($snippet_found === false) : 

					$db_snippet = (object) [
						"name" => $snippet_name,
						"status" => 1
					];

					$update_snippet_option[] =  $db_snippet;
					var_dump($update_snippet_option);

				endif;

			}


			update_option( $this->slug . "_snippets", $update_snippet_option);


		endif;



	}


	/**
	 * Display switches for our priv. admins to turn snippets on and off.
	 *
	 * @since 1.0.0
	 * @used-by $this::snippet_form_submission() - this handles the form submission
	 * @see - snippet_form_submit AND associated action
	 */

	public function output_snippet_switches(){

		$existing_snippets = get_option( $this->slug . "_snippets" );
		
		$snippet_add_meta_form_nonce = wp_create_nonce( 'snippet_add_meta_form_nonce' ); 

		// check our snippets option value in dB Exists
		if ($existing_snippets) : ?>
			
			<form class="aura-form basic" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="GET">
				<div class="selectAll-container"><input type="checkbox" id="aura-snipppets-selectAll" value="selectAll"> Select / Deselect All</div>

			<?php
			foreach ($existing_snippets as $value) { 

				// Grab the Plugin headers from the Snippet core file for some additional data
				$default_headers = array(
				    'Snippet Name' => 'Snippet Name',
				    'Version' => 'Version',
				    'Description' => 'Description',
				    'Dependency' => 'Dependency'
				    );

				$file_path = dirname(__file__) . '/' . $value->name;

				if ($file_path && file_exists($file_path . '/' . $value->name . '.php')) :

					$file_data = get_file_data($file_path . '/' . $value->name . '.php', $default_headers);
			?>
			
					<div class="snippet-switch row__switch">
						<div class="col__switch snippet-title">
							<label class="container" for="<?php echo $value->name; ?>" ><?php if ($file_data['Snippet Name']) : echo $file_data['Snippet Name']; else : echo $value->name; endif; ?>
							<input type="checkbox" id="<?php echo $value->name; ?>" name="snippets[]" value="<?php echo $value->name; ?>" <?php checked( 1 == $value->status ); ?>><span class="checkmark"></span>
							  </label>
						</div>
						<div class="col__switch snippet-desc">
							<strong>Description:</strong> <p><?php echo $file_data['Description']; ?></p>
							<?php if ($file_data['Dependency']) : echo '<p>Dependency: ' . $file_data["Dependency"] . '</p>'; endif; ?>
							<h5><span>File Path:</span> <?php echo $file_path; ?></h5>
						</div>
						<div class="col__switch snippet-version">
							<strong>Version:</strong> <?php echo $file_data['Version']; ?>
						</div>
					</div>

		<?php endif; } ?>

			<input type="hidden" name="action" value="snippet_form_submit">
			<input type="submit" value="Submit">
			</form>

		<?php

		else : return false;
		endif;

	}


	/**
	 * Handles the submission event for the snippet switches form
	 *
	 * @since 1.0.0
	 * @property wp_redirect - ?page=aura-supercommerce
	 * 
	 */

	public function snippet_form_submission(){
		

		$submitted_snippets = false;
		$update_snippets = array();

		// sanitize the input
		if (isset($_GET['snippets'])) {
			$submitted_snippets = $_GET['snippets'];
		}

		// grab a record of current directory snippets
		$dirs = $this::scan_dir_snippet_folders( plugin_dir_path(dirname(__FILE__)) );
		// grab a record of snippets and statuses in the database
		$existing_snippets = get_option( $this->slug . "_snippets" );
	
		// loop over directory snippets
		foreach ($dirs as $ex_snippet) {
			
			$found_snippet = false;
			
			if($submitted_snippets) :

				// loop over the forms submitted snippets
				foreach ($submitted_snippets as $sub_snippet) {
					
					// if what we have submitted in the form matches what's in the database we know it needs to be switched ON otherwise switch it OFF
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
		wp_redirect(admin_url('?page=aura-supercommerce'));

		exit;	

	}



	/**
	 * We call this when we want to REBUILD our snippets option value. Useful for when a new snippet is added or removed from the snippets/ dir. Also switches all snippets to OFF
	 *
	 * @since 1.0.0
	 * @FLAG : Migration, migrate
	 * @property wp_redirect - admin.php?page=aura-supercommerce
	 *
	*/

	public function _rebuild_snippets() {

		$this::check_existing_snippets(plugin_dir_path(dirname(__FILE__)), TRUE, FALSE);

		wp_redirect(admin_url('admin.php?page=aura-supercommerce'));

	}



	/**
	 * We call this when we want to REFRESH our snippets option value. Useful for when a new snippet is added or removed from the snippets/ dir.
	 *
	 * @since 1.0.0
	 * @FLAG : Migration, migrate
	 * @property wp_redirect - admin.php?page=aura-supercommerce
	 *
	*/

	public function _refresh_snippets() {

		$this::check_existing_snippets(plugin_dir_path(dirname(__FILE__)), FALSE, TRUE);

		wp_redirect(admin_url('admin.php?page=aura-supercommerce'));

	}


	/**
	 * This function first checks if a snippet is switched ON, if so, run the associated snippet file.
	 *
	 * It's essential snippet folder name and snippet main file match. (e.g. /snippet-example/snippet-example.php)
	 *
	 * @since 1.0.0
	 * 
	*/

	public function run_snippets() {
	
		$dirs = $this::scan_dir_snippet_folders( plugin_dir_path(dirname(__FILE__)) );

		foreach ($dirs as $snippet) {
			
			$status = $this::check_snippet_status( $snippet );

			if ($status == 1) : 

			   require_once plugin_dir_path( dirname( __FILE__ ) ) . 'snippets/' . $snippet . '/' . $snippet . '.php';

			endif;

			
		}
		
	}






}



