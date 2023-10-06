<?php
/**
 * @package   Barn2\woocommerce-product-filters
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */

namespace Barn2\Plugin\WC_Filters\Model;

use Barn2\Plugin\WC_Filters\Dependencies\Illuminate\Database\Eloquent\Model;
use Barn2\Plugin\WC_Filters\Dependencies\Sematico\FluentQuery\Concerns\HasUniqueIdentifier;
use Barn2\Plugin\WC_Filters\Plugin;
use Barn2\Plugin\WC_Filters\Utils\Settings;
use Barn2\Plugin\WC_Filters\Dependencies\Illuminate\Support\Collection;
use Barn2\Plugin\WC_Filters\Dependencies\Illuminate\Support\Str;
use Barn2\Plugin\WC_Filters\Dependencies\Parental\HasChildren;
use Barn2\Plugin\WC_Filters\Diff;
use Barn2\Plugin\WC_Filters\Model\Filters\Attribute;
use Barn2\Plugin\WC_Filters\Model\Filters\Color;
use Barn2\Plugin\WC_Filters\Model\Filters\Custom_Field;
use Barn2\Plugin\WC_Filters\Model\Filters\Price;
use Barn2\Plugin\WC_Filters\Model\Filters\Rating;
use Barn2\Plugin\WC_Filters\Model\Filters\Sale;
use Barn2\Plugin\WC_Filters\Model\Filters\Search;
use Barn2\Plugin\WC_Filters\Model\Filters\Sorter;
use Barn2\Plugin\WC_Filters\Model\Filters\Stock;
use Barn2\Plugin\WC_Filters\Model\Filters\Taxonomy;
use Barn2\Plugin\WC_Filters\Utils\Filters;

use function Barn2\Plugin\WC_Filters\wcf;

/**
 * Representation of an individual filter.
 */
class Filter extends Model {

	use HasUniqueIdentifier;
	use HasChildren;

	/**
	 * @var string
	 */
	protected $table = Plugin::META_PREFIX . 'filters';

	/**
	 * @var string
	 */
	protected $primaryKey = 'id';

	/**
	 * @var bool
	 */
	public $timestamps = \false;

	/**
	 * Automatically cast attributes in specific ways.\
	 *
	 * @var array
	 */
	protected $casts = [
		'options' => 'array'
	];

	/**
	 * @var array
	 */
	public $fillable = [
		'name',
		'slug',
		'filter_by',
		'priority',
		'options',
		'parent_filter'
	];

	/**
	 * @var array
	 */
	protected $appends = [
		'source_name',
		'input_name',
		'search_query',
		'hidden'
	];

	/**
	 * Indicates the column that should be used
	 * by the HasChildren trait to return the appropriate model.
	 *
	 * @var string
	 */
	protected $childColumn = 'filter_by';

	/**
	 * Maps a filter_by value to the appropriate model.
	 *
	 * @var array
	 */
	protected $childTypes = [
		'categories' => Taxonomy::class,
		'tags'       => Taxonomy::class,
		'attributes' => Attribute::class,
		'colors'     => Color::class,
		'price'      => Price::class,
		'ratings'    => Rating::class,
		'in_stock'   => Stock::class,
		'on_sale'    => Sale::class,
		'taxonomy'   => Taxonomy::class,
		'sorter'     => Sorter::class,
		'cf'         => Custom_Field::class,
		'search'     => Search::class,
	];

	/**
	 * Create the filter.
	 *
	 * If this is an "all attributes" type of filter,
	 * create the child filters too.
	 *
	 * The $groups parameter is only used for attribute fields when generating child filters.
	 *
	 * @param array $attributes
	 * @param array $groups list of groups to which the filter should be added to.
	 * @return self
	 */
	public static function create( array $attributes = [], array $groups = [] ) {
		$model = static::query()->create( $attributes );

		$is_attribute_filter = isset( $attributes['filter_by'] ) && $attributes['filter_by'] === 'attributes';

		if ( $is_attribute_filter ) {
			$attributes_mode = isset( $attributes['options']['attributes_mode'] ) ? $attributes['options']['attributes_mode'] : false;

			if ( $attributes_mode === 'all' ) {
				Filters::generate_child_filters( $model, $groups );
			}
		}

		return $model;
	}

	/**
	 * Get the filter's options.
	 *
	 * @return array
	 */
	public function get_options() {
		return $this->options;
	}

	/**
	 * Get the value of a specific option if it exists.
	 *
	 * If not, it returns false.
	 *
	 * @param string $key
	 * @return mixed false when not found
	 */
	public function get_option( string $key ) {
		return isset( $this->options[ $key ] ) ? $this->options[ $key ] : false;
	}

	/**
	 * Retrieve the human readable name of the selected source for the filter.
	 *
	 * @return string
	 */
	public function getSourceNameAttribute() {
		return Settings::get_filter_sources_list()[ $this->filter_by ];
	}

	/**
	 * Retrieve the human readable name of the selected input type for the filter.
	 *
	 * @return string
	 */
	public function getInputNameAttribute() {
		return Settings::get_supported_filter_types_list()[ $this->get_option( 'filter_type' ) ]['label'];
	}

	/**
	 * Determine if this filter is hidden in the admin panel.
	 *
	 * Hidden filters are generated when an "all attributes"
	 * type of filter is created.
	 *
	 * @return bool
	 */
	public function getHiddenAttribute() {
		return ! empty( $this->parent_filter );
	}

