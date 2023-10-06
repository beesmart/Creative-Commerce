<?php
/**
 * @package   Barn2\woocommerce-product-filters
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */

namespace Barn2\Plugin\WC_Filters\Traits;

use Barn2\Plugin\WC_Filters\Dependencies\Illuminate\Support\Collection;
use Barn2\Plugin\WC_Filters\Model\Filter;
use Barn2\Plugin\WC_Filters\Model\Filterable_Interface;
use Barn2\Plugin\WC_Filters\Model\Filters\Attribute;
use Barn2\Plugin\WC_Filters\Model\Filters\Search;
use Barn2\Plugin\WC_Filters\Model\Filters\Taxonomy;
use Barn2\Plugin\WC_Filters\Utils\Filters;

trait Query_Aware {

	/**
	 * Determine if it's a query we should be filtering or not.
	 *
	 * @param \WP_Query $query
	 * @return boolean
	 */
	private function is_main_query( $query ) {
		$is_main_query = ( $query->is_main_query() || $query->is_archive || ( $query->is_tax && Filters::is_taxonomy_page() ) );
		$is_main_query = ( $query->is_singular || $query->is_feed ) ? false : $is_main_query;
		$is_main_query = ( $query->get( 'suppress_filters', false ) ) ? false : $is_main_query;
		$is_main_query = ( '' !== $query->get( 'woocommerce-filters' ) ) ? (bool) $query->get( 'woocommerce-filters' ) : $is_main_query;

		/**
		 * Filter: allows developers to adjust the conditions
		 * used to determine whether or not the query being processed
		 * is a WCF main query.
		 *
		 * @param bool $is_main_query
		 * @param \WP_Query $query
		 * @return bool
		 */
		return apply_filters( 'wcf_is_main_query', $is_main_query, $query );
	}

	/**
	 * Get the orderby parameter for prefilling.
	 *
	 * @return string|bool
	 */
	public function get_orderby() {

		$orderby = isset( $_GET['_wcf_orderby'] ) ? sanitize_text_field( $_GET['_wcf_orderby'] ) : false; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( empty( $orderby ) ) {
			$orderby = isset( $_GET['_orderby'] ) ? sanitize_text_field( $_GET['_orderby'] ) : false; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}

		return $orderby;
	}

	/**
	 * Query the database and find all post ids belonging to the selected filters.
	 *
	 * @return array
	 */
	private function get_filtered_post_ids( $custom_query = false ) {

		global $wp_query;

		$the_query = $wp_query;

		if ( $custom_query ) {
			$the_query = $custom_query;
		}

		// Only get relevant post IDs
		$args = array_merge(
			$the_query->query_vars,
			[
				'paged'                  => 1,
				'posts_per_page'         => -1,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				'cache_results'          => false,
				'no_found_rows'          => true,
				'nopaging'               => true, // prevent "offset" issues
				'woocommerce-filters'    => false,
				'fields'                 => 'ids',
			]
		);

		// If the filters collection contains a Search filter, we need to add the "s" parameter to the query.
		if ( $this->filters instanceof Collection ) {
			foreach ( $this->filters as $filter ) {
				if ( $filter instanceof Search ) {
					$args['s'] = $filter->get_search_query();
				}
			}
		}

		$query = new \WP_Query( $args );

		$post_ids = $query->posts;

		if ( $this->filters instanceof Collection ) {
			foreach ( $this->filters as $filter ) {
				if ( $filter instanceof Filterable_Interface ) {

					$matches = $filter->find_posts();

					$needles         = ( 'search' === $filter->filter_by ) ? $matches : $post_ids;
					$haystack        = ( 'search' === $filter->filter_by ) ? $post_ids : $matches;
					$haystack        = array_flip( $haystack );
					$intersected_ids = [];

					foreach ( $needles as $post_id ) {
						if ( isset( $haystack[ $post_id ] ) ) {
							$intersected_ids[] = $post_id;
						}
					}

					$post_ids = $intersected_ids;
				}
			}
		}

		/**
		 * Filter: allows developers to adjust the array of retrieved products
		 * after it has queried the database and found the appropriate results.
		 *
		 * @param array $post_ids products that have been found
		 * @param Collection $filters filters that have been used to retrieve the products
		 * @return array
		 */
		$post_ids = apply_filters( 'wcf_filtered_post_ids', $post_ids, $this->filters );

		$this->post_ids = $post_ids;
		$this->is_404   = empty( $post_ids ); // When no posts have been found, mark it as 404.

		return $post_ids;
	}

