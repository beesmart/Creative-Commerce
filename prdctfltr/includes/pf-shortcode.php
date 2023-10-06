<?php

	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	class XforWC_Product_Filters_Shortcodes_AJAX_Fix {
		function in_admin() {
			return false;
		}
	}

	class XforWC_Product_Filters_Shortcodes {

		public static $settings;

		public static function init() {
			$class = __CLASS__;
			new $class;
		}

		function __construct() {
			add_shortcode( 'prdctfltr_sc_products', __CLASS__ . '::prdctfltr_sc_products_700' );
			add_shortcode( 'prdctfltr_sc_get_filter', __CLASS__ . '::prdctfltr_sc_get_filter' );
			add_shortcode( 'products_finder', __CLASS__ . '::get_finder' );

			add_action( 'woocommerce_before_subcategory', __CLASS__. '::add_category_support', 10, 1 );
			add_action( 'wp_ajax_nopriv_prdctfltr_respond_550', __CLASS__ . '::prdctfltr_respond_550' );
			add_action( 'wp_ajax_prdctfltr_respond_550', __CLASS__ . '::prdctfltr_respond_550' );

			$shortcodes = array(
				'products',
				'recent_products',
				'sale_products',
				'best_selling_products',
				'top_rated_products',
				'featured_products',
				'product_cat',
				'product_category',
				'product_attribute'
			);

			foreach( $shortcodes as $shortcode ) {
				add_action( 'woocommerce_shortcode_before_' . $shortcode . '_loop', __CLASS__ . '::add_wcsc_filter', 10, 1 );
				add_action( 'woocommerce_shortcode_after_' . $shortcode . '_loop', __CLASS__ . '::after_wcsc_filter', 999, 1 );
				add_action( 'woocommerce_shortcode_' . $shortcode . '_loop_no_results', __CLASS__ . '::noresults_wcsc_filter', 10, 1 );
				add_filter( 'shortcode_atts_' . $shortcode, __CLASS__ . '::extend_atts', 10, 4 );
			}

		}

		public static function after_wcsc_filter( $atts ) {
			if ( !empty( $atts['prdctfltr'] ) ) {
				echo '</div>';

				self::_reset_wcsc_loop();
			}
		}


		public static function _reset_wcsc_loop() {
			
			remove_filter( 'the_posts', 'XforWC_Product_Filters_Shortcodes::get_wp_query' );
			remove_filter( 'loop_start', 'XforWC_Product_Filters_Shortcodes::get_wp_query' );
			remove_filter( 'pre_get_posts', 'XforWC_Product_Filters_Frontend::sc_wc_query' );
			remove_filter( 'parse_tax_query', 'XforWC_Product_Filters_Frontend::sc_wc_tax' );
			remove_filter( 'woocommerce_shortcode_products_query', 'XforWC_Product_Filters_Shortcodes::fix_wcsc_parameters', 9999, 3 );
			self::$settings['wcsc_products'] = null;
			self::$settings['instance'] = null;

			global $prdctfltr_global;
			
			$prdctfltr_global['sc_init'] = true;
			$prdctfltr_global['sc_products'] = true;
			$prdctfltr_global['preset'] = null;
			$prdctfltr_global['action'] = null;
		
		}
		
		public static function get_wp_query_320( $posts, $query ) {

			global $wp_query;
			if ( wp_doing_ajax() ) {
				$wp_query = $query;
			}

			if ( wp_doing_ajax() ) {
				$paged = self::$settings['opt']['pf_paged'];
			}
			else {
				$paged =  max( 1, $query->get( 'paged' ) );
			}

			$found = $query->found_posts;
			$per_page = $query->get( 'posts_per_page' ) == -1 ? $found : $query->get( 'posts_per_page' );

			XforWC_Product_Filters_Frontend::$options['sc_instance'] = array(
				'paged'			=> $paged,
				'per_page'		=> $per_page,
				'total'			=> $found,
				'max_num_pages' => isset( $query->max_num_pages ) && $query->max_num_pages>0 ? $query->max_num_pages : ceil( $found/$per_page ),
				'first'			=> ( $per_page * $paged ) - $per_page + 1,
				'last'			=> min( $found, $per_page * $paged ),
				'request'		=> $query->request
			);

			self::$settings['instance'] = $query;

			return $posts;

		}

		public static function get_wp_query( $query ) {

			global $wp_query;
			if ( wp_doing_ajax() ) {
				$wp_query = $query;
			}

			if ( wp_doing_ajax() ) {
				$paged = self::$settings['opt']['pf_paged'];
			}
			else {
				$paged =  max( 1, $query->get( 'paged' ) );
			}

			$found = $query->found_posts;
			$per_page = $query->get( 'posts_per_page' ) == -1 ? $found : $query->get( 'posts_per_page' );

			XforWC_Product_Filters_Frontend::$options['sc_instance'] = array(
				'paged'			=> $paged,
				'per_page'		=> $per_page,
				'total'			=> $found,
				'max_num_pages' => isset( $query->max_num_pages ) && $query->max_num_pages>0 ? $query->max_num_pages : ceil( $found/$per_page ),
				'first'			=> ( $per_page * $paged ) - $per_page + 1,
				'last'			=> min( $found, $per_page * $paged ),
				'request'		=> $query->request
			);

		}

		public static function add_wcsc_filter( $atts ) {
			if ( !empty( $atts['prdctfltr'] ) ) {
				global $prdctfltr_global;

				$prdctfltr_global['unique_id'] = uniqid( 'prdctfltr-' );
				$prdctfltr_global['preset'] = ( isset( $atts['preset'] ) ? $atts['preset'] : '' );

				$prdctfltr_global['pagefilters'][$prdctfltr_global['unique_id']]['wcsc'] = true;
				$prdctfltr_global['pagefilters'][$prdctfltr_global['unique_id']]['loop_name'] = self::$settings['wcsc']['loop_name'];
				$prdctfltr_global['pagefilters'][$prdctfltr_global['unique_id']]['query_args'] = self::$settings['wcsc']['query_args'];
				$prdctfltr_global['pagefilters'][$prdctfltr_global['unique_id']]['atts'] = self::$settings['wcsc']['atts'];

				if ( !isset( self::$settings['sc'] ) ) {
					self::$settings['sc'] = $prdctfltr_global['pagefilters'][$prdctfltr_global['unique_id']];
				}

				$classes = array(
					'prdctfltr_sc',
					'prdctfltr_sc_products',
					'prdctfltr_wcsc',
				);
				
				if ( isset( $atts['ajax'] ) ) {
					$classes[] = 'prdctfltr_ajax';
				}

				if ( self::$settings['wcsc_products'] == 'aside' ) {
					$classes[] = 'prdctfltr_aside';
				}
?>
				<div class="<?php esc_attr_e( implode( ' ', $classes ) ); ?>" data-id="<?php esc_attr_e( $prdctfltr_global['unique_id'] ); ?>">
<?php
				if ( !isset( self::$settings['instance'] ) ) {
					self::set_help_wp_query( $atts );
				}

				if ( !wp_doing_ajax() ) {
					if ( self::$settings['wcsc_products'] == 'yes' ) {
						include( XforWC_Product_Filters_Frontend::$dir . 'templates/product-filter.php' );
					} else if ( self::$settings['wcsc_products'] == 'aside' ) {
						$_title = array(
							'<h2 class="widget-title">',
							'</h2>'
						);

						the_widget( 'prdctfltr', array(
								'preset' => 'pf_inherit',
								'template' => $prdctfltr_global['preset'],
								'disable_overrides' => '',
								'id' => '',
								'class' => '',
							), array( 'before_title' => stripslashes( $_title[0] ), 'after_title' => stripslashes( $_title[1] ) )
						);
					}
				}

				self::reset_help_wp_query();
			}
		}

		public static function reset_help_wp_query() {
			if ( isset( self::$settings['hlp_wp_query'] ) ) {
				global $wp_query;
				$wp_query = self::$settings['hlp_wp_query'];
				unset( self::$settings['hlp_wp_query'] );
			}
		}

		public static function set_help_wp_query( $atts ) {
			global $wp_query;
			self::$settings['hlp_wp_query'] = $wp_query;
			$wp_query = new WP_Query( self::$settings['wcsc']['query_args'] );
			self::get_wp_query_320( array(), $wp_query );
		}
		
		public static function noresults_wcsc_filter( $atts ) {
			if ( !empty( $atts['prdctfltr'] ) ) {

				self::add_wcsc_filter( $atts );

				//echo '<div class="prdctfltr_sc prdctfltr_sc_products prdctfltr_wcsc' . ( isset( $atts['ajax'] ) ? ' prdctfltr_ajax' : '' ) . '" data-id="' . esc_attr( $prdctfltr_global['unique_id'] ) . '">';

				wc_get_template( 'loop/loop-start.php' );

				do_action( 'woocommerce_no_products_found' );

				wc_get_template( 'loop/loop-end.php' );

				//echo '</div>';

				self::_reset_wcsc_loop();

			}
		}

		public static function fix_wcsc_parameters( $query_args, $atts, $loop_name ) {

			if ( !empty( $atts['prdctfltr'] ) ) {

				global $prdctfltr_global;

				$query_args['no_found_rows'] = 0;
				$query_args['paged'] = isset( self::$settings['opt']['pf_paged'] ) && self::$settings['opt']['pf_paged'] > 1 ? self::$settings['opt']['pf_paged'] : get_query_var( 'paged' );

				if ( $query_args['paged']<2 && wp_doing_ajax() ) {
					$query_args['offset'] = self::$settings['opt']['pf_offset'];
				}

				$query_args['prdctfltr_active'] = true;

				$prdctfltr_global['sc_init'] = true;
				$prdctfltr_global['sc_products'] = true;

				self::$settings['wcsc']['query_args'] = $query_args;
				self::$settings['wcsc']['atts'] = $atts;
				self::$settings['wcsc']['loop_name'] = $loop_name;

			}

			return $query_args;

		}

		public static function add_wcsc_pagination( $attributes ) {

			global $wp_query;

			if ( wp_doing_ajax() ) {
				self::$settings['paginationExport'] = self::get_pagination( self::$settings['paginationExport'] );
			}
			else {
				if ( isset( self::$settings['paginationExport'] ) ) {
					echo self::get_pagination( self::$settings['paginationExport'] );
					unset( self::$settings['paginationExport'] );
				}
			}

		}

		public static function extend_atts( $out, $pairs, $atts, $shortcode ) {

			if ( !empty( $atts['prdctfltr'] ) && in_array( $atts['prdctfltr'], array( 'yes', 'widget', 'aside' ) ) ) {
				$out['prdctfltr'] = $atts['prdctfltr'];
				$out['cache'] = false;

				self::$settings['wcsc_products'] = $atts['prdctfltr'];
				
				$out['ajax'] = isset( $atts['ajax'] ) && $atts['ajax'] == 'yes' ? 'yes' : null;
				$out['preset'] = isset( $atts['preset'] ) && $atts['preset'] !== '' ? $atts['preset'] : null;
				
				self::$settings['wcsc']['shortcode'] = $shortcode;

				if ( isset( $atts['paginate'] ) && $atts['paginate'] == 'yes' ) {
					//$out['paginate'] = 'no';
					$atts['pagination'] = 'yes';
					remove_action( 'woocommerce_after_shop_loop', 'woocommerce_pagination' );
				}

				$out['pagination'] = isset( $atts['pagination'] ) && $atts['pagination'] !== '' ? $atts['pagination'] : null;

				if ( $out['pagination'] ) {
					self::$settings['paginationExport'] = array(
						'sc' => 'yes',
						'ajax' => $out['ajax'],
						'type' => $out['pagination'],
					);

					add_action( 'woocommerce_shortcode_after_' . $shortcode . '_loop', __CLASS__ . '::add_wcsc_pagination', 998, 1 );
				}

				if ( !wp_doing_ajax() ) {
					global $prdctfltr_global;
					if ( !isset( $prdctfltr_global['done_filters'] ) ) {
						XforWC_Product_Filters_Frontend::make_global( $_REQUEST, 'FALSE' );
					}
				}

				add_filter( 'found_posts', 'XforWC_Product_Filters_Shortcodes::get_wp_query_320', 999, 2 );

				add_filter( 'pre_get_posts', 'XforWC_Product_Filters_Frontend::sc_wc_query' );
				add_filter( 'parse_tax_query', 'XforWC_Product_Filters_Frontend::sc_wc_tax' );

				add_filter( 'woocommerce_shortcode_products_query', __CLASS__ . '::fix_wcsc_parameters', 9999, 3 );

			}
			else {
				$atts['prdctfltr'] = null;
			}

			return $out;

		}

		public static function add_category_support( $category ) {

			echo '<span class="prdctfltr_cat_support" style="display:none!important;" data-slug="' . esc_attr( $category->slug ) . '"></span>';

		}

		public static function get_categories() {

			global $wp_query, $prdctfltr_global;

			$defaults = array(
				'before'        => '',
				'after'         => '',
				'force_display' => false
			);

			$args = array();

			$args = wp_parse_args( $args, $defaults );

			extract( $args );

			$selected_term = isset( $prdctfltr_global['active_filters']['product_cat'][0] ) ? $prdctfltr_global['active_filters']['product_cat'][0] : '';
			if ( $selected_term == '' ) {
				$selected_term = isset( $prdctfltr_global['active_permalinks']['product_cat'][0] ) ? $prdctfltr_global['active_permalinks']['product_cat'][0] : '';
			}

			if ( $selected_term !== '' ) {

				if ( term_exists( $selected_term, 'product_cat' ) ) {

					$term = get_term_by( 'slug', $selected_term, 'product_cat' );

				}

			}

			if ( !isset( $term ) ) {

				$term = (object) array( 'term_id' => 0 );

			}

			$parent_id = ( $term->term_id == 0 ? 0 : $term->term_id );

			$product_categories = get_categories( apply_filters( 'woocommerce_product_subcategories_args', array(
				'parent'       => $parent_id,
				'menu_order'   => 'ASC',
				'hide_empty'   => 1,
				'hierarchical' => 1,
				'taxonomy'     => 'product_cat',
				'pad_counts'   => 1
			) ) );

			if ( $product_categories ) {

				echo wp_kses_post( $before );

				foreach ( $product_categories as $category ) {
					wc_get_template( 'content-product_cat.php', array(
						'category' => $category
					) );
				}

				if ( $term->term_id !== 0 ) {

					$display_type = get_term_meta( $term->term_id, 'display_type', true );

					switch ( $display_type ) {

						case 'subcategories' :
							$wp_query->post_count    = 0;
							$wp_query->max_num_pages = 0;
						break;

						case '' :
						default :
							if ( get_option( 'woocommerce_category_archive_display' ) == 'subcategories' ) {
								$wp_query->post_count    = 0;
								$wp_query->max_num_pages = 0;
							}
						break;

					}

				}

				if ( $term->term_id == 0 && get_option( 'woocommerce_shop_page_display' ) == 'subcategories' ) {
					$wp_query->post_count    = 0;
					$wp_query->max_num_pages = 0;
				}

				echo wp_kses_post( $after );

				return true;

			}

		}

		public static function prdctfltr_sc_products_700( $atts, $content = null ) {

			$atts = shortcode_atts( array(
				'preset' => '',
				'rows' => 4,
				'columns' => 4,
				'fallback_css' => 'no',
				'ajax' => 'no',
				'pagination' => 'yes',
				'use_filter' => 'yes',
				'show_categories' => 'no',
				'show_products' => 'yes',
				'min_price' => '',
				'max_price' => '',
				'orderby' => '',
				'order' => '',
				'product_cat'=> '',
				'product_tag'=> '',
				'product_characteristics'=> '',
				'operator' => 'IN',
				'sale_products' => '',
				'instock_products' => '',
				'http_query' => '',
				'disable_overrides' => 'yes',
				'action' => '',
				'show_loop_title' => '',
				'show_loop_price' => '',
				'show_loop_rating' => '',
				'show_loop_add_to_cart' => '',
				'bot_margin' => 36,
				'class' => '',
				'shortcode_id' => ''
			), $atts, 'prdctfltr_sc_products' );

			if ( !isset( self::$settings['opt'] ) ) {

				$paged = isset( $_REQUEST['paged'] ) ? intval( $_REQUEST['paged'] ) : get_query_var( 'paged' );

				if ( $paged < 1 ) {
					$paged = 1;
				}

				$opt = array(
					'pf_request' => array(),
					'pf_requested' => array(),
					'pf_filters' => array(),
					'pf_widget_title' => null,
					'pf_set' => 'shortcode',
					'pf_paged' => $paged,
					'pf_pagefilters' => array(),
					'pf_shortcode' => '',
					'pf_offset' => 0,
					'pf_restrict' => '',
					'pf_adds' => array(),
					'pf_orderby_template' => null,
					'pf_count_template' => null
				);

				self::$settings['opt'] = $opt;
			}
			else {
				$paged = self::$settings['opt']['pf_paged'];
			}

			$ordering_args = array( 'orderby' => $atts['orderby'], 'order' => $atts['order'] );
			$meta_query    = WC()->query->get_meta_query();
			$query_args    = array(
				'post_type'           => 'product',
				'post_status'         => 'publish',
				'ignore_sticky_posts' => 1,
				'orderby'             => $ordering_args['orderby'],
				'order'               => $ordering_args['order'],
				'posts_per_page'      => $atts['columns']*$atts['rows'],
				'meta_query'          => $meta_query,
				'tax_query'           => WC()->query->get_tax_query(),
				'paged'               => $paged
			);

			$taxonomies = array_filter( array(
				'product_cat' => $atts['product_cat'],
				'product_tag' => $atts['product_tag'],
				'characteristics' => $atts['product_characteristics']
			) );

			$query_args = self::add_taxonomy_args( $query_args, $taxonomies, $atts['operator'] );

			if ( !empty( $atts['min_price'] ) ) {
				$query_args['min_price'] = $atts['min_price'];
			}

			if ( !empty( $atts['max_price'] ) ) {
				$query_args['max_price'] = $atts['max_price'];
			}

			if ( !empty( $atts['sale_products'] ) && $atts['sale_products'] == 'on' ) {
				$query_args['sale_products'] = 'on';
			}

			if ( !empty( $atts['instock_products'] ) && in_array( $atts['instock_products'], array( 'in', 'out', 'both' ) ) ) {
				self::$settings['sc_instock'] = $atts['instock_products'];
				$query_args['instock_products'] = $atts['instock_products'];
			}

			if ( !empty( $atts['http_query'] ) ) {
				parse_str( html_entity_decode( $atts['http_query'] ), $httpQuery );
				$query_args = array_merge( $query_args, $httpQuery );
			}

			if ( isset( $ordering_args['meta_key'] ) ) {
				$query_args['meta_key'] = $ordering_args['meta_key'];
			}

			global $prdctfltr_global;

			if ( !wp_doing_ajax() /*&& !isset( $prdctfltr_global['done_filters'] )*/ ) {
				XforWC_Product_Filters_Frontend::make_global( $_REQUEST, 'FALSE' );
				if ( isset( self::$settings['sc_instock'] ) ) {
					unset( self::$settings['sc_instock'] );
				}
			}
			else if ( isset( self::$settings['sc_ajax_filters'] ) ) {
				XforWC_Product_Filters_Frontend::make_global( self::$settings['sc_ajax_filters'], 'AJAX' );
			}

			$prdctfltr_global['sc_init'] = true;
			$prdctfltr_global['sc_products'] = true;
			$prdctfltr_global['unique_id'] = uniqid( 'prdctfltr-' );
			$prdctfltr_global['action'] = ( $atts['action'] !== '' ? $atts['action'] : '' );
			$prdctfltr_global['preset'] = ( $atts['preset'] !== '' ? $atts['preset'] : '' );
			$prdctfltr_global['disable_overrides'] = ( $atts['disable_overrides'] == 'yes' ? 'yes' : 'no' );

			if ( $atts['ajax'] == 'yes' ) {
				$add_ajax = ' data-page="' . esc_attr( $paged ) . '"'; // OK
			}

			$prdctfltr_global['pagefilters'][$prdctfltr_global['unique_id']]['sc'] = true;
			$prdctfltr_global['pagefilters'][$prdctfltr_global['unique_id']]['query_args'] = $query_args;
			$prdctfltr_global['pagefilters'][$prdctfltr_global['unique_id']]['atts'] = $atts;

			$prdctfltr_global['pagefilters'][$prdctfltr_global['unique_id']]['atts']['per_page'] = $atts['columns'] * $atts['rows'];

			if ( $atts['show_products'] == 'no' ) {
				$prdctfltr_global['step_filter'] = true;
				XforWC_Product_Filters_Frontend::$options['step_filter'] = true;
			}
			else {
				if ( !isset( self::$settings['sc'] ) ) {
					self::$settings['sc'] = $prdctfltr_global['pagefilters'][$prdctfltr_global['unique_id']];
				}
			}

			$query = self::make_query_700();

			if ( wp_doing_ajax() ) {
				global $wp_query;
				$wp_query = $query;
			}

			$cached = '';
			if ( !wp_doing_ajax() && $atts['use_filter'] == 'yes' ) {
				ob_start();
				include( XforWC_Product_Filters_Frontend::$dir . 'templates/product-filter.php' );
				$cached = ob_get_clean();
			}

			$cache_products = '';
			$cache_pagination = '';
			if ( $atts['show_products'] !== 'no' ) {
				$cache_products = self::get_products( $query );
				$pagination_args = array(
					'sc' => 'yes',
					'ajax' => $atts['ajax'],
					'type' => $atts['pagination']
				);
				if ( wp_doing_ajax() ) {
					self::$settings['paginationExport'] = self::get_pagination( $pagination_args );
				}
				else {
					$cache_pagination = self::get_pagination( $pagination_args );
				}
			}

			if ( !wp_doing_ajax() ) {
				wp_reset_query();
				wp_reset_postdata();
				$prdctfltr_global['unique_id'] = null;
				$prdctfltr_global['sc_init'] = null;
				$prdctfltr_global['sc_products'] = null;
				$prdctfltr_global['step_filter'] = null;
				$prdctfltr_global['preset'] = null;
				$prdctfltr_global['action'] = null;
				XforWC_Product_Filters_Frontend::$settings['maxheight'] = null;
			}


			$bot_margin = ( int ) $atts['bot_margin'];
			$margin = " style='margin-bottom:" . $bot_margin . "px'"; // OK

			return '<div' . ( $atts['shortcode_id'] !== '' ? ' id="' . esc_attr( $atts['shortcode_id'] ) .'"' : '' ) . ' class="prdctfltr_sc prdctfltr_sc_products woocommerce' . ( $atts['ajax'] == 'yes' ? ' prdctfltr_ajax' : '' ) . ( $atts['fallback_css'] == 'yes' ? ' prdctfltr_fallback_css prdctfltr_columns_fallback_' . esc_attr( $atts['columns'] ) : '' ) . ( $atts['class'] !== '' ? ' ' . esc_attr( $atts['class'] ) : '' ) . '"' . $margin . ( $atts['ajax'] == 'yes' ? $add_ajax : '' ) . '>' . do_shortcode( $cached . $cache_products . $cache_pagination ) . '</div>';

		}

		public static function add_taxonomy_args( $args, $taxonomies, $operator ) {
			if ( ! empty( $taxonomies ) ) {
				foreach( $taxonomies as $taxonomy => $terms ) {
					if ( empty( $args['tax_query'] ) ) {
						$args['tax_query'] = array();
					}
					$args['tax_query'][] = array(
						array(
							'taxonomy' => $taxonomy,
							'terms'    => array_map( 'sanitize_title', explode( ',', $terms ) ),
							'field'    => 'slug',
							'operator' => $operator,
						),
					);
				}

			}

			return $args;
		}

		public static function get_language_help() {
			if ( class_exists( 'SitePress' ) ) {
				global $sitepress, $wpml_term_translations;

				$taxonomyReq = new WPML_Display_As_Translated_Tax_Query( $sitepress, $wpml_term_translations );
				$taxonomyReq->add_hooks();
			}
		}

		public static function prdctfltr_respond_550() {

			if ( !is_array( $_POST ) ) {
				die(0);
				exit;
			}
			
			self::get_language_help();
		
			$set = array(
				'pf_request' => array(),
				'pf_requested' => array(),
				'pf_filters' => array(),
				'pf_widget_title' => null,
				'pf_set' => 'shortcode',
				'pf_paged' => '',
				'pf_pagefilters' => array(),
				'pf_shortcode' => null,
				'pf_offset' => 0,
				'pf_restrict' => '',
				'pf_adds' => array(),
				'pf_orderby_template' => null,
				'pf_count_template' => null,
				'pf_url' => '',
				'pf_step' => 0,
				'pf_active' => null
			);

			$opt = array();

			foreach( $set as $k => $v ) {
				if ( isset( $_POST[$k] ) && $_POST[$k] !== '' ) {
					$opt[$k] = $_POST[$k];
				}
				else {
					$opt[$k] = $v;
				}
			}

			self::$settings['opt'] = $opt;

			$pf_request = isset( $opt['pf_request'] ) ? $opt['pf_request'] : array();
			$pf_requested = isset( $opt['pf_requested'] ) ? $opt['pf_requested'] : array();

			if ( empty( $pf_request ) || empty( $pf_requested ) ) {
				die(0);
				exit;
			}

			global $prdctfltr_global;
			$prdctfltr_global['pagefilters'] = $opt['pf_pagefilters'];
			$prdctfltr_global['unique_id'] = key( $pf_requested );

			$active_filters = isset( $opt['pf_filters'] ) && is_array( $opt['pf_filters'] ) ? $opt['pf_filters'] : array();

			$curr_filters = array();

			foreach ( $active_filters as $k => $v ) {
				$curr_filters = array_merge( $curr_filters, array_unique( $v, SORT_REGULAR ) );
			}
			self::$settings['sc_ajax_filters'] = $curr_filters;

			if ( $opt['pf_set'] == 'shortcode' ) {

				if ( isset( $prdctfltr_global['pagefilters'][$prdctfltr_global['unique_id']]['wcsc'] ) ) {

					$prdctfltr_global['sc_init'] = true;
					$prdctfltr_global['sc_query'] = $opt['pf_shortcode'];

					if ( isset( $prdctfltr_global['pagefilters'][$prdctfltr_global['unique_id']]['atts'] ) ) {
						extract( $prdctfltr_global['pagefilters'][$prdctfltr_global['unique_id']]['atts'] );
					}
					else if ( is_array( $prdctfltr_global['pagefilters'] ) ) {
						$pf_pagefilters = $prdctfltr_global['pagefilters'];
						reset( $pf_pagefilters );
						extract( $prdctfltr_global['pagefilters'][key( $pf_pagefilters )]['atts'] );
					}

				}
				else {

					$prdctfltr_global['sc_init'] = true;
					$prdctfltr_global['sc_query'] = $opt['pf_shortcode'];

					if ( isset( $prdctfltr_global['pagefilters'][$prdctfltr_global['unique_id']]['atts'] ) ) {
						extract( $prdctfltr_global['pagefilters'][$prdctfltr_global['unique_id']]['atts'] );
					}
					else if ( is_array( $prdctfltr_global['pagefilters'] ) ) {
						$pf_pagefilters = $prdctfltr_global['pagefilters'];
						reset( $pf_pagefilters );
						extract( $prdctfltr_global['pagefilters'][key( $pf_pagefilters )]['atts'] );
					}

				}

			}
			else {

				$permalink_taxonomies = array();
				$permalink_taxonomies_data = array();

				foreach( $opt['pf_adds'] as $k => $v ) {

					if ( strpos( $v, ',' ) ) {
						$pf_helper = explode( ',', $v );
						$permalink_taxonomies_data[$k.'_relation'] = 'IN';
					}
					else if ( strpos( $v, '+' ) ) {
						$pf_helper = explode( '+', $v );
						$permalink_taxonomies_data[$k.'_relation'] = 'AND';
					}
					else if ( strpos( $v, ' ' ) ) {
						$pf_helper = explode( ' ', $v );
						$permalink_taxonomies_data[$k.'_relation'] = 'AND';
					}
					else {
						$pf_helper = array( $v );
						$permalink_taxonomies_data[$k.'_relation'] = 'IN';
					}

					foreach( $pf_helper as $val ) {
						if ( term_exists( $val, $k ) !== null ) {
							$pf_helper_real[] = $val;
						}
					}

					if ( !empty( $pf_helper_real ) ) {
						if ( isset( $permalink_taxonomies_data[$k . '_relation'] ) && $permalink_taxonomies_data[$k . '_relation'] == 'AND' ){
							$permalink_taxonomies_data[$k . '_string'] = implode( '+', $pf_helper_real );
						}
						else {
							$permalink_taxonomies_data[$k . '_string'] = implode( ',', $pf_helper_real );
						}
						$permalink_taxonomies[$k] = $permalink_taxonomies_data[$k . '_string'];
					}


					$prdctfltr_global['permalinks_data'] = $permalink_taxonomies_data;
					$prdctfltr_global['sc_query'] = $prdctfltr_global['active_permalinks'] = $permalink_taxonomies;

				}

				$pagination_args = array();

			}

			$data = array();

			$atts = array();
			if ( self::$settings['opt']['pf_step'] == 0 && isset( $opt['pf_active'] ) ) {
				$atts = $opt['pf_active'];
			}
			else {
				$atts = $prdctfltr_global['pagefilters'][key( $pf_requested )];
			}

			if ( isset( $atts['wcsc'] ) ) {

				XforWC_Product_Filters_Frontend::make_global( $curr_filters, 'AJAX' );

				$GLOBALS['post'] = '';

				$data['query'] = self::get_query_string( $curr_filters );
				$data['products'] = call_user_func( 'WC_Shortcodes::' . $atts['loop_name'], $atts['atts'] );
				if ( isset( self::$settings['paginationExport'] ) && is_string( self::$settings['paginationExport'] ) ) {
					$data['pagination'] = self::$settings['paginationExport'];
				}

				global $wp_query;
				$query = $wp_query;

			}

			else if ( isset( $atts['sc'] ) ) {

				$data['query'] = self::get_query_string( $curr_filters );
				$data['products'] = call_user_func( 'XforWC_Product_Filters_Shortcodes::prdctfltr_sc_products_700', $atts['atts'] );
				if ( isset( self::$settings['paginationExport'] ) && is_string( self::$settings['paginationExport'] ) ) {
					$data['pagination'] = self::$settings['paginationExport'];
				}

				global $wp_query;
				$query = $wp_query;

			}

			else if ( isset( $atts['archive'] ) ) {

				XforWC_Product_Filters_Frontend::make_global( $curr_filters, 'AJAX' );

				$query = self::make_query_700();
				global $wp_query;
				$wp_query = $query;
				$data['query'] = self::get_query_string( $curr_filters );
				$data['products'] = self::get_products( $query );
				$data['pagination'] = self::get_pagination( $pagination_args );

			}

			foreach( $pf_request as $filter => $options ) {

				if ( in_array( $filter, $pf_requested ) ) {

					ob_start();

					$prdctfltr_global['unique_id'] = $filter;
					$prdctfltr_global['disable_overrides'] = !empty( $options['disable_overrides'] ) ? $options['disable_overrides'] : '';

					if ( $options['widget_search'] !== 'yes' ) {
						if ( empty( $prdctfltr_global['pagefilters'][$prdctfltr_global['unique_id']]['archive'] ) && !empty( $prdctfltr_global['pagefilters'][$prdctfltr_global['unique_id']]['atts']['preset'] ) ) {
							$prdctfltr_global['preset'] = $prdctfltr_global['pagefilters'][$prdctfltr_global['unique_id']]['atts']['preset'];
						}
						include( XforWC_Product_Filters_Frontend::$dir . 'templates/product-filter.php' );
					}
					else {

						$widgetOptions = $options['widget_options'];

						if ( isset( $widgetOptions['preset'] ) && $widgetOptions['preset'] !== '' ) {
							$prdctfltr_global['preset'] = $widgetOptions['preset'];
						}

						$defaults = array(
							'style' => 'pf_default',
							'preset' => '',
							'disable_overrides' => 'no',
							'id' => '',
							'class' => '',
						);

						foreach( $defaults as $k => $v ) {
							if ( !isset( $widgetOptions[$k] ) ) {
								$widgetOptions[$k] = $v;
							}
						}

						$curr_title = array(
							'<h2 class="widget-title">',
							'</h2>'
						);

						if ( isset( $opt['pf_widget_title'] ) ) {
							$curr_title = explode( '%%%', $opt['pf_widget_title'] );
						}

						the_widget( 'prdctfltr', array(
								'preset' => $widgetOptions['style'],
								'template' => $widgetOptions['preset'],
								'disable_overrides' => $widgetOptions['disable_overrides'],
								'id' => $widgetOptions['id'],
								'class' => $widgetOptions['class'],
							), array( 'before_title' => stripslashes( $curr_title[0] ), 'after_title' => stripslashes( $curr_title[1] ) )
						);

					}

					$data['product_filter'][] = array(
						'id' => $filter,
						'filter' => ob_get_clean(),
					);

				}

			}

			if ( $opt['pf_step'] == 0 ) {

				$ajaxTemplates = XforWC_Product_Filters_Frontend::$options['install']['ajax']['dont_load'];

				if ( !empty( $wp_query ) ) {

					if ( isset( self::$settings['loop_start'] ) ) {
						$data['loop_start'] = self::$settings['loop_start'];
					}
					else {
						ob_start();
						woocommerce_product_loop_start();
						$data['loop_start'] = ob_get_clean();
					}

					if ( isset( self::$settings['loop_end'] ) ) {
						$data['loop_end'] = self::$settings['loop_end'];
					}
					else {
						ob_start();
						woocommerce_product_loop_end();
						$data['loop_end'] = ob_get_clean();
					}

					if ( !in_array( 'result', $ajaxTemplates ) && isset( $opt['pf_count_template'] ) ) {

						if ( $wp_query->found_posts > 0 ) {
							wc_set_loop_prop( 'total', intval( XforWC_Product_Filters_Frontend::$options['sc_instance']['total'] ) );
							wc_set_loop_prop( 'per_page', intval( XforWC_Product_Filters_Frontend::$options['sc_instance']['per_page'] ) );
							wc_set_loop_prop( 'current_page', max( 1, intval( XforWC_Product_Filters_Frontend::$options['sc_instance']['paged'] ) ) );

							ob_start();
							woocommerce_result_count();
							$data['count'] = ob_get_clean();
						}
						else {
							$data['count'] = '';
						}

					}

					if ( get_query_var( 'paged' ) < 2 ) {
						$wp_query->set( 'paged', 0 );
					}

					if ( !in_array( 'breadcrumbs', $ajaxTemplates ) ) {
						$data['breadcrumbs'] = self::get_breadcrumbs();
					}

					if ( !in_array( 'title', $ajaxTemplates ) ) {
						$data['title'] = self::get_title();
					}

					if ( !in_array( 'desc', $ajaxTemplates ) ) {
						$data['desc'] = self::get_description();
					}

				}

				if ( !in_array( 'orderby', $ajaxTemplates ) && isset( $opt['pf_orderby_template'] ) ) {

					if ( !isset( $_GET['orderby'] ) && isset( $prdctfltr_global['active_filters']['orderby'] ) ) {
						$_GET['orderby'] = $prdctfltr_global['active_filters']['orderby'];
					}
					else if ( !isset( $_GET['orderby'] ) ) {
						$orderby  = XforWC_Product_Filters_Frontend::get_catalog_ordering_args();
						$_GET['orderby'] = $orderby;
					}

					if ( isset( $_GET['orderby'] ) ) {
						$rememberTotal = wc_get_loop_prop( 'total' );

						wc_set_loop_prop( 'total', 1 );

						ob_start();
						woocommerce_catalog_ordering();
						$data['orderby'] = ob_get_clean();

						wc_set_loop_prop( 'total', $rememberTotal );
					}

				}

			}

			if ( isset( $prdctfltr_global['ranges'] ) ) {
				$data['ranges'] = $prdctfltr_global['ranges'];
			}
			if ( isset( $prdctfltr_global['filter_js'] ) ) {
				$data['js_filters'] = $prdctfltr_global['filter_js'];
			}

			wp_send_json( apply_filters( 'prdctfltr_before_ajax_json_send', $data ) );
			exit;

		}

		public static function get_query_string( $curr_filters ) {

			if ( XforWC_Product_Filters_Frontend::$options['install']['ajax']['permalinks'] == 'yes' ) {
				return '';
			}

			$opt = self::$settings['opt'];

			if ( $opt['pf_step'] !== 0 ) {
				return '';
			}

			$lang = isset( $_REQUEST['lang'] ) ? $_REQUEST['lang'] : false;

			global $wp_rewrite;

			$redirect = trailingslashit( preg_replace( '%\/page/[0-9]+%', '', esc_url( $opt['pf_url'] ) ) );

			if ( strpos( $redirect, '?' ) > 0 ) {
				$redirect = explode( '?', $redirect );
				$redirect = $redirect[0];
			}

			$_SERVER['REQUEST_URI'] = str_replace( get_bloginfo( 'url' ), '', trailingslashit( $redirect ) );

			$filter_query_before = untrailingslashit( $redirect );
			if ( $opt['pf_paged'] > 1 ) {
				global $paged;
				$paged = $opt['pf_paged'];
				if ( get_option( 'permalink_structure' ) == '' ) {
					$filter_query_before = untrailingslashit( $redirect ) . '/' . '?paged=' . $opt['pf_paged'];
				}
				else {
					$filter_query_before = untrailingslashit( $redirect ) . '/' . $wp_rewrite->pagination_base . '/' . $opt['pf_paged'];
				}
			}

			foreach ( $curr_filters as $cfk => $cfv ) {
				if ( !isset( $filter_query ) ) {
					$filter_query = '/?' . $cfk . '=' . $cfv;
				}
				else {
					$filter_query .= '&' . $cfk . '=' . $cfv;
				}
			}

			
			if ( $opt['pf_set'] == 'shortcode' || XforWC_Product_Filters_Frontend::$options['install']['ajax']['permalinks'] == 'query' ) {
				$redirect = isset( $filter_query ) ? $filter_query_before . $filter_query : trailingslashit( $filter_query_before );

				if ( !empty( $lang ) ) {
					return add_query_arg( 'lang', $lang, $redirect );
				}
				return $redirect;
			}

			if ( get_option( 'permalink_structure' ) !== '' ) {

				global $wp_query;

				$current = $wp_query->get_queried_object();

				if ( isset( $current->taxonomy ) && isset( $curr_filters[$current->taxonomy] ) ) {

					$rewrite = $wp_rewrite->get_extra_permastruct( $current->taxonomy );

					if ( $rewrite !== false ) {

						if ( strpos( $curr_filters[$current->taxonomy], ',' ) || strpos( $curr_filters[$current->taxonomy], '+' ) || strpos( $curr_filters[$current->taxonomy], ' ' ) ) {
							if ( strpos( $curr_filters[$current->taxonomy], ',' ) ) {
								$terms = explode( ',', $curr_filters[$current->taxonomy] );
							}
							else if ( strpos( $curr_filters[$current->taxonomy], '+' ) ) {
								$terms = explode( '+', $curr_filters[$current->taxonomy] );
							}
							else if ( strpos( $curr_filters[$current->taxonomy], ' ' ) ) {
								$terms = explode( ' ', $curr_filters[$current->taxonomy] );
							}

							foreach( $terms as $term ) {
								$checked = get_term_by( 'slug', $term, $current->taxonomy );
								if ( !is_wp_error( $checked ) ) {
									/*if ( $checked->parent !== 0 ) {*/
										$parents[] = $checked->parent;
									/*}*/
								}
							}

							$parent_slug = '';
							if ( isset( $parents ) ) {
								$parents_unique = array_unique( $parents );
								if ( count( $parents_unique ) == 1 && $parents_unique[0] !== 0 ) {
									$not_found = false;
									$parent_check = $parents_unique[0];
									while ( $not_found === false ) {
										if ( $parent_check !== 0 ) {
											$checked = get_term_by( 'id', $parent_check, $current->taxonomy );
											if ( !is_wp_error( $checked ) ) {
												$get_parent = $checked->slug;
												$parent_slug =  $get_parent . '/' . $parent_slug;
												if ( $checked->parent !== 0 ) {
													$parent_check = $checked->parent;
												}
												else {
													$not_found = true;
												}
											}
											else {
												$not_found = true;
											}
										}
										else {
											$not_found = true;
										}
									}

								}
							}

							$redirect = untrailingslashit( preg_replace( '/\?.*/', '', get_bloginfo( 'url' ) ) ) . '/' . str_replace( '%' . $current->taxonomy . '%', $parent_slug . $curr_filters[$current->taxonomy], $rewrite );
						}
						else {
							$link = get_term_link( $curr_filters[$current->taxonomy], $current->taxonomy );
							if ( !is_wp_error( $link ) ) {
								$redirect = preg_replace( '/\?.*/', '', $link );
							}
						}

						unset( $curr_filters[$current->taxonomy] );

					}
					else {

						$redirect = get_permalink( XforWC_Product_Filters_Frontend::prdctfltr_wpml_get_id( wc_get_page_id( 'shop' ) ) );

					}

					if ( strpos( $redirect, '?' ) > 0 ) {
						$redirect = explode( '?', $redirect );
						$redirect = $redirect[0];
					}

					$redirect = untrailingslashit( $redirect );

					$_SERVER['REQUEST_URI'] = str_replace( get_bloginfo( 'url' ), '', trailingslashit( $redirect ) );

					if ( $opt['pf_paged'] > 1 ) {
						$redirect = $redirect . '/' . $wp_rewrite->pagination_base . '/' . $opt['pf_paged'];
					}

					if ( !empty( $curr_filters ) ) {

						$req = '';

						foreach( $curr_filters as $k => $v ) {
							if ( $v == '' || in_array( $k, apply_filters('prdctfltr_block_request', array( 'woocs_order_emails_is_sending' ) ) ) ) {
								continue;
							}

							$req .= $k . '=' . $v . '&';
						}

						$redirect = $redirect . '/?' . $req;

						if ( substr( $redirect, -1 ) == '&' ) {
							$redirect = substr( $redirect, 0, -1 );
						}

					}
					else {
						$redirect = trailingslashit( $redirect );
					}
				
					if ( !empty( $lang ) ) {
						return add_query_arg( 'lang', $lang, $redirect );
					}

					return $redirect;

				}
				else {

					$redirect = get_permalink( XforWC_Product_Filters_Frontend::prdctfltr_wpml_get_id( wc_get_page_id( 'shop' ) ) );

					if ( strpos( $redirect, '?' ) > 0 ) {
						$redirect = explode( '?', $redirect );
						$redirect = $redirect[0];
					}


					$redirect = untrailingslashit( $redirect );

					$_SERVER['REQUEST_URI'] = str_replace( get_bloginfo( 'url' ), '', trailingslashit( $redirect ) );

					if ( $opt['pf_paged'] > 1 ) {
						$redirect = $redirect . '/' . $wp_rewrite->pagination_base . '/' . $opt['pf_paged'];
					}

					if ( !empty( $curr_filters ) ) {

						$req = '';

						foreach( $curr_filters as $k => $v ) {
							if ( $v == '' || in_array( $k, apply_filters('prdctfltr_block_request', array( 'woocs_order_emails_is_sending' ) ) ) ) {
								continue;
							}

							$req .= $k . '=' . $v . '&';
						}

						$redirect = $redirect . '/?' . $req;

						if ( substr( $redirect, -1 ) == '&' ) {
							$redirect = substr( $redirect, 0, -1 );
						}

					}
					else {
						$redirect = trailingslashit( $redirect );
					}

					if ( !empty( $lang ) ) {
						return add_query_arg( 'lang', $lang, $redirect );
					}

					return $redirect;
				}

			}

		}

		public static function get_breadcrumbs() {
			$out = '';
			
			if ( function_exists( 'woocommerce_breadcrumb' ) ) {
				ob_start();

				woocommerce_breadcrumb();

				$out .= ob_get_clean();
			}

			return $out;
		}

		public static function get_title() {
			$out = '';
			if ( function_exists( 'woocommerce_page_title' ) ) {
				ob_start();
			?>
				<h1 class="woocommerce-products-header__title page-title"><?php woocommerce_page_title(); ?></h1>
			<?php
				$out .= ob_get_clean();
			}
			return $out;
		}

		public static function get_description() {
			$out = '';
			global $wp_query;

			if ( function_exists( 'woocommerce_taxonomy_archive_description' ) ) {
				ob_start();
				woocommerce_taxonomy_archive_description();
				$out .= ob_get_clean();
			}
			if ( $out == '' && function_exists( 'woocommerce_product_archive_description' ) ) {
				ob_start();
				woocommerce_product_archive_description();
				$out .= ob_get_clean();
			}
			return $out;
		}

		public static function add_product_class( $classes ) {
			global $product;

			if ( !empty( $product ) && !in_array( $product->get_type(), $classes ) ) {
				$classes[] = $product->get_type();
			}

			return $classes;
		}


		public static function make_query_700() {

			global $prdctfltr_global;

			$opt = self::$settings['opt'];

			add_filter( 'post_class', __CLASS__ . '::add_product_class' );

			$per_page = apply_filters( 'loop_shop_per_page', get_option( 'posts_per_page' ) );
			$columns = apply_filters( 'loop_shop_columns', 4 );

			if ( isset( $prdctfltr_global['pagefilters'][$prdctfltr_global['unique_id']]['sc'] )) {
				$per_page = $prdctfltr_global['pagefilters'][$prdctfltr_global['unique_id']]['atts']['per_page'];
				$columns = $prdctfltr_global['pagefilters'][$prdctfltr_global['unique_id']]['atts']['columns'];
			}
			if ( isset( $prdctfltr_global['pagefilters'][$prdctfltr_global['unique_id']]['archive'] )) {
				$per_page = $prdctfltr_global['pagefilters'][$prdctfltr_global['unique_id']]['atts']['per_page'];
				$columns = $prdctfltr_global['pagefilters'][$prdctfltr_global['unique_id']]['atts']['columns'];
			}
			if ( isset( $prdctfltr_global['active_filters']['products_per_page'] ) ) {
				$per_page = $prdctfltr_global['active_filters']['products_per_page'];
			}

			wc_set_loop_prop( 'columns', $columns );

			$query_args = $prdctfltr_global['pagefilters'][$prdctfltr_global['unique_id']]['query_args'];

			$query_args['paged'] = $opt['pf_paged'];
			$query_args['posts_per_page'] = intval( $per_page );
			$query_args['fields'] = 'ids';
			$query_args['no_found_rows'] = 0;

			$offset = intval( $opt['pf_offset'] );
			if ( $offset>0 ) {
				$query_args['offset'] = $offset;
			}

			$query_args['prdctfltr_active'] = true;

			add_filter( 'pre_get_posts', 'XforWC_Product_Filters_Frontend::sc_wc_query', 999999, 1 );
			add_filter( 'parse_tax_query', 'XforWC_Product_Filters_Frontend::sc_wc_tax', 999999, 1 );

			$products = new WP_Query( $query_args );

			WC()->query->remove_ordering_args();
			remove_filter( 'pre_get_posts', 'XforWC_Product_Filters_Frontend::sc_wc_query', 999999 );
			remove_filter( 'parse_tax_query', 'XforWC_Product_Filters_Frontend::sc_wc_tax', 999999 );

			$products->is_search = false;

			XforWC_Product_Filters_Frontend::$options['sc_instance'] = array(
				'paged'			=> $opt['pf_paged'],
				'per_page'		=> $per_page,
				'total'			=> $products->found_posts,
				'max_num_pages' => isset( $products->max_num_pages ) ? $products->max_num_pages : ceil( $products->found_posts/$per_page ),
				'first'			=> ( $per_page * $opt['pf_paged'] ) - $per_page + 1,
				'last'			=> $offset > 0 ? min( $products->found_posts, $offset + $per_page ) : min( $products->found_posts, $per_page * $opt['pf_paged'] ),
				'request'		=> $products->request
			);

			global $paged;
			$paged = $opt['pf_paged'];

			return $products;

		}

		public static function get_pagination( $pagination ) {

			if ( isset( $pagination['type'] ) && $pagination['type'] == 'no' ) {
				return;
			}

			if ( !isset( self::$settings['wcsc'] ) ) {
				if ( !isset( self::$settings['instance'] ) ) {
					return;
				}
			}

			if ( ( $custom_pagination = XforWC_Product_Filters_Frontend::$options['install']['ajax']['pagination_function'] ) !== '' ) {
				if ( function_exists( $custom_pagination ) ) {
					ob_start();
					call_user_func( $custom_pagination );
					$html = ob_get_clean();
					if ( $html == '' ) {
						$html = call_user_func( $custom_pagination );
					}
					return $html;
				}
			}

			global $prdctfltr_global;

			// if ( wp_doing_ajax() ) {
			// 	$GLOBALS['current_screen'] = new XforWC_Product_Filters_Shortcodes_AJAX_Fix;
			// }

			$args = array(
				'total' => XforWC_Product_Filters_Frontend::$options['sc_instance']['max_num_pages'],
				'current' => XforWC_Product_Filters_Frontend::$options['sc_instance']['paged']
			);

			if ( isset( $pagination['sc'] ) ) {

				$ajax = $pagination['ajax'];
				$type = $pagination['type'];

				if ( $ajax == 'yes' ) {
					switch ( $type ) {
						case 'yes' :
							$class = 'default';
						break;
						case 'override' :
							$class = 'prdctfltr-pagination-default';
						break;
						case 'loadmore' :
							$class = 'prdctfltr-pagination-load-more';
						break;
						case 'infinite' :
							$class = 'prdctfltr-pagination-infinite-load';
						break;
						default :
							$class = 'default';
						break;
					}
				}
				else {
					$class = 'default';
				}

				$prdctfltr_global['pagination_type'] = $class;

				global $wp_query;
				$remember_query = $wp_query;
				$wp_query = self::$settings['instance'];

				ob_start();
				if ( $prdctfltr_global['pagination_type'] == 'default' ) {
					wc_get_template( 'loop/pagination.php', $args );
				}
				else {
					XforWC_Product_Filters_Frontend::$settings['template'] = 'loop/pagination.php';
					include( XforWC_Product_Filters_Frontend::$dir . 'templates/getright.php' );
				}
				$pagination = ob_get_clean();

				$wp_query = $remember_query;
			}
			else {
				$ajax = 'yes';
				$prdctfltr_global['pagination_type'] = XforWC_Product_Filters_Frontend::$options['install']['ajax']['pagination_type'];

				ob_start();
				wc_get_template( 'loop/pagination.php', $args );
				$pagination = ob_get_clean();
			}

			unset( $prdctfltr_global['pagination_type'] );

			return $pagination;

		}

		public static function get_products( $products ) {

			global $prdctfltr_global;

			$opt = self::$settings['opt'];

			$offset = intval( $opt['pf_offset'] );

			$loop_elements = array();

			if ( isset( $opt['pf_active_sc'] ) && isset( $prdctfltr_global['pagefilters'][$opt['pf_active_sc']]['atts'] ) ) {
				extract( $prdctfltr_global['pagefilters'][$opt['pf_active_sc']]['atts'] );

				$check_elements = array(
					'title' => $show_loop_title,
					'price' => $show_loop_price,
					'rating' => $show_loop_rating,
					'add_to_cart' => $show_loop_add_to_cart
				);

				foreach( $check_elements as $k => $v ) {
					if ( !empty( $v ) && $v == 'no' ) {
						$loop_elements[] = $k;
					}
				}

			}
			else if ( isset( $prdctfltr_global['unique_id'] ) && isset( $prdctfltr_global['pagefilters'][$prdctfltr_global['unique_id']]['atts']['show_products'] ) ) {

				extract( $prdctfltr_global['pagefilters'][$prdctfltr_global['unique_id']]['atts'] );

				$check_elements = array(
					'title' => $show_loop_title,
					'price' => $show_loop_price,
					'rating' => $show_loop_rating,
					'add_to_cart' => $show_loop_add_to_cart
				);

				foreach( $check_elements as $k => $v ) {
					if ( !empty( $v ) && $v == 'no' ) {
						$loop_elements[] = $k;
					}
				}

			}
			else {
				$show_categories = 'archive';
			}

			if ( in_array( $show_categories, array( 'archive', 'yes' ) ) ) {
				if ( !isset( $prdctfltr_global['active_filters']['product_cat'][0] ) ) {
					$products->is_post_type_archive = true;
					$show_products = get_option( 'woocommerce_shop_page_display' ) == 'subcategories' ? 'no' : 'yes';
					if ( !empty( $prdctfltr_global['active_filters'] ) ) {
						if ( function_exists( 'wc_set_loop_prop' ) ) {
							wc_set_loop_prop( 'is_filtered', true );
						}
						$show_products = 'yes';
					}
				}
				else {
					$parent_id    = get_queried_object_id();
					$display_type = get_term_meta( $parent_id, 'display_type', true );
					$display_type = '' === $display_type ? get_option( 'woocommerce_category_archive_display', '' ) : $display_type;
					$products->is_post_type_archive = false;
					$show_products = $display_type == 'subcategories' ? 'no' : 'yes';
					if ( !empty( $prdctfltr_global['active_filters'] ) && count( $prdctfltr_global['active_filters'] ) > 1 ) {
						if ( function_exists( 'wc_set_loop_prop' ) ) {
							wc_set_loop_prop( 'is_filtered', true );
						}
						$show_products = 'yes';
					}
					$asked_term = get_term_by( 'slug', $prdctfltr_global['active_filters']['product_cat'][0], 'product_cat' );
					$child_terms = get_term_children( $asked_term->term_id, 'product_cat' );
					if ( empty( $child_terms ) ) {
						$show_products = 'yes';
					}
				}
			}

			self::$settings['instance'] = $products;

			if ( $products->have_posts() ) {

				if ( wp_doing_ajax() ) {
					add_filter( 'get_terms', __CLASS__ . '::wc1_change_term_counts', 10, 2 );
				}

				if ( !empty( $loop_elements ) ) {
					self::make_visibility( 'remove', $loop_elements );
				}

				if ( $show_categories !== 'archive' ) {
					remove_filter( 'woocommerce_product_loop_start', 'woocommerce_maybe_show_product_subcategories' );
				}

				$html = '';

				ob_start();
				woocommerce_product_loop_start();
				$loop_start = ob_get_clean();
				self::$settings['loop_start'] = $loop_start;

				ob_start();
				if ( $offset < 1 ) {
					if ( ( $show_categories == 'yes' && function_exists( 'woocommerce_output_product_categories' ) ) ) {
						if ( wc_get_loop_prop( 'is_filtered' ) !== true ) {
							$selected_term = isset( $prdctfltr_global['active_filters']['product_cat'][0] ) ? $prdctfltr_global['active_filters']['product_cat'][0] : '';
							if ( $selected_term == '' ) {
								$selected_term = isset( $prdctfltr_global['active_permalinks']['product_cat'][0] ) ? $prdctfltr_global['active_permalinks']['product_cat'][0] : '';
							}
							if ( $selected_term !== '' ) {
								if ( term_exists( $selected_term, 'product_cat' ) ) {
									$term = get_term_by( 'slug', $selected_term, 'product_cat' );
								}
							}

							woocommerce_output_product_categories( array(
								'parent_id' => isset( $term ) ? $term->term_id : 0,
							) );
						}
					}
				}

				if ( $offset > 0 ) {

					if ( function_exists( 'wc_get_default_products_per_row' ) ) {
						$columns = wc_get_default_products_per_row();
					}
					else {
						$columns = get_option( 'woocommerce_catalog_columns', 4 );
					}

					$set = wc_get_loop_prop( 'columns', $columns );
					if ( $set ) {
						$columns = $set;
					}

					$curr_offset = $offset / $columns;
					$decimal = $curr_offset - (int) $curr_offset;

					if ( $decimal > 0 ) {
						wc_set_loop_prop( 'loop', $decimal * $columns );
					}
					else {
						wc_set_loop_prop( 'loop', 0 );
					}

				}

				if ( wp_doing_ajax() ) {
					$remember = $_SERVER['REQUEST_URI'];
				}

				if ( $show_products !== 'no' ) {

					while ( $products->have_posts() ) : $products->the_post();

						if ( wp_doing_ajax() ) {
							$_SERVER['REQUEST_URI'] = esc_url( get_permalink() );
						}

						do_action( 'woocommerce_shop_loop' );

						wc_get_template( 'content-product.php' );

					endwhile;

				}

				if ( wp_doing_ajax() ) {
					$_SERVER['REQUEST_URI'] = $remember;
				}
				$products = ob_get_clean();

				ob_start();
				woocommerce_product_loop_end();
				$loop_end = ob_get_clean();
				self::$settings['loop_end'] = $loop_end;

				if ( !empty( $loop_elements ) ) {
					self::make_visibility( 'add', $loop_elements );
				}

				if ( wp_doing_ajax() ) {
					remove_filter( 'get_terms', __CLASS__ . '::wc1_change_term_counts', 10 );
				}

				return $loop_start . $products . $loop_end;

			}
			else {
	
				if ( $show_products == 'no' ) {
					return '';
				}

				ob_start();

				wc_get_template( 'loop/loop-start.php' );

				do_action( 'woocommerce_no_products_found' );

				wc_get_template( 'loop/loop-end.php' );

				return ob_get_clean();

			}

			do_action( 'prdctfltr_reset_loop' );

		}

		public static function get_finder( $atts, $content = null ) {
			$atts = shortcode_atts( array(
				'preset' => '',
				'ajax' => 'no',
				'disable_overrides' => 'yes',
				'action' => '',
				'class' => '',
				'shortcode_id' => ''
			), $atts, 'products_finder' );

			echo do_shortcode( '[prdctfltr_sc_products show_products="no" ajax="' . esc_attr( $atts['ajax'] ) . '" preset="' . esc_attr( $atts['preset'] ) . '" action="' . esc_attr( $atts['action'] ) . '" class="' . esc_attr( $atts['class'] ) . '" shortcode_id="' . esc_attr( $atts['shortcode_id'] ) . '" disable_overrides="' . esc_attr( $atts['disable_overrides'] ) . '"]' );
		}

		public static function prdctfltr_sc_get_filter( $atts, $content = null ) {

			$shortcode_atts = shortcode_atts( array(
				'preset' => '',
				'ajax' => 'no',
				'disable_overrides' => 'yes',
				'action' => '',
				'bot_margin' => 36,
				'class' => '',
				'shortcode_id' => ''
			), $atts );

			extract( $shortcode_atts );

			global $prdctfltr_global;

			$prdctfltr_global['sc_init'] = true;

			$paged = isset( $_GET['paged'] ) ? intval( $_GET['paged'] ) : get_query_var( 'paged' );

			if ( $paged < 1 ) {
				$paged = 1;
			}

			$prdctfltr_global['unique_id'] = uniqid( 'prdctfltr-' );
			$prdctfltr_global['action'] = ( $action !== '' ? $action : '' );
			$prdctfltr_global['preset'] = ( $preset !== '' ? $preset : '' );
			$prdctfltr_global['disable_overrides'] = ( $disable_overrides == 'yes' ? 'yes' : 'no' );

			$prdctfltr_global['pagefilters'][$prdctfltr_global['unique_id']]['query_args'] = array();
			$prdctfltr_global['pagefilters'][$prdctfltr_global['unique_id']]['atts'] = $shortcode_atts;
			$prdctfltr_global['pagefilters'][$prdctfltr_global['unique_id']]['args'] = array();
			$prdctfltr_global['pagefilters'][$prdctfltr_global['unique_id']]['request'] = array();

			if ( $ajax == 'yes' ) {
				$add_ajax = ' data-page="' . esc_attr( $paged ) . '"'; // OK
			}

			$opt = array(
				'pf_request' => array(),
				'pf_requested' => array(),
				'pf_filters' => array(),
				'pf_widget_title' => null,
				'pf_set' => 'archive',
				'pf_paged' => $paged,
				'pf_pagefilters' => array(),
				'pf_shortcode' => '',
				'pf_offset' => 0,
				'pf_restrict' => '',
				'pf_adds' => array(),
				'pf_orderby_template' => null,
				'pf_count_template' => null
			);

			self::$settings['opt'] = $opt;


			$query = self::make_query_700();

			if ( wp_doing_ajax() ) {
				global $wp_query;
				$wp_query = $query;
			}

			ob_start();

			include( XforWC_Product_Filters_Frontend::$dir . 'templates/product-filter.php' );

			$cached = ob_get_clean();

			$prdctfltr_global['unique_id'] = null;
			wp_reset_query();
			wp_reset_postdata();

			$bot_margin = ( int ) $bot_margin;
			$margin = " style='margin-bottom:" . $bot_margin . "px'"; // OK

			return '<div' . ( $shortcode_id !== '' ? ' id="' . esc_attr( $shortcode_id ) . '"' : '' ) . ' class="prdctfltr_sc prdctfltr_sc_filter woocommerce ' . ( $ajax == 'yes' ? ' prdctfltr_ajax' : '' ) . ( $class !== '' ? ' ' . esc_attr( $class ) : '' ) . '"' . $margin . ( $ajax == 'yes' ? $add_ajax : '' ) . '>' . do_shortcode( $cached ) . '</div>';

		}

		public static function make_visibility( $action = 'remove', $loop_elements = array() ) {

			if ( $action == 'remove' ) {

				if ( in_array( 'title', $loop_elements ) ) {
					remove_action( 'woocommerce_shop_loop_item_title', 'woocommerce_template_loop_product_title', 10 );
				}
				if ( in_array( 'rating', $loop_elements ) ) {
					remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_rating', 5 );
				}
				if ( in_array( 'price', $loop_elements ) ) {
					remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price', 10 );
				}
				if ( in_array( 'add_to_cart', $loop_elements ) ) {
					remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10 );
				}

			}
			else if ( $action == 'add' ) {

				if ( in_array( 'title', $loop_elements ) ) {
					add_action( 'woocommerce_shop_loop_item_title', 'woocommerce_template_loop_product_title', 10 );
				}
				if ( in_array( 'rating', $loop_elements ) ) {
					add_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_rating', 5 );
				}
				if ( in_array( 'price', $loop_elements ) ) {
					add_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price', 10 );
				}
				if ( in_array( 'add_to_cart', $loop_elements ) ) {
					add_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10 );
				}

			}

		}

		public static function fix_empty_cat_display($false) {
			return false;
		}

		public static function wc1_change_term_counts( $terms, $taxonomies ) {

			if ( ! isset( $taxonomies[0] ) || ! in_array( $taxonomies[0], apply_filters( 'woocommerce_change_term_counts', array( 'product_cat', 'product_tag' ) ) ) ) {
				return $terms;
			}
			$term_counts = $o_term_counts = get_transient( 'wc_term_counts' );
			foreach ( $terms as $k => &$term ) {
				if ( is_object( $term ) ) {
					if ( $term->term_id == -1 ) {
						unset( $terms[$k] );
					}
					$term_counts[ $term->term_id ] = isset( $term_counts[ $term->term_id ] ) ? $term_counts[ $term->term_id ] : get_term_meta( $term->term_id, 'product_count_' . $taxonomies[0] , true );
					if ( '' !== $term_counts[ $term->term_id ] ) {
						$term->count = absint( $term_counts[ $term->term_id ] );
					}
				}
			}

			if ( $term_counts != $o_term_counts ) {
				set_transient( 'wc_term_counts', $term_counts, DAY_IN_SECONDS * 30 );
			}
			return $terms;
		}

	}
	add_action( 'init', array( 'XforWC_Product_Filters_Shortcodes', 'init' ), 999 );

