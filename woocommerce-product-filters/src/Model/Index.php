<?php
/**
 * @package   Barn2\woocommerce-product-filters
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */

namespace Barn2\Plugin\WC_Filters\Model;

use Barn2\Plugin\WC_Filters\Dependencies\Illuminate\Database\Eloquent\Builder;
use Barn2\Plugin\WC_Filters\Dependencies\Illuminate\Database\Eloquent\Model;
use Barn2\Plugin\WC_Filters\Dependencies\Illuminate\Support\Collection;
use Barn2\Plugin\WC_Filters\Dependencies\Sematico\FluentQuery\Concerns\HasUniqueIdentifier;
use Barn2\Plugin\WC_Filters\Plugin;

/**
 * Representation of a single indexed post from the database.
 */
class Index extends Model {

	use HasUniqueIdentifier;

	/**
	 * @var string
	 */
	protected $table = Plugin::META_PREFIX . 'index';

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
		'post_id',
		'filter_id',
		'facet_name',
		'facet_value',
		'facet_display_value',
		'term_id',
		'parent_id',
		'depth',
		'variation_id',
	];

	/**
	 * Scope the index query to target data with a specific post id.
	 *
	 * @param Builder $query
	 * @param string $post_id
	 * @return Builder
	 */
	public function scopeByID( Builder $query, $post_id ) {
		return $query->where( 'post_id', $post_id );
	}

	/**
	 * Scope the index query to target data with from a specific filter id.
	 *
	 * @param Builder $query
	 * @param string $filter_id
	 * @return Builder
	 */
	public function scopeByFilterID( Builder $query, string $filter_id ) {
		return $query->where( 'filter_id', $filter_id );
	}

}
