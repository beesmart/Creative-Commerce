<?php
/**
 * @package   Barn2\woocommerce-product-filters
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */

namespace Barn2\Plugin\WC_Filters\Integrations;

use Barn2\Plugin\WC_Filters\Dependencies\Lib\Registerable;

use function Barn2\Plugin\WC_Filters\wcf;

/**
 * Provides integration with the Private store plugin.
 */
class Plugin_Private_Store implements Registerable {
	/**
	 * Register the integration.
	 *
	 * @return void
	 */
	public function register() {
		if ( ! function_exists( '\Barn2\Plugin\WC_Private_Store\wps' ) ) {
			return;
		}

		$this->init();
	}

	/**
	 * When the store is locked:
	 *
	 * - dequeue our assets
	 * - remove our custom dom elements
	 * - remove the loop wrapper
	 * - disable our custom pagination template
	 * - override the shortcode output with an empty output.
	 *
	 * @return void
	 */
	public function init() {
		if ( ! \Barn2\Plugin\WC_Private_Store\Util::is_store_locked() ) {
			return;
		}

		$displayer = wcf()->get_service( 'display' );

		remove_action( 'wp_enqueue_scripts', [ $displayer, 'assets' ] );
		remove_action( 'woocommerce_before_shop_loop', [ $displayer, 'add_mobile_drawer' ], 9 );
		remove_action( 'woocommerce_before_shop_loop', [ $displayer, 'add_shop_filters' ], 9 );
		remove_action( 'loop_start', [ $displayer, 'add_template_tag' ] );
		remove_action( 'loop_no_results', [ $displayer, 'add_template_tag' ] );
		remove_action( 'loop_end', [ $displayer, 'add_closing_template_tag' ] );
		remove_filter( 'wc_get_template', [ $displayer, 'filter_templates' ] );

		// Overrides the output of the original shortcode.
		add_shortcode(
			'wpf-filters',
			function() {
				return '';
			}
		);
	}
}
