<?php
/**
 * @package   Barn2\woocommerce-product-filters
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */

namespace Barn2\Plugin\WC_Filters\Model\Filters;

use Barn2\Plugin\WC_Filters\Model\Filter;
use Barn2\Plugin\WC_Filters\Model\Filterable_Interface;
use Barn2\Plugin\WC_Filters\Model\Indexable_Interface;
use Barn2\Plugin\WC_Filters\Traits\Simple_Finder;

/**
 * Represents a rating filter.
 */
class Rating extends Filter implements Indexable_Interface, Filterable_Interface {

	use Simple_Finder;

	/**
	 * @inheritdoc
	 */
	public function generate_index_data( array $defaults, string $post_id ) {
		$output  = [];
		$product = wc_get_product( $post_id );

		if ( ! $product ) {
			return $output;
		}

		$rating = $product->get_average_rating();

		$defaults['facet_value']         = $rating;
		$defaults['facet_display_value'] = $rating;

		$params   = $defaults;
		$output[] = $params;

		return $output;

	}

	/**
	 * @inheritdoc
	 */
	public function get_search_query() {
		$value = "{$this->search_query}.00";

		if ( $value === '.00' ) {
			return;
		}

		return $value;
	}

}
