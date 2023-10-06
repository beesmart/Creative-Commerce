<?php
/**
 * @package   Barn2\woocommerce-product-filters
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */

namespace Barn2\Plugin\WC_Filters\Admin\Wizard\Steps;

use Barn2\Plugin\WC_Filters\Dependencies\Setup_Wizard\Steps\Cross_Selling;
use Barn2\Plugin\WC_Filters\Dependencies\Setup_Wizard\Util;

/**
 * Upsell step of the setup wizard.
 */
class Upsell extends Cross_Selling {
	public function __construct() {
		parent::__construct();
		$this->set_name( esc_html__( 'More', 'woocommerce-product-filters' ) );
		$this->set_description( __( 'Enhance your store with these fantastic plugins from Barn2.', 'woocommerce-product-filters' ) );
		$this->set_title( esc_html__( 'Extra features', 'woocommerce-product-filters' ) );
	}
}
