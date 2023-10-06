<?php
/**
 * @package   Barn2\woocommerce-product-filters
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */

namespace Barn2\Plugin\WC_Filters\Traits;

use Barn2\Plugin\WC_Filters\Dependencies\Illuminate\Support\Collection;

trait Params_Provider {

	/**
	 * Prepare the collection of parameters that will then be injected into the url.
	 *
	 * @return array
	 */
	public function prepare_url_params() {
		if ( ! $this->filters instanceof Collection || ( $this->filters instanceof Collection && $this->filters->isEmpty() ) ) {
			return [];
		}

		return $this->filters
			->keyBy( 'slug' )
			->map(
				function ( $item ) {
					return $item->search_query;
				}
			)
			->toArray();
	}

}
