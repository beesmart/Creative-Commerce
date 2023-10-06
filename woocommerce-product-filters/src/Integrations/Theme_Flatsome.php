<?php
/**
 * @package   Barn2\woocommerce-product-filters
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */

namespace Barn2\Plugin\WC_Filters\Integrations;

class Theme_Flatsome extends Theme_Integration {

	public $template = 'flatsome';

	/**
	 * @inheritdoc
	 */
	public function enqueue_fix() {

		$css = '
			.woocommerce-pagination .page-numbers li .page-numbers {
				padding:0.5rem 1rem;
			}
			.wcf-horizontal-sort button {
				margin-bottom: 0;
			}
			#main .col, div#wrapper, main#main {
				position:static;
			}
		';

		wp_add_inline_style( $this->get_dummy_handle(), $css );
	}
}
