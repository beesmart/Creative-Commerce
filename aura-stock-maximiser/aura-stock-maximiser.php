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
 * @package           aura-stock-maximiser
 *
 * @wordpress-plugin
 * Plugin Name:       Creative Commerce - Stockist Maximiser
 * Plugin URI:        https://digitalzest.co.uk
 * Description:       Maximise your stockist potential by gaining data and insights into the most popular stockists within your industry. 
 * Version:           1.0.1
 * Author:            Digital Zest
 * Author URI:        https://digitalzest.co.uk
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       aura-stock-maximiser
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
define( 'AURA_STOCKIST_VERSION', '1.0.1' );
define( 'AURA_STOCKIST_DIR', 'aura-stock-maximiser/aura-stock-maximiser.php' );
define( 'AURA_STOCKIST_SLUG', 'aura-stock-maximiser' );
define( 'AURA_STOCKIST_TITLE', 'Stockist-Maximiser' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-aura-stockist-activator.php
 */
function activate_aura_stockist() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-aura-stockist-activator.php';
	Aura_Stockist_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-aura-stockist-deactivator.php
 */
function deactivate_aura_stockist() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-aura-stockist-deactivator.php';
	Aura_Stockist_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_aura_stockist' );
register_deactivation_hook( __FILE__, 'deactivate_aura_stockist' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-aura-stockist.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_aura_stockist() {

	$plugin = new Aura_Stockist();
	$plugin->run();

}
run_aura_stockist();
