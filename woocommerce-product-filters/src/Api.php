<?php
/**
 * @package   Barn2\woocommerce-product-filters
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */

namespace Barn2\Plugin\WC_Filters;

use Attribute;
use Barn2\Plugin\WC_Filters\Dependencies\Illuminate\Support\Collection;
use Barn2\Plugin\WC_Filters\Model\Countable_Interface;
use Barn2\Plugin\WC_Filters\Model\Filter;
use Barn2\Plugin\WC_Filters\Model\Filters\Taxonomy;
use Barn2\Plugin\WC_Filters\Model\Group;
use Barn2\Plugin\WC_Filters\Model\Index;
use Barn2\Plugin\WC_Filters\Utils\Filters;
use Barn2\Plugin\WC_Filters\Dependencies\Lib\Registerable;

/**
 * REST Api
 */
class Api implements Registerable {

	const API_NAMESPACE = 'wcf/v1';

	/**
	 * Hook into WP.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	/**
	 * Check if a given request has admin access.
	 *
	 * @param  \WP_REST_Request $request Full data about the request.
	 * @return \WP_Error|bool
	 */
	public function check_permissions( $request ) {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Verify frontend requests.
	 *
	 * @param \WP_REST_Request $request Full data about the request.
	 * @return bool
	 */
	public function check_public_permission( $request ) {
		$nonce = $request->get_header( 'x-wp-nonce' );

		if ( $nonce && wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Register the REST Api routes.
	 *
	 * @return void
	 */
	public function register_routes() {

		register_rest_route(
			self::API_NAMESPACE,
			'/indexer-status/',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_indexer_status' ],
				'permission_callback' => [ $this, 'check_permissions' ],
			]
		);

		register_rest_route(
			self::API_NAMESPACE,
			'/start-batch-index/',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'start_batch_index' ],
				'permission_callback' => [ $this, 'check_permissions' ],
			]
		);

		register_rest_route(
			self::API_NAMESPACE,
			'/counters/',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'get_counters' ],
				'permission_callback' => [ $this, 'check_public_permission' ],
			]
		);

		register_rest_route(
			self::API_NAMESPACE,
			'/pricing/',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'get_pricing' ],
				'permission_callback' => '__return_true',
			]
		);
	}

	/**
	 * Get the status of the indexer.
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function get_indexer_status( $request ) {

		/** @var Indexer $indexer */
		$indexer = wcf()->get_service( 'indexer' );

		$message = __( 'Regenerating the index in the background. Product filtering and sorting may not be accurate until this finishes. It will take a few minutes and this notice will disappear when complete.', 'woocommerce-product-filters' );

		return new \WP_REST_Response(
			[
				'success' => true,
				'running' => $indexer->is_batch_index_running(),
				'message' => $message
			],
			200
		);
	}

	/**
	 * Start the batch indexing of all products.
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function start_batch_index( $request ) {

		/** @var \WC_Action_Queue $queue */
		$queue = wcf()->get_service( 'queue' );

		/** @var Indexer $indexer */
		$indexer = wcf()->get_service( 'indexer' );

		$indexer->set_index_running( true );

		Diff::update_current_state();

		// Cancel all previous batches.
		$queue->cancel_all( 'wcf_batch_index' );

		$queue->schedule_single(
			time(),
			'wcf_batch_index',
			[
				'offset' => 0,
				'limit'  => $indexer->get_chunk_size(),
			],
			'wcf_batch_index'
		);

		return new \WP_REST_Response(
			[
				'success' => true,
			],
			200
		);
	}

	/**
	 * Query the api and retrieve the list of filters
	 * and their counters.
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function get_counters( $request ) {

		$posts   = wc_clean( $request->get_param( 'posts' ) ) ?? [];
		$counts  = [];
		$filters = new Collection();
		$groups  = wc_clean( $request->get_param( 'groups' ) );
		$config  = $request->get_param( 'config' ); // This is used to determine if additional attributes should be injected.

		if ( ! empty( $groups ) && is_array( $groups ) ) {
			foreach ( $groups as $group_id => $group_params ) {

				$group = Group::find( $group_id );

				if ( $group instanceof Group ) {
					$group_filters = $group->get_filters();

					if ( $group_filters instanceof Collection && $group_filters->isNotEmpty() ) {
						$filters = $filters->concat( $group_filters );
					}
				}
			}
		}

		$filters = self::inject_values_into_filters( $filters, $request->get_param( 'values' ) );
		$filters = Filters::set_additional_attributes( $filters, $config );

		if ( $filters->isNotEmpty() ) {
			foreach ( $filters as $filter ) {
				if ( $filter instanceof Countable_Interface ) {
					$counts[ $filter->slug ] = $filter->get_choices_counts( $posts, $filters, true );
				}
			}
		}

		return new \WP_REST_Response(
			[
				'success' => true,
				'counts'  => $counts,
				'posts'   => $posts
			],
			200
		);
	}

	/**
	 * Inject searched values into the collection of filters.
	 *
	 * @param Collection $collection
	 * @param Collection $values
	 * @return Collection
	 */
	public static function inject_values_into_filters( Collection $collection, $values ) {

		$values = new Collection( $values );

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
			function( $instance ) use ( $filters_with_multiple_options, $multiselectable, $values ) {
				$filter_class = get_class( $instance );

				if ( in_array( $filter_class, $filters_with_multiple_options, true ) && in_array( $instance->get_option( 'filter_type' ), $multiselectable, true ) ) {
					$value = $values->get( $instance->slug );

					if ( ! is_array( $value ) ) {
						$value = explode( ',', $value );
					}

					$instance->setAttribute( 'search_query', $value );
				} else {
					$instance->setAttribute( 'search_query', $values->get( $instance->slug ) );
				}
			}
		);

		return $collection;
	}

	/**
	 * Get the maximum pricing for a given taxonomy term.
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function get_pricing( $request ) {

		$term     = $request->get_param( 'term_id' );
		$taxonomy = $request->get_param( 'taxonomy' );
		$filter   = $request->get_param( 'filter' );

		if ( $term ) {
			$term = get_term_by( 'term_id', absint( $term ), $taxonomy );
		}

		if ( ! $term ) {
			return new \WP_REST_Response(
				[
					'success' => true,
					'max'     => get_option( 'wcf_highest_price', false )
				],
				200
			);
		}

		$args = [
			'post_type'                   => 'product',
			'post_status'                 => 'publish',
			'posts_per_page'              => -1,
			'tax_query'                   => [
				[
					'taxonomy' => $taxonomy,
					'field'    => 'term_id',
					'terms'    => $term->term_id
				]
			],
			'fields'                      => 'ids',
			'wcwp_disable_loop_protector' => true,
			'update_post_meta_cache'      => false,
			'update_post_term_cache'      => false,
		];

		$products = ( new \WP_Query( $args ) )->get_posts() ?? [];

		if ( empty( $products ) ) {
			return new \WP_REST_Response(
				[
					'success' => true,
					'max'     => get_option( 'wcf_highest_price', false )
				],
				200
			);
		}

		/**
		 * Filter: allows adjustments to the list of product ids used to retrieve
		 * that max price for the pricing slider filter on taxonomy pages.
		 *
		 * @param array $products_ids
		 * @return array
		 */
		$products_ids = apply_filters( 'wcf_pricing_api_products_ids', $products );

		$data = Index::where( 'filter_id', $filter )
			->whereIn( 'post_id', $products_ids )
			->orderByRaw( 'CONVERT(facet_value, SIGNED) desc' )
			->first();

		return new \WP_REST_Response(
			[
				'success' => true,
				'max'     => $data instanceof Index ? ceil( number_format( (float) $data->facet_value, 2, '.', '' ) ) : get_option( 'wcf_highest_price', false ),
			],
			200
		);
	}

}
