<?php

/**
 *
 * @link              httpss://auracreativemedia.co.uk
 * @since             1.0.0
 * @package           Aura_Supercommerce
 *
 * @wordpress-plugin
 * Plugin Name:       Creative Commerce
 * Plugin URI:        httpss://digitalzest.co.uk/
 * Description:       Plugin Management and Hub for Aura Products
 * Version:           1.0.1
 * Author:            Digital Zest
 * Author URI:        httpss://digitalzest.co.uk/
 * License:           GPL-2.0+
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       aura-supercommerce
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * CONSTANTS
 * 
 * AURA_SUPERCOMMERCE_SLUG is used for many snippet related functions and exists on all Aura products though individual instances/values vary. 
 * AURA_SUPERCOMMERCE_PLUGINS is used by Aura_Supercommerce_Admin::get_activated_plugin_html() to show the various Aura products to the end user and list_active_plugins() to compare Aura plugins and other plugins which are unrelated.
 * AURA_SUPERCOMMERCE_PLUGINS -> id, slug, image_URL - this relates to the ID and fields found from the Aura Licence Issuing Website
*
*
* @FLAG : Migration, migrate
*/


define( 'AURA_SUPERCOMMERCE_VER', '1.0.1' );
define( 'AURA_SUPERCOMMERCE_SLUG', 'aura-supercommerce' );
define( 'AURA_SUPERCOMMERCE_PLUGINS', 
	array( 
		'aura-supercommerce' => array(
			'id' => 2,
			'title' => 'Foundation',
			'slug' => 'aura-supercommerce',
			'image_URL' => 'https://superdev.colourcreation.co.uk/wp-content/plugins/aura-supercommerce/admin/partials/images/dz-cc-place.jpg',
			'dependencies' => ""
		),  
		'aura-dual-engine' => array(
			'id' => 3,
			'title' => 'Dual Engine',
			'slug' => 'aura-dual-engine',
			'image_URL' => 'https://superdev.colourcreation.co.uk/wp-content/plugins/aura-supercommerce/admin/partials/images/dz-cc-place.jpg',
			'dependencies' => array('fma-additional-registration-attributes', 'woocommerce-memberships', 'woocommerce-role-based-methods', 'woocommerce-table-rate-shipping', 'woocommerce-product-bundles')
		),
		'aura-time-saver' => array(
			'id' => 4,
			'title' => 'Time Saver Tech',
			'slug' => 'aura-time-saver',
			'image_URL' => 'https://superdev.colourcreation.co.uk/wp-content/plugins/aura-supercommerce/admin/partials/images/dz-cc-place.jpg',
			'dependencies' => array('woocommerce-xero', 'stock-locations-for-woocommerce', 'yith-woocommerce-barcodes-premium')
		),
		'aura-publicity' => array(
			'id' => 5,
			'title' => 'Publicity Machine',
			'slug' => 'aura-publicity',
			'image_URL' => 'https://superdev.colourcreation.co.uk/wp-content/plugins/aura-supercommerce/admin/partials/images/dz-cc-place.jpg',
			'dependencies' => ""
		),
		'aura-conversion' => array(
			'id' => 6,
			'title' => 'Conversion',
			'slug' => 'aura-conversion',
			'image_URL' => 'https://superdev.colourcreation.co.uk/wp-content/plugins/aura-supercommerce/admin/partials/images/dz-cc-place.jpg',
			'dependencies' => array('automatewoo')
		),
		'aura-trade-booster' => array(
			'id' => 7,
			'title' => 'Trade Booster',
			'slug' => 'aura-trade-booster',
			'image_URL' => 'https://superdev.colourcreation.co.uk/wp-content/plugins/aura-supercommerce/admin/partials/images/dz-cc-place.jpg',
			'dependencies' => array('wp-store-locator', 'woocommerce-advanced-notifications', 'woocommerce-shipping-multiple-addresses')
		),
		'aura-agent' => array(
			'id' => 8,
			'title' => 'Agent Perfection',
			'slug' => 'aura-agent',
			'image_URL' => 'https://superdev.colourcreation.co.uk/wp-content/plugins/aura-supercommerce/admin/partials/images/dz-cc-place.jpg',
			'dependencies' => array('woo-agent-order-management')
		),
		'aura-stock-maximiser' => array(
			'id' => 9,
			'title' => 'Stockist Maximiser',
			'slug' => 'aura-stock-maximiser',
			'image_URL' => 'https://superdev.colourcreation.co.uk/wp-content/plugins/aura-supercommerce/admin/partials/images/dz-cc-place.jpg',
			'dependencies' => ""
		),
	) 
);



/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-plugin-name-activator.php
 */


function activate_aura_supercommerce() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-aura-supercommerce-activator.php';
	Aura_Supercommerce_Activator::activate();
	Aura_Supercommerce_Activator::db_install();
	Aura_Supercommerce_Activator::db_install_data();

	Aura_Supercommerce_Activator::check_plugin_upgrade();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-plugin-name-deactivator.php
 */
function deactivate_aura_supercommerce() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-aura-supercommerce-deactivator.php';
	Aura_Supercommerce_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_aura_supercommerce' );
register_deactivation_hook( __FILE__, 'deactivate_aura_supercommerce' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
 require plugin_dir_path( __FILE__ ) . 'includes/class-aura-supercommerce.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_aura_supercommerce() {

	$plugin = new Aura_Supercommerce();
	$plugin->run();

}



run_aura_supercommerce();