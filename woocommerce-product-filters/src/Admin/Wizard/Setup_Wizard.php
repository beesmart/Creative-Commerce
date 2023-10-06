<?php
/**
 * @package   Barn2\woocommerce-product-filters
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */

namespace Barn2\Plugin\WC_Filters\Admin\Wizard;

use Barn2\Plugin\WC_Filters\Admin\Wizard\Steps\Completed;
use Barn2\Plugin\WC_Filters\Admin\Wizard\Steps\Filter_Behavior;
use Barn2\Plugin\WC_Filters\Admin\Wizard\Steps\Filter_Display;
use Barn2\Plugin\WC_Filters\Admin\Wizard\Steps\Filter_Visibility;
use Barn2\Plugin\WC_Filters\Admin\Wizard\Steps\License_Verification;
use Barn2\Plugin\WC_Filters\Admin\Wizard\Steps\Upsell;
use Barn2\Plugin\WC_Filters\Dependencies\Setup_Wizard\Setup_Wizard as WPF_Setup_Wizard;
use Barn2\Plugin\WC_Filters\Dependencies\Lib\Plugin\License\EDD_Licensing;
use Barn2\Plugin\WC_Filters\Dependencies\Lib\Plugin\License\Plugin_License;
use Barn2\Plugin\WC_Filters\Dependencies\Lib\Plugin\Licensed_Plugin;
use Barn2\Plugin\WC_Filters\Dependencies\Lib\Registerable;

use function Barn2\Plugin\WC_Filters\wcf;

class Setup_Wizard implements Registerable {
	/**
	 * Plugin instance
	 *
	 * @var Licensed_Plugin
	 */
	private $plugin;

	/**
	 * Wizard instance
	 *
	 * @var WPF_Setup_Wizard
	 */
	private $wizard;

	/**
	 * Get things started.
	 *
	 * @param Licensed_Plugin $plugin
	 */
	public function __construct( Licensed_Plugin $plugin ) {
		$this->plugin = $plugin;

		$steps = [
			new License_Verification(),
			new Filter_Display(),
			new Filter_Visibility(),
			new Filter_Behavior(),
			new Upsell(),
			new Completed()
		];

		$wizard = new WPF_Setup_Wizard( $this->plugin, $steps );

		$wizard->configure(
			[
				'skip_url'        => admin_url( 'edit.php?post_type=product&page=filters&tab=settings' ),
				'license_tooltip' => esc_html__( 'The licence key is contained in your order confirmation email.', 'woocommerce-product-filters' ),
				'utm_id'          => 'wpf',
				'groups_admin'    => admin_url( 'edit.php?post_type=product&page=filters&tab=filters' ),
				'widgets_page'    => admin_url( 'widgets.php' ),
				'signpost'        => [
					[
						'title' => __( 'Manage filters', 'woocommerce-product-filters' ),
						'href'  => admin_url( 'edit.php?post_type=product&page=filters&tab=filters' )
					],
					[
						'title' => __( 'Create filter widgets', 'woocommerce-product-filters' ),
						'href'  => admin_url( 'widgets.php' ),
					]
				]
			]
		);

		$wizard->set_lib_url( wcf()->get_dir_url() . '/dependencies/barn2/setup-wizard/' );
		$wizard->set_lib_path( wcf()->get_dir_path() . '/dependencies/barn2/setup-wizard/' );

		$wizard->add_edd_api( EDD_Licensing::class );
		$wizard->add_license_class( Plugin_License::class );
		$wizard->add_restart_link( 'filters', 'filtersgeneral_settings' );

		$this->wizard = $wizard;
	}

	/**
	 * Register the service.
	 *
	 * @return void
	 */
	public function register() {
		$this->wizard->boot();

		add_action( 'admin_enqueue_scripts', [ $this, 'setup_wizard_style_fixes' ] );
	}

	/**
	 * Fix some styling issues of the setup wizard.
	 *
	 * @return void
	 */
	public function setup_wizard_style_fixes() {
		?>
		<style>
			.barn2-wizard-input.input-checkbox.with-top-border,
			.barn2-wizard-input.input-radio.with-top-border {
				padding-top: 24px !important;
			}
			.barn2-wizard-input.input-radio.with-top-border {
				padding-top: 24px !important;
				padding-bottom: 0 !important;
				margin-bottom: 0.5rem !important;
			}
			.barn2-wizard-input.with-no-bottom-margin {
				margin-bottom: 0.5rem !important;
			}
		</style>
		<?php
	}

}