	/**
	 * Get a list of the groups to which the filter belongs to.
	 *
	 * This method is required because when using Eloquent in WordPress,
	 * we cannot reliably build a json relationship.
	 *
	 * @return Collection
	 */
	public function get_groups() {
		$db = wcf()->get_service( 'db' );

		$collection = $db::table( 'wcf_groups' )
			->whereJsonContains( 'filters', [ strval( $this->getID() ) ] )
			->get();

		if ( ! $collection->isEmpty() ) {
			$collection->transform(
				function( $item, $key ) {
					return Group::findOrFail( $item->id );
				}
			);
		}

		return $collection;
	}

	/**
	 * Lazy load the list of groups to which the filter has been added to.
	 *
	 * @return array
	 */
	public function getGroupsAttribute() {

		$db = wcf()->get_service( 'db' );

		$collection = $db::table( 'wcf_groups' )
			->whereJsonContains( 'filters', [ strval( $this->getID() ) ] )
			->get();

		if ( ! $collection->isEmpty() ) {
			$collection->transform(
				function( $item, $key ) {
					return $item->id;
				}
			);

			return $collection->toArray();
		}

		return [];
	}

	/**
	 * Lazy load the list of groups to which the filter has been added to.
	 * Return an array of ID=>name.
	 *
	 * @return array
	 */
	public function getGroupsNamesAttribute() {

		$db = wcf()->get_service( 'db' );

		$collection = $db::table( 'wcf_groups' )
			->whereJsonContains( 'filters', [ strval( $this->getID() ) ] )
			->get();

		if ( ! $collection->isEmpty() ) {
			$collection->transform(
				function( $item, $key ) {
					return [
						'id'   => $item->id,
						'name' => $item->name,
					];
				}
			);

			return $collection->toArray();
		}

		return [];
	}

	/**
	 * Add the filter to the new groups and remove the filter
	 * from all other groups.
	 *
	 * This method is best used when pre-serving the position of the
	 * filter into a group is needed.
	 *
	 * @param array $selected_groups list of IDs of groups to which the filter should be added to.
	 * @return Filter
	 */
	public function update_groups( array $selected_groups ) {

		// Groups selected through the "add/edit" filter screen.
		$selected_groups = ( new Collection( $selected_groups ) )->transform(
			function ( $item, $key ) {
				return Group::find( $item );
			}
		);

		// Current groups to which the filter belongs to.
		$current_groups = $this->get_groups();

		// List of groups to which the filter should be added to.
		$new_groups = $selected_groups->diff( $current_groups );

		// List of groups from which the filter should be removed from.
		$remove_from = $current_groups->diff( $selected_groups );

		if ( $remove_from->isNotEmpty() ) {
			/** @var Group $group_to_remove */
			foreach ( $remove_from as $group_to_remove ) {
				$group_to_remove->delete_filter( $this->getID() );
			}
		}

		if ( $new_groups->isNotEmpty() ) {
			/** @var Group $new_group */
			foreach ( $new_groups as $new_group ) {
				$new_group->add_filter( $this );
			}
		}

		return $this;
	}

	/**
	 * Delete the filter from the database.
	 *
	 * This has a cascading effect:
	 * - deletes all data indexed for this filter
	 * - removes the filter from all groups to which it was added.
	 *
	 * @return bool
	 */
	public function delete() {

		parent::delete();

		$groups = $this->get_groups();

		if ( ! $groups->isEmpty() ) {
			/** @var Group $group */
			foreach ( $groups as $group ) {
				$group->delete_filter( $this->getID() );
			}
		}

		$indexed_data = Index::byFilterID( $this->getID() )->delete();

		Diff::update_current_state();

		return true;
	}

	/**
	 * Attach search parameters criteria to the filter.
	 *
	 * These are the instructions that will be used via the
	 * Filterable_Interface method.
	 *
	 * @param mixed $value
	 * @return Filter
	 */
	public function setSearchQueryAttribute( $value ) {
		$this->attributes['search_query'] = $value;

		return $this;
	}

	/**
	 * Get the search query parameters assigned to the filter.
	 *
	 * @return mixed
	 */
	public function getSearchQueryAttribute() {
		return isset( $this->attributes['search_query'] ) ? $this->attributes['search_query'] : false;
	}

	public function get_attribute_orderby_args( $taxonomy ) {
		$orderby = wc_attribute_orderby( wc_attribute_taxonomy_name( $taxonomy ) );
		$args    = [];

		if ( $orderby === 'id' ) {
			$args = [
				'orderby' => 'id',
				'order'   => 'desc',
			];
		}

		return $args;
	}

	/**
	 * Automatically set the slug attribute by counting for existing ones.
	 *
	 * @param string $value
	 * @return void
	 */
	public function setSlugAttribute( $value ) {
		if ( static::whereSlug( $slug = Str::slug( $value ) )->exists() ) {
			if ( static::whereSlug( $slug )->get( 'id' )->first()->id !== $this->id ) {
				$slug = $this->incrementSlug( $slug );

				if ( static::whereSlug( $slug )->exists() ) {
					return $this->setSlugAttribute( $slug );
				}
			}
		}

		$this->attributes['slug'] = $slug;
	}

	/**
	 * Increment slug
	 *
	 * @param  string $slug
	 * @return string
	 **/
	public function incrementSlug( $slug ) {
		// Get the slug of the created post earlier
		$max = static::whereSlug( $slug )->latest( 'id' )->value( 'slug' );

		if ( is_numeric( $max[-1] ) ) {
			return preg_replace_callback(
				'/(\d+)$/',
				function ( $matches ) {
					return $matches[1] + 1;
				},
				$max
			);
		}

		return "{$slug}-2";
	}

}
