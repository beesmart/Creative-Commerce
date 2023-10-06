<?php
/**
 * @package   Barn2\woocommerce-product-filters
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */

namespace Barn2\Plugin\WC_Filters;

use Barn2\Plugin\WC_Filters\Dependencies\Illuminate\Support\Collection;
use Barn2\Plugin\WC_Filters\Traits\Counters_Aware;
use Barn2\Plugin\WC_Filters\Traits\Params_Provider;

/**
 * Query cache handler helper class.
 *
 * This class handles caching of a WP Query together with a filters collection
 * inside global variables so that they can then later be referenced when
 * making ajax requests through the forms.
 *
 * Currently used for integration with the WPT and WRO plugins.
 */
class Query_Cache {

	use Counters_Aware;
	use Params_Provider;

	/**
	 * Query instance.
	 *
	 * @var \WP_Query
	 */
	protected $query;

	/**
	 * Orderby parameter
	 *
	 * @var string
	 */
	protected $orderby;

	/**
	 * Filters collection.
	 *
	 * @var Collection
	 */
	protected $filters;

	/**
	 * List of found posts.
	 *
	 * @var array
	 */
	protected $post_ids = [];

	/**
	 * Cache prefix used for setting global variables.
	 *
	 * @var string
	 */
	protected $cache_prefix;

	/**
	 * Whether or not the request is to be reset.
	 *
	 * @var boolean
	 */
	protected $reset = false;

	/**
	 * Initialize the caching class.
	 *
	 * @param \WP_Query $query
	 * @param string $orderby
	 * @param Collection $filters
	 * @param array $post_ids
	 */
	public function __construct( \WP_Query $query, string $orderby, Collection $filters, array $post_ids ) {
		$this->query    = $query;
		$this->orderby  = $orderby;
		$this->filters  = $filters;
		$this->post_ids = $post_ids;
	}

	/**
	 * Specific the prefix used for the caching.
	 *
	 * @param string $prefix
	 * @return self
	 */
	public function set_cache_prefix( string $prefix ) {
		$this->cache_prefix = $prefix;
		return $this;
	}

	/**
	 * Get the caching prefix string.
	 *
	 * @return string
	 */
	public function get_cache_prefix() {
		return $this->cache_prefix;
	}

	/**
	 * Get the query instance.
	 *
	 * @return \WP_Query
	 */
	public function get_query() {
		return $this->query;
	}

	/**
	 * Get the orderby parameter.
	 *
	 * @return string
	 */
	public function get_orderby() {
		return $this->orderby;
	}

	/**
	 * Get the filters collection.
	 *
	 * @return Collection
	 */
	public function get_filters() {
		return $this->filters;
	}

	/**
	 * Get the list of found posts.
	 *
	 * @return array
	 */
	public function get_post_ids() {
		return $this->post_ids;
	}

	/**
	 * Manually set post ids by sending an array of posts objects.
	 *
	 * @param array $posts
	 * @return self
	 */
	public function set_post_ids( array $posts ) {
		$ids = [];

		if ( ! empty( $posts ) ) {
			foreach ( $posts as $post ) {
				$ids[] = isset( $post->ID ) ? $post->ID : $post; // can either be object or straight ID (Elementor)
			}
		}

		$this->post_ids = $ids;

		return $this;
	}

	/**
	 * Whether or not, no posts have been found.
	 *
	 * @return boolean
	 */
	public function is_404() {
		return empty( $this->get_post_ids() );
	}

	/**
	 * Add all items to global variables.
	 *
	 * @return self
	 */
	public function cache() {
		$prefix                         = $this->get_cache_prefix();
		$GLOBALS[ "{$prefix}_query" ]   = $this->get_query();
		$GLOBALS[ "{$prefix}_filters" ] = $this->get_filters();

		return $this;
	}

	/**
	 * Purge the global variables.
	 *
	 * @return self
	 */
	public function purge() {
		$prefix = $this->get_cache_prefix();

		unset( $GLOBALS[ "{$prefix}_query" ] );
		unset( $GLOBALS[ "{$prefix}_filters" ] );

		return $this;
	}

}
