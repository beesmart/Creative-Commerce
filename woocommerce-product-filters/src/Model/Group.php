<?php
/**
 * @package   Barn2\woocommerce-product-filters
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */

namespace Barn2\Plugin\WC_Filters\Model;

use Barn2\Plugin\WC_Filters\Dependencies\Illuminate\Database\Eloquent\Model;
use Barn2\Plugin\WC_Filters\Dependencies\Illuminate\Support\Collection;
use Barn2\Plugin\WC_Filters\Dependencies\Sematico\FluentQuery\Concerns\HasUniqueIdentifier;
use Barn2\Plugin\WC_Filters\Model\Filters\Attribute;
use Barn2\Plugin\WC_Filters\Plugin;

/**
 * Representation of an individual group and it's filters.
 */
class Group extends Model {

	use HasUniqueIdentifier;

	/**
	 * @var string
	 */
	protected $table = Plugin::META_PREFIX . 'groups';

	/**
	 * @var string
	 */
	protected $primaryKey = 'id';

	/**
	 * @var bool
	 */
	public $timestamps = \false;

	/**
	 * @var array
	 */
	public $fillable = [
		'name',
		'priority',
		'filters'
	];

	/**
	 * Automatically cast attributes in specific ways.\
	 *
	 * @var array
	 */
	protected $casts = [
		'filters' => 'array'
	];

	/**
	 * Remove a specific filter from this group.
	 *
	 * If the filter has child filters, remove the children too.
	 *
	 * @param string $filter_id id of the filter to remove.
	 * @return Group
	 */
	public function delete_filter( $filter_id ) {

		$filters = ( new Collection( $this->filters ) )->reject(
			function( $value, $key ) use ( $filter_id ) {
				return strval( $value ) === strval( $filter_id );
			}
		);

		$child_filters = Filter::where( 'parent_filter', $filter_id )->get();

		if ( $child_filters instanceof Collection && $child_filters->isNotEmpty() ) {
			foreach ( $child_filters as $child ) {
				$filters = $filters->reject(
					function( $value, $key ) use ( $child ) {
						return strval( $value ) === strval( $child->getID() );
					}
				);
			}
		}

		$this->update(
			[
				'filters' => $filters->unique()->values()->all()
			]
		);

		return $this;

	}

	/**
	 * Attach a filter to the group.
	 *
	 * If the filter has child filters, add those too.
	 *
	 * @param Filter $filter
	 * @return Group
	 */
	public function add_filter( Filter $filter ) {

		$filters = ( new Collection( $this->filters ) )->push( strval( $filter->getID() ) );

		$child_filters = Filter::where( 'parent_filter', $filter->getID() )->get();

		if ( $child_filters instanceof Collection && $child_filters->isNotEmpty() ) {
			foreach ( $child_filters as $child ) {
				$filters->push( strval( $child->getID() ) );
			}
		}

		$this->update(
			[
				'filters' => $filters->unique()->values()->all()
			]
		);

		return $this;

	}

	/**
	 * Get the collection of filters associated to this group.
	 * Exclude the parent "all attributes" filter.
	 *
	 * @param bool $exclude_atts
	 * @param bool $exclude_atts_parent
	 * @return Collection|null
	 */
	public function get_filters( bool $exclude_atts = false, bool $exclude_atts_parent = false ) {
		$ids = array_map( 'absint', $this->filters );

		if ( empty( $ids ) ) {
			return null;
		}

		$filters = Filter::whereIn( 'id', $ids )->orderByRaw( 'FIELD (id, ' . implode( ', ', $ids ) . ') ASC' )->get();

		if ( $filters instanceof Collection && $filters->isNotEmpty() ) {
			foreach ( $filters as $index => $filter ) {
				if ( $exclude_atts && $filter instanceof Attribute && $filter->has_parent() ) {
					$filters->pull( $index );
				}
				if ( $exclude_atts_parent && $filter instanceof Attribute && $filter->get_attributes_mode() === 'all' ) {
					$filters->pull( $index );
				}
			}
		}

		return $filters->values();
	}

}
