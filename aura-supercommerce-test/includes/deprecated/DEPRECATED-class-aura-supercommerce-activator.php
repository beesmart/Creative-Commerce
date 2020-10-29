<?php
/**
 * Fired during plugin activation
 *
 * @link              https://auracreativemedia.co.uk
 * @since             1.0.0
 * @package           Aura_Supercommerce
 * @subpackage 		  Aura_Supercommerce/includes
 */
/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Aura_Supercommerce
 * @subpackage Aura_Supercommerce/includes
 */
class Aura_Supercommerce_Activator {
	/**
	 * 
	 *
	 * @since    1.0.0
	 */
	public static function activate() {

		global $aura_supercommerce_db_ver;
		$aura_supercommerce_db_ver = '1.0';

		$slug = AURA_SUPERCOMMERCE_SLUG;

		add_option( $slug . '_snippets', '' );
	}




	public static function db_install() {
		global $wpdb;
		global $aura_supercommerce_db_ver;


		// USE THIS TO RUN EXISTING UPDATES ON PLUGINS
		$installed_ver = get_option( "aura_supercommerce_db_ver" );

		// $table_name_config = $wpdb->prefix . 'aurasc_config';
		// $table_name_plugins = $wpdb->prefix . 'aurasc_plugins';
		
		// $charset_collate = $wpdb->get_charset_collate();

		// $sql_config = "CREATE TABLE $table_name_config (
		// 	id mediumint(9) NOT NULL AUTO_INCREMENT,
		// 	time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		// 	name tinytext NOT NULL,
		// 	text text NOT NULL,
		// 	url varchar(55) DEFAULT '' NOT NULL,
		// 	PRIMARY KEY  (id)
		// ) $charset_collate;";

		// require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		// dbDelta( $sql_config );

		// $sql_plugin = "CREATE TABLE $table_name_plugins (
		// 	id mediumint(9) NOT NULL AUTO_INCREMENT,
		// 	plugin_id mediumint(9) NOT NULL,
		// 	plugin_name varchar(55) DEFAULT '' NOT NULL,
		// 	plugin_slug varchar(55) DEFAULT '' NOT NULL,
		// 	activated BOOLEAN NOT NULL DEFAULT FALSE,
		// 	PRIMARY KEY  (id)
		// ) $charset_collate;";

		// dbDelta( $sql_plugin );


		add_option( 'aura_supercommerce_db_ver', $aura_supercommerce_db_ver );

		// USE THIS TO RUN UPDATES ON EXISITING PLUGINS
		if ( $installed_ver != $aura_supercommerce_db_ver ) {

			// $table_name_config = $wpdb->prefix . 'aurasc_config';
			// $table_name_plugins = $wpdb->prefix . 'aurasc_plugins';

			// $sql_config = "CREATE TABLE $table_name_config (
			// 	id mediumint(9) NOT NULL AUTO_INCREMENT,
			// 	time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			// 	name tinytext NOT NULL,
			// 	text text NOT NULL,
			// 	url varchar(100) DEFAULT '' NOT NULL,
			// 	PRIMARY KEY  (id)
			// );";

			// require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

			// dbDelta( $sql_config );

			// $sql_plugin = "CREATE TABLE $table_name_plugins (
			// 	id mediumint(9) NOT NULL AUTO_INCREMENT,
			// 	plugin_id Imediumint(9) NOT NULL,
			// 	plugin_name varchar(55) DEFAULT '' NOT NULL,
			// 	plugin_slug varchar(55) DEFAULT '' NOT NULL,
			// 	activated BOOLEAN NOT NULL DEFAULT FALSE,
			// 	PRIMARY KEY  (id)
			// ) $charset_collate;";

			
			// dbDelta( $sql_plugin );

			update_option( "aura_supercommerce_db_ver", $aura_supercommerce_db_ver );

		} 



	}


	public static function db_install_data() {
		// global $wpdb;

		// THIS ALL NEEDS UPDATING

		// Remeber to use
		// $wpdb->show_error();
		// $wpdb->print_error();
		
		// $table_name_config = $wpdb->prefix . 'aurasc_config';
		// $table_name_plugins = $wpdb->prefix . 'aurasc_plugins';
		
		// $url = plugin_dir_path( dirname( __FILE__ ) ) . 'plugins.json';
		// $json = file_get_contents($url);
		// $content = json_decode($json, true);
		// $site_url = get_site_url();

// this needs editing. First match the client based on URL, then if you find a match continue.
		// Grab the values from that same inner array and populate the table plugins


		// foreach ($content as $match) {

		// 	if ($match['client_url'] === $site_url){

		// 		// add admins to options
		// 		$admin_users = $match['assigned_admin'];
		// 		$admin_users_array = explode(', ', $admin_users);

		// 		update_option( "aura_supercommerce_privilege", serialize($admin_users_array) );

		// 		foreach ($match['plugins'] as $plugin) {

		// 			// add plugins to a seperate table
		// 			$wpdb->insert( $table_name_plugins,
		// 			    array( 
		// 			    	'plugin_id' => $plugin['ID'],
		// 					'plugin_name' => $plugin['Name'], 
		// 					'plugin_slug' => $plugin['PluginSlug'], 
		// 					'activated' => $plugin['Activated'],
		// 					'latest_version' => $plugin['LatestVersion'],
		// 					) 
		// 			);

		// 			// add plugins to array on options table
		// 			$aura_supercommerce_plugin_activation[] = array(
		// 			        'plugin_id' => $plugin['ID'],
		// 					'plugin_name' => $plugin['Name'], 
		// 					'plugin_slug' => $plugin['PluginSlug'], 
		// 					'activated' => $plugin['Activated'],
		// 					'latest_version' => $plugin['LatestVersion'],
					        
		// 			);
		// 		}

		// 		update_option( "aura_supercommerce_activation", serialize($aura_supercommerce_plugin_activation) );

		// 	}
			
			
		// }




	
		
	}
	

	public static function myplugin_update_db_check() {
	    global $aura_supercommerce_db_ver;
	    if ( get_site_option( 'aura_supercommerce_db_ver' ) != $aura_supercommerce_db_ver ) {
	        db_install();
	       
	    }
	}

	public static function check_plugin_upgrade() {
	    add_action( 'plugins_loaded', 'myplugin_update_db_check' );
	}

			

	

}



