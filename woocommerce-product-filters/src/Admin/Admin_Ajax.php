<?php
/**
 * @package   Barn2\woocommerce-product-filters
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */

namespace Barn2\Plugin\WC_Filters\Admin;

use Barn2\Plugin\WC_Filters\Dependencies\Illuminate\Support\Collection;
use Barn2\Plugin\WC_Filters\Diff;
use Barn2\Plugin\WC_Filters\Model\Filter;
use Barn2\Plugin\WC_Filters\Model\Filters\Attribute;
use Barn2\Plugin\WC_Filters\Model\Filters\Color;
use Barn2\Plugin\WC_Filters\Model\Filters\Taxonomy;
use Barn2\Plugin\WC_Filters\Model\Group;
use Barn2\Plugin\WC_Filters\Model\Index;
use Barn2\Plugin\WC_Filters\Utils\Products;
use Barn2\Plugin\WC_Filters\Utils\Settings;
use Barn2\Plugin\WC_Filters\Dependencies\Lib\Registerable;
use Barn2\Plugin\WC_Filters\Utils\Filters;

/**
 * Handles all ajax requests from the admin panel for the plugin.
 */
class Admin_Ajax implements Registerable {

	/**
	 * Hook into WP
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'wp_ajax_wcf_save_filter', [ $this, 'save_filter' ] );
		add_action( 'wp_ajax_wcf_get_filters', [ $this, 'get_filters' ] );
		add_action( 'wp_ajax_wcf_delete_filter', [ $this, 'delete_filter' ] );
		add_action( 'wp_ajax_wcf_save_priority', [ $this, 'save_priority' ] );
		add_action( 'wp_ajax_wcf_get_terms_taxonomies', [ $this, 'get_terms_taxonomies' ] );
		add_action( 'wp_ajax_wcf_get_group', [ $this, 'get_group' ] );
		add_action( 'wp_ajax_wcf_save_group', [ $this, 'save_group' ] );
		add_action( 'wp_ajax_wcf_create_group', [ $this, 'create_group' ] );
		add_action( 'wp_ajax_wcf_get_groups', [ $this, 'get_groups' ] );
		add_action( 'wp_ajax_wcf_delete_group', [ $this, 'delete_group' ] );
		add_action( 'wp_ajax_wcf_duplicate_group', [ $this, 'duplicate_group' ] );
	}

	/**
	 * Send a json error back to the react app.
	 *
	 * @param string $message
	 * @return void
	 */
	public function send_error( string $message ) {
		wp_send_json_error( [ 'error_message' => $message ], 403 );
	}

	/**
	 * Create or save an existing filter.
	 *
	 * @return void
	 */
	public function save_filter() {

		check_ajax_referer( 'wcf_save_filter_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			$this->send_error( esc_html__( 'You are not authorized', 'woocommerce-product-filters' ) );
		}

		$filter_name = isset( $_POST['filter_name'] ) ? stripslashes( sanitize_text_field( $_POST['filter_name'] ) ) : false;
		$filter_by   = isset( $_POST['filter_by'] ) ? sanitize_text_field( $_POST['filter_by'] ) : false;
		$editing     = isset( $_POST['editing'] ) ? absint( $_POST['editing'] ) : false;
		$groups      = isset( $_POST['groups'] ) ? wc_clean( $_POST['groups'] ) : [];
		$slug        = sanitize_title_with_dashes( $filter_name, '', 'save' );

		if ( ! empty( $groups ) && ! is_array( $groups ) ) {
			$groups = [ $groups ];
		}

		if ( ! $filter_name ) {
			$this->send_error( esc_html__( 'Please enter a name for this filter', 'woocommerce-product-filters' ) );
		}

		// We only need some values not all of them.
		$options = ( new Collection( wc_clean( $_POST ) ) )->except( [ 'filter_name', 'filter_by', 'action', 'nonce', 'editing', 'groups' ] );
		$filter  = false;

		if ( $editing ) {
			$filter = Filter::find( $editing );

			if ( ! $filter instanceof Filter ) {
				$this->send_error( esc_html__( 'Something went wrong while updating the filter', 'woocommerce-product-filters' ) );
			}

			$filter->update(
				[
					'name'      => $filter_name,
					'filter_by' => $filter_by,
					'options'   => $options->toArray(),
					'slug'      => $slug
				]
			);

			$indexed = Index::where( 'filter_id', $filter->getID() )->update(
				[
					'facet_name' => $filter_name
				]
			);

		} else {
			$filter = Filter::create(
				[
					'name'      => $filter_name,
					'slug'      => $slug,
					'filter_by' => $filter_by,
					'options'   => $options->toArray(),
					'priority'  => 0
				],
				is_array( $groups ) ? $groups : []
			);
		}

		if ( $filter instanceof Filter && ! empty( $filter->getID() ) ) {
			// Update the groups to which the filter belongs to if any has been selected.
			if ( is_array( $groups ) ) {
				$filter->update_groups( $groups );
			}

			wp_send_json_success(
				[
					'reindex' => Diff::is_reindex_needed()
				]
			);
		}

		$this->send_error( esc_html__( 'Something went wrong while creating the filter', 'woocommerce-product-filters' ) );
	}

