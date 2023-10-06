<?php
/**
 * @package   Barn2\woocommerce-product-filters
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */

namespace Barn2\Plugin\WC_Filters;

use Barn2\Plugin\WC_Filters\Dependencies\Illuminate\Support\Collection;
use Barn2\Plugin\WC_Filters\Model\Filter;
use Barn2\Plugin\WC_Filters\Model\Filterable_Interface;
use Barn2\Plugin\WC_Filters\Model\Filters\Search;

/**
 * Helper class that handles integration
 * of third party plugins and themes that cannot
 * support the ajax filtering.
 */
class Request_Fallback {

	/**
	 * Sanitize collection of parsed and requested parameters.
	 *
	 * @var Collection
	 */
	public $parameters;

	/**
	 * Collection of retrieved filters instances.
	 *
	 * @var Collection
	 */
	public $filters;

	/**
	 * WP Query instance.
	 *
	 * @var object
	 */
	public $query;

	/**
	 * Holds the orderby parameter.
	 *
	 * @var string
	 */
	public $orderby = null;

	/**
	 * List of post ids retrieved.
	 *
	 * @var array
	 */
	protected $post_ids = [];

	/**
	 * Initialize the fallback request handler.
	 *
	 * @param string $parameters
	 * @param string $orderby
	 */
	public function __construct( string $parameters = null, string $orderby = null ) {
		if ( ! empty( $parameters ) ) {
			$this->parameters = $this->parse_request( $parameters );
		}

		if ( ! empty( $orderby ) ) {
			$this->orderby = $orderby;
		}
	}

	/**
	 * Programmatically set the parameters for the fallback request.
	 *
	 * @param Collection $parameters
	 * @return self
	 */
	public function set_parameters( Collection $parameters ) {
		$this->parameters = $parameters;

		return $this;
	}

	/**
	 * Parses string as if it were the query string passed via
	 * a URL and sets variables in the current scope.
	 *
	 * @param string $string
	 * @return Collection
	 */
	public function parse_request( string $string ) {
		$output = [];

		parse_str( urldecode( $string ), $output );

		return new Collection( wc_clean( $output ) );
	}

	/**
	 * Set the query that we're working with.
	 *
	 * @param \WP_Query $query
	 * @return self
	 */
	public function set_wp_query( \WP_Query $query ) {
		$this->query = $query;
		return $this;
	}

	/**
	 * Gr
	 *
	 * @return self
	 */
	public function load_filters() {

		$requested_filters = $this->prepare_requested_filters_collection( $this->parameters );

		if ( $requested_filters->isEmpty() ) {
			return $this;
		}

		$filters = Filter::whereIn( 'slug', $requested_filters->keys() )->get();

		if ( empty( $filters ) ) {
			return $this;
		}

		$this->filters = $this->attach_search_query_to_filters( $filters );

		return $this;
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
	 * Apply specific modifications to the collection keys and/or values.
	 *
	 * - Modify strings such as "true" or "false" to bools.
	 * - Remove any false (bool) value from the collection.
	 *
	 * @param Collection $collection
	 * @return Collection
	 */
	public function prepare_requested_filters_collection( Collection $collection ) {

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
	 * Attach the value of the filter from the frontend request,
	 * to the `Filter` instance `search_query` attribute.
	 *
	 * This is then later used via the `get_search_query` method of the Filter.
	 *
	 * @param Collection $collection
	 * @return Collection
	 */
	private function attach_search_query_to_filters( Collection $collection ) {

		$collection->each(
			function( $instance ) {
				$instance->setAttribute( 'search_query', $this->parameters->get( $instance->slug ) );
			}
		);

		return $collection;
	}

	/**
	 * Update the query instance and inject the found posts.
	 *
	 * @param \WP_Query $query
	 * @param array $args additional args that can be merged within the get_filtered_post_ids query args.
	 * @return void
	 */
	public function update_query_vars( &$query, $args = [] ) {

		// Only set the post-ids if the collection isn't empty.
		if ( $this->filters instanceof Collection && $this->filters->isNotEmpty() ) {
			$post_ids = $this->get_filtered_post_ids( $query, $args );

			if ( ! empty( $post_ids ) || ! is_array( $post_ids ) ) {
				$query->set( 'post__in', $post_ids );
			}

			// Set a nonsensical value when no posts are found.
			if ( empty( $post_ids ) ) {
				$query->set( 'post__in', [ 0 ] );
			}
		}
	}

	/**
	 * Query the database and find all post ids belonging to the selected filters.
	 *
	 * @param \WP_Query $the_query
	 * @param array $additional_args additional args that can be merged within the get_filtered_post_ids query args.
	 * @return array
	 */
	private function get_filtered_post_ids( $the_query, $additional_args = [] ) {

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
				'fields'                 => 'ids',
			]
		);

		if ( ! empty( $additional_args ) ) {
			$args = array_merge( $args, $additional_args );
		}

		// If the filters collection contains a Search filter, we need to add the "s" parameter to the query.
		if ( $this->filters instanceof Collection ) {
			foreach ( $this->filters as $filter ) {
				if ( $filter instanceof Search ) {
					$args['s'] = $filter->get_search_query();
				}
			}
		}

		/**
		 * Filter: allows developers to adjust the arguments for the
		 * query that loads the filtered results.
		 *
		 * @param array $args
		 * @return array
		 */
		$args     = apply_filters( 'wcf_filtered_post_ids_query_args', $args );
		$query    = new \WP_Query( $args );
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

		$this->post_ids = $post_ids;

		return $post_ids;
	}

	/**
	 * Get the list of found post ids.
	 *
	 * @return array
	 */
	public function get_found_post_ids() {
		return $this->post_ids;
	}

	/**
	 * Programmatically set the orderby parameter for the fallback handler.
	 *
	 * @param string $orderby
	 * @return self
	 */
	public function set_orderby( string $orderby ) {
		$this->orderby = $orderby;

		return $this;
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
	 * Prepare the collection of parameters that will then be used
	 * to display the active filters list.
	 *
	 * @return array
	 */
	public function get_active_filters() {
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
