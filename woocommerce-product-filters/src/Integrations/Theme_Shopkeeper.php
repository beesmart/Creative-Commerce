<?php
/**
 * @package   Barn2\woocommerce-product-filters
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */

namespace Barn2\Plugin\WC_Filters\Integrations;

/**
 * Shopkeeper theme-specific styling.
 */
class Theme_Shopkeeper extends Theme_Integration {

	public $template = 'shopkeeper';

	/**
	 * @inheritdoc
	 */
	public function enqueue_fix() {

		$css = '
			.wcf-form-elements-wrapper input[type="checkbox"],
			.wcf-form-elements-wrapper input[type="radio"],
			.wcf-form-elements-wrapper input[type="radio"]:after,
			.wcf-form-elements-wrapper input[type="radio"]:before {
				display:none !important;
			}
			.wcf-dropdown-menu ul li {padding:1rem !important;}
		';

		wp_add_inline_style( $this->get_dummy_handle(), $css );

	}

}
