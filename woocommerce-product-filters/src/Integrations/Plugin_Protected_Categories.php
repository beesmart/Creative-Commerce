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
use Barn2\Plugin\WC_Filters\Model\Filters\Taxonomy;
use Barn2\Plugin\WC_Filters\Model\Index;
use Barn2\Plugin\WC_Filters\Dependencies\Lib\Registerable;

/**
 * Provides integration with the WPC plugin.
 *
 * If you have disabled the ‘Show categories’ option on the settings page
 * then hidden categories should not be shown in the filters
 * unless the user has unlocked the category.
 */
class Plugin_Protected_Categories implements Registerable {
	/**
	 * Register the integration.
	 *
	 * @return void
	 */
	public function register() {
		if ( ! function_exists( '\Barn2\Plugin\WC_Protected_Categories\wpc' ) ) {
			return;
		}

		$this->init();
	}

	/**
	 * Hook into WP
	 *
	 * @return void
	 */
	public function init() {
		if ( \Barn2\Plugin\WC_Protected_Categories\Util::showing_protected_categories() ) {
			return;
		}

		add_filter( 'wcf_all_choices_counts_bypass', [ $this, 'generate_all_choices_count' ], 10, 2 );
		add_filter( 'wcf_choices_counts_bypass', [ $this, 'generate_choices_count' ], 10, 3 );
		add_filter( 'wcf_taxonomy_filter_terms_list', [ $this, 'hide_protected_categories' ], 10, 2 );
		add_filter( 'wcf_filtered_post_ids', [ $this, 'hide_protected_products' ], 10, 3 );
	}

	/**
	 * Return the proper count of purchasable products by checking whether or not
	 * products are protected.
	 *
	 * @param boolean|string $bypass initial bypass value.
	 * @param Filter $filter filter instance.
	 * @return boolean|string
	 */
	public function generate_all_choices_count( $bypass, Filter $filter ) {
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
	 * @param Filter $filter filter instance.
	 * @param array $post_ids list of ids to specifically check
	 * @return boolean|string
	 */
	public function generate_choices_count( $bypass, Filter $filter, array $post_ids = [] ) {
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
	 * Hide protected categories from the Taxonomy filter.
	 *
	 * @param array $terms
	 * @param Taxonomy $filter
	 * @return array
	 */
	public function hide_protected_categories( array $terms, Taxonomy $filter ) {
		// Bail if the filter isn't handling the categories.
		if ( $filter->get_taxonomy_slug() !== 'product_cat' ) {
			return $terms;
		}

		foreach ( $terms as $key => $term ) {
			$term_id = isset( $term->term_id ) ? $term->term_id : $term['id'];
			if ( $this->is_protected_term( $term_id ) ) {
				unset( $terms[ $key ] );
			}
		}

		return $terms;
	}

	/**
	 * Given a term id number, check if it's protected.
	 *
	 * @param string|int $term_id
	 * @return boolean
	 */
	private function is_protected_term( $term_id ) {
		$visibility = \Barn2\Plugin\WC_Protected_Categories\Util::get_category_visibility( $term_id );

		return \Barn2\Plugin\WC_Protected_Categories\Util::is_protected( $visibility );
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
				$categories = \Barn2\Plugin\WC_Protected_Categories\Util::get_the_category_visibility( isset( $product->post_id ) ? $product->post_id : $product );
				$protected  = false;

				if ( \Barn2\Plugin\WC_Protected_Categories\Util::is_protected( $categories ) ) {
					$protected = true;
				}

				return $protected;
			}
		);

		return $filtered;
	}

}
