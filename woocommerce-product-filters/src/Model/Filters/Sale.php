<?php
/**
 * @package   Barn2\woocommerce-product-filters
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */

namespace Barn2\Plugin\WC_Filters\Model\Filters;

use Barn2\Plugin\WC_Filters\Model\Countable_Interface;
use Barn2\Plugin\WC_Filters\Model\Filter;
use Barn2\Plugin\WC_Filters\Model\Filterable_Interface;
use Barn2\Plugin\WC_Filters\Model\Indexable_Interface;
use Barn2\Plugin\WC_Filters\Model\Preloadable_Interface;
use Barn2\Plugin\WC_Filters\Traits\Counts_Provider;
use Barn2\Plugin\WC_Filters\Traits\Simple_Finder;

/**
 * Represents the "on sale" filter.
 */
class Sale extends Filter implements Indexable_Interface, Filterable_Interface, Countable_Interface, Preloadable_Interface {

	use Simple_Finder;
	use Counts_Provider;

	/**
	 * @inheritdoc
	 */
	public function generate_index_data( array $defaults, string $post_id ) {

		$output  = [];
		$product = wc_get_product( $post_id );

		if ( ! $product ) {
			return $output;
		}

		if ( $product->is_on_sale() ) {
			$defaults['facet_value']         = 1;
			$defaults['facet_display_value'] = __( 'On sale', 'woocommerce-product-filters' );

			$params   = $defaults;
			$output[] = $params;
		}

		return $output;

	}

	/**
	 * @inheritdoc
	 */
	public function get_search_query() {
		return 1;
	}

}
