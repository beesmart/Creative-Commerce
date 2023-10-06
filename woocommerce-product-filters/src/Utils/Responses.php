<?php
/**
 * @package   Barn2\woocommerce-product-filters
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */

namespace Barn2\Plugin\WC_Filters\Utils;

/**
 * Helper class with common methods used during
 * json responses for ajax requests.
 */
class Responses {

	/**
	 * Generate the not found message template.
	 *
	 * @return string
	 */
	public static function generate_no_products_template() {

		ob_start();

		wc_get_template( 'loop/no-products-found.php' );

		$js_field_html = ob_get_clean();

		return wp_kses_post( str_replace( "\n", '', $js_field_html ) );
	}

}
