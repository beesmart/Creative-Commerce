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
use Barn2\Plugin\WC_Filters\Utils\Filters;
use Barn2\Plugin\WC_Filters\Utils\Products;
use Barn2\Plugin\WC_Filters\Utils\Responses;
use Barn2\Plugin\WC_Filters\Dependencies\Lib\Registerable;
use JsonSerializable;

use function Barn2\Plugin\WC_Filters\wcf;

/**
 * Handles the Elementor Pro integration.
 */
class Plugin_Elementor implements Registerable, JsonSerializable {

	/**
	 * Holds the prefix used for identifying the query.
	 */
	const INTEGRATION_PREFIX = '_elementor';

	/**
	 * Holds the query and filters cache handler.
	 *
	 * @var Query_Cache
	 */
	protected $cache;

	/**
	 * List of widgets & sections that we support.
	 *
	 * @var array
	 */
	protected $hookables = [
		'section_content' => [
			'woocommerce-products',
			'wc-archive-products'
		],
	];

	/**
	 * Widgets we've added support to.
	 *
	 * @var array
	 */
	protected $supported_widgets = [];

	/**
	 * Cache whether we found our widgets or not.
	 *
	 * @var bool
	 */
	private $found_widget;

	/**
	 * For some reason Elementor runs queries twice.
	 *
	 * @var integer
	 */
	private $products_query_counter = 0;

	/**
	 * Intercept the 2nd Elementor query query.
	 *
	 * @var integer
	 */
	private $products_query_offset = 1;

	/**
	 * Register the integration.
	 *
	 * @return void
	 */
	public function register() {
		if ( ! function_exists( 'elementor_pro_load_plugin' ) ) {
			return;
		}
		$this->init();
	}

	/**
	 * Hook into WP & Elementor.
	 *
	 * @return void
	 */
	public function init() {
		$this->intercept();
		$this->hook_into_widgets();
		add_action( 'elementor/widget/before_render_content', [ $this, 'add_template_class' ] );
		add_filter( 'elementor/widget/render_content', [ $this, 'add_closing_wrapper' ], 10, 2 );
		add_action( 'pre_get_posts', [ $this, 'pre_get_posts' ], 30 );
		add_action( 'wcf_prefilled_query', [ $this, 'cache_query' ], 10, 4 );
		$this->catch_elementor_page();
	}

	/**
	 * Enqueue the elementor specific assets.
	 *
	 * @return void
	 */
	public function assets() {

		$file_name = 'wcf-elementor';

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
	}

	/**
	 * Attach our controls to the Elementor widgets.
	 *
	 * @return void
	 */
	protected function hook_into_widgets() {
		foreach ( $this->hookables as $section_id => $widgets ) {
			foreach ( $widgets as $widget ) {
				add_action( "elementor/element/{$widget}/{$section_id}/after_section_end", [ $this, 'register_controls' ], 10, 2 );
				$this->supported_widgets[ $widget ] = true;
			}
		}
	}