	/**
	 * Load the filters and attach values to them.
	 *
	 * @param Collection $requested_filters
	 * @return void
	 */
	private function process_filters( Collection $requested_filters ) {

		$requested_filters = $this->prepare_requested_filters_collection( $requested_filters );

		if ( $requested_filters->isEmpty() ) {
			return;
		}

		$filters = Filter::whereIn( 'slug', $requested_filters->keys() )->get();

		if ( empty( $filters ) ) {
			return;
		}

		$this->filters = $this->attach_search_query_to_filters( $filters );
	}

	/**
	 * Apply specific modifications to the collection keys and/or values.
	 *
	 * - Modify strings such as "true" or "false" to bools.
	 * - Remove any false (bool) value from the collection.
	 *
	 * @param Collection $collection
	 * @return Collection
	 */
	private function prepare_requested_filters_collection( Collection $collection ) {

		$filtered = $collection->transform(
			function( $item, $key ) {
				switch ( $item ) {
					case 'true':
						$item = true;
						break;
					case 'false':
					case null:
						$item = false;
						break;
				}
				return $item;
			}
		);

		$filtered = $filtered->reject(
			function( $value, $key ) {
				return $value === false || empty( $value );
			}
		);

		$filtered = $this->array_remove_empty( $filtered );

		return $filtered;
	}

	/**
	 * Recursively remove empty values from a collection.
	 *
	 * @param array $haystack
	 * @return array
	 */
	private function array_remove_empty( $haystack ) {
		foreach ( $haystack as $key => $value ) {
			if ( is_array( $value ) ) {
				$haystack[ $key ] = $this->array_remove_empty( $haystack[ $key ] );
			}

			if ( empty( $haystack[ $key ] ) ) {
				unset( $haystack[ $key ] );
			}
		}

		return $haystack;
	}

	/**
	 * Attach the value of the filter from the frontend request,
	 * to the `Filter` instance `search_query` attribute.
	 *
	 * This is then later used via the `get_search_query` method of the Filter.
	 *
	 * @param Collection $collection
	 * @return Collection
	 */
	private function attach_search_query_to_filters( Collection $collection ) {

		$filters_with_multiple_options = [
			Taxonomy::class,
			Attribute::class
		];

		$multiselectable = [
			'checkboxes',
			'labels',
			'images',
		];

		$collection->each(
			function( $instance ) use ( $filters_with_multiple_options, $multiselectable ) {
				$filter_class = get_class( $instance );

				if ( in_array( $filter_class, $filters_with_multiple_options, true ) && in_array( $instance->get_option( 'filter_type' ), $multiselectable, true ) ) {
					$value = $this->parameters->get( $instance->slug );

					if ( ! is_array( $value ) ) {
						$value = explode( ',', $value );
					}

					$instance->setAttribute( 'search_query', $value );
				} else {
					$instance->setAttribute( 'search_query', $this->parameters->get( $instance->slug ) );
				}
			}
		);

		return $collection;
	}

	/**
	 * If an orderby parameter was found within the request,
	 * update the query instance by injecting the
	 * appropriate sorting functions/parameters.
	 *
	 * @param \WP_Query $query
	 * @return void
	 */
	public function maybe_order_results( &$query ) {
		if ( empty( $this->orderby ) ) {
			return;
		}

		switch ( $this->orderby ) {
			case 'price':
				add_filter( 'posts_clauses', [ \WC()->query, 'order_by_price_asc_post_clauses' ] );
				break;
			case 'price-desc':
				add_filter( 'posts_clauses', [ \WC()->query, 'order_by_price_desc_post_clauses' ] );
				break;
			case 'date':
				$query->set( 'orderby', 'date ID' );
				$query->set( 'order', 'desc' );
				break;
			case 'rating':
				add_filter( 'posts_clauses', [ \WC()->query, 'order_by_rating_post_clauses' ] );
				break;
			case 'popularity':
				add_filter( 'posts_clauses', [ \WC()->query, 'order_by_popularity_post_clauses' ] );
				break;
			case 'menu_order':
				$query->set( 'orderby', 'menu_order title' );
				break;
		}
	}

	/**
	 * If a filter was found within the request,
	 * update the query instance by injecting the
	 * appropriate search query.
	 *
	 * @param \WP_Query $wp_query
	 * @return void
	 */
	public function maybe_insert_search_query( &$wp_query ) {
		/** @var Collection $filters */
		$filters = $this->filters;

		// Check if the collection contains an item that is the instance of Sarch
		// and if the search query is not empty.
		if ( $filters->contains(
			function( $item, $key ) {
				return $item instanceof Search && ! empty( $item->get_search_query() );
			}
		) ) {
			$wp_query->set(
				's',
				$filters->first(
					function( $item, $key ) {
						return $item instanceof Search && ! empty( $item->get_search_query() );
					}
				)->get_search_query()
			);
		}
	}

}
