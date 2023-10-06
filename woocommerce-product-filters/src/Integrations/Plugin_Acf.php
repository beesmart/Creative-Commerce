<?php
/**
 * @package   Barn2\woocommerce-product-filters
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */

namespace Barn2\Plugin\WC_Filters\Integrations;

use Barn2\Plugin\WC_Filters\Utils\Settings;
use Barn2\Plugin\WC_Filters\Dependencies\Lib\Registerable;

/**
 * Provides custom fields integration for filters
 * powered by the ACF plugin.
 */
class Plugin_Acf implements Registerable, Custom_Fields_Provider {

	public $registered = false;

	/**
	 * {@inheritdoc}
	 */
	public function register() {
		if ( function_exists( 'acf' ) && version_compare( acf()->settings['version'], '5.0', '>=' ) ) {
			$this->init();
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function is_registered() {
		return $this->registered;
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_provider_name() {
		return 'Advanced Custom Fields';
	}

	/**
	 * Hook into WP.
	 *
	 * @return void
	 */
	public function init() {
		$this->registered = true;

		add_filter( 'wcf_supported_sources', [ $this, 'register_source' ] );
		add_filter( 'wcf_supported_sources_filter_types', [ $this, 'register_types_support' ] );
		add_filter( 'wcf_all_choices_counts_bypass', [ $this, 'bypass_choices_counters' ], 10, 2 );
	}

	/**
	 * Bypass counters for certain field types.
	 *
	 * @param boolean $bypass
	 * @param Filter $filter
	 * @return mixed
	 */
	public function bypass_choices_counters( $bypass, $filter ) {

		$type = $filter->get_option( 'filter_type' );

		if ( $type === 'number' ) {
			return [];
		}

		return false;
	}

	/**
	 * Register the custom fields source.
	 *
	 * @param array $list
	 * @return array
	 */
	public function register_source( $list ) {
		$list = Settings::array_splice_after_key( $list, 'price', [ 'cf' => __( 'Custom field', 'woocommerce-product-filters' ) ] );

		return $list;
	}

	/**
	 * Register the supported types by the "cf" source type.
	 *
	 * @param array $types
	 * @return array
	 */
	public function register_types_support( $types ) {

		$types['dropdown']['supports']   = array_merge( $types['dropdown']['supports'], [ 'cf' ] );
		$types['checkboxes']['supports'] = array_merge( $types['dropdown']['supports'], [ 'cf' ] );
		$types['radio']['supports']      = array_merge( $types['dropdown']['supports'], [ 'cf' ] );
		$types['range']['supports']      = array_merge( $types['dropdown']['supports'], [ 'cf' ] );

		$types['true_false'] = [
			'label'    => __( 'Checkbox', 'woocommerce-product-filters' ),
			'supports' => [],
		];

		$types['text'] = [
			'label'    => __( 'Text input', 'woocommerce-product-filters' ),
			'supports' => [ 'cf' ],
		];

		$types['number'] = [
			'label'    => __( 'Range slider', 'woocommerce-product-filters' ),
			'supports' => [],
		];

		return $types;
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_fields( $flattened = true ) {

		$fields = [];

		add_action( 'pre_get_posts', [ $this, 'disable_wpml' ] );
		$field_groups = acf_get_field_groups();
		remove_action( 'pre_get_posts', [ $this, 'disable_wpml' ] );

		foreach ( $field_groups as $field_group ) {
			$fields[] = acf_get_fields( $field_group );
		}

		$fields = $this->get_only_supported( $fields );

		if ( ! $flattened ) {
			return $fields;
		}

		$fields = $this->flatten_fields( $fields );

		return $fields;
	}

	/**
	 * We need to get field groups in all languages.
	 *
	 * @param object $query
	 * @return void
	 */
	public function disable_wpml( $query ) {
		$query->set( 'suppress_filters', true );
		$query->set( 'lang', '' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_supported_types() {
		return [
			'radio',
			'select',
			'true_false',
			'text',
			'number'
		];
	}

	/**
	 * Get the list of supported fields only.
	 *
	 * @param array $groups
	 * @return array
	 */
	private function get_only_supported( array $groups ) {

		$types_supported = $this->get_supported_types();

		foreach ( $groups as $group_key => $fields ) {
			foreach ( $fields as $field_key => $field ) {
				if ( ! in_array( $field['type'], $types_supported, true ) ) {
					unset( $groups[ $group_key ][ $field_key ] );
				}
			}
		}

		return $groups;
	}

	/**
	 * {@inheritdoc}
	 */
	public function flatten_fields( $groups ) {

		$formatted = [];

		$restricted_types = [
			'true_false',
		];

		foreach ( $groups as $fields ) {
			foreach ( $fields as $field ) {
				$formatted[] = [
					'value'      => $field['name'],
					'label'      => $field['label'],
					'type'       => 'acf',
					'restricted' => in_array( $field['type'], $restricted_types, true ),
					'transform'  => $field['type']
				];
			}
		}

		return $formatted;
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_field_value( string $meta_key, $post_id ) {
		return get_field( $meta_key, $post_id );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_field_display_value( string $meta_key, $option ) {

		$display = false;
		$groups  = $this->get_fields( false );

		foreach ( $groups as $fields ) {
			foreach ( $fields as $field ) {
				if ( ! isset( $field['name'] ) ) {
					continue;
				}
				if ( $field['name'] === $meta_key && isset( $field['choices'] ) && isset( $field['choices'][ $option ] ) ) {
					return $field['choices'][ $option ];
				}
			}
		}

		return $display;
	}

}
