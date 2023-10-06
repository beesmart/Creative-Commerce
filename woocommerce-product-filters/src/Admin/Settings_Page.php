<?php
/**
 * @package   Barn2\woocommerce-product-filters
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */

namespace Barn2\Plugin\WC_Filters\Admin;

use Barn2\Plugin\WC_Filters\Dependencies\Lib\Conditional;
use Barn2\Plugin\WC_Filters\Dependencies\Lib\Plugin\License\License;
use Barn2\Plugin\WC_Filters\Dependencies\Lib\Plugin\Licensed_Plugin;
use Barn2\Plugin\WC_Filters\Dependencies\Lib\Registerable;
use Barn2\Plugin\WC_Filters\Dependencies\Lib\Service;
use Barn2\Plugin\WC_Filters\Dependencies\Lib\Util;

/**
 * Plugin settings page.
 */
class Settings_Page implements Service, Registerable, Conditional {
	/**
	 * Plugin handling the page.
	 *
	 * @var Licensed_Plugin
	 */
	public $plugin;

	/**
	 * License handler.
	 *
	 * @var License
	 */
	public $license;

	/**
	 * List of settings.
	 *
	 * @var array
	 */
	public $registered_settings = [];

	/**
	 * Constructor.
	 *
	 * @param Licensed_Plugin $plugin
	 */
	public function __construct( Licensed_Plugin $plugin ) {
		$this->plugin              = $plugin;
		$this->license             = $plugin->get_license();
		$this->registered_settings = $this->get_settings_tabs();
	}

	/**
	 * {@inheritdoc}
	 */
	public function is_required() {
		return Util::is_admin();
	}

	/**
	 * {@inheritdoc}
	 */
	public function register() {
		$this->register_settings_tabs();

		add_action( 'admin_menu', [ $this, 'add_settings_page' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'assets' ] );
	}

	/**
	 * Enqueue assets for the settings page.
	 *
	 * @return void
	 */
	public function assets() {

		$screen = get_current_screen();

		if ( $screen->base !== 'product_page_filters' ) {
			return;
		}

		$file_name = 'wcf-settings';

		$admin_script_path       = 'assets/build/' . $file_name . '.js';
		$admin_script_asset_path = $this->plugin->get_dir_path() . 'assets/build/' . $file_name . '.asset.php';
		$admin_script_asset      = file_exists( $admin_script_asset_path )
		? require $admin_script_asset_path
		: [
			'dependencies' => [],
			'version'      => filemtime( $admin_script_path )
		];
		$script_url              = $this->plugin->get_dir_url() . $admin_script_path;

		wp_register_script(
			$file_name,
			$script_url,
			$admin_script_asset['dependencies'],
			$admin_script_asset['version'],
			true
		);

		wp_enqueue_script( 'barn2-tiptip' );

		wp_enqueue_script( $file_name );

		wp_enqueue_style( $file_name, $this->plugin->get_dir_url() . 'assets/build/wcf-admin-editor.css', [ 'wp-components' ], $admin_script_asset['version'] );

	}

	/**
	 * Retrieves the settings tab classes.
	 *
	 * @return array
	 */
	private function get_settings_tabs() {
		$settings_tabs = [
			Filters_Editor::TAB_ID      => new Filters_Editor(),
			Settings_Controller::TAB_ID => new Settings_Controller( $this->plugin )
		];

		return $settings_tabs;
	}

	/**
	 * Register the settings tab classes.
	 */
	private function register_settings_tabs() {
		array_map(
			function( $setting_tab ) {
				if ( $setting_tab instanceof Registerable ) {
					$setting_tab->register();
				}
			},
			$this->registered_settings
		);
	}

	/**
	 * Register the Settings submenu page.
	 */
	public function add_settings_page() {
		add_submenu_page( 'edit.php?post_type=product', __( 'Filters', 'woocommerce-product-filters' ), __( 'Filters', 'woocommerce-product-filters' ), 'manage_woocommerce', 'filters', [ $this, 'render_settings_page' ] );
	}

	/**
	 * Render the Settings page.
	 */
	public function render_settings_page() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$active_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : 'filters';

		?>
		<div class='woocommerce-layout__header'>
			<div class="woocommerce-layout__header-wrapper">
				<h3 class='woocommerce-layout__header-heading'>
					<?php esc_html_e( 'Product Filters', 'woocommerce-product-filters' ); ?>
				</h3>
				<div class="links-area">
					<?php $this->support_links(); ?>
				</div>
			</div>
		</div>
		<div class="wrap barn2-settings">
			<?php do_action( 'barn2_before_plugin_settings', $this->plugin->get_id() ); ?>
			<div class="barn2-settings-inner">

				<h2 class="nav-tab-wrapper">
					<?php
					foreach ( $this->registered_settings as $setting_tab ) {
						$active_class = $active_tab === $setting_tab->get_id() ? ' nav-tab-active' : '';
						?>
						<a href="<?php echo esc_url( add_query_arg( 'tab', $setting_tab->get_id(), $this->plugin->get_settings_page_url() ) ); ?>" class="<?php echo esc_attr( sprintf( 'nav-tab%s', $active_class ) ); ?>">
							<?php echo esc_html( $setting_tab->get_title() ); ?>
						</a>
						<?php
					}
					?>
				</h2>

				<h1></h1>

				<div class="inside-wrapper">
					<?php if ( $active_tab === 'filters' ) : ?>
						<?php echo $this->registered_settings[ $active_tab ]->output(); //phpcs:ignore ?>
					<?php else : ?>
						<h2>
							<?php esc_html_e( 'Product Filters', 'woocommerce-product-filters' ); ?>
						</h2>
						<p>
							<?php esc_html_e( 'The following options control the WooCommerce Product Filters extension.', 'woocommerce-product-filters' ); ?>
						</p>

						<form action="options.php" method="post">
							<?php
							settings_errors();
							settings_fields( $this->registered_settings[ $active_tab ]::OPTION_GROUP );
							do_settings_sections( $this->registered_settings[ $active_tab ]::MENU_SLUG );
							?>

							<p class="submit">
								<input name="Submit" type="submit" name="submit" class="button button-primary" value="<?php esc_attr_e( 'Save Changes', 'woocommerce-product-filters' ); ?>" />
							</p>
						</form>
					<?php endif; ?>
				</div>

			</div>
			<?php do_action( 'barn2_after_plugin_settings', $this->plugin->get_id() ); ?>
		</div>
		<?php
	}

	/**
	 * Output the Barn2 Support Links.
	 */
	public function support_links() {
		printf(
			'<p>%s | %s | %s</p>',
			// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
			Util::format_link( $this->plugin->get_documentation_url(), __( 'Documentation', 'woocommerce-product-filters' ), true ),
			Util::format_link( $this->plugin->get_support_url(), __( 'Support', 'woocommerce-product-filters' ), true ),
			sprintf(
				'<a class="barn2-wiz-restart-btn" href="%s">%s</a>',
				add_query_arg( [ 'page' => $this->plugin->get_slug() . '-setup-wizard' ], admin_url( 'admin.php' ) ),
				__( 'Setup wizard', 'woocommerce-product-filters' )
			)
			// phpcs:enable
		);
	}
}
