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
use Barn2\Plugin\WC_Filters\Model\Filters\Attribute;
use Barn2\Plugin\WC_Filters\Model\Filters\Taxonomy;
use Barn2\Plugin\WC_Filters\Traits\Counters_Aware;
use Barn2\Plugin\WC_Filters\Traits\Params_Provider;
use Barn2\Plugin\WC_Filters\Traits\Query_Aware;
use Barn2\Plugin\WC_Filters\Utils\Filters;
use Barn2\Plugin\WC_Filters\Utils\Products;
use Barn2\Plugin\WC_Filters\Utils\Responses;
use Barn2\Plugin\WC_Filters\Dependencies\Lib\Registerable;

/**
 * Responsible of handling query filtering requests.
 */
class Request implements Registerable {

	use Counters_Aware;
	use Params_Provider;
	use Query_Aware;

	/**
	 * Collection of the submitted values through the search form.
	 *
	 * @var Collection
	 */
	protected $request;

	/**
	 * Collection of the filter models loaded.
	 * This list contains ONLY the filters for which
	 * the user as selected values.
	 *
	 * @var Collection
	 */
	protected $filters;

	/**
	 * Holds the paged argument for the query.
	 *
	 * @var bool|int
	 */
	protected $paged = false;

	/**
	 * Whether or not the request is a reset request.
	 *
	 * @var boolean
	 */
	protected $reset = false;

	/**
	 * List of post ids found.
	 *
	 * @var array
	 */
	protected $post_ids = [];

	/**
	 * Holds the sorting method of the query.
	 *
	 * @var boolean|string
	 */
	protected $orderby = false;

	/**
	 * Determine if the filtering request produced no results.
	 *
	 * @var boolean
	 */
	protected $is_404 = false;

	/**
	 * Collection of requested parameters.
	 *
	 * @var Collection
	 */
	protected $parameters;

	/**
	 * Hook into WP.
	 *
	 * @return void
	 */
	public function register() {
		$this->intercept();
	}

	/**
	 * Intercept the search request, load the hooks and buffer the output.
	 *
	 * @return void
	 */
	public function intercept() {
		$action = isset( $_POST['action'] ) ? sanitize_key( $_POST['action'] ) : false; //phpcs:ignore

		if ( $action !== 'wcf_fetch_data' ) {
			return;
		}

		// Grab the request into a collection so that it's easier to work with.
		$this->parameters = new Collection( $_POST );

		$this->paged       = $this->get_paged();
		$this->reset       = $this->is_reset();
		$this->orderby     = $this->get_orderby();
		$requested_filters = $this->get_filters(); //phpcs:ignore

		if ( ! empty( $requested_filters ) ) {
			$this->process_filters( $requested_filters );
		}

		add_filter( 'show_admin_bar', '__return_false' );

		add_action( 'pre_get_posts', [ $this, 'update_query_vars' ], 999 );

		add_action( 'shutdown', [ $this, 'inject_template' ], 0 );
		ob_start();
	}

	/**
	 * Get the paged parameter of the request.
	 *
	 * @return int|bool
	 */
	private function get_paged() {
		return ! empty( $this->parameters->get( '_paged' ) ) ? absint( $this->parameters->get( '_paged' ) ) : false;
	}

	/**
	 * Determine if this was a reset request.
	 *
	 * @return boolean
	 */
	private function is_reset() {
		return $this->parameters->get( 'reset' ) === 'true';
	}

	/**
	 * Determine if an orderby parameter was provided.
	 *
	 * @return string|bool
	 */
	private function get_orderby() {
		return $this->parameters->has( 'sorting' ) ? $this->parameters->get( 'sorting' ) : false;
	}

