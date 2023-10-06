<?php

/**
 * Fired during plugin activation
 *
 * @link       https://digitalzest.co.uk
 * @since      1.0.0
 *
 * @package    Dz_Woo_Tpt
 * @subpackage Dz_Woo_Tpt/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Dz_Woo_Tpt
 * @subpackage Dz_Woo_Tpt/includes
 * @author     Paul Taylor <hello@digitalzest.co.uk>
 */
class Dz_Woo_Tpt_Activator {


	public static function activate() {
		
			global $wpdb;
			$table_name = $wpdb->prefix . 'dz_customer_top_types';
			$charset_collate = $wpdb->get_charset_collate();
			
			$sql = "CREATE TABLE $table_name (
				ID bigint(20) NOT NULL AUTO_INCREMENT,
				Product_ID bigint(20) NOT NULL,
				User_ID bigint(20) NOT NULL,
				Amount_Sold mediumint(9) NOT NULL,
				UNIQUE (User_ID, Product_ID),
				PRIMARY KEY  (ID)
			) $charset_collate;";
			
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			dbDelta( $sql );

	}
	

}
