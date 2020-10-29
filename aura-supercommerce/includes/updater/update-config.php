<?php


/**
 * The Update-specific functionality of the plugin. This class also handles the plugin updates of ALL other child plugins.
 *
 *
 * @link              https://auracreativemedia.co.uk
 * @link 			  https://github.com/YahnisElsts/plugin-update-checker
 * @since             1.0.0
 * @package           Aura_Supercommerce
 * @subpackage        Aura_Supercommerce/includes/updater
 * @FLAG              Migrate / migrate / server url
 */

/** 
*
Class logic based on 

https://github.com/YahnisElsts/plugin-update-checker

AND 

hirasso's solution here: 
"How is the best use if you have many plugins?"
https://github.com/YahnisElsts/plugin-update-checker/issues/379 
*
**/


if( !class_exists( 'aura_licence_checker' ) ) {
    // load our custom updater
    include( WP_PLUGIN_DIR . '/aura-supercommerce/includes/licence/class-plugin-licence.php' );
}

// Let's first check if this website/user has an Active licence key
$aura_licence_checker_class = new aura_licence_checker;
$licence_status = $aura_licence_checker_class->check_has_licence();

require( WP_PLUGIN_DIR . '/aura-supercommerce/plugin-update-checker/plugin-update-checker.php');


// If they have a key continue

if($licence_status === 'active'){

	class Aura_Plugin_Updater {

	  private $registered_plugins = [];

	  /**
	   * Register a plugin for updates
	   *
	   * @param string $file $file_path - The plugin (path locally), to update, since we likely have multiple plugins
	   * @param string $key $licence_slug - The shorthand for the plugin, i.e. the slug
	   * @return boolean Success status
	   * @FLAG : Migration, migrate
	   */
	  public function register_plugin( $file_path, $licence_slug ) {

	    // return early if the plugin doesn't exist
	    if( !file_exists($file_path) ) return false;

	    // return early the plugin was already registered
	    if( in_array($file_path, $this->registered_plugins) ) return false;

	    // Now let's check if the licence has this particular plugin attached.
	    $aura_licence_checker  = new aura_licence_checker;
	    $products = $aura_licence_checker->check_licence_products();
	  
	    $licence_has_product = false;

	    // if the licenced products match the local plugin, then we can let them update
	    foreach ($products as $value) {

	    	$expl_slug = $aura_licence_checker->explode_on_product_title($value);

	    	if ($licence_slug === $expl_slug) : $licence_has_product = true; endif;

	    }

	    // return early if the product/plugin is not on the licence
	    if ( !$licence_has_product ) : return false; endif;


	    $this->registered_plugins[] = $file_path;

	    $plugin_slug = basename( dirname( $file_path ) );

	    // Let Plugin run it's updates by calling PUC
	    $update_checker = Puc_v4_Factory::buildUpdateChecker(
	      "https://superdev.colourcreation.co.uk/wp-update-server-master/?action=get_metadata&slug=$plugin_slug", // Metadata URL.
	      $file_path, // Full path to the main plugin file.
	      $plugin_slug, // Plugin slug. Usually it's the same as the name of the directory.
	      24*6 // <-- update interval in hours
	    );

	    return true;

	  }

	}

	// The filenames of the custom plugins along with the licence API slug (`my-plugin-folder/my-plugin-file.php`)
	$aura_updater_config = [

	  'plugins' => [
	    'aura-supercommerce' => 'aura-supercommerce/aura-supercommerce.php',
	    'aura-dual-engine' => 'aura-dual-engine/aura-dual-engine.php',
	    'aura-time-saver' => 'aura-time-saver/aura-time-saver.php',
	    'aura-publicity' => 'aura-publicity/aura-publicity.php',
	    'aura-conversion' => 'aura-conversion/aura-conversion.php',
	    'aura-trade-booster' => 'aura-trade-booster/aura-trade-booster.php',
	    'aura-agent' => 'aura-agent/aura-agent.php',
	    'aura-stock-maximiser' => 'aura-stock-maximiser/aura-stock-maximiser.php',
	  ],
	];

	// The Updater Class
	$aura_updater = new Aura_Plugin_Updater();
	// traverse through all plugins, and if they exist, register them for updates.
	foreach( $aura_updater_config['plugins'] as $key => $file ) {
	  $file = trailingslashit(WP_PLUGIN_DIR) . $file;
	  $aura_updater->register_plugin( $file, $key  );
	}

 }