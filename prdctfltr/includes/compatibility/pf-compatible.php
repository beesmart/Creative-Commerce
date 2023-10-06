<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class XforWC_Product_Filters_Compatible_Settings {

	public static $options;

	public static function _fix_installation() {

		$templates = get_option( 'wc_settings_prdctfltr_enable', false );

		if ( $templates == 'yes' ) {
			$wc_templates = get_option( 'wc_settings_prdctfltr_enable_overrides', false );
			if ( is_array( $wc_templates ) ) {
				if ( in_array( 'result-count', $wc_templates ) ) {
					self::$options['install']['templates']['result_count'] = 'default';
				}
				else if ( in_array( 'orderby', $wc_templates ) ) {
					self::$options['install']['templates']['orderby'] = 'default';
				}
				
			}
		}

		if ( $templates == 'action' ) {
			$hook = explode( ':', get_option( 'wc_settings_prdctfltr_enable_action', false ) );
			$hook[1] = isset( $hook[1] ) ? floatval( $hook[1] ) : 10;
			self::$options['install']['actions'][] = array(
				'name' => ucwords( str_replace( '_', ' ', $hook[0] ) ) . ' ' . esc_html__( 'Filter', 'prdctfltr' ),
				'hook' => $hook[0],
				'priority' => $hook[1],
			);
		}

	}

	public static function _fix_ajax() {

		$options = array(
			'wrapper' => get_option( 'wc_settings_prdctfltr_ajax_class', false ),
			'category' => get_option( 'wc_settings_prdctfltr_ajax_category_class', false ),
			'product' => get_option( 'wc_settings_prdctfltr_ajax_product_class', false ),
			'pagination' => get_option( 'wc_settings_prdctfltr_ajax_pagination_class', false ),
			'pagination_function' => get_option( 'wc_settings_prdctfltr_ajax_pagination', false ),
			'pagination_type' => get_option( 'wc_settings_prdctfltr_pagination_type', false ),
			'result_count' => get_option( 'wc_settings_prdctfltr_ajax_count_class', false ),
			'order_by' => get_option( 'wc_settings_prdctfltr_ajax_orderby_class', false ),
			'failsafe' => get_option( 'wc_settings_prdctfltr_ajax_failsafe', false ),
			'js' => get_option( 'wc_settings_prdctfltr_ajax_js', false ),
			'columns' => get_option( 'wc_settings_prdctfltr_ajax_columns', false ),
			'rows' => get_option( 'wc_settings_prdctfltr_ajax_rows', false ),
			'animation' => get_option( 'wc_settings_prdctfltr_product_animation', false ),
			'scroll_to' => get_option( 'wc_settings_prdctfltr_after_ajax_scroll', false ),
			'permalinks' => get_option( 'wc_settings_prdctfltr_ajax_permalink', false ),
			'dont_load' => get_option( 'wc_settings_prdctfltr_ajax_templates', false ),
		);

		self::$options['install']['ajax'] = array_merge( array(
			'enable' => get_option( 'wc_settings_prdctfltr_use_ajax', false ),
			'automatic' => 'yes',
		), $options );

	}

	public static function _fix_general() {

		$options = array(
			'hide_empty' => get_option( 'wc_settings_prdctfltr_hideempty', false ),
			'variable_images' => get_option( 'wc_settings_prdctfltr_use_variable_images', false ),
			'analytics' => get_option( 'wc_settings_prdctfltr_use_analytics', false ),
			'clear_all' => get_option( 'wc_settings_prdctfltr_clearall', false ),
			'force_product' => get_option( 'wc_settings_prdctfltr_force_product', false ),
			'force_action' => get_option( 'wc_settings_prdctfltr_force_action', false ),
			'force_redirects' => get_option( 'wc_settings_prdctfltr_force_redirects', false ),
			'remove_single_redirect' => get_option( 'wc_settings_prdctfltr_remove_single_redirect', false ),
			'supported_overrides' => get_option( 'wc_settings_prdctfltr_more_overrides', false ),
		);

		self::$options['general'] = $options;

	}

	public static function _fix_presets() {

		$options = array();
		$presets = get_option( 'prdctfltr_templates', array() );

		foreach( $presets as $preset => $value ) {
			$options[] = array(
				'slug' => $preset,
				'name' => ucfirst( str_replace( '-', ' ', $preset ) ),
			);
		}

		self::$options['presets'] = $options;

	}

	public static function _fix_taxonomies() {
		$characteristics = get_option( 'wc_settings_prdctfltr_custom_tax', false );

		if ( $characteristics == 'yes' ) {
			update_option( '_prdctfltr_taxonomies', array(
					array(
						'name' => 'Characteristic',
						'plural_name' => 'Characteristics',
						'hierarchy' => 'no',
					),
				)
			);

			delete_option( 'wc_settings_prdctfltr_custom_tax' );
		}
	}


	public static function _fix_overrides() {

		$overrides = get_option( 'prdctfltr_overrides', false );

		if ( $overrides !== false && self::$options['general']['supported_overrides'] ) {
			$manager = array();

			foreach( self::$options['general']['supported_overrides'] as $key ) {
				if ( isset( $overrides[$key] ) && is_array( $overrides[$key] ) ) {
					$manager[$key] = array();
					foreach( $overrides[$key] as $k => $v ) {
						$term = get_term_by( 'slug', $k, $key );
						if ( !empty( $term ) && !is_wp_error( $term ) ) {
							$manager[$key][] = array(
								'name' => $term->name,
								'term' => $term->term_id,
								'slug' => $term->slug,
								'preset' => $v,
							);
						}
					}
				}
			}

			return $manager;
		}

		return false;

	}

	public static function fix_options() {

		self::_fix_installation();
		self::_fix_ajax();
		self::_fix_general();
		self::_fix_presets();
		self::_fix_taxonomies();

		return self::$options;

	}

}