	/**
	 * Get filters from the database ready for display in the list table.
	 * Load groups too.
	 *
	 * @return void
	 */
	public function get_filters() {

		check_ajax_referer( 'wcf_get_filters_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			$this->send_error( esc_html__( 'You are not authorized', 'woocommerce-product-filters' ) );
		}

		$filters = Filter::orderBy( 'priority', 'asc' )
					->orderBy( 'id', 'desc' )
					->where( 'parent_filter', 0 )
					->get();

		// Lazy load groups into the filters.
		if ( $filters && ! $filters->isEmpty() ) {
			$filters->each(
				function( $instance ) {
					$instance->append( 'groups' );
					$instance->append( 'groups_names' );
				}
			);
		}

		$groups = Group::orderBy( 'priority', 'asc' )
					->orderBy( 'id', 'desc' )
					->get();

		wp_send_json_success(
			[
				'filters' => $filters->toArray(),
				'groups'  => $groups->toArray()
			]
		);
	}

	/**
	 * Save the priority of groups.
	 *
	 * @return void
	 */
	public function save_priority() {

		check_ajax_referer( 'wcf_save_priority_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			$this->send_error( esc_html__( 'You are not authorized', 'woocommerce-product-filters' ) );
		}

		$data = isset( $_POST['data'] ) && ! empty( $_POST['data'] ) ? wc_clean( $_POST['data'] ) : [];

		if ( empty( $data ) || ! is_array( $data ) ) {
			$this->send_error( esc_html__( 'No records to update have been found.', 'woocommerce-product-filters' ) );
		}

		foreach ( $data as $group ) {
			$the_group = Group::find( $group['id'] );

			$the_group->update(
				[
					'priority' => absint( $group['priority'] )
				]
			);
		}

		wp_send_json_success();
	}

	/**
	 * Delete a filter from the database.
	 *
	 * @return void
	 */
	public function delete_filter() {

		check_ajax_referer( 'wcf_delete_item_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			$this->send_error( esc_html__( 'You are not authorized', 'woocommerce-product-filters' ) );
		}

		$item_id = isset( $_POST['item_id'] ) ? absint( $_POST['item_id'] ) : false;

		if ( ! $item_id ) {
			$this->send_error( esc_html__( 'No filter found matching the criteria.', 'woocommerce-product-filters' ) );
		}

		$filter = Filter::find( $item_id );

		if ( $filter ) {
			$filter->delete();
		}

		wp_send_json_success(
			[
				'reindex' => Diff::is_reindex_needed()
			]
		);
	}

