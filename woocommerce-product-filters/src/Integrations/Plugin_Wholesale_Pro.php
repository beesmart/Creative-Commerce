<?php
/**
 * @package   Barn2\woocommerce-product-filters
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */

namespace Barn2\Plugin\WC_Filters\Integrations;

use Barn2\Plugin\WC_Filters\Dependencies\Illuminate\Database\Eloquent\Collection;
use Barn2\Plugin\WC_Filters\Model\Filter;
use Barn2\Plugin\WC_Filters\Model\Filters\Stock;
use Barn2\Plugin\WC_Filters\Model\Filters\Taxonomy;
use Barn2\Plugin\WC_Filters\Model\Index;
use Barn2\Plugin\WC_Filters\Dependencies\Lib\Registerable;

use function Barn2\Plugin\WC_Filters\wcf;

/**
 * Provides integration with the Wholesale Pro plugin.
 */
class Plugin_Wholesale_Pro implements Registerable {

	/**
	 * Hook into WP if the plugin is found.
	 *
	 * @return void
	 */
	public function register() {
		if ( ! function_exists( '\Barn2\Plugin\WC_Wholesale_Pro\woocommerce_wholesale_pro' ) ) {
			return;
		}

		$this->init();
	}

	/**
	 * Run hooks.
	 *
	 * @return void
	 */
	protected function init() {
		add_filter( 'wcf_all_choices_counts_bypass', [ $this, 'generate_stock_all_choices_count' ], 10, 2 );
		add_filter( 'wcf_choices_counts_bypass', [ $this, 'generate_stock_choices_count' ], 10, 3 );

		// add_filter( 'wcf_all_taxonomy_choices_counts_bypass', [ $this, 'generate_all_taxonomies_choices_count' ], 10, 2 );

		add_filter( 'wcf_filtered_post_ids', [ $this, 'hide_protected_products' ], 10, 3 );
	}

	/**
	 * Return the proper count of purchasable products by checking whether or not
	 * products are protected.
	 *
	 * @param boolean|string $bypass initial bypass value.
	 * @param Stock $filter filter instance.
	 * @return boolean|string
	 */
	public function generate_stock_all_choices_count( $bypass, Filter $filter ) {
		$query = Index::where( 'filter_id', $filter->getID() )->orderBy( 'facet_value', 'ASC' )->get();

		if ( $query instanceof Collection && $query->isNotEmpty() ) {
			$filtered = $this->get_filtered_collection( $query );

			if ( $filtered instanceof Collection && ! empty( $filtered ) ) {
				return (string) $filtered->count();
			}
		}

		return $bypass;

	}

	/**
	 * Return the proper count of purchasable products by checking whether or not
	 * products are protected.
	 *
	 * @param boolean|string $bypass initial bypass value.
	 * @param Stock $filter filter instance.
	 * @param array $post_ids list of ids to specifically check
	 * @return boolean|string
	 */
	public function generate_stock_choices_count( $bypass, Filter $filter, array $post_ids = [] ) {
		$query = Index::where( 'filter_id', $filter->getID() )
			->whereIn( 'post_id', $post_ids )
			->orderBy( 'facet_value', 'ASC' )
			->get();

		if ( $query instanceof Collection && $query->isNotEmpty() ) {
			$filtered = $this->get_filtered_collection( $query );

			if ( $filtered instanceof Collection && ! empty( $filtered ) ) {
				return (string) $filtered->count();
			}
		}

		return $bypass;
	}

	/**
	 * Hide protected products from the filtered search query.
	 *
	 * @param array $products
	 * @param Collection $filters
	 * @return array
	 */
	public function hide_protected_products( array $products, Collection $filters ) {
		$collection = new Collection( $products );
		$filtered   = $this->get_filtered_collection( $collection );

		return $filtered->toArray();
	}

	/**
	 * Parse a collection of indexed values and reject products that are protected.
	 *
	 * @param Collection $collection
	 * @return Collection
	 */
	private function get_filtered_collection( Collection $collection ) {
		$filtered = $collection->reject(
			function ( $product, $key ) {
				$categories = \Barn2\Plugin\WC_Wholesale_Pro\Util::get_the_category_visibility( isset( $product->post_id ) ? $product->post_id : $product );
				$protected  = false;

				if ( \Barn2\Plugin\WC_Wholesale_Pro\Util::is_protected( $categories ) ) {
					$protected = true;
				}

				return $protected;
			}
		);

		return $filtered;
	}

	/**
	 * Change the count of indexed terms for the Taxonomy filter.
	 * This is needed because WWP hides categories only so any
	 * other type of term doesn't actually know how many
	 * visible posts it has.
	 *
	 * @param bool $bypass
	 * @param Taxonomy $filter
	 * @return Collection
	 */
	public function generate_all_taxonomies_choices_count( $bypass, Taxonomy $filter ) {
		if ( $filter->get_taxonomy_slug() === 'product_cat' ) {
			return false;
		}

		$db = wcf()->get_service( 'db' );

		$indexed_terms = Index::select( 'facet_value', $db::raw( 'COUNT(DISTINCT post_id) AS counter' ) )
			->where( 'filter_id', $filter->getID() )
			->whereNotIn( 'post_id', $this->get_hidden_products() )
			->groupBy( 'facet_value' )
			->orderBy( 'counter', 'DESC' )
			->orderBy( 'facet_value', 'ASC' )
			->get();

		if ( $indexed_terms instanceof Collection ) {
			return $indexed_terms;
		}

		return $bypass;
	}

	/**
	 * Get the list of hidden categories ids.
	 *
	 * @return array
	 */
	private function get_hidden_categories_ids() {

		$categories = [];

		foreach ( \Barn2\Plugin\WC_Wholesale_Pro\Util::to_category_visibilities( \Barn2\Plugin\WC_Wholesale_Pro\Util::get_product_categories() ) as $category ) {
			if ( $category->is_protected() ) {
				$categories[] = $category->get_term_id();
			}
		}

		return $categories;

	}

	/**
	 * Get the list of products from the hidden categories.
	 *
	 * @return array
	 */
	private function get_hidden_products() {

		$args = [
			'post_type'                   => 'product',
			'post_status'                 => 'publish',
			'ignore_sticky_posts'         => 1,
			'posts_per_page'              => -1,
			'meta_query'                  => [
				[
					'key'     => '_visibility',
					'value'   => [ 'catalog', 'visible' ],
					'compare' => 'IN'
				]
			],
			'tax_query'                   => [
				[
					'taxonomy' => 'product_cat',
					'field'    => 'term_id',
					'terms'    => $this->get_hidden_categories_ids()
				]
			],
			'fields'                      => 'ids',
			'wcwp_disable_loop_protector' => true,
			'update_post_meta_cache'      => false,
			'update_post_term_cache'      => false,
		];

		$products = ( new \WP_Query( $args ) )->get_posts() ?? [];

		return $products;

	}

}
