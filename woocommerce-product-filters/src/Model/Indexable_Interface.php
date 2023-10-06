<?php
/**
 * @package   Barn2\woocommerce-product-filters
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */

namespace Barn2\Plugin\WC_Filters\Model;

/**
 * Indexable_Interface provides a single method "generate_index_data".
 * The method is responsible for retrieving the data for the Indexer.
 */
interface Indexable_Interface {

	/**
	 * Generate data for the indexer.
	 *
	 * @param array $defaults default index values
	 * @param string $post_id the ID of the post to index
	 * @return array
	 */
	public function generate_index_data( array $defaults, string $post_id );

}
