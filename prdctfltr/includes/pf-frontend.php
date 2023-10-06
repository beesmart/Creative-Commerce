<?php

	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	class XforWC_Product_Filters_Frontend {

		public static $version;

		public static $dir;
		public static $path;
		public static $url_path;

		public static $settings;
		public static $options;

		public static $filter;
		public static $theme;
		public static $opt;

		public static function init() {
			$class = __CLASS__;
			new $class;
		}

		function __construct() {

			self::$version = XforWC_Product_Filters::$version;

			self::$dir = trailingslashit( Prdctfltr()->plugin_path() );
			self::$path = trailingslashit( Prdctfltr()->plugin_path() );
			self::$url_path = trailingslashit( Prdctfltr()->plugin_url() );

			self::$options = Prdctfltr()->get_default_options();

			self::_install_filter();

			add_filter( 'body_class', array( $this, 'add_body_class' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'prdctfltr_scripts' ), 999999 );
			add_action( 'wp_footer', array( $this, 'localize_scripts' ), 0 );

			add_action( 'woocommerce_product_query', array( $this, 'prdctfltr_wc_query' ), 999998, 2 );
			add_action( 'woocommerce_product_query', array( $this, 'prdctfltr_wc_tax' ), 999999, 2 );

			add_action( 'prdctfltr_output_css', array( $this, 'prdctfltr_add_css' ) );

			add_filter( 'woocommerce_product_is_visible', array( $this, 'outofstock_show' ), 2, 999999 );

			if ( !is_admin() && get_option( 'permalink_structure' ) !== '' && self::$options['general']['force_redirects'] !== 'yes' ) {
				add_action( 'template_redirect', array( $this, 'prdctfltr_redirect' ), 0 );
			}

			if ( self::$options['general']['remove_single_redirect'] == 'yes' ) {
				add_filter( 'woocommerce_redirect_single_search_result', array( $this, 'return_false' ), 999 );
			}
			if ( self::$options['general']['analytics'] == 'yes' ) {
				add_action( 'wp_ajax_nopriv_prdctfltr_analytics', array( $this, 'prdctfltr_analytics' ) );
				add_action( 'wp_ajax_prdctfltr_analytics', array( $this, 'prdctfltr_analytics' ) );
			}

			add_action( 'prdctfltr_output', array( $this, 'prdctfltr_get_filter' ), 10 );

			if ( self::$options['general']['variable_images'] == 'yes' ) {
				add_action( 'woocommerce_product_get_image', array( $this, 'prdctfltr_switch_thumbnails_350' ), 999, 5 );
			}

			add_action( 'prdctfltr_filter_before', array( $this, 'make_filterjs' ) );
			add_action( 'prdctfltr_filter_after', array( $this, 'do_after' ) );
			add_action( 'prdctfltr_filter_form_after', array( $this, 'get_added_inputs' ) );
			add_action( 'prdctfltr_filter_wrapper_before', array( $this, 'get_added_inputs' ) );
			add_action( 'prdctfltr_filter_wrapper_before', array( $this, 'make_adoptive' ), 10 );
			add_action( 'prdctfltr_filter_wrapper_before', array( $this, 'get_top_bar' ), 20 );

			add_action( 'woocommerce_after_shop_loop', array( $this, 'cleanup' ), 999 );

			add_action( 'prdctfltr_before_ajax_json_send', array( $this, 'add_variations_data' ), 999 );

			add_filter( 'wcml_multi_currency_ajax_actions', array( $this, 'wcml_currency' ), 50, 1 );
			add_filter( 'xforwc__add_meta_information_used', array( $this, 'prdctfltr_info' ) );

		}

		function _install_filter() {

			include_once( 'class-themes.php' );
			$auto = XforWC_Product_Filters_Themes::get_theme();

			if ( self::$options['install']['ajax']['automatic'] == 'yes' ) {
				if ( $auto !== false ) {
					self::$options['install']['ajax'] = array_merge( self::$options['install']['ajax'], $auto );
				}
				else {
					self::$options['install']['ajax']['enable'] = 'no';
				}
			}

			if ( self::$options['install']['ajax']['automatic'] == 'no' && $auto !== false ) {
				foreach( $auto as $k => $v ) {
					if ( !isset( self::$options['install']['ajax'][$k] ) || self::$options['install']['ajax'][$k] == '' ) {
						self::$options['install']['ajax'][$k] = $v;
					}
				}
			}

			if ( !empty( self::$options['install']['actions'] ) ) {
				foreach( self::$options['install']['actions'] as $action ) {
					include_once( 'class-filter.php' );
					$filter = new XforWC_Product_Filters_Hooks( $action );
				}
			}

			add_filter( 'woocommerce_locate_template', array( $this, 'prdctrfltr_add_filter' ), 0, 3 );
			add_filter( 'wc_get_template_part', array( $this, 'prdctrfltr_add_filter' ), 0, 3 );
		}

		function prdctfltr_info( $val ) {
			return array_merge( $val, array( 'Product Filter for WooCommerce' ) );
		}

		function true( $var ) {
			return true;
		}

		function do_after() {
			global $prdctfltr_global;

			$prdctfltr_global['init'] = true;

			if ( !isset( $prdctfltr_global['mobile'] ) && self::$settings['instance']['responsive']['behaviour'] == 'switch' ) {
				$prdctfltr_global['mobile'] = true;

				$prdctfltr_global['unique_id'] = null;
				$prdctfltr_global['preset'] = self::$settings['instance']['responsive']['preset'];

				include( self::$dir . 'templates/product-filter.php' );

			}

			if ( !isset( $prdctfltr_global['sc_init'] ) ) {
				unset( $prdctfltr_global['unique_id'] );
			}
			unset( $prdctfltr_global['mobile'] );
			unset( $prdctfltr_global['preset'] );
			unset( $prdctfltr_global['sc_query'] );
			unset( self::$options['step_filter'] );
		}

		function make_filterjs() {

			global $prdctfltr_global;

			if ( !isset( $prdctfltr_global['sc_init'] ) ) {

				if ( !isset( $prdctfltr_global['mobile'] )&& !wp_doing_ajax() &&  is_shop() || is_product_taxonomy() || is_search() || isset( self::$settings['shop_query']['wc_query'] ) && self::$settings['shop_query']['wc_query'] == 'product_query' ) {

					$columns = get_option( 'woocommerce_catalog_columns', 4 );
					if ( function_exists( 'wc_get_default_products_per_row' ) ) {
						$columns = wc_get_default_products_per_row();
					}

					$per_page = get_option( 'posts_per_page' );
					if ( function_exists( 'wc_get_default_product_rows_per_page' ) ) {
						$per_page = $columns * wc_get_default_product_rows_per_page();
					}
					$per_page = apply_filters( 'loop_shop_per_page', $per_page );

					$pf_col = intval( self::$options['install']['ajax']['columns'] );
					$pf_row = intval( self::$options['install']['ajax']['rows'] );

					if ( $pf_col > 0 ) {
						$columns = $pf_col;
					}
					if ( $pf_row > 0 ) {
						$per_page = $columns*$pf_row;
					}

					if ( !isset( self::$settings['shop_query'] ) ) {

						$ordering_args = array( 'orderby' => 'menu_order title', 'order' => 'ASC' );

						$meta_query    = WC()->query->get_meta_query();
						$query_args    = array(
							'post_type'           => 'product',
							'post_status'         => 'publish',
							'ignore_sticky_posts' => 1,
							'orderby'             => $ordering_args['orderby'],
							'order'               => $ordering_args['order'],
							'posts_per_page'      => $per_page,
							'meta_query'          => $meta_query,
							'tax_query'           => WC()->query->get_tax_query()
						);

						self::$settings['shop_query'] = $query_args;

					}

					$prdctfltr_global['pagefilters'][$prdctfltr_global['unique_id']]['archive'] = true;
					$prdctfltr_global['pagefilters'][$prdctfltr_global['unique_id']]['query_args'] = array_unique( self::$settings['shop_query'], SORT_REGULAR );

					$prdctfltr_global['pagefilters'][$prdctfltr_global['unique_id']]['atts'] = array(
						'archive' => true,
						'columns' => $columns,
						'per_page' => $per_page,
						'preset' => isset( $prdctfltr_global['preset'] ) ? $prdctfltr_global['preset'] : null,
					);

					if( isset( $prdctfltr_global['atts_data'] ) && is_array( $prdctfltr_global['atts_data'] ) ) {
						array_merge( $prdctfltr_global['pagefilters'][$prdctfltr_global['unique_id']]['atts'], $prdctfltr_global['atts_data'] );
					}

				}

			}

			$prdctfltr_global['filter_js'][$prdctfltr_global['unique_id']] = array(
				'widget_search' => isset( $prdctfltr_global['widget_search'] ) ? 'yes' : 'no',
				'widget_options' => isset( $prdctfltr_global['widget_options'] ) ? $prdctfltr_global['widget_options'] : '',
				'collectors' => self::$settings['instance']['general']['collectors'],
				'collector_style' => self::$settings['instance']['general']['collector_style'],
				'button_text' => self::$settings['instance']['style']['filter_button'],
				'disable_overrides' => !empty( $prdctfltr_global['disable_overrides'] ) ? $prdctfltr_global['disable_overrides'] : '',

				'_tx_clearall' => self::$settings['instance']['style']['_tx_clearall'],
			);

		}

		public static function get_filter_appearance() {

			global $prdctfltr_global;

			self::$settings['instance'] = null;
			self::$settings['cnt'] = 1;

			self::make_filter();

			$prdctfltr_global['unique_id'] = isset( $prdctfltr_global['unique_id'] ) ? $prdctfltr_global['unique_id'] : uniqid( 'prdctfltr-' );

		}

		function get_added_inputs() {
			global $prdctfltr_global;

			$pf_activated = isset( $prdctfltr_global['pf_activated'] ) ? $prdctfltr_global['pf_activated'] : array();

			if ( 1==1 ) {

		?>
			<div class="prdctfltr_add_inputs">
			<?php
				if ( !in_array( 'search', self::$settings['active'] ) && isset( $pf_activated['s'] ) ) {
					echo '<input type="hidden" name="' . ( isset( $prdctfltr_global['sc_init'] ) ? 'search_products' : 's' ) . '" value="' . esc_attr( $pf_activated['s'] ) . '" />';
				}
				if ( isset( $_GET['page_id'] ) ) {
					echo '<input type="hidden" name="page_id" value="' . esc_attr( $_GET['page_id'] ) . '" />';
				}
				if ( isset($_GET['lang']) ) {
					echo '<input type="hidden" name="lang" value="' . esc_attr( $_GET['lang'] ) . '" />';
				}
				if ( !in_array( 'orderby', self::$settings['active'] ) && isset( $pf_activated['orderby'] ) ) {
					echo '<input type="hidden" name="orderby" value="' . esc_attr( $pf_activated['orderby'] ) . '" class="pf_added_orderby" />';
				}

				if ( !empty( $prdctfltr_global['active_permalinks'] ) ) {
					foreach ( $prdctfltr_global['active_permalinks'] as $pf_k => $pf_v ) {
						if ( !in_array( $pf_k, self::$settings['active'] ) ) {
							echo '<input type="hidden" name="' . esc_attr( $pf_k ) . '" value="' . esc_attr( $prdctfltr_global['permalinks_data'][$pf_k . '_string'] ) . '" class="pf_added_input" />';
						}
						$prdctfltr_global['filter_js'][$prdctfltr_global['unique_id']]['adds'][$pf_k] = $prdctfltr_global['permalinks_data'][$pf_k . '_string'];
					}
				}

				$curr_posttype = self::$options['general']['force_product'];
				if ( $curr_posttype == 'no' ) {
					if ( !isset( $pf_activated['s'] ) && get_option( 'permalink_structure' ) == '' && ( is_shop() || is_product_taxonomy() ) ) {
						echo '<input type="hidden" name="post_type" value="product" />';
					}
				}
				else {
					echo '<input type="hidden" name="post_type" value="product" />';
				}

				if ( isset( $pf_activated['products_per_page'] ) ) {
					echo '<input type="hidden" name="products_per_page" value="' . absint( $pf_activated['products_per_page'] ) . '"  class="pf_added_input" />';
				}

				do_action( 'prdctfltr_add_inputs' );
			?>
			</div>
		<?php
			}

		}

		function prdctfltr_scripts() {
			wp_enqueue_style( 'prdctfltr', self::$url_path .'includes/css/styles' . ( is_rtl() ? '-rtl' : '' ) . '.css', false, self::$version );

			wp_register_script( 'prdctfltr-main-js', self::$url_path .'includes/js/prdctfltr.js', array( 'jquery', 'hoverIntent' ), self::$version, true );
			wp_enqueue_script( 'prdctfltr-main-js' );
		}

		function localize_scripts() {

			global $prdctfltr_global;

			if ( !isset( $prdctfltr_global['init'] ) ) {
				wp_dequeue_script( 'prdctfltr-main-js' );
			}
			else if ( wp_script_is( 'prdctfltr-main-js', 'enqueued' ) ) {

				global $wp_rewrite;

				$curr_args = apply_filters( 'prdctfltr_localize_javascript', array(
					'ajax' => admin_url( 'admin-ajax.php' ),
					'wc_ajax' => WC()->ajax_url(),
					'url' => self::$url_path,
					'rtl' => is_rtl(),
					'page_rewrite' => $wp_rewrite->pagination_base,
					'js' => stripslashes( self::$options['install']['ajax']['js'] ),
					'use_ajax' => self::$options['install']['ajax']['enable'],
					'ajax_class' => self::$options['install']['ajax']['wrapper'],
					'ajax_category_class' => self::$options['install']['ajax']['category'],
					'ajax_product_class' => self::$options['install']['ajax']['product'],
					'ajax_pagination_class' => self::$options['install']['ajax']['pagination'],
					'ajax_count_class' => self::$options['install']['ajax']['result_count'],
					'ajax_orderby_class' => self::$options['install']['ajax']['order_by'],
					'ajax_pagination_type' => self::$options['install']['ajax']['pagination_type'],
					'ajax_animation' => self::$options['install']['ajax']['animation'],
					'ajax_scroll' => self::$options['install']['ajax']['scroll_to'],
					'analytics' => self::$options['general']['analytics'],
					'clearall' => isset( self::$options['general']['clear_all'] ) ? self::$options['general']['clear_all'] : false,
					'permalinks' => self::$options['install']['ajax']['permalinks'],
					'ajax_failsafe' => self::$options['install']['ajax']['failsafe'],
					'localization' => array(
						'close_filter' => esc_html__( 'Close filter', 'prdctfltr' ),
						'filter_terms' => esc_html__( 'Filter terms', 'prdctfltr' ),
						'ajax_error' => esc_html__( 'AJAX Error!', 'prdctfltr' ),
						'show_more' => esc_html__( 'Show more', 'prdctfltr' ),
						'show_less' => esc_html__( 'Show less', 'prdctfltr' ),
						'noproducts' => esc_html__( 'No products found!', 'prdctfltr' ),
						'clearall' => esc_html__( 'Clear filters', 'prdctfltr' ),
						'getproducts' => esc_html__( 'Show products', 'prdctfltr' ),
						'outofstock' => esc_html__( 'Out of stock', 'prdctfltr' ),
						'backorder' => esc_html__( 'On backorder', 'prdctfltr' ),
					),
					'js_filters' => ( isset( $prdctfltr_global['filter_js'] ) ? $prdctfltr_global['filter_js'] : array() ),
					'pagefilters' => ( isset( $prdctfltr_global['pagefilters'] ) ? $prdctfltr_global['pagefilters'] : array() ),
					'rangefilters' => ( isset( $prdctfltr_global['ranges'] ) ? $prdctfltr_global['ranges'] : array() ),
					'orderby' => ( isset( $prdctfltr_global['default_order']['orderby'] ) ? $prdctfltr_global['default_order']['orderby'] : '' ),
					'order' => ( isset( $prdctfltr_global['default_order']['order'] ) ? $prdctfltr_global['default_order']['order'] : '' ),
					'active_sc' => ( isset( XforWC_Product_Filters_Shortcodes::$settings['sc'] ) ? XforWC_Product_Filters_Shortcodes::$settings['sc'] : '' ),
					'animation' => array(
						'delay' => 100,
						'duration' => 300
					),
					'active_filtering' => array(
						'active' => empty( $prdctfltr_global['active_filters'] ) ? false : $prdctfltr_global['active_filters'],
						'variable' => $this->get_variable_products_helper(),
					)
				) );

				wp_localize_script( 'prdctfltr-main-js', 'prdctfltr', $curr_args );

			}

		}

		public static function make_global( $set, $query = array() ) {

			global $prdctfltr_global;

			if ( isset( $prdctfltr_global['mobile'] ) ) {
				return true;
			}

			if ( 1==1 ) :

			$stop = false;

			if ( $stop === false ) {

				$taxonomies = array();
				$taxonomies_data = array();
				$permalink_taxonomies = array();
				$permalink_taxonomies_data = array();
				$misc = array();
				$rng_terms = array();
				$mta_terms = array();
				$rng_for_activated = array();
				$mtarn_for_activated = array();
				$mtar_for_activated = array();
				$mta_for_activated = array();
				$mta_for_array = array();

				$product_taxonomies = get_object_taxonomies( 'product' );
				if ( ( $product_type = array_search( 'product_type', $product_taxonomies ) ) !== false ) {
					unset( $product_taxonomies[$product_type] );
				}

				$sc_args = array();

				$prdctfltr_global['taxonomies'] = $product_taxonomies;

				if ( isset( $prdctfltr_global['sc_query'] ) && is_array( $prdctfltr_global['sc_query'] ) ) {
					foreach( $prdctfltr_global['sc_query'] as $sck => $scv ) {
						if ( in_array( $sck, $product_taxonomies ) ) {
							continue;
						}
						$sc_args[$sck] = $scv;
					}
				}

				$set = array_merge( $sc_args, $set );

				if ( isset( $set ) && !empty( $set ) ) {

					$get = $set;

					if ( isset( $get['search_products'] ) && !empty( $get['search_products'] ) && !isset( $get['s'] ) ) {
						$get['s'] = $get['search_products'];
					}

					$allowed = array( 'orderby', 'product_order', 'order', 'product_sort', 'min_price', 'max_price', 'instock_products', 'sale_products', 'products_per_page', 'product_count', 's', 'vendor', 'rating_filter' );

					foreach( $get as $k => $v ){
						if ( $v == '' ) {
							continue;
						}

						if ( in_array( $k, $allowed ) ) {
							if ( $k == 'order' || $k == 'product_sort' ) {
								$misc['order'] = ( strtoupper( $v ) == 'DESC' ? 'DESC' : 'ASC' );
							}
							else if ( $k == 'orderby' || $k == 'product_order' ) {
								$misc['orderby'] = strtolower( $v );
							}
							else if ( in_array( $k, array( 'products_per_page', 'product_count' ) ) ) {
								$misc['products_per_page'] = intval( $v );
							}
							else if ( in_array( $k, array( 'min_price', 'max_price' ) ) ) {
								$misc[$k] = $v == '' ? '' : floatval( $v );
							}
							else {
								$misc[$k] = $v;
							}
						}
						else if ( taxonomy_exists( $k ) || substr( $k, 0, 7 ) == 'filter_' ) {

							if ( strpos( $v, ',' ) ) {
								$selected = explode( ',', $v );
								$taxonomies_data[$k . '_relation'] = 'IN';
							}
							else if ( strpos( $v, '+' ) ) {
								$selected = explode( '+', $v );
								$taxonomies_data[$k . '_relation'] = 'AND';
							}
							else if ( strpos( $v, ' ' ) ) {
								$selected = explode( ' ', $v );
								$taxonomies_data[$k . '_relation'] = 'AND';
							}
							else {
								$selected = array( $v );
							}

							if ( substr( $k, 0, 3 ) == 'pa_' ) {
								$f_attrs[] = 'attribute_' . $k;

								foreach( $selected as $val ) {
									if ( term_exists( $val, $k ) !== null ) {
										$taxonomies[$k][] = $val;
										$f_terms[] = self::prdctfltr_utf8_decode($val);
									}
								}
							}
							else if ( substr( $k, 0, 7 ) == 'filter_' ) {
								$k = 'pa_' . substr( $k, 7 );
								$f_attrs[] = 'attribute_' . $k;

								foreach( $selected as $val ) {
									if ( term_exists( $val, $k ) !== null ) {
										$taxonomies[$k][] = $val;
										$f_terms[] = self::prdctfltr_utf8_decode($val);
									}
								}
							}
							else {
								foreach( $selected as $val ) {
									if ( term_exists( $val, $k ) !== null ) {
										$taxonomies[$k][] = $val;
									}
								}
							}

							if ( !empty( $taxonomies[$k] ) ) {
								if ( isset( $taxonomies_data[$k . '_relation'] ) && $taxonomies_data[$k . '_relation'] == 'AND' ){
									$taxonomies_data[$k . '_string'] = implode( '+', $taxonomies[$k] );
								}
								else {
									$taxonomies_data[$k . '_string'] = implode( ',', $taxonomies[$k] );
								}
							}

						}
						else if ( substr($k, 0, 4) == 'rng_' ) {

							if ( substr($k, 0, 8) == 'rng_min_' ) {
								$rng_for_activated[$k] = ( $k == 'rng_min_price' ? floatval( $v ): $v );
								$rng_terms[str_replace('rng_min_', '', $k)]['min'] = $v;
							}
							else if ( substr($k, 0, 8) == 'rng_max_' ) {
								$rng_for_activated[$k] = ( $k == 'rng_max_price' ? floatval( $v ): $v );
								$rng_terms[str_replace('rng_max_', '', $k)]['max'] = $v;
							}
							else if ( substr($k, 0, 12) == 'rng_orderby_' ) {
								$rng_terms[str_replace('rng_orderby_', '', $k)]['orderby'] = $v;
							}
							else if ( substr($k, 0, 10) == 'rng_order_' ) {
								$rng_terms[str_replace('rng_order_', '', $k)]['order'] = ( strtoupper( $v ) == 'DESC' ? 'DESC' : 'ASC' );
							}

						}
						else if ( preg_match( '#^' . apply_filters( 'prdctfltr_meta_range_numeric_key_prefix', 'mtarn_' ) . '#i', $k ) === 1  ) {
							$mtarn_for_activated[$k] = explode( ',', $v );

							$mta_key = esc_attr( substr( $k, strlen( apply_filters( 'prdctfltr_meta_range_numeric_key_prefix', 'mtarn_' ) ) ) );
							$mta_type = 'DECIMAL';
							$mta_compare = 'BETWEEN';

							$mta_terms[] = array(
								'key' => $mta_key,
								'type' => $mta_type,
								'compare' => $mta_compare,
								'value' => $mtarn_for_activated[$k]
							);
						}
						else if ( preg_match( '#^' . apply_filters( 'prdctfltr_meta_range_key_prefix', 'mtar_' ) . '#i', $k ) === 1  ) {
							$mtar_for_activated[$k] = explode( ',', $v );

							$mta_key = esc_attr( substr( $k, strlen( apply_filters( 'prdctfltr_meta_range_numeric_key_prefix', 'mtar_' ) ) ) );
							$mta_type = 'CHAR';
							$mta_compare = 'IN';

							$mta_terms[] = array(
								'key' => $mta_key,
								'type' => $mta_type,
								'compare' => $mta_compare,
								'value' => $mtar_for_activated[$k]
							);
						}
						else if ( preg_match('#^' . apply_filters( 'prdctfltr_meta_key_prefix', 'mta_' ) . '#i', $k) === 1 ) {

							$mta_key = esc_attr( substr($k, strlen( apply_filters( 'prdctfltr_meta_key_prefix', 'mta_' ) ), -5) );
							$mta_type = self::get_meta_type( substr($k, -4, 1) );
							$mta_compare = self::get_meta_compare( substr($k, -2, 2) );

							if ( strpos( $v, ',' ) ) {
								$mta_selected = array_map( 'esc_attr', explode( ',', $v ));
								$mta_relation = 'OR';
							}
							else if ( strpos( $v, '+' ) ) {
								$mta_selected = array_map( 'esc_attr', explode( '+', $v ) );
								$mta_relation = 'AND';
							}
							else {
								$mta_selected = esc_attr( $v );
							}

							$mta_for_activated[$k] = $v;
							$mta_for_array[$k] = is_array( $mta_selected ) ? $mta_selected : array( $mta_selected );
							if ( is_array( $mta_selected ) ) {
								$mta_terms['relation'] = $mta_relation;
								foreach( $mta_selected as $mta_sngl ) {
									if ( strpos( $mta_compare, 'BETWEEN') > -1 && strpos( $mta_sngl, '-' ) ) {
										$mta_sngl = explode( '-', $mta_sngl );
									}
									$mta_terms[] = array(
										'key' => $mta_key,
										'type' => $mta_type,
										'compare' => $mta_compare,
										'value' => $mta_sngl
									);
								}
							}
							else {
								if ( strpos( $mta_compare, 'BETWEEN') > -1 && strpos( $mta_selected, apply_filters( 'prdctfltr_meta_key_between_separator', '-' ) ) ) {
									$mta_selected = explode( apply_filters( 'prdctfltr_meta_key_between_separator', '-' ), $mta_selected );
								}
								$mta_terms[] = array(
									'key' => $mta_key,
									'type' => $mta_type,
									'compare' => $mta_compare,
									'value' => $mta_selected
								);
							}

						}

					}

					if ( !empty( $rng_terms ) ) {

						foreach ( $rng_terms as $rng_name => $rng_inside ) {

							if ( !in_array( $rng_name, array( 'price' ) ) ) {

								if ( ( isset( $rng_inside['min'] ) && isset( $rng_inside['max'] ) ) === false || !taxonomy_exists( $rng_name ) ) {
									unset( $rng_terms[$rng_name] );
									unset( $rng_for_activated['rng_min_' . $rng_name] );
									unset( $rng_for_activated['rng_max_' . $rng_name] );
									continue;
								}

								if ( isset( $rng_terms[$rng_name]['orderby'] ) && $rng_terms[$rng_name]['orderby'] == 'number' ) {
									$curr_term_args = array(
										'hide_empty' => self::$options['general']['hide_empty'] == 'yes' ? 1 : 0,
										'orderby' => 'slug'
									);
									$pf_terms = self::prdctfltr_get_terms( $rng_name, $curr_term_args );
									$pf_sort_args = array(
										'order' => isset( $rng_terms[$rng_name]['order'] ) ? $rng_terms[$rng_name]['order'] : ''
									);
									$pf_terms = self::prdctfltr_sort_terms_naturally( $pf_terms, $pf_sort_args );
								}
								else {
									$curr_term_args = array(
										'hide_empty' => self::$options['general']['hide_empty'] == 'yes' ? 1 : 0,
										'orderby' => isset( $rng_terms[$rng_name]['orderby'] ) ? $rng_terms[$rng_name]['orderby'] : '',
										'order' => isset( $rng_terms[$rng_name]['order'] ) ? $rng_terms[$rng_name]['order'] : ''
									);
									$pf_terms = self::prdctfltr_get_terms( $rng_name, $curr_term_args );
								}

								if ( empty( $pf_terms ) ) {
									continue;
								}

								$rng_found = false;

								$curr_ranges = array();

								foreach ( $pf_terms as $c => $s ) {
									if ( $rng_found == true ) {
										$curr_ranges[] = $s->slug;
										if ( $s->slug == $rng_inside['max'] ) {
											$rng_found = false;
											continue;
										}
									}
									if ( $s->slug == $rng_inside['min'] && $rng_found === false ) {
										$rng_found = true;
										$curr_ranges[] = $s->slug;
									}
								}

								$taxonomies[$rng_name] = $curr_ranges;
								$taxonomies_data[$rng_name.'_string'] = implode( ',', $curr_ranges );
								$taxonomies_data[$rng_name.'_relation'] = 'IN';

								if ( substr( $rng_name, 0, 3 ) == 'pa_' ) {
									$f_attrs[] = 'attribute_' . $rng_name;

									foreach ( $curr_ranges as $cr ) {
										$f_terms[] = $cr;
									}
								}

							}
							else {
								if ( !isset( $rng_inside['min'] ) || !isset( $rng_inside['max'] ) || ( $rng_inside['min'] < $rng_inside['max'] ) === false ) {
									unset( $rng_terms[$rng_name] );
									unset( $rng_for_activated['rng_min_' . $rng_name] );
									unset( $rng_for_activated['rng_max_' . $rng_name] );
								}
							}

						}

					}

				}

				if ( is_product_taxonomy() || isset( $prdctfltr_global['sc_query'] ) && !empty( $prdctfltr_global['sc_query'] ) ) {

					$check_links = apply_filters( 'prdctfltr_check_permalinks', $product_taxonomies );

					foreach( $check_links as $check_link ) {

						$curr_link = false;
						$pf_helper = array();
						$pf_helper_real = array();
						$is_attribute = substr( $check_link, 0, 3 ) == 'pa_' ? true : false;


						if ( !isset( $set[$check_link] ) && ( $curr_var = get_query_var( $check_link ) ) !== '' ) {
							$curr_link = $curr_var;
						}
						else if ( isset( $prdctfltr_global['sc_query'][$check_link] ) && $prdctfltr_global['sc_query'][$check_link] !== '' ) {
							$curr_link = $prdctfltr_global['sc_query'][$check_link];
						}

						else {
							$curr_link = false;
						}

						if ( $curr_link ) {

							if ( !is_array( $curr_link ) ) {
								if ( strpos( $curr_link, ',' ) ) {
									$pf_helper = explode( ',', $curr_link );
									$permalink_taxonomies_data[$check_link.'_relation'] = 'IN';
								}
								else if ( strpos( $curr_link, '+' ) ) {
									$pf_helper = explode( '+', $curr_link );
									$permalink_taxonomies_data[$check_link.'_relation'] = 'AND';
								}
								else if ( strpos( $curr_link, ' ' ) ) {
									$pf_helper = explode( ' ', $curr_link );
									$permalink_taxonomies_data[$check_link.'_relation'] = 'AND';
								}
								else {
									$pf_helper = array( $curr_link );
								}
							}
							else {
								$pf_helper = $curr_link;
							}

							foreach( $pf_helper as $val ) {
								if ( term_exists( $val, $check_link ) !== null ) {
									$pf_helper_real[] = $val;
									if ( $is_attribute ) {
										$f_terms[] = self::prdctfltr_utf8_decode($val);
									}
								}
							}

							if ( !empty( $pf_helper_real ) ) {
								$permalink_taxonomies[$check_link] = $pf_helper_real;

								if ( $is_attribute ) {
									$f_attrs[] = 'attribute_' . $check_link;
								}
								if ( isset( $permalink_taxonomies_data[$check_link . '_relation'] ) && $permalink_taxonomies_data[$check_link . '_relation'] == 'AND' ){
									$permalink_taxonomies_data[$check_link . '_string'] = implode( '+', $pf_helper_real );
								}
								else {
									$permalink_taxonomies_data[$check_link . '_string'] = implode( ',', $pf_helper_real );
								}
							}

						}

					}

				}

				if ( isset( $misc['order'] ) && !isset( $misc['orderby'] ) ) {
					unset( $misc['order'] );
				}

				$prdctfltr_global['done_filters'] = true;
				$prdctfltr_global['taxonomies_data'] = $taxonomies_data;
				$prdctfltr_global['active_taxonomies'] = $taxonomies;
				$prdctfltr_global['active_misc'] = $misc;
				$prdctfltr_global['range_filters'] = $rng_terms;
				$prdctfltr_global['meta_filters'] = $mta_terms;
				$prdctfltr_global['meta_data'] = $mta_for_activated;
				$prdctfltr_global['active_filters'] = array_merge( $prdctfltr_global['active_taxonomies'], $prdctfltr_global['active_misc'], $rng_for_activated, $mta_for_array, $mtarn_for_activated, $mtar_for_activated );

				$prdctfltr_global['active_permalinks'] = array_merge( $permalink_taxonomies, $prdctfltr_global['active_taxonomies'] );
				$prdctfltr_global['permalinks_data'] = array_merge( $permalink_taxonomies_data, $prdctfltr_global['taxonomies_data'] );

				if ( !empty( $prdctfltr_global['active_permalinks'] ) && ( is_shop() || is_product_taxonomy() ) ) {
					$prdctfltr_global['sc_query'] = $prdctfltr_global['active_permalinks'];
				}

				if ( !empty( $misc ) || !empty( $rng_for_activated ) || !empty( $mta_for_array ) || count( $taxonomies ) == 1 && !isset( $taxonomies['product_cat'] ) || count( $taxonomies ) > 1 ) {
					add_filter( 'woocommerce_is_filtered', 'XforWC_Product_Filters_Frontend::return_true' );
				}

				$prdctfltr_global['active_in_filter'] = $prdctfltr_global['active_filters'];
				if ( isset( $prdctfltr_global['sc_query'] ) && !is_array( $prdctfltr_global['sc_query'] ) ) {
					foreach ( $check_links as $check_link ) {
						if ( isset( $prdctfltr_global['sc_query'][$check_link] ) && isset( $prdctfltr_global['active_in_filter'][$check_link] ) && $prdctfltr_global['sc_query'][$check_link] == $prdctfltr_global['active_in_filter'][$check_link] ) {
							unset( $prdctfltr_global['active_in_filter'][$check_link] );
						}
						
					}
				}

				$prdctfltr_global['pf_activated'] = array_merge( $prdctfltr_global['active_in_filter'], $prdctfltr_global['active_permalinks'] );
				self::$options['activated'] = $prdctfltr_global['pf_activated'];

				if ( isset( $f_attrs ) ) {
					$prdctfltr_global['f_attrs'] = $f_attrs;
				}

				if ( isset( $f_terms ) ) {
					$prdctfltr_global['f_terms'] = $f_terms;
				}

				$pf_activated = $prdctfltr_global['active_taxonomies'];
				$pf_tax_query = array();

				if ( !empty( $pf_activated ) || !empty( $prdctfltr_global['active_permalinks'] ) ) {

					foreach ( $pf_activated as $k => $v ) {
						$relation = isset( $prdctfltr_global['taxonomies_data'][$k . '_relation'] ) && $prdctfltr_global['taxonomies_data'][$k.'_relation'] == 'AND' ? 'AND' : 'IN';
						if ( count( $v ) > 1 ) {
							if ( $relation == 'AND' ) {
								$precompile = array();
								foreach( $v as $k12 => $v12 ) {

									$asked_term = get_term_by( 'slug', $v12, $k );
									$child_terms = get_term_children( $asked_term->term_id, $k );

									if ( !empty( $child_terms ) ) {
										$precompile[] = array( 'taxonomy' => $k, 'field' => 'term_id', 'terms' => array_merge( $child_terms, array( $asked_term->term_id ) ), 'include_children' => false, 'operator' => 'IN' );
									}
									else {
										$precompile[] = array( 'taxonomy' => $k, 'field' => 'slug', 'terms' => $v12, 'include_children' => false, 'operator' => 'IN' );
									}
								}

								$precompile['relation'] = 'AND';

								$pf_tax_query[] = $precompile;
							}
							else {
								$pf_tax_query[] = array( 'taxonomy' => $k, 'field' => 'slug', 'terms' => $v, 'include_children' => true, 'operator' => 'IN' );
							}
						}
						else {
							$pf_tax_query[] = array( 'taxonomy' => $k, 'field' => 'slug', 'terms' => $v, 'include_children' => true, 'operator' => 'IN' );
						}
					}

					$pf_permalinks = $prdctfltr_global['active_permalinks'];

					foreach ( $pf_permalinks as $k => $v ) {
						$relation = isset( $prdctfltr_global['permalinks_data'][$k . '_relation'] ) && $prdctfltr_global['permalinks_data'][$k . '_relation'] == 'AND' ? 'AND' : 'IN';
						if ( count( $v ) > 1 ) {
							if ( $relation == 'AND' ) {
								$precompile = array();
								foreach( $v as $k12 => $v12 ) {

									$asked_term = get_term_by( 'slug', $v12, $k );
									$child_terms = get_term_children( $asked_term->term_id, $k );

									if ( !empty( $child_terms ) ) {
										$precompile[] = array( 'taxonomy' => $k, 'field' => 'term_id', 'terms' => array_merge( $child_terms, array( $asked_term->term_id ) ), 'include_children' => false, 'operator' => 'IN' );
									}
									else {
										$precompile[] = array( 'taxonomy' => $k, 'field' => 'slug', 'terms' => $v12, 'include_children' => false, 'operator' => 'IN' );
									}
								}

								$precompile['relation'] = 'AND';

								$pf_tax_query[] = $precompile;
							}
							else {
								$pf_tax_query[] = array( 'taxonomy' => $k, 'field' => 'slug', 'terms' => $v, 'include_children' => true, 'operator' => 'IN' );
							}
						}
						else {
							$pf_tax_query[] = array( 'taxonomy' => $k, 'field' => 'slug', 'terms' => $v, 'include_children' => true, 'operator' => 'IN' );
						}
					}

				}

				$active = $prdctfltr_global['active_filters'];

				if ( !isset($active['instock_products']) && isset(XforWC_Product_Filters_Shortcodes::$settings['sc_instock']) && in_array( XforWC_Product_Filters_Shortcodes::$settings['sc_instock'], array( 'in', 'out', 'both' ) ) ) {
					$active['instock_products'] = XforWC_Product_Filters_Shortcodes::$settings['sc_instock'];
				}

				if ( ( ( ( isset( $active['instock_products'] ) && $active['instock_products'] !== '' && ( $active['instock_products'] == 'in' || $active['instock_products'] == 'out' ) ) || get_option( 'woocommerce_hide_out_of_stock_items', 'no' ) == 'yes' ) !== false ) && ( !isset( $active['instock_products'] ) || $active['instock_products'] !== 'both' ) ) {
					$operator = isset( $active['instock_products'] ) && $active['instock_products'] == 'out' ? 'IN' : 'NOT IN';
				}

				if ( isset( $operator ) ) {
					$pf_tax_query[] = array( 'taxonomy' => 'product_visibility', 'field' => 'slug', 'terms' => array( 'outofstock' ), 'operator' => $operator );
				}
				$pf_tax_query[] = array( 'taxonomy' => 'product_visibility', 'field' => 'slug', 'terms' => array( 'exclude-from-' . ( isset( $active['s'] ) ? 'search' : 'catalog' ) ), 'operator' => 'NOT IN' );

				if ( isset( $active['rating_filter'] ) && in_array( $active['rating_filter'], array( 1,2,3,4,5 ) ) ) {
					$pf_tax_query[] = array( 'taxonomy' => 'product_visibility', 'field' => 'slug', 'terms' => array( sprintf( 'rated-%s', intval( $active['rating_filter'] ) ) ), 'operator' => 'IN' );
				}

				if ( !empty( $pf_tax_query ) ) {
					self::$settings['tax_query'] = $pf_tax_query;
					$prdctfltr_global['tax_query'] = $pf_tax_query;
				}

			}

			endif;

		}

		public static function outofstock_show( $visible, $id ) {
			global $prdctfltr_global;
			if ( isset( $prdctfltr_global['active_filters']['instock_products'] ) && in_array( $prdctfltr_global['active_filters']['instock_products'], array( 'both', 'out', 'in' ) ) ) {
				return true;
			}
			return $visible;
		}

		public static function get_min_max_price_meta_query( $args ) {

			$min = isset( $args['min_price'] ) ? floatval( $args['min_price'] ) : 0;
			$max = isset( $args['max_price'] ) ? floatval( $args['max_price'] ) : 9999999999;

			if ( wc_tax_enabled() && 'incl' === get_option( 'woocommerce_tax_display_shop' ) && !wc_prices_include_tax() ) {
				$tax_classes = array_merge( array( '' ), WC_Tax::get_tax_classes() );
				$class_min   = $min;

				foreach ( $tax_classes as $tax_class ) {
					if ( $tax_rates = WC_Tax::get_rates( $tax_class ) ) {
						$class_min = $min - WC_Tax::get_tax_total( WC_Tax::calc_exclusive_tax( $min, $tax_rates ) );
					}
				}

				$min = $class_min;
			}

			return array(
				'key'     => '_price',
				'value'   => array( $min, $max ),
				'compare' => 'BETWEEN',
				'type'    => 'DECIMAL(10,' . wc_get_price_decimals() . ')',
			);
		}

		public static function sc_wc_query( $query ) {
			if ( isset( $query->query_vars['prdctfltr_active'] ) ) {
				call_user_func( 'XforWC_Product_Filters_Frontend::prdctfltr_wc_query', $query, array() );
			}
		}
		public static function sc_wc_tax( $query ) {
			if ( isset( $query->query_vars['prdctfltr_active'] ) ) {
				call_user_func( 'XforWC_Product_Filters_Frontend::prdctfltr_wc_tax', $query, array() );
			}
		}

		public static function prdctfltr_wc_query( $query, $that ) {

			if ( !wp_doing_ajax() && current_filter() == 'woocommerce_product_query' ) {
				self::get_vars( $query, array() );
				self::make_global( $_REQUEST, $query );
			}

			global $prdctfltr_global;

			$stop = true;

			$curr_args = array();
			$f_attrs = array();
			$f_terms = array();
			$rng_terms = array();

			if ( isset( $prdctfltr_global['active_filters'] ) ) {

				$pf_activated =  $prdctfltr_global['active_filters'];

				if ( isset( $prdctfltr_global['range_filters'] ) ) {
					$rng_terms = $prdctfltr_global['range_filters'];
				}

				if ( isset( $prdctfltr_global['f_attrs'] ) ) {

					$f_attrs = $prdctfltr_global['f_attrs'];

					if ( isset( $prdctfltr_global['f_terms'] ) ) {
						$f_terms = $prdctfltr_global['f_terms'];
					}

				}

			}

			if ( wp_doing_ajax() && !isset( $prdctfltr_global['sc_init'] ) || isset( $prdctfltr_global['sc_init'] ) && isset( $pf_activated['orderby'] ) && $pf_activated['orderby'] !== '' ) {

				$orderby = '';
				$order = '';

				$default_order = apply_filters( 'woocommerce_default_catalog_orderby', get_option( 'woocommerce_default_catalog_orderby' ) );
				$default_explode = explode( '-', $default_order );
				$dfltOrderBy       = esc_attr( $default_explode[0] );
				$dfltOrder         = ( isset( $default_explode[1] ) && !empty( $default_explode[1] ) ? $default_explode[1] : '' );
				$prdctfltr_global['default_order']['orderby'] = isset( $dfltOrderBy ) ? $dfltOrderBy : '';
				$prdctfltr_global['default_order']['order'] = isset( $dfltOrder ) ? $dfltOrder : '';

				$orderby_value = isset( $pf_activated['orderby'] ) ? wc_clean( (string) $pf_activated['orderby'] ) : $default_order;
				$orderby_value = explode( '-', $orderby_value );
				$orderby       = esc_attr( $orderby_value[0] );
				$order         = isset( $pf_activated['order'] ) && !empty( $pf_activated['order'] ) ? ( $pf_activated['order'] == 'DESC' ? 'DESC' : 'ASC' ) : ( isset( $orderby_value[1] ) && !empty( $orderby_value[1] ) ? $orderby_value[1] : '' );


				$orderby = strtolower( $orderby );
				$order   = strtoupper( $order );

				switch ( $orderby ) {

					case 'rand' :
						$curr_args['orderby']  = 'rand';
					break;
					case 'date' :
					case 'date ID' :
						$curr_args['orderby']  = 'date';
						$curr_args['order']    = $order == 'ASC' ? 'ASC' : 'DESC';
					break;
					case 'price' :
						if ( 'DESC' === $order ) {
							add_filter( 'posts_clauses', array( WC()->query, 'order_by_price_desc_post_clauses' ) );
						} else {
							add_filter( 'posts_clauses', array( WC()->query, 'order_by_price_asc_post_clauses' ) );
						}
					break;
					case 'popularity' :
						$curr_args['meta_key'] = 'total_sales';
						add_filter( 'posts_clauses', array( WC()->query, 'order_by_popularity_post_clauses' ) );
					break;
					case 'rating' :
						$curr_args['orderby']  = array( "meta_value_num" => "DESC", "ID" => "ASC" );
						$curr_args['order']  = "ASC";
						$curr_args['meta_key'] = '_wc_average_rating';
					break;
					case 'title' :
						$curr_args['orderby']  = 'title';
						$curr_args['order']    = $order == 'DESC' ? 'DESC' : 'ASC';
					break;
					case 'menu_order' :
					case 'menu_order title' :
					case '' :
						$curr_args['orderby'] = 'menu_order title';
						$curr_args['order'] = $order == 'DESC' ? 'DESC' : 'ASC';
					break;
					case 'comment_count' :
						$curr_args['orderby'] = 'comment_count';
						$curr_args['order']   = $order == 'ASC' ? 'ASC' : 'DESC';
					break;
					default :
						$curr_args['orderby'] = $orderby;
						$curr_args['order']   = $order == 'ASC' ? 'ASC' : 'DESC';
					break;

				}

			}

			if ( !empty( $pf_activated ) && has_filter( 'post_clauses', array( 'WC_Query', 'price_filter_post_clauses') ) === false ) {
				add_filter( 'posts_clauses', 'XforWC_Product_Filters_Frontend::price_filter_post_clauses', 10, 2 );
			}

			if ( ( isset( $pf_activated['sale_products'] ) || isset( $query->query_vars['sale_products'] ) ) !== false ) {
				$curr_args['post__in'] = isset( $curr_args['post__in'] ) ? array_merge( $curr_args['post__in'], wc_get_product_ids_on_sale() ) : wc_get_product_ids_on_sale();
			}

			if ( isset( $pf_activated['products_per_page'] ) && $pf_activated['products_per_page'] !== '' ) {
				$curr_args = array_merge( $curr_args, array(
					'posts_per_page' => floatval( $pf_activated['products_per_page'] )
				) );
			}

			if ( isset( $pf_activated['s'] ) && $pf_activated['s'] !== '' ) {
				$curr_args = array_merge( $curr_args, array(
					's' => $pf_activated['s']
				) );
			}

			if ( isset( $pf_activated['vendor'] ) && $pf_activated['vendor'] !== '' ) {
				$curr_args = array_merge( $curr_args, array(
					'author' => $pf_activated['vendor']
				) );
			}

			if ( isset( $prdctfltr_global['meta_filters'] ) ) {
				$product_metas = self::unconvert_price_filter_limits( apply_filters( 'prdctfltr_meta_query', $prdctfltr_global['meta_filters'] ) );

				if ( !empty( $product_metas ) ) {
					$curr_args['meta_query']['relation'] = 'AND';
					$curr_args['meta_query'][] = $product_metas;
					$checkMeta = isset( $query->query_vars['meta_query'] ) ? $query->query_vars['meta_query'] : array() ;
					if ( !empty( $checkMeta ) ) {
						foreach( $checkMeta as $mk => $mv ) {
							if ( $mk == 'price_filter' || is_array( $mv ) && key( $mv ) == 'price_filter' ) {
								unset($checkMeta[$mk]);
							}
						}
					}
					$curr_args['meta_query'][] = $checkMeta;
				}
			}

			if ( !isset($pf_activated['instock_products']) && isset($query->query_vars['instock_products']) && in_array( $query->query_vars['instock_products'], array( 'in', 'out', 'both' ) ) ) {
				$pf_activated['instock_products'] = $query->query_vars['instock_products'];
			}

			if ( ( ( ( isset( $pf_activated['instock_products'] ) && $pf_activated['instock_products'] !== '' && ( $pf_activated['instock_products'] == 'in' || $pf_activated['instock_products'] == 'out' ) ) || get_option( 'woocommerce_hide_out_of_stock_items', 'no' ) == 'yes' ) !== false ) && ( !isset( $pf_activated['instock_products'] ) || $pf_activated['instock_products'] !== 'both' ) ) {

				$cfa = count($f_attrs);

				if ( $cfa > 0 ) {

					$curr_atts =  implode( '","', array_map( 'esc_sql', $f_attrs ) );
					$curr_terms = implode( '","', array_map( 'esc_sql', $f_terms ) );

					$variableStockOut = array();
					for ( $i = 1; $i <= $cfa; $i++ ) {
						$variableStockOut = array_merge( $variableStockOut, self::__get_variable_product_outofstock( $curr_atts, $curr_terms, $i) );
					}

					if ( !empty( $variableStockOut ) ) {
						$variableStockOutFil = array();

						foreach ( $variableStockOut as $k => $p ) {
							if ( !in_array( $p[0], $variableStockOutFil ) ) {
								$variableStockOutFil[] = $p[0];
							}
						}

						self::$settings['variable_outofstock'] = $variableStockOutFil;
						
						if ( isset( $pf_activated['instock_products'] ) && $pf_activated['instock_products'] == 'out' ) {
							add_filter( 'posts_where' , array( 'XforWC_Product_Filters_Frontend', 'prdctfltr_add_variable_outofstock' ), 99998 );
						}
						else {
							$curr_args = array_merge( $curr_args, array( 'post__not_in' => $variableStockOutFil ) );
						}
						
					}

				}
			}

			foreach ( $curr_args as $k => $v ) {
				switch( $k ) {
					case 'post__in' :
						$v = array_unique( $v );
						$postIn = isset( $query->query_vars[$k] ) && !empty( $query->query_vars[$k] ) ? $query->query_vars[$k] : array();
						$ins = ( empty( $postIn ) ? $v : array_intersect( $postIn, $v ) );

						if ( isset( $variableStockOutFil ) ) {
							$ins = array_diff( $ins, $variableStockOutFil );
						}

						$query->set( $k, $ins );
					break;
					default:
						$query->set( $k, $v );
					break;
				}
			}

		}

		public static function __get_variable_product_outofstock( $curr_atts, $curr_terms, $int ) {

			global $wpdb;

			$outofstock = get_term_by( 'slug', 'outofstock', 'product_visibility' );

			return $wpdb->get_results( sprintf( '
				SELECT DISTINCT(%1$s.post_parent) as ID FROM %1$s
				INNER JOIN %2$s AS pf1 ON (%1$s.ID = pf1.post_id)
				INNER JOIN %3$s ON (%1$s.ID = %3$s.object_id)
				WHERE %1$s.post_type = "product_variation"
				AND pf1.meta_key IN ("' . $curr_atts . '") AND pf1.meta_value IN ("' . $curr_terms . '","")
				AND ( %1$s.ID IN ( SELECT object_id FROM %3$s WHERE term_taxonomy_id IN ( ' . $outofstock->term_id  . ' ) ) )
				AND ( %1$s.ID IN ( SELECT post_id FROM %2$s WHERE meta_key LIKE "attribute_pa_%%" GROUP BY post_id HAVING COUNT( DISTINCT meta_key ) = ' . $int . ' ) )
				GROUP BY pf1.post_id
				HAVING COUNT(DISTINCT pf1.meta_key) = ' . $int .'
				LIMIT 29999
			', $wpdb->posts, $wpdb->postmeta, $wpdb->term_relationships ), ARRAY_N );

		}

		public static function append_product_sorting_table_join( $sql ) {
			global $wpdb;
	
			if ( !strstr( $sql, 'wc_product_meta_lookup' ) ) {
				$sql .= " LEFT JOIN {$wpdb->wc_product_meta_lookup} wc_product_meta_lookup ON $wpdb->posts.ID = wc_product_meta_lookup.product_id ";
			}
			return $sql;
		}

		public static function price_filter_post_clauses( $args, $wp_query ) {

			$prices = self::get_prices( $wp_query->query_vars );

			if ( empty( $prices['max_price'] ) && empty( $prices['min_price'] ) ) {
				return $args;
			}

			$current_min_price = isset( $prices['min_price'] ) ? floatval( wp_unslash( $prices['min_price'] ) ) : 0;
			$current_max_price = isset( $prices['max_price'] ) ? floatval( wp_unslash( $prices['max_price'] ) ) : PHP_INT_MAX;
	
			if ( wc_tax_enabled() && 'incl' === get_option( 'woocommerce_tax_display_shop' ) && ! wc_prices_include_tax() ) {
				$tax_class = apply_filters( 'woocommerce_price_filter_widget_tax_class', '' );
				$tax_rates = WC_Tax::get_rates( $tax_class );
	
				if ( $tax_rates ) {
					$current_min_price -= WC_Tax::get_tax_total( WC_Tax::calc_inclusive_tax( $current_min_price, $tax_rates ) );
					$current_max_price -= WC_Tax::get_tax_total( WC_Tax::calc_inclusive_tax( $current_max_price, $tax_rates ) );
				}
			}
	
			global $wpdb;

			$args['join']   = self::append_product_sorting_table_join( $args['join'] );
			$args['where'] .= $wpdb->prepare(
				' AND wc_product_meta_lookup.min_price >= %f AND wc_product_meta_lookup.max_price <= %f ',
				$current_min_price,
				$current_max_price
			);
			return $args;

		}

		public static function get_vars( $query, $that ) {
			if ( $query->is_main_query() ) {

				$ordering_args = array( 'orderby' => 'menu_order title', 'order' => 'ASC' );

				$meta_query    = WC()->query->get_meta_query();
				$query_args    = array(
					'post_type'           => 'product',
					'post_status'         => 'publish',
					'ignore_sticky_posts' => 1,
					'orderby'             => $ordering_args['orderby'],
					'order'               => $ordering_args['order'],
					'meta_query'          => $meta_query,
					'tax_query'           => WC()->query->get_tax_query()
				);

				self::$settings['shop_query'] = $query_args;
			}
		}

		public static function prdctfltr_wc_tax( $query, $that ) {

			global $prdctfltr_global;

			$stop = true;
			$curr_args = array();

			$pf_tax_query = apply_filters( 'prdctfltr_tax_query', ( isset( self::$settings['tax_query'] ) ? self::$settings['tax_query'] : array() ) );

			$pf_activated = isset( $prdctfltr_global['active_taxonomies'] ) ? $prdctfltr_global['active_taxonomies'] : array();

			if ( !empty( $pf_tax_query ) ) {

				$pf_tax_query['relation'] = 'AND';

				$now = !empty( $query->tax_query->queries ) ? $query->tax_query->queries : array();

				if ( !empty( $now ) ) {
					foreach( $now as $k => $v ) {
						if ( isset( $v['taxonomy'] ) && $v['taxonomy'] == 'product_visibility' && isset( $v['terms'] ) && is_array( $v['terms'] ) && empty( array_intersect( array( 'exclude-from-catalog', 'exclude-from-search', 'outofstock' ), $v['terms'] ) ) ) {
							unset( $now[$k] );
						}
					}
					$query->query_vars['tax_query'] = $query->tax_query->queries = array_unique( array_merge( $pf_tax_query, $now ), SORT_REGULAR );
				}
				else {
					$query->query_vars['tax_query'] = $query->tax_query->queries = array_unique( $pf_tax_query, SORT_REGULAR );
				}

				if ( wp_doing_ajax() && empty( $query->tax_query->queried_terms ) && !empty( $pf_activated ) ) {

					$addTerms = array();

					foreach ( $pf_activated as $k => $v ) {
						$addTerms[$k] = array(
							'terms' => $v,
							'field' => 'slug'
						);
					}
					$query->is_tax = true;
					$query->tax_query->queried_terms = $addTerms;

				}

			}

		}

		public static function prdctfltr_add_variable_outofstock( $where ) {

			if ( !empty( self::$settings['variable_outofstock'] ) ) {
				global $wpdb;
				$where .= " OR $wpdb->posts.ID IN ('" . implode( "','", array_map( 'esc_sql', self::$settings['variable_outofstock'] ) ) . "') ";
				remove_filter( 'posts_where' , 'prdctfltr_add_variable_outofstock' );
			}
			return $where;
		}

		public static function get_prices( $query ) {
			global $prdctfltr_global;

			$pf_activated = $prdctfltr_global['active_filters'];

			$_min_price = null;

			if ( isset( $query['min_price'] ) ) {
				$_min_price =  $query['min_price'];
			}
			if ( isset( $pf_activated['rng_min_price'] ) ) {
				$_min_price = $pf_activated['rng_min_price'];
			}
			if ( isset( $pf_activated['min_price'] ) ) {
				$_min_price =  $pf_activated['min_price'];
			}

			$_max_price = null;

			if ( isset( $query['max_price'] ) ) {
				$_max_price =  $query['max_price'];
			}
			if ( isset( $pf_activated['rng_max_price'] ) ) {
				$_max_price = $pf_activated['rng_max_price'];
			}
			if ( isset( $pf_activated['max_price'] ) ) {
				$_max_price =  $pf_activated['max_price'];
			}

			if ( isset( $_min_price ) && !isset( $_max_price ) ) {
				$_max_price = PHP_INT_MAX;
			}

			if ( isset( $_max_price ) && !isset( $_min_price ) ) {
				$_min_price = 0;
			}

			if ( isset( $_min_price ) ) {
				$_min_price = floatval( $_min_price ) - apply_filters( 'prdctfltr_min_price_margin', 0.01 );
			}

			if ( isset( $_max_price ) ) {
				$_max_price = floatval( $_max_price ) + apply_filters( 'prdctfltr_max_price_margin', 0.01 );
			}

			return array(
				'min_price' => $_min_price,
				'max_price' => $_max_price
			);

		}

		function prdctrfltr_add_filter( $template, $slug, $name ) {

			if ( in_array( $slug, array( 'loop/orderby.php', 'loop/result-count.php', 'loop/pagination.php' ) ) ) {

				$do = false;

				switch ( $slug ) {
					case 'loop/pagination.php' :
						global $prdctfltr_global;
						if ( !isset( $prdctfltr_global['sc_init'] ) && self::$options['install']['ajax']['pagination_type'] !== 'default' && self::$options['install']['ajax']['enable'] == 'yes' && is_woocommerce() ) {
							$do = true;
						}
					break;
					case 'loop/orderby.php' :
						if ( isset( self::$options['install']['templates']['orderby'] ) && self::$options['install']['templates']['orderby'] !== '_do_not' ) {
							$do = true;
						}
					break;
					case 'loop/result-count.php' :
						if ( isset( self::$options['install']['templates']['result_count'] ) && self::$options['install']['templates']['result_count'] !== '_do_not' ) {
							$do = true;
						}
					break;
					default :
					break;
				}

				if ( $do ) {
					self::$settings['template'] = $slug;
					return self::$path . 'templates/getright.php';
				}

			}

			return $template;

		}

		function prdctfltr_redirect() {

			if ( !empty( $_REQUEST ) ) {

				if ( is_shop() || is_product_taxonomy() ) {

					$request = array();
					foreach( $_REQUEST as $k3 => $v3 ) {
						if ( taxonomy_exists( $k3 ) ) {
							if ( strpos( $v3, ' ' ) > -1 ) {
								$v3 = str_replace( ' ', '+', $v3 );
							}
						}
						else if ( $k3 == 's' ) {
							$v3 = str_replace( ' ', '%20', $v3 );
						}
						$request[$k3] = $v3;
					}

					global $wp_rewrite;

					$current = $GLOBALS['wp_the_query']->get_queried_object();
					if ( !isset( $current->taxonomy ) || !$current->taxonomy ) {
						if ( isset( $request['product_cat'] ) && $request['product_cat'] !== '' ) {
							$current = new stdClass();
							$current->taxonomy = 'product_cat';
							$current->slug = $request['product_cat'];
						}
					}

					if ( isset( $current->taxonomy ) ) {

						if ( isset( $request[$current->taxonomy] ) ) {

							if ( strpos( $request[$current->taxonomy], ',' ) || strpos( $request[$current->taxonomy], '+' ) || strpos ( $request[$current->taxonomy], ' ' ) ) {
								$rewrite = $wp_rewrite->get_extra_permastruct( $current->taxonomy );
								if ( $rewrite !== false ) {
									if ( strpos( $request[$current->taxonomy], ',' ) ) {
										$terms = explode( ',', $request[$current->taxonomy] );
									}
									else if ( strpos( $request[$current->taxonomy], '+' ) ) {
										$terms = explode( '+', $request[$current->taxonomy] );
									}
									else if ( strpos( $request[$current->taxonomy], ' ' ) ) {
										$terms = explode( ' ', $request[$current->taxonomy] );
									}

									foreach( $terms as $term ) {
										if ( !term_exists( $term, $current->taxonomy ) ) {
											continue;
										}
										$checked = get_term_by( 'slug', $term, $current->taxonomy );
										if ( !empty( $checked ) ) {
											$parents[] = $checked->parent;
										}
									}

									$parent_slug = '';
									if ( isset( $parents ) ) {
										$parents_unique = array_unique( array_filter( $parents ) );
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

									$redirect = preg_replace( '/\?.*/', '', get_bloginfo( 'url' ) ) . '/' . str_replace( '%' . $current->taxonomy . '%', $parent_slug . $request[$current->taxonomy], $rewrite );
								}
							}
							else {
								$link = get_term_link( $request[$current->taxonomy], $current->taxonomy );
								if ( !is_wp_error( $link ) ) {
									$redirect = preg_replace( '/\?.*/', '', $link );
								}
							}

							if ( isset( $redirect ) ) {

								$redirect = untrailingslashit( $redirect );

								unset( $request[$current->taxonomy] );

								if ( !empty( $request ) ) {

									$req = '';

									foreach( $request as $k => $v ) {
										if ( $v == '' || in_array( $k, apply_filters( 'prdctfltr_block_request', array( 'woocs_order_emails_is_sending' ) ) ) ) {
											unset( $request[$k] );
											continue;
										}

										$req .= $k . '=' . $v . '&';
									}

									$redirect = $redirect . '/?' . $req;

									if ( substr( $redirect, -1 ) == '&' ) {
										$redirect = substr( $redirect, 0, -1 );
									}

									if ( substr( $redirect, -1 ) == '?' ) {
										$redirect = substr( $redirect, 0, -1 );
									}

								}

								if ( isset( $redirect ) ) {

									wp_redirect( $redirect, 302 );
									exit();

								}

							}

						}

					}

				}

			}
			else {
				$uri  = $_SERVER['REQUEST_URI'];
				$qPos = strpos( $uri, '?' );

				if ( $qPos === strlen( $uri ) - 1 ) {
					wp_redirect( substr( $uri, 0, $qPos ), 302 );
					exit();
				}
			}

		}

		public static function prdctrfltr_search_array( $array, $attrs ) {
			$results = array();
			$found = 0;

			foreach ( $array as $subarray ) {
				if ( isset( $subarray['attributes'] ) ) {
					foreach ( $attrs as $k => $v ) {
						if ( in_array( $v, $subarray['attributes'] ) ) {
							$found++;
						}
					}
				}
				if ( count($attrs) == $found ) {
					$results[] = $subarray;
				}

				if ( !empty( $results ) ) {
					return $results;
				}

				$found = 0;
			}

			return $results;
		}

		public static function prdctfltr_sort_terms_hierarchicaly( Array &$cats, Array &$into, $parentId = 0 ) {
			foreach ( $cats as $i => $cat ) {
				if ( $cat->parent == $parentId ) {
					$into[$cat->term_id] = $cat;
					unset($cats[$i]);
				}
			}
			foreach ( $into as $topCat ) {
				$topCat->children = array();
				self::prdctfltr_sort_terms_hierarchicaly( $cats, $topCat->children, $topCat->term_id );
			}
		}

		public static function tofloat($num) {
			$num = substr( $num, -1 ) == '.' ?  substr( $num, 0 , -1 ) : $num;

			$dotPos = strrpos($num, '.');
			$commaPos = strrpos($num, ',');
			$sep = (($dotPos > $commaPos) && $dotPos) ? $dotPos : ((($commaPos > $dotPos) && $commaPos) ? $commaPos : false);

			if (!$sep) {
				return floatval(preg_replace("/[^0-9]/", "", $num));
			}

			return floatval(
				preg_replace("/[^0-9]/", "", substr($num, 0, $sep)) . '.' .
				preg_replace("/[^0-9]/", "", substr($num, $sep+1, strlen($num)))
			);
		}

		public static function prdctfltr_sort_terms_naturally( $terms, $args ) {

			$sort_terms = array();

			foreach($terms as $term) {
				$id = (string) self::tofloat( $term->name );
				$sort_terms[$id] = $term;
			}

			ksort( $sort_terms );

			if ( strtoupper( $args['order'] ) == 'DESC' ) {
				$sort_terms = array_reverse( $sort_terms );
			}

			return $sort_terms;

		}

		public static function prdctfltr_get_filter() {
			if ( !isset( self::$settings['get_filter'] ) ) {
				self::$settings['get_filter'] = current_filter();
				include( self::$dir . 'templates/product-filter.php' );
			}
		}

		public static function prdctfltr_get_between( $content, $start, $end ){
			$r = explode($start, $content);
			if (isset($r[1])){
				$r = explode($end, $r[1]);
				return $r[0];
			}
			return '';
		}

		public static function prdctfltr_utf8_decode( $str ) {
			$str = preg_replace( "/%u([0-9a-f]{3,4})/i", "&#x\\1;", urldecode( $str ) );
			return html_entity_decode( $str, null, 'UTF-8' );
		}

		public static function prdctfltr_wpml_get_term_id( $id, $taxonomy ) {
			if ( Prdctfltr()->get_language() === false ) {
				return $id;
			}

			if( function_exists( 'icl_object_id' ) ) {
				return icl_object_id( $id, $taxonomy, true, apply_filters( 'wpml_default_language', NULL ) );
			}
			else {
				return $id;
			}
		}

		public static function prdctfltr_wpml_get_id( $id ) {
			if( function_exists( 'icl_object_id' ) ) {
				return icl_object_id( $id, 'page', true );
			}
			else {
				return $id;
			}
		}

		public static function prdctfltr_get_styles() {

			global $prdctfltr_global;

			$styles = array(
				( in_array( self::$settings['instance']['style']['style'], array( 'pf_arrow', 'pf_arrow_inline', 'pf_default', 'pf_default_inline', 'pf_select', 'pf_default_select', 'pf_sidebar', 'pf_sidebar_right', 'pf_sidebar_css', 'pf_sidebar_css_right', 'pf_fullscreen' ) ) ? self::$settings['instance']['style']['style'] : 'pf_default' ),
				( self::$settings['instance']['style']['always_visible'] == 'no' && !in_array( 'hide_top_bar', self::$settings['instance']['style']['hide_elements'] ) || in_array( self::$settings['instance']['style']['style'], array( 'pf_sidebar', 'pf_sidebar_right', 'pf_sidebar_css', 'pf_sidebar_css_right', 'pf_fullscreen', 'pf_arrow', 'pf_arrow_inline' ) ) ? 'prdctfltr_slide' : 'prdctfltr_always_visible' ),
				( self::$settings['instance']['general']['instant'] == 'no' ? 'prdctfltr_click' : 'prdctfltr_click_filter' ),
				( !in_array( 'hide_top_bar', self::$settings['instance']['style']['hide_elements'] ) || in_array( self::$settings['instance']['style']['style'], array( 'pf_sidebar', 'pf_sidebar_right', 'pf_sidebar_css', 'pf_sidebar_css_right' ) ) ? '' : 'prdctfltr_disable_bar' ),
				self::$settings['instance']['style']['checkbox_style'],
				( 1 == 'no' ? '' : 'prdctfltr_search_fields' ),
				self::$settings['instance']['style']['hierarchy_style'],
				( self::$settings['instance']['general']['step_selection'] == 'yes' ? 'prdctfltr_tabbed_selection' : '' ),
				( self::$settings['instance']['adoptive']['enable'] !== 'no' && self::$settings['instance']['adoptive']['reorder_selected'] == 'yes' ? 'prdctfltr_adoptive_reorder' : '' ),
				( self::$settings['instance']['general']['reorder_selected'] == 'yes' ? 'prdctfltr_selected_reorder' : '' ),
				( isset( self::$settings['instance']['style']['content_align'] ) && in_array( self::$settings['instance']['style']['content_align'], array( 'right', 'center' ) ) ? 'pf_content_' . self::$settings['instance']['style']['content_align'] : '' )
			);

			if ( isset( self::$options['step_filter'] ) ) {
				$styles[] = 'prdctfltr_step_filter';
			}

			if ( in_array( 'hide_reset_button', self::$settings['instance']['style']['hide_elements'] ) ) {
				$styles[] = 'pf_remove_clearall';
			}

			if ( in_array( self::$settings['instance']['style']['style'], array( 'pf_arrow', 'pf_arrow_inline', 'pf_sidebar', 'pf_sidebar_right', 'pf_sidebar_css', 'pf_sidebar_css_right', 'pf_fullscreen' ) ) ) {
				self::$settings['instance']['style']['always_visible'] = 'no';

				if ( ( $key = array_search( 'hide_top_bar', self::$settings['instance']['style']['hide_elements'] ) ) !== false ) {
					unset( self::$settings['instance']['style']['hide_elements'][$key] );
				}

			}

			if ( isset( $prdctfltr_global['mobile'] ) ) {
				$styles[] = 'prdctfltr_mobile';
			}

			if ( in_array( self::$settings['instance']['responsive']['behaviour'], array( 'show', 'hide' ) ) ) {
				$styles[] = 'prdctfltr_mobile_' . self::$settings['instance']['responsive']['behaviour'];
			}

			$styles[] = self::$settings['instance']['style']['mode'];
			if ( self::$settings['instance']['style']['mode'] == 'pf_mod_masonry' ) {
				self::$options['scripts']['isotope'] = true;
			}

			if ( self::$settings['instance']['style']['js_scroll'] == 'yes' ) {
				$styles[] = 'prdctfltr_scroll_default';
				$styles[] = 'prdctfltr_custom_scroll';
			}
			else {
				$styles[] = 'prdctfltr_scroll_default';
			}

			return $styles;

		}

		public static function _search_array( $array, $field, $value ) {
			foreach( $array as $key => $object ) {
				if ( intval( $object[$field] ) === $value )
				 return $key;
			}

			return false;
		}

		public static function prdctfltr_get_settings() {

			global $prdctfltr_global;

			$preset = 'default';

			$pf_activated = isset( $prdctfltr_global['active_filters'] ) && is_array( $prdctfltr_global['active_filters'] ) ? $prdctfltr_global['active_filters'] : array();

			if ( isset ( $prdctfltr_global['active_permalinks'] ) && is_array( $prdctfltr_global['active_permalinks'] ) ) {
				$pf_activated = array_merge( $prdctfltr_global['active_permalinks'], $pf_activated );
			}

			if ( !empty( self::$opt ) ) {
				$preset = self::$opt;
			}

			if ( isset( $prdctfltr_global['preset'] ) && $prdctfltr_global['preset'] !== '' ) {
				$preset = $prdctfltr_global['preset'];
			}

			if ( !isset( $prdctfltr_global['mobile'] ) ) {
				if ( !isset( $prdctfltr_global['disable_overrides'] ) || ( isset( $prdctfltr_global['disable_overrides'] ) && $prdctfltr_global['disable_overrides'] !== 'yes' ) ) {

					if ( empty( self::$options['manager'] ) && class_exists( 'XforWC_Product_Filters_Compatible_Settings' ) ) {
						self::$options['manager'] = XforWC_Product_Filters_Compatible_Settings::_fix_overrides();
					}

					if ( !empty( self::$options['manager'] ) ) {

						$manager_support = self::$options['general']['supported_overrides'];

						foreach ( $manager_support as $taxonomy ) {
							if ( isset( self::$options['manager'][$taxonomy] ) ) {

								$term = isset( $pf_activated[$taxonomy][0] ) && term_exists( $pf_activated[$taxonomy][0], $taxonomy ) ? get_term_by( 'slug', $pf_activated[$taxonomy][0], $taxonomy ) : '';

								if ( !empty( $term ) ) {
									$checkWPML = self::prdctfltr_wpml_get_term_id( $term->term_id, $taxonomy );

									if ( $checkWPML !== $term->term_id ) {
										$term = get_term_by( 'id', $checkWPML, $taxonomy );
									}
								}

								if ( !empty( $term ) ) {

									$key = self::_search_array( self::$options['manager'][$taxonomy], 'term', $term->term_id );

									if ( $key === false && $term->parent !== 0 && is_taxonomy_hierarchical( $taxonomy ) ) {

										$parents = get_ancestors( $term->term_id, $taxonomy );

										foreach( $parents as $parent_id ) {

											$parent = get_term_by( 'id', $parent_id, $taxonomy );

											$key = self::_search_array( self::$options['manager'][$taxonomy], 'term', $parent_id );

											if ( $key !== false ) {
												$preset = self::$options['manager'][$taxonomy][$key]['preset'];
											}
											
											if ( array_key_exists( $parent->slug, self::$options['manager'][$taxonomy]) ) {
												$preset = self::$options['manager'][$taxonomy][$parent->slug];
												break;
											}

										}

									}

									if ( $key !== false ) {
										$preset = self::$options['manager'][$taxonomy][$key]['preset'];
										break;
									}

								}

							}

						}

					}

				}

			}

			$preset = sanitize_title( $preset );

			if ( isset( $preset ) && $preset !== '' ) {
				$prdctfltr_global['preset'] = $preset;
			}

			$option = Prdctfltr()->___get_preset( $preset );

			self::$settings['instance'] = isset( self::$settings['instance'] ) && is_array( self::$settings['instance'] ) ? array_merge( self::$settings['instance'], $option ) : $option;

		}

		public static function __fix_up_preset() {

			if ( empty( self::$settings['instance']['style']['hide_elements'] ) ) {
				self::$settings['instance']['style']['hide_elements'] = array();
			}

			if ( empty( self::$settings['instance']['general']['collectors'] ) || self::$settings['instance']['general']['collectors'] == 'false' ) {
				self::$settings['instance']['general']['collectors'] = array();
			}

			if ( !isset( self::$settings['widget'] ) ) {
				if ( isset( self::$settings['instance']['style']['mode'] ) ) {
					if ( in_array( self::$settings['instance']['style']['style'], array( 'pf_sidebar', 'pf_sidebar_right', 'pf_sidebar_css', 'pf_sidebar_css_right' ) ) ) {
						self::$settings['instance']['style']['mode'] = 'pf_mod_multirow';
					}
				}
				else {
					self::$settings['instance']['style']['mode'] = 'pf_mod_multirow';
				}
			}
			else {
				if ( in_array( self::$settings['widget']['style'], array( 'pf_default', 'pf_default_inline', 'pf_default_select' ) ) ) {
					self::$settings['instance']['style']['style'] = self::$settings['widget']['style'];
				}
				if ( self::$settings['instance']['style']['style'] == 'pf_select' ) {
					self::$settings['instance']['style']['style'] = 'pf_default_select';
				}

				self::$settings['instance']['style']['mode'] = 'pf_mod_multirow';
			}

		}

		public static function __check_preset_buttons() {

			if ( self::$settings['instance']['style']['loading_animation'] !== 'none' && substr( self::$settings['instance']['style']['loading_animation'], 0, 4 ) !== 'css-' ) {
				self::$settings['instance']['style']['loading_animation'] = 'css-spinner-full';
			}

			if ( self::$settings['instance']['style']['button_position'] == 'top' ) {
				add_action( 'prdctfltr_filter_form_before', 'XforWC_Product_Filters_Frontend::prdctfltr_filter_buttons', 10 );
				remove_action( 'prdctfltr_filter_form_after', 'XforWC_Product_Filters_Frontend::prdctfltr_filter_buttons');
			}
			else if ( self::$settings['instance']['style']['button_position'] == 'both' ) {
				add_action( 'prdctfltr_filter_form_after', 'XforWC_Product_Filters_Frontend::prdctfltr_filter_buttons', 10 );
				add_action( 'prdctfltr_filter_form_before', 'XforWC_Product_Filters_Frontend::prdctfltr_filter_buttons', 10 );
			}
			else {
				add_action( 'prdctfltr_filter_form_after', 'XforWC_Product_Filters_Frontend::prdctfltr_filter_buttons', 10 );
				remove_action( 'prdctfltr_filter_form_before', 'XforWC_Product_Filters_Frontend::prdctfltr_filter_buttons');
			}

		}

		public static function prdctfltr_get_terms( $taxonomy, $args ) {

			if ( !taxonomy_exists( $taxonomy ) ) {
				return array();
			}

			$args['hide_empty'] = self::$options['general']['hide_empty'] == 'yes' ? 1 : 0;

			$orderby = isset( $args['orderby'] ) ? $args['orderby'] : wc_attribute_orderby( $taxonomy );

			$get_terms_args = array();

			switch ( $orderby ) {
				case 'name' :
					$get_terms_args['orderby']    = 'name';
					$get_terms_args['menu_order'] = false;
				break;
				case 'id' :
					$get_terms_args['orderby']    = 'id';
					$get_terms_args['order']      = 'ASC';
					$get_terms_args['menu_order'] = false;
				break;
				case '' :
				case 'menu_order' :
					unset( $args['orderby'] );
					unset( $args['order'] );
					$get_terms_args['menu_order'] = 'ASC';
				break;
			}

			$args = array_merge( $args, $get_terms_args );
			$args['taxonomy'] = $taxonomy;

			if ( !empty( self::$filter['include']['selected'] ) && self::$filter['include']['selected'] !== 'false' ) {
				$relation = isset( self::$filter['include']['relation'] ) && self::$filter['include']['relation'] == 'OUT' ? 'OUT' : 'IN';
				if ( $relation == 'IN' ) {
					$args['include'] = self::$filter['include']['selected'];
				}
				else {
					$args['exclude'] = self::$filter['include']['selected'];
				}
			}

			if ( $taxonomy == 'product_cat' ) {
				if ( !isset( $args['exclude'] ) ) {
					$args['exclude'] = array();
				}
				$args['exclude'][] = get_option( 'default_product_cat' );
			}

			$key = self::__build_cache_key( $args );

			if ( isset( self::$options['cache'][$key] ) ) {
				return self::$options['cache'][$key];
			}

			$terms = get_terms( apply_filters( 'prdctfltr_get_terms_args', $args ) );

			self::$options['cache'][$key] = $terms;

			return $terms;

		}

		public static function fix_custom_terms( $terms ) {
			$return = array();

			if ( !is_array( $terms ) ) {
				return $return;
			}

			foreach( $terms as $k => $v ) {

				$key = self::__find_customized_term( $v->term_id, self::$filter['style']['terms'] );
				if ( $key !== false && !empty( self::$filter['style']['terms'][$key] ) ) {
					$return[$key] = $v;
				}
				else {
					$return[] = $v;
				}

			}

			ksort( $return, SORT_REGULAR );

			return $return;
		}

		public static function prdctfltr_in_array( $needle, $haystack ) {
			return in_array( strtolower( $needle ), array_map( 'strtolower', $haystack ) );
		}

		public static function prdctfltr_filter_buttons() {

			global $prdctfltr_global;

			$pf_activated = ( isset( $prdctfltr_global['active_in_filter'] ) ? $prdctfltr_global['active_in_filter'] : array() );

		?>
			<div class="prdctfltr_buttons">
			<?php
				if ( self::$settings['instance']['general']['instant'] == 'no' ) {
			?>
				<a class="button prdctfltr_woocommerce_filter_submit" href="#">
					<?php
						if ( self::$settings['instance']['style']['filter_button'] !== '' ) {
							echo esc_html( self::$settings['instance']['style']['filter_button'] );
						}
						else {
							esc_html_e( 'Filter selected', 'prdctfltr' );
						}
					?>
				</a>
			<?php
				}
				if ( !in_array( 'hide_sale_button', self::$settings['instance']['style']['hide_elements'] ) ) {
				?>
				<span class="prdctfltr_sale">
				<?php
					printf(
						'<label%2$s><input name="sale_products" type="checkbox"%3$s/><span>%1$s</span></label>',
						self::$settings['instance']['style']['_tx_sale'] !== '' ? esc_html( self::$settings['instance']['style']['_tx_sale'] ) : esc_html__( 'Show only products on sale' , 'prdctfltr' ),
						isset( $pf_activated['sale_products'] ) ? ' class="prdctfltr_active"' : '',
						isset( $pf_activated['sale_products'] ) ? ' checked' : ''
					);
				?>
				</span>
				<?php
				}
				if ( !in_array( 'hide_instock_button', self::$settings['instance']['style']['hide_elements'] ) && !in_array( 'instock', self::$settings['active'] ) ) {
				?>
				<span class="prdctfltr_instock">
				<?php
					if ( get_option( 'woocommerce_hide_out_of_stock_items', 'no' ) == 'yes' ) {
						printf(
							'<label%2$s><input name="instock_products" type="checkbox" value="both"%3$s/><span>%1$s</span></label>',
							esc_html__('Show out of stock products' , 'prdctfltr' ),
							isset( $pf_activated['instock_products'] ) ? ' class="prdctfltr_active"' : '',
							isset( $pf_activated['instock_products'] ) ? ' checked' : ''
						);
					}
					else {
						printf(
							'<label%2$s><input name="instock_products" type="checkbox" value="in"%3$s/><span>%1$s</span></label>',
							self::$settings['instance']['style']['_tx_instock'] !== '' ? esc_html( self::$settings['instance']['style']['_tx_instock'] ) : esc_html__( 'In stock only' , 'prdctfltr' ),
							isset( $pf_activated['instock_products'] ) ? ' class="prdctfltr_active"' : '',
							isset( $pf_activated['instock_products'] ) ? ' checked' : ''
						);
					}
			?>
				</span>
			<?php
				}
			?>
			</div>
		<?php

		}

		public static function __find_customized_term( $id, $terms ) {

			foreach( $terms as $key => $term ) {
				if ( isset( $term['id'] ) && $term['id'] == $id ) {
					return $key;
				}
				if ( isset( $term['value'] ) && $term['value'] == $id ) {
					return $key;
				}
			}
			return false;

		}

		public static function __get_customized_term_none( $style ) {
			switch( $style ) {
				case 'color' :
					return 'transparent';
				break;
				case 'image' :
					return self::$url_path . '/includes/images/pf-transparent.gif';
				break;
				default :
					return self::__get_none_string();
				break;
			}
			return '';
		}

		public static function get_customized_term_700( $term_id, $term_slug, $term_name, $cnt, $checked = '', $sublevel = '' ) {

			if ( !empty( $term_id ) ) {
				$data = array(
					'tooltip' => '',
					'data' => '',
				);

				if ( !empty( self::$filter['style']['terms'] ) ) {

					$key = self::__find_customized_term( $term_id, self::$filter['style']['terms'] );

					if ( $key !== false ) {
						$data = array_merge( array(
							'tooltip' => '',
							'data' => '',
						), self::$filter['style']['terms'][$key] );
					}

				}
			}
			else {
				$data = array(
					'value' => '',
					'title' => self::__get_none_string(),
					'tooltip' => self::__get_none_tooltip_string(),
					'data' => self::__get_customized_term_none( self::$filter['style']['style']['type'] ),
				);
			}

			if ( !empty( $data['title'] ) ) {
				$term_name = $data['title'];
			}



			$tip = empty( $data['tooltip'] ) ? false : $data['tooltip'];

			switch ( self::$filter['style']['style']['type'] ) {

				case 'text':
					return '<span class="prdctfltr_customize_' . esc_attr( self::$filter['style']['style']['css'] ) . ' prdctfltr_customize"><span class="prdctfltr_customize_name">' . esc_html( $term_name ) . '</span>' . ( $cnt !== false ? ' <span class="prdctfltr_customize_count">' . $cnt . '</span>' : '' ) . ( $tip !== false ? '<span class="prdctfltr_tooltip"><span>' . wp_kses_post( $tip ) . '</span></span>' : '' ) . ( $checked !== '' ? '<input type="checkbox" value="' . esc_attr( $term_slug ) . '"' . esc_html( $checked ) . '/>' : '' ) . wp_kses_post( $sublevel ) . '</span>';
				break;

				case 'color':
					$size = !empty( self::$filter['style']['size'] ) ? self::$filter['style']['size'] : 42; 

					if ( !empty( self::$filter['style']['label'] ) && self::$filter['style']['label'] == 'side' ) {
						return '<span class="prdctfltr_customize_block prdctfltr_customize" style="line-height:' . absint( $size ) . 'px;"><span class="prdctfltr_customize_color_text"><span style="background-color:' . Prdctfltr()->esc_color( $data['data'] ) . ';width:' . absint( $size ) . 'px;height:' . absint( $size ) . 'px;"></span></span>' . ( $tip !== false ? '<span class="prdctfltr_tooltip"><span>' . wp_kses_post( $tip ) . '</span></span>' : '' ) . ( $checked !== '' ? '<input type="checkbox" value="' . esc_attr( $term_slug ) . '"' . esc_html( $checked ) . '/>' : '' ) . '<span class="prdctfltr_customization_search">' . esc_html( $term_name ) . '</span><span class="prdctfltr_customize_color_text_tip">' . esc_html( $term_name ) . '</span>' . ( $cnt !== false ? ' <span class="prdctfltr_count">' . $cnt . '</span>' : '' ) . wp_kses_post( $sublevel ) . '</span>';
					}
					else {
						return '<span class="prdctfltr_customize_block prdctfltr_customize"><span class="prdctfltr_customize_color" style="background-color:' . Prdctfltr()->esc_color( $data['data'] ) . ';width:' . absint( $size ) . 'px;height:' . absint( $size ) . 'px;"></span>' . ( $cnt !== false ? ' <span class="prdctfltr_customize_count">' . $cnt . '</span>' : '' ) . ( $tip !== false ? '<span class="prdctfltr_tooltip"><span>' . wp_kses_post( $tip ) . '</span></span>' : '' ) . ( $checked !== '' ? '<input type="checkbox" value="' . esc_attr( $term_slug ) . '"' . esc_html( $checked ) . '/>' : '' ) . '<span class="prdctfltr_customization_search">' . esc_html( $term_name ) . '</span>' . wp_kses_post( $sublevel ) . '</span>';
					}
				break;

				case 'image':
					$size = !empty( self::$filter['style']['size'] ) ? self::$filter['style']['size'] : 42; 

					if ( !empty( self::$filter['style']['label'] ) && self::$filter['style']['label'] == 'side' ) {
						return '<span class="prdctfltr_customize_block prdctfltr_customize" style="line-height:' . absint( $size ) . 'px;"><span class="prdctfltr_customize_image_text"><img src="' . ( empty( $data['data'] ) ? self::$url_path . '/includes/images/pf-placeholder.gif' : esc_url( $data['data'] ) ) . '" height="' . absint( $size ) . '" /></span>' . ( $tip !== false ? '<span class="prdctfltr_tooltip"><span>' . wp_kses_post( $tip ) . '</span></span>' : '' ) . ( $checked !== '' ? '<input type="checkbox" value="' . esc_attr( $term_slug ) . '"' . esc_html( $checked ) . '/>' : '' ) . '<span class="prdctfltr_customization_search">' . esc_html( $term_name ) . '</span><span class="prdctfltr_customize_image_text_tip">' . esc_html( $term_name ) . '</span>' . ( $cnt !== false ? ' <span class="prdctfltr_count">' . $cnt . '</span>' : '' ) . wp_kses_post( $sublevel ) . '</span>';
					}
					else {
						return '<span class="prdctfltr_customize_block prdctfltr_customize"><span class="prdctfltr_customize_image"><img src="' . ( empty( $data['data'] ) ? self::$url_path . '/includes/images/pf-placeholder.gif' : esc_url( $data['data'] ) ) . '" height="' . absint( $size ) . '" /></span>' . ( $cnt !== false ? ' <span class="prdctfltr_customize_count">' . $cnt . '</span>' : '' ) . ( $tip !== false ? '<span class="prdctfltr_tooltip"><span>' . wp_kses_post( $tip ) . '</span></span>' : '' ) . ( $checked !== '' ? '<input type="checkbox" value="' . esc_attr( $term_slug ) . '"' . esc_html( $checked ) . '/>' : '' ) . '<span class="prdctfltr_customization_search">' . esc_html( $term_name ) . '</span>' . wp_kses_post( $sublevel ) . '</span>';
					}
				break;

				case 'select':
					return '<span class="prdctfltr_customize_select prdctfltr_customize">' . ( $checked !== '' ? '<input type="checkbox" value="' . esc_attr( $term_slug ) . '"' . esc_html( $checked ) . '/>' : '' ) . '<span class="prdctfltr_customize_name">' . esc_html( $term_name ) . '</span>' . ( $cnt !== false ? ' <span class="prdctfltr_customize_count">' . $cnt . '</span>' : '' ) . wp_kses_post( $sublevel ) . '</span>' . ( $tip !== false ? '<span class="prdctfltr_tooltip"><span>' . wp_kses_post( $tip ) . '</span></span>' : '' );
				break;

				case 'html':
					if ( !empty( $data['data'] ) ) {
						return wp_kses_post( stripslashes( $data['data'] ) ) . '<span class="prdctfltr_customization_search">' . esc_html( $term_name ) . '</span>' . ( $tip !== false ? '<span class="prdctfltr_tooltip"><span>' . wp_kses_post( $tip ) . '</span>' . wp_kses_post( $sublevel ) . '</span>' : '' );
					}
					else {
						return esc_html( $term_name );
					}
				break;

				default :
					return '';
				break;
			}

		}

		public static function add_customized_terms_css() {

			if ( empty( self::$filter['style']['style']['css'] ) ) {
				return false;
			}

			self::$filter['style']['key'] = isset( self::$filter['style']['key'] ) ? self::$filter['style']['key'] : 'pf_style_' . uniqid();

			if ( self::$filter['style']['style']['css'] == 'border' ) {
				$css_entry = sprintf( '%1$s .prdctfltr_customize {border-color:%2$s;color:%2$s;}%1$s label.prdctfltr_active .prdctfltr_customize {border-color:%3$s;color:%3$s;}%1$s label.pf_adoptive_hide .prdctfltr_customize {border-color:%4$s;color:%4$s;}', '.' . esc_attr( self::$filter['style']['key'] ), Prdctfltr()->esc_color( self::$filter['style']['style']['normal'] ), Prdctfltr()->esc_color( self::$filter['style']['style']['active'] ), Prdctfltr()->esc_color( self::$filter['style']['style']['disabled'] ) );
			}
			else if ( self::$filter['style']['style']['css'] == 'background' ) {
				$css_entry = sprintf( '%1$s .prdctfltr_customize {background-color:%2$s;}%1$s label.prdctfltr_active .prdctfltr_customize {background-color:%3$s;}%1$s label.pf_adoptive_hide .prdctfltr_customize {background-color:%4$s;}', '.' . esc_attr( self::$filter['style']['key'] ), Prdctfltr()->esc_color( self::$filter['style']['style']['normal'] ), Prdctfltr()->esc_color( self::$filter['style']['style']['active'] ), Prdctfltr()->esc_color( self::$filter['style']['style']['disabled'] ) );
			}
			else if ( self::$filter['style']['style']['css'] == 'round' ) {
				$css_entry = sprintf( '%1$s .prdctfltr_customize {background-color:%2$s;border-radius:50%%;}%1$s label.prdctfltr_active .prdctfltr_customize {background-color:%3$s;}%1$s label.pf_adoptive_hide .prdctfltr_customize {background-color:%4$s;}', '.' . esc_attr( self::$filter['style']['key'] ), Prdctfltr()->esc_color( self::$filter['style']['style']['normal'] ), Prdctfltr()->esc_color( self::$filter['style']['style']['active'] ), Prdctfltr()->esc_color( self::$filter['style']['style']['disabled'] ) );
			}
			else {
				$css_entry = '';
			}

			if ( !isset( self::$options['css'] ) ) {
				self::$options['css'] = $css_entry;
			}
			else {
				self::$options['css'] .= $css_entry;
			}

		}

		public static function prdctfltr_add_css() {
			if ( isset( self::$options['css'] ) ) {
?>
				<style type="text/css">
					<?php echo self::$options['css']; ?>
				</style>
<?php
			}
		}

		function prdctfltr_analytics() {

			check_ajax_referer( 'prdctfltr_analytics', 'pf_nonce' );

			$data = isset( $_POST['filters'] ) ? $_POST['filters'] : '';

			if ( empty( $data ) ) {
				wp_die(1);
				exit;
			}

			if ( isset( $data['min_price'] ) && isset( $data['max_price'] ) ) {
				$data['price'] = $data['min_price'] . ' - ' . $data['max_price'];
				unset( $data['max_price'] );
			}

			$stats = get_option( '_prdctfltr_analytics', array() );

			foreach( $data as $k =>$v ) {
				if ( strpos( $v, ',' ) ) {
					$selected = explode( ',', $v );
				}
				else if ( strpos( $v, '+' ) ) {
					$selected = explode( '+', $v );
				}
				else {
					$selected = array( $v );
				}

				foreach ( $selected as $k2 => $v2 ) {
					if ( isset( $stats[$k] ) ) {
						if ( isset( $stats[$k][$v2] ) ) {
							$stats[$k][$v2] = intval( $stats[$k][$v2] ) + 1;
						}
						else {
							$stats[$k][$v2] = 1;
						}
					}
					else {
						$stats[$k][$v2] = 1;
					}
				}

			}

			update_option( '_prdctfltr_analytics', $stats );

			wp_die(1);
			exit;
		}

		public static function get_term_count_800( $term ) {
			if ( empty( self::$settings['adoptive'] ) ) {
				return '<span class="pf-recount">' . self::__get_term_count( $term ) . '</span>';
			}

			if ( isset( self::$settings['instance']['adoptive']['term_counts'] ) ) {
				switch( self::$settings['instance']['adoptive']['term_counts'] ) {
					case 'default' :
						$of = self::__get_term_count( $term );
						$has = isset( self::$settings['adoptive'][self::$filter['taxonomy']][$term->slug] ) ? self::$settings['adoptive'][self::$filter['taxonomy']][$term->slug] : 0;

						return $has < $of ? '<span class="pf-recount">' . $has . '</span>' . apply_filters( 'prdctfltr_count_separator', '/' ) . $of : '<span class="pf-recount">' . $of . '</span>';
					break;
					case 'total' :
						$of = self::__get_term_count( $term );

						return '<span class="pf-recount">' . $of . '</span>';
					break;
					case 'count' :
					default:
						$has = isset( self::$settings['adoptive'][self::$filter['taxonomy']][$term->slug] ) ? self::$settings['adoptive'][self::$filter['taxonomy']][$term->slug] : 0;

						return '<span class="pf-recount">' . $has . '</span>';
					break;
				}
			}
		}

		public static function nice_number( $n ) {
			$n = ( 0 + str_replace( ',', '', $n ) );

			if( !is_numeric( $n ) ){
				return false;
			}

			if ( $n > 1000000000000 ) {
				return round( ( $n / 1000000000000 ) , 1 ).' ' . esc_html__( 'trillion' , 'prdctfltr' );
			}
			else if ( $n > 1000000000 ) {
				return round( ( $n / 1000000000 ) , 1 ).' ' . esc_html__( 'billion' , 'prdctfltr' );
			}
			else if ( $n > 1000000 ) {
				return round( ( $n / 1000000 ) , 1 ).' ' . esc_html__( 'million' , 'prdctfltr' );
			}
			else if ( $n > 1000 ) {
				return round( ( $n / 1000 ) , 1 ).' ' . esc_html__( 'thousand' , 'prdctfltr' );
			}

			return number_format($n);
		}

		public static function tofloatprice( $num ) {
			$num = substr( $num, -1 ) == '.' ?  substr( $num, 0 , -1 ) : $num;
			$numDeci = apply_filters( 'wc_get_price_decimals', get_option( 'woocommerce_price_num_decimals', 2 ) );

			if ( $numDeci==0 ) {
				return floatval(preg_replace("/[^0-9]/", "", $num));
			}

			return floatval(
				preg_replace("/[^0-9]/", "", substr($num, 0, -$numDeci)) . '.' .
				preg_replace("/[^0-9]/", "", substr($num, -$numDeci+1))
			);
		}


		public static function price_to_float( $ptString ) {

			$ptString = str_replace( get_woocommerce_currency_symbol(), '', $ptString );

			$ptString = str_replace( '&nbsp;', '', $ptString );

			return self::tofloatprice( $ptString );

		}

		public static function get_filtered_price( $mode = 'yes' ) {

			global $prdctfltr_global;
			if ( isset( $prdctfltr_global['globals']['_get_filtered_price'] ) ) {
				return $prdctfltr_global['globals']['_get_filtered_price'];
			}
			global $wpdb;

			$tax_query  = ( $mode =='yes' && isset( $prdctfltr_global['tax_query'] ) ? $prdctfltr_global['tax_query'] : array() );

			if ( empty( $tax_query ) ) {
				global $wp_query;
				$tax_query = isset( $wp_query->query_vars['tax_query'] ) && !empty( $wp_query->query_vars['tax_query'] ) ? $wp_query->query_vars['tax_query'] : array();
			}

			$tax_query  = new WP_Tax_Query( $tax_query );

			$tax_query_sql  = $tax_query->get_sql( $wpdb->posts, 'ID' );
			$sql  = "SELECT min( FLOOR( price_meta.meta_value ) ) as min_price, max( CEILING( price_meta.meta_value ) ) as max_price FROM {$wpdb->posts} ";
			$sql .= " LEFT JOIN {$wpdb->postmeta} as price_meta ON {$wpdb->posts}.ID = price_meta.post_id " . $tax_query_sql['join'];
			$sql .= " 	WHERE {$wpdb->posts}.post_type = ('" . implode( "','", array_map( 'esc_sql', apply_filters( 'woocommerce_price_filter_post_type', array( 'product' ) ) ) ) . "')
						AND {$wpdb->posts}.post_status = 'publish'
						AND price_meta.meta_key IN ('" . implode( "','", array_map( 'esc_sql', apply_filters( 'woocommerce_price_filter_meta_keys', array( '_price' ) ) ) ) . "')
						AND price_meta.meta_value > '' ";
			$sql .= $tax_query_sql['where'];

			$prices = $wpdb->get_row( $sql );

			if ( intval( $prices->min_price ) < 0 && intval( $prices->max_price ) <= 0 && $mode == 'yes' ) {
				return self::get_filtered_price( 'no' );
			}
			else if ( intval( $prices->min_price ) >= 0 && intval( $prices->min_price ) < intval( $prices->max_price ) ) {
				$prdctfltr_global['globals']['_get_filtered_price'] = $prices;
				return $prices;
			}
			else {

				$_min = floor( $wpdb->get_var(
					sprintf('
						SELECT min(meta_value + 0)
						FROM %1$s
						LEFT JOIN %2$s ON %1$s.ID = %2$s.post_id
						WHERE ( meta_key = \'%3$s\' OR meta_key = \'%4$s\' )
						AND meta_value != ""
						', $wpdb->posts, $wpdb->postmeta, '_price', '_min_variation_price' )
					)
				);

				$_max = ceil( $wpdb->get_var(
					sprintf('
						SELECT max(meta_value + 0)
						FROM %1$s
						LEFT JOIN %2$s ON %1$s.ID = %2$s.post_id
						WHERE ( meta_key = \'%3$s\' OR meta_key = \'%4$s\' )
						AND meta_value != ""
						', $wpdb->posts, $wpdb->postmeta, '_price', '_max_variation_price' )
				) );

				$prices = new stdClass();

				if ( $_min >= 0 && $_min < $_max ) {
					$prices->min_price = $_min;
					$prices->max_price = $_max;
				}
				else {
					$prices->min_price = 0;
					$prices->max_price = 1000;
				}

				$prdctfltr_global['globals']['_get_filtered_price'] = $prices;
				return $prices;
			}

		}

		function add_body_class( $classes ) {
			if ( is_shop() || is_product_taxonomy() ) {
				if ( self::$options['install']['ajax']['enable'] == 'yes' ) {
					$classes[] = 'prdctfltr-ajax';
				}
				$classes[] = 'prdctfltr-shop';
			}

			return $classes;
		}

		function debug() {
			global $prdctfltr_global;
		?>
			<div class="prdctfltr_debug"><?php var_dump( $prdctfltr_global ); ?></div>
		<?php
		}

		public static function return_true() {
			return true;
		}

		function return_false() {
			return false;
		}

		public static function get_catalog_ordering_args() {

			$orderby_value = apply_filters( 'woocommerce_default_catalog_orderby', get_option( 'woocommerce_default_catalog_orderby' ) );

			return $orderby_value;

		}

		public static function __build_term_cache_key( $term ) {
			return '_' . $term->term_id . '_' . self::$filter['filter'];
		}

		public static function __get_term_count( $term ) {

			if ( isset( self::$filter['terms_recount'] ) ) {
				$key = self::__build_term_cache_key( $term );

				if ( isset( self::$options['cache'][$key] ) ) {
					return self::$options['cache'][$key];
				}

				global $wpdb;

				$pf_childs = get_term_children( $term->term_id, self::$filter['taxonomy'] );
				if ( empty( $pf_childs ) ) {
					$pf_parent = '
						SELECT SUM(%1$s.count) as count FROM %1$s
						WHERE %1$s.term_id = "' . esc_sql( $term->term_id ) . '"
						OR %1$s.parent = "' . esc_sql( $term->term_id ) . '"
					';
				}
				else {
					$pf_parent = '
						SELECT SUM(%1$s.count) as count FROM %1$s
						WHERE %1$s.term_id = "' . esc_sql( $term->term_id ) . '"
						OR %1$s.parent IN ("' . implode( '","', array_map( 'esc_sql', array_merge( $pf_childs, array( $term->term_id ) ) ) ) . '")
					';
				}
				$term_count = $wpdb->get_var( sprintf( $pf_parent, $wpdb->term_taxonomy ) );

				self::$options['cache'][$key] = $term_count;

				return !empty( $term_count ) ? $term_count : $term->count;
			}
			else {
				return $term->count;
			}

		}

		public static function _hierarchy_parent_check() {
			if ( isset( self::$filter['style']['style'] ) ) {
				if ( isset( self::$filter['style']['style']['type'] ) && self::$filter['style']['style']['type'] == 'system' || isset( self::$filter['style']['style']['type'] ) && self::$filter['style']['style']['type'] == 'selectize'  ) {
					return false;
				}
			}

			return true;
		}

		public static function get_taxonomy_terms( $terms, $parent = false ) {

			foreach ( $terms as $term ) {
				if ( !empty( self::$filter['style']['style']['type'] ) && !in_array( self::$filter['style']['style']['type'], array( 'system', 'selectize' ) ) ) {

					if ( self::$filter['term_count'] == 'no' ) {
						$term_count = false;
					} else {
						if ( self::____check_adoptive() ) {
							$term_count = self::get_term_count_800( $term );
						} else {
							$term_count = '<span class="pf-recount">' . self::__get_term_count( $term ) . '</span>';
						}
					}

					$sublevel = ( self::$filter['hierarchy'] == 'yes' && !empty( $term->children ) ? '<i class="prdctfltr-plus"></i>' : '' );
					$insert = self::get_customized_term_700( $term->term_id, $term->slug, $term->name, $term_count, '', $sublevel );

				}
				else {

					if ( self::$filter['term_count'] == 'no' ) {
						$term_count = '';
					} else {
						if ( self::____check_adoptive() ) {
							$term_count = ' <span class="prdctfltr_count">' . self::get_term_count_800( $term ) .  '</span>';
						} else {
							$term_count = ' <span class="prdctfltr_count"><span class="pf-recount">' . self::__get_term_count( $term ) .  '</span></span>';
						}
					}

					$title = $term->name;
					$tip = '';

					if ( !empty( self::$filter['style']['terms'] ) ) {

						$key = self::__find_customized_term( $term->term_id, self::$filter['style']['terms'] );

						if ( $key !== false && !empty( self::$filter['style']['terms'][$key] ) ) {
							if ( !empty( self::$filter['style']['terms'][$key]['title'] ) ) {
								$title = self::$filter['style']['terms'][$key]['title'];
							}
							if ( !empty( self::$filter['style']['terms'][$key]['tooltip'] ) ) {
								$tip = empty( self::$filter['style']['terms'][$key]['tooltip'] ) ? false : '<span class="prdctfltr_tooltip"><span>' . wp_kses_post( self::$filter['style']['terms'][$key]['tooltip'] ) . '</span></span>';
							}
						}

					}

					$insert = '<span>' . esc_html( $title ) . $term_count . $tip . ( self::_hierarchy_parent_check() && !empty( $term->children ) ? '<i class="prdctfltr-plus"></i>' : '' ) . '</span>';

				}

				$pf_adoptive_class = '';

				if ( self::____check_adoptive() && !empty( self::$settings['adoptive'][self::$filter['taxonomy']] ) && !array_key_exists( $term->slug, self::$settings['adoptive'][self::$filter['taxonomy']] ) ) {
					$pf_adoptive_class = ' pf_adoptive_hide';
				}

				if ( $parent === false && !empty( $term->parent ) ) {
					$termParent = get_term_by( 'id', intval( $term->parent ), self::$filter['taxonomy'] );

					if ( !empty( $termParent ) ) {
						$parent = $termParent->slug;
					}
				}

				printf('<label class="%6$s%4$s%7$s%8$s%10$s"><input type="checkbox" value="%1$s"%3$s%9$s />%2$s%5$s</label>', esc_attr( $term->slug ), wp_kses_post( $insert ), ( in_array( $term->slug, self::$filter['selected'] ) ? ' checked' : '' ), ( in_array( $term->slug, self::$filter['selected'] ) ? ' prdctfltr_active' : '' ), '',esc_attr( $pf_adoptive_class ), ( !empty( $term->children ) && in_array( $term->slug, self::$filter['selected'] ) ? ' prdctfltr_clicked' : '' ), ' prdctfltr_ft_' . esc_attr( sanitize_title( $term->slug ) ), ( self::_hierarchy_parent_check() && $parent !== false ? ' data-parent="' . esc_attr( $parent ) . '"' : '' ), ( self::_hierarchy_parent_check() && !empty( $term->children ) ? ' pfw--has-childeren' : '' ) );

				if ( self::$filter['hierarchy'] == 'yes' && !empty( $term->children ) ) {

					printf( '<div class="prdctfltr_sub"%1$s>', self::_hierarchy_parent_check() ? ' data-sub="' . esc_attr( $term->slug ) . '"' : '' );

					self::get_taxonomy_terms( $term->children, $term->slug );

					printf( '</div>' );

				}

			}

		}

		function wcml_currency( $actions ) {
			$actions[] = 'prdctfltr_respond_550';
			return $actions;
		}

		public static function get_dynamic_filter_title_700() {

			if ( self::___check_for_hidden( 'title' ) ) {
				return false;
			}

			$args = apply_filters( 'prdctfltr_filter_title_args', array(
				'filter' => 'rng_' . self::$filter['taxonomy'],
				'title' => self::$filter['title'],
				'before' => '<span class="prdctfltr_' . ( isset( self::$settings['widget'] ) ? 'widget' : 'regular' ) . '_title">',
				'after' => '</span>',
			) );

			extract( $args );

			echo wp_kses_post( $before );

			if ( self::$filter['title'] != '' ) {
				echo esc_html( self::$filter['title'] );
			}
			else {
				if ( self::$filter['taxonomy'] !== 'price' && taxonomy_exists( self::$filter['taxonomy'] ) ) {
					$taxonomy = get_taxonomy( self::$filter['taxonomy'] );
					echo esc_html( $taxonomy->labels->name );
				}
				else {
					esc_html_e( 'Price', 'prdctfltr' );
				}
			}
		?>
			<i class="prdctfltr-down"></i>
		<?php
			echo wp_kses_post( $after );
		}

		public static function get_filter_taxonomy_title() {

			if ( self::___check_for_hidden( 'title' ) ) {
				return '';
			}

			$args = apply_filters( 'prdctfltr_filter_title_args', array(
				'before' => '<span class="prdctfltr_' . ( isset( self::$settings['widget'] ) ? 'widget' : 'regular' ) . '_title">',
				'after' => '</span>',
			) );

			extract( $args );

			echo wp_kses_post( $before );

			if ( self::$filter['title'] != '' ) {
				echo esc_html( self::$filter['title'] );
			}
			else {
				if ( substr( self::$filter['taxonomy'], 0, 3 ) == 'pa_' ) {
					echo wc_attribute_label( self::$filter['taxonomy'] );
				}
				else {
					if ( self::$filter['taxonomy'] == 'product_cat' ) {
						esc_html_e( 'Categories', 'prdctfltr' );
					}
					else if ( self::$filter['taxonomy'] == 'product_tag') {
						esc_html_e( 'Tags', 'prdctfltr' );
					}
					else if ( self::$filter['taxonomy'] == 'characteristics' ) {
						esc_html_e( 'Characteristics', 'prdctfltr' );
					}
					else {
						$term = get_taxonomy( self::$filter['taxonomy'] );
						echo esc_html( $term->label );
					}
				}
			}
		?>
			<i class="prdctfltr-down"></i>
		<?php
			echo wp_kses_post( $after );

		}

		public static function get_filter_title( $filter, $title, $terms = array() ) {

			if ( self::___check_for_hidden( 'title' ) ) {
				return '';
			}

			$args = apply_filters( 'prdctfltr_filter_title_args', array(
				'filter' => $filter,
				'title' => $title,
				'before' => '<span class="prdctfltr_' . ( isset( self::$settings['widget'] ) ? 'widget' : 'regular' ) . '_title">',
				'after' => '</span>',
			) );

			extract( $args );

			echo wp_kses_post( $before );

			echo esc_html( $title );

		?>
			<i class="prdctfltr-down"></i>
		<?php
			echo wp_kses_post( $after );
		}

		public static function ___check_term_include( $term ) {
			$relation = isset( self::$filter['include']['relation'] ) && self::$filter['include']['relation'] == 'IN' ? 'IN' : 'OUT';
			if ( !empty( self::$filter['include']['selected'] ) && is_array( self::$filter['include']['selected'] ) ) {
				if ( !in_array( $term, self::$filter['include']['selected'] ) ) {
					return $relation == 'IN' ? true : false;
				}
			}
			return $relation == 'IN' ? false : true;
		}

		public static function catalog_instock( $get = '' ) {

			$array = array();
			if ( !self::___check_for_hidden( 'none' ) ) {
				$array[] = self::___get_none_array();
			}

			$instock = array(
				'both'    => esc_html__( 'All Products', 'prdctfltr' ),
				'in'      => esc_html__( 'In Stock', 'prdctfltr' ),
				'out'     => esc_html__( 'Out Of Stock', 'prdctfltr' )
			);

			if ( !empty( self::$filter['style']['terms'] ) ) {

				foreach ( self::$filter['style']['terms'] as $k => $v ) {
					if ( self::___check_term_include( $v['id'] ) ) {
						continue;
					}
					if ( empty( $v['title'] ) ) {
						$v['title'] = $instock[$v['id']];
					}
					/* Bad fix */
					if ( empty( $v['value'] ) ) {
						$v['value'] = $v['id'];
					}
					$array[] = $v;
				}

			}
			else {
				foreach( $instock as $k => $v ) {
					if ( self::___check_term_include( $k ) ) {
						continue;
					}
					$array[] = array(
						'value' => $k,
						'title' => $v,
						'tooltip' => $v,
						'data' => $k,
					);
				}
			}

			$array = apply_filters( 'prdctfltr_catalog_instock', $array );

			return $array;

		}

		public static function catalog_ordering() {

			$array = array();
			if ( !self::___check_for_hidden( 'none' ) ) {
				$array[] = self::___get_none_array();
			}

			$orderby = array(
				'menu_order'       => esc_html__( 'Default', 'prdctfltr' ),
				'comment_count'    => esc_html__( 'Review Count', 'prdctfltr' ),
				'popularity'       => esc_html__( 'Popularity', 'prdctfltr' ),
				'rating'           => esc_html__( 'Average rating', 'prdctfltr' ),
				'date'             => esc_html__( 'Newness', 'prdctfltr' ),
				'price'            => esc_html__( 'Price: low to high', 'prdctfltr' ),
				'price-desc'       => esc_html__( 'Price: high to low', 'prdctfltr' ),
				'rand'             => esc_html__( 'Random Products', 'prdctfltr' ),
				'title'            => esc_html__( 'Product Name', 'prdctfltr' )
			);

			if ( !empty( self::$filter['style']['terms'] ) ) {

				foreach ( self::$filter['style']['terms'] as $k => $v ) {
					if ( self::___check_term_include( $v['id'] ) ) {
						continue;
					}
					if ( empty( $v['title'] ) ) {
						$v['title'] = $orderby[$v['id']];
					}
					/* Bad fix */
					if ( empty( $v['value'] ) ) {
						$v['value'] = $v['id'];
					}
					$array[] = $v;
				}

			}
			else {
				foreach( $orderby as $k => $v ) {
					if ( self::___check_term_include( $k ) ) {
						continue;
					}
					$array[] = array(
						'value' => $k,
						'title' => $v,
						'tooltip' => $v,
						'data' => $k,
					);
				}
			}

			$array = apply_filters( 'prdctfltr_catalog_orderby', $array );

			return $array;

		}

		public static function make_filter() {

			global $wp_query;

			if ( isset( self::$options['sc_instance'] ) ) {
				$pf_paged = self::$options['sc_instance']['paged'];
				$pf_per_page = self::$options['sc_instance']['per_page'];
				$pf_total = self::$options['sc_instance']['total'];
				$pf_first = self::$options['sc_instance']['first'];
				$pf_last = self::$options['sc_instance']['last'];
				$pf_request = self::$options['sc_instance']['request'];
			}
			else if ( is_shop() || is_product_taxonomy() || is_search() || isset( $wp_query->query_vars['wc_query'] ) && $wp_query->query_vars['wc_query'] == 'product_query' ) {
				$pf_paged = max( 1, $wp_query->get( 'paged' ) );
				$pf_per_page = $wp_query->get( 'posts_per_page' );
				$pf_total = $wp_query->found_posts;
				$pf_first = ( $pf_per_page * $pf_paged ) - $pf_per_page + 1;
				$pf_last = $wp_query->get( 'offset' ) > 0 ? min( $pf_total, $wp_query->get( 'offset' ) + $wp_query->get( 'posts_per_page' ) ) : min( $pf_total, $wp_query->get( 'posts_per_page' ) * $pf_paged );
				$pf_request = $wp_query->request;
			}
			else {
				$pf_paged = 1;
				$pf_per_page = 10;
				$pf_total = false;
				$pf_first = 0;
				$pf_last = 0;
				$pf_request = '';
			}

			self::$settings['instance'] = array(
				'paged'     => $pf_paged,
				'per_page'  => $pf_per_page,
				'total'     => $pf_total,
				'first'     => $pf_first,
				'last'      => $pf_last,
				'request'   => $pf_request,
				'activated' => array()
			);

			self::prdctfltr_get_settings();
			self::__fix_up_preset();
			self::__check_preset_buttons();
			self::__check_action();
			self::__make_max_columns();
			self::__make_active_filters();

		}

		public static function __make_active_filters() {
			if ( empty( self::$settings['instance']['filters'] ) ) {
				self::$settings['instance']['filters'] = array();
			}


			$filters = array();

			foreach( self::$settings['instance']['filters'] as $filter ) {
				$filters[] = isset( $filter['taxonomy'] ) ? $filter['taxonomy'] : $filter['filter'];
			}

			self::$settings['active'] = $filters;
		}

		public static function get_top_bar_showing() {
			if ( self::$settings['instance']['total'] === false ) {
				return false;
			}

			$pf_step_filter = isset( self::$options['step_filter'] ) ? 'yes' : '';
		?>
			<span class="prdctfltr_showing">
		<?php
			if ( self::$settings['instance']['total'] == 0 ) {
				esc_html_e( 'No products found!', 'prdctfltr' );
			}
			else if ( self::$settings['instance']['total'] == 1 ) {
				if ( $pf_step_filter !== '' ) {
					esc_html_e( 'Found a single result', 'prdctfltr' );
				}
				else {
					esc_html_e( 'Showing the single result', 'prdctfltr' );
				}
			}
			else if ( self::$settings['instance']['total'] <= self::$settings['instance']['per_page'] || -1 == self::$settings['instance']['per_page'] ) {
				if ( $pf_step_filter !== '' ) {
					echo esc_html__( 'Found', 'prdctfltr' ) . ' ' . absint( self::$settings['instance']['total'] ) . ' ' . esc_html__( 'results', 'prdctfltr' );
				}
				else {
					echo esc_html__( 'Showing all', 'prdctfltr' ) . ' ' . absint( self::$settings['instance']['total'] ) . ' ' . esc_html__( 'results', 'prdctfltr' );
				}
			}
			else {
				if ( $pf_step_filter !== '' ) {
					echo esc_html__( 'Found', 'prdctfltr' ) . ' ' . absint( self::$settings['instance']['total'] ) . ' ' . esc_html__( 'results', 'prdctfltr' );
				}
				else {
					echo esc_html__( 'Showing', 'prdctfltr' ) . ' ' . absint( self::$settings['instance']['first'] ) . ' - ' . absint( self::$settings['instance']['last'] ) . ' ' . esc_html__( 'of', 'prdctfltr' ) . ' ' . absint( self::$settings['instance']['total'] ) . ' ' . esc_html__( 'results', 'prdctfltr' );
				}
			}
		?>
			</span>
		<?php

		}

		function get_top_bar() {
			if ( isset( self::$settings['widget'] ) && in_array( self::$settings['widget']['style'], array( 'pf_default', 'pf_default_inline', 'pf_default_select' ) ) ) {
				return false;
			}

			if ( in_array( 'hide_top_bar', self::$settings['instance']['style']['hide_elements'] ) ) {
				return false;
			}

			$icon = self::$settings['instance']['style']['filter_icon'];
		?>
			<span class="prdctfltr_filter_title">
			<?php
				$hide = '';
				if ( in_array( 'hide_icon', self::$settings['instance']['style']['hide_elements'] ) ) {
					$hide = ' pfw-hidden-element';
				}
			?>
				<a class="prdctfltr_woocommerce_filter<?php echo esc_attr( $hide ) . ' pf_ajax_' . ( self::$settings['instance']['style']['loading_animation'] !== '' ? esc_attr( self::$settings['instance']['style']['loading_animation'] ) : 'css-spinner-full-01' ); ?>" href="#"><i class="<?php echo ( $icon == '' ? 'prdctfltr-bars' : esc_attr( $icon ) ); ?><?php echo ( substr( self::$settings['instance']['style']['loading_animation'], 0, 4 ) == 'css-' ? ' ' . esc_attr( self::$settings['instance']['style']['loading_animation'] ) : '' ); ?>"></i></a>
				<span class="prdctfltr_woocommerce_filter_title">
			<?php
				if ( self::$settings['instance']['style']['filter_title'] !== '' ) {
					echo esc_html( self::$settings['instance']['style']['filter_title'] );
				}
				else {
					esc_html_e( 'Filter products', 'prdctfltr' );
				}
			?>
				</span>
			<?php
				if ( !in_array( 'hide_showing', self::$settings['instance']['style']['hide_elements'] ) ) {
					self::get_top_bar_showing();
				}
			?>
			</span>
		<?php

			wc_set_loop_prop( 'total', self::$settings['instance']['total'] );
			wc_set_loop_prop( 'per_page', self::$settings['instance']['per_page'] );

		}

		public static function get_action_tag() {

			$action = isset( self::$settings['instance']['action'] ) ? self::$settings['instance']['action'] : '';

			return apply_filters( 'prdctfltr_filter_action', $action );

		}

		public static function __check_action() {

			global $prdctfltr_global;

			$action = '';

			if ( !empty( self::$settings['instance']['general']['form_action'] ) ) {
				$action = ' action="' . ( self::$settings['instance']['general']['form_action'] == '/' ? '/' : esc_url( self::$settings['instance']['general']['form_action'] ) ) . '"';
			}

			if ( isset( $prdctfltr_global['action'] ) && $prdctfltr_global['action'] !== '' ) {
				$action = ' action="' . ( $prdctfltr_global['action'] == '/' ? '/' : esc_url( $prdctfltr_global['action'] ) ) . '"';
			}

			if ( $action == '' ) {
				if ( is_woocommerce() || is_cart() || is_checkout() || is_account_page() || is_home() ) {
					if ( self::$options['general']['force_action'] == 'yes' ) {
						if ( is_product_taxonomy() ) {
							$action = ' action=""';
						}
						else {
							$action = ' action="' . get_the_permalink( self::prdctfltr_wpml_get_id( wc_get_page_id( 'shop' ) ) ) . '"';
						}
					}
					else {
						$action = ' action="' . get_the_permalink( self::prdctfltr_wpml_get_id( wc_get_page_id( 'shop' ) ) ) . '"';
					}
				}
				else if ( is_page() ) {
					global $wp;
					if ( get_option( 'permalink_structure' ) == '' ) {
						$action = ' action="' . esc_url( remove_query_arg( array( 'page', 'paged' ), esc_url( add_query_arg( $wp->query_string, '', home_url( $wp->request ) ) ) ) ) . '"';
					} else {
						$action = ' action="' . preg_replace( '%\/page/[0-9]+%', '', home_url( $wp->request ) ) . '"';
					}
				}
				else {
					$action = ' action="' . get_the_permalink( self::prdctfltr_wpml_get_id( wc_get_page_id( 'shop' ) ) ) . '"';
				}
			}

			self::$settings['instance']['action'] = $action;

		}

		public static function get_meta_compare( $compare ) {

			switch ( $compare ) {

				case 11 :
				case '!=' :
					return $compare !== '!=' ? '!=' : 11;
				break;

				case 12 :
				case '>' :
					return $compare !== '>' ? '>' : 12;
				break;

				case 13 :
				case '<' :
					return $compare !== '<' ? '<' : 13;
				break;

				case 14 :
				case '>=' :
					return $compare !== '>=' ? '>=' : 14;
				break;

				case 15 :
				case '<=' :
					return $compare !== '<=' ? '<=' : 15;
				break;

				case 16 :
				case 'LIKE' :
					return $compare !== 'LIKE' ? 'LIKE' : 16;
				break;

				case 17 :
				case 'NOT LIKE' :
					return $compare !== 'NOT LIKE' ? 'NOT LIKE' : 17;
				break;

				case 18 :
				case 'IN' :
					return $compare !== 'IN' ? 'IN' : 18;
				break;

				case 19 :
				case 'NOT IN' :
					return $compare !== 'NOT IN' ? 'NOT IN' : 19;
				break;

				case 20 :
				case 'EXISTS' :
					return $compare !== 'EXISTS' ? 'EXISTS' : 20;
				break;

				case 21 :
				case 'NOT EXISTS' :
					return $compare !== 'NOT EXISTS' ? 'NOT EXISTS' : 21;
				break;

				case 22 :
				case 'NOT EXISTS' :
					return $compare !== 'NOT EXISTS' ? 'NOT EXISTS' : 22;
				break;

				case 23 :
				case 'BETWEEN' :
					return $compare !== 'BETWEEN' ? 'BETWEEN' : 23;
				break;

				case 24 :
				case 'NOT BETWEEN' :
					return $compare !== 'NOT BETWEEN' ? 'NOT BETWEEN' : 24;
				break;

				case 10 :
				case '=' :
					return $compare !== '=' ? '=' : 10;
				break;

				default :
					return '';
				break;
			}

		}

		public static function get_meta_type( $type ) {

			switch ( $type ) {

				case 1 :
				case 'BINARY' :
					return $type !== 'BINARY' ? 'BINARY' : 1;
				break;

				case 2 :
				case 'CHAR' :
					return $type !== 'CHAR' ? 'CHAR' : 2;
				break;

				case 3 :
				case 'DATE' :
					return $type !== 'DATE' ? 'DATE' : 3;
				break;

				case 4 :
				case 'DATETIME' :
					return $type !== 'DATETIME' ? 'DATETIME' : 4;
				break;

				case 5 :
				case 'DECIMAL' :
					return $type !== 'DECIMAL' ? 'DECIMAL' : 5;
				break;

				case 6 :
				case 'SIGNED' :
					return $type !== 'SIGNED' ? 'SIGNED' : 6;
				break;

				case 7 :
				case 'UNSIGNED' :
					return $type !== 'UNSIGNED' ? 'UNSIGNED' : 7;
				break;

				case 8 :
				case 'TIME' :
					return $type !== 'TIME' ? 'TIME' : 8;
				break;

				case 0 :
				case 'NUMERIC' :
					return $type !== 'NUMERIC' ? 'NUMERIC' : 0;
				break;

				default :
					return '';
				break;

			}

		}

		public static function build_meta_key( $key, $compare, $type ) {
			return apply_filters( 'prdctfltr_meta_key_prefix', 'mta_' ) . $key . '_' . self::get_meta_type( $type ) . '_' . self::get_meta_compare( $compare );
		}

		public static function build_meta_range_key( $key, $numeric ) {
			return !empty( $numeric ) && $numeric == 'yes' ? apply_filters( 'prdctfltr_meta_range_numeric_key_prefix', 'mtarn_' ) . $key : apply_filters( 'prdctfltr_meta_range_key_prefix', 'mtar_' ) . $key;
		}

		public static function __get_max_columns() {
			return self::$settings['instance']['style']['columns'];
		}

		public static function __make_max_columns() {
			if ( isset( self::$settings['widget'] ) && in_array( self::$settings['instance']['style']['style'], array( 'pf_default', 'pf_default_inline', 'pf_default_select' ) ) ) {
				self::$settings['instance']['style']['columns'] = 1;
			} else if ( in_array( self::$settings['instance']['style']['style'], array( 'pf_sidebar', 'pf_sidebar_right', 'pf_sidebar_css', 'pf_sidebar_css_right' ) ) ) {
				self::$settings['instance']['style']['columns'] = 1;
			} else {
				$fc = !empty( self::$settings['instance']['filters'] ) ? count( self::$settings['instance']['filters'] ) : 3;

				if ( self::$settings['instance']['style']['mode'] == 'pf_mod_row' ) {
					self::$settings['instance']['style']['columns'] = $fc;
				} else {
					$columns = empty( self::$settings['instance']['style']['columns'] ) ? 3 : self::$settings['instance']['style']['columns'];
					self::$settings['instance']['style']['columns'] = $fc < $columns ? $fc : intval( $columns );
				}
			}
		}

		public static function get_wrapper_tag_parameters() {
			echo 'class="prdctfltr_filter_wrapper prdctfltr_columns_' . esc_attr( self::__get_max_columns() ) . ( count( self::$settings['instance']['filters'] ) == 1 ? ' prdctfltr_single_filter' : '' ) . '" data-columns="' . absint( self::__get_max_columns() ) . '"';
		}

		public static function get_filter_tag_parameters() {

			$styles = self::prdctfltr_get_styles();

			echo 'class="prdctfltr_wc prdctfltr_woocommerce woocommerce ' . ( isset( self::$settings['widget'] ) ? 'prdctfltr_wc_widget' : 'prdctfltr_wc_regular' ) . ' ' . esc_attr( implode( ' ', $styles  ) ) . '"';
			echo self::$options['install']['ajax']['enable'] == 'yes'? ' data-page="' . absint( self::$settings['instance']['paged'] ) . '"' : '';
			echo ' data-loader="' . esc_attr( self::$settings['instance']['style']['loading_animation'] ) . '"';
			echo ( Prdctfltr()->get_language() !== false ? ' data-lang="' . esc_attr( substr( Prdctfltr()->get_language(), 1 ) ) . '"' : '' );
			echo self::$options['general']['analytics'] == 'yes' ? ' data-nonce="' . wp_create_nonce( 'prdctfltr_analytics' ) . '"' : '';

			global $prdctfltr_global;
			if ( !isset( $prdctfltr_global['mobile'] ) ) {
				if ( in_array( self::$settings['instance']['responsive']['behaviour'], array( 'show', 'hide', 'switch' ) ) ) {
					echo ' data-mobile="' . absint( self::$settings['instance']['responsive']['resolution'] ) . '"';
				}
			}
			echo ' data-id="' . esc_attr( $prdctfltr_global['unique_id'] ) . '"';

		}

		public static function prdctfltr_switch_thumbnails_350( $image, $product, $size, $attr, $placeholder ) {

			global $prdctfltr_global;

			if ( !empty( $prdctfltr_global['f_attrs'] ) || isset( $prdctfltr_global['unique_id'] ) && isset( $prdctfltr_global['pagefilters'][$prdctfltr_global['unique_id']]['atts']['attribute'] ) ) {

				global $product;

				if ( method_exists( $product, 'is_type' ) && $product->is_type( 'variable' ) ) {

					if ( empty( self::$settings['v_attr'] ) ) {
						$pf_activated = isset( $prdctfltr_global['active_filters'] ) ? $prdctfltr_global['active_filters'] : array();
						$pf_permalinks = isset( $prdctfltr_global['active_permalinks'] ) ? $prdctfltr_global['active_permalinks'] : array();

						if ( isset( $prdctfltr_global['unique_id'] ) && isset( $prdctfltr_global['pagefilters'][$prdctfltr_global['unique_id']]['atts']['attribute'] ) && isset( $prdctfltr_global['pagefilters'][$prdctfltr_global['unique_id']]['atts']['filter'] ) ) {
							$atts = $prdctfltr_global['pagefilters'][$prdctfltr_global['unique_id']]['atts'];

							if ( !empty( $atts ) ) {
								$pf_permalinks[strstr( $atts['attribute'], 'pa_' ) ? sanitize_title( $atts['attribute'] ) : 'pa_' . sanitize_title( $atts['attribute'] )] = array_map( 'sanitize_title', explode( ',', $atts['filter'] ) );
							}
						}

						$pf_activated = array_merge( $pf_activated, $pf_permalinks );

						if ( !empty( $pf_activated ) ) {
							$attrs = array();
							foreach( $pf_activated as $k => $v ){
								if ( substr( $k, 0, 3 ) == 'pa_' ) {
									$attrs = $attrs + array(
										$k => $v[0]
									);
								}
							}
							self::$settings['v_attr'] = $attrs;
						}
					}


					if ( !empty( self::$settings['v_attr'] ) ) {

						$variables = $product->get_variation_attributes();
						$varIntersect = array_intersect_key( self::$settings['v_attr'], $variables );

						if ( !empty( $varIntersect ) ) {

							foreach ( $product->get_children() as $child_id ) {

								$variation = wc_get_product( $child_id );

								$curr_var_set[$child_id]['attributes'] = $variation->get_variation_attributes();
								$curr_var_set[$child_id]['variation_id'] = $variation->get_id();
							}

							$found = XforWC_Product_Filters_Frontend::prdctrfltr_search_array( $curr_var_set, self::$settings['v_attr'] );
						}

					}
				}
			}

			if ( !empty( $found ) && has_post_thumbnail( $found[0]['variation_id'] ) ) {
				
				return str_replace( preg_replace('/.[^.]*$/', '', wp_get_attachment_image_src( get_post_thumbnail_id( $product->get_id() ), 'full', false ) ) , preg_replace('/.[^.]*$/', '', wp_get_attachment_image_src( get_post_thumbnail_id( $found[0]['variation_id'] ), 'full', false ) ), $image );
			}

			return $image;
		}

		public static function prdctfltr_switch_thumbnails( $html, $post_ID, $post_thumbnail_id, $size, $attr ) {

			global $prdctfltr_global;

			if ( !empty( $prdctfltr_global['f_attrs'] ) || isset( $prdctfltr_global['unique_id'] ) && isset( $prdctfltr_global['pagefilters'][$prdctfltr_global['unique_id']]['atts']['attribute'] ) ) {

				global $product;

				if ( method_exists( $product, 'is_type' ) && $product->is_type( 'variable' ) ) {

					if ( empty( self::$settings['v_attr'] ) ) {
						$pf_activated = isset( $prdctfltr_global['active_filters'] ) ? $prdctfltr_global['active_filters'] : array();
						$pf_permalinks = isset( $prdctfltr_global['active_permalinks'] ) ? $prdctfltr_global['active_permalinks'] : array();

						if ( isset( $prdctfltr_global['unique_id'] ) && isset( $prdctfltr_global['pagefilters'][$prdctfltr_global['unique_id']]['atts']['attribute'] ) && isset( $prdctfltr_global['pagefilters'][$prdctfltr_global['unique_id']]['atts']['filter'] ) ) {
							$atts = $prdctfltr_global['pagefilters'][$prdctfltr_global['unique_id']]['atts'];

							if ( !empty( $atts ) ) {
								$pf_permalinks[strstr( $atts['attribute'], 'pa_' ) ? sanitize_title( $atts['attribute'] ) : 'pa_' . sanitize_title( $atts['attribute'] )] = array_map( 'sanitize_title', explode( ',', $atts['filter'] ) );
							}
						}

						$pf_activated = array_merge( $pf_activated, $pf_permalinks );

						if ( !empty( $pf_activated ) ) {
							$attrs = array();
							foreach( $pf_activated as $k => $v ){
								if ( substr( $k, 0, 3 ) == 'pa_' ) {
									$attrs = $attrs + array(
										$k => $v[0]
									);
								}
							}
							self::$settings['v_attr'] = $attrs;
						}
					}


					if ( !empty( self::$settings['v_attr'] ) ) {

						$variables = $product->get_variation_attributes();
						$varIntersect = array_intersect_key( self::$settings['v_attr'], $variables );

						if ( !empty( $varIntersect ) ) {

							foreach ( $product->get_children() as $child_id ) {

								$variation = wc_get_product( $child_id );

								$curr_var_set[$child_id]['attributes'] = $variation->get_variation_attributes();
								$curr_var_set[$child_id]['variation_id'] = $variation->get_id();
							}

							$found = XforWC_Product_Filters_Frontend::prdctrfltr_search_array( $curr_var_set, self::$settings['v_attr'] );
						}

					}
				}
			}

			if ( !empty( $found ) && has_post_thumbnail( $found[0]['variation_id'] ) ) {
				return str_replace( preg_replace('/.[^.]*$/', '', wp_get_attachment_image_src( $post_thumbnail_id, 'full', false ) ), preg_replace('/.[^.]*$/', '', wp_get_attachment_image_src( get_post_thumbnail_id( $found[0]['variation_id'] ), 'full', false ) ), $html );
			}

			return $html;

		}

		public static function unconvert_price_filter_limits( $meta_query ) {

			if ( !isset( $meta_query['price_filter'] ) ) {
				return $meta_query;
			}

			if ( isset( $meta_query['price_filter'] ) && isset($meta_query['price_filter']['key']) && $meta_query['price_filter']['key'] === '_price' ) {

				$currency = apply_filters( 'wcml_get_client_currency', null );

				if ( $currency !== null ) {
					if ( $currency !== get_option( 'woocommerce_currency' ) ) {
						global $woocommerce_wpml;
						$meta_query['price_filter']['value'][0] = $woocommerce_wpml->multi_currency->prices->unconvert_price_amount( $meta_query['price_filter']['value'][0] );
						$meta_query['price_filter']['value'][1] = $woocommerce_wpml->multi_currency->prices->unconvert_price_amount( $meta_query['price_filter']['value'][1] );
					}
				}
				else {
					$meta_query['price_filter']['value'][0] = apply_filters( 'woocs_back_convert_price', $meta_query['price_filter']['value'][0] );
					$meta_query['price_filter']['value'][1] = apply_filters( 'woocs_back_convert_price', $meta_query['price_filter']['value'][1] )+1;
				}

			}

			return $meta_query;

		}

		public static function get_filter_search() {

			global $prdctfltr_global;

			$pf_srch = ( isset( $prdctfltr_global['sc_init'] ) && $prdctfltr_global['sc_init'] === true ? 'search_products' : 's' );

			$pf_placeholder = self::$filter['placeholder'] != '' ? esc_attr( self::$filter['placeholder'] ) : esc_attr( esc_html__( 'Product keywords', 'prdctfltr' ) );

			printf( '<label%3$s>%1$s <a href="#" class="pf_search_trigger"></a><span>%2$s</span></label>', '<input class="pf_search" name="' . esc_attr( $pf_srch ) .'" type="text"' . ( isset( self::$options['activated']['s'] ) ? ' value="' . esc_attr( self::$options['activated']['s'] ) . '"' : '' ) . ' placeholder="' . esc_attr( $pf_placeholder ) . '">', get_search_query() == '' ? ( isset( self::$options['activated']['s'] ) ? esc_html( self::$options['activated']['s'] ) : '' ) : esc_html( get_search_query() ), isset( self::$options['activated']['s'] ) ? ' class="prdctfltr_active"' : '' );

		}

		public static function ___check_for_hidden( $object ) {
			if ( isset( self::$filter['hide_elements'] ) && is_array( self::$filter['hide_elements'] ) && in_array( $object, self::$filter['hide_elements'] ) ) {
				return true;
			}
			return false;
		}

		public static function _get_filter_meta_range_terms() {

			if ( empty( self::$filter['style']['terms'] ) ) {
				esc_html_e( 'Error! No terms!', 'prdctfltr' );
			}
			else {

				global $prdctfltr_global;

				$rngId = self::_get_range_id();
	
				$prdctfltr_global['ranges'][$rngId] = array();
				$prdctfltr_global['ranges'][$rngId]['type'] = 'double';
				$prdctfltr_global['ranges'][$rngId]['min_interval'] = 1;
				$prdctfltr_global['ranges'][$rngId]['prettyValues'] = array();

				$c=0;

				$setMin = isset( self::$options['activated'][self::$filter['name']][0] ) ? self::$options['activated'][self::$filter['name']][0] : '' ;
				$setMax = isset( self::$options['activated'][self::$filter['name']] ) ? self::$options['activated'][self::$filter['name']][count( self::$options['activated'][self::$filter['name']] )-1] : '' ;

				foreach ( self::$filter['style']['terms'] as $meta ) {

					if ( $meta['value'] == $setMin ) {
						$prdctfltr_global['ranges'][$rngId]['from'] = $c;
					}

					if ( $meta['value'] == $setMax ) {
						$prdctfltr_global['ranges'][$rngId]['to'] = $c;
					}

					$prdctfltr_global['ranges'][$rngId]['prettyValues'][] = '<span class=\'pf_range_val\'>' . esc_html( $meta['value'] ) . '</span>' . esc_html( $meta['title'] );
	
					$c++;
		
				}

				if ( !empty( $prdctfltr_global['ranges'][$rngId]['prettyValues'] ) ) {
					$prdctfltr_global['ranges'][$rngId]['min'] = 0;
					$prdctfltr_global['ranges'][$rngId]['max'] = count( $prdctfltr_global['ranges'][$rngId]['prettyValues'] )-1;
				}

				$prdctfltr_global['ranges'][$rngId]['decorate_both'] = false;
				$prdctfltr_global['ranges'][$rngId]['values_separator'] = ' &rarr; ';
				$prdctfltr_global['ranges'][$rngId]['force_edges'] = true;

				foreach( self::__build_range_options() as $k24 => $v23 ) {
					if ( $v23 == '' ) {
						continue;
					}
					switch( $k24 ) {
						case 'prefix':
							$outv23 = $v23 . ( isset( $prdctfltr_global['ranges'][$rngId][$k24] ) ? $prdctfltr_global['ranges'][$rngId][$k24] : '' );
						break;
						case 'postfix':
							$outv23 = ( isset( $prdctfltr_global['ranges'][$rngId][$k24] ) ? $prdctfltr_global['ranges'][$rngId][$k24] : '' ) . $v23;
						break;
						default :
							$outv23 = $v23;
						break;
					}
					$prdctfltr_global['ranges'][$rngId][$k24] = $outv23;
				}
	
				printf( '<input id="%1$s" class="pf_rng_%2$s" data-filter="%2$s" />', esc_attr( $rngId ), esc_attr( self::$filter['name'] ) );

			}

		}

		public static $rngId = 0;

		public static function _get_range_id() {
			return 'prdctfltr_rng_' . self::$rngId++;
		}

		public static function _get_filter_meta_range_numeric_terms() {

			global $prdctfltr_global;

			$rngId = self::_get_range_id();

			$prdctfltr_global['ranges'][$rngId] = array();
			$prdctfltr_global['ranges'][$rngId]['type'] = 'double';
			$prdctfltr_global['ranges'][$rngId]['decorate_both'] = false;
			$prdctfltr_global['ranges'][$rngId]['values_separator'] = ' &rarr; ';
			$prdctfltr_global['ranges'][$rngId]['force_edges'] = true;

			if ( !empty( self::$filter['start'] ) ) {
				$min =  self::$filter['start'];
			}
			else {
				$min = 0;
			}

			if ( !empty( self::$filter['end'] ) ) {
				$max =  self::$filter['end'];
			}
			else {
				$max =  100;
			}

			$prdctfltr_global['ranges'][$rngId]['min'] = $min;
			$prdctfltr_global['ranges'][$rngId]['max'] = $max;

			if ( isset( self::$options['activated'][self::$filter['name']][0] ) ) {
				$prdctfltr_global['ranges'][$rngId]['from'] = self::$options['activated'][self::$filter['name']][0];
			}

			if ( isset( self::$options['activated'][self::$filter['name']][1] ) ) {
				$prdctfltr_global['ranges'][$rngId]['to'] = self::$options['activated'][self::$filter['name']][1];
			}

			if ( self::$filter['grid'] == 'yes' ) {
				$prdctfltr_global['ranges'][$rngId]['grid'] = true;
			}

			foreach( self::__build_range_options() as $k24 => $v23 ) {
				if ( $v23 == '' ) {
					continue;
				}
				switch( $k24 ) {
					case 'prefix':
						$outv23 = $v23 . ( isset( $prdctfltr_global['ranges'][$rngId][$k24] ) ? $prdctfltr_global['ranges'][$rngId][$k24] : '' );
					break;
					case 'postfix':
						$outv23 = ( isset( $prdctfltr_global['ranges'][$rngId][$k24] ) ? $prdctfltr_global['ranges'][$rngId][$k24] : '' ) . $v23;
					break;
					default :
						$outv23 = $v23;
					break;
				}
				$prdctfltr_global['ranges'][$rngId][$k24] = $outv23;
			}

			printf( '<input id="%1$s" class="pf_%2$s" data-filter="%2$s" />', esc_attr( $rngId ), esc_attr( self::$filter['name'] ) );

		}

		public static function get_filter_meta_range_terms() {
			if ( !empty( self::$filter['numeric'] ) && self::$filter['numeric'] == 'yes' ) {
				self:: _get_filter_meta_range_numeric_terms();
			}
			else {
				self:: _get_filter_meta_range_terms();
			}
		}

		public static function get_filter_meta_terms() {

			if ( !self::___check_for_hidden( 'none' ) ) {
				printf('<label class="prdctfltr_ft_none"><input type="checkbox" value="" />%1$s</label>', wp_kses_post( ( !empty( self::$filter['style']['style']['type'] ) && !in_array( self::$filter['style']['style']['type'], array( 'system', 'selectize' ) ) ? self::get_customized_term_700( '', 'none', self::__get_none_string(), false ) : '<span>' . self::__get_none_string() . '</span>' ) ) );
			}

			if ( empty( self::$filter['style']['terms'] ) ) {
				esc_html_e( 'Error! No terms!', 'prdctfltr' );
			}
			else {
				foreach ( self::$filter['style']['terms'] as $meta ) {

					$checked = ( isset( self::$options['activated'][self::$filter['name']] ) && in_array( $meta['value'],  self::$options['activated'][self::$filter['name']] ) ? ' checked' : ' ' );

					printf( '<label%1$s>%2$s</label>', ( isset( self::$options['activated'][self::$filter['name']] ) && in_array( $meta['value'],  self::$options['activated'][self::$filter['name']] ) ? ' class="prdctfltr_active prdctfltr_ft_' . esc_attr( sanitize_title( $meta['value'] ) ) .'"' : ' class="prdctfltr_ft_' . esc_attr( sanitize_title( $meta['value'] ) ) .'"' ), !empty( self::$filter['style']['style']['type'] ) && !in_array( self::$filter['style']['style']['type'], array( 'system', 'selectize' ) ) ? self::get_customized_term_700( $meta['value'], sanitize_title( $meta['title'] ), $meta['title'], false, $checked ) : sprintf( '<input type="checkbox" value="%1$s"%2$s/><span>%3$s</span>', esc_attr( $meta['value'] ), esc_attr( $checked ), esc_html( $meta['title'] ) ) );
				}
			}

		}

		public static function get_filter( $filterElement ) {

			self::get_true_filter( $filterElement );

			if ( empty( self::$filter['taxonomy'] ) && in_array( $filterElement['filter'], array( 'taxonomy', 'range' ) ) ) {
				return false;
			}

			if ( self::check_adoptive() === false ) {
				return false;
			}

			self::get_filter_wrapper_start();

			self::get_filter_input_fields();

			switch ( self::$filter['filter'] ) {
				case 'range' :
					self::get_dynamic_filter_title_700();
				break;
				case 'taxonomy' :
					self::get_filter_taxonomy_title();
				break;
				default :
					self::get_filter_title( self::$filter['filter'], self::$filter['title'] );
				break;
			}

			self::get_filter_description();

			self::add_customized_terms_css();

			self::get_filter_checkboxes_wrapper_start();

			switch ( self::$filter['filter'] ) {
				case 'meta' :
					self::get_filter_meta_terms();
				break;
				case 'search' :
					self::get_filter_search();
				break;
				case 'range' :
					self::get_filter_range_terms();
				break;
				case 'taxonomy' :
					self::get_filter_taxonomy_terms();
				break;
				case 'meta_range' :
					self::get_filter_meta_range_terms();
				break;
				default :
					self::get_filter_terms();
				break;
			}

			self::get_filter_checkboxes_wrapper_end();

			self::get_filter_wrapper_end();

		}

		public static function get_true_filter_description( $name ) {
			return self::$filter['desc'];
		}

		public static function get_true_filter_customization( $name ) {
			return self::$filter['style'];
		}

		public static function get_true_filter( $filter ) {

			switch( $filter['filter'] ) {

				case 'search' :

					self::$filter = array_merge( array(
						'filter'        => $filter['filter'],
						'title'         => esc_html__( 'Search Products', 'prdctfltr' ),
						'desc'          => '',
						'placeholder'   => '',
						'style'         => '',
						'class'         => 'search',
						'name'          => 'search',
					), array_filter( $filter ) );


				break;

				case 'per_page' :

					self::$filter = array_merge( array(
						'filter'        => $filter['filter'],
						'title'         => esc_html__( 'Per Page', 'prdctfltr' ),
						'desc'          => '',
						'style'         => '',
						'class'         => 'perpage',
						'name'          => 'products_per_page',
					), array_filter( $filter ) );

				break;

				case 'instock' :

					self::$filter = array_merge( array(
						'filter'        => $filter['filter'],
						'title'         => esc_html__( 'Availability', 'prdctfltr' ),
						'desc'          => '',
						'include' => array(
							'relation' => 'IN',
							'selected' => array()
						),
						'hide_elements' => array(),
						'style'         => '',
						'class'         => 'instock',
						'name'          => 'instock_products',
					), array_filter( $filter ) );

				break;

				case 'orderby' :
				case 'sort' :

					self::$filter = array_merge( array(
						'filter'        => $filter['filter'],
						'title'         => esc_html__( 'Order By', 'prdctfltr' ),
						'desc'          => '',
						'include' => array(
							'relation' => 'IN',
							'selected' => array()
						),
						'hide_elements' => array(),
						'style'         => '',
						'class'         => 'orderby',
						'name'          => 'orderby',
					), array_filter( $filter ) );

				break;

				case 'vendor' :

					self::$filter = array_merge( array(
						'filter'        => $filter['filter'],
						'title'         => esc_html__( 'Vendor', 'prdctfltr' ),
						'desc'          => '',
						'include' => array(
							'relation' => 'IN',
							'selected' => array()
						),
						'hide_elements' => array(),
						'style'         => '',
						'class'         => 'vendor',
						'name'         => 'vendor',
					), array_filter( $filter ) );


				break;

				case 'rating_filter' :

					self::$filter = array_merge( array(
						'filter'        => $filter['filter'],
						'title'         => esc_html__( 'Rating', 'prdctfltr' ),
						'desc'          => '',
						'include' => array(
							'relation' => 'IN',
							'selected' => array()
						),
						'hide_elements' => array(),
						'style'         => '',
						'class'         => 'rating_filter',
						'name'         => 'rating_filter',
					), array_filter( $filter ) );


				break;

				case 'price' :

					self::$filter = array_merge( array(
						'filter'        => $filter['filter'],
						'title'         => esc_html__( 'Price', 'prdctfltr' ),
						'desc'          => '',
						'include' => array(
							'relation' => 'IN',
							'selected' => array()
						),
						'hide_elements' => array(),
						'style'         => '',
						'class'         => 'byprice',
						'name'         => 'price',
					), array_filter( $filter ) );

				break;

				case 'meta' :

					$key = self::build_meta_key( $filter['key'], $filter['compare'], $filter['type'] );

					self::$filter = array_merge( array(
						'filter' => 'meta',
						'title' => esc_html__( 'Meta', 'prdctfltr' ),
						'desc' => '',

						'key' => '',
						'compare' => '=',
						'type' => 'CHAR',
						'limit' => '',
						'multiselect' => 'no',
						'multiselect_relation' => 'IN',
						'hide_elements' => array(),

						'style' => '',

						'name' => $key,
						'class' => 'meta',
					), array_filter( $filter ) );


					self::$filter['class'] .= ( self::$filter['multiselect'] == 'yes' ? ' prdctfltr_multi' : ' prdctfltr_single' ) . ( self::$filter['multiselect_relation'] == 'AND' ? ' prdctfltr_merge_terms' : '' );

				break;

				case 'meta_range' :

					$key = self::build_meta_range_key( $filter['key'], $filter['numeric'] );

					self::$filter = array_merge( array(
						'filter' => 'range',
						'title' => '',
						'desc' => '',

						'key' => '',
						'numeric' => '',

						'style' => '',
						'grid' => 'no',
						'start' => '',
						'end' => '',
						'prefix' => '',
						'postfix' => '',
						'step' => '',
						'grid_num' => '',

						'name' => $key,
						'class' => 'meta_range',
					), $filter );

					self::$filter['class'] .= ' prdctfltr_' . $filter['filter'] . ' pf_rngstyle_' . self::$filter['design'];

				break;
			
				case 'range' :

					self::$filter = array_merge( array(
						'filter' => 'range',
						'title' => '',
						'desc' => '',
						'include' => array(
							'relation' => 'IN',
							'selected' => array()
						),
						'orderby' => 'name',
						'order' => 'ASC',
						'adoptive' => 'no',

						'style' => '',
						'grid' => 'no',
						'start' => '',
						'end' => '',
						'prefix' => '',
						'postfix' => '',
						'step' => '',
						'grid_num' => '',

						'name' => 'rng_' . $filter['taxonomy'],
						'class' => 'rng_' . $filter['taxonomy'],
					), $filter );

					self::$filter['class'] .= ' prdctfltr_' . $filter['filter'] . ' pf_rngstyle_' . self::$filter['design'];

				break;

				case 'taxonomy' :
				default :
					self::make_up_filter_700( $filter );
				break;
			}

		}

		public static function make_up_filter_700( $filter ) {

			if ( !taxonomy_exists( $filter['taxonomy'] ) ) {
				return false;
			}

			self::$filter = array_merge( array(
				'filter' => 'taxonomy',
				'class' => '',
				'title' => '',
				'desc' => '',
				'include' => array(
					'relation' => 'IN',
					'selected' => array()
				),
				'orderby' => 'name',
				'order' => 'ASC',
				'multiselect' => 'no',
				'multiselect_relation' => 'IN',
				'adoptive' => 'no',
				'selection_reset' => 'no',
				'hide_elements' => array(),
				'hierarchy' => 'no',
				'hierarchy_mode' => 'no',
				'hierarchy_expand' => 'showall',
				'limit' => 0,
				'term_count' => 'yes',
				'term_search' => 'yes',
				'style' => '',
			), $filter );

			self::$filter['name'] = self::$filter['taxonomy'];

			self::$filter['class'] .= self::$filter['name'] . ' prdctfltr_attributes' . ( self::$filter['multiselect'] == 'yes' ? ' prdctfltr_multi' : ' prdctfltr_single' ) . ( self::____check_adoptive() ? ' ' . self::$filter['adoptive'] . ' prdctfltr_adoptive' : '' ) . ( self::$filter['multiselect_relation'] == 'AND' ? ' prdctfltr_merge_terms' : '' ) . ( self::$filter['hierarchy_expand'] == 'yes' ? ' prdctfltr_expand_parents' : '' ) . ( self::$filter['hierarchy'] == 'yes' ? ' prdctfltr_hierarchy' : '' ) . ( in_array( self::$filter['hierarchy_mode'], array( 'drill', 'drillback', 'subonly', 'subonlyback' ) ) ? ' prdctfltr_' . self::$filter['hierarchy_mode'] : '' );

			$pf_terms = array();

			if ( self::$filter['orderby'] == 'number' ) {
				$curr_term_args = array(
					'hide_empty' => self::$options['general']['hide_empty'] == 'yes' ? 1 : 0,
					'orderby' => 'slug'
				);
				$pf_terms = self::prdctfltr_get_terms( self::$filter['taxonomy'], $curr_term_args );
				$pf_sort_args = array(
					'order' => ( isset( self::$filter['order'] ) ? self::$filter['order'] : '' )
				);
				$pf_terms = self::prdctfltr_sort_terms_naturally( $pf_terms, $pf_sort_args );
			}
			else {
				$curr_term_args = array(
					'hide_empty' => self::$options['general']['hide_empty'] == 'yes' ? 1 : 0,
					'orderby' => ( self::$filter['orderby'] !== '' ? self::$filter['orderby'] : wc_attribute_orderby( self::$filter['taxonomy'] ) ),
					'order' => ( self::$filter['order'] !== '' ? self::$filter['order'] : '' )
				);

				$pf_terms = self::prdctfltr_get_terms( self::$filter['taxonomy'], $curr_term_args );
			}

			self::$filter['terms_recount'] = self::$filter['term_count'] == 'yes' && wp_doing_ajax() && is_taxonomy_hierarchical( self::$filter['taxonomy'] );

			if ( isset( self::$filter['hierarchy'] ) && self::$filter['hierarchy'] == 'yes' ) {

				$pf_terms_sorted = array();
				self::prdctfltr_sort_terms_hierarchicaly( $pf_terms, $pf_terms_sorted );
				self::$filter['terms'] = $pf_terms_sorted;

			}
			else {
				self::$filter['terms'] = $pf_terms;
			}

			if ( !empty( self::$filter['custom_order'] ) && !empty( self::$filter['style']['terms'] ) ) {
				self::$filter['terms'] = self::fix_custom_terms( self::$filter['terms'] );
			}

		}

		public static function __get_max_height_value() {
			return isset( self::$settings['instance']['style']['max_height'] ) && absint( self::$settings['instance']['style']['max_height'] ) > 0 ? absint( self::$settings['instance']['style']['max_height'] ) . 'px' : false;
		}

		public static function __get_max_height() {
			if ( in_array( self::$filter['filter'], array( 'range', 'search' ) ) ) {
				return false;
			}

			if ( isset( self::$filter['style']['style']['type'] ) && in_array( self::$filter['style']['style']['type'], array( 'system', 'selectize' ) ) ) {
				return false;
			}

			$maxHeight = self::__get_max_height_value();

			if ( empty( $maxHeight ) ) {
				return false;
			}

			if ( isset( self::$filter['style']['style']['type'] ) && in_array( self::$filter['style']['style']['type'], array( 'pf_select', 'select' ) ) ) {
				return ' style="height:' . esc_attr( $maxHeight ) . ';"';
			}

			return ' style="max-height:' . esc_attr( $maxHeight ) . ';"';
		}

		public static function __get_filter_class() {
			$classes = array(
				'prdctfltr_filter',
				'prdctfltr_' . self::$filter['class'],
			);

			if ( !empty( self::$filter['selection_reset'] ) && self::$filter['selection_reset'] == 'yes' ) {
				$classes[] = 'prdctfltr_selection';
			}

			if ( !empty( self::$filter['style'] ) ) {
				if ( !empty( self::$filter['style']['label'] ) && self::$filter['style']['label'] == 'side' ) {
					$classes[] = 'prdctfltr_side_lables';
				}
				if ( !empty( self::$filter['style']['swatchDesign'] ) && self::$filter['style']['swatchDesign'] == 'round' ) {
					$classes[] = 'prdctfltr_round_swatches';
				}
			}

			if ( !in_array( self::$filter['filter'], array( 'range', 'search' ) ) ) {

				if ( !empty( self::$filter['term_display'] ) && in_array( self::$filter['term_display'], array( 'inline', '2_columns', '3_columns' ) ) ) {
					$classes[] = 'prdctfltr_' . self::$filter['term_display'];
				}

				if ( !empty( self::$filter['style']['style']['type'] ) ) {
					if ( self::$settings['instance']['style']['style'] == 'pf_select' && in_array( self::$filter['style']['style']['type'], array( 'selectbox', 'system', 'selectize' ) ) ) {
						unset( self::$filter['style']['style']['type'] );
					}
					else {
						self::$filter['style']['key'] = isset( self::$filter['style']['key'] ) ? self::$filter['style']['key'] : 'pf_style_' . uniqid();
			
						$classes[] = self::$filter['style']['key'];
	
						$classes[] = 'prdctfltr_terms_customized';
						$classes[] = 'prdctfltr_terms_customized_' . self::$filter['style']['style']['type'];
					}
				}
				else {
					$classes[] = 'prdctfltr_text';
				}

				if ( !empty( self::$filter['term_search'] ) && self::$filter['term_search'] == 'yes' ) {
					$classes[] = 'prdctfltr_add_search';
				}
			}

			echo implode( ' ', $classes );
		}

		public static function get_filter_wrapper_start() {

			if ( ( self::$settings['instance']['style']['mode'] == 'pf_mod_multirow' || self::$settings['instance']['style']['style'] == 'pf_select' ) && self::__get_max_columns() !== 1 && !isset( self::$settings['widget'] ) && self::$settings['cnt'] == self::__get_max_columns() ) {
				self::$settings['cnt'] = 0;
				self::$filter['class'] .= ' prdctfltr_clearnext';
			}

			?>
				<div class="<?php esc_attr( self::__get_filter_class() ); ?>" data-filter="<?php echo esc_attr( self::$filter['name'] ); ?>"<?php echo isset( self::$filter['limit'] ) && intval( self::$filter['limit'] ) > 0 ? ' data-limit="' . absint( self::$filter['limit'] ) . '"' : '';?>>
			<?php
		}

		public static function get_filter_checkboxes_wrapper_start() {
		?>
			<div <?php echo empty( ( $max_height = self::__get_max_height() ) ) ? 'class="prdctfltr_add_scroll"' : 'class="prdctfltr_add_scroll prdctfltr_max_height" ' . $max_height ; ?>>
				<div class="prdctfltr_checkboxes">
			<?php
		}

		public static function get_filter_input_taxonomy() {

			global $prdctfltr_global;

			$curr_cat_selected = array();

			if ( isset( self::$options['activated'][self::$filter['taxonomy']] ) ) {
				$curr_cat_selected = is_array( self::$options['activated'][self::$filter['taxonomy']] ) ? self::$options['activated'][self::$filter['taxonomy']] : array( self::$options['activated'][self::$filter['taxonomy']] );
			}

			if ( empty( $curr_cat_selected ) && !isset( $prdctfltr_global['sc_init'] ) &&  isset( $prdctfltr_global['active_permalinks'][self::$filter['taxonomy']] ) ) {
				$curr_cat_selected = is_array( $prdctfltr_global['active_permalinks'][self::$filter['taxonomy']] ) ? $prdctfltr_global['active_permalinks'][self::$filter['taxonomy']] : array( $prdctfltr_global['active_permalinks'][self::$filter['taxonomy']] );
			}

			if ( !empty( $curr_cat_selected ) ) {
				$curr_cat_selected = array_map( 'sanitize_title', $curr_cat_selected );
			}

			if ( isset( self::$options['activated']['rng_min_' . self::$filter['taxonomy']] ) ) {
				$curr_cat_selected = array();
			}

			if ( !empty( $curr_cat_selected ) ) {
				$tax_val = isset( $prdctfltr_global['taxonomies_data'][self::$filter['taxonomy'].'_string'] ) ? esc_attr( $prdctfltr_global['taxonomies_data'][self::$filter['taxonomy'].'_string'] ) : '';
				if ( $tax_val == '' && !empty( $curr_cat_selected ) ) {
					$tax_val = isset( $prdctfltr_global['permalinks_data'][self::$filter['taxonomy'].'_string'] ) ? esc_attr( $prdctfltr_global['permalinks_data'][self::$filter['taxonomy'].'_string'] ) : '';
				}
				self::$filter['selected'] = $curr_cat_selected;
			}
			else {
				self::$filter['selected'] = array();
			}

			$termAddParent = '';
			if ( !empty( $curr_cat_selected ) ) {

				foreach( $curr_cat_selected as $tax_val_term ) {

					if ( term_exists( $tax_val_term, self::$filter['taxonomy'] ) !== null ) {
						$curr_term = get_term_by( 'slug', $tax_val_term, self::$filter['taxonomy'] );
						$pf_term_parent[] = $curr_term->parent;
					}

				}

				$doNotTerm = null;
				if ( !empty( $pf_term_parent ) ) {
					$firstValueTerm = current( $pf_term_parent );
					foreach ( $pf_term_parent as $valTerm ) {
						if ( $firstValueTerm !== $valTerm ) {
							$doNotTerm = true;
						}
					}
					if ( !isset( $doNotTerm ) && $pf_term_parent[0] !== 0 ) {
						$currParent = get_term_by( 'id', $pf_term_parent[0], self::$filter['taxonomy'] );
						$termAddParent = $currParent->slug;
					}
				}
			}
			self::$settings['instance']['activated'][] = self::$filter['taxonomy'];
		?>
			<input name="<?php echo esc_attr( self::$filter['taxonomy'] ); ?>" type="hidden"<?php echo ( !empty( $tax_val ) ? ' value="' . esc_attr( $tax_val ) . '"' : '' ) . ( self::_hierarchy_parent_check() && !empty( $termAddParent ) ? ' data-parent="' . esc_attr( $termAddParent ) . '"' : '' ); ?> />
		<?php

		}

		public static function get_filter_input_meta() {
			global $prdctfltr_global;
			self::$settings['instance']['activated'][] = self::$filter['name'];
			?>
				<input name="<?php echo esc_attr( self::$filter['name'] ); ?>" type="hidden"<?php echo ( isset( $prdctfltr_global['meta_data'][self::$filter['name']] ) ? ' value="' . esc_attr( $prdctfltr_global['meta_data'][self::$filter['name']] ) . '"' : '' );?>>
			<?php
		}

		public static function get_filter_input_range() {
			?>
				<input name="rng_min_<?php echo esc_attr( self::$filter['taxonomy'] ); ?>" type="hidden"<?php echo ( isset( self::$options['activated']['rng_min_' . self::$filter['taxonomy']] ) ? ' value="' . esc_attr( self::$options['activated']['rng_min_' . self::$filter['taxonomy']] ) . '"' : '' );?>>
				<input name="rng_max_<?php echo esc_attr( self::$filter['taxonomy'] ); ?>" type="hidden"<?php echo ( isset( self::$options['activated']['rng_max_' . self::$filter['taxonomy']] ) ? ' value="' . esc_attr( self::$options['activated']['rng_max_' . self::$filter['taxonomy']] ) . '"' : '' );?>>
			<?php
				self::$settings['instance']['activated'][] = self::$filter['taxonomy'];
				self::$settings['instance']['activated'][] = 'rng_min_' . self::$filter['taxonomy'];
				self::$settings['instance']['activated'][] = 'rng_max_' . self::$filter['taxonomy'];

				if ( self::$filter['taxonomy'] !== 'price' ) {
				?>
					<input name="rng_orderby_<?php echo esc_attr( self::$filter['taxonomy'] ); ?>" type="hidden" value="<?php echo !empty( self::$filter['custom_order'] ) && !empty( self::$filter['style']['terms'] ) ? 'custom_order' : esc_attr( self::$filter['orderby'] ); ?>">
					<input name="rng_order_<?php echo esc_attr( self::$filter['taxonomy'] ); ?>" type="hidden" value="<?php echo !empty( self::$filter['order'] ) ? esc_attr( self::$filter['order'] ) : ''; ?>">
				<?php
				}
		}

		public static function get_filter_input_price() {
			self::$settings['instance']['activated'][] = 'min_price';
			self::$settings['instance']['activated'][] = 'max_price';
			?>
				<input name="min_price" type="hidden"<?php echo ( isset( self::$options['activated']['min_price'] ) ? ' value="' . esc_attr( self::$options['activated']['min_price'] ) . '"' : '' );?>>
				<input name="max_price" type="hidden"<?php echo ( isset( self::$options['activated']['max_price'] ) ? ' value="' . esc_attr( self::$options['activated']['max_price'] ) . '"' : '' );?>>
			<?php
		}

		public static function __get_filter_input_default_value() {
			echo isset( self::$options['activated'][self::$filter['name']] ) ? ' value="' . esc_attr( ( is_array( self::$options['activated'][self::$filter['name']] ) ? implode( ',', self::$options['activated'][self::$filter['name']] ) : self::$options['activated'][self::$filter['name']] ) ) . '"' : '';
		}

		public static function get_filter_input_default() {
			if ( self::$filter['filter'] == 'search' ) {
				return false;
			}
?>
				<input name="<?php echo esc_attr( self::$filter['name'] ); ?>" type="hidden"<?php self::__get_filter_input_default_value(); ?>>
<?php

			self::$settings['instance']['activated'][] =  self::$filter['name'];
		}

		public static function get_filter_input_fields() {

			switch( self::$filter['filter'] ){
				case 'price' :
					self::get_filter_input_price();
				break;

				case 'meta' :
					self::get_filter_input_meta();
				break;

				case 'range' :
					self::get_filter_input_range();
				break;

				case 'taxonomy' :
					self::get_filter_input_taxonomy();
				break;

				default:
					self::get_filter_input_default();
				break;
			}

		}

		public static function get_filter_description() {

			$desc = isset( self::$filter['desc'] ) && !empty( self::$filter['desc'] ) ? self::$filter['desc'] : '';
			if ( $desc !== '' ) {
				printf( '<div class="prdctfltr_description">%1$s</div>', do_shortcode( wp_kses_post( $desc ) ) );
			}

		}

		public static function get_filter_checkboxes_wrapper_end() {
			?>
						</div>
					</div>
			<?php
		}

		public static function get_filter_wrapper_end() {
			?>
				</div>
			<?php

			self::$settings['cnt']++;

		}

		public static function ____check_adoptive() {
			if ( isset( self::$settings['instance']['adoptive']['enable'] ) && self::$settings['instance']['adoptive']['enable'] == "yes" && in_array( self::$filter['adoptive'], array( 'pf_adptv_default', 'pf_adptv_unclick', 'pf_adptv_click' ) ) ) {
				return true;
			}
			return false;
		}

		public static function get_filter_range_terms() {

			global $prdctfltr_global;

			$rngId = self::_get_range_id();

			$prdctfltr_global['ranges'][$rngId] = array();
			$prdctfltr_global['ranges'][$rngId]['type'] = 'double';
			$prdctfltr_global['ranges'][$rngId]['min_interval'] = 1;

			if ( !in_array( self::$filter['taxonomy'], array( 'price' ) ) ) {

				if ( isset( self::$filter['orderby'] ) && self::$filter['orderby'] == 'number' ) {
					$curr_term_args = array(
						'hide_empty' => self::$options['general']['hide_empty'] == 'yes' ? 1 : 0,
						'orderby' => 'slug'
					);
					$pf_terms = self::prdctfltr_get_terms( self::$filter['taxonomy'], $curr_term_args );
					$pf_sort_args = array(
						'order' => ( isset( self::$filter['order'] ) ? self::$filter['order'] : '' )
					);
					$pf_terms = self::prdctfltr_sort_terms_naturally( $pf_terms, $pf_sort_args );
				}
				else {
					$curr_term_args = array(
						'hide_empty' => self::$options['general']['hide_empty'] == 'yes' ? 1 : 0,
						'orderby' => ( self::$filter['orderby'] !== '' ? self::$filter['orderby'] : wc_attribute_orderby( self::$filter['taxonomy'] ) ),
						'order' => ( self::$filter['order'] !== '' ? self::$filter['order'] : '' )
					);
					$pf_terms = self::prdctfltr_get_terms( self::$filter['taxonomy'], $curr_term_args );
				}

				if ( !empty( self::$filter['custom_order'] ) && !empty( self::$filter['style']['terms'] ) ) {
					$pf_terms = self::fix_custom_terms( $pf_terms );
				}

				$prdctfltr_global['ranges'][$rngId]['prettyValues'] = array();

				$c=0;

				foreach ( $pf_terms as $attribute ) {

					if ( self::____check_adoptive() && isset( self::$settings['adoptive'][self::$filter['taxonomy']] ) && count( self::$settings['adoptive'][self::$filter['taxonomy']] ) !== 1 ) {
						if ( !isset( self::$settings['adoptive'][self::$filter['taxonomy']][$attribute->slug] ) ) {
							continue;
						}
					}

					if ( isset( self::$options['activated']['rng_min_' . self::$filter['taxonomy']] ) && self::$options['activated']['rng_min_' . self::$filter['taxonomy']] == $attribute->slug ) {
						$prdctfltr_global['ranges'][$rngId]['from'] = $c;
					}

					if ( isset( self::$options['activated']['rng_max_' . self::$filter['taxonomy']] ) && self::$options['activated']['rng_max_' . self::$filter['taxonomy']] == $attribute->slug ) {
						$prdctfltr_global['ranges'][$rngId]['to'] = $c;
					}

					$title = $attribute->name;

					if ( !empty( self::$filter['style']['terms'] ) ) {

						$key = self::__find_customized_term( $attribute->term_id, self::$filter['style']['terms'] );

						if ( $key !== false && !empty( self::$filter['style']['terms'][$key] ) && !empty( self::$filter['style']['terms'][$key]['title'] ) ) {
							$title = self::$filter['style']['terms'][$key]['title'];
						}

					}

					$prdctfltr_global['ranges'][$rngId]['prettyValues'][] = '<span class=\'pf_range_val\'>' . esc_html( $attribute->slug ) . '</span>' . esc_html( $title );

					$c++;
				}

				if ( !empty( $prdctfltr_global['ranges'][$rngId]['prettyValues'] ) ) {
					$prdctfltr_global['ranges'][$rngId]['min'] = 0;
					$prdctfltr_global['ranges'][$rngId]['max'] = count( $prdctfltr_global['ranges'][$rngId]['prettyValues'] )-1;
				}

				$prdctfltr_global['ranges'][$rngId]['decorate_both'] = false;
				$prdctfltr_global['ranges'][$rngId]['values_separator'] = ' &rarr; ';
				$prdctfltr_global['ranges'][$rngId]['force_edges'] = true;

			}
			else {

				if ( !empty( self::$filter['start'] ) ) {
					$min =  self::$filter['start'];
				}
				else {
					$prices = self::get_filtered_price( 'yes' );
					$min = $prices->min_price;
				}

				if ( !empty( self::$filter['end'] ) ) {
					$max =  self::$filter['end'];
				}
				else {
					if ( !isset( $prices ) ) {
						$prices = self::get_filtered_price( 'yes' );
					}
					$max =  $prices->max_price;
				}

				$pf_curr_min = self::price_to_float( strip_tags( wc_price( floor( $min ) ) ) );
				$pf_curr_max = self::price_to_float( strip_tags( wc_price( ceil( $max ) ) ) );

				$prdctfltr_global['ranges'][$rngId]['min'] = apply_filters( 'wcml_raw_price_amount', $pf_curr_min );
				$prdctfltr_global['ranges'][$rngId]['max'] = apply_filters( 'wcml_raw_price_amount', $pf_curr_max );
				$prdctfltr_global['ranges'][$rngId]['minR'] = $pf_curr_min;
				$prdctfltr_global['ranges'][$rngId]['maxR'] = $pf_curr_max;
				$prdctfltr_global['ranges'][$rngId]['force_edges'] = true;

				$currency_pos = get_option( 'woocommerce_currency_pos', 'left' );
				$currency = get_woocommerce_currency_symbol();

				switch ( $currency_pos ) {
					case 'right' :
						$prdctfltr_global['ranges'][$rngId]['postfix'] = $currency;
					break;
					case 'right_space' :
						$prdctfltr_global['ranges'][$rngId]['postfix'] = ' ' . $currency;
					break;
					case 'left_space' :
						$prdctfltr_global['ranges'][$rngId]['prefix'] = $currency . ' ';
					break;
					case 'left' :
					default :
						$prdctfltr_global['ranges'][$rngId]['prefix'] = $currency;
					break;
				}

				if ( isset( self::$options['activated']['rng_min_' . self::$filter['taxonomy']] ) ) {
					$prdctfltr_global['ranges'][$rngId]['from'] = self::$options['activated']['rng_min_' . self::$filter['taxonomy']];
				}

				if ( isset( self::$options['activated']['rng_max_' . self::$filter['taxonomy']] ) ) {
					$prdctfltr_global['ranges'][$rngId]['to'] = self::$options['activated']['rng_max_' . self::$filter['taxonomy']];
				}

			}

			if ( self::$filter['grid'] == 'yes' ) {
				$prdctfltr_global['ranges'][$rngId]['grid'] = true;
			}

			foreach( self::__build_range_options() as $k24 => $v23 ) {
				if ( $v23 == '' ) {
					continue;
				}
				switch( $k24 ) {
					case 'prefix':
						$outv23 = $v23 . ( isset( $prdctfltr_global['ranges'][$rngId][$k24] ) ? $prdctfltr_global['ranges'][$rngId][$k24] : '' );
					break;
					case 'postfix':
						$outv23 = ( isset( $prdctfltr_global['ranges'][$rngId][$k24] ) ? $prdctfltr_global['ranges'][$rngId][$k24] : '' ) . $v23;
					break;
					default :
						$outv23 = $v23;
					break;
				}
				$prdctfltr_global['ranges'][$rngId][$k24] = $outv23;
			}

			printf( '<input id="%1$s" class="pf_rng_%2$s" data-filter="%2$s" />', esc_attr( $rngId ), esc_attr( self::$filter['taxonomy'] ) );

		}

		public static function __build_range_options() {
			return array(
				'start' => self::$filter['start'],
				'end' => self::$filter['end'],
				'prefix' => self::$filter['prefix'],
				'postfix' => self::$filter['postfix'],
				'step' => self::$filter['step'],
				'grid_num' => self::$filter['grid_num'],
			);
		}

		public static function get_filter_taxonomy_terms() {

			if ( !self::___check_for_hidden( 'none' ) ) {
				if ( !empty( self::$filter['style']['style']['type'] ) && !in_array( self::$filter['style']['style']['type'], array( 'system', 'selectize' ) ) ) {
					$blank = self::get_customized_term_700( '', 'none', self::__get_none_string(), false );
				}
				else {
					$blank = self::__get_none_string();
				}

				printf('<label class="prdctfltr_ft_none"><input type="checkbox" value="" /><span>%1$s</span></label>', wp_kses_post( $blank ) );
			}

			self::get_taxonomy_terms( self::$filter['terms'] );

		}

		public static function get_filter_terms() {

			self::get_filter_labels( self::check_for_customization_700() );

		}

		public static function get_filter_checked( $id ) {
			switch ( self::$filter['filter'] ) {
				case 'price' :
					$price = ( isset( self::$options['activated']['min_price'] ) ? self::$options['activated']['min_price'] . '-' . ( isset( self::$options['activated']['max_price'] ) ? self::$options['activated']['max_price'] : '' ) : '' );
					return ( $price == $id ? true : false );
				break;
				default :
					return ( isset( self::$options['activated'][self::$filter['name']] ) && self::$options['activated'][self::$filter['name']] == $id ? true : false );
				break;
			}
		}

		public static function get_filter_labels( $terms ) {

			if ( $terms === false ) {
				$terms = self::get_false_terms();
			}


			foreach ( $terms as $term ) {

				$checked = $term['value'] == '' ? false : self::get_filter_checked( $term['value'] );

				$class = array();

				if ( $checked ) {
					$class[] = 'prdctfltr_active';
				}

				$class[] = 'prdctfltr_ft_' . ( $term['value'] == '' ? 'none' : sanitize_title( $term['value'] ) );

				printf( '<label class="%1$s">%2$s</label>', esc_attr( implode( ' ', $class ) ), ( !empty( self::$filter['style']['style']['type'] ) && !in_array( self::$filter['style']['style']['type'], array( 'system', 'selectize' ) ) ? self::get_customized_term_700( $term['value'], $term['value'], $term['title'], false, ( $checked === true ? ' checked' : ' ' ) ) : sprintf( '<input type="checkbox" value="%1$s"%2$s/><span>%3$s%4$s</span>', esc_attr( $term['value'] ), ( $checked === true ? ' checked' : ' ' ), esc_html( $term['title'] ), ( empty( $term['tooltip'] ) ? '' : '<span class="prdctfltr_tooltip"><span>' . wp_kses_post( $term['tooltip'] ) . '</span></span>' ) ) ) );
			}

		}

		public static function check_for_customization_700() {
			if ( !in_array( self::$filter['filter'], array( 'per_page', 'price' ) ) ) {
				return false;
			}

			if ( empty( self::$filter['style']['terms'] ) ) {
				esc_html_e( 'Error! No terms!', 'prdctfltr' );
				return array();
			}

			$opts = array();

			if ( !self::___check_for_hidden( 'none' ) ) {
				$opts[] = self::___get_none_array();
			}

			switch ( self::$filter['filter'] ) {
				case 'price' :
					$merge = array();

					foreach( self::$filter['style']['terms'] as $term ) {

						$range = explode( '-', $term['value'] );

						if ( $range[0] !== '' ) {
							$range[0] = strip_tags( wc_price( apply_filters( 'wcml_raw_price_amount', $range[0] ) ) );
						}

						if ( $range[1] !== '' ) {
							$range[1] = strip_tags( wc_price( apply_filters( 'wcml_raw_price_amount', $range[1] ) ) );
						}
						else {
							$range[1] = '&infin;';
						}

						$merge['value'] = self::price_to_float( $range[0] ) . '-' .( empty( $range[1] ) ? '' : self::price_to_float( $range[1] ) );

						if ( $term['title'] == '' ) {
							$merge['title'] = $range[0] . ' &mdash; ' . $range[1];
						}

						$opts[] = array_merge( $term, $merge );
					}

					return $opts;

				break;

				case 'per_page' :

					foreach( self::$filter['style']['terms'] as $term ) {
						$opts[] = array(
							'value' => $term['value'],
							'title' => $term['title'],
						);
					}

					return $opts;

				break;

				default :
				break;
			}

			return false;
		}

		public static function get_false_terms() {
			return call_user_func( 'self::get_false_terms_' . self::$filter['filter'] );
		}

		public static function get_false_terms_() {
			return array();
		}

		public static function get_false_terms_price() {
			return array();
		}

		public static function ___get_none_array() {
			return array(
				'value' => '',
				'title' => self::__get_none_string(),
				'tooltip' => self::__get_none_tooltip_string(),
				'data' => false,
			);
		}

		public static function __get_none_string() {
			return isset( self::$filter['none_text'] ) && !empty( self::$filter['none_text'] ) ? esc_html( self::$filter['none_text'] ) : apply_filters( 'prdctfltr_none_text', esc_html__( 'None', 'prdctfltr' ) );
		}

		public static function __get_none_tooltip_string() {
			return '';
		}

		public static function get_false_terms_rating_filter() {
			$array = array();

			if ( !self::___check_for_hidden( 'none' ) ) {
				$array[] = self::___get_none_array();
			}

			$rated = array(
				'1' => esc_html__( '1 Star', 'prdctfltr' ),
				'2' => esc_html__( '2 Stars', 'prdctfltr' ),
				'3' => esc_html__( '3 Stars', 'prdctfltr' ),
				'4' => esc_html__( '4 Stars', 'prdctfltr' ),
				'5' => esc_html__( '5 Stars', 'prdctfltr' ),
			);

			if ( !empty( self::$filter['style']['terms'] ) ) {

				foreach ( self::$filter['style']['terms'] as $k => $v ) {
					if ( self::___check_term_include( $v['id'] ) ) {
						continue;
					}
					if ( empty( $v['title'] ) ) {
						$v['title'] = $rated[$v['id']];
					}
					/* Bad fix */
					if ( empty( $v['value'] ) ) {
						$v['value'] = $v['id'];
					}
					$array[] = $v;
				}

			}
			else {
				foreach( $rated as $k => $v ) {
					if ( self::___check_term_include( $k ) ) {
						continue;
					}
					$array[] = array(
						'value' => $k,
						'title' => $v,
						'tooltip' => $v,
						'data' => $k,
					);
				}
			}

			return $array;
		}

		public static function get_false_terms_vendor() {

			$array = array();

			if ( !self::___check_for_hidden( 'none' ) ) {
				$array[] = self::___get_none_array();
			}

			$args = array(
				'orderby' => 'nicename',
			);

			if ( !empty( self::$filter['include']['selected'] ) && self::$filter['include']['selected'] !== 'false' ) {
				$relation = isset( self::$filter['include']['relation'] ) && self::$filter['include']['relation'] == 'OUT' ? 'OUT' : 'IN';
				if ( $relation == 'IN' ) {
					$args['include'] = self::$filter['include']['selected'];
				}
				else {
					$args['exclude'] = self::$filter['include']['selected'];
				}
			}

			$key = self::__build_cache_key( $args );

			if ( !empty( self::$options['cache'][$key] ) ) {
				return self::$options['cache'][$key];
			}

			$users = get_users( $args );

			foreach ( $users as $user ) {
				$array[] = array(
					'value' => intval( $user->ID ),
					'title' => $user->display_name,
					'tooltip' => $user->display_name,
					'data' => false,
				);
			}

			self::$options['cache'][$key] = $array;

			return $array;

		}

		public static function __build_cache_key( $args ) {
			if ( empty( $args ) ) {
				return false;
			}
			return '_' . md5( wp_json_encode( $args ) ) . '_' . self::$filter['filter'];
		}

		public static function get_false_terms_orderby() {
			return self::catalog_ordering();
		}

		public static function get_false_terms_instock() {
			return self::catalog_instock();
		}

		public static function get_false_terms_per_page() {
			return array();
		}

		function make_adoptive() {

			global $prdctfltr_global;

			$pf_adoptive_active = false;

			if ( !empty( self::$settings['instance']['adoptive']['active_on'] ) ) {
				switch ( self::$settings['instance']['adoptive']['active_on'] ) {
					case 'always' :
						$pf_adoptive_active = true;
					break;
					case 'permalink' :
						if ( !empty( $prdctfltr_global['active_filters'] ) || !empty( $prdctfltr_global['active_permalinks'] ) ) {
							$pf_adoptive_active = true;
						}
					break;
					case 'filter' :
						if ( !empty( $prdctfltr_global['active_filters'] ) ) {
							$pf_adoptive_active = true;
						}
					break;
					default :
						$pf_adoptive_active = false;
					break;
				}
			}

			self::$settings['products_loop'] = false;

			if ( $pf_adoptive_active === true && self::$settings['instance']['adoptive']['enable'] == 'yes' ) {

				$adpt_taxes = !empty( self::$settings['instance']['adoptive']['depend_on'] ) && is_string( self::$settings['instance']['adoptive']['depend_on'] ) ? array( self::$settings['instance']['adoptive']['depend_on'] ) : array();

				if ( !empty( $adpt_taxes ) ) {

					if ( self::$settings['instance']['total'] == 0 ) {
						$adpt_taxes = array( 'product_cat' );
					}

					$pf_products = array();

					$adpt_go = false;
					foreach( $adpt_taxes as $adpt_key => $adpt_tax ) {
						if ( !empty( $prdctfltr_global['active_filters'] ) && array_key_exists( $adpt_tax, $prdctfltr_global['active_filters'] ) ) {
							$adpt_go = true;
						}
						if ( !empty( $prdctfltr_global['active_permalinks'] ) && array_key_exists( $adpt_tax, $prdctfltr_global['active_permalinks'] ) ) {
							$adpt_go = true;
						}
						if ( apply_filters( 'prdctfltr_adoptive_go', false ) ) {
							$adpt_go = true;
						}
					}

					if ( $adpt_go === true ) {

						$adoptive_args = array(
							'post_type'				=> 'product',
							'post_status'			=> 'publish',
							'fields'				=> 'ids',
							'posts_per_page'		=> apply_filters( 'prdctfltr_adoptive_precision', 999 )
						);

						$tax_query = array();

						for ( $i = 0; $i < count( $adpt_taxes ); $i++ ) {

							if ( isset( $prdctfltr_global['active_filters'][$adpt_taxes[$i]] ) && taxonomy_exists( $adpt_taxes[$i] ) ) {
								$tax_query[] = array(
									'taxonomy' => $adpt_taxes[$i],
									'field' => 'slug',
									'terms' => $prdctfltr_global['active_filters'][$adpt_taxes[$i]]
								);
							}

							if ( isset( $prdctfltr_global['active_permalinks'][$adpt_taxes[$i]] ) && taxonomy_exists( $adpt_taxes[$i] ) ) {
								$tax_query[] = array(
									'taxonomy' => $adpt_taxes[$i],
									'field' => 'slug',
									'terms' => $prdctfltr_global['active_permalinks'][$adpt_taxes[$i]]
								);
							}

						}

						if ( !empty( $tax_query ) ) {
							$tax_query['relation'] = 'AND';
							$adoptive_args['tax_query'] = $tax_query;
						}

						$pf_help_products = new WP_Query( apply_filters( 'prdctfltr_adoptive_query', $adoptive_args ) );

						global $wpdb;
						$pf_products = $wpdb->get_results( $pf_help_products->request );

					}

				}
				else {

					$request = self::$settings['instance']['request'];

					if ( !empty( $request ) && is_string( $request ) ) {

						$t_str = $request;

						$t_pos = strpos( $request, 'SQL_CALC_FOUND_ROWS' );
						if ( $t_pos !== false ) {
							$t_str = str_replace( 'SQL_CALC_FOUND_ROWS', '', $request );
						}

						$t_pos = strpos( $request, 'LIMIT' );
						if ( $t_pos !== false ) {
							$t_str = substr( $request, 0, $t_pos );
						}

						$t_str .= ' LIMIT 0,' . apply_filters( 'prdctfltr_adoptive_precision', 999 ) . ' ';

						global $wpdb;
						$pf_products = $wpdb->get_results( $t_str );

					}
				}

				if ( !empty( $pf_products ) ) {

					$curr_in = array();
					foreach ( $pf_products as $p ) {
						if ( !isset( $p->ID ) ) {
							continue;
						}
						if ( !in_array( $p->ID, $curr_in ) ) {
							$curr_in[] = $p->ID;
						}
					}

					if ( !empty( $curr_in ) && is_array( $curr_in ) ) {

						$adoptive_taxes = array();
						$mysql_adoptive_taxes = '';

						if ( !empty( self::$settings['active'] ) ) {
							foreach( self::$settings['active'] as $k24 => $v54 ) {
								if ( taxonomy_exists( $v54 ) ) {
									$adoptive_taxes[] = $v54;
								}
							}

							$mysql_adoptive_taxes = 'AND %2$s.taxonomy IN ("' . implode( '","', array_map( 'esc_sql', $adoptive_taxes ) ) . '")';
						}

						$output_terms = array();

						$pf_product_terms_query = '
							SELECT %3$s.slug, %2$s.parent, %2$s.taxonomy, COUNT(DISTINCT %1$s.object_id) as count FROM %1$s
							INNER JOIN %2$s ON (%1$s.term_taxonomy_id = %2$s.term_taxonomy_id) ' . $mysql_adoptive_taxes . '
							INNER JOIN %3$s ON (%2$s.term_id = %3$s.term_id) 
							WHERE %1$s.object_id IN ("' . implode( '","', array_map( 'esc_sql', $curr_in ) ) . '")
							GROUP BY slug,taxonomy
						';

						$pf_product_terms = $wpdb->get_results( sprintf( $pf_product_terms_query, $wpdb->term_relationships, $wpdb->term_taxonomy, $wpdb->terms ) );
						$pf_adpt_set = array();

						foreach ( $pf_product_terms as $p ) {

							if ( !isset( $output_terms[$p->taxonomy] ) ) {
								$output_terms[$p->taxonomy] = array();
							}

							if ( !array_key_exists( $p->slug, $output_terms[$p->taxonomy] ) ) {
								$output_terms[$p->taxonomy][$p->slug] = $p->count;
							}
							else {
								$output_terms[$p->taxonomy][$p->slug] = $p->count+(isset($output_terms[$p->taxonomy][$p->slug])?$output_terms[$p->taxonomy][$p->slug]:0);
							}

							$adpt_prnt = intval( $p->parent );
							if ( $adpt_prnt > 0 ) {
								while ( $adpt_prnt !== 0 ) {
									$adpt_prnt_term = get_term_by( 'id', $adpt_prnt, $p->taxonomy );
									$output_terms[$p->taxonomy][$adpt_prnt_term->slug] = $p->count+(isset($output_terms[$p->taxonomy][$adpt_prnt_term->slug])?$output_terms[$p->taxonomy][$adpt_prnt_term->slug]:0);
									$adpt_prnt = ( ( $adpt_prnt_val = intval( $adpt_prnt_term->parent ) ) > 0 ? $adpt_prnt_val : 0 );
								}
							}

						}

						self::$settings['products_loop'] = $curr_in;
					}

				}
			} 

			if ( isset( $output_terms ) ) {
				self::$settings['adoptive'] = $output_terms;
			}
		}

		public static function check_adoptive() {

			if ( !in_array( self::$filter['filter'], array( 'range', 'taxonomy' ) ) ) {
				return true;
			}

			switch ( self::$filter['filter'] ) {
				case 'range' :
					if ( self::$filter['taxonomy'] !== 'price' && self::____check_adoptive() && ( isset( self::$settings['adoptive'] ) && ( !isset( self::$settings['adoptive'][self::$filter['taxonomy']] ) || isset( self::$settings['adoptive'][self::$filter['taxonomy']] ) && empty( self::$settings['adoptive'][self::$filter['taxonomy']]) ) === true ) ) {
						return false;
					}
				break;
				default :

					if ( self::____check_adoptive()  && ( isset( self::$settings['adoptive'] ) && ( !isset( self::$settings['adoptive'][self::$filter['taxonomy']] ) || isset( self::$settings['adoptive'][self::$filter['taxonomy']] ) && empty( self::$settings['adoptive'][self::$filter['taxonomy']]) ) === true ) ) {
						return false;
					}

				break;
			}

			return true;

		}

		public function __get_option( $o, $d = false ) {
			$p = self::$options;
			for ( $i = 0; $i <= count($o); $i++ ) {
				if ( !isset( $p[$i] ) ) {
					return $d;
				}
				$p = $p[$i];
			}
			return $p;
		}

		function __maybe_skip_variable() {
			$__maybe_skip = array();

			if ( !empty( $_POST['pf_active_variations'] ) && is_array( $_POST['pf_active_variations'] ) ) {
				$__maybe_skip = array_flip( array_filter( $_POST['pf_active_variations'], "absint" ) );
			}

			return $__maybe_skip;
		}

		function get_variable_products_helper() {
			global $prdctfltr_global;

			if ( !isset( $prdctfltr_global['f_terms'] ) ) {
				return false;
			}

			if ( empty( self::$settings['products_loop'] ) ) {
				return false;
			}

			$__maybe_skip = $this->__maybe_skip_variable();

			$products = array();

			foreach( self::$settings['products_loop'] as $n => $id ) {
				if ( !empty( $__maybe_skip ) && array_key_exists( $id, $__maybe_skip ) ) {
					continue;
				}

				$opt = array();
				$type = WC()->product_factory->get_product_type( $id ); 

				if ( $type == 'variable' ) {
					$opt = array( '_id' => $id );
					$product = wc_get_product( $id );

					foreach ( $product->get_children() as $child_id ) {
						$variation = wc_get_product( $child_id );
					
						$opt['_v'][] = array(
							$variation->get_variation_attributes(),
							$variation->is_in_stock(),
							$variation->backorders_require_notification(),
						);
					}
				}

				if ( !empty( $opt['_v'] ) ) {
					$products[$n] = $opt;
				}
			}

			if ( empty( $products ) ) {
				return false;
			}

			return $products;
		}

		function add_variations_data( $data ) {
			$data['active_filtering']['variable'] = $this->get_variable_products_helper();

			return $data;
		}

		function cleanup() {
			remove_filter( 'woocommerce_is_filtered', 'XforWC_Product_Filters_Frontend::return_true' );
		}

	}

	add_action( 'woocommerce_init', array( 'XforWC_Product_Filters_Frontend', 'init' ) );

	if ( !function_exists( 'xforwc__add_meta_information' ) ) {
		function xforwc__add_meta_information_action() {
			echo '<meta name="generator" content="XforWooCommerce.com - ' . esc_attr( implode( ' - ', apply_filters( 'xforwc__add_meta_information_used', array() ) ) ) . '"/>';
		}
		function xforwc__add_meta_information() {
			add_action( 'wp_head', 'xforwc__add_meta_information_action', 99 );
		}
		xforwc__add_meta_information();
	}
