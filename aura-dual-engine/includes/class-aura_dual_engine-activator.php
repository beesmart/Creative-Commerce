<?php

/**
 * Fired during plugin activation
 *
 * @link       https://auracreativemedia.co.uk
 * @since      1.0.0
 *
 * @package    Aura_dual_engine
 * @subpackage Aura_dual_engine/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Aura_dual_engine
 * @subpackage Aura_dual_engine/includes
 * @author     Paul Taylor <paul@auracreativemedia.co.uk>
 */
class Aura_dual_engine_Activator {

	/**
	 * Run on activate, setup snippets option and db version.
	 *
	 * @since    1.0.0
	 *
	 */
	
	public static function activate() {

		$slug = AURA_DUAL_ENGINE_SLUG;

		add_option( $slug . '_snippets', '' );

	}

}
