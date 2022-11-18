<?php
/**
 * Fired during plugin deactivation
 *
  * @link              https://auracreativemedia.co.uk
  * @since             1.0.0
  * @package           Aura_Supercommerce
  * @subpackage 	   Aura_Supercommerce/includes
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

 
class Aura_Supercommerce_Deactivator {
	/**
	 * Delete Options and Transients
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {
    global $wpdb;
    global $aura_supercommerce_db_ver;
   
    // $table_name_plugins = $wpdb->prefix . 'aurasc_plugins';
    // $wpdb->query( "DROP TABLE IF EXISTS $table_name_plugins" );
    delete_option("aura_supercommerce_db_ver");
    delete_option("aura_supercommerce_snippets");
    delete_transient( 'licence_transient_data' );
	}
}