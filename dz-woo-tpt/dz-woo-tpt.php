<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://digitalzest.co.uk
 * @since             1.0.0
 * @package           Dz_Woo_Tpt
 *
 * @wordpress-plugin
 * Plugin Name:       DZ - Top Product Types
 * Plugin URI:        https://digitalzest.co.uk
 * Description:       Generates a table for storing top product types and uses a Pabbly webhook to output the data.
 * Version:           1.0.0
 * Author:            Paul Taylor
 * Author URI:        https://digitalzest.co.uk
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       dz-woo-tpt
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'DZ_WOO_TPT_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-dz-woo-tpt-activator.php
 */
function activate_dz_woo_tpt() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-dz-woo-tpt-activator.php';
	Dz_Woo_Tpt_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-dz-woo-tpt-deactivator.php
 */
function deactivate_dz_woo_tpt() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-dz-woo-tpt-deactivator.php';
	Dz_Woo_Tpt_Deactivator::deactivate();
}


function uninstall_dz_woo_tpt() {
	require_once plugin_dir_path( __FILE__ ) . 'uninstall.php';
	Dz_Woo_Tpt_Uninstall::uninstall();
}


register_activation_hook( __FILE__, 'activate_dz_woo_tpt' );
register_deactivation_hook( __FILE__, 'deactivate_dz_woo_tpt' );
register_uninstall_hook( __FILE__, 'uninstall_dz_woo_tpt' );


/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-dz-woo-tpt.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_dz_woo_tpt() {

	$plugin = new Dz_Woo_Tpt();
	$plugin->run();

}
run_dz_woo_tpt();
