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
use Barn2\Plugin\WC_Filters\Model\Indexable_Interface;
use Barn2\Plugin\WC_Filters\Traits\Simple_Finder;
use Barn2\Plugin\WC_Filters\Utils\Filters;
use Barn2\Plugin\WC_Filters\Meta_Fields;
use Barn2\Plugin\WC_Filters\Model\Countable_Interface;
use Barn2\Plugin\WC_Filters\Model\Index;
use Barn2\Plugin\WC_Filters\Model\Preloadable_Interface;
use Barn2\Plugin\WC_Filters\Model\Storable_Interface;
use Barn2\Plugin\WC_Filters\Dependencies\Illuminate\Support\Collection;
use Barn2\Plugin\WC_Filters\Integrations\Plugin_Ept;

use function Barn2\Plugin\WC_Filters\wcf;

/**
 * Represents a custom field filter.
 */
class Custom_Field extends Filter implements Indexable_Interface, Filterable_Interface, Storable_Interface, Countable_Interface, Preloadable_Interface {

	/**
	 * @inheritdoc
	 */
	public function getInputNameAttribute() {
		return __( 'Custom field', 'woocommerce-product-filters' );
	}

	/**
	 * @inheritdoc
	 */
	public function get_search_query() {

		$type  = $this->get_option( 'filter_type' );
		$value = $this->search_query;

		switch ( $type ) {
			case 'checkboxes':
				$value = $this->get_checkboxes_query();
				break;
			case 'number':
			case 'range':
				$value = $this->get_range_query();
				break;
		}

		return $value;
	}

	/**
	 * Parse filter query for the range slider.
	 *
	 * @return array
	 */
	private function get_range_query() {
		return explode( ',', $this->search_query );
	}

	/**
	 * Parse filter query for the checkboxes filter.
	 *
	 * @return array
	 */
	private function get_checkboxes_query() {
		if ( is_array( $this->search_query ) ) {
			return $this->search_query;
		}

		return explode( ',', $this->search_query );
	}

	/**
	 * Retrieve the meta key assigned to the filter.
	 *
	 * @return string
	 */
	public function get_custom_field_key() {
		return $this->get_option( 'cf' )['value'];
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

		$meta_key = $this->get_custom_field_key();

		if ( ! Filters::custom_field_exists( $meta_key ) ) {
			return $output;
		}

		/** @var Meta_Fields $meta_fields */
		$meta_fields = wcf()->get_service( 'meta_fields' );

		$value   = $meta_fields->get_field_value( $meta_key, $post_id );
		$display = $meta_fields->get_field_display_value( $meta_key, $value );

		if ( empty( $display ) ) {
			$display = $value;
		}

		if ( empty( $value ) ) {
			return $output;
		}

		$defaults['facet_value']         = $value;
		$defaults['facet_display_value'] = $display;

		$params   = $defaults;
		$output[] = $params;

		return $output;
	}

	/**
	 * @inheritdoc
	 */
	public function get_json_store_data() {

		$type = $this->get_option( 'filter_type' );
		$data = [];

		switch ( $type ) {
			case 'number':
			case 'range':
				$data = $this->get_json_store_data_range();
				break;
			default:
				$data = Index::select( 'facet_display_value AS label', 'facet_value AS value' )
					->distinct()
					->where( 'filter_id', $this->getID() )->orderBy( 'value', 'ASC' )->get();
				break;
		}

		return $data;
	}

	/**
	 * Get json data for the number range field.
	 *
	 * @return array
	 */
	private function get_json_store_data_range() {

		$indexed = Index::where( 'filter_id', $this->getID() )->distinct()->get();

		$items = $indexed->each(
			function ( $item, $key ) {
				$item->setAttribute( 'number', (int) filter_var( $item->facet_value, FILTER_SANITIZE_NUMBER_INT ) );
			}
		);

		$indexed = $items->sortBy( 'facet_value', SORT_NATURAL )->values();

		$min = (int) filter_var( $indexed->first()->facet_value, FILTER_SANITIZE_NUMBER_INT );
		$max = (int) filter_var( $indexed->last()->facet_value, FILTER_SANITIZE_NUMBER_INT );

		if ( $min < 0 ) {
			$min = 0;
		}

		if ( $max <= $min ) {
			$max = $min + 1;
		}

		return [
			'min'  => $min,
			'max'  => $max,
			'unit' => $this->get_option( 'range_unit' ),
		];
	}

