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
class Theme_Xstore extends Theme_Integration {

	public $template = 'xstore';

	/**
	 * @inheritdoc
	 */
	public function enqueue_fix() {

		$css = '
			.wcf-rating-wrapper li {
				display:inline-block !important;
				width:auto !important;
			}
			.woocommerce-pagination .page-numbers li span,
			.woocommerce-pagination .page-numbers li a {
				border: 0 !important;
				padding:7px !important;
			}
		';

		wp_add_inline_style( $this->get_dummy_handle(), $css );

	}

}
