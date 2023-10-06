<?php
/**
 * @package   Barn2\woocommerce-product-filters
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */

namespace Barn2\Plugin\WC_Filters\Admin\Settings;

use Barn2\Plugin\WC_Filters\Dependencies\Lib\Plugin\Licensed_Plugin;
use Barn2\Plugin\WC_Filters\Dependencies\Lib\Registerable;

/**
 * A special tab that enqueues assets to load a react js powered app.
 * Override the output method to specify the dom element where the app will be rendered.
 */
abstract class React_Tab implements Registerable {

	/**
	 * Name of the section.
	 * Needed so that react assets are loaded only on this specific section.
	 *
	 * @var string
	 */
	public $section_name;

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
	private $plugin;

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
	}

	/**
	 * Hook assets needed for this specific tab only.
	 *
	 * @return void
	 */
	public function assets() {

		$screen = get_current_screen();

		if ( $screen->id !== 'woocommerce_page_wc-settings' ) {
			return;
		}

		if ( ! isset( $_GET['tab'] ) || isset( $_GET['tab'] ) && $_GET['tab'] !== 'filters' ) {
			return;
		}

		if ( ! isset( $_GET['section'] ) || isset( $_GET['section'] ) && $_GET['section'] !== $this->section_name ) {
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

		$json = $this->get_json();

		if ( ! empty( $json ) ) {
			wp_add_inline_script( $file_name, 'const ' . esc_attr( $this->json_accessor ) . ' = ' . wp_json_encode( $json ), 'before' );
		}

		wp_register_style( $file_name, $this->plugin->get_dir_url() . 'assets/build/' . $file_name . '.css', [ 'wp-components' ], $admin_script_asset['version'] );

		wp_enqueue_style( $file_name );

	}

	/**
	 * Output the dom element where the react app will render.
	 *
	 * @return void
	 */
	abstract public function output();

	/**
	 * Get json data for the app.
	 *
	 * @return array
	 */
	abstract public function get_json();

}
