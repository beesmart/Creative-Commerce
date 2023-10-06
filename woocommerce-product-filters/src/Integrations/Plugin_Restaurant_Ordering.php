<?php
/**
 * @package   Barn2\woocommerce-product-filters
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */

namespace Barn2\Plugin\WC_Filters\Integrations;

use Barn2\Plugin\WC_Filters\Api;
use Barn2\Plugin\WC_Filters\Dependencies\Illuminate\Support\Collection;
use Barn2\Plugin\WC_Filters\Display;
use Barn2\Plugin\WC_Filters\Query_Cache;
use Barn2\Plugin\WC_Filters\Utils\Products;
use Barn2\Plugin\WC_Filters\Utils\Responses;
use Barn2\Plugin\WC_Filters\Dependencies\Lib\Registerable;
use JsonSerializable;

use function Barn2\Plugin\WC_Filters\wcf;

/**
 * Adds filtering support for the restaurant ordering plugin.
 */
class Plugin_Restaurant_Ordering implements Registerable, JsonSerializable {

	/**
	 * Holds the prefix used for identifying the query.
	 */
	const INTEGRATION_PREFIX = '_wro';

	/**
	 * Holds the ID number of the restaurant order page
	 * selected through the plugin's settings.
	 *
	 * @var string|bool
	 */
	protected $restaurant_page_id;

	/**
	 * Holds the query and filters cache handler.
	 *
	 * @var Query_Cache
	 */
	protected $cache;

	/**
	 * Register the integration.
	 *
	 * @return void
	 */
	public function register() {
		if ( ! function_exists( '\Barn2\Plugin\WC_Restaurant_Ordering\wro' ) ) {
			return;
		}
		$this->init();
	}

	/**
	 * Hook into WP.
	 *
	 * @return void
	 */
	public function init() {
		$this->restaurant_page_id = get_option( 'wro_menu_page', false );
		$this->intercept();

		add_action( 'wp_enqueue_scripts', [ $this, 'assets' ] );
		add_filter( 'wc_restaurant_ordering_wc_get_products_args', [ $this, 'add_argument_to_query' ] );
		add_filter( 'wc_restaurant_ordering_menu_output', [ $this, 'add_fallback_elements' ], 10, 3 );
		add_action( 'wcf_prefilled_query', [ $this, 'cache_query' ], 10, 4 );
		add_action( 'wc_restaurant_ordering_before_restaurant_info', [ $this, 'add_opening_tag' ] );
		add_action( 'wc_restaurant_ordering_after_menu_items', [ $this, 'add_closing_tag' ] );
	}

	/**
	 * Intercept the search request, load the hooks and buffer the output.
	 *
	 * @return void
	 */
	public function intercept() {
		//phpcs:ignore
		if ( ! isset( $_GET[ self::INTEGRATION_PREFIX ] ) ) {
			return;
		}

		add_filter( 'show_admin_bar', '__return_false' );
		add_action( 'shutdown', [ $this, 'inject_template' ], 0 );
		ob_start();
	}

	/**
	 * Cache the prefilled query so that it can then be used at a later point
	 * when sending back an ajax response.
	 *
	 * @param \WP_Query $query
	 * @param Collection $filters
	 * @param string $orderby
	 * @param array $post_ids
	 * @return void
	 */
	public function cache_query( \WP_Query $query, Collection $filters, string $orderby, array $post_ids ) {
		if ( ! $query->get( 'woocommerce-filters-prefilled' ) && ! $query->get( 'woocommerce-filters-wro' ) ) {
			return;
		}

		$cache = new Query_Cache( $query, $orderby, $filters, $post_ids );
		$cache->set_cache_prefix( self::INTEGRATION_PREFIX );
		$cache->cache();

		$this->cache = $cache;
	}

