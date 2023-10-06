<?php
/**
 * @package   Barn2\woocommerce-product-filters
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */

namespace Barn2\Plugin\WC_Filters\Utils;

use Barn2\Plugin\WC_Filters\Plugin;

/**
 * Utility methods to work with terms.
 */
class Terms {

	/**
	 * Cached terms.
	 *
	 * @var array
	 */
	public static $term_cache;

	/**
	 * Check if term has color.
	 *
	 * @param string $term_id
	 * @return boolean
	 */
	public static function has_color( $term_id ) {
		return get_term_meta( $term_id, Plugin::META_PREFIX . 'term_has_color', true );
	}

	/**
	 * Get the thumbnail ID attached to a term.
	 *
	 * @param string $term_id
	 * @return string
	 */
	public static function get_term_image( $term_id ) {
		return wp_get_attachment_url( get_term_meta( $term_id, 'thumbnail_id', true ) );
	}

	/**
	 * Get the color of a term.
	 *
	 * @param string $term_id
	 * @return string
	 */
	public static function get_color( $term_id ) {
		return get_term_meta( $term_id, Plugin::META_PREFIX . 'term_color', true );
	}

	/**
	 * Get an array of term information, including depth
	 *
	 * @param string $taxonomy
	 * @return array
	 */
	public static function get_term_depths( $taxonomy ) {

		if ( isset( self::$term_cache[ $taxonomy ] ) ) {
			return self::$term_cache[ $taxonomy ];
		}

		$output  = [];
		$parents = [];

		$terms = self::get_terms( $taxonomy );

		// Get term parents
		foreach ( $terms as $term ) {
			$parents[ $term->term_id ] = $term->parent;
		}

		// Build the term array
		foreach ( $terms as $term ) {
			$output[ $term->term_id ] = [
				'term_id'   => $term->term_id,
				'name'      => $term->name,
				'slug'      => $term->slug,
				'parent_id' => $term->parent,
				'depth'     => 0
			];

			$current_parent = $term->parent;
			while ( 0 < (int) $current_parent ) {
				$current_parent = $parents[ $current_parent ];
				$output[ $term->term_id ]['depth']++;

				// Prevent an infinite loop
				if ( 50 < $output[ $term->term_id ]['depth'] ) {
					break;
				}
			}
		}

		self::$term_cache[ $taxonomy ] = $output;

		return $output;

	}

	/**
	 * Get terms across all languages (thanks, WPML)
	 *
	 * @param string $taxonomy
	 * @return array
	 */
	public static function get_terms( $taxonomy ) {
		global $wpdb;

		$sql = "
        SELECT t.term_id, t.name, t.slug, tt.parent FROM {$wpdb->term_taxonomy} tt
        INNER JOIN {$wpdb->terms} t ON t.term_id = tt.term_id
        WHERE tt.taxonomy = %s";

		return $wpdb->get_results( $wpdb->prepare( $sql, $taxonomy ) );
	}

	/**
	 * Retrieve taxonomy tree formatted for the frontend submission field's options property.
	 *
	 * @param string  $taxonomy the taxonomy to analyze.
	 * @param integer $parent the id of the parent term to analyze.
	 * @param array   $include the term ids to specifically analyze.
	 * @return array
	 */
	public static function get_taxonomy_hierarchy( $taxonomy, $parent = 0, $include = [] ) {

		$taxonomy = is_array( $taxonomy ) ? array_shift( $taxonomy ) : $taxonomy;

		if ( ! taxonomy_exists( $taxonomy ) ) {
			return [];
		}

		$terms = get_terms(
			$taxonomy,
			[
				'parent'     => $parent,
				'hide_empty' => false,
				'include'    => $include,
				'exclude'    => Filters::get_default_excluded_terms( $taxonomy ),
				'orderby'    => 'menu_order',
			]
		);

		$children = [];

		foreach ( $terms as $term ) {

			$term->children = self::get_taxonomy_hierarchy( $taxonomy, $term->term_id );

			$new_item = [
				'id'    => $term->term_id,
				'label' => html_entity_decode( $term->name ),
				'slug'  => $term->slug,
				'depth' => count( get_ancestors( $term->term_id, $taxonomy ) )
			];

			if ( is_array( $term->children ) && ! empty( $term->children ) ) {
				$new_item['children'] = $term->children;
			}

			$children[] = $new_item;

		}

		return $children;

	}

	/**
	 * Split a list of hierachical terms into grouped and ungrouped.
	 *
	 * Grouped: are terms that have child terms.
	 * Ungrouped: are terms that do not have child terms.
	 *
	 * This is needed for hierarchy to work for inputs on the frontend.
	 *
	 * @param array $terms
	 * @return array
	 */
	public static function get_grouped_taxonomy_tree( array $terms ) {
		$grouped   = [];
		$ungrouped = [];

		foreach ( $terms as $term ) {
			if ( isset( $term['children'] ) ) {

				$children = [];

				foreach ( $term['children'] as $child ) {
					$children[] = $child;
				}

				$grouped[ html_entity_decode( $term['label'] ) ] = $children;
			} else {
				$ungrouped[] = $term;
			}
		}

		$results = [
			'__ungrouped' => $ungrouped,
		];

		if ( ! empty( $grouped ) ) {
			$results = $grouped + $results;
		}

		return $results;
	}

}