	/**
	 * Register custom controls for the widgets.
	 *
	 * @param object $element Elementor widget instance.
	 * @param array $args
	 * @return void
	 */
	public function register_controls( $element, $args ) {
		$element->start_controls_section(
			'wcf_section',
			[
				'label' => __( 'WooCommerce Product Filters', 'woocommerce-product-filters' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT
			]
		);

		$element->add_control(
			'enable_wcf',
			[
				'label'        => __( 'Enable filtering', 'woocommerce-product-filters' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'woocommerce-product-filters' ),
				'label_off'    => __( 'No', 'woocommerce-product-filters' ),
				'return_value' => 'yes',
				'default'      => 'no'
			]
		);

		$element->end_controls_section();
	}

	/**
	 * Add custom classes to the elements wrapper if filtering is enabled
	 * onto our supported widgets.
	 *
	 * @param object $widget widget instance
	 * @return void
	 */
	public function add_template_class( $widget ) {
		$name = $widget->get_name();

		if ( isset( $this->supported_widgets[ $name ] ) && 'yes' === $widget->get_settings( 'enable_wcf' ) ) {
			$widget->add_render_attribute( '_wrapper', 'class', [ 'wcf-template', 'wcf-elementor-widget' ] );

			$this->found_widget = $name;
			$this->assets();
		}
	}

	/**
	 * Add a closing wrapper around the elements containing our supported element.
	 *
	 * @param string $content
	 * @param object $widget
	 * @return string
	 */
	public function add_closing_wrapper( $content, $widget ) {

		$name = $widget->get_name();

		if ( isset( $this->supported_widgets[ $name ] ) && 'yes' === $widget->get_settings( 'enable_wcf' ) ) {
			// Collect query details.
			if ( Filters::is_taxonomy_page() ) {
				global $wp_query;
				$products_ids = $wp_query->posts;
			} else {
				$query_details = new Elementor_Shortcode( $widget->get_settings() );
				$products_ids  = $query_details->get_products()->ids;
			}

			$display_service = wcf()->get_service( 'display' );

			ob_start();
			$display_service->add_template_tag( true );
			$tag = ob_get_clean();

			ob_start();
			$display_service->add_closing_template_tag( true );
			$closing_tag = ob_get_clean();

			ob_start();
			$display_service->add_active_filters();
			$display_service->add_mobile_drawer();
			$display_service->add_sorting_bar();
			$mobile = ob_get_clean();

			// Inject both the display service tag and fallback elements.
			ob_start();
			$this->add_fallback_total_products( $products_ids );
			$this->add_fallback_product_ids( $products_ids );
			if ( Filters::is_taxonomy_page() ) {
				$this->add_fallback_url();
			}
			$fallback_elements = ob_get_clean();

			return $mobile . $fallback_elements . '<div class="wcf-elementor-products-wrapper">' . $tag . $content . $closing_tag . '</div>';
		}

		return $content;
	}

	/**
	 * If this is an elementor page and one of our
	 * supported elements has been found:
	 *
	 * - dequeue the default prefiller json so we can replace it.
	 * - disable our custom pagination template override.
	 *
	 * @return void
	 */
	public function catch_elementor_page() {

		// Disable elements for all pages when they haave the products widget.
		add_action(
			'wp',
			function() {
				if ( ! is_page() ) {
					return;
				}

				$page_id = get_queried_object_id();
				$widgets = $this->get_widgets_list( $page_id );

				if ( ! in_array( 'woocommerce-products', $widgets, true ) ) {
					return;
				}

				$this->disable_horizontal_elements();
				$this->disable_wp_query_wrapper();
				$this->disable_pagination_template_override();
			}
		);

		// Disable elements for archive pages when they have the archive widget.
		add_action(
			'wp',
			function() {
				if ( ! Filters::is_taxonomy_page() && ! is_shop() ) {
					return;
				}

				if ( ! $this->has_location() ) {
					return;
				}

				$template = $this->get_template();

				if ( empty( $template ) ) {
					return;
				}

				if ( count( $template ) > 1 ) {
					$this->disable_horizontal_elements();
					$this->disable_wp_query_wrapper();
					$this->disable_pagination_template_override();
					return;
				}

				$widgets = $this->get_widgets_list( key( $template ) );

				if ( ! in_array( 'wc-archive-products', $widgets, true ) ) {
					return;
				}

				$this->disable_horizontal_elements();
				$this->disable_wp_query_wrapper();
				$this->disable_pagination_template_override();
			}
		);
	}

	/**
	 * Disable our custom pagination template override.
	 *
	 * @return void
	 */
	public function disable_pagination_template_override() {
		$display = wcf()->get_service( 'display' );
		remove_action( 'wc_get_template', [ $display, 'filter_templates' ], 10 );
	}

	/**
	 * Get the list of widgets being used on an elementor page.
	 *
	 * @param string|int $page_id
	 * @return array
	 */
	public function get_widgets_list( $page_id ) {

		$widgets        = [];
		$elementor_data = get_post_meta( $page_id, '_elementor_data' );

		if ( empty( $elementor_data ) ) {
			return [];
		}

		$reg_exp      = '/"widgetType":"([^"]*)/i';
		$widgets_list = [];

		if ( ! preg_match_all( $reg_exp, $elementor_data[0], $widgets_list, PREG_SET_ORDER ) ) {
			return [];
		}

		foreach ( $widgets_list as $found ) {
			if ( ! isset( $found[1] ) ) {
				continue;
			}

			$widgets[] = sanitize_text_field( $found[1] );
		}

		return $widgets;
	}

	/**
	 * Add support for filtering to the elementor queries.
	 *
	 * @param \WP_Query $query
	 * @return void
	 */
	public function pre_get_posts( $query ) {
		// Ignore `Ele Custom Skin` single queries
		if ( $query->is_singular || true === $query->get( 'suppress_filters', false ) ) {
			$query->set( 'woocommerce-filters', false );
			$query->set( 'woocommerce-filters-elementor', false );
		}
		// A supported widget is in use
		elseif ( ! empty( $this->found_widget ) && 'done' !== $this->found_widget ) {
			// Elementor runs the product query twice, so intercept only the 2nd query
			if ( 'woocommerce-products' === $this->found_widget ) {
				$is_correct_query = ( $this->products_query_offset === $this->products_query_counter );
				$query->set( 'woocommerce-filters', $is_correct_query );
				$query->set( 'woocommerce-filters-elementor', $is_correct_query );
				++$this->products_query_counter;
			} else {
				$query->set( 'woocommerce-filters', true );
				$query->set( 'woocommerce-filters-elementor', true );
			}
		}
		// Ignore this single page if the Elementor widget is disabled
		elseif ( $this->has_location( 'single' ) && empty( $this->found_widget ) ) {
			$query->set( 'woocommerce-filters', false );
			$query->set( 'woocommerce-filters-elementor', false );
		}
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
		if ( ! $query->get( 'woocommerce-filters-prefilled' ) && ! $query->get( 'woocommerce-filters-elementor' ) ) {
			return;
		}

		$cache = new Query_Cache( $query, $orderby, $filters, $post_ids );
		$cache->set_cache_prefix( self::INTEGRATION_PREFIX );
		$cache->cache();

		$this->cache = $cache;
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

	/**
	 * Generate the queried term link which is used for firing ajax queries.
	 *
	 * @return void
	 */
	private function add_fallback_url() {
		$url = get_term_link( get_queried_object() );
		?>
		<div id="wcf-fallback-url" data-url="<?php echo esc_url( $url ); ?>"></div>
		<?php
	}

	/**
	 * Add count of total products as a hidden div.
	 *
	 * @param array $ids
	 * @return void
	 */
	private function add_fallback_total_products( $ids ) {
		?>
		<div id="wcf-fallback-products-count" data-count="<?php echo absint( count( $ids ) ); ?>"></div>
		<?php
	}

	/**
	 * Disable the horizontal elements automatically attached.
	 *
	 * @return void
	 */
	public function disable_horizontal_elements() {
		$display = wcf()->get_service( 'display' );
		remove_action( 'woocommerce_before_shop_loop', [ $display, 'add_mobile_drawer' ], 8 );
		remove_action( 'woocommerce_before_shop_loop', [ $display, 'add_shop_filters' ], 8 );
		remove_action( 'woocommerce_before_shop_loop', [ $display, 'add_sorting_bar' ], 9 );
	}

	/**
	 * Add list of ids of all products loaded.
	 *
	 * @param array $ids
	 * @return void
	 */
	private function add_fallback_product_ids( $ids ) {
		?>
		<div id="wcf-fallback-post-ids" data-ids="<?php echo esc_attr( implode( ',', $ids ) ); ?>"></div>
		<?php
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

		if ( isset( $_GET[ self::INTEGRATION_PREFIX ] ) && isset( $_GET['product-page'] ) ) {
			return;
		}

		add_filter( 'show_admin_bar', '__return_false' );
		add_action( 'shutdown', [ $this, 'inject_template' ], 0 );
		ob_start();
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

		$elementor_query = $this->cache->get_query();
		$found_posts     = count( $this->cache->get_post_ids() );
		$counts          = $this->cache->get_counts();
		$result_count    = $this->cache->generate_result_count( $elementor_query, $this->cache->get_post_ids() );
		$url_params      = $this->cache->prepare_url_params();
		$is_404          = $this->cache->is_404();
		$no_products_tpl = $this->cache->is_404() ? Responses::generate_no_products_template() : false;
		$orderby         = $this->cache->get_orderby();

		$this->cache->purge();

		if ( $is_404 ) {
			$found_posts = 0;
		}

		wp_send_json(
			[
				'output'          => Products::get_string_between( $html, '<!--wcf-loop-start-->', '<!--wcf-loop-end-->' ),
				'found_posts'     => $found_posts,
				'paged'           => empty( $elementor_query->get( 'paged' ) ) ? 1 : $elementor_query->get( 'paged' ),
				'posts_per_page'  => $elementor_query->get( 'posts_per_page' ),
				'offset'          => $elementor_query->get( 'offset' ),
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
	 * Check if the current page has a valid elementor template location.
	 *
	 * @param string $location
	 * @return boolean
	 */
	private function has_location( $location = 'archive' ) {
		if ( 'single' === $location && 0 == get_queried_object_id() ) {
			return false;
		}

		$manager   = \ElementorPro\Plugin::instance()->modules_manager->get_modules( 'theme-builder' )->get_conditions_manager();
		$documents = $manager->get_documents_for_location( $location );
		return ! empty( $documents );
	}

	/**
	 * Get the template associated with the current location.
	 *
	 * @param string $location
	 * @return mixed
	 */
	private function get_template( $location = 'archive' ) {
		if ( 'single' === $location && 0 == get_queried_object_id() ) {
			return false;
		}

		$manager   = \ElementorPro\Plugin::instance()->modules_manager->get_modules( 'theme-builder' )->get_conditions_manager();
		$documents = $manager->get_documents_for_location( $location );

		return $documents;
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
