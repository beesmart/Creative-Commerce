<?php
/**
 * @package   Barn2\woocommerce-product-filters
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */

namespace Barn2\Plugin\WC_Filters\Integrations;

use Barn2\Plugin\WC_Filters\Dependencies\Illuminate\Support\Collection;
use Barn2\Plugin\WC_Filters\Display;
use Barn2\Plugin\WC_Filters\Request_Fallback;
use JsonSerializable;

use function Barn2\Plugin\WC_Filters\wcf;

/**
 * Uncode theme integration.
 */
class Theme_Uncode extends Theme_Integration implements JsonSerializable, Fallback_Aware_Interface {

	use Fallback_Aware;

	public $template = 'uncode';

	/**
	 * Register the service.
	 *
	 * @return void
	 */
	public function register() {
		if ( ! $this->should_enqueue() ) {
			return;
		}
		parent::register();
		$this->load_fallback_hooks();
		add_action( 'wp_enqueue_scripts', [ $this, 'assets' ] );
	}

	/**
	 * Load the assets specific to this integration.
	 *
	 * @return void
	 */
	public function assets() {

		$file_name = 'wcf-uncode';

		$integration_script_path       = 'assets/build/' . $file_name . '.js';
		$integration_script_asset_path = wcf()->get_dir_path() . 'assets/build/' . $file_name . '.asset.php';
		$integration_script_asset      = file_exists( $integration_script_asset_path )
		? require $integration_script_asset_path
		: [
			'dependencies' => [],
			'version'      => filemtime( $integration_script_path )
		];
		$script_url                    = wcf()->get_dir_url() . $integration_script_path;

		$integration_script_asset['dependencies'][] = Display::IDENTIFIER;

		wp_register_script(
			$file_name,
			$script_url,
			$integration_script_asset['dependencies'],
			$integration_script_asset['version'],
			true
		);

		wp_enqueue_script( $file_name );

		wp_add_inline_script(
			'wcf-uncode',
			'const WCF_UNCODE_COMPAT = ' . wp_json_encode(
				[
					'baseUrl' => $this->get_page_base_url()
				]
			),
			'before'
		);

	}

	/**
	 * Get the string that is then attached as an argument
	 * to the WP_Query $args array as a flag.
	 *
	 * The flag is used to shortcircuit our filtered results
	 * injection.
	 *
	 * Without the flag we'd end up causing an infinite loop.
	 *
	 * @return string
	 */
	public function get_query_argument_id() {
		return 'uncode-query';
	}

	/**
	 * Enable fallback mode for the theme.
	 *
	 * @param bool $enabled
	 * @return bool
	 */
	public function apply_fallback_mode( $enabled ) {
		return true;
	}

	/**
	 * Insert the filtered results into the query.
	 *
	 * This is used via pre_get_posts.
	 *
	 * @param \WP_Query $query
	 * @return void
	 */
	public function filter_query( $query ) {
		if ( ( is_admin() && ! wp_doing_ajax() ) || ! $this->is_main_query( $query ) || $query->get( $this->get_query_argument_id() ) === true ) {
			return;
		}

		$search_string = $this->get_requested_filters();
		$orderby       = $this->get_requested_orderby();

		$fallback_request = new Request_Fallback();

		if ( ! empty( $search_string ) ) {
			$collection = $fallback_request->parse_request( $search_string );

			if ( $collection instanceof Collection && $collection->isNotEmpty() ) {
				$fallback_request->set_parameters( $collection );

				$fallback_request = $fallback_request->load_filters();

				// Load values from filter and inject appropriate results.
				$fallback_request->update_query_vars( $query );
			}
		}

		if ( ! empty( $orderby ) ) {
			$fallback_request->set_orderby( $orderby );

			// Sort results if needed.
			$fallback_request->maybe_order_results( $query );
		}

	}

	/**
	 * Determine if it's a query we should be filtering or not.
	 *
	 * @param \WP_Query $query
	 * @return boolean
	 */
	private function is_main_query( $query ) {
		$is_main_query = ( $query->is_main_query() || $query->is_archive );
		$is_main_query = ( $query->is_singular || $query->is_feed ) ? false : $is_main_query;
		$is_main_query = ( $query->get( 'suppress_filters', false ) ) ? false : $is_main_query;
		return $is_main_query;
	}

	/**
	 * @inheritdoc
	 */
	public function enqueue_fix() {

		$css = '
			.wcf-form-elements-wrapper input[type="checkbox"],
			.wcf-form-elements-wrapper input[type="radio"],
			.wcf-form-elements-wrapper input[type="radio"]:after,
			.wcf-form-elements-wrapper input[type="radio"]:before {
				display:none !important;
			}
		';

		wp_add_inline_style( $this->get_dummy_handle(), $css );

	}

	/**
	 * Get the base url for the fallback mode.
	 *
	 * @return string
	 */
	public function get_page_base_url() {

		$is_shop_page     = is_shop();
		$is_shop_taxonomy = is_product_category() || is_product_tag();

		if ( $is_shop_page && ! $is_shop_taxonomy ) {
			return get_permalink( wc_get_page_id( 'shop' ) );
		}

		return get_term_link( get_queried_object_id() );

	}

}
