<?php
/**
 * @package   Barn2\woocommerce-product-filters
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */

namespace Barn2\Plugin\WC_Filters;

use Barn2\Plugin\WC_Filters\Admin\Admin_Controller;
use Barn2\Plugin\WC_Filters\Admin\Wizard\Setup_Wizard;
use Barn2\Plugin\WC_Filters\Dependencies\Sematico\FluentQuery\DatabaseCapsule;
use Barn2\Plugin\WC_Filters\Integrations\Plugin_Acf;
use Barn2\Plugin\WC_Filters\Integrations\Plugin_Elementor;
use Barn2\Plugin\WC_Filters\Integrations\Plugin_Ept;
use Barn2\Plugin\WC_Filters\Integrations\Plugin_Private_Store;
use Barn2\Plugin\WC_Filters\Integrations\Plugin_Product_Table;
use Barn2\Plugin\WC_Filters\Integrations\Plugin_Protected_Categories;
use Barn2\Plugin\WC_Filters\Integrations\Plugin_Restaurant_Ordering;
use Barn2\Plugin\WC_Filters\Integrations\Plugin_Show_Single_Variations;
use Barn2\Plugin\WC_Filters\Integrations\Plugin_Wholesale_Pro;
use Barn2\Plugin\WC_Filters\Integrations\Theme_Avada;
use Barn2\Plugin\WC_Filters\Integrations\Theme_Divi;
use Barn2\Plugin\WC_Filters\Integrations\Theme_Flatsome;
use Barn2\Plugin\WC_Filters\Integrations\Theme_GeneratePress;
use Barn2\Plugin\WC_Filters\Integrations\Theme_Jupiter;
use Barn2\Plugin\WC_Filters\Integrations\Theme_Salient;
use Barn2\Plugin\WC_Filters\Integrations\Theme_Shopkeeper;
use Barn2\Plugin\WC_Filters\Integrations\Theme_TheSeven;
use Barn2\Plugin\WC_Filters\Integrations\Theme_Uncode;
use Barn2\Plugin\WC_Filters\Integrations\Theme_Xstore;
use Barn2\Plugin\WC_Filters\Integrations\WC_Shortcodes;
use Barn2\Plugin\WC_Filters\Dependencies\Lib\Plugin\Licensed_Plugin;
use Barn2\Plugin\WC_Filters\Dependencies\Lib\Plugin\Premium_Plugin;
use Barn2\Plugin\WC_Filters\Dependencies\Lib\Registerable;
use Barn2\Plugin\WC_Filters\Dependencies\Lib\Service_Container;
use Barn2\Plugin\WC_Filters\Dependencies\Lib\Service_Provider;
use Barn2\Plugin\WC_Filters\Dependencies\Lib\Translatable;
use Barn2\Plugin\WC_Filters\Dependencies\Lib\Util;
use WPTRT\AdminNotices\Notices;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * WCF Plugin.
 */
class Plugin extends Premium_Plugin implements Licensed_Plugin, Registerable, Translatable, Service_Provider {

	use Service_Container;

	const NAME        = 'WooCommerce Product Filters';
	const ITEM_ID     = 392496;
	const META_PREFIX = 'wcf_';

	private $services = [];

	/**
	 * Constructs and initalizes the main plugin class.
	 *
	 * @param string $file The main plugin file.
	 * @param string $version The current plugin version.
	 */
	public function __construct( $file = null, $version = '1.0.0' ) {
		parent::__construct(
			[
				'name'               => self::NAME,
				'item_id'            => self::ITEM_ID,
				'version'            => $version,
				'file'               => $file,
				'is_woocommerce'     => true,
				'settings_path'      => 'edit.php?post_type=product&page=filters&tab=settings',
				'documentation_path' => 'kb-categories/product-filters-documentation',
			]
		);
	}

	/**
	 * Hook into WordPress
	 *
	 * @return void
	 */
	public function register() {
		parent::register();

		// We create Plugin_Setup here so the plugin activation hook will run.
		$plugin_setup = new Plugin_Setup( $this->get_file(), $this );
		$plugin_setup->register();

		add_action( 'init', [ $this, 'load_textdomain' ] );
		add_action( 'plugins_loaded', [ $this, 'maybe_load_plugin' ] );
	}

	/**
	 * Register plugin services
	 *
	 * @return void
	 */
	public function maybe_load_plugin() {
		// Don't load anything if WooCommerce not active.
		if ( ! $this->check_wc_requirements() ) {
			return;
		}

		$this->register_services();
	}

