<?php
/**
 * @package   Barn2\woocommerce-product-filters
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */

namespace Barn2\Plugin\WC_Filters\Admin;

use Barn2\Plugin\WC_Filters\Admin\Terms\Term_Image;
use Barn2\Plugin\WC_Filters\Admin\Terms\Color_Picker;
use Barn2\Plugin\WC_Filters\Dependencies\Lib\Plugin\Admin\Admin_Links;
use Barn2\Plugin\WC_Filters\Dependencies\Lib\Plugin\License\Admin\License_Setting;
use Barn2\Plugin\WC_Filters\Dependencies\Lib\Plugin\License\License;
use Barn2\Plugin\WC_Filters\Dependencies\Lib\Plugin\Licensed_Plugin;
use Barn2\Plugin\WC_Filters\Dependencies\Lib\Registerable;
use Barn2\Plugin\WC_Filters\Dependencies\Lib\Service;
use Barn2\Plugin\WC_Filters\Dependencies\Lib\Service_Container;
use Barn2\Plugin\WC_Filters\Dependencies\Lib\WooCommerce\Admin\Custom_Settings_Fields;
use Barn2\Plugin\WC_Filters\Dependencies\Lib\WooCommerce\Admin\Navigation;
use Barn2\Plugin\WC_Filters\Indexer;
use Barn2\Plugin\WC_Filters\Dependencies\Lib\Admin\Settings_Scripts;

use function Barn2\Plugin\WC_Filters\wcf;

/**
 * Handles everything related to the admin side.
 */
class Admin_Controller implements Registerable, Service {

	use Service_Container;

	/**
	 * The plugin.
	 *
	 * @var Licensed_Plugin
	 */
	private $plugin;

	/**
	 * License details
	 *
	 * @var License_Setting
	 */
	private $license_setting;

	/**
	 * License handler.
	 *
	 * @var License
	 */
	private $license;

	/**
	 * Get things started.
	 *
	 * @param Licensed_Plugin $plugin
	 * @param License_Setting $license_setting
	 * @param License $license
	 */
	public function __construct( Licensed_Plugin $plugin, License_Setting $license_setting, License $license ) {
		$this->plugin          = $plugin;
		$this->license         = $license;
		$this->license_setting = $license_setting;
	}

	/**
	 * Hook into WP
	 *
	 * @return void
	 */
	public function register() {
		$this->register_services();

		// Add settings page.
		add_action( 'admin_notices', [ $this, 'notices' ] );
	}

	/**
	 * Get admin components list.
	 *
	 * @return array
	 */
	public function get_services() {
		$services = [
			'admin_links'        => new Admin_Links( $this->plugin ),
			'wc_navigation'      => new Navigation( $this->plugin, 'filters', __( 'Filters', 'woocommerce-product-filters' ) ),
			'terms_color_picker' => new Color_Picker( $this->plugin ),
			'terms_term_image'   => new Term_Image( $this->plugin ),
			'ajax'               => new Admin_Ajax(),
			'scripts'            => new Settings_Scripts( $this->plugin ),
			'settings'           => new Settings_Page( $this->plugin ),
		];

		return $services;
	}

	/**
	 * Display a global message once the indexing has started.
	 *
	 * @return void
	 */
	public function notices() {

		/** @var Indexer $indexer */
		$indexer = wcf()->get_service( 'indexer' );

		if ( ! $indexer->is_batch_index_running() ) {
			return;
		}

		if ( $indexer->is_silently_running() ) {
			return;
		}

		?>
		<div class="notice notice-warning wcf-admin-notice main-notice">
			<div class="components-notice__content">
				<h2><?php esc_html_e( 'WooCommerce Product Filters', 'woocommerce-product-filters' ); ?></h2>
				<p><?php esc_html_e( 'Regenerating the index in the background. Product filtering and sorting may not be accurate until this finishes. It will take a few minutes and this notice will disappear when complete.', 'woocommerce-product-filters' ); ?></p>
			</div>
		</div>

		<style>
			.wcf-admin-notice {
				background-color: #fff !important;
				box-shadow: 0 1px 4px rgba(0,0,0,0.15) !important;
			}

			.wcf-admin-notice h2 {
				margin-bottom: 0.5rem;
			}

			.wcf-admin-notice .components-notice__content {
				margin: 10px 25px 10px 0.5rem;
			}

			.wcf-admin-notice .components-notice__content p {
				margin: 0;
			}

			.wcf-admin-notice .is-primary {
				margin-top: 1rem;
			}
		</style>
		<?php

	}
}
