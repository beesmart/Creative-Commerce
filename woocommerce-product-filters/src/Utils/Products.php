<?php
/**
 * @package   Barn2\woocommerce-product-filters
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */

namespace Barn2\Plugin\WC_Filters\Utils;

use Barn2\Plugin\WC_Filters\Dependencies\Illuminate\Support\Str;

/**
 * Utility methods for products.
 */
class Products {

	/**
	 * Get a list of registered taxonomies for products
	 * excluding a certain subset.
	 *
	 * @param bool $exclude_default when true, it will exclude categories and tags too.
	 * @param bool $with_attributes when true, it will include attributes too.
	 * @return array
	 */
	public static function get_registered_taxonomies( $exclude_default = false, $with_attributes = false ) {

		$list        = [];
		$not_allowed = [ 'product_visibility', 'product_shipping_class', 'product_type' ];

		if ( $exclude_default ) {
			$not_allowed[] = 'product_cat';
			$not_allowed[] = 'product_tag';
		}

		$registered = get_object_taxonomies( 'product', 'objects' );

		foreach ( $registered as $taxonomy ) {
			if ( in_array( $taxonomy->name, $not_allowed, true ) ) {
				continue;
			}
			if ( Str::startsWith( $taxonomy->name, 'pa_' ) && ! $with_attributes ) {
				continue;
			}
			$list[ $taxonomy->name ] = $taxonomy->label;
		}

		return $list;
	}

	/**
	 * Get the list of all global attributes taxonomies.
	 *
	 * @param boolean $with_prefix whether we need the "pa_" prefix in front of the name.
	 * @return array
	 */
	public static function get_registered_attributes( $with_prefix = false ) {
		$list       = [];
		$attributes = wc_get_attribute_taxonomies();

		foreach ( $attributes as $attr ) {
			$list[ $with_prefix ? 'pa_' . $attr->attribute_name : $attr->attribute_name ] = $attr->attribute_label;
		}

		return $list;
	}

	/**
	 * Get currency data about the store.
	 *
	 * @return array
	 */
	public static function get_currency_data() {
		$currency = get_woocommerce_currency();

		return [
			'code'              => $currency,
			'precision'         => wc_get_price_decimals(),
			'symbol'            => html_entity_decode( get_woocommerce_currency_symbol( $currency ) ),
			'symbolPosition'    => get_option( 'woocommerce_currency_pos' ),
			'decimalSeparator'  => wc_get_price_decimal_separator(),
			'thousandSeparator' => wc_get_price_thousand_separator(),
			'priceFormat'       => html_entity_decode( get_woocommerce_price_format() ),
		];
	}

	/**
	 * Returns the list of sorting options.
	 *
	 * @return array
	 */
	public static function get_catalog_sorting_options() {
		$catalog_orderby_options = apply_filters(
			'woocommerce_catalog_orderby',
			[
				'menu_order' => __( 'Default sorting', 'woocommerce-product-filters' ),
				'popularity' => __( 'Sort by popularity', 'woocommerce-product-filters' ),
				'rating'     => __( 'Sort by average rating', 'woocommerce-product-filters' ),
				'date'       => __( 'Sort by latest', 'woocommerce-product-filters' ),
				'price'      => __( 'Sort by price: low to high', 'woocommerce-product-filters' ),
				'price-desc' => __( 'Sort by price: high to low', 'woocommerce-product-filters' ),
			]
		);

		return $catalog_orderby_options;
	}

	/**
	 * Determine if we're visiting a product attribute page.
	 *
	 * @return boolean
	 */
	public static function is_product_attribute_page() {
		$object = get_queried_object();

		return isset( $object->taxonomy ) && ! empty( $object->taxonomy ) && taxonomy_is_product_attribute( $object->taxonomy );
	}

	/**
	 * Get a substring between two strings.
	 *
	 * @param string $string
	 * @param string $start
	 * @param string $end
	 * @return string
	 */
	public static function get_string_between( $string, $start, $end ) {
		$string = ' ' . $string;
		$ini    = strpos( $string, $start );
		if ( $ini === 0 ) {
			return '';
		}
		$ini += strlen( $start );
		$len  = strpos( $string, $end, $ini ) - $ini;
		return substr( $string, $ini, $len );
	}

}
