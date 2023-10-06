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

class Plugin_Ept implements Registerable, Custom_Fields_Provider {

	public $registered = false;

	/**
	 * {@inheritdoc}
	 */
	public function register() {
		if ( function_exists( '\Barn2\Plugin\Easy_Post_Types_Fields\ept' ) ) {
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
		return 'Easy Post Types and Fields';
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

		$types['text'] = [
			'label'    => __( 'Text input', 'woocommerce-product-filters' ),
			'supports' => [ 'cf' ],
		];

		return $types;
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_supported_types() {
		return [
			'text',
			'editor'
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_fields( $flattened = true ) {
		$fields = \Barn2\Plugin\Easy_Post_Types_Fields\Util::get_custom_fields( 'product' );

		if ( $flattened ) {
			$fields = $this->flatten_fields( $fields );
		}

		return $fields;
	}

	/**
	 * {@inheritdoc}
	 */
	public function flatten_fields( $fields ) {

		$formatted = [];

		foreach ( $fields as $field ) {

			$type = $field['type'];

			if ( $type === 'editor' ) {
				$type = 'text';
			}

			$formatted[] = [
				'value'     => 'product_' . $field['slug'],
				'label'     => $field['name'],
				'type'      => 'ept',
				'transform' => $type
			];
		}

		return $formatted;
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_field_value( string $meta_key, $post_id ) {
		return strip_tags( get_post_meta( $post_id, $meta_key, true ) );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_field_display_value( string $meta_key, $option ) {
		return '';
	}

}