	/**
	 * Get the list of filters requested.
	 *
	 * @param array $filters_list optional explicit filters list.
	 * @return Collection
	 */
	public function get_filters( array $filters_list = [] ) {
		$filters = [];

		if ( ! empty( $filters_list ) ) {
			$filters = $filters_list;
		} else {
			$filters = wc_clean( json_decode( $_POST['filters'], true ) );
		}

		return ( new Collection( $filters ) )->except( 'action' );
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

		$this->request = $requested_filters;
		$filters       = Filter::whereIn( 'slug', $requested_filters->keys() )->get();

		if ( empty( $filters ) ) {
			return;
		}

		$this->filters = $this->attach_search_query_to_filters( $filters );
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
					$value = $this->request->get( $instance->slug );

					if ( ! is_array( $value ) ) {
						$value = explode( ',', $value );
					}

					$instance->setAttribute( 'search_query', $value );
				} else {
					$instance->setAttribute( 'search_query', $this->request->get( $instance->slug ) );
				}
			}
		);

		return $collection;
	}

	/**
	 * Inject the list of found post ids into the query.
	 *
	 * @param \WP_Query $query
	 * @return void
	 */
	public function update_query_vars( $query ) {

		if ( ( is_admin() && ! wp_doing_ajax() ) || ! $this->is_main_query( $query ) ) {
			return;
		}

		if ( $query->get( 'woocommerce-filters-bypass' ) === true ) {
			return;
		}

		if ( ! empty( $query->get( 'post_type' ) ) && ! Filters::query_has_product_post_type( $query ) ) {
			return;
		}

		$query->set( 'woocommerce-filters', true );

		// Only set the post-ids if the collection isn't empty.
		if ( $this->filters instanceof Collection && $this->filters->isNotEmpty() ) {
			// Reset the pagination if we've got results.
			$this->paged = false;

			$post_ids = $this->get_filtered_post_ids( $query );

			if ( empty( $post_ids ) || ! is_array( $post_ids ) ) {
				$this->is_404 = true;
			}

			if ( ! empty( $post_ids ) || ! is_array( $post_ids ) ) {
				$query->set( 'post__in', $post_ids );
			}

			$this->maybe_insert_search_query( $query );
		}

		$this->maybe_order_results( $query );

		if ( $this->paged ) {
			$query->set( 'paged', $this->paged );
		}
	}

	/**
	 * Get the appropriate count for the found posts property.
	 *
	 * @param \WP_Query $wp_query
	 * @return int
	 */
	public function get_found_posts( $wp_query ) {
		$found = 0;

		if ( $this->is_404 === true ) {
			return $found;
		}

		if ( absint( $wp_query->found_posts ) === count( $this->post_ids ) ) {
			$found = $wp_query->found_posts;
		} elseif ( $wp_query->found_posts && ! empty( $this->post_ids ) && $wp_query->found_posts !== count( $this->post_ids ) ) {
			$found = count( $this->post_ids );
		} else {
			$found = $wp_query->found_posts;
		}

		return $found;
	}

	/**
	 * Send the output back via json.
	 *
	 * @return void
	 */
	public function inject_template() {

		global $wp_query;

		$html = ob_get_clean();

		preg_match( '/<body(.*?)>(.*?)<\/body>/s', $html, $matches );

		if ( ! empty( $matches ) ) {
			$html = trim( $matches[2] );
		}

		$values = $this->request instanceof Collection ? $this->request->toArray() : [];

		wp_send_json(
			[
				'output'          => Products::get_string_between( $html, '<!--wcf-loop-start-->', '<!--wcf-loop-end-->' ),
				'found_posts'     => $this->get_found_posts( $wp_query ),
				'paged'           => empty( $wp_query->get( 'paged' ) ) ? 1 : $wp_query->get( 'paged' ),
				'posts_per_page'  => $wp_query->get( 'posts_per_page' ),
				'offset'          => $wp_query->get( 'offset' ),
				'counts'          => $this->get_counts( $this->filters, $values ),
				'result_count'    => $this->generate_result_count( $wp_query, $this->post_ids, $this->filters ),
				'url_params'      => $this->prepare_url_params(),
				'is_404'          => $this->is_404,
				'no_products_tpl' => $this->is_404 ? Responses::generate_no_products_template() : false,
				'orderby'         => $this->orderby,
				'reset'           => $this->reset,
				'ids'             => $this->post_ids,
			]
		);
	}

}
