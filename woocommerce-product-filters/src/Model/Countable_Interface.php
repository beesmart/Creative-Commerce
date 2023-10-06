<?php
/**
 * @package   Barn2\woocommerce-product-filters
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */

namespace Barn2\Plugin\WC_Filters\Model;

/**
 * Indicates that the filter can provide a list of counts
 * of the available choices.
 */
interface Countable_Interface {

	/**
	 * Return the number of choices of each indexed
	 * value based on the filtered post ids.
	 *
	 * @param array $post_ids
	 * @param mixed $filters
	 * @param boolean $prefilling
	 * @return array
	 */
	public function get_choices_counts( array $post_ids, $filters = false, $prefilling = false );

}
