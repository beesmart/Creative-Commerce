<?php
/**
 * @package   Barn2\woocommerce-product-filters
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */

namespace Barn2\Plugin\WC_Filters\Integrations;

use Barn2\Plugin\WC_Filters\Api;
use Barn2\Plugin\WC_Filters\Display;
use Barn2\Plugin\WC_Filters\Dependencies\Lib\Registerable;
use JsonSerializable;

use function Barn2\Plugin\WC_Filters\wcf;

/**
 * Adds integration for WC Shortcodes.
 */
class WC_Shortcodes implements Registerable, JsonSerializable {

	/**
	 * List of supported WC shortcodes.
	 *
	 * @var array
	 */
	public $types = [
		'recent_products',
		'products',
		'sale_products',
		'best_selling_products',
		'top_rated_products',
		'featured_products',
	];

	/**
	 * Register the integration.
	 *
	 * @return void
	 */
	public function register() {
		$this->catch_page();
	}

	/**
	 * Load the assets specific to this integration.
	 *
	 * @return void
	 */
	public function assets() {

		$file_name = 'wcf-wc-shortcodes';

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

		// This is needed because the WCF_Fallback constants is used by
		// the function we're using to update counters - we're not really using fallback mode here.
		wp_add_inline_script( $file_name, 'const WCF_Fallback = ' . wp_json_encode( $this ), 'before' );
	}

	/**
	 * Wraps the output of the [products] shortcode with
	 * a custom div and generate our fallback elements for prefilling.
	 *
	 * @param array $attributes
	 * @return void
	 */
	public function add_tags( $attributes ) {
		$query = ( new WC_Shortcodes_Wrapper( $attributes, 'products' ) )->get_all_queried_products();

		echo $this->generate_fallback_output( isset( $query->ids ) ? $query->ids : [] );

		add_filter(
			'wcf_results_count',
			function( $args, $original_query, $post_ids, $filters ) use ( $attributes, $query ) {
				$posts_per_page = $attributes['limit'];
				$current_page   = $attributes['page'];

				$args['total']    = $query->total;
				$args['per_page'] = $posts_per_page === '-1' ? $query->total : $posts_per_page;
				$args['current']  = $current_page;

				return $args;
			},
			10,
			4
		);

		echo '<div class="wcf-wc-shortcode-wrapper">';
		$this->add_opening_tag();
	}

	/**
	 * Close the shortcode wrapper div element and inject
	 * the required html comment for the ajax request.
	 *
	 * @param array $attributes
	 * @return void
	 */
	public function add_close_tags( $attributes ) {
		$this->add_closing_tag();
		echo '</div>';
	}

	/**
	 * Generates the output of our fallback elements.
	 *
	 * @param array $products
	 * @return void
	 */
	public function generate_fallback_output( array $products ) {
		if ( empty( $products ) ) {
			return;
		}

		$service = wcf()->get_service( 'display' );

		ob_start();

		$service->add_active_filters();
		$service->add_mobile_drawer();
		$service->add_sorting_bar();

		echo '<div id="wcf-fallback-products-count" data-count="' . absint( count( $products ) ) . '"></div>';
		echo '<div id="wcf-fallback-post-ids" data-ids="' . esc_attr( implode( ',', $products ) ) . '"></div>';

		$totals = ob_get_clean();

		return $totals;
	}

	/**
	 * Disable the original query wrapper.
	 *
	 * @return void
	 */
	public function disable_wp_query_wrapper() {
		$display = wcf()->get_service( 'display' );
		remove_action( 'loop_start', [ $display, 'add_template_tag' ] );
		remove_action( 'loop_no_results', [ $display, 'add_template_tag' ] );
		remove_action( 'loop_end', [ $display, 'add_closing_template_tag' ] );
		remove_action( 'woocommerce_before_shop_loop', [ $display, 'add_mobile_drawer' ], 8 );
		remove_action( 'woocommerce_before_shop_loop', [ $display, 'add_shop_filters' ], 8 );
		remove_action( 'woocommerce_before_shop_loop', [ $display, 'add_shop_filters' ], 8 );
		remove_action( 'woocommerce_before_shop_loop', [ $display, 'add_active_filters' ], 8 );
		remove_action( 'woocommerce_before_shop_loop', [ $display, 'add_sorting_bar' ], 9 );
	}

	/**
	 * Add the opening tag from our display service.
	 *
	 * @return void
	 */
	public function add_opening_tag() {
		$service = wcf()->get_service( 'display' );
		$service->add_template_tag( true );
	}

	/**
	 * Add the closing tag from our display service.
	 *
	 * @return void
	 */
	public function add_closing_tag() {
		$service = wcf()->get_service( 'display' );
		$service->add_closing_template_tag( true );
	}

	/**
	 * Add filtering flags to the shortcodes.
	 *
	 * @param array $args
	 * @param array $attributes
	 * @param string $type
	 * @return array
	 */
	public function add_flags( $args, $attributes, $type ) {
		$args['woocommerce-filters']               = true;
		$args['woocommerce-filters-wc-shortcodes'] = true;
		return $args;
	}

	/**
	 * Disable our query wrappers on page load.
	 *
	 * @return void
	 */
	public function catch_page() {
		add_action(
			'wp',
			function() {
				if ( ! is_page() ) {
					return;
				}

				if ( $this->has_wc_shortcode() ) {
					add_filter( 'woocommerce_shortcode_products_query', [ $this, 'add_flags' ], 10, 3 );
					foreach ( $this->types as $type ) {
						add_action( 'woocommerce_shortcode_before_' . $type . '_loop', [ $this, 'add_tags' ] );
						add_action( 'woocommerce_shortcode_after_' . $type . '_loop', [ $this, 'add_close_tags' ] );
					}
					$this->disable_wp_query_wrapper();
					add_filter( 'wcf_is_pagination_disabled', '__return_true' );
					$this->assets();
				}
			}
		);
	}

	/**
	 * Determine if the page has at least one of the supported shortcodes.
	 *
	 * @return boolean
	 */
	private function has_wc_shortcode() {

		global $post;

		$has_shortcode = false;

		foreach ( $this->types as $shortcode_string ) {
			if ( has_shortcode( $post->post_content, $shortcode_string ) ) {
				$has_shortcode = true;
			}
		}

		return $has_shortcode;
	}

	/**
	 * Prepare fallback json array.
	 *
	 * @return array
	 */
	#[\ReturnTypeWillChange]
	public function jsonSerialize() {
		return [
			'restNonce' => wp_create_nonce( 'wp_rest' ),
			'rest'      => trailingslashit( get_rest_url() . Api::API_NAMESPACE ) . 'counters',
		];
	}

}
