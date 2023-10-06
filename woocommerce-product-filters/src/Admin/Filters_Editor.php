<?php
/**
 * @package   Barn2\woocommerce-product-filters
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */

namespace Barn2\Plugin\WC_Filters\Admin;

use Barn2\Plugin\WC_Filters\Api;
use Barn2\Plugin\WC_Filters\Utils\Settings;
use Barn2\Plugin\WC_Filters\Dependencies\Lib\Registerable;
use JsonSerializable;
use Barn2\Plugin\WC_Filters\Meta_Fields;

use function Barn2\Plugin\WC_Filters\wcf;

/**
 * Displays the filters editor.
 */
class Filters_Editor implements Registerable, JsonSerializable {

	const TAB_ID = 'filters';

	/**
	 * Tab ID
	 *
	 * @var string
	 */
	public $id;

	/**
	 * Tab title
	 *
	 * @var string
	 */
	public $title;

	/**
	 * Get things started.
	 */
	public function __construct() {
		$this->id    = 'filters';
		$this->title = __( 'Filters', 'woocommerce-product-filters' );
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'admin_enqueue_scripts', [ $this, 'assets' ], 999999 );
	}

	/**
	 * Output content of the page.
	 *
	 * @return void
	 */
	public function output() {
		return '<div id="wcf-admin-editor"></div>';
	}

	/**
	 * Json configuration for the editor.
	 *
	 * @return array
	 */
	#[\ReturnTypeWillChange]
	public function jsonSerialize() {

		/** @var Meta_Fields */
		$meta_provider = wcf()->get_service( 'meta_fields' );

		return [
			'ajax_url'                   => admin_url( 'admin-ajax.php' ),
			'get_nonce'                  => wp_create_nonce( 'wcf_get_groups_nonce' ),
			'get_filters_nonce'          => wp_create_nonce( 'wcf_get_filters_nonce' ),
			'save_nonce'                 => wp_create_nonce( 'wcf_save_group_nonce' ),
			'delete_nonce'               => wp_create_nonce( 'wcf_delete_group_nonce' ),
			'save_priority_nonce'        => wp_create_nonce( 'wcf_save_priority_nonce' ),
			'load_group_nonce'           => wp_create_nonce( 'wcf_load_group_nonce' ),
			'create_nonce'               => wp_create_nonce( 'wcf_create_group_nonce' ),
			'duplicate_nonce'            => wp_create_nonce( 'wcf_duplicate_group_nonce' ),
			'get_terms_taxonomies_nonce' => wp_create_nonce( 'wcf_get_terms_taxonomies_nonce' ),
			'filters_page_url'           => admin_url( 'admin.php?page=wc-settings&tab=filters&section=filters-editor' ),
			'sources_list'               => Settings::get_filter_sources_list(),
			'filter_types_list'          => Settings::get_supported_filter_types_list(),
			'attributes_page'            => esc_url( admin_url( 'edit.php?post_type=product&page=product_attributes' ) ),
			'categories_page'            => esc_url( admin_url( 'edit-tags.php?taxonomy=product_cat&post_type=product' ) ),
			'shop_page_url'              => esc_url( wc_get_page_permalink( 'shop' ) ),
			'api_root'                   => trailingslashit( esc_url_raw( rest_url() ) ),
			'api_nonce'                  => wp_create_nonce( 'wp_rest' ),
			'settings_page_url'          => esc_url( admin_url( 'edit.php?post_type=product&page=filters&tab=settings' ) ),
			'has_custom_fields'          => $meta_provider->has_providers(),
			'cf_api'                     => trailingslashit( get_rest_url() . Api::API_NAMESPACE ) . 'cf',
		];
	}

	/**
	 * Get the tab title.
	 *
	 * @return string
	 */
	public function get_title() {
		return $this->title;
	}

	/**
	 * Get the tab ID.
	 *
	 * @return string
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * Enqueue assets when required.
	 *
	 * @return void
	 */
	public function assets() {

		$screen = get_current_screen();

		if ( $screen->id !== 'product_page_filters' || isset( $_GET['tab'] ) && $_GET['tab'] === 'settings' ) {
			return;
		}

		$file_name = 'wcf-admin-editor';

		$admin_script_path       = 'assets/build/' . $file_name . '.js';
		$admin_script_asset_path = wcf()->get_dir_path() . 'assets/build/' . $file_name . '.asset.php';
		$admin_script_asset      = file_exists( $admin_script_asset_path )
		? require $admin_script_asset_path
		: [
			'dependencies' => [],
			'version'      => filemtime( $admin_script_path )
		];
		$script_url              = wcf()->get_dir_url() . $admin_script_path;

		wp_register_script(
			$file_name,
			$script_url,
			$admin_script_asset['dependencies'],
			$admin_script_asset['version'],
			true
		);

		wp_enqueue_script( 'barn2-tiptip' );

		wp_enqueue_script( $file_name );

		wp_add_inline_script( $file_name, 'const wcf_groups_editor = ' . wp_json_encode( $this ), 'before' );

		wp_enqueue_style( $file_name, wcf()->get_dir_url() . 'assets/build/wcf-admin-editor.css', [ 'wp-components' ], $admin_script_asset['version'] );

		wp_set_script_translations(
			$file_name,
			'woocommerce-product-filters',
			wcf()->get_dir_path() . 'languages/'
		);
	}
}