	/**
	 * Duplicate a filter group.
	 *
	 * @return void
	 */
	public function duplicate_group(): void {
		check_ajax_referer( 'wcf_duplicate_group_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			$this->send_error( esc_html__( 'You are not authorized', 'woocommerce-product-filters' ) );
		}

		$item_id = isset( $_POST['item_id'] ) ? absint( $_POST['item_id'] ) : false;

		if ( ! $item_id ) {
			$this->send_error( esc_html__( 'No filter found matching the criteria.', 'woocommerce-product-filters' ) );
		}

		$group = Group::find( $item_id );

		if ( ! $group instanceof Group || empty( $group->getID() ) ) {
			$this->send_error( esc_html__( 'No filter found matching the criteria.', 'woocommerce-product-filters' ) );
		}

		$new_group = $group->replicate();
		$new_group->save();

		$filters = $group->get_filters();

		// Delete the previous filters.
		$new_group->update(
			[
				'name'     => sanitize_text_field( $new_group->name ) . ' - ' . esc_html__( 'Copy', 'woocommerce-product-filters' ),
				'filters'  => [],
				'priority' => absint( $new_group->priority + 1 )
			]
		);

		// Add filters to the new group.
		if ( ! empty( $filters ) ) {
			foreach ( $filters as $filter ) {
				$new_filter = $filter->replicate();
				$new_filter->save();

				$new_group->add_filter( $new_filter );
			}
		}

		wp_send_json_success(
			[
				'reindex' => true
			]
		);
	}

	/**
	 * Get the list of taxonomies and attributes available for products.
	 *
	 * @return void
	 */
	public function get_terms_taxonomies() {

		check_ajax_referer( 'wcf_get_terms_taxonomies_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			$this->send_error( esc_html__( 'You are not authorized', 'woocommerce-product-filters' ) );
		}

		$taxonomies   = Settings::parse_array_for_select_control( Products::get_registered_taxonomies( true ) );
		$empty_option = [
			[
				'value' => '',
				'label' => '',
			]
		];

		wp_send_json_success(
			[
				'taxonomies' => array_merge( $empty_option, $taxonomies ),
				'attributes' => Settings::parse_array_for_select_control( Products::get_registered_attributes() )
			]
		);
	}

	/**
	 * Save or create a group in the database.
	 *
	 * @return void
	 */
	public function save_group() {
		check_ajax_referer( 'wcf_save_group_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			$this->send_error( esc_html__( 'You are not authorized', 'woocommerce-product-filters' ) );
		}

		$group_name = isset( $_POST['name'] ) ? stripslashes( wc_clean( $_POST['name'] ) ) : false;

		if ( ! $group_name || empty( $group_name ) ) {
			$this->send_error( esc_html__( 'Please enter a name for the group.', 'woocommerce-product-filters' ) );
		}

		$group_id = isset( $_POST['group_id'] ) && ! empty( $_POST['group_id'] ) && is_numeric( $_POST['group_id'] ) ? absint( $_POST['group_id'] ) : false;

		if ( ! $group_id ) {
			$this->send_error( esc_html__( 'Something went wrong: could not update the selected group', 'woocommerce-product-filters' ) );
		}

		$group = Group::find( $group_id );

		if ( ! $group || ! $group instanceof Group ) {
			$this->send_error( esc_html__( 'Something went wrong: could not update the selected group', 'woocommerce-product-filters' ) );
		}

		$current_filters = $group->get_filters()->pluck( 'id' );
		$filters         = isset( $_POST['filters'] ) ? wc_clean( $_POST['filters'] ) : [];

		if ( ! $filters || empty( $filters ) ) {
			$this->send_error( esc_html__( 'Please add at least 1 filter to the group.', 'woocommerce-product-filters' ) );
		}

		foreach ( $filters as $filter ) {
			$this->validate_filter_settings( $filter );
		}

		$filters = $this->validate_custom_fields_settings( $filters );

		$filters         = new Collection( $filters );
		$updated_filters = new Collection();

		foreach ( $filters as $index => $filter ) {
			$filter = $this->create_or_update_filter( $filter, $group_id, $index );
			$updated_filters->push( $filter );
		}

		$filters = $this->maybe_attach_children( $updated_filters->pluck( 'id' ) );

		// Get filters that have been deleted and delete them.
		$missing = $current_filters->diff( $updated_filters->pluck( 'id' ) );

		if ( $missing->isNotEmpty() ) {
			foreach ( $missing as $missing_filter_id ) {
				$missing_filter = Filter::find( $missing_filter_id );

				// Do not delete the missing child filters.
				if ( $missing_filter instanceof Attribute && $missing_filter->has_parent() && $filters->contains( $missing_filter->parent_filter ) ) {
					continue;
				}

				if ( $missing_filter instanceof Filter ) {
					$missing_filter->delete();
				}
			}
		}

		$has_dupes = $this->has_unique_sources( $filters );

		if ( is_wp_error( $has_dupes ) ) {
			$this->send_error( $has_dupes->get_error_message() );
		}

		$group->update(
			[
				'name'    => $group_name,
				'filters' => $filters->toArray(),
			]
		);

		wp_send_json_success(
			[
				'group_id' => $group->getID(),
				'reindex'  => true
			]
		);
	}

