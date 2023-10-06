<?php
/**
 * @package   Barn2\woocommerce-product-filters
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */

namespace Barn2\Plugin\WC_Filters;

use Barn2\Plugin\WC_Filters\Model\Filter;
use WP_Query;
use Barn2\Plugin\WC_Filters\Model\Index;
use Barn2\Plugin\WC_Filters\Dependencies\Illuminate\Support\Collection;
use Barn2\Plugin\WC_Filters\Dependencies\Illuminate\Support\Str;
use Barn2\Plugin\WC_Filters\Model\Filters\Attribute;
use Barn2\Plugin\WC_Filters\Model\Filters\Price;
use Barn2\Plugin\WC_Filters\Model\Group;
use Barn2\Plugin\WC_Filters\Model\Indexable_Interface;
use Barn2\Plugin\WC_Filters\Utils\Filters;
use Barn2\Plugin\WC_Filters\Utils\Products;
use Barn2\Plugin\WC_Filters\Dependencies\Lib\Registerable;

use function Barn2\Plugin\WC_Filters\wcf;

/**
 * Handles indexing of posts.
 */
class Indexer implements Registerable {

	/**
	 * Is wp_insert_post running?
	 *
	 * @var boolean
	 */
	public $is_saving = false;

	/**
	 * Number of posts to index before updating progress.
	 *
	 * @var integer
	 */
	public $chunk_size = 30;

	/**
	 * The individual facet (filter) being processed.
	 *
	 * @var Filter
	 */
	public $facet;

	/**
	 * Hook into WP to keeps the index up to date.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'save_post', [ $this, 'save_post' ] );
		add_action( 'delete_post', [ $this, 'delete_post' ] );
		add_action( 'edited_term', [ $this, 'edit_term' ], 10, 3 );
		add_action( 'delete_term', [ $this, 'delete_term' ], 10, 3 );
		add_action( 'set_object_terms', [ $this, 'set_object_terms' ] );
		add_filter( 'wp_insert_post_parent', [ $this, 'is_wp_insert_post' ] );
		add_action( 'woocommerce_product_import_inserted_product_object', [ $this, 'index_on_csv_import' ], 10, 2 );
		add_action( 'woocommerce_attribute_updated', [ $this, 'update_attribute_filters' ], 10, 3 );
		add_action( 'woocommerce_attribute_added', [ $this, 'create_attribute_filter' ], 10, 2 );
		add_action( 'woocommerce_attribute_deleted', [ $this, 'delete_attribute_filter' ], 10, 3 );
	}

	/**
	 * Get the chunk size for batch indexing.
	 *
	 * @return int|string
	 */
	public function get_chunk_size() {
		/**
		 * Filter: allows developers to change the amount of posts processed with each batch.
		 * Default is 30.
		 *
		 * @param string|int $chunk_size
		 * @return string|int
		 */
		return apply_filters( 'wcf_batch_index_chunk_size', $this->chunk_size );
	}

	/**
	 * Update the index when a post is saved.
	 *
	 * @param string $post_id
	 * @return void
	 */
	public function save_post( $post_id ) {
		if ( defined( 'DOING_AUTOSAVE' ) && \DOING_AUTOSAVE ) {
			return;
		}

		if ( false !== wp_is_post_revision( $post_id ) ) {
			return;
		}

		if ( 'auto-draft' === get_post_status( $post_id ) ) {
			return;
		}

		if ( get_post_type( $post_id ) !== 'product' ) {
			return;
		}

		$product    = wc_get_product( $post_id );
		$visibility = $product->get_catalog_visibility();

		if ( $visibility === 'hidden' && $this->should_skip_hidden_products() ) {
			Index::where( 'post_id', $post_id )->delete();
		} else {
			$this->index( $post_id );
		}

		/**
		 * Action: hook into the `save_post` WP hook but after
		 * we've executed our custom logic that indexes products.
		 *
		 * @param string|int $product_id
		 * @param object $product WC instance of a product
		 */
		do_action( 'wcf_after_product_save', $post_id, $product );

		$this->is_saving = false;

	}

