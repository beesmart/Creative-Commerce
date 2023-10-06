<?php
/**
 * @package   Barn2\woocommerce-product-filters
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */

namespace Barn2\Plugin\WC_Filters\Integrations;

/**
 * Indicates that a class is making use of the fallback mode.
 */
interface Fallback_Aware_Interface {

	/**
	 * Get the string that is then attached as an argument
	 * to the WP_Query $args array as a flag.
	 *
	 * The flag is used to shortcircuit our filtered results
	 * injection.
	 *
	 * Without the flag we'd end up causing an infinite loop.
	 *
	 * @return string
	 */
	public function get_query_argument_id();

	/**
	 * Determine where to apply fallback mode, when enabled.
	 * This is used via the wcf_fallback_mode filter.
	 *
	 * @param boolean $enabled
	 * @return boolean
	 */
	public function apply_fallback_mode( bool $enabled );

	/**
	 * Insert the filtered results into the query.
	 *
	 * This is used via pre_get_posts.
	 *
	 * @param \WP_Query $query
	 * @return void
	 */
	public function filter_query( $query );

	/**
	 * Get the value of the url query string that holds
	 * the filters selected via the form.
	 *
	 * @return string
	 */
	public function get_requested_filters();

	/**
	 * Get the value of the url query string that holds
	 * the orderby parameter.
	 *
	 * @return string
	 */
	public function get_requested_orderby();

	/**
	 * Load json values needed for the fallback js.
	 *
	 * @return void
	 */
	public function enqueue_json();

	/**
	 * Define the fallback json data.
	 *
	 * @return array
	 */
	public function jsonSerialize();

}
