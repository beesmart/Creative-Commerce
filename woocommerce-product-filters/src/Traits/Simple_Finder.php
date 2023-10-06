<?php
/**
 * @package   Barn2\woocommerce-product-filters
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */

namespace Barn2\Plugin\WC_Filters\Traits;

use Barn2\Plugin\WC_Filters\Model\Index;
use Barn2\Plugin\WC_Filters\Utils\Filters;

/**
 * Handles finding of posts that don't require
 * particularly complex sql queries.
 *
 * This is currently used by the following filters:
 *
 * - Sale
 * - Stock
 * - Rating
 */
trait Simple_Finder {

	/**
	 * @inheritdoc
	 */
	public function find_posts() {
		$data = Index::select( 'post_id' )
			->distinct()
			->where( 'filter_id', $this->getID() )
			->where( 'facet_value', $this->get_search_query() )
			->get();

		return Filters::flatten_results( $data );
	}

}