	/**
	 * Index products when they're added via csv import.
	 *
	 * @param object $object Product instance.
	 * @param array $data
	 * @return void
	 */
	public function index_on_csv_import( $object, $data ) {

		$this->index( $object->get_id() );

		/**
		 * Action: hook into the csv import process,
		 * fires after the product has been imported & indexed.
		 *
		 * @param object $product WC instance of a product
		 */
		do_action( 'wcf_after_product_import', $product );

	}

	/**
	 * Delete indexed data when a post is deleted.
	 *
	 * @param string $post_id
	 * @return void
	 */
	public function delete_post( $post_id ) {

		if ( get_post_type( $post_id ) !== 'product' ) {
			return;
		}

		Index::byID( $post_id )->delete();

		/**
		 * Action: hook into the product delete process and
		 * after the index has been cleared of data related to
		 * the product that has been deleted.
		 *
		 * @param string|int $post_id
		 */
		do_action( 'wcf_after_product_delete', $post_id );

	}

	/**
	 * Update indexed data when a product related term (taxonomy & attribute) is saved.
	 *
	 * @param string $term_id
	 * @param string $tt_id
	 * @param string $taxonomy
	 * @return void
	 */
	public function edit_term( $term_id, $tt_id, $taxonomy ) {

		$taxonomies = Products::get_registered_taxonomies();
		$attributes = wc_get_attribute_taxonomy_names();
		$taxonomies = array_keys( $taxonomies );

		if ( empty( $attributes ) || ! is_array( $attributes ) ) {
			$attributes = [];
		}

		if ( ! in_array( $taxonomy, $taxonomies, true ) && ! in_array( $taxonomy, $attributes, true ) ) {
			return;
		}

		$term = get_term( $term_id, $taxonomy );
		$slug = $this->safe_value( $term->slug );

		$args = [
			'facet_value'         => $slug,
			'facet_display_value' => stripslashes( sanitize_text_field( $term->name ) ),
		];

		$index = Index::where( 'term_id', $term_id )->update( $args );

	}

	/**
	 * When an attribute has been updated, find any attribute filters
	 * and update it's details.
	 *
	 * @param int    $id       Added attribute ID.
	 * @param array  $data     Attribute data.
	 * @param string $old_slug Attribute old name.
	 * @return void
	 */
	public function update_attribute_filters( $id, $data, $old_slug ) {
		$taxonomy_name = $data['attribute_label'];
		$taxonomy_slug = $data['attribute_name'];

		$filter = Filter::where( 'slug', "attribute_{$old_slug}" )
					->where( 'parent_filter', '>', 0 )
					->first();

		if ( $filter instanceof Attribute && $filter->get_attributes_mode() === 'specific' && $taxonomy_slug === $filter->get_option( 'specific_attribute' ) ) {
			$filter->update(
				[
					'name' => stripslashes( sanitize_text_field( $taxonomy_name ) ),
					'slug' => 'attribute_' . $this->safe_value( $taxonomy_slug ),
				]
			);

			$index = Index::where( 'filter_id', $filter->getID() )->update(
				[
					'facet_name' => stripslashes( sanitize_text_field( $taxonomy_name ) )
				]
			);
		}
	}