	/**
	 * Validate settings of custom fields.
	 *
	 * @param array $filters
	 * @return array
	 */
	public function validate_custom_fields_settings( $filters ) {

		$parsed = [];

		foreach ( $filters as $key => $filter ) {

			if ( isset( $filter['cf'] ) ) {
				$restricted = isset( $filter['cf']['restricted'] ) && $filter['cf']['restricted'] === 'true';

				if ( $restricted ) {
					$transform_to          = $filter['cf']['transform'];
					$filter['filter_type'] = $transform_to;

					unset( $filter['cf']['restricted'] );
					unset( $filter['cf']['transform'] );
				}
			}

			$parsed[] = $filter;

		}

		return $parsed;
	}

	/**
	 * When creating a group detect whether or not child filters should be
	 * attached too.
	 *
	 * How it works:
	 * Detects the "all" attribute type of filter and queries the child filters
	 * once found, push each child filter right after the parent attribute place
	 * into the array so that when the parent attribute is moved around
	 * the children follow the same exact position and respect the priority.
	 *
	 * @param Collection $filters
	 * @return Collection
	 */
	private function maybe_attach_children( Collection $filters ) {
		if ( $filters->isNotEmpty() ) {
			foreach ( $filters as $index => $filter_id ) {
				$filter = Filter::find( $filter_id );

				if ( $filter instanceof Attribute && $filter->get_attributes_mode() === 'all' ) {
					$child_filters = Filter::where( 'parent_filter', $filter->getID() )->get();

					if ( $child_filters instanceof Collection && $child_filters->isNotEmpty() ) {
						foreach ( $child_filters as $child ) {
							$filters->splice( $index + 1, 0, [ strval( $child->getID() ) ] );
						}
					}
				}
			}
		}

		return $filters;
	}

	/**
	 * Get details about a given group.
	 *
	 * @return void
	 */
	public function get_group() {

		check_ajax_referer( 'wcf_load_group_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			$this->send_error( esc_html__( 'You are not authorized', 'woocommerce-product-filters' ) );
		}

		$group_id = isset( $_POST['group_id'] ) ? absint( $_POST['group_id'] ) : false;

		if ( ! $group_id ) {
			$this->send_error( esc_html__( 'No group ID has been found.', 'woocommerce-product-filters' ) );
		}

		$group = Group::find( $group_id );

		if ( ! $group instanceof Group ) {
			$this->send_error( esc_html__( 'Something went wrong, could not find the selected group.', 'woocommerce-product-filters' ) );
		}

		$filters = $group->get_filters( true );

		wp_send_json_success(
			[
				'group_id' => $group->getID(),
				'name'     => $group->name,
				'filters'  => $filters,
			]
		);
	}

