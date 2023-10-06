<?php
/**
 * @package   Barn2\woocommerce-product-filters
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */

namespace Barn2\Plugin\WC_Filters\Admin\Terms;

use Barn2\Plugin\WC_Filters\Dependencies\Lib\Plugin\Licensed_Plugin;
use Barn2\Plugin\WC_Filters\Dependencies\Lib\Registerable;

/**
 * Base class that handles react powered js files to output on
 * taxonomy terms pages.
 */
abstract class React_Term implements Registerable {

	/**
	 * Name of the file that will be enqueued.
	 * Name only, without the extension.
	 *
	 * @var string
	 */
	public $file_name;

	/**
	 * Plugin instance.
	 *
	 * @var Licensed_Plugin
	 */
	public $plugin;

	/**
	 * The name of the constant that holds the json data
	 * for the frontend.
	 *
	 * @var string
	 */
	public $json_accessor;

	/**
	 * Get things started.
	 *
	 * @param Licensed_Plugin $plugin
	 */
	public function __construct( Licensed_Plugin $plugin ) {
		$this->plugin = $plugin;
	}

	/**
	 * Hook into WP.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'admin_enqueue_scripts', [ $this, 'assets' ] );
		add_action( 'created_term', [ $this, 'created_term' ], 10, 3 );
		add_action( 'edit_term', [ $this, 'created_term' ], 10, 3 );
	}

	/**
	 * Determine if the asset should enqueue.
	 *
	 * @return boolean
	 */
	abstract public function should_enqueue();

	/**
	 * Enqueue the assets.
	 *
	 * @return void
	 */
	public function assets() {

		if ( ! $this->should_enqueue() ) {
			return;
		}

		$file_name = $this->file_name;

		$admin_script_path       = 'assets/build/' . $file_name . '.js';
		$admin_script_asset_path = $this->plugin->get_dir_path() . 'assets/build/' . $file_name . '.asset.php';
		$admin_script_asset      = file_exists( $admin_script_asset_path )
		? require $admin_script_asset_path
		: [
			'dependencies' => [],
			'version'      => filemtime( $admin_script_path )
		];
		$script_url              = $this->plugin->get_dir_url() . $admin_script_path;

		wp_register_script(
			$file_name,
			$script_url,
			$admin_script_asset['dependencies'],
			$admin_script_asset['version'],
			true
		);

		wp_enqueue_script( $file_name );

		wp_register_style( $file_name, $this->plugin->get_dir_url() . 'assets/build/' . $file_name . '.css', [ 'wp-components' ], $admin_script_asset['version'] );

		wp_enqueue_style( $file_name );

	}

	/**
	 * Determine if we're viewing the edit page of a term.
	 *
	 * @return boolean
	 */
	protected function is_edit_page() {
		return $this->should_enqueue() && isset( $_GET['tag_ID'] );
	}

	/**
	 * Update the term metadata.
	 *
	 * @param string $term_id
	 * @param string $tt_id
	 * @param string $taxonomy
	 * @return void
	 */
	abstract public function created_term( $term_id, $tt_id, $taxonomy );

}
