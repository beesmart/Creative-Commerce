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
use Barn2\Plugin\WC_Filters\Model\Filters\Attribute;
use Barn2\Plugin\WC_Filters\Model\Filters\Taxonomy;
use Barn2\Plugin\WC_Filters\Model\Index;
use Barn2\Plugin\WC_Filters\Query_Cache;
use Barn2\Plugin\WC_Filters\Request_Fallback;
use Barn2\Plugin\WC_Filters\Utils\Products;
use Barn2\Plugin\WC_Filters\Utils\Responses;
use Barn2\Plugin\WC_Filters\Dependencies\Lib\Registerable;
use JsonSerializable;
use Barn2\Plugin\WC_Filters\Model\Filter;

use function Barn2\Plugin\WC_Filters\wcf;

/**
 * Integration class for the product table plugin.
 */
class Plugin_Product_Table implements Registerable, JsonSerializable {

	/**
	 * Holds the prefix used for identifying the query.
	 */
	const INTEGRATION_PREFIX = '_wpt';

	/**
	 * Misc settings from the WPT plugin.
	 *
	 * @var Collection
	 */
	public $settings;

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
		if ( ! function_exists( '\Barn2\Plugin\WC_Product_Table\wpt' ) ) {
			return;
		}
		$this->settings = new Collection( \Barn2\Plugin\WC_Product_Table\Util\Settings::get_setting_misc() );
		$this->init();
		$this->catch_table_page();
	}

	/**
	 * Hook into WP.
	 *
	 * @return void
	 */
	public function init() {
		add_filter( 'wc_product_table_query_args', [ $this, 'add_argument_to_query' ] );
		add_filter( 'wc_product_table_get_table_output', [ $this, 'table_output' ], 10, 3 );
		add_action( 'wp_enqueue_scripts', [ $this, 'assets' ], 20 );
		add_action( 'pre_get_posts', [ $this, 'pre_get_posts' ] );
		add_action( 'wcf_prefilled_query', [ $this, 'cache_query' ], 10, 4 );

		if ( ! class_exists( '\Iconic_WSSV' ) ) {
			add_action( 'wcf_after_product_index', [ $this, 'index_variations' ], 20, 4 );
			add_filter( 'wcf_pricing_api_products_ids', [ $this, 'adjust_pricing_api' ], 10 );
			add_action( 'wcf_after_product_delete', [ $this, 'after_delete_product' ], 10 );
		}

		add_filter( 'wc_product_table_separate_available_variations', [ $this, 'adjust_children' ], 10, 3 );

		$this->intercept();
	}

	/**
	 * Make sure to filter only variations.
	 *
	 * @param array $children
	 * @param object $product
	 * @param object $table instance of the table
	 * @return array
	 */
	public function adjust_children( $children, $product, $table ) {

		$variations_visible = $table->args->variations === 'separate';

		if ( ! $this->cache instanceof Query_Cache || ! $variations_visible ) {
			return $children;
		}

		$filters          = $this->cache->get_filters();
		$filters_ids      = $filters->isNotEmpty() ? $filters->pluck( 'id' )->toArray() : [];
		$indexed_products = [];
		$children_ids     = [];
		$found_children   = [];
		$posts_by_filter  = [];

		if ( empty( $filters_ids ) ) {
			return $children;
		}

		foreach ( $filters as $filter ) {
			$found                               = $filter->find_posts();
			$indexed_products                    = array_merge( $indexed_products, $found );
			$posts_by_filter[ $filter->getID() ] = $found;
		}

		foreach ( $children as $child ) {
			$children_ids[] = $child->get_id();
		}

		if ( ! empty( $children_ids ) && ! empty( $indexed_products ) ) {
			$matching_results = array_intersect( $children_ids, $indexed_products );

			if ( ! empty( $matching_results ) ) {
				foreach ( $children as $matched ) {
					if ( in_array( $matched->get_id(), $matching_results ) ) {
						$found_children[] = $matched;
					}
				}

				if ( ! empty( $found_children ) ) {
					return $found_children;
				}
			}
		}

		return $children;
	}

	/**
	 * Load the assets specific to this integration.
	 *
	 * @return void
	 */
	public function assets() {

		if ( ! $this->should_enqueue() ) {
			return;
		}

		$file_name = 'wcf-product-table';

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
		$integration_script_asset['dependencies'][] = 'wc-product-table';

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
	 * Determine if the assets should be loaded.
	 *
	 * @return boolean
	 */
	private function should_enqueue() {
		$enabled              = false;
		$shop_override        = $this->settings->get( 'shop_override' ) === true;
		$archive_override     = $this->settings->get( 'archive_override' ) === true;
		$search_override      = $this->settings->get( 'search_override' ) === true;
		$product_tag_override = $this->settings->get( 'product_tag_override' ) === true;
		$attribute_override   = $this->settings->get( 'attribute_override' ) === true;

		$queried_object = get_queried_object();

		global $post;

		// This could be made a 1-3 liner but I think it's more readable this way.
		if ( $shop_override && is_shop() ) {
			$enabled = true;
		} elseif ( $archive_override && is_product_category() ) {
			$enabled = true;
		} elseif ( $search_override && is_search() ) {
			$enabled = true;
		} elseif ( $product_tag_override && is_product_tag() ) {
			$enabled = true;
		} elseif ( $attribute_override && isset( $queried_object->taxonomy ) && ! empty( $queried_object->taxonomy ) && taxonomy_is_product_attribute( $queried_object->taxonomy ) ) {
			$enabled = true;
		} elseif ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'product_table' ) ) {
			$enabled = true;
		}

		return $enabled;
	}

	/**
	 * If this is a product table page.
	 *
	 * - disable our custom pagination template override.
	 *
	 * @return void
	 */
	public function catch_table_page() {
		add_action(
			'wp',
			function() {
				if ( ! is_page() ) {
					return;
				}

				global $post;

				if ( has_shortcode( $post->post_content, 'product_table' ) ) {
					$this->disable_wp_query_wrapper();
				}
			}
		);
	}

	/**
	 * Disable parent terms restriction on table.
	 *
	 * @return void
	 */
	private function disable_parent_terms_restriction() {
		add_filter(
			'wcf_taxonomy_json_terms_args',
			function( $args, $filter ) {

				if ( isset( $args['parent'] ) ) {
					unset( $args['parent'] );
				}

				return $args;
			},
			20,
			2
		);
	}

	/**
	 * If the table has specific restrictions like categories.
	 * Remove those terms from the list of terms of a filter.
	 *
	 * @param object $table
	 * @return array
	 */
	private function adjust_available_terms( $table ) {

		$categories = preg_split( '/[+,]/', $table->args->category ) ?? [];
		$tags       = preg_split( '/[+,]/', $table->args->tag ) ?? [];
		$taxonomies = empty( $table->args->term ) ? [] : $table->args->term;

		if ( ! empty( $taxonomies ) ) {
			$taxonomies = substr( strstr( $taxonomies, ':' ), strlen( ':' ) );

			if ( ! empty( $taxonomies ) ) {
				$taxonomies = preg_split( '/[+,]/', $taxonomies ) ?? [];
			}
		}

		$restricted_to = array_merge(
			$categories,
			$tags,
			$taxonomies
		);

		add_filter(
			'wcf_taxonomy_filter_terms_list',
			function( $terms, $filter ) use ( $table, $categories, $restricted_to ) {
				if ( $filter instanceof Taxonomy ) {
					foreach ( $terms as $index => $term ) {
						if ( $term instanceof \WP_Term ) {
							if ( isset( $term->slug ) && in_array( $term->slug, $restricted_to, true ) ) {
								unset( $terms[ $index ] );
							}
						} else {
							if ( isset( $term['slug'] ) && in_array( $term['slug'], $restricted_to, true ) ) {
								unset( $terms[ $index ] );
							}
						}
					}
				}
				return $terms;
			},
			20,
			2
		);

		add_filter(
			'wcf_taxonomy_filter_attribute_terms_list',
			function( $terms, $filter ) use ( $table, $categories, $restricted_to ) {
				if ( $filter instanceof Attribute ) {
					foreach ( $terms as $index => $term ) {
						if ( $term instanceof \WP_Term ) {
							if ( isset( $term->slug ) && in_array( $term->slug, $restricted_to, true ) ) {
								unset( $terms[ $index ] );
							}
						} else {
							if ( isset( $term['slug'] ) && in_array( $term['slug'], $restricted_to, true ) ) {
								unset( $terms[ $index ] );
							}
						}
					}
				}
				return $terms;
			},
			20,
			2
		);
	}

	/**
	 * Adjust output of the table and include our custom elements.
	 *
	 * @param string $result
	 * @param string $output
	 * @param object $table
	 * @return string
	 */
	public function table_output( $result, $output, $table ) {

		$this->disable_parent_terms_restriction();
		$this->adjust_available_terms( $table );

		if ( $output === 'html' ) {
			$display_service = wcf()->get_service( 'display' );

			ob_start();
			$display_service->add_template_tag( true );
			$tag = ob_get_clean();

			ob_start();
			$display_service->add_closing_template_tag( true );
			$closing_tag = ob_get_clean();

			$fallback_total = $this->add_fallback_total_products( $table->query );
			$fallback_ids   = $this->add_fallback_product_ids( $table->query );

			ob_start();
			if ( ! is_shop() ) {
				$display_service->add_mobile_drawer();
				$display_service->add_active_filters();
				$display_service->add_sorting_bar( true );
			}
			$mobile = ob_get_clean();

			return $mobile . $fallback_ids . $fallback_total . '<div class="wcf-wpt-table-wrapper">' . $tag . $result . $closing_tag . '</div>';
		}

		return $result;
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
	 * Add count of total products as a hidden div.
	 *
	 * @param object $query
	 * @return string
	 */
	private function add_fallback_total_products( $query ) {
		ob_start();
		?>
		<div id="wcf-fallback-products-count" data-count="<?php echo absint( count( $query->get_products() ) ); ?>"></div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Add list of ids of all products loaded.
	 *
	 * @param object $query
	 * @return string
	 */
	private function add_fallback_product_ids( $query ) {
		$ids = [];

		if ( ! empty( $query->get_products() ) ) {
			foreach ( $query->get_products() as $product ) {
				$ids[] = $product->get_id();

				if ( $product->is_type( 'variable' ) ) {
					$ids = array_merge( $ids, $product->get_children() );
				}
			}
		}
		ob_start();
		?>
		<div id="wcf-fallback-post-ids" data-ids="<?php echo esc_attr( implode( ',', $ids ) ); ?>"></div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Add the filtering flag to the queries.
	 *
	 * @param array $args
	 * @return array
	 */
	public function add_argument_to_query( $args ) {
		$args['woocommerce-filters']     = true;
		$args['woocommerce-filters-wpt'] = true;

		return $args;
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
		if ( ! $query->get( 'woocommerce-filters-prefilled' ) && ! $query->get( 'woocommerce-filters-wpt' ) ) {
			return;
		}

		$cache = new Query_Cache( $query, $orderby, $filters, $post_ids );
		$cache->set_cache_prefix( self::INTEGRATION_PREFIX );
		$cache->cache();

		$this->cache = $cache;
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

		$wpt_query       = $this->cache->get_query();
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
				'paged'           => empty( $wpt_query->get( 'paged' ) ) ? 1 : $wpt_query->get( 'paged' ),
				'posts_per_page'  => $wpt_query->get( 'posts_per_page' ),
				'offset'          => $wpt_query->get( 'offset' ),
				'counts'          => $counts,
				'result_count'    => $result_count,
				'url_params'      => $url_params,
				'is_404'          => $is_404,
				'no_products_tpl' => $no_products_tpl,
				'orderby'         => $orderby,
				'reset'           => false,
				'ids'             => $this->cache->get_post_ids(),
			]
		);
	}

	/**
	 * Detect if an ajax request is taking place.
	 *
	 * Filtering of the query only takes place when
	 * the WPT Table is triggering an ajax request.
	 *
	 * @param \WP_Query $query
	 * @return void
	 */
	public function pre_get_posts( $query ) {

		if ( $query->get( 'woocommerce-filters-wpt' ) === true ) {

			// Do not filter the WPT query if we're not within an ajax request.
			if ( ! defined( 'DOING_AJAX' ) || defined( 'DOING_AJAX' ) && DOING_AJAX !== true ) {
				return;
			}

			$prefilled = isset( $_POST['wpt_search'] ) && ! empty( $_POST['wpt_search'] ) ? $_POST['wpt_search'] : false;
			$filters   = isset( $_POST['woocommerceFilters'] ) && ! empty( $_POST['woocommerceFilters'] ) ? $this->prepare_filters_collection( wc_clean( $_POST['woocommerceFilters'] ) ) : false;
			$orderby   = isset( $_POST['woocommerceOrderBy'] ) && ! empty( $_POST['woocommerceOrderBy'] ) ? sanitize_text_field( $_POST['woocommerceOrderBy'] ) : false;

			if ( empty( $filters ) && empty( $orderby ) && empty( $prefilled ) ) {
				return;
			}

			$did_prefill = false;

			$fallback_handler = new Request_Fallback();

			if ( ! empty( $prefilled ) && empty( $filters ) ) {
				$collection = $fallback_handler->parse_request( $prefilled );

				if ( $collection instanceof Collection ) {
					$fallback_handler->set_parameters( $collection );
					$did_prefill = true;
				}
			} elseif ( ! empty( $filters ) ) {
				$fallback_handler->set_parameters( $filters );
			}

			if ( ! empty( $filters ) || ! empty( $prefilled ) ) {
				$fallback_handler->load_filters();

				$fallback_handler->update_query_vars(
					$query,
					[
						'woocommerce-filters-wpt'    => false,
						'woocommerce-filters-bypass' => false
					]
				);
			}

			// Sort the results if needed.
			if ( ! empty( $orderby ) ) {
				$fallback_handler->set_orderby( $orderby );
				$fallback_handler->maybe_order_results( $query );
			}

			add_filter(
				'wc_product_table_ajax_response',
				function( $output ) use ( $fallback_handler, $did_prefill ) {

					$products = $fallback_handler->get_found_post_ids();

					if ( ! empty( $products ) ) {
						$output['recordsFiltered'] = count( $products );
						$output['recordsTotal']    = count( $products );
						$output['productsList']    = $fallback_handler->get_found_post_ids();
						$output['activeFilters']   = $fallback_handler->get_active_filters();
						$output['didPrefill']      = $did_prefill;
					}

					return $output;
				}
			);

		}
	}

	/**
	 * Build a collection of all the parameters sent through the ajax request.
	 *
	 * @param array $groups
	 * @return Collection
	 */
	private function prepare_filters_collection( array $groups ) {

		$parameters = [];

		foreach ( $groups as $group_id => $filters ) {
			$parameters = array_merge( $filters, $parameters );
		}

		return new Collection( $parameters );
	}

	/**
	 * Index variations of a product.
	 *
	 * @param string|int $post_id the id of the product
	 * @param object $product instance of a WC product
	 * @param array $filters collection of filters for which the index is being generated
	 * @param Indexer $indexer instance of the indexer
	 */
	public function index_variations( $product_id, $product, $filters, $indexer ) {
		// Abort if not a variable product.
		if ( ! $product->is_type( 'variable' ) ) {
			return;
		}

		$variations = $product->get_children();

		foreach ( $variations as $i => $variation_id ) {
			$indexer->index_post( $variation_id, $filters );
		}

		if ( $product->get_catalog_visibility() === 'hidden' ) {
			Index::whereIn( 'post_id', [ $product->get_id() ] )->delete();
		}
	}

	/**
	 * Inject variations ids into the list of products
	 * that is loaded when checking the max price.
	 *
	 * @param array $ids
	 * @return array
	 */
	public function adjust_pricing_api( $ids ) {

		$new_ids = [];

		if ( is_array( $ids ) && ! empty( $ids ) ) {
			foreach ( $ids as $product_id ) {

				$product   = wc_get_product( $product_id );
				$new_ids[] = $product_id;

				if ( ! $product->is_type( 'variable' ) ) {
					continue;
				}

				$variations = $product->get_children();

				if ( ! empty( $variations ) && is_array( $variations ) ) {
					$new_ids = array_merge( $new_ids, $variations );
				}
			}
		}

		return $new_ids;
	}

	/**
	 * Automatically delete variations data when the product is deleted.
	 *
	 * @param string|int $product_id
	 * @return void
	 */
	public function after_delete_product( $product_id ) {

		$product = wc_get_product( $product_id );

		if ( ! $product->is_type( 'variable' ) ) {
			return;
		}

		$variations = $product->get_children();

		if ( is_array( $variations ) && ! empty( $variations ) ) {
			Index::whereIn( 'post_id', $variations )->delete();
		}
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
