<?php

/**
 * Fired during plugin activation
 *
 * @link       https://auracreativemedia.co.uk/
 * @since      1.0.0
 *
 * @package    Aura_Trade_Booster
 * @subpackage Aura_Trade_Booster/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Aura_Trade_Booster
 * @subpackage Aura_Trade_Booster/includes
 * @author     Paul Taylor <info@auracreativemedia.co.uk>
 */
class Aura_Trade_Booster_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {

		$slug = AURA_TRADE_BOOSTER_SLUG;

		add_option( $slug . '_snippets', '' );
	}

}
