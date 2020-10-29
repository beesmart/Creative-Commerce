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

		/**
		 * Run on activate, setup snippets option and db version.
		 *
		 * @since    1.0.0
		 *
		 */

		global $aura_supercommerce_db_ver;
		$aura_supercommerce_db_ver = '1.0';

		$slug = AURA_SUPERCOMMERCE_SLUG;

		add_option( $slug . '_snippets', '' );
	}




	public static function db_install() {

		/**
		 * This function is currently unused but added as a future possibility
		 *
		 * @since    1.0.0
		 *
		 */


		global $wpdb;
		global $aura_supercommerce_db_ver;


		// USE THIS TO RUN EXISTING UPDATES ON PLUGINS
		$installed_ver = get_option( "aura_supercommerce_db_ver" );


		add_option( 'aura_supercommerce_db_ver', $aura_supercommerce_db_ver );

		// USE THIS TO RUN UPDATES ON EXISITING PLUGINS
		if ( $installed_ver != $aura_supercommerce_db_ver ) {

			update_option( "aura_supercommerce_db_ver", $aura_supercommerce_db_ver );

		} 



	}


	public static function db_install_data() {
	
		/**
		 * This function is currently unused but added as a future possibility
		 *
		 * @since    1.0.0
		 *
		 */
	}
	

	public static function myplugin_update_db_check() {

		/**
		 * This function is currently unused but added as a future possibility
		 *
		 * @since    1.0.0
		 *
		 */

	    global $aura_supercommerce_db_ver;
	    if ( get_site_option( 'aura_supercommerce_db_ver' ) != $aura_supercommerce_db_ver ) {
	        db_install();
	       
	    }
	}

	public static function check_plugin_upgrade() {

		/**
		 * This function is currently unused but added as a future possibility
		 *
		 * @since    1.0.0
		 *
		 */

	    add_action( 'plugins_loaded', 'myplugin_update_db_check' );
	}

			

}



