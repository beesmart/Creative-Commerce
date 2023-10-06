<?php
/**
 * @package   Barn2\woocommerce-product-filters
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */

namespace Barn2\Plugin\WC_Filters\Model\Filters;

use Barn2\Plugin\WC_Filters\Model\Filter;

/**
 * Search filter model.
 */
class Search extends Filter {

	/**
	 * @inheritdoc
	 */
	public function get_search_query() {
		return $this->search_query;
	}

}
