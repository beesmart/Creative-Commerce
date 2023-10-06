<?php
/**
 * @package   Barn2\woocommerce-product-filters
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */

namespace Barn2\Plugin\WC_Filters\Admin\Settings;

use Barn2\Plugin\WC_Filters\Utils\Settings;
use Barn2\Plugin\WC_Filters\Dependencies\Lib\Plugin\License\Admin\License_Setting;
use Barn2\Plugin\WC_Filters\Dependencies\Lib\Registerable;

/**
 * Settings tab for the plugin.
 */
class General implements Registerable {

	/**
	 * Page ID
	 *
	 * @var string
	 */
	public $id;

	/**
	 * License settings handler.
	 *
	 * @var License_Setting
	 */
	private $license_setting;

	/**
	 * Get things started.
	 *
	 * @param string $id section id
	 * @param License_Setting $license_setting
	 */
	public function __construct( $id, License_Setting $license_setting ) {
		$this->id              = $id;
		$this->license_setting = $license_setting;
	}

	/**
	 * Hook into WP.
	 *
	 * @return void
	 */
	public function register() {
	}

	/**
	 * Settings list.
	 *
	 * @return array
	 */
	public function get_settings() {
		return Settings::get_general_settings( $this->id, $this->license_setting, true );
	}

}
