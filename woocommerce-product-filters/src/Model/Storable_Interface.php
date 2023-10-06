<?php
/**
 * @package   Barn2\woocommerce-product-filters
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */

namespace Barn2\Plugin\WC_Filters\Model;

/**
 * Represents a filter that should attach data to the frontend react data store.
 */
interface Storable_Interface {

	/**
	 * Get the json data to attach to the frontend react data store.
	 *
	 * @return mixed
	 */
	public function get_json_store_data();

}
