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
 * Version:           1.4.3
 * Author:            Digital Zest
 * Author URI:        httpss://digitalzest.co.uk/
 * License:           GPL-2.0+
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       aura-supercommerce
 * Tested up to:      5.8
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}


define( 'AURA_SUPERCOMMERCE_VER', '1.4.3' );
define( 'AURA_SUPERCOMMERCE_DIR', 'aura-supercommerce/aura-supercommerce.php' );
define( 'AURA_SUPERCOMMERCE_SLUG', 'aura-supercommerce' );
define( 'AURA_SUPERCOMMERCE_PLUGINS', 
	array( 
		'aura-supercommerce' => array(
			'id' => 2,
			'title' => 'Foundation',
			'slug' => 'aura-supercommerce',
			'image_URL' => 'https://superdev.auracreativemedia.co.uk/wp-content/plugins/aura-supercommerce/admin/partials/images/dz-cc-place.jpg',
		
		),  
		'aura-dual-engine' => array(
			'id' => 3,
			'title' => 'Dual Engine',
			'slug' => 'aura-dual-engine',
			'image_URL' => 'https://superdev.auracreativemedia.co.uk/wp-content/plugins/aura-supercommerce/admin/partials/images/dz-cc-place.jpg',
	
		),
		'aura-time-saver' => array(
			'id' => 4,
			'title' => 'Time Saver Tech',
			'slug' => 'aura-time-saver',
			'image_URL' => 'https://superdev.auracreativemedia.co.uk/wp-content/plugins/aura-supercommerce/admin/partials/images/dz-cc-place.jpg',
	
		),
		'aura-publicity' => array(
			'id' => 5,
			'title' => 'Publicity Machine',
			'slug' => 'aura-publicity',
			'image_URL' => 'https://superdev.auracreativemedia.co.uk/wp-content/plugins/aura-supercommerce/admin/partials/images/dz-cc-place.jpg',
	
		),
		'aura-conversion' => array(
			'id' => 6,
			'title' => 'Conversion',
			'slug' => 'aura-conversion',
			'image_URL' => 'https://superdev.auracreativemedia.co.uk/wp-content/plugins/aura-supercommerce/admin/partials/images/dz-cc-place.jpg',

		),
		'aura-trade-booster' => array(
			'id' => 7,
			'title' => 'Trade Booster',
			'slug' => 'aura-trade-booster',
			'image_URL' => 'https://superdev.auracreativemedia.co.uk/wp-content/plugins/aura-supercommerce/admin/partials/images/dz-cc-place.jpg',

		),
		'aura-agent' => array(
			'id' => 8,
			'title' => 'Agent Perfection',
			'slug' => 'aura-agent',
			'image_URL' => 'https://superdev.auracreativemedia.co.uk/wp-content/plugins/aura-supercommerce/admin/partials/images/dz-cc-place.jpg',

		),
		'aura-stock-maximiser' => array(
			'id' => 9,
			'title' => 'Stockist Maximiser',
			'slug' => 'aura-stock-maximiser',
			'image_URL' => 'https://superdev.auracreativemedia.co.uk/wp-content/plugins/aura-supercommerce/admin/partials/images/dz-cc-place.jpg',

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
register_activation_hook( __FILE__, 'activate_refresh_sc_core_snippets' );

register_deactivation_hook( __FILE__, 'deactivate_aura_supercommerce' );
register_deactivation_hook( __FILE__, 'deactivate_refresh_sc_core_snippets' );


/**
 * 2 Cron functions that run Hourly and force a refresh of all snippets. Although snippets should update on plugin updates this single Cron ensures all of the SuperComm suite of plugins refresh their snippets regulary, the reason, to make sure they find new snippets when we add them to the ecosystem.
 */
function activate_refresh_sc_core_snippets() {

    if (! wp_next_scheduled ( 'supercomm_cron_hourly_snippets' )) {
       wp_schedule_event( time(), 'hourly', 'supercomm_cron_hourly_snippets' );
    }
}

function deactivate_refresh_sc_core_snippets() {
    wp_clear_scheduled_hook( 'supercomm_cron_hourly_snippets' );

}


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