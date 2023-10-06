<?php
/**
 * @package   Barn2\woocommerce-product-filters
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */

namespace Barn2\Plugin\WC_Filters\Integrations;

/**
 * Extends the WC Shortcodes generator with new methods
 * required for our custom features.
 */
class WC_Shortcodes_Wrapper extends \WC_Shortcode_Products {
	/**
	 * Get the query results.
	 *
	 * @return object
	 */
	public function get_products() {
		return $this->get_query_results();
	}

	/**
	 * Get all queried products and keep the
	 *
	 * @return object
	 */
	public function get_all_queried_products() {
		$this->query_args['posts_per_page'] = -1;
		return $this->get_query_results();
	}

}
