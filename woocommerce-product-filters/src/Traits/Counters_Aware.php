<?php
/**
 * @package   Barn2\woocommerce-product-filters
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */

namespace Barn2\Plugin\WC_Filters\Traits;

use Barn2\Plugin\WC_Filters\Api;
use Barn2\Plugin\WC_Filters\Dependencies\Illuminate\Support\Collection;
use Barn2\Plugin\WC_Filters\Model\Countable_Interface;
use Barn2\Plugin\WC_Filters\Model\Filter;
use Barn2\Plugin\WC_Filters\Model\Preloadable_Interface;
use Barn2\Plugin\WC_Filters\Utils\Filters;

/**
 * Common methods used by various classes to handle
 * the counters for filters.
 */
trait Counters_Aware {

	/**
	 * Loop through the available filters and print a list
	 * of counters for each available choice indexed by the filter.
	 *
	 * @return array
	 */
	public function get_counts( $filters = false, $values = [] ) {
		$counts  = [];
		$filters = Filter::all();

		if ( ! empty( $values ) ) {
			$filters = Api::inject_values_into_filters( $filters, $values ); //phpcs:ignore
		}

		$is_taxonomy_page       = Filters::is_taxonomy_page();
		$current_query_post_ids = $is_taxonomy_page ? Filters::get_current_query_object_ids() : [];

		if ( ! empty( $filters ) ) {
			foreach ( $filters as $filter ) {
				if (
					$this->reset
					|| ( ! $this->reset && ! empty( $this->paged ) && empty( $this->filters ) )
					|| ( ! $this->reset && empty( $this->filters ) && ! empty( $this->orderby ) )
					|| ( ! $this->reset && empty( $this->filters ) && empty( $this->orderby ) )
					|| ( ! $this->reset && $this->filters instanceof Collection && $this->filters->isEmpty() )
				) {
					if ( $filter instanceof Preloadable_Interface ) {
						$counts[ $filter->slug ] = $filter->get_all_choices_counts( $current_query_post_ids );
					}
				} else {
					if ( $filter instanceof Countable_Interface ) {
						$counts[ $filter->slug ] = $filter->get_choices_counts( $this->post_ids, $filters );
					}
				}
			}
		}

		return $counts;
	}

	/**
	 * Use the WC template to generate the results counter.
	 *
	 * @return string
	 */
	public function generate_result_count( $query = false, $post_ids = [], $filters = null ) {

		$total = wc_get_loop_prop( 'total' );

		if ( ! empty( $post_ids ) ) {
			$total = count( $post_ids );
		}

		if ( empty( $post_ids ) ) {
			$total = 0;
		} elseif ( ! empty( $post_ids ) ) {
			$total = count( $post_ids );
		}

		if ( $filters === null && $total === 0 ) {
			$total = wc_get_loop_prop( 'total' );
		}

		$args = [
			'total'    => $total,
			'per_page' => wc_get_loop_prop( 'per_page' ),
			'current'  => wc_get_loop_prop( 'current_page' ),
		];

		/**
		 * Filter: allows developers to adjust the arguments
		 * used to generate the WooCommerce result-count template
		 * output.
		 *
		 * @param array $args
		 * @param bool|\WP_Query $query optional wp query instance that might be sent through in some cases.
		 * @param array $post_ids list of post ids that might be sent through in some cases.
		 * @param null|Collection $filters list of filters possibly sent through during request.
		 * @return array
		 */
		$args = apply_filters( 'wcf_results_count', $args, $query, $post_ids, $this->filters );

		ob_start();

		wc_get_template( 'filter-result-count.php', $args );

		$js_field_html = ob_get_clean();

		return esc_js( str_replace( "\n", '', $js_field_html ) );
	}

}
