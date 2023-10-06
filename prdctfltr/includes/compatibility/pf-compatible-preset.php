<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class XforWC_Product_Filters_Compatible_Preset {

	public static $preset;

	public static function fix_preset( $key ) {

		$preset = get_option( $key, false );
		if ( $preset === false ) {
			return array();
		}

		if ( is_string( $preset ) && substr( $preset, 0, 1 ) == '{' ) {
			self::$preset = json_decode( stripslashes( $preset ), true );
		}

		$options = array();

		if ( is_array( self::$preset ) ) {

			$options = array(
				'general' => self::fix_options_group( self::get_options_group( 'general' ) ),
				'style' => self::fix_options_group( self::get_options_group( 'style' ) ),
				'adoptive' => self::fix_options_group( self::get_options_group( 'adoptive' ) ),
				'responsive' => self::fix_options_group( self::get_options_group( 'responsive' ) ),
				'filters' => '',
			);

		}

		$options['filters'] = array_filter( self::build_filters() );

		return $options;
	}

	public static function get_options_group( $group ) {
		include( 'fix-' . $group . '.php' );

		if ( isset( $fix ) ) {
			return $fix;
		}
	}

	public static function fix_options_group( $options ) {
		
		foreach( $options as $option ) {
			$fixed[$option[0]] = isset( $option['function'] ) ? call_user_func( 'XforWC_Product_Filters_Compatible_Preset::' . $option['function'], $option ) : self::___u( self::$preset[$option[1]] );
		}

		return $fixed;
	}

	public static function build_filters() {
		if ( !is_array( self::$preset['wc_settings_prdctfltr_active_filters'] ) ) {
			return array();
		}
		
		$adv = 0;
		$rng = 0;
		$mta = 0;
		$filters = array();

		foreach( self::___u( self::$preset['wc_settings_prdctfltr_active_filters'] ) as $filter ) {

			switch( $filter ) {
				case 'cat' :
					$filters[] = self::__build_taxonomy_filter( 'product_cat' );
				break;
				case 'tag' :
					$filters[] = self::__build_taxonomy_filter( 'product_tag' );
				break;
				case 'char' :
					$filters[] = self::__build_taxonomy_filter( 'characteristics' );
				break;
				case 'advanced' :
					$filters[] = self::__build_taxonomy_filter( 'advanced', $adv );
					$adv++;
				break;
				case 'meta' :
					$filters[] = self::__build_meta_filter( $mta );
					$mta++;
				break;
				case 'range' :
					$filters[] = self::__build_range_filter( $rng );
					$rng++;
				break;
				case 'search' :
					$filters[] = self::__build_search_filter();
				break;
				case 'vendor' :
					$filters[] = self::__build_vendor_filter();
				break;
				case 'instock' :
					$filters[] = self::__build_instock_filter();
				break;
				case 'per_page' :
					$filters[] = self::__build_per_page_filter();
				break;
				case 'orderby' :
				case 'sort' :
					$filters[] = self::__build_sort_filter();
				break;
				case 'price' :
					$filters[] = self::__build_price_filter();
				break;
				default :
					$filters[] = self::__build_taxonomy_filter( $filter );
				break;
			}
			
		}

		return self::make_extra_filter_options( $filters );
	}

	public static function make_extra_filter_options( $filters ) {
		$term_count = self::___u( self::$preset['wc_settings_prdctfltr_show_counts'] );
		$term_search = self::___u( self::$preset['wc_settings_prdctfltr_show_search'] );
		$term_display = strpos( self::___u( self::$preset['wc_settings_prdctfltr_style_preset'] ), 'inline' ) === false ? 'none' : 'inline';

		$i=0;
		foreach( $filters as $filter ) {
			if ( $filter['filter'] == 'taxonomy' ) {
				$filters[$i]['term_count'] = $term_count;
			}
			if ( in_array( $filter, array( 'taxonomy', 'meta', 'vendor' ) ) ) {
				$filters[$i]['term_search'] = $term_search;
			}
			if ( !in_array( $filter, array( 'range', 'search' ) ) ) {
				$filters[$i]['term_display'] = $term_display;
			}
			$i++;
		}

		return $filters;
	}

	public static function __build_search_filter() {
		return array(
			'filter' => 'search',
			'title' => self::___u( self::$preset['wc_settings_prdctfltr_search_title'] ),
			'desc' => self::___u( self::$preset['wc_settings_prdctfltr_search_description'] ),
			'placeholder' => self::___u( self::$preset['wc_settings_prdctfltr_search_placeholder'] ),
			'hide_elements' => array_filter( array(
				self::___u( self::$preset['wc_settings_prdctfltr_search_title'] ) == 'false' ? 'title' : false,
			) ),
		);
	}

	public static function __build_vendor_filter() {
		return array(
			'filter' => 'vendor',
			'title' => self::___u( self::$preset['wc_settings_prdctfltr_vendor_title'] ),
			'desc' => self::___u( self::$preset['wc_settings_prdctfltr_vendor_description'] ),
			'include' => array(
				'relation' => 'IN',
				'selected' => self::___u( self::$preset['wc_settings_prdctfltr_include_vendor'] ),
			),
			'hide_elements' => array_filter( array(
				self::___u( self::$preset['wc_settings_prdctfltr_vendor_title'] ) == 'false' ? 'title' : false,
			) ),
			'style' => self::__make_new_term_key( 'vendor', self::___u( self::$preset['wc_settings_prdctfltr_vendor_term_customization'] ) ),
		);
	}

	public static function __build_instock_filter() {
		return array(
			'filter' => 'instock',
			'title' => self::___u( self::$preset['wc_settings_prdctfltr_instock_title'] ),
			'desc' => self::___u( self::$preset['wc_settings_prdctfltr_instock_description'] ),
			'include' => array(
				'relation' => 'IN',
				'selected' => array(),
			),
			'hide_elements' => array_filter( array(
				self::___u( self::$preset['wc_settings_prdctfltr_instock_title'] ) == 'false' ? 'title' : false,
			) ),
			'style' => self::__make_new_term_key( 'instock', self::___u( self::$preset['wc_settings_prdctfltr_instock_term_customization'] ) ),
		);
	}

	public static function __build_per_page_filter() {
		return array(
			'filter' => 'per_page',
			'title' => self::___u( self::$preset['wc_settings_prdctfltr_perpage_title'] ),
			'desc' => self::___u( self::$preset['wc_settings_prdctfltr_perpage_description'] ),
			'label' => self::___u( self::$preset['wc_settings_prdctfltr_perpage_label'] ),
			'hide_elements' => array_filter( array(
				self::___u( self::$preset['wc_settings_prdctfltr_perpage_title'] ) == 'false' ? 'title' : false,
			) ),
			'style' => self::__merge_style_and_term_keys( 'per_page', self::___u( self::$preset['wc_settings_prdctfltr_perpage_term_customization'] ), self::___u( self::$preset['wc_settings_prdctfltr_perpage_filter_customization'] ) ),
		);
	}

	public static function __build_sort_filter() {
		return array(
			'filter' => 'orderby',
			'title' => self::___u( self::$preset['wc_settings_prdctfltr_orderby_title'] ),
			'desc' => self::___u( self::$preset['wc_settings_prdctfltr_orderby_description'] ),
			'include' => array(
				'relation' => 'IN',
				'selected' => self::___u( self::$preset['wc_settings_prdctfltr_include_orderby'] ),
			),
			'hide_elements' => array_filter( array(
				self::___u( self::$preset['wc_settings_prdctfltr_orderby_none'] ) == 'yes' ? 'none' : false,
				self::___u( self::$preset['wc_settings_prdctfltr_orderby_title'] ) == 'false' ? 'title' : false,
			) ),
			'style' => self::__make_new_term_key( 'orderby', self::___u( self::$preset['wc_settings_prdctfltr_orderby_term_customization'] ) ),
		);
	}

	public static function __make_new_term_key( $filter, $style_key ) {

		$style = array(
			'filter' => $filter,
			'key' => $style_key,
		);
		$opt_style = get_option( $style_key, false );

		if ( empty( $opt_style ) ) {
			return array();
		}

		$terms = array();
		$opt_terms = self::___get_terms_for_keys( $filter );

		$i=0;
		foreach( $opt_terms as $term ) {
			$terms[] = array(
				'id' => $filter == 'vendor' ? intval( $term['value'] ) : $term['value'],
				'slug' => $filter == 'vendor' ? intval( $term['value'] ) : $term['value'],
				'value' => $filter == 'vendor' ? intval( $term['value'] ) : $term['value'],
				'title' => '',
				'data' => false,
			);
			if ( $opt_style['style'] !== 'text' && array_key_exists( 'term_' . $term['value'], $opt_style['settings'] ) ) {
				$terms[$i]['data'] = $opt_style['settings']['term_' . $term['value']];
			}
			if ( array_key_exists( 'tooltip_' . $term['value'], $opt_style['settings'] ) ) {
				$terms[$i]['tooltip'] = $opt_style['settings']['tooltip_' . $term['value']];
			}
			$i++;
		}
		if ( !empty( $terms ) ) {
			$style['terms'] = $terms;
		}

		return self::__make_a_text_style( $opt_style, $style );
	}

	public static function ___get_terms_for_keys( $filter ) {
		$set = array();

		switch ( $filter ) {
			case 'instock' :
				$instock = apply_filters( 'prdctfltr_catalog_instock', array(
					'both'    => esc_html__( 'All Products', 'prdctfltr' ),
					'in'      => esc_html__( 'In Stock', 'prdctfltr' ),
					'out'     => esc_html__( 'Out Of Stock', 'prdctfltr' )
				) );

				foreach( $instock as $k => $v ) {
					$set[] = array( 'value' => $k, 'title' => $v );
				}

				return $set;
			break;
			case 'orderby' :

				$orderby = apply_filters( 'prdctfltr_catalog_orderby', array(
					'menu_order'    => esc_html__( 'Default', 'prdctfltr' ),
					'comment_count' => esc_html__( 'Review Count', 'prdctfltr' ),
					'popularity'    => esc_html__( 'Popularity', 'prdctfltr' ),
					'rating'        => esc_html__( 'Average rating', 'prdctfltr' ),
					'date'          => esc_html__( 'Newness', 'prdctfltr' ),
					'price'         => esc_html__( 'Price: low to high', 'prdctfltr' ),
					'price-desc'    => esc_html__( 'Price: high to low', 'prdctfltr' ),
					'rand'          => esc_html__( 'Random Products', 'prdctfltr' ),
					'title'         => esc_html__( 'Product Name', 'prdctfltr' ),
				) );

				foreach( $orderby as $k => $v ) {
					$set[] = array( 'value' => $k, 'title' => $v );
				}

				return $set;
			break;
			case 'vendor' :
				$vendors = get_users( array( 'fields' => array( 'ID', 'display_name' ) ) );

				foreach ( $vendors as $vendor ) {
					$set[] = array( 'value' => $vendor->ID, 'title' => $vendor->display_name );
				}

				return $set;
			break;
			default :
				return array();
			break;
		}

	}

	public static function __build_price_filter() {
		return array(
			'filter' => 'price',
			'title' => self::___u( self::$preset['wc_settings_prdctfltr_price_title'] ),
			'desc' => self::___u( self::$preset['wc_settings_prdctfltr_price_description'] ),
			'hide_elements' => array_filter( array(
				self::___u( self::$preset['wc_settings_prdctfltr_price_none'] ) == 'yes' ? 'none' : false,
				self::___u( self::$preset['wc_settings_prdctfltr_price_title'] ) == 'false' ? 'title' : false,
			) ),
			'style' => self::__merge_style_and_term_keys( 'price', self::___u( self::$preset['wc_settings_prdctfltr_price_term_customization'] ), self::___u( self::$preset['wc_settings_prdctfltr_price_filter_customization'] ) ),
		);
	}

	public static function __merge_style_and_term_keys( $filter, $style_key, $terms_key ) {

		$terms = array();
		$opt_terms = get_option( $terms_key, false );

		$style = array(
			'filter' => $filter,
			'key' => $style_key,
		);
		$opt_style = get_option( $style_key, false );

		if ( $opt_style === false ) {
			$opt_style = array(
				'style' => '',
				'settings' => array(),
			);
		}
 
		if ( $opt_terms !== false ) {
			$i=0;
			foreach( $opt_terms['settings'] as $value => $title ) {
				$terms[] = array(
					'id' => 'c' . $i,
					'slug' => 'c' . $i,
					'value' => $value,
					'title' => $title,
					'data' => false,
				);
				if ( $opt_style['style'] !== 'text' && array_key_exists( $value, $opt_style['settings'] ) ) {
					$terms[$i]['data'] = $opt_style['settings'][$value];
				}
				if ( array_key_exists( 'tooltip_' . $value, $opt_style['settings'] ) ) {
					$terms[$i]['tooltip'] = $opt_style['settings']['tooltip_' . $value];
				}
				$i++;
			}
			if ( !empty( $terms ) ) {
				$style['terms'] = $terms;
			}
		}
		else {
			$terms = self::__get_terms_from_options( $filter );
			if ( !empty( $terms ) ) {
				$style['terms'] = $terms;
			}
		}

		return self::__make_a_text_style( $opt_style, $style );
	}

	public static function __get_terms_from_options( $filter ) {
		switch( $filter ) {
			case 'price' :
				return self::__get_terms_from_options_for_price();
			break;
			case 'per_page' :
				return self::__get_terms_from_options_for_per_page();
			break;
			default :
			break;
		}
	}

	public static function __get_terms_from_options_for_price() {

		$prices = array();
		$prices_currency = array();
		$array = array();

		$price_set = self::___u( self::$preset['wc_settings_prdctfltr_price_range'] );
		$price_add = self::___u( self::$preset['wc_settings_prdctfltr_price_range_add'] );
		$price_limit = self::___u( self::$preset['wc_settings_prdctfltr_price_range_limit'] );

		for ( $i = 0; $i < $price_limit; $i++ ) {

			if ( $i == 0 ) {
				$min_price = 0;
				$max_price = $price_set;
			}
			else {
				$min_price = $price_set+($i-1)*$price_add;
				$max_price = $price_set+$i*$price_add;
			}

			$prices[$i] = self::price_to_float( strip_tags( wc_price( apply_filters( 'wcml_raw_price_amount', $min_price ) ) ) ) . '-' . ( ($i+1) == $price_limit ? '' : self::price_to_float( strip_tags( wc_price( apply_filters( 'wcml_raw_price_amount', $max_price ) ) ) ) );

			$prices_currency[$i] = strip_tags( wc_price( apply_filters( 'wcml_raw_price_amount', $min_price ) ) ) . ( $i+1 == $price_limit ? '+' : ' - ' . strip_tags( wc_price( apply_filters( 'wcml_raw_price_amount', $max_price ) ) ) );

			$array[] = array(
				'id' => 'c' . $i,
				'slug' => 'c' . $i,
				'value' => $prices[$i],
				'title' => $prices_currency[$i],
				'tooltip' => $prices_currency[$i],
				'data' => false,
			);

		}

		return $array;
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

	public static function __get_terms_from_options_for_per_page() {

		$array = array();

		$perpage_set = intval( self::___u( self::$preset['wc_settings_prdctfltr_perpage_range'] ) );
		$perpage_limit = intval( self::___u( self::$preset['wc_settings_prdctfltr_perpage_range_limit'] ) );

		for ( $i = 1; $i <= $perpage_limit; $i++ ) {
			$array[] = array(
				'id' => 'c' . ($i-1),
				'slug' => 'c' . ($i-1),
				'value' => $perpage_set*$i,
				'title' => $perpage_set*$i . ' ' . ( self::___u( self::$preset['wc_settings_prdctfltr_perpage_label'] ) == '' ? esc_html__( 'Products', 'prdctfltr' ) : self::$preset['wc_settings_prdctfltr_perpage_label'] ),
			);
		}

		return $array;

	}

	public static function __build_taxonomy_filter( $taxonomy, $adv = null ) {
		$filter = array();

		if ( isset( $adv ) ) {
			$filter = self::__get_advanced_filter( $adv );
		}
		else {
			if ( !taxonomy_exists( $taxonomy ) ) {
				return false;
			}
			$filter = self::__get_taxonomy_filter( $taxonomy );
		}

		return $filter;
	}

	public static function __get_advanced_filter( $adv ) {
		$filters = self::___u( self::$preset['wc_settings_prdctfltr_advanced_filters'] );

		if ( !taxonomy_exists( $filters['pfa_taxonomy'][$adv] ) ) {
			return false;
		}

		return array(
			'filter' => 'taxonomy',
			'taxonomy' => self::___u( $filters['pfa_taxonomy'][$adv] ),
			'title' => self::___u( $filters['pfa_title'][$adv] ),
			'desc' => self::___u( $filters['pfa_description'][$adv] ),
			'include' => self::__fix_taxonomy_includes( $filters['pfa_taxonomy'][$adv], $filters['pfa_include'][$adv] ),
			'orderby' => self::___u( $filters['pfa_orderby'][$adv] ),
			'order' => self::___u( $filters['pfa_order'][$adv] ),
			'limit' => self::___u( $filters['pfa_limit'][$adv] ),
			'hierarchy' => self::___u( $filters['pfa_hierarchy'][$adv] ),
			'hierarchy_mode' => self::___u( $filters['pfa_mode'][$adv] ),
			'hierarchy_expand' => self::___u( $filters['pfa_hierarchy_mode'][$adv] ),
			'multiselect' => self::___u( $filters['pfa_multiselect'][$adv] ),
			'multiselect_relation' => self::___u( $filters['pfa_relation'][$adv] ),
			'selection_reset' => self::___u( $filters['pfa_selection'][$adv] ),
			'adoptive' => self::__fix_adoptive( $filters['pfa_adoptive'][$adv] ),
			'hide_elements' => array_filter( array(
				$filters['pfa_none'][$adv] == 'yes' ? 'none' : false,
				$filters['pfa_title'][$adv] == 'false' ? 'title' : false,
			) ),
			'style' => self::__make_new_term_key_for_taxonomy( $filters['pfa_taxonomy'][$adv], $filters['pfa_term_customization'][$adv] ),
		);
	}

	public static function __make_new_term_key_for_taxonomy( $filter, $style_key ) {

		if ( empty( $style_key ) ) {
			return array();
		}

		$opt_style = get_option( $style_key, false );

		if ( empty( $opt_style ) ) {
			return array();
		}

		$style = array(
			'filter' => $filter,
			'key' => $style_key,
		);

		$terms = array();
		$opt_terms = self::_terms_get( $filter );

		$i=0;

		foreach( $opt_terms as $term ) {
			
			if ( array_key_exists( 'term_' . $term['slug'], $opt_style['settings'] ) ) {
				$terms[] = array(
					'value' => '',
					'title' => '',
					'data' => false,
					'id' => $term['id'],
					'slug' => $term['slug'],
				);
				if ( $opt_style['style'] !== 'text' && array_key_exists( 'term_' . $term['slug'], $opt_style['settings'] ) ) {
					$terms[$i]['data'] = $opt_style['settings']['term_' . $term['slug']];
				}
				if ( array_key_exists( 'tooltip_' . $term['slug'], $opt_style['settings'] ) ) {
					$terms[$i]['tooltip'] = $opt_style['settings']['tooltip_' . $term['slug']];
				}
				$i++;
			}
		}

		if ( !empty( $terms ) ) {
			$style['terms'] = $terms;
		}

		return self::__make_a_text_style( $opt_style, $style );
	}

	public static function __build_meta_filter( $mta ) {
		$filters = self::___u( self::$preset['wc_settings_prdctfltr_meta_filters'] );

		return array(
			'filter' => 'meta',
			'key' => self::___u( $filters['pfm_key'][$mta] ),
			'compare' => self::___u( $filters['pfm_compare'][$mta] ),
			'type' => self::___u( $filters['pfm_type'][$mta] ),
			'title' => self::___u( $filters['pfm_title'][$mta] ),
			'desc' => self::___u( $filters['pfm_description'][$mta] ),
			'limit' => self::___u( $filters['pfm_limit'][$mta] ),
			'multiselect' => self::___u( $filters['pfm_multiselect'][$mta] ),
			'multiselect_relation' => self::___u( $filters['pfm_relation'][$mta] ),
			'hide_elements' => array_filter( array(
				$filters['pfm_none'][$mta] == 'yes' ? 'none' : false,
				$filters['pfm_title'][$mta] == 'false' ? 'title' : false,
			) ),
			'style' => self::__merge_style_and_term_keys_for_meta( $filters['pfm_term_customization'][$mta], $filters['pfm_filter_customization'][$mta] ),
		);
	}
	
	public static function __merge_style_and_term_keys_for_meta( $style_key, $terms_key ) {

		$opt_terms = get_option( $terms_key, false );

		$opt_style = get_option( $style_key, false );

		if ( empty( $opt_style ) && empty( $opt_terms ) ) {
			return array();
		}

		$terms = array();
		$style = array(
			'filter' => 'meta',
			'key' => $style_key,
		);

		if ( $opt_terms !== false ) {
			$i=0;
			foreach( $opt_terms['settings'] as $meta ) {
				$terms[] = array(
					'id' => 'c' . $i,
					'value' => $meta['value'],
					'title' => $meta['text'],
					'data' => false,
					'slug' => $meta['value'],
				);
				if ( $opt_style['style'] !== 'text' && is_array( $opt_style['settings'] ) && array_key_exists( 'term_' . $meta['value'], $opt_style['settings'] ) ) {
					$terms[$i]['data'] = $opt_style['settings']['term_' . $meta['value']];
				}
				if ( is_array( $opt_style['settings'] ) && array_key_exists( 'tooltip_' . $meta['value'], $opt_style['settings'] ) ) {
					$terms[$i]['tooltip'] = $opt_style['settings']['tooltip_' . $meta['value']];
				}
				$i++;
			}

			if ( !empty( $terms ) ) {
				$style['terms'] = $terms;
			}
		}

		return self::__make_a_text_style( $opt_style, $style );
	}

	public static function __make_a_text_style( $opt_style, $style ) {
		if ( $opt_style !== false ) {
			if ( isset( $opt_style['style'] ) ) {

				if ( $opt_style['style'] == 'image-text' ) {
					$style['label'] = 'side';
					$style['style'] = array(
						'type' => 'image',
					);
				}
				else {
					$style['style'] = array(
						'type' => $opt_style['style'],
					);
				}

				if ( $opt_style['style'] == 'text' ) {
					$style['style']['css'] = $opt_style['settings']['type'];
					$style['style']['normal'] = $opt_style['settings']['normal'];
					$style['style']['active'] = $opt_style['settings']['active'];
					$style['style']['disabled'] = $opt_style['settings']['disabled'];
				}
			}
		}
		return $style;
	}

	public static function __build_range_filter( $rng ) {
		$filters = self::___u( self::$preset['wc_settings_prdctfltr_range_filters'] );

		if ( isset( $filters['pfr_custom'][$rng] ) && is_string( $filters['pfr_custom'][$rng] ) && substr( $filters['pfr_custom'][$rng], 0, 1 ) == '{' ) {
			$custom = json_decode( $filters['pfr_custom'][$rng], true );
		}

		return array(
			'filter' => 'range',
			'taxonomy' => self::___u( $filters['pfr_taxonomy'][$rng] ),
			'title' => self::___u( $filters['pfr_title'][$rng] ),
			'desc' => self::___u( $filters['pfr_description'][$rng] ),
			'include' => self::__fix_taxonomy_includes( $filters['pfr_taxonomy'][$rng], $filters['pfr_include'][$rng] ),
			'orderby' => self::___u( $filters['pfr_orderby'][$rng] ),
			'order' => self::___u( $filters['pfr_order'][$rng] ),
			'design' => self::___u( $filters['pfr_style'][$rng] ),
			'grid' => self::___u( $filters['pfr_grid'][$rng] ),
			'start' => isset( $custom['start'] ) ? $custom['start'] : '',
			'end' => isset( $custom['end'] ) ? $custom['end'] : '',
			'prefix' => isset( $custom['prefix'] ) ? $custom['prefix'] : '',
			'postfix' => isset( $custom['postfix'] ) ? $custom['postfix'] : '',
			'step' => isset( $custom['step'] ) ? $custom['step'] : '',
			'grid_num' => isset( $custom['grid_num'] ) ? $custom['grid_num'] : '',
			'adoptive' => self::__fix_adoptive( self::___u( $filters['pfr_adoptive'][$rng] ) ),
			'hide_elements' => array(
				$filters['pfr_title'][$rng] == 'false' ? 'title' : false,
			),
		);
	}

	public static function __get_taxonomy_filter( $taxonomy ) {
		switch( $taxonomy ) {
			case 'product_cat' :
				return array(
					'filter' => 'taxonomy',
					'taxonomy' => 'product_cat',
					'title' => self::___u( self::$preset['wc_settings_prdctfltr_cat_title'] ),
					'desc' => self::___u( self::$preset['wc_settings_prdctfltr_cat_description'] ),
					'include' => self::__fix_taxonomy_includes( 'product_cat', self::___u( self::$preset['wc_settings_prdctfltr_include_cats'] ) ),
					'orderby' => self::___u( self::$preset['wc_settings_prdctfltr_cat_orderby'] ),
					'order' => self::___u( self::$preset['wc_settings_prdctfltr_cat_order'] ),
					'limit' => self::___u( self::$preset['wc_settings_prdctfltr_cat_limit'] ),
					'hierarchy' => self::___u( self::$preset['wc_settings_prdctfltr_cat_hierarchy'] ),
					'hierarchy_mode' => self::___u( self::$preset['wc_settings_prdctfltr_cat_mode'] ),
					'hierarchy_expand' => self::___u( self::$preset['wc_settings_prdctfltr_cat_hierarchy_mode'] ),
					'multiselect' => self::___u( self::$preset['wc_settings_prdctfltr_cat_multi'] ),
					'multiselect_relation' => self::___u( self::$preset['wc_settings_prdctfltr_cat_relation'] ),
					'selection_reset' => self::___u( self::$preset['wc_settings_prdctfltr_cat_selection'] ),
					'adoptive' => self::__fix_adoptive( self::___u( self::$preset['wc_settings_prdctfltr_cat_adoptive'] ) ),
					'hide_elements' => array_filter( array(
						self::___u( self::$preset['wc_settings_prdctfltr_cat_none'] ) == 'yes' ? 'none' : false,
						self::___u( self::$preset['wc_settings_prdctfltr_cat_title'] ) == 'false' ? 'title' : false,
					) ),
					'style' => self::__make_new_term_key_for_taxonomy( $taxonomy, self::___u( self::$preset['wc_settings_prdctfltr_cat_term_customization'] ) ),
				);
			break;
			case 'product_tag' :
				return array(
					'filter' => 'taxonomy',
					'taxonomy' => 'product_tag',
					'title' => self::___u( self::$preset['wc_settings_prdctfltr_tag_title'] ),
					'desc' => self::___u( self::$preset['wc_settings_prdctfltr_tag_description'] ),
					'include' => self::__fix_taxonomy_includes( 'product_tag', self::___u( self::$preset['wc_settings_prdctfltr_include_tags'] ) ),
					'orderby' => self::___u( self::$preset['wc_settings_prdctfltr_tag_orderby'] ),
					'order' => self::___u( self::$preset['wc_settings_prdctfltr_tag_order'] ),
					'limit' => self::___u( self::$preset['wc_settings_prdctfltr_tag_limit'] ),
					'multiselect' => self::___u( self::$preset['wc_settings_prdctfltr_tag_multi'] ),
					'multiselect_relation' => self::___u( self::$preset['wc_settings_prdctfltr_tag_relation'] ),
					'selection_reset' => self::___u( self::$preset['wc_settings_prdctfltr_tag_selection'] ),
					'adoptive' => self::__fix_adoptive( self::___u( self::$preset['wc_settings_prdctfltr_tag_adoptive'] ) ),
					'hide_elements' => array_filter( array(
						self::___u( self::$preset['wc_settings_prdctfltr_tag_none'] ) == 'yes' ? 'none' : false,
						self::___u( self::$preset['wc_settings_prdctfltr_tag_title'] ) == 'false' ? 'title' : false,
					) ),
					'style' => self::__make_new_term_key_for_taxonomy( $taxonomy, self::___u( self::$preset['wc_settings_prdctfltr_tag_term_customization'] ) ),
				);
			break;
			case 'characteristics' :
				return array(
					'filter' => 'taxonomy',
					'taxonomy' => 'characteristics',
					'title' => self::___u( self::$preset['wc_settings_prdctfltr_custom_tax_title'] ),
					'desc' => self::___u( self::$preset['wc_settings_prdctfltr_custom_tax_description'] ),
					'include' => self::__fix_taxonomy_includes( 'characteristics', self::___u( self::$preset['wc_settings_prdctfltr_include_chars'] ) ),
					'orderby' => self::___u( self::$preset['wc_settings_prdctfltr_custom_tax_orderby'] ),
					'order' => self::___u( self::$preset['wc_settings_prdctfltr_custom_tax_order'] ),
					'limit' => self::___u( self::$preset['wc_settings_prdctfltr_custom_tax_limit'] ),
					'multiselect' => self::___u( self::$preset['wc_settings_prdctfltr_chars_multi'] ),
					'multiselect_relation' => self::___u( self::$preset['wc_settings_prdctfltr_custom_tax_relation'] ),
					'selection_reset' => self::___u( self::$preset['wc_settings_prdctfltr_chars_selection'] ),
					'adoptive' => self::__fix_adoptive( self::___u( self::$preset['wc_settings_prdctfltr_chars_adoptive'] ) ),
					'hide_elements' => array_filter( array(
						self::___u( self::$preset['wc_settings_prdctfltr_chars_none'] ) == 'yes' ? 'none' : false,
						self::___u( self::$preset['wc_settings_prdctfltr_custom_tax_title'] ) == 'false' ? 'title' : false,
					) ),
					'style' => self::__make_new_term_key_for_taxonomy( $taxonomy, self::___u( self::$preset['wc_settings_prdctfltr_chars_term_customization'] ) ),
				);
			break;
			default :
				return array(
					'filter' => 'taxonomy',
					'taxonomy' => $taxonomy,
					'title' => self::___u( self::$preset['wc_settings_prdctfltr_' . $taxonomy . '_title'] ),
					'desc' => self::___u( self::$preset['wc_settings_prdctfltr_' . $taxonomy . '_description'] ),
					'include' => self::__fix_taxonomy_includes( $taxonomy, self::___u( self::$preset['wc_settings_prdctfltr_include_' . $taxonomy] ) ),
					'orderby' => self::___u( self::$preset['wc_settings_prdctfltr_' . $taxonomy . '_orderby'] ),
					'order' => self::___u( self::$preset['wc_settings_prdctfltr_' . $taxonomy . '_order'] ),
					'limit' => self::___u( self::$preset['wc_settings_prdctfltr_' . $taxonomy . '_limit'] ),
					'hierarchy' => self::___u( self::$preset['wc_settings_prdctfltr_' . $taxonomy . '_hierarchy'] ),
					'hierarchy_mode' => self::___u( self::$preset['wc_settings_prdctfltr_' . $taxonomy . '_mode'] ),
					'hierarchy_expand' => self::___u( self::$preset['wc_settings_prdctfltr_' . $taxonomy . '_hierarchy_mode'] ),
					'multiselect' => self::___u( self::$preset['wc_settings_prdctfltr_' . $taxonomy . '_multi'] ),
					'multiselect_relation' => self::___u( self::$preset['wc_settings_prdctfltr_' . $taxonomy . '_relation'] ),
					'selection_reset' => self::___u( self::$preset['wc_settings_prdctfltr_' . $taxonomy . '_selection'] ),
					'adoptive' => self::__fix_adoptive( self::___u( self::$preset['wc_settings_prdctfltr_' . $taxonomy . '_adoptive'] ) ),
					'hide_elements' => array_filter( array(
						self::___u( self::$preset['wc_settings_prdctfltr_' . $taxonomy . '_none'] ) == 'yes' ? 'none' : false,
						self::___u( self::$preset['wc_settings_prdctfltr_' . $taxonomy . '_title'] ) == 'false' ? 'title' : false,
					) ),
					'style' => self::__make_new_term_key_for_taxonomy( $taxonomy, self::___u( self::$preset['wc_settings_prdctfltr_' . $taxonomy . '_term_customization'] ) ),
				);
			break;
		}
	}

	public static function __fix_adoptive( $adoptive ) {
		if ( $adoptive == 'yes' ) {
			if ( in_array( self::___u( self::$preset['wc_settings_prdctfltr_adoptive_style'] ), array( 'pf_adptv_default', 'pf_adptv_unclick', 'pf_adptv_click' ) ) ) {
				return self::$preset['wc_settings_prdctfltr_adoptive_style'];
			}
		}
		return 'no';
	}

	public static function __fix_taxonomy_includes( $taxonomy, $includes ) {
		$ids = array();

		if ( !empty( $includes ) && is_array( $includes ) ) {
			foreach( $includes as $slug ) {
				$data = term_exists( $slug, $taxonomy );
				if ( $data['term_id'] ) {
					$ids[] = $data['term_id'];
				}
			}
		}

		return array(
			'relation' => 'IN',
			'selected' => $ids,
		);
	}

	public static function __fix_max_height( $option ) {
		return self::___u( self::$preset['wc_settings_prdctfltr_limit_max_height'] ) == 'yes' ? self::___u( self::$preset['wc_settings_prdctfltr_max_height'] ) : false;
	}

	public static function __fix_hide_options( $option ) {
		return array_filter( array(
			self::___u( self::$preset['wc_settings_prdctfltr_disable_bar'] ) == 'yes' ? 'hide_top_bar' : false,
			self::___u( self::$preset['wc_settings_prdctfltr_disable_sale'] ) == 'yes' ? 'hide_sale_button' : false,
			self::___u( self::$preset['wc_settings_prdctfltr_disable_instock'] ) == 'yes' ? 'hide_instock_button' : false,
			self::___u( self::$preset['wc_settings_prdctfltr_disable_reset'] ) == 'yes' ? 'hide_reset_button' : false,
		) );
	}

	public static function __fix_preset_style( $option ) {
		$opt = self::___u( self::$preset['wc_settings_prdctfltr_style_preset'] ) ? self::$preset['wc_settings_prdctfltr_style_preset'] : 'pf_default';

		if ( $opt == 'pf_default_inline' ) {
			$opt = 'pf_default';
		}

		if ( $opt == 'pf_arrow_inline' ) {
			$opt = 'pf_arrow';
		}

		return $opt;
	}

	public static function __fix_responsive( $option ) {
		return self::___u( self::$preset['wc_settings_prdctfltr_mobile_preset'] ) && self::$preset['wc_settings_prdctfltr_mobile_preset'] !== 'default' ? 'switch' : 'none';
	}

	public static function __fix_responsive_preset( $option ) {
		return self::___u( self::$preset['wc_settings_prdctfltr_mobile_preset'] ) ? self::$preset['wc_settings_prdctfltr_mobile_preset'] : 'default';
	}

	public static function _terms_get_options( $terms, &$ready, &$level ) {
		foreach ( $terms as $term ) {
			$ready[] = array(
				'id' => $term->term_id,
				'name' => ( $level > 0 ? str_repeat( '&nbsp;&nbsp;', $level ) : '' ) . $term->name,
				'slug' => $term->slug,
			);

			if ( !empty( $term->children ) ) {
				$level++;
				self::_terms_get_options( $term->children, $ready, $level );
				$level--;
			}
		}
	}

	public static function _terms_sort_hierarchicaly( Array &$cats, Array &$into, $parentId = 0 ) {
		foreach ( $cats as $i => $cat ) {
			if ( $cat->parent == $parentId ) {
				$into[$cat->term_id] = $cat;
				unset($cats[$i]);
			}
		}
		foreach ( $into as $topCat ) {
			$topCat->children = array();
			self::_terms_sort_hierarchicaly( $cats, $topCat->children, $topCat->term_id );
		}
	}

	public static function _terms_get( $taxonomy ) {
		$ready = array();

		if ( taxonomy_exists( $taxonomy ) ) {

			$args = array(
				'hide_empty' => 0,
				'hierarchical' => ( is_taxonomy_hierarchical( $taxonomy ) ? 1 : 0 )
			);

			$terms = get_terms( $taxonomy, $args );

			if ( is_taxonomy_hierarchical( $taxonomy ) ) {
				$terms_sorted = array();
				self::_terms_sort_hierarchicaly( $terms, $terms_sorted );
				$terms = $terms_sorted;
			}

			if ( !empty( $terms ) && !is_wp_error( $terms ) ){
				$var =0;
				self::_terms_get_options( $terms, $ready, $var );
			}

		}

		return $ready;
	}

	public static function ___u( &$o ) {
		if ( isset( $o ) ) {
			return $o;
		}
		return false;
	}

}
