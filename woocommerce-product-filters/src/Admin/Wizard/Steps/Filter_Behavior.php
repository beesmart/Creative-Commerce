<?php
/**
 * @package   Barn2\woocommerce-product-filters
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */

namespace Barn2\Plugin\WC_Filters\Admin\Wizard\Steps;

use Barn2\Plugin\WC_Filters\Admin\Settings_Controller;
use Barn2\Plugin\WC_Filters\Dependencies\Setup_Wizard\Api;
use Barn2\Plugin\WC_Filters\Dependencies\Setup_Wizard\Step;
use Barn2\Plugin\WC_Filters\Dependencies\Setup_Wizard\Util;
use Barn2\Plugin\WC_Filters\Utils\Settings;

class Filter_Behavior extends Step {

	/**
	 * {@inheritdoc}
	 */
	public function __construct() {
		$this->set_id( 'filter-behavior' );
		$this->set_name( __( 'Behavior', 'woocommerce-product-filters' ) );
		$this->set_title( __( 'Filter behavior and content', 'woocommerce-product-filters' ) );
		$this->set_description( __( 'How do you want the filters to work?', 'woocommerce-product-filters' ) );
	}

	/**
	 * {@inheritdoc}
	 */
	public function setup_fields() {
		$settings        = Settings_Controller::get_behavior_settings();
		$button_settings = Settings_Controller::get_button_settings();

		$fields = Util::pluck_wc_settings(
			$settings,
			[
				'wcf_options[filter_mode]',
			]
		);

		$button_fields = Util::pluck_wc_settings(
			$button_settings,
			[
				'wcf_options[button_text]',
			]
		);

		$fields = array_merge( $fields, $button_fields );

		$fields['wcf_options[button_text]']['conditions'] = [
			'wcf_options[filter_mode]' => [
				'op'    => 'eq',
				'value' => 'button',
			]
		];

		$fields['wcf_options[filter_mode]']['value'] = Settings::get_option( 'filter_mode' );

		$fields['wcf_options[filter_mode]']['options'][0]['label'] = __( 'Apply filters as soon as the customer makes a selection', 'woocommerce-product-filters' );
		$fields['wcf_options[filter_mode]']['options'][1]['label'] = __( 'Click a button to apply the filters', 'woocommerce-product-filters' );

		$fields['count_heading'] = [
			'type'  => 'heading',
			'label' => __( 'Product count', 'woocommerce-product-filters' ),
			'size'  => 'h4',
			'style' => [ 'marginBottom' => '12px' ]
		];

		$fields['number_products'] = [
			'type'    => 'checkbox',
			'label'   => __( 'Display the number of products next to each option', 'woocommerce-product-filters' ),
			'value'   => Settings::get_option( 'display_count', false ),
			'classes' => [ 'checkbox-no-border', 'last-checkbox' ]
		];

		return $fields;
	}

	/**
	 * {@inheritdoc}
	 */
	public function submit( array $values ) {

		$options = $values['wcf_options'];

		$number_products = isset( $values['number_products'] ) && $values['number_products'] === 'true' || $values['number_products'] === '1';

		Settings::update_option( 'display_count', $number_products );

		foreach ( $options as $id => $value ) {
			Settings::update_option( $id, $value );
		}

		return Api::send_success_response();

	}

}
