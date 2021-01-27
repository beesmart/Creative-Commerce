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
 * @package           Aura_Publicity
 *
 * @wordpress-plugin
 * Plugin Name:       Creative Commerce - Publicity
 * Plugin URI:        https://digitalzest.co.uk
 * Description:       The Publicity plugin creates a gated area in which you can advertise your business to nominated users and stakeholders.
 * Version:           1.0.1
 * Author:            Digital Zest
 * Author URI:        https://digitalzest.co.uk
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       aura-publicity
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
define( 'AURA_PUBLICITY_VERSION', '1.0.1' );
define( 'AURA_PUBLICITY_SLUG', 'aura-publicity' );
define( 'AURA_PUBLICITY_TITLE', 'Publicity-Machine' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-aura-publicity-activator.php
 */
function activate_aura_publicity() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-aura-publicity-activator.php';
	Aura_Publicity_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-aura-publicity-deactivator.php
 */
function deactivate_aura_publicity() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-aura-publicity-deactivator.php';
	Aura_Publicity_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_aura_publicity' );
register_deactivation_hook( __FILE__, 'deactivate_aura_publicity' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-aura-publicity.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_aura_publicity() {

	$plugin = new Aura_Publicity();
	$plugin->run();

}
run_aura_publicity();
