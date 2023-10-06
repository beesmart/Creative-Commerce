<?php
/**
 * @package   Barn2\woocommerce-product-filters
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */

namespace Barn2\Plugin\WC_Filters;

use Barn2\Plugin\WC_Filters\Dependencies\Illuminate\Support\Collection;
use Barn2\Plugin\WC_Filters\Traits\Params_Provider;
use Barn2\Plugin\WC_Filters\Traits\Query_Aware;
use Barn2\Plugin\WC_Filters\Utils\Filters;
use Barn2\Plugin\WC_Filters\Dependencies\Lib\Registerable;
use JsonSerializable;

/**
 * Handles prefilling of the query.
 */
class Prefiller implements Registerable, JsonSerializable {

	use Query_Aware;
	use Params_Provider;

	/**
	 * URL parameters request.
	 *
	 * @var Collection
	 */
	public $parameters;

	/**
	 * List of filters loaded based on the prefilling parameters.
	 *
	 * @var Collection
	 */
	public $filters;

	/**
	 * Orderby parameter for the query.
	 *
	 * @var string
	 */
	public $orderby;

	/**
	 * Paged parameter for the query.
	 *
	 * @var string|int
	 */
	public $paged;

	/**
	 * Collection of post ids that has been found.
	 *
	 * @var array
	 */
	public $post_ids = [];

	/**
	 * Whether or not the prefiller should run.
	 *
	 * @var boolean
	 */
	protected $run = true;

	/**
	 * Hook the service.
	 *
	 * @return void
	 */
	public function register() {
		$action = isset( $_POST['action'] ) ? sanitize_key( $_POST['action'] ) : false; //phpcs:ignore

		if ( $action === 'wcf_fetch_data' ) {
			return;
		}

		$this->parameters = new Collection( wc_clean( $_GET ) );
		$this->init();
	}

	/**
	 * Initialize the service.
	 *
	 * @return void
	 */
	public function init() {
		if ( ! $this->parameters->has( '_wcf_filter' ) && ! $this->parameters->has( '_wcf_search' ) && empty( $this->get_orderby() ) ) {
			return;
		}

		// It's a fallback request.
		if ( $this->parameters->has( '_wcf_search' ) ) {
			$this->parameters = $this->parse_request( wc_clean( rawurldecode( $_GET['_wcf_search'] ) ) );
		}

		// Check paged parameter.
		if ( $this->parameters->has( '_paged' ) ) {
			$this->paged = $this->parameters->get( '_paged' );
		}

		$this->orderby = $this->get_orderby();

		if ( ! empty( $this->parameters ) ) {
			$this->process_filters( $this->parameters );
		}

		add_action( 'pre_get_posts', [ $this, 'update_query_vars' ], 20 );
		add_action( 'wp_enqueue_scripts', [ $this, 'assets' ] );
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
	 * Attach the json on the frontend.
	 *
	 * @return void
	 */
	public function assets() {
		wp_add_inline_script( Display::IDENTIFIER, 'const WCF_Prefiller = ' . wp_json_encode( $this ), 'before' );
	}

	/**
	 * Update the query parameters with the prefelling results.
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
		$query->set( 'woocommerce-filters-prefilled', true );
		$query->set( 'woocommerce-filters-bypass', true );
		$post_ids = [];

		// Only set the post-ids if the collection isn't empty.
		if ( $this->filters instanceof Collection && $this->filters->isNotEmpty() ) {
			// Reset the pagination if we've got results.
			$this->paged = false;

			$post_ids = $this->get_filtered_post_ids( $query );

			if ( empty( $post_ids ) || ! is_array( $post_ids ) ) {
				$query->set_404();
			}

			if ( ! empty( $post_ids ) || ! is_array( $post_ids ) ) {
				$query->set( 'post__in', $post_ids );
			}

			$this->maybe_insert_search_query( $query );
		}

		if ( ! empty( $this->paged ) && is_numeric( $this->paged ) ) {
			$query->set( 'paged', absint( $this->paged ) );
		}

		$this->maybe_order_results( $query );

		/**
		 * Hook: allow developers to add custom logic while filtering a prefilled query.
		 *
		 * @param \WP_Query $query WP query instance
		 * @param Collection $filters filters collection
		 * @param string $orderby order parameter
		 * @param array $post_ids list of filtered posts.
		 */
		do_action( 'wcf_prefilled_query', $query, $this->filters, $this->orderby, $post_ids );
	}

	/**
	 * Load the prefilling parameters for the counters request.
	 *
	 * @return array
	 */
	#[\ReturnTypeWillChange]
	public function jsonSerialize() {
		return [
			'prefill'   => $this->filters->toArray(),
			'post_ids'  => $this->post_ids,
			'paged'     => absint( $this->paged ),
			'restNonce' => wp_create_nonce( 'wp_rest' ),
			'rest'      => trailingslashit( get_rest_url() . Api::API_NAMESPACE ) . 'counters',
			'values'    => $this->prepare_url_params(),
			'orderby'   => $this->orderby,
			'run'       => $this->should_run(),
		];
	}

	/**
	 * Determine whether or not the prefiller ajax request should run.
	 * This request updates the counters automatically on page load.
	 *
	 * @return boolean
	 */
	public function should_run() {
		return $this->run;
	}

	/**
	 * Set whether or not the automated counters update ajax request
	 * should run.
	 *
	 * @param boolean $run
	 * @return self
	 */
	public function set_should_run( bool $run ) {
		$this->run = $run;

		return $this;
	}

}
