<?php
/**
 * @package   Barn2\woocommerce-product-filters
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */

namespace Barn2\Plugin\WC_Filters;

/**
 * Factory to create/return the shared plugin instance.
 *
 * @package   Barn2\woocommerce-product-filters
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Plugin_Factory {

	private static $plugin = null;

	/**
	 * Create/return the shared plugin instance.
	 *
	 * @param string $file
	 * @param string $version
	 * @return Plugin
	 */
	public static function create( $file, $version ) {
		if ( null === self::$plugin ) {
			self::$plugin = new Plugin( $file, $version );
		}
		return self::$plugin;
	}

}
