<?php
/**
 * @package   Barn2\woocommerce-product-filters
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */

namespace Barn2\Plugin\WC_Filters\Admin\Wizard\Steps;

use Barn2\Plugin\WC_Filters\Dependencies\Setup_Wizard\Api;
use Barn2\Plugin\WC_Filters\Dependencies\Setup_Wizard\Step;
use Barn2\Plugin\WC_Filters\Utils\Settings;

class Filter_Display extends Step {

	/**
	 * {@inheritdoc}
	 */
	public function __construct() {
		$this->set_id( 'filter-display' );
		$this->set_name( __( 'Shop filters', 'woocommerce-product-filters' ) );
		$this->set_title( __( 'Shop filters', 'woocommerce-product-filters' ) );
		$this->set_description( __( 'Would you like to display filters above the lists of products in your shop? (If not, then you can add filters manually using a widget or shortcode.)', 'woocommerce-product-filters' ) );
		$this->set_tooltip(
			sprintf(
				__( 'Select a filter group to display the same filters at the top of all your main store pages (to get you started, we’ve created a ‘Recommended Filters’ group for you).', 'woocommerce-product-filters' ),
				'https://barn2.com/kb/creating-filters/'
			)
		);
	}

	/**
	 * {@inheritdoc}
	 */
	public function setup_fields() {

		$filters  = $this->get_filters();
		$selected = Settings::get_option( 'group_display_shop_archive' );

		// We need to override the key prop because of WC's dropdown.
		$filters[0]['key'] = 'none';

		$options = [
			'group' => [
				'type'    => 'select',
				'label'   => esc_html__( 'Select a filter group to appear at the top of your store (optional)', 'woocommerce-product-filters' ),
				'value'   => 'option_1',
				'options' => $filters,
				'value'   => is_numeric( $selected ) ? absint( $selected ) : 'none',
			]
		];

		return $options;
	}

	/**
	 * Get the list of filter but only when
	 * on the setup wizard page.
	 *
	 * @return array
	 */
	private function get_filters() {

		$groups  = Settings::get_groups_for_dropdown();
		$options = [];

		if ( empty( $groups ) ) {
			return [];
		}

		foreach ( $groups as $id => $name ) {
			$options[] = [
				'value' => $id,
				'label' => $name,
			];
		}

		return $options;

	}

	/**
	 * {@inheritdoc}
	 */
	public function submit( array $values ) {

		$group = $values['group'];

		if ( $group === 'none' ) {
			$group = '';
		}

		Settings::update_option( 'group_display_shop_archive', $group );

		return Api::send_success_response();

	}

}
