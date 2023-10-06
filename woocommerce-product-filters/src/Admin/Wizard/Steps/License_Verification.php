<?php
/**
 * @package   Barn2\woocommerce-product-filters
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */

namespace Barn2\Plugin\WC_Filters\Admin\Wizard\Steps;

use Barn2\Plugin\WC_Filters\Dependencies\Setup_Wizard\Steps\Welcome;

/**
 * License verification step of the setup wizard.
 */
class License_Verification extends Welcome {
	/**
	 * Setup step.
	 */
	public function __construct() {
		parent::__construct();
		$this->set_name( esc_html__( 'Welcome', 'woocommerce-product-filters' ) );
		$this->set_title( esc_html__( 'Welcome to WooCommerce Product Filters', 'woocommerce-product-filters' ) );
		$this->set_description( esc_html__( 'Add product filters in no time', 'woocommerce-product-filters' ) );
		$this->set_tooltip( esc_html__( 'Use this setup wizard to quickly configure the options for your product filters. You can easily change these options later on the plugin settings page or by relaunching the setup wizard.', 'woocommerce-product-filters' ) );
	}
}
