<?php
/**
 * @package   Barn2\woocommerce-product-filters
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */

namespace Barn2\Plugin\WC_Filters\Utils;

use Barn2\Plugin\WC_Filters\Dependencies\Illuminate\Database\Eloquent\Collection;
use Barn2\Plugin\WC_Filters\Model\Group;

/**
 * Handles utility functions for the settings page.
 */
class Settings {

	/**
	 * Get an option
	 * Looks to see if the specified setting exists, returns default if not.
	 *
	 * @param string $key the key to retrieve.
	 * @param mixed  $default default value to use in case option is not available.
	 * @return mixed
	 */
	public static function get_option( $key = '', $default = false ) {
		$plugin_options = get_option( 'wcf_options', [] );
		$value          = ! empty( $plugin_options[ $key ] )
			? $plugin_options[ $key ]
			: $default;

		/**
		 * Filters the retrieval of an option.
		 *
		 * @param mixed $value the original value.
		 * @param string $key the key of the option being retrieved.
		 * @param mixed $default default value if nothing is found.
		 */
		$value = apply_filters( 'wcf_get_option', $value, $key, $default );
		return apply_filters( 'wcf_get_option_' . $key, $value, $key, $default );
	}

	/**
	 * Update an option.
	 *
	 * Updates an etting value in both the db and the global variable.
	 * Warning: Passing in an empty, false or null string value will remove
	 *          the key from the options array.
	 *
	 * @param string          $key         The Key to update.
	 * @param string|bool|int $value       The value to set the key to.
	 * @param bool $bypass_cap whether or not the capability check should be bypassed.
	 * @return boolean True if updated, false if not.
	 */
	public static function update_option( $key = '', $value = false, $bypass_cap = false ) {

		if ( ! current_user_can( 'manage_options' ) && ! $bypass_cap ) {
			return;
		}

		// If no key, exit.
		if ( empty( $key ) ) {
			return false;
		}

		if ( empty( $value ) ) {
			$remove_option = self::delete_option( $key );
			return $remove_option;
		}

		// First let's grab the current settings.
		$options = get_option( 'wcf_options', [] );

		/**
		 * Filter the final value of an option before being saved into the database.
		 *
		 * @param mixed $value the value about to be saved.
		 * @param string $key the key of the option that is being saved.
		 */
		$value = apply_filters( 'wcf_update_option', $value, $key );

		// Next let's try to update the value.
		$options[ $key ] = $value;
		$did_update      = update_option( 'wcf_options', $options );

		return $did_update;
	}

	/**
	 * Removes a setting value in the database.
	 *
	 * @param string $key         The Key to delete.
	 * @return boolean True if removed, false if not.
	 */
	public static function delete_option( $key = '' ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// If no key, exit.
		if ( empty( $key ) ) {
			return false;
		}

		// First let's grab the current settings.
		$options = get_option( 'wcf_options', [] );

		// Next let's try to update the value.
		if ( isset( $options[ $key ] ) ) {
			unset( $options[ $key ] );
		}

		$did_update = update_option( 'wcf_options', $options );

		return $did_update;
	}

	/**
	 * Prepare a key => value array to be used within the SelectControl
	 * react component.
	 *
	 * @param array $arr
	 * @return array
	 */
	public static function parse_array_for_select_control( $arr ) {

		$new_arr = [];

		foreach ( $arr as $value => $label ) {
			$new_arr[] = [
				'value' => $value,
				'label' => $label,
			];
		}

		return $new_arr;
	}

	/**
	 * Get the list of sources that filters are allowed to pick from.
	 *
	 * @return array
	 */
	public static function get_filter_sources_list() {
		$list = [
			'categories' => __( 'Category', 'woocommerce-product-filters' ),
			'attributes' => __( 'Attribute', 'woocommerce-product-filters' ),
			'colors'     => __( 'Color', 'woocommerce-product-filters' ),
			'tags'       => __( 'Tag', 'woocommerce-product-filters' ),
			'taxonomy'   => __( 'Custom taxonomy', 'woocommerce-product-filters' ),
			'price'      => __( 'Price', 'woocommerce-product-filters' ),
			'ratings'    => __( 'Rating', 'woocommerce-product-filters' ),
			'in_stock'   => __( 'In stock', 'woocommerce-product-filters' ),
			'on_sale'    => __( 'On sale', 'woocommerce-product-filters' ),
			'sorter'     => __( 'Sort by', 'woocommerce-product-filters' ),
			'search'     => __( 'Search', 'woocommerce-product-filters' )
		];

		$taxonomies = Products::get_registered_taxonomies();

		if ( count( $taxonomies ) === 2 && isset( $taxonomies['product_cat'] ) && isset( $taxonomies['product_tag'] ) ) {
			unset( $list['taxonomy'] );
		}

		/**
		 * Filter: makes the supported sources list filterable.
		 *
		 * @param array $list
		 * @return array
		 */
		return apply_filters( 'wcf_supported_sources', $list );
	}

	/**
	 * Get the list of supported filter types for the "Filter type"
	 * setting within the filters editor.
	 *
	 * The list also contains the type of "sources" that the field supports.
	 *
	 * @return array
	 */
	public static function get_supported_filter_types_list() {
		$types = [
			'dropdown'   => [
				'label'    => __( 'Dropdown', 'woocommerce-product-filters' ),
				'supports' => [ 'categories', 'attributes', 'taxonomy', 'tags' ],
			],
			'checkboxes' => [
				'label'    => __( 'Checkboxes', 'woocommerce-product-filters' ),
				'supports' => [ 'categories', 'attributes', 'taxonomy', 'tags' ]
			],
			'radio'      => [
				'label'    => __( 'Radio buttons', 'woocommerce-product-filters' ),
				'supports' => [ 'categories', 'attributes', 'taxonomy', 'tags' ]
			],
			'labels'     => [
				'label'    => __( 'Labels', 'woocommerce-product-filters' ),
				'supports' => [ 'categories', 'attributes', 'taxonomy', 'tags' ]
			],
			'images'     => [
				'label'    => __( 'Images', 'woocommerce-product-filters' ),
				'supports' => [ 'categories', 'attributes', 'taxonomy' ]
			],
			'range'      => [
				'label'    => __( 'Range slider', 'woocommerce-product-filters' ),
				'supports' => [ 'categories', 'attributes', 'taxonomy', 'tags' ]
			]
		];

		/**
		 * Filter: allows overriding of the label and supported sources of
		 * each input filter type.
		 *
		 * @param array $types
		 * @return array
		 */
		return apply_filters( 'wcf_supported_sources_filter_types', $types );
	}

	/**
	 * Get an array of filter groups ( id => name ).
	 * Usually used for dropdowns.
	 *
	 * @return array
	 */
	public static function get_groups_for_dropdown() {
		$groups = Group::all();
		$list   = [
			'' => __( 'None', 'woocommerce-product-filters' ),
		];

		if ( $groups instanceof Collection && ! $groups->isEmpty() ) {
			foreach ( $groups as $group ) {
				$list[ $group->getID() ] = $group->name;
			}
		}

		return $list;
	}

	/**
	 * Place an array at a given position.
	 *
	 * @param array $array
	 * @param string $key
	 * @param array $array_to_insert
	 * @return array
	 */
	public static function array_splice_after_key( $array, $key, $array_to_insert ) {
		$key_pos = array_search( $key, array_keys( $array ) );
		if ( $key_pos !== false ) {
			++$key_pos;
			$second_array = array_splice( $array, $key_pos );
			$array        = array_merge( $array, $array_to_insert, $second_array );
		}
		return $array;
	}

}