	/**
	 * When a new attribute has been added check if an "all"
	 * attribute exists, if it does - create the child filter.
	 *
	 * @param int   $id   Added attribute ID.
	 * @param array $data Attribute data.
	 * @return void
	 */
	public function create_attribute_filter( $id, $data ) {

		$filter = Filter::where( 'filter_by', 'attributes' )
					->whereRaw( "JSON_CONTAINS(JSON_EXTRACT(options, '$.attributes_mode'), '\"all\"')" )
					->first();

		if ( $filter instanceof Attribute && $filter->get_attributes_mode() === 'all' ) {
			$child_filter = Filter::create(
				[
					'name'          => stripslashes( sanitize_text_field( $data['attribute_label'] ) ),
					'slug'          => 'attribute_' . sanitize_title( $data['attribute_name'] ),
					'filter_by'     => 'attributes',
					'options'       => [
						'filter_type'        => $filter->get_option( 'filter_type' ),
						'attributes_mode'    => 'specific',
						'specific_attribute' => sanitize_title( $data['attribute_name'] ),
					],
					'priority'      => $filter->priority,
					'parent_filter' => $filter->getID()
				]
			);

			$groups     = $filter->get_groups();
			$groups_ids = [];

			if ( ! empty( $groups ) ) {
				/** @var Group */
				foreach ( $groups as $group ) {
					$groups_ids[] = $group->getID();
				}
			}

			if ( $child_filter instanceof Filter && ! empty( $child_filter->getID() ) && is_array( $groups_ids ) && ! empty( $groups_ids ) ) {
				$child_filter->update_groups( $groups_ids );
			}
		}

	}

	/**
	 * Delete the related attribute filter when an attribute is removed.
	 *
	 * @param int    $id       Attribute ID.
	 * @param string $name     Attribute name.
	 * @param string $taxonomy Attribute taxonomy name.
	 * @return void
	 */
	public function delete_attribute_filter( $id, $name, $taxonomy ) {

		$filters = Filter::where( 'slug', "attribute_{$name}" )->where( 'parent_filter', '>', 0 )->get();

		if ( $filters instanceof Collection && $filters->isNotEmpty() ) {
			/** @var Attribute $filter */
			foreach ( $filters as $filter ) {
				$filter->delete();
			}
		}

	}

	/**
	 * Cleanup term related data from the index, when the term is deleted.
	 *
	 * @param string $term_id
	 * @param string $tt_id
	 * @param string $taxonomy
	 * @return void
	 */
	public function delete_term( $term_id, $tt_id, $taxonomy ) {

		$taxonomies = Products::get_registered_taxonomies();
		$attributes = wc_get_attribute_taxonomy_names();
		$taxonomies = array_keys( $taxonomies );

		if ( empty( $attributes ) || ! is_array( $attributes ) ) {
			$attributes = [];
		}

		if ( ! in_array( $taxonomy, $taxonomies, true ) && ! in_array( $taxonomy, $attributes, true ) ) {
			return;
		}

		Index::where( 'term_id', $term_id )->delete();

	}

	/**
	 * We're hijacking wp_insert_post_parent
	 * Prevent our set_object_terms() hook from firing within wp_insert_post
	 *
	 * @param string $post_parent
	 * @return mixed
	 */
	public function is_wp_insert_post( $post_parent ) {
		$this->is_saving = true;
		return $post_parent;
	}

	/**
	 * Support for manual taxonomy associations.
	 *
	 * @param string|int $object_id
	 * @return void
	 */
	public function set_object_terms( $object_id ) {
		if ( ! $this->is_saving ) {
			$this->index( $object_id );
		}
	}

	/**
	 * Rebuild the index for all or a given post.
	 *
	 * @param boolean|string|int $post_id false to re-index everything.
	 * @return void
	 */
	public function index( $post_id = false ) {

		// Abort if trying to process anything else other than products.
		if ( $post_id && get_post_type( $post_id ) !== 'product' ) {
			return;
		}

		// Delete all records for the given post.
		Index::byID( $post_id )->delete();

		$post_ids = $this->get_post_ids_to_index( $post_id );
		$filters  = Filter::all();

		foreach ( $post_ids as $counter => $post_id ) {
			$this->index_post( $post_id, $filters );
		}

		/**
		 * Hook: allow developers to hook into the indexing process once it's complete.
		 */
		do_action( 'wcf_indexer_complete' );

	}

