<?php
/**
 * @package   Barn2\woocommerce-product-filters
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */

namespace Barn2\Plugin\WC_Filters\Model;

/**
 * Indicates that a filter should preload counts of possible choices
 * on page load.
 */
interface Preloadable_Interface {

	/**
	 * Generate counts of all indexed choices for a filter.
	 *
	 * @param array $post_ids option list of post ids needed for pre load. Usually required on taxonomy pages.
	 * @return array
	 */
	public function get_all_choices_counts( array $post_ids = [] );

}
