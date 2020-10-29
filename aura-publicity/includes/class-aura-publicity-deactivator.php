<?php

/**
 * Fired during plugin deactivation
 *
 * @link       https://digitalzest.co.uk
 * @since      1.0.0
 *
 * @package    Aura_Publicity
 * @subpackage Aura_Publicity/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    Aura_Publicity
 * @subpackage Aura_Publicity/includes
 * @author     Digital Zest <info@digitalzest.co.uk>
 */
class Aura_Publicity_Deactivator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {

		global $wpdb;
		global $aura_supercommerce_db_ver;

		delete_option("aura_publicity_snippets");
	}

}
