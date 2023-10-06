<?php
/**
 * @package   Barn2\woocommerce-product-filters
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */

namespace Barn2\Plugin\WC_Filters\Traits;

use Barn2\Plugin\WC_Filters\Dependencies\Illuminate\Database\Eloquent\Collection;
use Barn2\Plugin\WC_Filters\Dependencies\Illuminate\Support\Collection as SupportCollection;
use Barn2\Plugin\WC_Filters\Model\Filters\Attribute;
use Barn2\Plugin\WC_Filters\Model\Filters\Sale;
use Barn2\Plugin\WC_Filters\Model\Filters\Sorter;
use Barn2\Plugin\WC_Filters\Model\Filters\Stock;
use Barn2\Plugin\WC_Filters\Model\Filters\Taxonomy;
use Barn2\Plugin\WC_Filters\Model\Index;
use Barn2\Plugin\WC_Filters\Utils\Filters;

use function Barn2\Plugin\WC_Filters\wcf;

/**
 * Adjusts the counting criteria of filters choices.
 *
 * For "checkboxes" types of filters, the user is allowed to select
 * one or more choices. When only one filter is used,
 * results are merged instead of being restricted to the
 * related selected choices.
 */
trait Prefilling_Aware {

	public function include_variations() {
		return isset( $this->with_variations ) ? $this->with_variations : false;
	}

	/**
	 * @inheritdoc
	 */
	public function get_choices_counts( array $post_ids, $filters = false, $prefilling = false ) {
		$db            = wcf()->get_service( 'db' );
		$count         = [];
		$other_filters = [];
		$post_ids      = apply_filters( 'wcf_get_choices_counts_post_ids', $post_ids, $this, $filters, $prefilling );

		if ( $filters instanceof SupportCollection ) {
			foreach ( $filters as $filter ) {

				if ( $filter instanceof Sorter || $filter instanceof Stock || $filter instanceof Sale ) {
					continue;
				}

				$other_filters[ $filter->slug ] = $filter->get_search_query();
			}
		}

		if ( $this instanceof Taxonomy || $this instanceof Attribute ) {
			$query = Index::select( 'facet_value', $db::raw( 'COUNT(DISTINCT post_id) AS counter' ) )
				->where( 'filter_id', $this->getID() )
				->groupBy( 'facet_value' )
				->orderBy( 'counter', 'DESC' )
				->orderBy( 'facet_value', 'ASC' );
		} else {
			$query = Index::select( 'term_id', $db::raw( 'COUNT(DISTINCT post_id) AS counter' ) )
				->where( 'filter_id', $this->getID() )
				->groupBy( 'term_id' )
				->orderBy( 'counter', 'DESC' )
				->orderBy( 'term_id', 'ASC' );
		}

		$other_filters = Filters::array_remove_empty( $other_filters );

		$should_restrict = $this->should_restrict( $prefilling, $other_filters ) || Filters::is_taxonomy_page() || $this->taxonomy_filter_should_restrict( $prefilling, $other_filters );

		if ( $should_restrict ) {
			$query->whereIn( 'post_id', $post_ids );
		}

		$query = $query->get();

		// If we need to include variations, we need to alter the counts.
		if ( $this->include_variations() && $should_restrict ) {
			$query2 = Index::select( 'facet_value', $db::raw( 'COUNT(DISTINCT post_id) AS counter' ) )
				->where( 'filter_id', $this->getID() )
				->whereIn( 'variation_id', $post_ids )
				->whereNotIn( 'post_id', $post_ids )
				->groupBy( 'facet_value' )
				->orderBy( 'counter', 'DESC' )
				->orderBy( 'facet_value', 'ASC' );

			$indexed_variations = $query2->get()->toArray();

			$query->transform(
				function ( $item, $key ) use ( $indexed_variations ) {
					$to_sum = false;

					foreach ( $indexed_variations as $indexed ) {
						if ( $indexed['facet_value'] === $item->facet_value ) {
							$to_sum = $indexed['counter'];
						}
					}

					if ( $to_sum ) {
						return $item->setAttribute( 'counter', absint( $to_sum ) + absint( $item->counter ) );
					}

					return $item;
				}
			);
		}

		if ( $query instanceof Collection ) {
			return $query;
		}

		return $count;
	}

	/**
	 * Checks if results should be restricted or merged.
	 *
	 * @param bool $prefilling
	 * @param array $other_filters
	 * @return boolean
	 */
	private function should_restrict( $prefilling, $other_filters = [] ) {

		$includes_this_filter      = isset( $other_filters[ $this->slug ] );
		$includes_only_this_filter = $includes_this_filter && count( $other_filters ) === 1;
		$includes_more_filters     = count( $other_filters ) > 1;

		if ( $prefilling && $includes_more_filters ) {
			return true;
		} elseif ( $prefilling && ! $includes_this_filter ) {
			return true;
		} elseif ( ! $includes_only_this_filter ) {
			return true;
		}

		return false;
	}

	/**
	 * Determine if filtering should be restricted to match all filters
	 * when one or more filter is enabled and is a checkbox.
	 *
	 * @param [type] $prefilling
	 * @param array $other_filters
	 * @return bool
	 */
	private function taxonomy_filter_should_restrict( $prefilling, $other_filters = [] ) {

		if (
			$this instanceof Taxonomy && $this->get_option( 'filter_type' ) !== 'checkboxes' ||
			$this instanceof Attribute && $this->get_option( 'filter_type' ) !== 'checkboxes'
		) {
			return true;
		} elseif ( $this->get_option( 'filter_type' ) === 'checkboxes' && $this->should_restrict( $prefilling, $other_filters ) ) {
			return true;
		}

		return false;
	}

}
