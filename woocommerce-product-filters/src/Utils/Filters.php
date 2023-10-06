<?php
/**
 * @package   Barn2\woocommerce-product-filters
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */

namespace Barn2\Plugin\WC_Filters\Utils;

use Barn2\Plugin\WC_Filters\Dependencies\Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Barn2\Plugin\WC_Filters\Dependencies\Illuminate\Support\Collection;
use Barn2\Plugin\WC_Filters\Model\Filter;
use Barn2\Plugin\WC_Filters\Model\Filters\Attribute;
use Barn2\Plugin\WC_Filters\Model\Index;

use function Barn2\Plugin\WC_Filters\wcf;

/**
 * Collection of helper methods to work with filters.
 */
class Filters {

	public static function get_terms_from_attribute( $variation_id, $taxonomy ) {

		$product = wc_get_product( $variation_id );

		if ( ! $product->is_type( 'variation' ) ) {
			return;
		}

		$attributes = $product->get_attributes();
		$terms      = [];

		if ( isset( $attributes[ $taxonomy ] ) ) {
			$terms = get_terms(
				[
					'taxonomy' => $taxonomy,
					'slug'     => $attributes[ $taxonomy ],
				]
			);
		}

		return $terms;
	}

	/**
	 * Helper function that generates an array of taxonomy related data.
	 *
	 * The function has been abstracted here because we have multiple type
	 * of filters that generate the same type of data (but in a slightly different way).
	 *
	 * @param array $defaults
	 * @param string $post_id
	 * @param string $taxonomy
	 * @return array
	 */
	public static function generate_taxonomy_index_data( array $defaults, string $post_id, string $taxonomy ) {

		$output       = [];
		$used_terms   = [];
		$term_objects = wp_get_post_terms( absint( $post_id ), $taxonomy );

		$product = wc_get_product( $post_id );

		if ( empty( $term_objects ) && $product->is_type( 'variation' ) ) {
			$term_objects = self::get_terms_from_attribute( $post_id, $taxonomy );
		}

		if ( is_wp_error( $term_objects ) ) {
			return $output;
		}

		// Store the term depths
		$hierarchy = Terms::get_term_depths( $taxonomy );

		foreach ( $term_objects as $term ) {

			// Prevent duplicate terms
			if ( isset( $used_terms[ $term->term_id ] ) ) {
				continue;
			}
			$used_terms[ $term->term_id ] = true;

			// Handle hierarchical taxonomies
			$term_info = $hierarchy[ $term->term_id ];
			$depth     = $term_info['depth'];

			$params                        = $defaults;
			$params['facet_value']         = $term->slug;
			$params['facet_display_value'] = $term->name;
			$params['term_id']             = $term->term_id;
			$params['parent_id']           = $term_info['parent_id'];
			$params['variation_id']        = $product->is_type( 'variation' ) ? $product->get_parent_id() : 0;
			$params['depth']               = $depth;
			$output[]                      = $params;

			// Automatically index implicit parents
			while ( $depth > 0 ) {
				$term_id   = $term_info['parent_id'];
				$term_info = $hierarchy[ $term_id ];
				$depth     = $depth - 1;

				if ( ! isset( $used_terms[ $term_id ] ) ) {
					$used_terms[ $term_id ] = true;

					$params                        = $defaults;
					$params['facet_value']         = $term_info['slug'];
					$params['facet_display_value'] = $term_info['name'];
					$params['term_id']             = $term_id;
					$params['parent_id']           = $term_info['parent_id'];
					$params['variation_id']        = $product->is_type( 'variation' ) ? $product->get_parent_id() : 0;
					$params['depth']               = $depth;
					$output[]                      = $params;
				}
			}
		}

		return $output;
	}

	/**
	 * Helper function that generates an array of taxonomy related data.
	 *
	 * Data is then processed and terms without numbers in their name are removed
	 * from the list.
	 *
	 * @param array $defaults
	 * @param string $post_id
	 * @param string $taxonomy
	 * @return array
	 */
	public static function generate_ranged_taxonomy_index_data( array $defaults, string $post_id, string $taxonomy ) {

		$output = self::generate_taxonomy_index_data( $defaults, $post_id, $taxonomy );

		if ( ! empty( $output ) ) {
			foreach ( $output as $index => $term ) {
				if ( ! preg_match( '~[0-9]+~', $term['facet_display_value'] ) ) {
					unset( $output[ $index ] );
				}

				$output[ $index ]['facet_value'] = preg_replace( '/[^0-9]/', '', $output[ $index ]['facet_value'] );
			}
		}

		return $output;
	}

