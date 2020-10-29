<?php

/**
 * Fired during plugin activation
 *
 * @link       https://auracreativemedia.co.uk
 * @since      1.0.0
 *
 * @package    Aura_time_saver
 * @subpackage Aura_time_saver/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Aura_time_saver
 * @subpackage Aura_time_saver/includes
 * @author     Paul Taylor <paul@auracreativemedia.co.uk>
 */
class Aura_time_saver_Activator {

	/**
	 * Run on activate, setup snippets option and db version.
	 *
	 * @since    1.0.0
	 *
	 */
	
	public static function activate() {

		$slug = AURA_TIME_SAVER_SLUG;

		add_option( $slug . '_snippets', '' );

	}

}
