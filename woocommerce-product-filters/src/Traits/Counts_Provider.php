<?php
/**
 * @package   Barn2\woocommerce-product-filters
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */

namespace Barn2\Plugin\WC_Filters\Traits;

use Barn2\Plugin\WC_Filters\Dependencies\Illuminate\Support\Collection;
use Barn2\Plugin\WC_Filters\Model\Index;
use Barn2\Plugin\WC_Filters\Utils\Filters;

use function Barn2\Plugin\WC_Filters\wcf;

/**
 * Helper methods used by the "sale" and "stock" filters.
 * Retrieve counts of possible choices and provide
 * a filter so that results can be adjusted if needed.
 */
trait Counts_Provider {
	/**
	 * @inheritdoc
	 */
	public function get_choices_counts( array $post_ids, $filters = false, $prefilling = false ) {

		$db    = wcf()->get_service( 'db' );
		$count = [];

		/**
		 * Filter: allow developers to return a custom count for the
		 * filter when counting specific posts.
		 *
		 * @param boolean $bypass whether or not to return a custom value.
		 * @param Filter $filter
		 * @param array $post_ids
		 * @return string return a string to display a custom number.
		 */
		$bypass = apply_filters( 'wcf_all_choices_counts_bypass', false, $this, $post_ids );

		if ( $bypass !== false ) {
			return $bypass;
		}

		$query = Index::select( 'facet_value', $db::raw( 'COUNT(DISTINCT post_id) AS counter' ) )
			->where( 'filter_id', $this->getID() )
			->whereIn( 'post_id', $post_ids )
			->groupBy( 'facet_value' )
			->orderBy( 'counter', 'DESC' )
			->orderBy( 'facet_value', 'ASC' )
			->get();

		if ( $query instanceof Collection && $query->isNotEmpty() ) {
			return $query->first()->counter;
		}

		return $count;

	}

	/**
	 * @inheritdoc
	 */
	public function get_all_choices_counts( array $post_ids = [] ) {
		$db    = wcf()->get_service( 'db' );
		$count = [];

		if ( Filters::is_taxonomy_page() && ! empty( $post_ids ) ) {
			return $this->get_choices_counts( $post_ids );
		}

		/**
		 * Filter: allow developers to return a custom count for the
		 * filter when counting specific posts.
		 *
		 * @param boolean $bypass whether or not to return a custom value.
		 * @param Filter $filter
		 * @return string return a string to display a custom number.
		 */
		$bypass = apply_filters( 'wcf_all_choices_counts_bypass', false, $this );

		if ( $bypass !== false ) {
			return $bypass;
		}

		$query = Index::select( 'facet_value', $db::raw( 'COUNT(DISTINCT post_id) AS counter' ) )
			->where( 'filter_id', $this->getID() )
			->groupBy( 'facet_value' )
			->orderBy( 'counter', 'DESC' )
			->orderBy( 'facet_value', 'ASC' )
			->get();

		if ( $query instanceof Collection && ! empty( $query->first()->counter ) ) {
			return $query->first()->counter;
		}

		return $count;
	}
}
