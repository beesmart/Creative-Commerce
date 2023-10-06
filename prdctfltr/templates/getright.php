<?php

	if ( ! defined( 'ABSPATH' ) ) exit;

	if ( isset( XforWC_Product_Filters_Frontend::$settings['template'] ) ) {

		switch ( XforWC_Product_Filters_Frontend::$settings['template'] ) {

			case 'loop/orderby.php' :
				if ( XforWC_Product_Filters_Frontend::$options['install']['templates']['orderby'] !== '_hide' ) {
					if ( XforWC_Product_Filters_Frontend::$options['install']['templates']['orderby'] !== 'default' ) {
						global $prdctfltr_global;
						$prdctfltr_global['preset'] = XforWC_Product_Filters_Frontend::$options['install']['templates']['orderby'];
					}
					include( 'product-filter.php' );
				}
			break;

			case 'loop/result-count.php' :
				if ( XforWC_Product_Filters_Frontend::$options['install']['templates']['result_count'] !== '_hide' ) {
					if ( XforWC_Product_Filters_Frontend::$options['install']['templates']['result_count'] !== 'default' ) {
						global $prdctfltr_global;
						$prdctfltr_global['preset'] = XforWC_Product_Filters_Frontend::$options['install']['templates']['result_count'];
					}
					include( 'product-filter.php' );
				}
			break;

			case 'loop/pagination.php' :

				global $prdctfltr_global;

				$pf_pag_type = isset( $prdctfltr_global['pagination_type'] ) ? $prdctfltr_global['pagination_type'] : XforWC_Product_Filters_Frontend::$options['install']['ajax']['pagination_type'];

				if ( $pf_pag_type == 'prdctfltr-pagination-default' ) {

					global $wp_query;

					$total   = isset( $total ) ? $total : wc_get_loop_prop( 'total_pages' );
					$current = isset( $current ) ? $current : wc_get_loop_prop( 'current_page' );
					$base    = isset( $base ) ? $base : esc_url_raw( str_replace( 999999999, '%#%', remove_query_arg( 'add-to-cart', get_pagenum_link( 999999999, false ) ) ) );
					$format  = isset( $format ) ? $format : '';

					if ( $total <= 1 ) {
						return;
					}
					?>
					<nav class="woocommerce-pagination prdctfltr-pagination prdctfltr-pagination-default">
						<?php
							echo paginate_links( apply_filters( 'woocommerce_pagination_args', array( // WPCS: XSS ok.
								'base'         => $base,
								'format'       => $format,
								'add_args'     => false,
								'current'      => max( 1, $current ),
								'total'        => $total,
								'prev_text'    => '&larr;',
								'next_text'    => '&rarr;',
								'type'         => 'list',
								'end_size'     => 3,
								'mid_size'     => 3,
							) ) );
						?>
					</nav>
				<?php

				}
				else if ( $pf_pag_type == 'prdctfltr-pagination-load-more' ) {
					global $wp_query;

					$pf_found_posts = !isset( $wp_query->found_posts ) ? XforWC_Product_Filters_Shortcodes::$settings['instance']->found_posts : $wp_query->found_posts;
					$pf_per_page = !isset( $wp_query->query_vars['posts_per_page'] ) ? XforWC_Product_Filters_Shortcodes::$settings['instance']->query_vars['posts_per_page'] : $wp_query->query_vars['posts_per_page'];
					$pf_offset = isset( $wp_query->query_vars['offset'] ) ? $wp_query->query_vars['offset'] : 0;

				?>
					<nav class="woocommerce-pagination prdctfltr-pagination prdctfltr-pagination-load-more">
					<?php
						if ( $pf_found_posts > 0 && $pf_found_posts > $pf_per_page + $pf_offset ) {
						?>
							<a href="#" class="button"><?php esc_html_e( 'Load more', 'prdctfltr' ); ?></a>
						<?php
						}
					?>
					</nav>
				<?php
				}
				else if ( $pf_pag_type == 'prdctfltr-pagination-infinite-load' ) {
					global $wp_query;

					$pf_found_posts = !isset( $wp_query->found_posts ) ? XforWC_Product_Filters_Shortcodes::$settings['instance']->found_posts : $wp_query->found_posts;
					$pf_per_page = !isset( $wp_query->query_vars['posts_per_page'] ) ? XforWC_Product_Filters_Shortcodes::$settings['instance']->query_vars['posts_per_page'] : $wp_query->query_vars['posts_per_page'];
					$pf_offset = isset( $wp_query->query_vars['offset'] ) ? $wp_query->query_vars['offset'] : 0;

				?>
					<nav class="woocommerce-pagination prdctfltr-pagination prdctfltr-pagination-load-more prdctfltr-pagination-infinite-load">
					<?php
						if ( $pf_found_posts > 0 && $pf_found_posts > $pf_per_page + $pf_offset ) {
						?>
							<a href="#" class="button"><?php esc_html_e( 'Load more', 'prdctfltr' ); ?></a>
						<?php
						}
					?>
					</nav>
				<?php
				}

			break;

			default :
			break;

		}
		
	}

	return false;

