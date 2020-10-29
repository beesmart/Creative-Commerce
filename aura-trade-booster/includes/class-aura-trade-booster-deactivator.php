<?php

/**
 * Fired during plugin deactivation
 *
 * @link       https://auracreativemedia.co.uk/
 * @since      1.0.0
 *
 * @package    Aura_Trade_Booster
 * @subpackage Aura_Trade_Booster/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    Aura_Trade_Booster
 * @subpackage Aura_Trade_Booster/includes
 * @author     Paul Taylor <info@auracreativemedia.co.uk>
 */
class Aura_Trade_Booster_Deactivator {

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

		delete_option("aura_trade_booster_snippets");
	}

}