	/**
	 * Find the highest price indexed and save it into the
	 * database.
	 *
	 * @param Filter $filter
	 * @return mixed
	 */
	public static function calculate_max_price( Filter $filter ) {
		$db = wcf()->get_service( 'db' );

		$highest = $db::table( 'wcf_index' )
			->selectRaw( 'MAX(CAST(facet_value AS SIGNED))' )
			->where( 'filter_id', $filter->getID() )
			->get();

		if ( $highest instanceof Collection ) {
			$highest = current( (array) $highest->first() );
		}

		if ( is_numeric( $highest ) ) {
			update_option( 'wcf_highest_price', $highest );
		}

		return $highest;
	}

	/**
	 * Flatten the collection of results and return only the post_id.
	 * Usually used after the search query has been built.
	 *
	 * @param array|Collection $results
	 * @return array
	 */
	public static function flatten_results( $results ) {

		if ( is_array( $results ) && ! $results instanceof Collection ) {
			return $results;
		}

		$results = $results->map(
			function ( $results ) {
				return $results->only( [ 'post_id' ] );
			}
		);

		return $results->flatten()->unique()->all();
	}

	/**
	 * Generate child filters for an attribute type of filter.
	 *
	 * @param Attribute $filter
	 * @param array $groups
	 * @return void
	 */
	public static function generate_child_filters( $filter, $groups ) {
		$attributes_taxonomies = Products::get_registered_attributes();

		if ( ! empty( $attributes_taxonomies ) && is_array( $attributes_taxonomies ) ) {
			foreach ( $attributes_taxonomies as $slug => $label ) {

				$child_filter = Filter::create(
					[
						'name'          => $label,
						'slug'          => 'attribute_' . sanitize_title( $slug ),
						'filter_by'     => 'attributes',
						'options'       => [
							'filter_type'        => $filter->get_option( 'filter_type' ),
							'attributes_mode'    => 'specific',
							'specific_attribute' => $slug,
							'image_display_mode' => $filter->get_option( 'image_display_mode' )
						],
						'priority'      => $filter->priority,
						'parent_filter' => $filter->getID()
					]
				);

				if ( $child_filter instanceof Filter && ! empty( $child_filter->getID() ) && is_array( $groups ) && ! empty( $groups ) ) {
					$child_filter->update_groups( $groups );
				}
			}
		}
	}

	/**
	 * Determine if we're on a product taxonomy page.
	 *
	 * @return boolean
	 */
	public static function is_taxonomy_page() {
		$queried_object        = get_queried_object();
		$registered_taxonomies = Products::get_registered_taxonomies( false, true );

		return is_product_category() || is_product_tag() || ( is_tax() && array_key_exists( $queried_object->taxonomy, $registered_taxonomies ) );
	}

	/**
	 * Catch the current query parameters but load all posts.
	 *
	 * @return array
	 */
	public static function get_current_query_object_ids() {
		global $wp_query;

		// Only get relevant post IDs
		$args = array_merge(
			$wp_query->query_vars,
			[
				'paged'                  => 1,
				'posts_per_page'         => -1,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				'cache_results'          => false,
				'no_found_rows'          => true,
				'nopaging'               => true, // prevent "offset" issues
				'woocommerce-filters'    => false,
				'fields'                 => 'ids',
			]
		);

		$query = new \WP_Query( $args );

		return $query->posts;
	}

	/**
	 * Helper function to determine if fallback mode is enabled or not.
	 *
	 * @return boolean
	 */
	public static function is_using_fallback_mode() {
		/**
		 * Filter: determine if fallback mode should be used throughout the plugin.
		 *
		 * @param bool $fallback
		 * @return bool
		 */
		return apply_filters( 'wcf_fallback_mode', false );
	}

	/**
	 * Recursively remove empty values for an array.
	 *
	 * @param array $haystack
	 * @return array
	 */
	public static function array_remove_empty( $haystack ) {
		foreach ( $haystack as $key => $value ) {
			if ( is_array( $value ) ) {
				$haystack[ $key ] = self::array_remove_empty( $haystack[ $key ] );
			}

			if ( empty( $haystack[ $key ] ) ) {
				unset( $haystack[ $key ] );
			}
		}

		return $haystack;
	}

