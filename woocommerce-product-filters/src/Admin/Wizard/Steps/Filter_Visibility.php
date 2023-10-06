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

class Filter_Visibility extends Step {

	/**
	 * {@inheritdoc}
	 */
	public function __construct() {
		$this->set_id( 'filter-visibility' );
		$this->set_name( __( 'Visibility', 'woocommerce-product-filters' ) );
		$this->set_title( __( 'Filter visibility', 'woocommerce-product-filters' ) );
		$this->set_description( __( 'Next, decide whether to show or hide the filters when the page first loads', 'woocommerce-product-filters' ) );
	}

	/**
	 * {@inheritdoc}
	 */
	public function setup_fields() {

		$settings          = Settings_Controller::get_visibility_settings();
		$button_settings   = Settings_Controller::get_button_settings();
		$behavior_settings = Settings_Controller::get_behavior_settings();

		$fields = Util::pluck_wc_settings(
			$settings,
			[
				'wcf_options[desktop_visibility]',
				'wcf_options[mobile_visibility]',
				'wcf_options[show_filters_button_text]',
				'wcf_options[toggle_filters]',
			]
		);

		$button_fields = Util::pluck_wc_settings(
			$button_settings,
			[
				'wcf_options[show_filters_button_text]',
			]
		);

		$behavior_settings = Util::pluck_wc_settings(
			$behavior_settings,
			[
				'wcf_options[toggle_filters]',
				'wcf_options[toggle_default_status]'
			]
		);

		$fields = array_merge( $fields, $behavior_settings, $button_fields );

		$fields['wcf_options[toggle_default_status]']['conditions'] = [
			'wcf_options[toggle_filters]' => [
				'op'    => 'eq',
				'value' => true,
			],
		];

		$fields['wcf_options[show_filters_button_text]']['conditions'] = [
			'wcf_options[desktop_visibility]' => [
				'op'    => 'eq',
				'value' => 'closed',
			],
			'wcf_options[mobile_visibility]'  => [
				'op'    => 'eq',
				'value' => 'closed',
			]
		];

		$fields['wcf_options[show_filters_button_text]']['comparison'] = 'ANY';

		$fields['wcf_options[desktop_visibility]']['label']     = __( 'Filter visibility', 'woocommerce-product-filters' );
		$fields['wcf_options[mobile_visibility]']['label']      = __( 'On mobile', 'woocommerce-product-filters' );
		$fields['wcf_options[mobile_visibility]']['conditions'] = [
			'wcf_options[desktop_visibility]' => [
				'op'    => 'eq',
				'value' => 'closed',
			],
		];

		$fields['wcf_options[desktop_visibility]']['classes'] = [ 'with-no-bottom-margin' ];
		$fields['wcf_options[mobile_visibility]']['classes'] = [ 'with-top-border' ];

		$fields['wcf_options[desktop_visibility]']['value']       = Settings::get_option( 'desktop_visibility' );
		$fields['wcf_options[mobile_visibility]']['value']        = Settings::get_option( 'mobile_visibility' );
		$fields['wcf_options[show_filters_button_text]']['value'] = Settings::get_option( 'show_filters_button_text' );

		$fields['wcf_options[toggle_filters]']['value']       = Settings::get_option( 'toggle_filters', false );
		$fields['wcf_options[toggle_filters]']['description'] = __( 'Allow customers to toggle filters open or closed.', 'woocommerce-product-filters' );
		$fields['wcf_options[toggle_filters]']['classes']     = [ 'with-top-border', 'with-no-bottom-margin' ];

		$fields['wcf_options[toggle_default_status]']['value'] = Settings::get_option( 'toggle_default_status', 'closed' );

		return $fields;
	}

	/**
	 * {@inheritdoc}
	 */
	public function submit( array $values ) {

		$values = $values['wcf_options'];

		foreach ( $values as $id => $value ) {

			if ( $id === 'toggle_filters' ) {
				$val = $value === 'true' || $value === '1';
				Settings::update_option( $id, $val );
			} else {
				Settings::update_option( $id, $value );
			}
		}

		return Api::send_success_response();

	}

}