	/**
	 * Create a group in the database.
	 * Used by the modal form on the add/Edit filter page.
	 *
	 * @return void
	 */
	public function create_group() {
		check_ajax_referer( 'wcf_create_group_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			$this->send_error( esc_html__( 'You are not authorized', 'woocommerce-product-filters' ) );
		}

		$group_name = isset( $_POST['name'] ) ? stripslashes( wc_clean( $_POST['name'] ) ) : false;

		if ( ! $group_name || empty( $group_name ) ) {
			$this->send_error( esc_html__( 'Please enter a name for the group', 'woocommerce-product-filters' ) );
		}

		$filters = isset( $_POST['filters'] ) && is_array( $_POST['filters'] ) && ! empty( $_POST['filters'] ) ? wc_clean( $_POST['filters'] ) : [];

		if ( ! $filters || empty( $filters ) ) {
			$this->send_error( esc_html__( 'Please add at least 1 filter to the group.', 'woocommerce-product-filters' ) );
		}

		foreach ( $filters as $filter ) {
			$this->validate_filter_settings( $filter );
		}

		$filters = $this->validate_custom_fields_settings( $filters );

		$group = Group::create(
			[
				'name'     => $group_name,
				'priority' => 0,
				'filters'  => [],
			]
		);

		if ( ! $group instanceof Group || empty( $group->getID() ) ) {
			$this->send_error( esc_html__( 'Something went wrong while creating the group', 'woocommerce-product-filters' ) );
		}

		foreach ( $filters as $index => $filter ) {
			$filter = $this->create_or_update_filter( $filter, $group->getID(), $index );
		}

		wp_send_json_success(
			[
				'group_id' => $group->getID(),
				'reindex'  => true,
			]
		);
	}

	/**
	 * Validate various settings about a given filter.
	 *
	 * @param array $filter
	 * @return void
	 */
	private function validate_filter_settings( array &$filter ) {
		// Validate the name.
		if ( ! isset( $filter['filter_name'] ) || isset( $filter['filter_name'] ) && empty( $filter['filter_name'] ) ) {
			$this->send_error( esc_html__( 'One or more filters have no name assigned. Please make sure all filters have a name.', 'woocommerce-product-filters' ) );
		}
	}

	/**
	 * Create or update a filter.
	 *
	 * @param array $filter
	 * @param string|int $group_id
	 * @return Filter
	 */
	private function create_or_update_filter( array $filter, $group_id, $priority ) {

		$filter_name = isset( $filter['filter_name'] ) ? stripslashes( sanitize_text_field( $filter['filter_name'] ) ) : false;
		$slug        = sanitize_title_with_dashes( $filter_name, '', 'save' );
		$options     = ( new Collection( wc_clean( $filter ) ) )->except( [ 'filter_name', 'filter_by' ] );
		$filter_by   = isset( $filter['filter_by'] ) ? sanitize_text_field( $filter['filter_by'] ) : false;
		$filter_id   = isset( $filter['filter_id'] ) ? absint( $filter['filter_id'] ) : false;
		$slug        = preg_replace( '/[^A-Za-z0-9 ]/', '', $slug );

		if ( Filters::is_reserved_slug( $slug ) ) {
			$slug = 'p_' . $slug;
		}

		if ( $filter_id ) {
			$filter = Filter::find( $filter_id );

			$filter->update(
				[
					'name'      => $filter_name,
					'slug'      => $slug,
					'filter_by' => $filter_by,
					'options'   => $options->toArray(),
					'priority'  => $priority
				]
			);
		} else {
			$filter = Filter::create(
				[
					'name'      => $filter_name,
					'slug'      => $slug,
					'filter_by' => $filter_by,
					'options'   => $options->toArray(),
					'priority'  => $priority
				],
				[ $group_id ]
			);
		}

		if ( $filter instanceof Filter && ! empty( $filter->getID() ) ) {
			$filter->update_groups( [ $group_id ] );
		}

		return $filter;
	}

	/**
	 * Get filter group from the database.
	 *
	 * @return void
	 */
	public function get_groups() {

		check_ajax_referer( 'wcf_get_groups_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			$this->send_error( esc_html__( 'You are not authorized', 'woocommerce-product-filters' ) );
		}

		$groups = Group::orderBy( 'priority', 'asc' )
					->orderBy( 'id', 'desc' )
					->get();

		wp_send_json_success( $groups->toArray() );
	}

