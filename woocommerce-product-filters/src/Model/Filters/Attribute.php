<?php
/**
 * @package   Barn2\woocommerce-product-filters
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */

namespace Barn2\Plugin\WC_Filters\Model\Filters;

use Barn2\Plugin\WC_Filters\Dependencies\Illuminate\Support\Collection;
use Barn2\Plugin\WC_Filters\Model\Countable_Interface;
use Barn2\Plugin\WC_Filters\Model\Filter;
use Barn2\Plugin\WC_Filters\Model\Filterable_Interface;
use Barn2\Plugin\WC_Filters\Model\Index;
use Barn2\Plugin\WC_Filters\Model\Indexable_Interface;
use Barn2\Plugin\WC_Filters\Model\Preloadable_Interface;
use Barn2\Plugin\WC_Filters\Model\Storable_Interface;
use Barn2\Plugin\WC_Filters\Traits\Prefilling_Aware;
use Barn2\Plugin\WC_Filters\Traits\Search_Query_Array_Aware;
use Barn2\Plugin\WC_Filters\Traits\Taxonomy_Counts_Provider;
use Barn2\Plugin\WC_Filters\Utils\Filters;
use Barn2\Plugin\WC_Filters\Utils\Terms;

/**
 * Responsible for generating taxonomy related data of global attributes.
 */
class Attribute extends Filter implements Indexable_Interface, Storable_Interface, Filterable_Interface, Countable_Interface, Preloadable_Interface {

	use Prefilling_Aware;
	use Search_Query_Array_Aware;
	use Taxonomy_Counts_Provider;

	/**
	 * Delete child filters if found.
	 *
	 * @return bool
	 */
	public function delete() {
		if ( $this->get_attributes_mode() === 'all' ) {

			$child_filters = Filter::where( 'parent_filter', $this->getID() )->get();

			if ( $child_filters instanceof Collection && $child_filters->isNotEmpty() ) {
				foreach ( $child_filters as $filter ) {
					$filter->delete();
				}
			}
		}

		return parent::delete();
	}

	/**
	 * Update child filters types when updating the parent filter.
	 *
	 * @param array $attributes
	 * @param array $options
	 * @return bool
	 */
	public function update( array $attributes = [], array $options = [] ) {
		if ( $this->get_attributes_mode() === 'all' ) {

			$child_filters = Filter::where( 'parent_filter', $this->getID() )->get();

			if ( $child_filters instanceof Collection && $child_filters->isNotEmpty() ) {
				/** @var Filter $filter */
				foreach ( $child_filters as $filter ) {
					$options                       = $filter->get_options();
					$options['filter_type']        = $attributes['options']['filter_type'];
					$options['image_display_mode'] = isset( $attributes['options']['image_display_mode'] ) ? $attributes['options']['image_display_mode'] : false;

					$filter->update(
						[
							'options' => $options
						]
					);
				}
			}
		}

		return parent::update( $attributes, $options );
	}

	/**
	 * Get the attributes mode selected.
	 *
	 * @return string Either "all" or "specific".
	 */
	public function get_attributes_mode() {
		return $this->get_option( 'attributes_mode' );
	}

	/**
	 * Get the specific attribute assigned to the filter.
	 *
	 * @return string
	 */
	public function get_specific_attribute() {
		return $this->get_attributes_mode() === 'specific' ? $this->get_option( 'specific_attribute' ) : false;
	}

	/**
	 * Determine if the filter has a parent filter.
	 *
	 * @return boolean
	 */
	public function has_parent() {
		return ! empty( $this->parent_filter ) && absint( $this->parent_filter ) > 0;
	}

	/**
	 * @inheritdoc
	 */
	public function generate_index_data( array $defaults, string $post_id ) {

		$output = [];
		$mode   = $this->get_attributes_mode();

		if ( $mode === 'all' ) {
			// The "all" filter is a fake one. Bypass it when generating data.
			return [];
		} elseif ( $mode === 'specific' ) {
			$taxonomy = $this->get_option( 'specific_attribute' );

			if ( $this->get_option( 'filter_type' ) === 'range' ) {
				$output = Filters::generate_ranged_taxonomy_index_data( $defaults, $post_id, "pa_{$taxonomy}" );
			} else {
				$output = Filters::generate_taxonomy_index_data( $defaults, $post_id, "pa_{$taxonomy}" );
			}
		}

		return $output;
	}

	/**
	 * @inheritdoc
	 */
	public function get_json_store_data() {
		$data      = [];
		$attribute = $this->get_option( 'specific_attribute', false );
		$args      = array_merge(
			[
				'hide_empty' => true,
			],
			$this->get_attribute_orderby_args( $attribute )
		);

		if ( $this->get_option( 'filter_type' ) === 'images' ) {

			$terms_list = [];
			$terms      = get_terms(
				wc_attribute_taxonomy_name( $attribute ),
				$args
			);

			if ( ! empty( $terms ) ) {
				foreach ( $terms as $term ) {
					if ( $term instanceof \WP_Term ) {
						$terms_list[ absint( $term->term_id ) ] = [
							'term_id' => absint( $term->term_id ),
							'name'    => $term->name,
							'image'   => Terms::get_term_image( $term->term_id ),
							'slug'    => $term->slug,
						];
					}
				}
			}

			if ( ! empty( $terms_list ) ) {
				$data = $terms_list;
			}
		} elseif ( $this->get_option( 'filter_type' ) === 'range' ) {

			$indexed = Index::where( 'filter_id', $this->getID() )->get();
			$indexed = Filters::order_numerical_collection( $indexed );

			$min = (int) filter_var( $indexed->first()->facet_value, FILTER_SANITIZE_NUMBER_INT );
			$max = (int) filter_var( $indexed->last()->facet_value, FILTER_SANITIZE_NUMBER_INT );

			if ( $min < 0 ) {
				$min = 0;
			}

			if ( $max <= $min ) {
				$max = $min + 1;
			}

			$data = [
				'min'     => $min,
				'max'     => $max,
				'unit'    => $this->get_option( 'range_unit' ),
				'options' => $indexed
			];

		} else {

			$list = [];

			$terms = get_terms(
				wc_attribute_taxonomy_name( $attribute ),
				$args
			);

			if ( ! empty( $terms ) && is_array( $terms ) ) {
				foreach ( $terms as $tt ) {
					$list[] = [
						'id'    => $tt->term_id,
						'label' => $tt->name,
						'slug'  => $tt->slug,
					];
				}
				$data = $list;
			}
		}

		/**
		 * Filter: allows developers to modify the list of found terms
		 * generated by the Attribute filter type.
		 *
		 * @param array $terms
		 * @param Attribute $filter
		 * @return array
		 */
		return apply_filters( 'wcf_taxonomy_filter_attribute_terms_list', $data, $this );
	}

}