	/**
	 * Run the indexing process of all products in batches.
	 *
	 * @param integer $offset WP_Query offset parameter.
	 * @param integer $limit WP_Query posts_per_page parameter.
	 * @return void
	 */
	public function index_batch( $offset = 0, $limit = 0 ) {

		// Truncate the content of the index table when we're starting the batch processing.
		if ( $offset === 0 ) {
			Index::truncate();
			$this->set_index_running( true );
		}

		$post_ids = $this->get_post_ids_to_index( false, $offset, $limit );

		if ( ! empty( $post_ids ) && is_array( $post_ids ) ) {

			$filters = Filter::all();

			foreach ( $post_ids as $counter => $post_id ) {
				$this->index_post( $post_id, $filters );
			}

			// Determine if we should continue scheduling new listings for the indexing.
			if ( $offset === 0 ) {
				$offset = $limit;
			} else {
				$offset = $offset + $limit;
			}

			// Schedule next batch.
			$queue = wcf()->get_service( 'queue' );

			$queue->schedule_single(
				time(),
				'wcf_batch_index',
				[
					'offset' => $offset,
					'limit'  => $limit,
				],
				'wcf_batch_index'
			);

		}

		// Toggle indexing status off.
		if ( empty( $post_ids ) ) {
			$this->set_index_running( false );
		}

		/**
		 * Hook: allow developers to hook into the batch indexing process once a batch is complete.
		 *
		 * @param array $post_ids list of posts that were processed.
		 * @param string|int $offset wp query offeset processed.
		 * @param string|int $limit batch amount processed.
		 */
		do_action( 'wcf_batch_index_complete', $post_ids, $offset = 0, $limit = 0 );

	}

	/**
	 * Get an array of post IDs to index.
	 *
	 * @param boolean|string|int $post_id
	 * @param bool|int|string $offset
	 * @param bool|int|string $limit
	 * @return array
	 */
	public function get_post_ids_to_index( $post_id = false, $offset = false, $limit = false ) {
		$args = [
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'orderby'        => 'ID',
			'cache_results'  => false,
			'no_found_rows'  => true,
		];

		if ( is_int( $post_id ) ) {
			$args['p']              = $post_id;
			$args['posts_per_page'] = 1;
		}

		if ( $offset !== false && $limit !== false ) {
			$args['offset']         = $offset;
			$args['posts_per_page'] = $limit;
		}

		$query = new WP_Query( $args );
		return (array) $query->posts;
	}

	/**
	 * Determine if hidden products should be skipped during indexing.
	 *
	 * @return boolean
	 */
	public function should_skip_hidden_products() {
		return apply_filters( 'wcf_indexer_skip_hidden_products', true );
	}

	/**
	 * Index an individual post.
	 *
	 * @param string $post_id
	 * @param Collection $filters
	 * @return void
	 */
	public function index_post( $post_id, $filters ) {

		$product = wc_get_product( $post_id );

		if ( $product->get_catalog_visibility() === 'hidden' && $this->should_skip_hidden_products() ) {
			return;
		}

		if ( ! $product->is_in_stock() ) {
			return;
		}

		/** @var Filter $facet */
		foreach ( $filters as $facet ) {

			// Do not index search facets
			// @TODO

			$this->facet = $facet;

			$source = $facet->filter_by;

			// Set default row params.
			$defaults = [
				'post_id'             => $post_id,
				'filter_id'           => $facet->getID(),
				'facet_name'          => $facet->name,
				'facet_source'        => $source,
				'facet_value'         => '',
				'facet_display_value' => '',
				'term_id'             => 0,
				'parent_id'           => 0,
				'depth'               => 0,
				'variation_id'        => 0,
			];

			$rows = $this->get_row_data( $defaults );

			foreach ( $rows as $row ) {
				$this->index_row( $row );
			}

			if ( $facet instanceof Price ) {
				Filters::calculate_max_price( $facet );
			}
		}

		/**
		 * Action: hook into the indexing process after a product
		 * has been indexed.
		 *
		 * @param string|int $post_id the id of the product
		 * @param object $product instance of a WC product
		 * @param array $filters collection of filters for which the index is being generated
		 * @param Indexer $indexer instance of the indexer
		 */
		do_action( 'wcf_after_product_index', $post_id, $product, $filters, $this );

	}

