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
use Barn2\Plugin\WC_Filters\Dependencies\Illuminate\Support\Collection;

use function Barn2\Plugin\WC_Filters\wcf;

/**
 * Common methods shared between the Taxonomy and Attribute model.
 */
trait Taxonomy_Counts_Provider {
	/**
	 * @inheritdoc
	 */
	public function get_all_choices_counts( array $post_ids = [] ) {
		$db    = wcf()->get_service( 'db' );
		$count = [];

		if ( Filters::is_taxonomy_page() && ! empty( $post_ids ) ) {
			return $this->get_choices_counts( $post_ids );
		}

		$query = Index::select( 'facet_value', $db::raw( 'COUNT(DISTINCT post_id) AS counter' ) )
			->where( 'filter_id', $this->getID() )
			->groupBy( 'facet_value' )
			->orderBy( 'counter', 'DESC' )
			->orderBy( 'facet_value', 'ASC' )
			->get();

		if ( $query instanceof Collection ) {
			return $query;
		}

		return $count;
	}

	/**
	 * @inheritdoc
	 */
	public function find_posts() {
		$db       = wcf()->get_service( 'db' );
		$selected = $this->get_search_query();

		if ( count( $selected ) > 1 && $this->get_option( 'filter_type' ) !== 'checkboxes' ) {

			$data = Index::select( 'post_id' )
				->distinct()
				->where( 'filter_id', $this->getID() )
				->whereIn( 'term_id', $this->get_search_query() )
				->groupBy( 'post_id' )
				->having( $db::raw( 'count(distinct term_id)' ), '=', count( $this->get_search_query() ) )
				->get();

		} elseif ( $this->get_option( 'filter_type' ) === 'range' ) {

			$value = explode( ',', $selected[0] );

			$data = Index::select( 'post_id' )
				->distinct()
				->where( 'filter_id', $this->getID() )
				->whereBetween( $db::raw( 'CAST(facet_value AS SIGNED)' ), [ absint( $value[0] ), absint( $value[1] ) ] )
				->get();

		} else {

			$data = Index::select( 'post_id' )
				->distinct()
				->where( 'filter_id', $this->getID() )
				->whereIn( 'term_id', $this->get_search_query() )
				->get();

		}

		return Filters::flatten_results( $data );
	}
}
