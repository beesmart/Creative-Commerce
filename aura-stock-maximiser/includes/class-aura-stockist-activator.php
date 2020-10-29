<?php

/**
 * Fired during plugin activation
 *
 * @link       https://digitalzest.co.uk
 * @since      1.0.0
 *
 * @package    Aura_Stockist
 * @subpackage Aura_Stockist/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Aura_Stockist
 * @subpackage Aura_Stockist/includes
 * @author     Digital Zest <info@digitalzest.co.uk>
 */
class Aura_Stockist_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {

		$slug = AURA_STOCKIST_SLUG;

		add_option( $slug . '_snippets', '' );
	}	

}