	/**
	 * Delete a group from the database.
	 *
	 * @return void
	 */
	public function delete_group() {

		check_ajax_referer( 'wcf_delete_group_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			$this->send_error( esc_html__( 'You are not authorized', 'woocommerce-product-filters' ) );
		}

		$group_id = isset( $_POST['group_id'] ) ? absint( $_POST['group_id'] ) : false;

		if ( ! $group_id ) {
			$this->send_error( esc_html__( 'No record found matching the criteria.', 'woocommerce-product-filters' ) );
		}

		$group = Group::find( $group_id );

		if ( ! $group || ! $group instanceof Group ) {
			$this->send_error( esc_html__( 'No group found matching the criteria.', 'woocommerce-product-filters' ) );
		}

		$filters = $group->get_filters();

		if ( $filters instanceof Collection ) {
			foreach ( $filters as $filter ) {
				$filter->delete();
			}
		}

		$group->delete();

		wp_send_json_success();
	}

	/**
	 * Determine if the collection of filters has filters
	 * that are sharing the same data source or in the case
	 * of attributes, determine if there's any conflict.
	 *
	 * @param Collection $filters
	 * @return boolean|\WP_Error
	 */
	private function has_unique_sources( Collection $filters ) {
		$details = [];
		$filters = Filter::find( $filters->toArray() );

		if ( $filters ) {
			foreach ( $filters as $filter ) {
				$config = [
					'id'            => $filter->getID(),
					'name'          => $filter->name,
					'parent_filter' => $filter->parent_filter,
					'is_attribute'  => $filter instanceof Attribute || $filter instanceof Color,
					'filter_by'     => in_array( $filter->filter_by, [ 'taxonomy', 'attributes', 'colors' ], true ) ? $this->get_filter_data_source( $filter ) : $filter->filter_by,
				];

				$details[] = $config;
			}

			$collection = new Collection( $details );

			// Detect "filter_by" dupes.
			$grouped = $collection->groupBy( 'filter_by' );
			$dupes   = $grouped->filter(
				function ( Collection $groups ) {
					return $groups->count() > 1;
				}
			);

			// We don't need to check for custom fields.
			$dupes = $dupes->forget( 'cf' );

			if ( $dupes->isNotEmpty() ) {
				$dupes_names = [];

				foreach ( $dupes as $dupe ) {
					$dupes_names[] = $dupe->implode( 'name', ', ' );
				}

				return new \WP_Error(
					'dupes',
					sprintf(
						__( 'The following filters are sharing the same data source: %s. Each filter in a group must have a unique data source. Please adjust the filters accordingly.', 'woocommerce-product-filters' ),
						implode( ', ', $dupes_names )
					)
				);
			}

			// Detect attributes conflicts.
			$atts = $collection->where( 'is_attribute', true );

			// Contains "all attributes" filter and extra attribute filters.
			if ( $atts->count() > 1 && $atts->contains( 'filter_by', 'all_attributes' ) ) {
				$all_atts_filter = $atts->where( 'filter_by', 'all_attributes' )->first();

				$all_other_atts = $atts->reject(
					function ( $att ) {
						return $att['filter_by'] === 'all_attributes' || ! empty( $att['parent_filter'] );
					}
				);

				$other_atts_names = $all_other_atts->implode( 'name', ', ' );

				if ( empty( $other_atts_names ) ) {
					return true;
				}

				return new \WP_Error(
					'atts_conflict',
					sprintf(
						__( 'The "%1$s" filter has been found. Please remove the following filters from the group: %2$s.', 'woocommerce-product-filters' ),
						$all_atts_filter['name'],
						$other_atts_names
					)
				);
			}
		}

		return true;
	}

	/**
	 * Format the data source of an attribute filter.
	 * Used internally to check for dupes.
	 *
	 * @param Filter $filter
	 * @return string
	 */
	private function get_filter_data_source( Filter $filter ) {

		if ( $filter instanceof Attribute ) {
			$mode = $filter->get_option( 'attributes_mode' );

			if ( $mode === 'all' ) {
				return 'all_attributes';
			}

			return $filter->get_option( 'specific_attribute' );
		} elseif ( $filter instanceof Taxonomy ) {
			return $filter->get_taxonomy_slug();
		}

		return $filter->filter_by;
	}

}