	/**
	 * Print the expected json response before headers are sent.
	 *
	 * @return void
	 */
	public function inject_template() {
		$html = ob_get_clean();

		preg_match( '/<body(.*?)>(.*?)<\/body>/s', $html, $matches );

		if ( ! empty( $matches ) ) {
			$html = trim( $matches[2] );
		}

		if ( empty( $this->cache->get_post_ids() ) ) {
			$this->cache->set_post_ids( $this->cache->get_query()->get_posts() );
		}

		$wro_query       = $this->cache->get_query();
		$found_posts     = count( $this->cache->get_post_ids() );
		$counts          = $this->cache->get_counts();
		$result_count    = $this->cache->generate_result_count();
		$url_params      = $this->cache->prepare_url_params();
		$is_404          = $this->cache->is_404() && empty( $this->cache->get_filters() );
		$no_products_tpl = $this->cache->is_404() && empty( $this->cache->get_filters() ) ? Responses::generate_no_products_template() : false;
		$orderby         = $this->cache->get_orderby();

		$this->cache->purge();

		if ( $is_404 ) {
			$found_posts = 0;
		}

		wp_send_json(
			[
				'output'          => Products::get_string_between( $html, '<!--wcf-loop-start-->', '<!--wcf-loop-end-->' ),
				'found_posts'     => $found_posts,
				'paged'           => empty( $wro_query->get( 'paged' ) ) ? 1 : $wro_query->get( 'paged' ),
				'posts_per_page'  => $wro_query->get( 'posts_per_page' ),
				'offset'          => $wro_query->get( 'offset' ),
				'counts'          => $counts,
				'result_count'    => $result_count,
				'url_params'      => $url_params,
				'is_404'          => $is_404,
				'no_products_tpl' => $no_products_tpl,
				'orderby'         => $orderby,
				'reset'           => false,
			]
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
		return 'woocommerce-filters-wro';
	}

	/**
	 * Add the filtering flag to the queries.
	 *
	 * @param array $args
	 * @return array
	 */
	public function add_argument_to_query( $args ) {
		$args[ $this->get_query_argument_id() ] = true;
		$args['woocommerce-filters']            = true;
		return $args;
	}

	/**
	 * Load the integration js files.
	 *
	 * @return void
	 */
	public function assets() {
		if ( ! is_page( $this->restaurant_page_id ) ) {
			return;
		}

		$file_name = 'wcf-restaurant-ordering';

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
		wp_add_inline_script( Display::IDENTIFIER, 'const WCF_Fallback = ' . wp_json_encode( $this ), 'before' );

		$this->disable_wp_query_wrapper();
	}

	/**
	 * Add an hidden div that contains the total number of products
	 * that the table's query has produced.
	 *
	 * We need this because there's no other way to retrieve the number
	 * that we need to show inside our sidebar filters.
	 *
	 * @param string $output original output
	 * @param array $options menu options
	 * @param array $products list of products inside the query.
	 * @return string
	 */
	public function add_fallback_elements( $output, $options, $products ) {
		$post_ids = [];

		foreach ( $products as $product ) {
			$post_ids[] = $product->get_id();
		}

		$service = wcf()->get_service( 'display' );

		ob_start();

		$service->add_active_filters();
		$service->add_sorting_bar( true );
		$service->add_mobile_drawer();

		echo '<div id="wcf-fallback-products-count" data-count="' . absint( count( $products ) ) . '"></div>';
		echo '<div id="wcf-fallback-post-ids" data-ids="' . esc_attr( implode( ',', $post_ids ) ) . '"></div>';

		$totals = ob_get_clean();

		return $totals . $output;
	}

	/**
	 * Disable the original query wrapper because elementor runs queries twice
	 * so the wrapper goes to the wrong place.
	 *
	 * @return void
	 */
	public function disable_wp_query_wrapper() {
		$display = wcf()->get_service( 'display' );
		remove_action( 'loop_start', [ $display, 'add_template_tag' ] );
		remove_action( 'loop_no_results', [ $display, 'add_template_tag' ] );
		remove_action( 'loop_end', [ $display, 'add_closing_template_tag' ] );
	}

	public function add_opening_tag() {
		$service = wcf()->get_service( 'display' );
		$service->add_template_tag( true );
	}

	public function add_closing_tag() {
		$service = wcf()->get_service( 'display' );
		$service->add_closing_template_tag( true );
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
