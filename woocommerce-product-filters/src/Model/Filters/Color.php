<?php
/**
 * @package   Barn2\woocommerce-product-filters
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */

namespace Barn2\Plugin\WC_Filters\Model\Filters;

use Barn2\Plugin\WC_Filters\Dependencies\Illuminate\Database\Eloquent\Collection;
use Barn2\Plugin\WC_Filters\Model\Countable_Interface;
use Barn2\Plugin\WC_Filters\Model\Filter;
use Barn2\Plugin\WC_Filters\Model\Filterable_Interface;
use Barn2\Plugin\WC_Filters\Model\Index;
use Barn2\Plugin\WC_Filters\Model\Indexable_Interface;
use Barn2\Plugin\WC_Filters\Model\Preloadable_Interface;
use Barn2\Plugin\WC_Filters\Model\Storable_Interface;
use Barn2\Plugin\WC_Filters\Traits\Prefilling_Aware;
use Barn2\Plugin\WC_Filters\Traits\Search_Query_Array_Aware;
use Barn2\Plugin\WC_Filters\Utils\Filters;
use Barn2\Plugin\WC_Filters\Utils\Terms;

use function Barn2\Plugin\WC_Filters\wcf;

/**
 * Represents a color attribute filter.
 */
class Color extends Filter implements Indexable_Interface, Storable_Interface, Filterable_Interface, Countable_Interface, Preloadable_Interface {

	use Prefilling_Aware;
	use Search_Query_Array_Aware;

	/**
	 * @inheritdoc
	 */
	public function getInputNameAttribute() {
		return __( 'Color picker', 'woocommerce-product-filters' );
	}

	/**
	 * @inheritdoc
	 */
	public function generate_index_data( array $defaults, string $post_id ) {

		$output   = [];
		$taxonomy = $this->get_option( 'color_attribute' );

		if ( $taxonomy ) {
			$output = Filters::generate_taxonomy_index_data( $defaults, $post_id, "pa_{$taxonomy}" );
		}

		return $output;
	}

	/**
	 * @inheritdoc
	 */
	public function get_json_store_data() {

		$args = array_merge(
			[
				'hide_empty' => false
			],
			$this->get_attribute_orderby_args( $this->get_option( 'color_attribute' ) )
		);

		$terms = get_terms(
			wc_attribute_taxonomy_name( $this->get_option( 'color_attribute' ) ),
			$args
		);

		$formatted_list = [];

		if ( ! empty( $terms ) ) {
			foreach ( $terms as $term ) {
				if ( $term instanceof \WP_Term ) {
					$formatted_list[] = [
						'term_id' => absint( $term->term_id ),
						'name'    => $term->name,
						'color'   => Terms::get_color( $term->term_id )
					];
				}
			}
		}

		return $formatted_list;
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

		$query = Index::select( 'term_id', $db::raw( 'COUNT(DISTINCT post_id) AS counter' ) )
			->where( 'filter_id', $this->getID() )
			->groupBy( 'term_id' )
			->orderBy( 'counter', 'DESC' )
			->orderBy( 'term_id', 'ASC' )
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
		$terms = $this->get_search_query();

		if ( is_array( $terms ) && count( $terms ) === 1 ) {
			$terms = explode( ',', $terms[0] );
		}

		$data = Index::select( 'post_id' )
				->distinct()
				->where( 'filter_id', $this->getID() )
				->whereIn( 'term_id', $terms )
				->get();

		return Filters::flatten_results( $data );
	}

}
