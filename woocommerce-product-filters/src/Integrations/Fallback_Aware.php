<?php
/**
 * @package   Barn2\woocommerce-product-filters
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */

namespace Barn2\Plugin\WC_Filters\Integrations;

use Barn2\Plugin\WC_Filters\Api;
use Barn2\Plugin\WC_Filters\Dependencies\Illuminate\Support\Collection;
use Barn2\Plugin\WC_Filters\Display;
use Barn2\Plugin\WC_Filters\Request_Fallback;
use Barn2\Plugin\WC_Filters\Utils\Filters;

/**
 * Common methods used when making use of the fallback mode.
 */
trait Fallback_Aware {

	/**
	 * Load hooks for the fallback mode.
	 *
	 * @return void
	 */
	public function load_fallback_hooks() {
		add_filter( 'wcf_fallback_mode', [ $this, 'apply_fallback_mode' ] );
		add_filter( 'wcf_filtered_post_ids_query_args', [ $this, 'apply_integration_flag' ] );
		add_action( 'pre_get_posts', [ $this, 'filter_query' ], 999 );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_json' ] );
	}

	/**
	 * Insert a special flag into the WP_Query args array
	 * used by the query that retrieves filtered post ids.
	 *
	 * @param array $args
	 * @return array
	 */
	public function apply_integration_flag( $args ) {
		$args[ $this->get_query_argument_id() ] = true;
		return $args;
	}

	/**
	 * Insert the filtered results into the query.
	 *
	 * This is used via pre_get_posts.
	 *
	 * @param \WP_Query $query
	 * @return void
	 */
	public function filter_query( $query ) {
		if ( $query->get( $this->get_query_argument_id() ) !== true ) {
			return;
		}

		$search_string = $this->get_requested_filters();
		$orderby       = $this->get_requested_orderby();

		$fallback_request = new Request_Fallback();

		if ( ! empty( $search_string ) ) {
			$collection = $fallback_request->parse_request( $search_string );

			if ( $collection instanceof Collection && $collection->isNotEmpty() ) {
				$fallback_request->set_parameters( $collection );

				$fallback_request = $fallback_request->load_filters();

				// Load values from filter and inject appropriate results.
				$fallback_request->update_query_vars( $query );
			}
		}

		if ( ! empty( $orderby ) ) {
			$fallback_request->set_orderby( $orderby );

			// Sort results if needed.
			$fallback_request->maybe_order_results( $query );
		}
	}

	/**
	 * Get the value of the url query string that holds
	 * the filters selected via the form.
	 *
	 * @return string
	 */
	public function get_requested_filters() {
		$search_string = isset( $_POST['_wcf_search'] ) && ! empty( $_POST['_wcf_search'] ) ? $_POST['_wcf_search'] : false;

		if ( empty( $search_string ) ) {
			return isset( $_GET['_wcf_search'] ) && ! empty( $_GET['_wcf_search'] ) ? $_GET['_wcf_search'] : false;
		}

		return $search_string;
	}

	/**
	 * Get the value of the url query string that holds
	 * the orderby parameter.
	 *
	 * @return string
	 */
	public function get_requested_orderby() {
		$orderby = isset( $_POST['_wcf_orderby'] ) && ! empty( $_POST['_wcf_orderby'] ) ? sanitize_text_field( $_POST['_wcf_orderby'] ) : false;

		if ( empty( $orderby ) ) {
			$orderby = isset( $_GET['_wcf_orderby'] ) && ! empty( $_GET['_wcf_orderby'] ) ? sanitize_text_field( $_GET['_wcf_orderby'] ) : false;
		}

		return $orderby;
	}

	/**
	 * Load json values needed for the fallback js.
	 *
	 * @return void
	 */
	public function enqueue_json() {
		if ( ! Filters::is_using_fallback_mode() ) {
			return;
		}

		wp_add_inline_script( Display::IDENTIFIER, 'const WCF_Fallback = ' . wp_json_encode( $this ), 'before' );
	}

	/**
	 * Prepare fallback json array.
	 *
	 * @return array
	 */
	#[\ReturnTypeWillChange]
	public function jsonSerialize() {
		$prefill = [];

		if ( ! empty( $this->get_requested_filters() ) ) {
			$parser     = new Request_Fallback( $this->get_requested_filters() );
			$parameters = $parser->prepare_requested_filters_collection( $parser->parameters );
			$prefill    = $parameters->toArray();
		}

		return [
			'prefill'   => $prefill,
			'orderby'   => sanitize_text_field( $this->get_requested_orderby() ),
			'restNonce' => wp_create_nonce( 'wp_rest' ),
			'rest'      => trailingslashit( get_rest_url() . Api::API_NAMESPACE ) . 'counters',
		];
	}
}
