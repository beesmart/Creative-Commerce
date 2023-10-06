<?php
/**
 * @package   Barn2\woocommerce-product-filters
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */

namespace Barn2\Plugin\WC_Filters\Integrations;

/**
 * GeneratePress theme-specific styling.
 */
class Theme_GeneratePress extends Theme_Integration {

	public $template = 'generatepress';

	/**
	 * @inheritdoc
	 */
	public function register() {
		parent::register();

		// Fix the issue with the hider hiding the loop.
		if ( function_exists( 'generatepress_wc_before_shop_loop' ) ) {
			remove_action( 'woocommerce_before_shop_loop', 'generatepress_wc_before_shop_loop' );
			add_action( 'woocommerce_after_shop_loop', 'generatepress_wc_before_shop_loop' );
		}
	}

	/**
	 * @inheritdoc
	 */
	public function enqueue_fix() {

		$css = '
			.wcf-widget-toggle {
				margin-bottom:1rem;
			}
		';

		wp_add_inline_style( $this->get_dummy_handle(), $css );
	}

}
