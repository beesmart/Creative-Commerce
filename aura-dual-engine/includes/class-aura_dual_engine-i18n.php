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
 * @package    Aura_dual_engine
 * @subpackage Aura_dual_engine/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Aura_dual_engine
 * @subpackage Aura_dual_engine/includes
 * @author     Paul Taylor <paul@auracreativemedia.co.uk>
 */
class Aura_dual_engine_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'aura_dual_engine',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



}
