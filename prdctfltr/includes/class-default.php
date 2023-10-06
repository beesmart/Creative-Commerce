<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class XforWC_Product_Filters_Defaults {

	public static $options = array();
	public static $preset = array();

	public static $defaults = array(
		'autoload' => array(
			'install' => array (
				'templates' => array(
					'result_count' => '_do_not',
					'orderby' => '_do_not',
				),
				'actions' => array(),
				'ajax' => array (
					'enable' => 'yes',
					'automatic' => 'yes',
					'wrapper' => '',
					'category' => '',
					'product' => '',
					'pagination' => '',
					'pagination_function' => '',
					'pagination_type' => 'default',
					'result_count' => '',
					'order_by' => '',
					'failsafe' => array (
						'wrapper',
						'product',
					),
					'js' => '',
					'columns' => '',
					'rows' => '',
					'animation' => 'default',
					'scroll_to' => 'none',
					'permalinks' => 'no',
					'dont_load' => array(),
				),
			),
			'general' => array (
				'hide_empty' => 'no',
				'variable_images' => 'yes',
				'analytics' => false,
				'clear_all' => array(),
				'force_product' => 'no',
				'force_action' => 'no',
				'force_redirects' => 'no',
				'remove_single_redirect' => 'yes',
				'supported_overrides' => array(
					'product_cat',
					'product_tag',
				),
				'register_taxonomy' => array(),
			),
			'scripts' => array(),
		),

		'preset' => array (
			'filters' => array (
				array (
					'taxonomy' => 'product_cat',
					'desc' => '',
					'include' => '',
					'orderby' => '',
					'order' => 'ASC',
					'limit' => '',
					'hierarchy' => 'yes',
					'hierarchy_mode' => 'showall',
					'hierarchy_expand' => 'yes',
					'multiselect' => 'no',
					'multiselect_relation' => 'IN',
					'selection_reset' => 'no',
					'adoptive' => 'no',
					'term_count' => 'yes',
					'term_search' => 'yes',
					'hide_elements' => array ( 'none', ),
					'title' => '', 'filter' => 'taxonomy',
				),
				array (
					'desc' => '',
					'design' => 'thin',
					'grid' => 'yes',
					'start' => '',
					'end' => '',
					'prefix' => '',
					'postfix' => '',
					'step' => '',
					'grid_num' => '1',
					'hide_elements' => '',
					'style' => '',
					'title' => '',
					'taxonomy' => 'price',
					'filter' => 'range',
					'type' => 'range',
				),
				array (
					'desc' => '',
					'include' => '',
					'selection_reset' => 'no',
					'hide_elements' => array ( 'none', ),
					'title' => '',
					'filter' => 'orderby',
				),
			),

			'general' => array (
				'instant' => 'yes',
				'step_selection' => 'no',
				'reorder_selected' => 'no',
				'collectors' => array ( 'collector', ),
				'collector_style' => 'flat',
				'form_action' => '',
				'no_products' => '',
			),

			'style' => array (
				'style' => 'pf_default',
				'always_visible' => 'no',
				'mode' => 'pf_mod_multirow',
				'columns' => '3',
				'max_height' => '',
				'js_scroll' => 'no',
				'checkbox_style' => 'prdctfltr_round',
				'hierarchy_style' => 'prdctfltr_hierarchy_lined',
				'filter_icon' => '',
				'filter_title' => '',
				'filter_button' => '',
				'button_position' => 'bottom',
				'content_align' => 'left',
				'hide_elements' => '',
				'loading_animation' => 'css-spinner-full',
				'_tx_sale' => '',
				'_tx_instock' => '',
				'_tx_clearall' => '',
			),

			'adoptive' => array (
				'enable' => 'no',
				'active_on' => 'always',
				'depend_on' => '',
				'term_counts' => 'default',
				'reorder_selected' => 'no',
			),

			'responsive' => array (
				'behaviour' => 'none',
				'resolution' => '768',
				'preset' => '',
			),
		),
	);

	public static function __get_default_option( $option ) {
		if ( !is_array( $option ) ) {
			return false;
		}

		$p = self::$defaults;

		foreach( $option as $key ) {
			if ( isset( $p[$key] ) ) {
				$p = $p[$key];
			}
		}

		return $p;
	}



	public static function __get_default_preset( $options ) {

		self::$preset = $options;

		self::___default_general();
		self::___default_style();
		self::___default_adoptive();
		self::___default_responsive();

		return self::$preset;
	}

	public static function ___default_general() {
		if ( empty( self::$preset['general'] ) ) {
			self::$preset['general'] = array();
			if ( !isset( self::$preset['filters'] ) ) {
				self::$preset['filters'] = self::$defaults['preset']['filters'];
			}
		}

		self::$preset['general'] = array_merge( self::$defaults['preset']['general'], self::$preset['general'] );
	}

	public static function ___default_style() {
		if ( empty( self::$preset['style'] ) ) {
			self::$preset['style'] = array();
		}

		self::$preset['style'] = array_merge( self::$defaults['preset']['style'], self::$preset['style'] );
	}

	public static function ___default_adoptive() {
		if ( empty( self::$preset['adoptive'] ) ) {
			self::$preset['adoptive'] = array();
		}

		self::$preset['adoptive'] = array_merge( self::$defaults['preset']['adoptive'], self::$preset['adoptive'] );
	}

	public static function ___default_responsive() {
		if ( empty( self::$preset['responsive'] ) ) {
			self::$preset['responsive'] = array();
		}

		self::$preset['responsive'] = array_merge( self::$defaults['preset']['responsive'], self::$preset['responsive'] );
	}

	public static function __get_default_autoload( $options ) {

		self::$options = $options;

		self::___check_install();
		self::___check_general();
		//self::___check_manager();
		//self::___check_presets();

		self::___check_more();

		return self::$options;

	}

	public static function ___check_install() {
		if ( empty( self::$options['install'] ) ) {
			self::$options['install'] = self::$defaults['autoload']['install'];
		}

		if ( empty( self::$options['install']['templates'] ) ) {
			self::$options['install']['templates'] = array();
		}

		self::$options['install']['templates'] = array_merge( self::$defaults['autoload']['install']['templates'], self::$options['install']['templates'] );

		if ( empty( self::$options['install']['actions'] ) ) {
			self::$options['install']['actions'] = array();
		}

		if ( empty( self::$options['install']['ajax'] ) ) {
			self::$options['install']['ajax'] = array();
		}

		self::$options['install']['ajax'] = array_merge( self::$defaults['autoload']['install']['ajax'], self::$options['install']['ajax'] );
	}

	public static function ___check_general() {
		if ( empty( self::$options['general'] ) ) {
			self::$options['general'] = array();
		}
		self::$options['general'] = array_merge( self::$defaults['autoload']['general'], self::$options['general'] );
	}

	public static function ___check_manager() {
		if ( empty( self::$options['manager'] ) ) {
			self::$options['manager'] = array();
		}
	}

	public static function ___check_presets() {
		if ( empty( self::$options['presets'] ) ) {
			self::$options['presets'] = array();
		}
	}

	public static function ___check_more() {
		if ( empty( self::$options['install']['ajax']['dont_load'] ) ) {
			self::$options['install']['ajax']['dont_load'] = array();
		}
	}

	public static function __get_default( $type ) {
		return self::$defaults[$type];
	}

}