	/**
	 * Find the data for the specified facet.
	 *
	 * @param array $defaults
	 * @return array
	 */
	private function get_row_data( $defaults ) {

		$output  = [];
		$facet   = $this->facet;
		$post_id = $defaults['post_id'];
		$source  = $facet->filter_by;

		if ( $facet instanceof Indexable_Interface ) {
			$output = $facet->generate_index_data( $defaults, $post_id );
		}

		return $output;

	}

	/**
	 * Pre-process the indexed data before insertion.
	 *
	 * @param array $params indexed data for the processed facet
	 * @return void
	 */
	public function index_row( $params ) {

		/**
		 * Filter: allow hooks to bypass the row insertion by returning anything else other than an array.
		 *
		 * @param array $params indexed data for the facet
		 * @param Indexer $indexer instance of the indexer
		 * @return array
		 */
		$params = apply_filters( 'wcf_index_row', $params, $this );

		// Allow hooks to bypass the row insertion.
		if ( is_array( $params ) ) {
			$this->insert( $params );
		}

	}

	/**
	 * Insert the indexed data into the database table.
	 *
	 * @param array $params indexed data
	 * @return string ID number of the row belonging to the data
	 */
	public function insert( $params ) {

		$value         = $params['facet_value'];
		$display_value = $params['facet_display_value'];

		// Only accept scalar values
		if ( '' === $value || ! is_scalar( $value ) ) {
			return;
		}

		$row = Index::create(
			[
				'post_id'             => $params['post_id'],
				'filter_id'           => $params['filter_id'],
				'facet_name'          => $params['facet_name'],
				'facet_value'         => $this->safe_value( $value ),
				'facet_display_value' => $display_value,
				'term_id'             => $params['term_id'],
				'parent_id'           => $params['parent_id'],
				'depth'               => $params['depth'],
				'variation_id'        => $params['variation_id']
			]
		);

		return $row;

	}

	/**
	 * Hash a facet value if needed
	 *
	 * @param string $value
	 * @return string
	 */
	private function safe_value( $value ) {
		$value = remove_accents( $value );

		if ( preg_match( '/[^a-z0-9_.\- ]/i', $value ) ) {
			if ( ! preg_match( '/^\d{4}-(0[1-9]|1[012])-([012]\d|3[01])/', $value ) ) {
				$value = md5( $value );
			}
		}

		$value = str_replace( ' ', '-', strtolower( $value ) );
		$value = preg_replace( '/[-]{2,}/', '-', $value );
		$value = ( 50 < strlen( $value ) ) ? substr( $value, 0, 50 ) : $value;
		return $value;
	}

	/**
	 * Set a flag in the database to mark the index as running
	 *
	 * @param boolean $running whether or not the index is running.
	 * @param boolean $silently whether or not the indexer should be running silently (no admin notice displayed)
	 * @return void
	 */
	public function set_index_running( bool $running, bool $silently = false ) {
		if ( $running ) {
			update_option( 'wcf_index_running', true );
			if ( $silently ) {
				update_option( 'wcf_index_running_silent', true );
			}
		} else {
			delete_option( 'wcf_index_running' );
			delete_option( 'wcf_index_running_silent' );
		}
	}

	/**
	 * Determine if batch indexing is currently running.
	 *
	 * @return boolean
	 */
	public function is_batch_index_running() {
		return get_option( 'wcf_index_running', false ) === '1';
	}

	/**
	 * Determine if the indexing is silently running.
	 *
	 * @return boolean
	 */
	public function is_silently_running() {
		return get_option( 'wcf_index_running_silent', false ) === '1';
	}

}
