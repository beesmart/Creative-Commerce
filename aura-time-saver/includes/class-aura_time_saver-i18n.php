<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://auracreativemedia.co.uk
 * @since      1.0.0
 *
 * @package    Aura_time_saver
 * @subpackage Aura_time_saver/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Aura_time_saver
 * @subpackage Aura_time_saver/includes
 * @author     Paul Taylor <paul@auracreativemedia.co.uk>
 */
class Aura_time_saver_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'aura_time_saver',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



}
