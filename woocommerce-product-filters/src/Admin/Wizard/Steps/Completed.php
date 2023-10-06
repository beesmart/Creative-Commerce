<?php
/**
 * @package   Barn2\woocommerce-product-filters
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */

namespace Barn2\Plugin\WC_Filters\Admin\Wizard\Steps;

use Barn2\Plugin\WC_Filters\Dependencies\Setup_Wizard\Steps\Ready;

/**
 * The last step of the setup wizard.
 */
class Completed extends Ready {
	/**
	 * Setup the step.
	 */
	public function __construct() {
		parent::__construct();
		$this->set_name( esc_html__( 'Ready', 'woocommerce-product-filters' ) );
		$this->set_title( esc_html__( 'Ready!', 'woocommerce-product-filters' ) );
		$this->set_description(
			'Congratulations, you have finished setting up the plugin. We have created a ‘Recommended Filters’ group for you and added some commonly used filters, so you can start displaying them on your site straight away.

			<br/><br/>Alternatively, you can edit these filters, create your own filter groups, and more.'
		);
	}
}
