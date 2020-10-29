<?php

/**
 * Fired during plugin deactivation
 *
 * @link       https://auracreativemedia.co.uk
 * @since      1.0.0
 *
 * @package    Aura_time_saver
 * @subpackage Aura_time_saver/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    Aura_time_saver
 * @subpackage Aura_time_saver/includes
 * @author     Paul Taylor <paul@auracreativemedia.co.uk>
 */
class Aura_time_saver_Deactivator {

	/**
	 *  Delete Options and Transients
	 *
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {

		global $wpdb;
		global $aura_supercommerce_db_ver;

		delete_option("aura_time_saver_snippets");
	}

}
