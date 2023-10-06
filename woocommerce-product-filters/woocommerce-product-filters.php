<?php
/**
 * The main plugin file for WooCommerce Product Filters
 *
 * This file is included during the WordPress bootstrap process if the plugin is active.
 *
 * @package   Barn2\woocommerce-product-filters
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 *
 * @wordpress-plugin
 * Plugin Name:     WooCommerce Product Filters
 * Plugin URI:      https://barn2.com/wordpress-plugins/woocommerce-product-filters/
 * Description:     Help customers to find what they want quickly and easily. Add product filters for price, color, category, size, attributes, and more.
 * Version:         1.3.1
 * Author:          Barn2 Plugins
 * Author URI:      https://barn2.com
 * Text Domain:     woocommerce-product-filters
 * Domain Path:     /languages
 *
 * WC requires at least: 3.7
 * WC tested up to: 7.3
 *
 * Copyright:       Barn2 Media Ltd
 * License:         GNU General Public License v3.0
 * License URI:     http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Barn2\Plugin\WC_Filters;

defined( 'ABSPATH' ) || exit;

const PLUGIN_VERSION = '1.3.1';
const PLUGIN_FILE    = __FILE__;

// Include autoloader.
require_once __DIR__ . '/vendor/autoload.php';

/**
 * Helper function to access the shared plugin instance.
 *
 * @return Plugin
 */
function wcf() {
	return Plugin_Factory::create( PLUGIN_FILE, PLUGIN_VERSION );
}

wcf()->register();
