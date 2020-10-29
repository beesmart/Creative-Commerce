<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://digitalzest.co.uk
 * @since      1.0.0
 *
 * @package    Aura_Stockist
 * @subpackage Aura_Stockist/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Aura_Stockist
 * @subpackage Aura_Stockist/includes
 * @author     Digital Zest <info@digitalzest.co.uk>
 */
class Aura_Stockist_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'aura-stockist',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



}
