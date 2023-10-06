<?php
/**
 * @package   Barn2\woocommerce-product-filters
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */

namespace Barn2\Plugin\WC_Filters\Model;

/**
 * Indicates that a filter is capable of filtering the WP query.
 */
interface Filterable_Interface {

	/**
	 * Return the formatted searched string attached to the filter.
	 *
	 * @return mixed
	 */
	public function get_search_query();

	/**
	 * Return the list of indexed data belonging to the filter.
	 *
	 * @return array
	 */
	public function find_posts();

}
