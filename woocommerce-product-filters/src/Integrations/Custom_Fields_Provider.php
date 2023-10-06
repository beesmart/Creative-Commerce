<?php
/**
 * @package   Barn2\woocommerce-product-filters
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */

namespace Barn2\Plugin\WC_Filters\Integrations;

interface Custom_Fields_Provider {

	/**
	 * Determine if the provider is registered.
	 *
	 * @return boolean
	 */
	public function is_registered();

	/**
	 * Returns the name of the provider.
	 *
	 * @return string
	 */
	public function get_provider_name();

	/**
	 * Returns a list of all registered custom fields.
	 *
	 * @param bool $flattened flattened by default
	 * @return array
	 */
	public function get_fields( bool $flattened = true );

	/**
	 * Formats the list of registered custom fields to an
	 * array compatible with the fields editor.
	 *
	 * @param array $fields list of fields to flatten and format.
	 * @return array
	 */
	public function flatten_fields( array $fields );

	/**
	 * Returns a list of supported field types.
	 *
	 * @return array
	 */
	public function get_supported_types();

	/**
	 * Returns the value of a custom field.
	 *
	 * @param string $meta_key
	 * @param string|int $post_id
	 * @return mixed
	 */
	public function get_field_value( string $meta_key, $post_id );

	/**
	 * Returns the human readable value of a given option of a field.
	 *
	 * @param string $meta_key
	 * @param string $option
	 * @return mixed
	 */
	public function get_field_display_value( string $meta_key, $option );

}
