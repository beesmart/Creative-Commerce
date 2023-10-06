<?php
/**
 * @package   Barn2\woocommerce-product-filters
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */

namespace Barn2\Plugin\WC_Filters\Model\Filters;

use Barn2\Plugin\WC_Filters\Model\Filter;
use Barn2\Plugin\WC_Filters\Model\Filterable_Interface;
use Barn2\Plugin\WC_Filters\Model\Index;
use Barn2\Plugin\WC_Filters\Model\Indexable_Interface;
use Barn2\Plugin\WC_Filters\Utils\Filters;

use function Barn2\Plugin\WC_Filters\wcf;

/**
 * Represents the price filter.
 */
class Price extends Filter implements Indexable_Interface, Filterable_Interface {

	/**
	 * @inheritdoc
	 */
	public function getInputNameAttribute() {
		return __( 'Slider', 'woocommerce-product-filters' );
	}

	/**
	 * @inheritdoc
	 */
	public function generate_index_data( array $defaults, string $post_id ) {

		$output  = [];
		$product = wc_get_product( $post_id );

		if ( ! $product ) {
			return $output;
		}

		if ( $product->is_type( 'variable' ) ) {
			$price_min = $product->get_variation_price( 'min' );
			$price_max = $product->get_variation_price( 'max' );
		} else {
			$price_min = $price_max = $product->get_price();
		}

		$defaults['facet_value']         = $price_min;
		$defaults['facet_display_value'] = $price_max;

		$params   = $defaults;
		$output[] = $params;

		return $output;
	}

	/**
	 * @inheritdoc
	 */
	public function get_search_query() {
		return explode( ',', $this->search_query );
	}

	/**
	 * @inheritdoc
	 */
	public function find_posts() {
		$value = $this->get_search_query();
		$db    = wcf()->get_service( 'db' );

		if ( ! isset( $value[0] ) || ! isset( $value[1] ) ) {
			return [];
		}

		$data = Index::select( 'post_id' )
			->distinct()
			->where( 'filter_id', $this->getID() )
			->whereBetween( $db::raw( 'CAST(facet_value AS SIGNED)' ), [ absint( $value[0] ), absint( $value[1] ) ] )
			->get();

		return Filters::flatten_results( $data );
	}

}
