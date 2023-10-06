<?php
/**
 * @package   Barn2\woocommerce-product-filters
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */

namespace Barn2\Plugin\WC_Filters\Integrations;

use Barn2\Plugin\WC_Filters\Dependencies\Lib\Registerable;

/**
 * Provides integration with the Jupiterx theme.
 *
 * Reasons:
 * - Due to extreme specifity, the theme breaks search inputs of dropdowns.
 * - Due to certain stylings of elements of the theme, dropdowns are pushed to the bottom of the page.
 */
class Theme_Jupiter extends Theme_Integration {

	public $template = 'jupiterx';

	/**
	 * @inheritdoc
	 */
	public function register() {
		parent::register();
		add_action( 'after_setup_theme', [ $this, 'init' ] );
	}

	/**
	 * Hook after theme has been set up.
	 *
	 * @return void
	 */
	public function init() {
		if ( ! defined( 'JUPITERX_VERSION' ) ) {
			return;
		}
		add_filter( 'wcf_dropdowns_searchable_mobile', '__return_false' );
	}

	/**
	 * @inheritdoc
	 */
	public function enqueue_fix() {
		 $css = '
			@media (max-width: 782px) {
				#jupiterx-primary,
				.jupiterx-sidebar {
					position:static;
				}
				.jupiterx-main {
					z-index:inherit;
				}
		  	}
			.wcf-horizontal-dropdown .wcf-c1 {
				color:inherit;
			}
		';

		wp_add_inline_style( $this->get_dummy_handle(), $css );

	}

}