	/**
	 * Get list of services
	 *
	 * @return array
	 */
	public function get_services() {
		$services = [];

		if ( is_admin() ) {
			$services['settings'] = new Admin_Controller( $this, $this->get_license_setting(), $this->get_license() );
		}

		$database_capsule = new DatabaseCapsule();
		$database_capsule->boot();

		$services['db']           = $database_capsule;
		$services['setup_wizard'] = new Setup_Wizard( $this );

		if ( class_exists( 'WC_Action_Queue' ) ) {
			$services['queue'] = new \WC_Action_Queue();
		}

		$services['actions']      = new Actions();
		$services['indexer']      = new Indexer();
		$services['api']          = new Api();

		if ( $this->has_valid_license() ) {
			$services['display']   = new Display();
			$services['prefiller'] = new Prefiller();
			$services['request']   = new Request();
			$services['widget']    = new Widget();

			$services['integration_theme/avada']                   = new Theme_Avada();
			$services['integration_theme/generatepress']           = new Theme_GeneratePress();
			$services['integration_theme/divi']                    = new Theme_Divi();
			$services['integration_theme/the7']                    = new Theme_TheSeven();
			$services['integration_theme/salient']                 = new Theme_Salient();
			$services['integration_theme/shopkeeper']              = new Theme_Shopkeeper();
			$services['integration_theme/xstore']                  = new Theme_Xstore();
			$services['integration_theme/uncode']                  = new Theme_Uncode();
			$services['integration_theme/jupiter']                 = new Theme_Jupiter();
			$services['integration_theme/flatsome']                = new Theme_Flatsome();
			$services['integration_plugin/product-table']          = new Plugin_Product_Table();
			$services['integration_plugin/restaurant-ordering']    = new Plugin_Restaurant_Ordering();
			$services['integration_plugin/wholesale-pro']          = new Plugin_Wholesale_Pro();
			$services['integration_plugin/protected-categories']   = new Plugin_Protected_Categories();
			$services['integration_plugin/private_store']          = new Plugin_Private_Store();
			$services['integration_plugin/elementor']              = new Plugin_Elementor();
			$services['integration_plugin/wc-shortcodes']          = new WC_Shortcodes();
			$services['integration_plugin/show-single-variations'] = new Plugin_Show_Single_Variations();
			$services['integration_plugin/acf']                    = new Plugin_Acf();
			$services['integration_plugin/ept']                    = new Plugin_Ept();
		}

		$services['meta_fields'] = new Meta_Fields( $services );

		return $services;
	}

	/**
	 * Make plugin translatable
	 *
	 * @return void
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'woocommerce-product-filters', false, $this->get_slug() . '/languages' );
	}

	/**
	 * Check the WooCommerce requirements are met.
	 *
	 * @return bool
	 */
	private function check_wc_requirements(): bool {
		if ( ! class_exists( 'WooCommerce' ) ) {
			if ( is_admin() ) {
				$admin_notice = new Notices();
				$admin_notice->add(
					'wpo_woocommerce_missing',
					'',
					/* translators: %1$s: Install WooCommerce <a> tag open. %2$s: <a> tag close.  */
					sprintf( __( 'Please %1$sinstall WooCommerce%2$s in order to use WooCommerce Product Filters.', 'woocommerce-product-filters' ), Util::format_link_open( 'https://woocommerce.com/', true ), '</a>' ),
					[
						'type'       => 'error',
						'capability' => 'install_plugins',
						'screens'    => [ 'plugins' ],
					]
				);
				$admin_notice->boot();
			}

			return false;
		}

		global $woocommerce;

		if ( version_compare( $woocommerce->version, '3.7', '<' ) ) {
			if ( is_admin() ) {
				$admin_notice = new Notices();
				$admin_notice->add(
					'wpo_invalid_wc_version',
					'',
					/* translators: %1$s: Plugin name. */
					sprintf( __( 'The %1$s plugin requires WooCommerce 3.7 or greater. Please update your WooCommerce setup first.', 'woocommerce-product-filters' ), self::NAME ),
					[
						'type'       => 'error',
						'capability' => 'install_plugins',
						'screens'    => [ 'plugins', 'woocommerce_page_wc-settings' ],
					]
				);
				$admin_notice->boot();
			}

			return false;
		}

		return true;
	}
}
