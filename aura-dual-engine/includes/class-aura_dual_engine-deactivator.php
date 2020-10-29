<?php

/**
 * Fired during plugin deactivation
 *
 * @link       https://auracreativemedia.co.uk
 * @since      1.0.0
 *
 * @package    Aura_dual_engine
 * @subpackage Aura_dual_engine/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    Aura_dual_engine
 * @subpackage Aura_dual_engine/includes
 * @author     Paul Taylor <paul@auracreativemedia.co.uk>
 */
class Aura_dual_engine_Deactivator {

	/**
	 *  Delete Options and Transients
	 *
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {

		global $wpdb;
		global $aura_supercommerce_db_ver;

		delete_option("aura-dual-engine_snippets");
	}

}
