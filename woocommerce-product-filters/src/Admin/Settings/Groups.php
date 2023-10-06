<?php
/**
 * @package   Barn2\woocommerce-product-filters
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */

namespace Barn2\Plugin\WC_Filters\Admin\Settings;

/**
 * Filter groups tab of the settings panel.
 */
class Groups extends React_Tab {

	public $section_name  = 'filter-groups';
	public $file_name     = 'wcf-filter-groups';
	public $json_accessor = 'wcf_groups_editor';

	/**
	 * Output the dom element where the react app will render.
	 *
	 * @return void
	 */
	public function output() {
		echo '<div id="wcf-filter-groups-root"></div>';
	}

	/**
	 * App json data.
	 *
	 * @return array
	 */
	public function get_json() {
		return [
			'ajax_url'            => admin_url( 'admin-ajax.php' ),
			'get_nonce'           => wp_create_nonce( 'wcf_get_groups_nonce' ),
			'get_filters_nonce'   => wp_create_nonce( 'wcf_get_filters_nonce' ),
			'save_nonce'          => wp_create_nonce( 'wcf_save_group_nonce' ),
			'delete_nonce'        => wp_create_nonce( 'wcf_delete_group_nonce' ),
			'save_priority_nonce' => wp_create_nonce( 'wcf_save_priority_nonce' ),
			'filters_page_url'    => admin_url( 'admin.php?page=wc-settings&tab=filters&section=filters-editor' )
		];
	}

}
