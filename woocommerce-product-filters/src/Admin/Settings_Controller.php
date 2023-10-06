<?php
/**
 * @package   Barn2\woocommerce-product-filters
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */

namespace Barn2\Plugin\WC_Filters\Admin;

use Barn2\Plugin\WC_Filters\Utils\Settings;
use Barn2\Plugin\WC_Filters\Dependencies\Lib\Admin\Settings_API_Helper;
use Barn2\Plugin\WC_Filters\Dependencies\Lib\Plugin\License\Admin\License_Setting;
use Barn2\Plugin\WC_Filters\Dependencies\Lib\Plugin\Licensed_Plugin;
use Barn2\Plugin\WC_Filters\Dependencies\Lib\Registerable;
use Barn2\Plugin\WC_Filters\Dependencies\Lib\Util;

use function Barn2\Plugin\WC_Filters\wcf;

/**
 * Handles display of the settings pages.
 */
class Settings_Controller implements Registerable {

	const TAB_ID       = 'settings';
	const OPTION_GROUP = 'wc_product_filter_pro_settings';
	const MENU_SLUG    = 'wpf-settings-general';

	private $plugin;

	private $license_setting;

	private $id;

	private $title;

	private $default_settings = [];

	/**
	 * Get things started.
	 *
	 * @param License_Setting $license_setting
	 */
	public function __construct( Licensed_Plugin $plugin ) {
		$this->plugin          = $plugin;
		$this->license_setting = $plugin->get_license_setting();
		$this->id              = 'settings';
		$this->title           = __( 'Settings', 'woocommerce-product-filters' );
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'admin_init', [ $this, 'register_settings' ] );
	}

	public function register_settings() {

		register_setting(
			self::OPTION_GROUP,
			'wcf_options',
			[
				'type'        => 'string',
				'description' => 'WooCommerce Product Filters settings',
				'sanitize_callback' => [ $this, 'save_options' ]
			]
		);

		// Licence Key section.
		Settings_API_Helper::add_settings_section(
			'wcf_license_key',
			self::MENU_SLUG,
			'',
			'',
			[
				$this->license_setting->get_license_key_setting(),
				$this->license_setting->get_license_override_setting()
			]
		);

		Settings_API_Helper::add_settings_section(
			'wcf_visibility',
			self::MENU_SLUG,
			__( 'Shop filters', 'woocommerce-product-filters' ),
			[ $this, 'display_document_data_description' ],
			self::get_visibility_settings()
		);

		Settings_API_Helper::add_settings_section(
			'wcf_behaviour',
			self::MENU_SLUG,
			__( 'Behavior', 'woocommerce-product-filters' ),
			null,
			self::get_behavior_settings()
		);

		Settings_API_Helper::add_settings_section(
			'wcf_buttons',
			self::MENU_SLUG,
			__( 'Filter text', 'woocommerce-product-filters' ),
			__( 'Customize the text which appears on the filters.', 'woocommerce-product-filters' ),
			self::get_button_settings()
		);

	}

	/**
	 * Displays the description for the first settings section.
	 *
	 * @return void
	 */
	public function display_document_data_description() {
		$url = admin_url( 'widgets.php' );

		?>
		<p>
			<?php
			echo sprintf(
				__( 'These settings control the filters that appear above the list of products in your store. You can also add filters to a sidebar by adding a <a href="%s">Product Filters widget</a>.', 'woocommerce-product-filters' ),
				esc_url( $url )
			);
			?>
		</p>
		<?php
	}

	public static function get_button_settings() {
		return [
			[
				'title'   => __( '‘Show filters’ button', 'woocommerce-product-filters' ),
				'type'    => 'text',
				'id'      => 'wcf_options[show_filters_button_text]',
				'default' => __( 'Filter', 'woocommerce-product-filters' ),
			],
			[
				'title'   => __( 'Slide-out panel heading', 'woocommerce-product-filters' ),
				'type'    => 'text',
				'id'      => 'wcf_options[slideout_heading]',
				'default' => __( 'Filter', 'woocommerce-product-filters' ),
			],
			[
				'title'       => __( '‘Apply filters’ button', 'woocommerce-product-filters' ),
				'type'        => 'text',
				'id'          => 'wcf_options[button_text]',
				'default'     => __( 'Apply Filters', 'woocommerce-product-filters' ),
				'placeholder' => __( 'Apply Filters', 'woocommerce-product-filters' ),
			],
			[
				'title'       => __( '‘Clear filters’ link', 'woocommerce-product-filters' ),
				'type'        => 'text',
				'id'          => 'wcf_options[clear_button_text]',
				'default'     => __( 'Clear filters', 'woocommerce-product-filters' ),
				'placeholder' => __( 'Clear filters', 'woocommerce-product-filters' ),
			],
		];
	}

	public static function get_behavior_settings() {
		return [
			[
				'title'   => __( 'Filter mode', 'woocommerce-product-filters' ),
				// 'desc_tip' => __( 'Either apply each filter instantly as soon as you make a selection, or display a &quot;Filter&quot; button under the list of filters.', 'woocommerce-product-filters' ),
				'id'      => 'wcf_options[filter_mode]',
				'type'    => 'radio',
				'options' => [
					'instant' => __( 'Apply filters as soon as the customer makes a selection', 'woocommerce-product-filters' ),
					'button'  => __( 'Click a button to apply the filters', 'woocommerce-product-filters' ),
				],
				'default' => 'instant',
				'class'   => 'wcf-filter_mode'
			],
			[
				'title' => __( 'Toggle filters', 'woocommerce-product-filters' ),
				'label' => __( 'Allow customers to toggle filters open or closed', 'woocommerce-product-filters' ),
				'desc_tip' => __( 'This applies to filter widgets, the slide-out panel, and filters using the vertical layout which are added using a shortcode.', 'woocommerce-product-filters' ),
				'id'    => 'wcf_options[toggle_filters]',
				'type'  => 'checkbox',
				'class' => 'wcf-toggle_filters'
			],
			[
				'title'   => __( 'Default toggle state', 'woocommerce-product-filters' ),
				'id'      => 'wcf_options[toggle_default_status]',
				// 'desc_tip' => __( 'Decide whether the filters are toggled to open or closed when the page first loads.', 'woocommerce-product-filters' ),
				'type'    => 'radio',
				'options' => [
					'open'   => __( 'Open', 'woocommerce-product-filters' ),
					'closed' => __( 'Closed', 'woocommerce-product-filters' ),
				],
				'default' => 'closed',
				'class'   => 'wcf-toggle_default_status'
			],
			[
				'id'      => 'wcf_options[display_count]',
				'title'   => __( 'Product count', 'woocommerce-product-filters' ),
				'type'    => 'checkbox',
				'label' => __( 'Display the number of products next to each option', 'woocommerce-product-filters' ),
			],
		];
	}

	public static function get_visibility_settings() {
		$groups = Settings::get_groups_for_dropdown();

		return [
			[
				'title'   => __( 'Filter group', 'woocommerce-product-filters' ),
				'desc'    => __( 'Select a filter group to display above the lists of products in your store.', 'woocommerce-product-filters' ),
				'id'      => 'wcf_options[group_display_shop_archive]',
				'type'    => 'select',
				'options' => $groups,
				'class'   => 'wcf-horizontal-group',
			],

			[
				'title'   => __( 'Filter visibility', 'woocommerce-product-filters' ),
				'id'      => 'wcf_options[desktop_visibility]',
				'type'    => 'radio',
				'options' => [
					'open'   => __( 'Always display filters', 'woocommerce-product-filters' ),
					'closed' => __( 'Click button to reveal filters', 'woocommerce-product-filters' ),
				],
				'default' => 'open',
			],

			[
				'title'   => __( 'On mobile', 'woocommerce-product-filters' ),
				'id'      => 'wcf_options[mobile_visibility]',
				'type'    => 'radio',
				'options' => [
					'open'   => __( 'Always display filters', 'woocommerce-product-filters' ),
					'closed' => __( 'Click button to reveal filters', 'woocommerce-product-filters' ),
				],
				'default' => 'open',
				'class'   => 'wcf-mobile-filters'
			],

			[
				'title'       => __( 'Number of filters per row', 'woocommerce-product-filters' ),
				'type'        => 'number',
				'id'          => 'wcf_options[horizontal_per_row]',
				'desc'        => __( 'Enter the number of filters to display per row within the horizontal layout.', 'woocommerce-product-filters' ),
				'placeholder' => '4',
				'class'       => 'wcf-horizontal_per_row',
			],
		];
	}

	/**
	 * Get the tab title.
	 *
	 * @return string
	 */
	public function get_title() {
		return $this->title;
	}

	/**
	 * Get the tab ID.
	 *
	 * @return string
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * Enqueue assets when required.
	 *
	 * @return void
	 */
	public function assets() {

		global $current_section;

		if ( $current_section !== 'filters' ) {
			return;
		}

		$subsection = $this->get_current_subsection();

		if ( $subsection !== 'filters' && $subsection !== false ) {
			return;
		}

		$file_name = 'wcf-admin-editor';

		$admin_script_path       = 'assets/build/' . $file_name . '.js';
		$admin_script_asset_path = wcf()->get_dir_path() . 'assets/build/' . $file_name . '.asset.php';
		$admin_script_asset      = file_exists( $admin_script_asset_path )
		? require $admin_script_asset_path
		: [
			'dependencies' => [],
			'version'      => filemtime( $admin_script_path )
		];
		$script_url              = wcf()->get_dir_url() . $admin_script_path;

		wp_register_script(
			$file_name,
			$script_url,
			$admin_script_asset['dependencies'],
			$admin_script_asset['version'],
			true
		);

		wp_enqueue_script( $file_name );

		wp_add_inline_script( $file_name, 'const wcf_groups_editor = ' . wp_json_encode( $this ), 'before' );

		wp_enqueue_style( $file_name, wcf()->get_dir_url() . 'assets/build/wcf-admin-editor.css', [ 'wp-components' ], $admin_script_asset['version'] );

	}

	/**
	 * Adjust visibility settings if configuration is wrong.
	 *
	 * @param array $options
	 * @return array
	 */
	public function save_options( $options ) {

		$this->plugin->get_license_setting()->save_posted_license_key();

		$desktop_visibility = isset( $options['desktop_visibility'] ) ? $options['desktop_visibility'] : 'open';
		$mobile_visibility = isset( $options['mobile_visibility'] ) ? $options['mobile_visibility'] : 'open';

		if ( $mobile_visibility === 'open' && $desktop_visibility === 'closed' ) {
			$options['desktop_visibility'] = 'open';
			$options['mobile_visibility'] = 'closed';
		}

		if ( ! isset( $options['filter_mode'] ) ) {
			$options['filter_mode'] = 'instant';
		}

		if ( ! isset( $options['desktop_visibility'] ) ) {
			$options['desktop_visibility'] = 'open';
		}

		if ( ! isset( $options['mobile_visibility'] ) ) {
			$options['mobile_visibility'] = 'open';
		}

		return $options;

	}

}
