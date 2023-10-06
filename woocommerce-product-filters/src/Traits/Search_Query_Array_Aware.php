<?php
/**
 * @package   Barn2\woocommerce-product-filters
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */

namespace Barn2\Plugin\WC_Filters\Traits;

trait Search_Query_Array_Aware {

	/**
	 * @inheritdoc
	 */
	public function get_search_query() {
		return is_array( $this->search_query ) ? $this->search_query : [ $this->search_query ];
	}

}
