<?php
/**
 * @package   Barn2\woocommerce-product-filters
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */

namespace Barn2\Plugin\WC_Filters\Admin\Settings;

use Barn2\Plugin\WC_Filters\Utils\Settings;

/**
 * Filters tab of the settings panel.
 */
class Filters extends React_Tab {

	public $section_name  = 'filters-editor';
	public $file_name     = 'wcf-filters-editor';
	public $json_accessor = 'wcf_filters_editor';

	/**
	 * Output the dom element where the react app will render.
	 *
	 * @return void
	 */
	public function output() {
		echo '<div id="wcf-filters-editor-root"></div>';
	}

	/**
	 * App json data.
	 *
	 * @return array
	 */
	public function get_json() {
		return [
			'ajax_url'                   => admin_url( 'admin-ajax.php' ),
			'api_root'                   => trailingslashit( esc_url_raw( rest_url() ) ),
			'api_nonce'                  => wp_create_nonce( 'wp_rest' ),
			'sources_list'               => Settings::parse_array_for_select_control( Settings::get_filter_sources_list() ),
			'filter_types_list'          => Settings::get_supported_filter_types_list(),
			'save_nonce'                 => wp_create_nonce( 'wcf_save_filter_nonce' ),
			'get_nonce'                  => wp_create_nonce( 'wcf_get_filters_nonce' ),
			'delete_nonce'               => wp_create_nonce( 'wcf_delete_item_nonce' ),
			'save_priority_nonce'        => wp_create_nonce( 'wcf_save_priority_nonce' ),
			'get_terms_taxonomies_nonce' => wp_create_nonce( 'wcf_get_terms_taxonomies_nonce' ),
			'create_nonce'               => wp_create_nonce( 'wcf_create_group_nonce' ),
			'attributes_page'            => esc_url( admin_url( 'edit.php?post_type=product&page=product_attributes' ) ),
			'categories_page'            => esc_url( admin_url( 'edit-tags.php?taxonomy=product_cat&post_type=product' ) ),
			'shop_page_url'              => esc_url( wc_get_page_permalink( 'shop' ) ),
		];
	}

}