	/**
	 * Sort collection by the display value.
	 *
	 * @param EloquentCollection $items
	 * @return Collection
	 */
	public static function order_numerical_collection( EloquentCollection $items ) {
		$items = $items->each(
			function ( $item, $key ) {
				$item->setAttribute( 'number', (int) filter_var( $item->facet_display_value, FILTER_SANITIZE_NUMBER_INT ) );
			}
		);

		return $items->sortBy( 'facet_display_value', SORT_NATURAL )->values();
	}

	/**
	 * Determines if a given custom field exists.
	 *
	 * @param string $meta_key
	 * @return boolean
	 */
	public static function custom_field_exists( string $meta_key ) {
		$db = wcf()->get_service( 'db' );

		return $db::table( 'postmeta' )
			->where( 'meta_key', $meta_key )
			->exists();
	}

	/**
	 * Forcefully inject additional attributes to instances of filters
	 * by passing an key => value array.
	 *
	 * @param Collection $filters the filters for which new attributes will be injected.
	 * @param array|bool $config when an array, inject it as attributes.
	 * @return Collection
	 */
	public static function set_additional_attributes( Collection $filters, $config ) {

		$filters->each(
			function( $filter ) use ( $config ) {
				if ( ! empty( $config ) && is_array( $config ) ) {
					foreach ( $config as $key => $value ) {
						$filter->setAttribute( $key, $value );
					}
				}
			}
		);

		return $filters;
	}

	/**
	 * Get the list of default excluded terms that should not be
	 * displayed inside filters.
	 *
	 * @param string $taxonomy
	 * @return array
	 */
	public static function get_default_excluded_terms( $taxonomy ) {

		$exclude_uncategorized = apply_filters( 'wcf_exclude_uncategorised', $taxonomy === 'product_cat', $taxonomy );

		if ( $exclude_uncategorized ) {
			return [ (int) get_option( 'default_product_cat', 0 ) ];
		}

		return apply_filters( 'wcf_default_excluded_terms', [], $taxonomy );
	}

	/**
	 * Determine if the query's `post_type` argument is using one of the allowed post types.
	 *
	 * We check for the `product_variation` post type too due to some of the integrations
	 * that we provide.
	 *
	 * @param \WP_Query $query
	 * @return bool
	 */
	public static function query_has_product_post_type( $query ) {

		$allowed = [
			'product',
			'product_variation'
		];

		if ( is_array( $query->get( 'post_type' ) ) && $query->get( 'post_type' ) == $allowed ) {
			return true;
		}

		return in_array( $query->get( 'post_type' ), $allowed, true );
	}

	/**
	 * Determine if the given slug is reserved by WordPress.
	 *
	 * @param string $slug the slug to check.
	 * @return boolean true if the slug is reserved, false otherwise.
	 */
	public static function is_reserved_slug( $slug ) {

		$reserved = [
			'action',
			'attachment',
			'attachment_id',
			'author',
			'author_name',
			'calendar',
			'cat',
			'category',
			'category__and',
			'category__in',
			'category__not_in',
			'category_name',
			'comments_per_page',
			'comments_popup',
			'custom',
			'customize_messenger_channel',
			'customized',
			'cpage',
			'day',
			'debug',
			'embed',
			'error',
			'exact',
			'feed',
			'fields',
			'hour',
			'link_category',
			'm',
			'minute',
			'monthnum',
			'more',
			'name',
			'nav_menu',
			'nonce',
			'nopaging',
			'offset',
			'order',
			'orderby',
			'p',
			'page',
			'page_id',
			'paged',
			'pagename',
			'pb',
			'perm',
			'post',
			'post__in',
			'post__not_in',
			'post_format',
			'post_mime_type',
			'post_status',
			'post_tag',
			'post_type',
			'posts',
			'posts_per_archive_page',
			'posts_per_page',
			'preview',
			'robots',
			's',
			'search',
			'second',
			'sentence',
			'showposts',
			'static ',
			'status',
			'subpost',
			'subpost_id',
			'tag',
			'tag__and',
			'tag__in',
			'tag__not_in',
			'tag_id',
			'tag_slug__and',
			'tag_slug__in',
			'taxonomy',
			'tb',
			'term',
			'terms',
			'theme',
			'title',
			'type',
			'types',
			'w',
			'withcomments',
			'withoutcomments',
			'year',
		];

		return in_array( $slug, $reserved, true );
	}

}
