<?php
/**
 * @package   Barn2\woocommerce-product-filters
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */

namespace Barn2\Plugin\WC_Filters\Integrations;

/**
 * Salient theme-specific styling.
 */
class Theme_Salient extends Theme_Integration {

	public $template = 'salient';

	/**
	 * @inheritdoc
	 */
	public function enqueue_fix() {

		$css = '
			.wcf-dropdown-menu ul li {padding:1rem !important;}
			.woocommerce-result-count { position:static !important; float:none !important; }
		';

		wp_add_inline_style( $this->get_dummy_handle(), $css );

	}

}
