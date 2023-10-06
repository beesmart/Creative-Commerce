<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://digitalzest.co.uk
 * @since      1.0.0
 *
 * @package    Dz_Woo_Tpt
 * @subpackage Dz_Woo_Tpt/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Dz_Woo_Tpt
 * @subpackage Dz_Woo_Tpt/admin
 * @author     Paul Taylor <hello@digitalzest.co.uk>
 */
class Dz_Woo_Tpt_Admin {

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
		 * defined in Dz_Woo_Tpt_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Dz_Woo_Tpt_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/dz-woo-tpt-admin.css', array(), $this->version, 'all' );

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
		 * defined in Dz_Woo_Tpt_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Dz_Woo_Tpt_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/dz-woo-tpt-admin.js', array( 'jquery' ), $this->version, false );

	}


	/** Register an admin menu page*/
	public function dz_tpt_admin_page(){
		add_menu_page(
			'DZ - Top Product Types', 
			'DZ - Top Product Types', 
			'manage_options',
			'dz-top-product-types', 
			array( $this, 'admin_page_display' ), 
			'' ); 
	}


	public function dz_tpt_submenu_page(){
		add_submenu_page(
			'dz-top-product-types',
			'Tools',
			'Tools',
			'manage_options',
			'dz-top-product-types-tools',
			array( $this, 'admin_submenu_page_display' )
		 );
	}


	public function admin_submenu_page_display(){
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/submenu-display.php';
	}
	

	public function admin_page_display(){ 
	    require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/dz-woo-tpt-admin-display.php';
	}


	public function dz_tpt_settings() {
		register_setting('dz-tpt-settings-group', 'dz_tpt_options', array( $this, 'dz_tpt_sanitize_options' ));
		register_setting('dz-tpt-settings-group', 'dz_tpt_url', 'sanitize_text_field'); // You can use WordPress's sanitize_text_field
	}
	

	public function dz_tpt_sanitize_options($input) {
		// Sanitize and validate the input
		// Return the array of valid options
		return $input;
	}



	public function send_tpt_data_webhook( $order_id ){

		$order = wc_get_order( $order_id );
		$user_id = $order->get_user_id();

		$this->update_db_customer_data( $order_id );
		// Now call webhook to send data
		$this->call_dz_webhook( $user_id );
	}



	public function update_db_customer_data( $order_id ) {

		global $wpdb;  // Declare it as global within your function

		$table_name = $wpdb->prefix . 'dz_customer_top_types';  // Replace with your table name

		$order = wc_get_order( $order_id );
		$user_id = $order->get_user_id();

		// We don't want data from Guests so escape if they aren't logged in
		if ($user_id === 0) {
			return;  // Exit the function early
		}

		foreach ( $order->get_items() as $item_id => $item ) {

			$product_id = $item->get_product_id();
			$quantity = $item->get_quantity();  // Get the quantity for the item


			// Query Tables and Update with the customer ID, product ID and the amount
			
			// Prepare SQL statement
			$sql = $wpdb->prepare(
				"INSERT INTO $table_name (Product_ID, User_ID, Amount_Sold)
				VALUES (%d, %d, %d)
				ON DUPLICATE KEY UPDATE Amount_Sold = Amount_Sold + VALUES(Amount_Sold);",
				$product_id, $user_id, $quantity
			);

			if ($wpdb->query($sql) === false) {
				error_log("Database error: " . $wpdb->last_error);
			}
						
		}

		// Update the user meta on the wp_users table.
		$this->update_db_wp_user_table( $user_id );

	}




	// public function get_db_customer_data( $user_id ){
		
	// 	global $wpdb;  

	// 	$table_name = $wpdb->prefix . 'dz_customer_top_types';  // Replace with your actual table name

	// 	// Prepare SQL query to get top 3 products based on Amount_Sold
	// 	$sql = $wpdb->prepare(
	// 		"SELECT Product_ID FROM $table_name
	// 		WHERE User_ID = %d
	// 		ORDER BY Amount_Sold DESC
	// 		LIMIT 3",
	// 		$user_id
	// 	);

	// 	// Execute the query and get the results
	// 	$results = $wpdb->get_results($sql, ARRAY_A);

	// 	// Initialize an empty array to store the top product IDs
	// 	$top_product_ids = [];

	// 	// Loop through the result set and populate $top_product_ids
	// 	foreach ($results as $row) {
	// 		$top_product_ids[] = $row['Product_ID'];
	// 	}

	// 	// Now $top_product_ids contains the top 3 product IDs for the given user
	// 	return $top_product_ids;

	// }

	

	public function get_top_of_taxons($user_id) {
		global $wpdb;
	
		$table_name = $wpdb->prefix . 'dz_customer_top_types';
	
		$sql = $wpdb->prepare(
			"SELECT Product_ID FROM $table_name
			WHERE User_ID = %d
			ORDER BY Amount_Sold DESC",
			$user_id
		);
	
		$results = $wpdb->get_results($sql, ARRAY_A);
		$taxon_count = [];
		$dz_tpt_options = get_option('dz_tpt_options');
	
		if (!$dz_tpt_options) {
			return false;
		}
	
		foreach ($results as $row) {
			$product_id = $row['Product_ID'];
	
			foreach ($dz_tpt_options as $taxon) {
				$terms = get_the_terms($product_id, $taxon);
				$first_sibling_found = false;  // Flag to track if a parent (and therefore first sibling) has been found yet
	
				if ($terms && !is_wp_error($terms)) {
					foreach ($terms as $term) {
						if ($term->slug === 'uncategorized' || $term->slug === 'trade-only' || $term->slug === 'uncategorised') {
							continue;
						}

						if ($term->parent == 0) {

							if ($first_sibling_found) {
								continue;
							}
	
							if (!isset($taxon_count[$taxon])) {
								$taxon_count[$taxon] = [];
							}
		
							if (isset($taxon_count[$taxon][$term->slug])) {
								$taxon_count[$taxon][$term->slug]++;
							} else {
								$taxon_count[$taxon][$term->slug] = 1;
							}

							$first_sibling_found = true;  // Set flag that we've found the first parent (and therefore first sibling)
						}
					}
				}
			}
		}
	
		$top_taxons = [];
	
		foreach ($dz_tpt_options as $taxon) {
			if (isset($taxon_count[$taxon])) {
				arsort($taxon_count[$taxon]);
				$top_taxons[$taxon] = array_slice(array_keys($taxon_count[$taxon]), 0, 3, true);
			}
		}
	
		return $top_taxons;
	}

	


	public function update_db_wp_user_table( $user_id ){

		$top_taxons = $this->get_top_of_taxons( $user_id );

		update_user_meta( $user_id, 'dz_tpt_taxon_slugs', $top_taxons );

	}


	public function get_db_wp_user_data( $user_id ){

		$top_taxons = get_user_meta( $user_id, 'dz_tpt_taxon_slugs' );

		return $top_taxons;

	}


	public function call_dz_webhook( $user_id ){

		$webhook_url = get_option('dz_tpt_url');

		$user_top_types_unflat = $this->get_db_wp_user_data($user_id);
		$user_top_types = $user_top_types_unflat[0] ?? [];
		
		$user_obj = get_user_by('id', $user_id);
		
		// Initialize an empty $body array
		$body = [
				 'user_id' => $user_id, 
				 'user_email' => $user_obj->user_email
				];
		
		// Define the keys you're interested in
		$keys = ['product_cat', 'type', 'range', 'occasion'];
		
		foreach ($keys as $key) {
			for ($i = 1; $i <= 3; $i++) {
				// If the key exists in $user_top_types and its corresponding index exists
				if (isset($user_top_types[$key][$i-1])) {
					// Add it to $body
					$body["top_{$key}_{$i}"] = $user_top_types[$key][$i-1];
				}
			}
		}

	
		// // Set up the arguments for the POST request
		$args = array(
			'body'        => $body,
			'timeout'     => '5',
			'redirection' => '5',
			'httpversion' => '1.0',
			'blocking'    => true,
			'headers'     => array(),
			'cookies'     => array(),
		);
	
		// Make the POST request
		$response = wp_remote_post( $webhook_url, $args );
	
		// Check for errors
		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
			echo "Something went wrong: $error_message";
		} else {
			echo 'Response:<pre>';
			print_r( $response );
			echo '</pre>';
		}




	}

	public function my_ajax_rebuild_user_data(){

		try {
			// Perform the operation here
			$this->rebuild_user_data();
	
			// Operation was successful, send a success message
			wp_send_json_success('Operation completed successfully.');
	
		} catch (Exception $e) {
			// An error occurred, send an error message
			wp_send_json_error('An error occurred: ' . $e->getMessage());
		}
    
		wp_die(); // All ajax handlers should die when finished
	}


	public function rebuild_user_data(){

		// Fetch orders in batches of 100 to improve performance
		$batch_size = 100;
		$paged = 1;

		global $wpdb;  // Declare it as global within your function

		// We need to rebuild our database so first empty all amount values before we run through every order and get the existing amount values. Without this, the function will append values to old orders meaning the numbers will be wrong.
		$table_name = $wpdb->prefix . 'dz_customer_top_types';  // Replace with your table name
		$sql_reset = "UPDATE $table_name SET Amount_Sold = 0";

		if ($wpdb->query($sql_reset) === false) {
            error_log("Database error while resetting Amount_Sold: " . $wpdb->last_error);
        }

		do {
			// Iterate over all orders
			$args = array(
				'post_type' => 'shop_order',
				'post_status' => array('wc-completed', 'wc-processing'), // Add additional order statuses if needed
				'posts_per_page' => $batch_size,
				'paged' => $paged,
			);
			
			$loop = new WP_Query($args);
			
			if ($loop->have_posts()) {
				while ($loop->have_posts()) {

					$loop->the_post();
					$order_id = get_the_ID();
			
					// Your code to work with each $order object
					$this->update_db_customer_data( $order_id );

				}
			}

			wp_reset_postdata();
			$paged++;

		} while (count($loop->posts) === $batch_size);  // Stop if we have less than $batch_size orders
	}



	public function export_csv() {
		// Create a file header
		$header = array("User ID", "User name", "User email", "product_cat_1", "product_cat_2", "product_cat_3", "product_type_1", "product_type_2", "product_type_3", "product_occasion_1", "product_occasion_2", "product_occasion_3", "product_range_1", "product_range_2", "product_range_3");
		$output = fopen('php://output', 'w');
		fputcsv($output, $header);
	
		// Get all users
		$users = get_users();
	
		// Loop through each user
		foreach ($users as $user) {
			$user_id = $user->ID;
			$user_name = $user->display_name;
			$user_email = $user->user_email;
	
			$meta_data = get_user_meta($user_id, 'dz_tpt_taxon_slugs', true);
	
			$row = [$user_id, $user_name, $user_email];
	
			// Prepare your columns here based on the unserialized data
			foreach(['product_cat', 'type', 'occasion', 'range'] as $taxonomy) {
				for($i = 0; $i < 3; $i++) {
					if(isset($meta_data[$taxonomy][$i])) {
						$row[] = $meta_data[$taxonomy][$i];
					} else {
						$row[] = ''; // Empty cell if not present
					}
				}
			}
	
			fputcsv($output, $row);
		}
	
		fclose($output);
		exit();
	}
	





	 /**
	 * Log and debug functions, useful for tracking down issues, monitoring API calls etc
	 * They write to files in root of plugin, might need to do something about this
	 * Status page reads the files which is ok for now but we need to look into a better solution
	 *
	 * @since    1.0.0
	 * 
	 */

	 public function writeToErrorLog($error){

		$bt = debug_backtrace();
		$caller = array_shift($bt);

		$pluginlog = plugin_dir_path( dirname( __FILE__ ) ) . 'debug.log';

		$message = date('Y-m-d H:i:s') . ' FILE: ' . $caller['file'] . ' - LINE: ' . $caller['line'] . ' - ' . $error . PHP_EOL;

		error_log($message, 3, $pluginlog);

	}


	public function logTransactionsAPI($user_id, $user_top_types){
		
		$pluginlog = plugin_dir_path( dirname( __FILE__ ) ) . 'transaction.log';
		
		$transaction_msg = 'Webhook submitted on User ID #' . $user_id . '. Product IDs: ' . $user_top_types[0] . ', ' . $user_top_types[1] . ', ' . $user_top_types[2];
		$message = date('Y-m-d H:i:s') . ' - ' . $transaction_msg . PHP_EOL;

		error_log($message, 3, $pluginlog);
	
	}


}


	
