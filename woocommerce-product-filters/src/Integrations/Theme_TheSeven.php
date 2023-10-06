<?php
/**
 * @package   Barn2\woocommerce-product-filters
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */

namespace Barn2\Plugin\WC_Filters\Integrations;

/**
 * Divi theme-specific styling.
 */
class Theme_TheSeven extends Theme_Integration {

	public $template = 'dt-the7';

	/**
	 * @inheritdoc
	 */
	public function enqueue_fix() {

		$css = '
			.wcf-pagination {
				margin:2rem 0;
			}
		';

		wp_add_inline_style( $this->get_dummy_handle(), $css );

	}

}
