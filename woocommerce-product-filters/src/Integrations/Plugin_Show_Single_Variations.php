<?php
/**
 * @package   Barn2\woocommerce-product-filters
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */

namespace Barn2\Plugin\WC_Filters\Integrations;

use Barn2\Plugin\WC_Filters\Dependencies\Illuminate\Support\Collection;
use Barn2\Plugin\WC_Filters\Dependencies\Lib\Registerable;
use Barn2\Plugin\WC_Filters\Indexer;
use Barn2\Plugin\WC_Filters\Model\Filter;
use Barn2\Plugin\WC_Filters\Model\Index;

use function Barn2\Plugin\WC_Filters\wcf;

/**
 * Provides integration with the Show single variations plugin from Iconic.
 */
class Plugin_Show_Single_Variations implements Registerable {

	/**
	 * Register integration.
	 *
	 * @return void
	 */
	public function register() {
		if ( ! class_exists( '\Iconic_WSSV' ) ) {
			return;
		}

		$this->init();
	}

	/**
	 * Hook into WP.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'wcf_after_product_index', [ $this, 'index_variations' ], 20, 4 );
		add_filter( 'wcf_pricing_api_products_ids', [ $this, 'adjust_pricing_api' ], 10 );
		add_action( 'wcf_after_product_delete', [ $this, 'after_delete_product' ], 10 );
		add_filter( 'wcf_indexer_skip_hidden_products', '__return_false' );
		add_action( 'iconic_wssv_product_processed', [ $this, 'rebuild_index' ], 10 );
		add_filter( 'woocommerce_is_filtered', [ $this, 'adjust_is_filtered' ] );
	}

	/**
	 * Adjust conditions for the `is_filtered` function provided by WC.
	 *
	 * @param boolean $is_filtered
	 * @return boolean
	 */
	public function adjust_is_filtered( $is_filtered ) {

		if ( isset( $_GET['_wcf_filter'] ) && $this->is_prefilling() || isset( $_POST['action'] ) && $_POST['action'] === 'wcf_fetch_data' && $this->has_filters_in_ajax_request() ) {
			return true;
		}

		return $is_filtered;
	}

	/**
	 * Determine if prefilling is taking place and
	 * make sure we're actually using filters.
	 *
	 * @return boolean
	 */
	private function is_prefilling() {

		$query_params = new Collection( $_GET );

		if ( $query_params->isEmpty() ) {
			return false;
		}

		$query_params = $query_params->except(
			[
				'_wcf_filter',
				'sorting',
				'_paged',
			]
		);

		if ( $query_params->isEmpty() ) {
			return false;
		}

		$filters = Filter::whereIn( 'slug', $query_params->keys() )->count();

		return is_int( $filters ) && $filters > 0;
	}

	/**
	 * Determine if the ajax request has filters.
	 *
	 * @return boolean
	 */
	private function has_filters_in_ajax_request() {

		if ( isset( $_POST['filters'] ) && $_POST['filters'] !== '{}' && ! empty( $_POST['filters'] ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Index variations of a product.
	 *
	 * @param string|int $post_id the id of the product
	 * @param object $product instance of a WC product
	 * @param array $filters collection of filters for which the index is being generated
	 * @param Indexer $indexer instance of the indexer
	 */
	public function index_variations( $product_id, $product, $filters, $indexer ) {

		// Abort if not a variable product.
		if ( ! $product->is_type( 'variable' ) ) {
			return;
		}

		$variations = $product->get_children();

		foreach ( $variations as $i => $variation_id ) {
			\Iconic_WSSV_Product_Variation::set_taxonomies( $variation_id );
			\Iconic_WSSV_Product_Variation::set_visibility( $variation_id );
			\Iconic_WSSV_Product_Variation::set_featured_visibility( $variation_id );

			$visibility = $this->get_variation_visibility( $variation_id );

			if ( is_array( $visibility ) && ( in_array( 'filtered', $visibility ) || in_array( 'catalog', $visibility ) || in_array( 'search', $visibility ) ) ) {
				$indexer->index_post( $variation_id, $filters );
			} else {
				Index::whereIn( 'post_id', [ $variation_id ] )->delete();
			}
		}

		$parent_visibility            = \Iconic_WSSV_Product::get_visibility_term_slugs( $product->get_id() );
		$parent_exclude_from_filtered = in_array( 'exclude-from-filtered', $parent_visibility );

		if ( $product->get_catalog_visibility() === 'hidden' || $parent_exclude_from_filtered ) {
			Index::whereIn( 'post_id', [ $product->get_id() ] )->delete();
		}
	}

	/**
	 * Inject variations ids into the list of products
	 * that is loaded when checking the max price.
	 *
	 * @param array $ids
	 * @return array
	 */
	public function adjust_pricing_api( $ids ) {

		$new_ids = [];

		if ( is_array( $ids ) && ! empty( $ids ) ) {
			foreach ( $ids as $product_id ) {

				$product   = wc_get_product( $product_id );
				$new_ids[] = $product_id;

				if ( ! $product->is_type( 'variable' ) ) {
					continue;
				}

				$variations = $product->get_children();

				if ( ! empty( $variations ) && is_array( $variations ) ) {
					$new_ids = array_merge( $new_ids, $variations );
				}
			}
		}

		return $new_ids;
	}

	/**
	 * Automatically delete variations data when the product is deleted.
	 *
	 * @param string|int $product_id
	 * @return void
	 */
	public function after_delete_product( $product_id ) {

		$product = wc_get_product( $product_id );

		if ( ! $product->is_type( 'variable' ) ) {
			return;
		}

		$variations = $product->get_children();

		if ( is_array( $variations ) && ! empty( $variations ) ) {
			Index::whereIn( 'post_id', $variations )->delete();
		}
	}

	/**
	 * Index our products when running the iconic indexer.
	 *
	 * @param string|int $product_id
	 * @return void
	 */
	public function rebuild_index( $product_id ) {

		/** @var Indexer $index */
		$indexer = wcf()->get_service( 'indexer' );
		$filters = Filter::all();

		// Delete all records for the given post.
		Index::byID( $product_id )->delete();

		$indexer->index_post( $product_id, $filters );
	}

	/**
	 * Get the visibility setting of a variation.
	 *
	 * @param string|int $variation_id
	 * @return array
	 */
	public function get_variation_visibility( $variation_id ) {
		return get_post_meta( $variation_id, '_visibility', true );
	}

}
