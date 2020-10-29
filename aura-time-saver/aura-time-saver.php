<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://auracreativemedia.co.uk
 * @since             1.0.0
 * @package           Aura_time_saver
 *
 * @wordpress-plugin
 * Plugin Name:       Creative Commerce - Time Saver
 * Plugin URI:        https://digitalzest.co.uk/
 * Description:       This is a short description of what the plugin does. It's displayed in the WordPress admin area.
 * Version:           1.0.0
 * Author:            Digital Zest
 * Author URI:        https://digitalzest.co.uk/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       aura-time-saver
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * CONSTANTS
 * 
 * AURA_TIME_SAVER_SLUG is used for many snippet related functions and exists on all Aura products though individual instances/
 *
 * @FLAG : Migration, migrate
*/



define( 'AURA_TIME_SAVER_VERSION', '1.0.0' );
define( 'AURA_TIME_SAVER_SLUG', 'aura-time-saver' );
define( 'AURA_TIME_SAVER_TITLE', 'Time Saver' );


//require_once ABSPATH . '/wp-content/plugins/pluginname/pluginfunctions.php';


 /**
  * The code that runs during plugin activation.
  * This action is documented in includes/class-aura_time_saver-activator.php
  */
 function activate_aura_time_saver() {
 	require_once plugin_dir_path( __FILE__ ) . 'includes/class-aura_time_saver-activator.php';
 	Aura_time_saver_Activator::activate();
 }

 /**
  * The code that runs during plugin deactivation.
  * This action is documented in includes/class-aura_time_saver-deactivator.php
  */
 function deactivate_aura_time_saver() {
 	require_once plugin_dir_path( __FILE__ ) . 'includes/class-aura_time_saver-deactivator.php';
 	Aura_time_saver_Deactivator::deactivate();
 }

 register_activation_hook( __FILE__, 'activate_aura_time_saver' );
 register_deactivation_hook( __FILE__, 'deactivate_aura_time_saver' );

 /**
  * The core plugin class that is used to define internationalization,
  * admin-specific hooks, and public-facing site hooks.
  */
 require plugin_dir_path( __FILE__ ) . 'includes/class-aura_time_saver.php';

 /**
  * Begins execution of the plugin.
  *
  * Since everything within the plugin is registered via hooks,
  * then kicking off the plugin from this point in the file does
  * not affect the page life cycle.
  *
  * @since    1.0.0
  */


 function run_aura_time_saver() {

 	$plugin = new Aura_time_saver();
 	$plugin->run();

 }

 run_aura_time_saver();