	/**
	 * @inheritdoc
	 */
	public function get_choices_counts( array $post_ids, $filters = false, $prefilling = false ) {

		$db    = wcf()->get_service( 'db' );
		$count = [];

		/**
		 * Filter: allow developers to return a custom count for the
		 * filter when counting specific posts.
		 *
		 * @param boolean $bypass whether or not to return a custom value.
		 * @param Filter $filter
		 * @param array $post_ids
		 * @return string return a string to display a custom number.
		 */
		$bypass = apply_filters( 'wcf_all_choices_counts_bypass', false, $this, $post_ids );

		if ( $bypass !== false ) {
			return $bypass;
		}

		$query = Index::select( 'facet_value', $db::raw( 'COUNT(DISTINCT post_id) AS counter' ) )
			->where( 'filter_id', $this->getID() )
			->whereIn( 'post_id', $post_ids )
			->groupBy( 'facet_value' )
			->orderBy( 'counter', 'DESC' )
			->orderBy( 'facet_value', 'ASC' )
			->get();

		if ( $query instanceof Collection ) {
			return $query;
		}

		return $count;
	}

	/**
	 * @inheritdoc
	 */
	public function get_all_choices_counts( array $post_ids = [] ) {
		$db    = wcf()->get_service( 'db' );
		$count = [];

		if ( Filters::is_taxonomy_page() && ! empty( $post_ids ) ) {
			return $this->get_choices_counts( $post_ids );
		}

		/**
		 * Filter: allow developers to return a custom count for the
		 * filter when counting specific posts.
		 *
		 * @param boolean $bypass whether or not to return a custom value.
		 * @param Filter $filter
		 * @return string return a string to display a custom number.
		 */
		$bypass = apply_filters( 'wcf_all_choices_counts_bypass', false, $this );

		if ( $bypass !== false ) {
			return $bypass;
		}

		$query = Index::select( 'facet_value', $db::raw( 'COUNT(DISTINCT post_id) AS counter' ) )
			->where( 'filter_id', $this->getID() )
			->groupBy( 'facet_value' )
			->orderBy( 'counter', 'DESC' )
			->orderBy( 'facet_value', 'ASC' )
			->get();

		if ( $query instanceof Collection ) {
			return $query;
		}

		return $count;
	}

	/**
	 * @inheritdoc
	 */
	public function find_posts() {
		$type = $this->get_option( 'filter_type' );

		switch ( $type ) {
			case 'checkboxes':
				$data = $this->find_posts_checkboxes();
				break;
			case 'number':
			case 'range':
				$data = $this->find_posts_range();
				break;
			case 'text':
				$data = $this->find_posts_text();
				break;
			default:
				$data = Index::select( 'post_id' )
					->distinct()
					->where( 'filter_id', $this->getID() )
					->where( 'facet_value', $this->get_search_query() )
					->get();
				break;
		}

		return Filters::flatten_results( $data );
	}

	/**
	 * Get posts for for the checkboxes input.
	 *
	 * @return Collection
	 */
	private function find_posts_checkboxes() {
		return Index::select( 'post_id' )
			->distinct()
			->where( 'filter_id', $this->getID() )
			->whereIn( 'facet_value', $this->get_search_query() )
			->get();
	}

	/**
	 * Get posts for for the range input.
	 *
	 * @return Collection
	 */
	private function find_posts_range() {

		$value = $this->get_search_query();
		$db    = wcf()->get_service( 'db' );

		return Index::select( 'post_id' )
			->distinct()
			->where( 'filter_id', $this->getID() )
			->whereBetween( $db::raw( 'CAST(facet_value AS SIGNED)' ), [ absint( $value[0] ), absint( $value[1] ) ] )
			->get();
	}

	/**
	 * When using EPT's editor field, we fall back to text based search
	 * via WP Queries.
	 *
	 * @return array|Collection
	 */
	private function find_posts_text() {

		$string = $this->get_search_query();

		/** @var Meta_Fields $meta_fields */
		$meta_fields = wcf()->get_service( 'meta_fields' );

		$provider = $meta_fields->get_field_provider( $this->get_custom_field_key() );

		if ( $provider instanceof Plugin_Ept ) {

			$key  = str_replace( 'product_', '', $this->get_custom_field_key() );
			$key  = array_search( $key, array_column( $provider->get_fields( false ), 'slug' ), true );
			$type = $provider->get_fields( false )[ $key ]['type'];

			if ( $type === 'editor' ) {
				$args = [
					'post_type'              => 'product',
					'posts_per_page'         => -1,
					'update_post_meta_cache' => false,
					'update_post_term_cache' => false,
					'cache_results'          => false,
					'no_found_rows'          => true,
					'nopaging'               => true,
					'woocommerce-filters'    => false,
					'fields'                 => 'ids',
					'meta_query'             => [
						[
							'key'     => $this->get_custom_field_key(),
							'value'   => $string,
							'compare' => 'LIKE',
						],
					],
				];

				$query = new \WP_Query( $args );
				$posts = $query->get_posts();

				return $posts;
			}
		}

		$data = Index::select( 'post_id' )
				->distinct()
				->where( 'filter_id', $this->getID() )
				->where( 'facet_value', $string )
				->get();

		return $data;
	}

}
