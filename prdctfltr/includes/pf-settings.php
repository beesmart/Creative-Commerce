<?php

	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	class XforWC_Product_Filters_Settings {

		public static $settings = null;
		public static $presets = null;

		public static $plugin = array();

		public static function init() {

			self::$plugin = array(
				'name' => esc_html__( 'Product Filter for WooCommerce', 'prdctfltr' ),
				'xforwc' => esc_html__( 'Product Filters', 'prdctfltr' ),
				'slug' => 'product-filters',
				'label' => 'product_filter',
				'image' => Prdctfltr()->plugin_url() . '/includes/images/product-filter-for-woocommerce-elements.png',
				'path' => 'prdctfltr/prdctfltr',
				'version' => XforWC_Product_Filters::$version,
			);

			if ( isset($_GET['page'], $_GET['tab']) && ($_GET['page'] == 'wc-settings' ) && $_GET['tab'] == 'product_filter' ) {
				add_filter( 'svx_plugins_settings', array( 'XforWC_Product_Filters_Settings', 'get_settings' ), 50 );
				add_action( 'admin_enqueue_scripts', __CLASS__ . '::scripts', 9 );
			}

			if ( isset($_GET['page']) && ($_GET['page'] == 'xforwoocommerce' )) {
				add_action( 'admin_enqueue_scripts', __CLASS__ . '::scripts', 9 );
			}

			if ( function_exists( 'XforWC' ) ) {
				add_filter( 'xforwc_settings', array( 'XforWC_Product_Filters_Settings', 'xforwc' ), 9999999101 );
				add_filter( 'xforwc_svx_get_product_filters', array( 'XforWC_Product_Filters_Settings', '_get_settings_xforwc' ) );
			}

			add_filter( 'svx_plugins', array( 'XforWC_Product_Filters_Settings', 'add_plugin' ), 0 );

			add_action( 'wp_ajax_prdctfltr_analytics_reset', __CLASS__ . '::analytics_reset' );
		}

		public static function xforwc( $settings ) {
			$settings['plugins'][] = self::$plugin;

			return $settings;
		}

		public static function add_plugin( $plugins ) {
			$plugins[self::$plugin['label']] = array(
				'slug' => self::$plugin['label'],
				'name' => self::$plugin['xforwc']
			);

			return $plugins;
		}

		public static function _get_settings_xforwc() {
			$settings = self::get_settings( array() );
			return $settings[self::$plugin['label']];
		}

		public static function scripts() {
			wp_register_script( 'google-api', (is_ssl()?'https://':'http://') . 'www.google.com/jsapi', array(), false, true );
			wp_enqueue_script( 'google-api' );

			wp_register_script( 'product-filter', Prdctfltr()->plugin_url() . '/includes/js/svx-admin.js', array( 'jquery' ), Prdctfltr()->version(), true );
			wp_enqueue_script( 'product-filter' );
		}

		public static function ___get_taxonomy_option() {
			return array(
				'name' => esc_html__( 'Select Taxonomy', 'prdctfltr' ),
				'type' => 'select',
				'desc' => esc_html__( 'Select taxonomy for this filter', 'prdctfltr' ),
				'id'   => 'taxonomy',
				'options' => 'ajax:product_taxonomies:has_none',
				'default' => '',
				'class' => 'svx-update-list-title svx-selectize',
			);
		}

		public static function ___get_title_option() {
			return array(
				'name' => esc_html__( 'Title', 'prdctfltr' ),
				'type' => 'text',
				'desc' => esc_html__( 'Use alternative title', 'prdctfltr' ),
				'id'   => 'name',
				'default' => '',
			);
		}

		public static function ___get_desc_option() {
			return array(
				'name' => esc_html__( 'Description', 'prdctfltr' ),
				'type' => 'textarea',
				'desc' => esc_html__( 'Enter filter description', 'prdctfltr' ),
				'id'   => 'desc',
				'default' => '',
			);
		}
		
		public static function ___get_include_option() {
			return array(
				'name' => esc_html__( 'Include/Exclude', 'prdctfltr' ),
				'type' => 'include',
				'desc' => esc_html__( 'Select terms to include/exclude', 'prdctfltr' ),
				'id'   => 'include',
				'default' => false,
			);
		}

		public static function ___get_orderby_option() {
			return array(
				'name' => esc_html__( 'Order By', 'prdctfltr' ),
				'type' => 'select',
				'desc' => esc_html__( 'Select term order', 'prdctfltr' ),
				'id'   => 'orderby',
				'default' => '',
				'options' => array(
					'' => esc_html__( 'None (Custom Menu Order)', 'prdctfltr' ),
					'id' => esc_html__( 'ID', 'prdctfltr' ),
					'name' => esc_html__( 'Name', 'prdctfltr' ),
					'number' => esc_html__( 'Number', 'prdctfltr' ),
					'slug' => esc_html__( 'Slug', 'prdctfltr' ),
					'count' => esc_html__( 'Count', 'prdctfltr' )
				),
			);
		}

		public static function ___get_order_option() {
			return array(
				'name' => esc_html__( 'Order', 'prdctfltr' ),
				'type' => 'select',
				'desc' => esc_html__( 'Select ascending/descending', 'prdctfltr' ),
				'id'   => 'order',
				'default' => 'ASC',
				'options' => array(
					'ASC' => esc_html__( 'ASC', 'prdctfltr' ),
					'DESC' => esc_html__( 'DESC', 'prdctfltr' )
				),
			);
		}

		public static function ___get_limit_option() {
			return array(
				'name' => esc_html__( 'Show more', 'prdctfltr' ),
				'type' => 'number',
				'desc' => esc_html__( 'Show more button on term', 'prdctfltr' ),
				'id'   => 'limit',
				'default' => '',
			);
		}

		public static function ___get_hierarchy_option() {
			return array(
				'name' => esc_html__( 'Hierarchy', 'prdctfltr' ),
				'type' => 'checkbox',
				'desc' => esc_html__( 'Use hierarchy.', 'prdctfltr' ),
				'id'   => 'hierarchy',
				'default' => 'no',
			);
		}

		public static function ___get_hierarchy_mode_option() {
			return array(
				'name' => esc_html__( 'Hierarchy Mode', 'prdctfltr' ),
				'type' => 'select',
				'desc' => esc_html__( 'Select hierarchy mode', 'prdctfltr' ),
				'id'   => 'hierarchy_mode',
				'default' => 'showall',
				'options' => array(
					'showall' => esc_html__( 'Show all terms', 'prdctfltr' ),
					'drill' => esc_html__( 'Show current level terms (Drill filter)', 'prdctfltr' ),
					'drillback' => esc_html__( 'Show current level terms with parent term support (Drill filter)', 'prdctfltr' ),
					'subonly' => esc_html__( 'Show lower level hierarchy terms', 'prdctfltr' ),
					'subonlyback' => esc_html__( 'Show lower level hierarchy terms with parent term support', 'prdctfltr' )
				),
			);
		}

		public static function ___get_hierarchy_expand_option() {
			return array(
				'name' => esc_html__( 'Hierarchy Expand', 'prdctfltr' ),
				'type' => 'checkbox',
				'desc' => esc_html__( 'Expand hierarchy tree on load', 'prdctfltr' ),
				'id'   => 'hierarchy_expand',
				'default' => 'no',
			);
		}

		public static function ___get_multiselect_option() {
			return array(
				'name' => esc_html__( 'Multiselect', 'prdctfltr' ),
				'type' => 'checkbox',
				'desc' => esc_html__( 'Use multiselect', 'prdctfltr' ),
				'id'   => 'multiselect',
				'default' => 'no',
			);
		}

		public static function ___get_multiselect_relation_option() {
			return array(
				'name' => esc_html__( 'Multiselect Relation', 'prdctfltr' ),
				'type' => 'select',
				'desc' => esc_html__( 'Select multiselect relation', 'prdctfltr' ),
				'id'   => 'multiselect_relation',
				'default' => 'IN',
				'options' => array(
					'IN' => 'IN',
					'AND' => 'AND',
				),
			);
		}

		public static function ___get_selection_reset_option() {
			return array(
				'name' => esc_html__( 'Selection Reset', 'prdctfltr' ),
				'type' => 'checkbox',
				'desc' => esc_html__( 'Reset filters on select', 'prdctfltr' ),
				'id'   => 'selection_reset',
				'default' => 'no',
			);
		}

		public static function ___get_adoptive_option() {
			return array(
				'name' => esc_html__( 'Adoptive', 'prdctfltr' ),
				'type' => 'select',
				'desc' => esc_html__( 'Select adoptive filtering', 'prdctfltr' ),
				'id'   => 'adoptive',
				'default' => 'no',
				'options' => array(
					'no' => esc_html__( 'Not active on this filter', 'prdctfltr' ),
					'pf_adptv_default' => esc_html__( 'Terms will be hidden', 'prdctfltr' ),
					'pf_adptv_unclick' => esc_html__( 'Terms will be shown, but unclickable', 'prdctfltr' ),
					'pf_adptv_click' => esc_html__( 'Terms will be shown and clickable', 'prdctfltr' ),
				),
				'condition' => 'a_enable:yes',
			);
		}

		public static function ___get_adoptive_for_range_option() {
			return array(
				'name' => esc_html__( 'Adoptive', 'prdctfltr' ),
				'type' => 'select',
				'desc' => esc_html__( 'Select adoptive filtering', 'prdctfltr' ),
				'id'   => 'adoptive',
				'default' => 'no',
				'options' => array(
					'no' => esc_html__( 'Not active on this filter', 'prdctfltr' ),
					'pf_adptv_default' => esc_html__( 'Terms will be hidden', 'prdctfltr' ),
				),
				'condition' => 'a_enable:yes',
			);
		}

		public static function ___get_term_count_option() {
			return array(
				'name' => esc_html__( 'Term Count', 'prdctfltr' ),
				'type' => 'checkbox',
				'desc' => esc_html__( 'Show term count', 'prdctfltr' ),
				'id'   => 'term_count',
				'default' => 'no',
			);
		}

		public static function ___get_term_search_option() {
			return array(
				'name' => esc_html__( 'Term Search', 'prdctfltr' ),
				'type' => 'checkbox',
				'desc' => esc_html__( 'Show term search input', 'prdctfltr' ),
				'id'   => 'term_search',
				'default' => 'no',
			);
		}

		public static function ___get_term_display_option() {
			return array(
				'name' => esc_html__( 'Term Display', 'prdctfltr' ),
				'type' => 'select',
				'desc' => esc_html__( 'Select terms display style', 'prdctfltr' ),
				'id'   => 'term_display',
				'default' => 'none',
				'options' => array(
					'none' => esc_html__( 'Default', 'prdctfltr' ),
					'inline' => esc_html__( 'Inline', 'prdctfltr' ),
					'2_columns' => esc_html__( 'Split into two columns', 'prdctfltr' ),
					'3_columns' => esc_html__( 'Split into three columns', 'prdctfltr' ),
				),
			);
		}

		public static function ___get_none_text() {
			return array(
				'name' => esc_html__( 'None Text', 'prdctfltr' ),
				'type' => 'text',
				'desc' => esc_html__( 'Change none text', 'prdctfltr' ),
				'id'   => 'none_text',
				'default' => '',
			);
		}

		public static function ___get_hide_elements_option() {
			return array(
				'name' => esc_html__( 'Hide Elements', 'prdctfltr' ),
				'type' => 'multiselect',
				'desc' => esc_html__( 'Select elements to hide', 'prdctfltr' ),
				'id'   => 'hide_elements',
				'default' => '',
				'options' => array(
					'title' => esc_html__( 'Title', 'prdctflr' ),
					'none' => esc_html__( 'None', 'prdctfltr' ),
				),
				'class' => 'svx-selectize',
			);
		}

		public static function ___get_hide_elements_for_range_option() {
			return array(
				'name' => esc_html__( 'Hide Elements', 'prdctfltr' ),
				'type' => 'multiselect',
				'desc' => esc_html__( 'Select elements to hide', 'prdctfltr' ),
				'id'   => 'hide_elements',
				'default' => '',
				'options' => array(
					'title' => esc_html__( 'Title', 'prdctflr' ),
				),
				'class' => 'svx-selectize',
			);
		}

		public static function ___get_range_style_option() {
			return array(
				'name' => esc_html__( 'Style', 'prdctfltr' ),
				'type' => 'select',
				'desc' => esc_html__( 'Style', 'prdctfltr' ),
				'id'   => 'design',
				'default' => 'thin',
				'options' => array(
					'flat' => esc_html__( 'Flat', 'prdctfltr' ),
					'modern' => esc_html__( 'Modern', 'prdctfltr' ),
					'html5' => esc_html__( 'HTML5', 'prdctfltr' ),
					'white' => esc_html__( 'White', 'prdctfltr' ),
					'thin' => esc_html__( 'Thin', 'prdctfltr' ),
					'knob' => esc_html__( 'Knob', 'prdctfltr' ),
					'metal' => esc_html__( 'Metal', 'prdctfltr' )
				),
			);
		}

		public static function ___get_range_grid_option() {
			return array(
				'name' => esc_html__( 'Grid', 'prdctfltr' ),
				'type' => 'checkbox',
				'desc' => esc_html__( 'Show grid', 'prdctfltr' ),
				'id'   => 'grid',
				'default' => '',
			);
		}

		public static function ___get_range_start_option() {
			return array(
				'name' => esc_html__( 'Start', 'prdctfltr' ),
				'type' => 'text',
				'desc' => esc_html__( 'Range start', 'prdctfltr' ),
				'id'   => 'start',
				'default' => '',
			);
		}

		public static function ___get_range_end_option() {
			return array(
				'name' => esc_html__( 'End', 'prdctfltr' ),
				'type' => 'text',
				'desc' => esc_html__( 'Range end', 'prdctfltr' ),
				'id'   => 'end',
				'default' => '',
			);
		}

		public static function ___get_range_prefix_option() {
			return array(
				'name' => esc_html__( 'Prefix', 'prdctfltr' ),
				'type' => 'text',
				'desc' => esc_html__( 'Terms prefix', 'prdctfltr' ),
				'id'   => 'prefix',
				'default' => '',
			);
		}

		public static function ___get_range_postfix_option() {
			return array(
				'name' => esc_html__( 'Postfix', 'prdctfltr' ),
				'type' => 'text',
				'desc' => esc_html__( 'Terms postfix', 'prdctfltr' ),
				'id'   => 'postfix',
				'default' => '',
			);
		}

		public static function ___get_range_step_option() {
			return array(
				'name' => esc_html__( 'Step', 'prdctfltr' ),
				'type' => 'number',
				'desc' => esc_html__( 'Step value', 'prdctfltr' ),
				'id'   => 'step',
				'default' => '',
			);
		}

		public static function ___get_range_grid_num_option() {
			return array(
				'name' => esc_html__( 'Grid density', 'prdctfltr' ),
				'type' => 'number',
				'desc' => esc_html__( 'Grid density value', 'prdctfltr' ),
				'id'   => 'grid_num',
				'default' => '',
			);
		}

		public static function ___get_meta_key_option() {
			return array(
				'name' => esc_html__( 'Meta key', 'prdctfltr' ),
				'type' => 'text',
				'desc' => esc_html__( 'Enter meta key', 'prdctfltr' ),
				'id'   => 'meta_key',
				'default' => '',
			);
		}

		public static function ___get_meta_compare_option() {
			return array(
				'name' => esc_html__( 'Meta compare', 'prdctfltr' ),
				'type' => 'select',
				'desc' => esc_html__( 'Select meta compare', 'prdctfltr' ),
				'id'   => 'meta_compare',
				'default' => '=',
				'options' => array(
					'=' => '=',
					'!=' => '!=',
					'>' => '>',
					'<' => '<',
					'>=' => '>=',
					'<=' => '<=',
					'LIKE' => 'LIKE',
					'NOT LIKE' => 'NOT LIKE',
					'IN' => 'IN',
					'NOT IN' => 'NOT IN',
					'EXISTS' => 'EXISTS',
					'NOT EXISTS' => 'NOT EXISTS',
					'BETWEEN' => 'BETWEEN',
					'NOT BETWEEN' => 'NOT BETWEEN',
				),
			);
		}

		public static function ___get_meta_numeric() {
			return array(
				'name' => esc_html__( 'Numeric', 'prdctfltr' ),
				'type' => 'checkbox',
				'desc' => esc_html__( 'Meta values are numeric', 'prdctfltr' ),
				'id'   => 'meta_numeric',
				'default' => '',
			);
		}

		public static function ___get_meta_type_option() {
			return array(
				'name' => esc_html__( 'Meta type', 'prdctfltr' ),
				'type' => 'select',
				'desc' => esc_html__( 'Select meta type', 'prdctfltr' ),
				'id'   => 'meta_type',
				'default' => 'CHAR',
				'options' => array(
					'NUMERIC' => 'NUMERIC',
					'BINARY' => 'BINARY',
					'CHAR' => 'CHAR',
					'DATE' => 'DATE',
					'DATETIME' => 'DATETIME',
					'DECIMAL' => 'DECIMAL',
					'SIGNED' => 'SIGNED',
					'TIME' => 'TIME',
					'UNSIGNED' => 'UNSIGNED',
				),
			);
		}

		public static function ___get_placeholder_option() {
			return array(
				'name' => esc_html__( 'Placeholder', 'prdctfltr' ),
				'type' => 'text',
				'desc' => esc_html__( 'Placeholder text', 'prdctfltr' ),
				'id'   => 'placeholder',
				'default' => '',
			);
		}

		public static function ___get_label_option() {
			return array(
				'name' => esc_html__( 'Label', 'prdctfltr' ),
				'type' => 'text',
				'desc' => esc_html__( 'Label text', 'prdctfltr' ),
				'id'   => 'label',
				'default' => '',
			);
		}

		public static function __build_filters() {

			$array = array();

			$array['taxonomy'] = array(
				'taxonomy' => self::___get_taxonomy_option(),
				'name' => self::___get_title_option(),
				'desc' => self::___get_desc_option(),
				'include' => self::___get_include_option(),
				'orderby' => self::___get_orderby_option(),
				'order' => self::___get_order_option(),
				'limit' => self::___get_limit_option(),
				'hierarchy' => self::___get_hierarchy_option(),
				'hierarchy_mode' => self::___get_hierarchy_mode_option(),
				'hierarchy_expand' => self::___get_hierarchy_expand_option(),
				'multiselect' => self::___get_multiselect_option(),
				'multiselect_relation' => self::___get_multiselect_relation_option(),
				'selection_reset' => self::___get_selection_reset_option(),
				'adoptive' => self::___get_adoptive_option(),
				'term_count' => self::___get_term_count_option(),
				'term_search' => self::___get_term_search_option(),
				'term_display' => self::___get_term_display_option(),
				'none_text' => self::___get_none_text(),
				'hide_elements' => self::___get_hide_elements_option(),
			);


			$array['range'] = array(
				'taxonomy' => self::___get_taxonomy_option(),
				'name' => self::___get_title_option(),
				'desc' => self::___get_desc_option(),
				'include' => self::___get_include_option(),
				'orderby' => self::___get_orderby_option(),
				'order' => self::___get_order_option(),
				'design' => self::___get_range_style_option(),
				'start' => self::___get_range_start_option(),
				'end' => self::___get_range_end_option(),
				'prefix' => self::___get_range_prefix_option(),
				'postfix' => self::___get_range_postfix_option(),
				'step' => self::___get_range_step_option(),
				'grid' => self::___get_range_grid_option(),
				'grid_num' => self::___get_range_grid_num_option(),
				'adoptive' => self::___get_adoptive_for_range_option(),
				'hide_elements' => self::___get_hide_elements_for_range_option(),
			);

			$array['meta'] = array(
				'name' => self::___get_title_option(),
				'desc' => self::___get_desc_option(),
				'meta_key' => self::___get_meta_key_option(),
				'meta_compare' => self::___get_meta_compare_option(),
				'meta_type' => self::___get_meta_type_option(),
				'limit' => self::___get_limit_option(),
				'multiselect' => self::___get_multiselect_option(),
				'multiselect_relation' => self::___get_multiselect_relation_option(),
				'selection_reset' => self::___get_selection_reset_option(),
				'term_search' => self::___get_term_search_option(),
				'term_display' => self::___get_term_display_option(),
				'none_text' => self::___get_none_text(),
				'hide_elements' => self::___get_hide_elements_option(),
			);


			$array['meta_range'] = array(
				'name' => self::___get_title_option(),
				'desc' => self::___get_desc_option(),
				'meta_key' => self::___get_meta_key_option(),
				'meta_numeric' => self::___get_meta_numeric(),
				'design' => self::___get_range_style_option(),
				'start' => self::___get_range_start_option(),
				'end' => self::___get_range_end_option(),
				'prefix' => self::___get_range_prefix_option(),
				'postfix' => self::___get_range_postfix_option(),
				'step' => self::___get_range_step_option(),
				'grid' => self::___get_range_grid_option(),
				'grid_num' => self::___get_range_grid_num_option(),
				'hide_elements' => self::___get_hide_elements_for_range_option(),
			);

			$array['vendor'] = array(
				'name' => self::___get_title_option(),
				'desc' => self::___get_desc_option(),
				'include' => self::___get_include_option(),
				'limit' => self::___get_limit_option(),
				'multiselect' => self::___get_multiselect_option(),
				'selection_reset' => self::___get_selection_reset_option(),
				'term_search' => self::___get_term_search_option(),
				'term_display' => self::___get_term_display_option(),
				'none_text' => self::___get_none_text(),
				'hide_elements' => self::___get_hide_elements_option(),
			);

			$array['rating_filter'] = array(
				'name' => self::___get_title_option(),
				'desc' => self::___get_desc_option(),
				'include' => self::___get_include_option(),
				'limit' => self::___get_limit_option(),
				'multiselect' => self::___get_multiselect_option(),
				'selection_reset' => self::___get_selection_reset_option(),
				'term_search' => self::___get_term_search_option(),
				'term_display' => self::___get_term_display_option(),
				'none_text' => self::___get_none_text(),
				'hide_elements' => self::___get_hide_elements_option(),
			);

			$array['orderby'] = array(
				'name' => self::___get_title_option(),
				'desc' => self::___get_desc_option(),
				'include' => self::___get_include_option(),
				'selection_reset' => self::___get_selection_reset_option(),
				'term_display' => self::___get_term_display_option(),
				'none_text' => self::___get_none_text(),
				'hide_elements' => self::___get_hide_elements_option(),
			);

			$array['search'] = array(
				'name' => self::___get_title_option(),
				'desc' => self::___get_desc_option(),
				'placeholder' => self::___get_placeholder_option(),
				'none_text' => self::___get_none_text(),
				'hide_elements' => self::___get_hide_elements_option(),
			);

			$array['instock'] = array(
				'name' => self::___get_title_option(),
				'desc' => self::___get_desc_option(),
				'include' => self::___get_include_option(),
				'selection_reset' => self::___get_selection_reset_option(),
				'term_display' => self::___get_term_display_option(),
				'none_text' => self::___get_none_text(),
				'hide_elements' => self::___get_hide_elements_option(),
			);

			$array['price'] = array(
				'name' => self::___get_title_option(),
				'desc' => self::___get_desc_option(),
				'selection_reset' => self::___get_selection_reset_option(),
				'term_display' => self::___get_term_display_option(),
				'none_text' => self::___get_none_text(),
				'hide_elements' => self::___get_hide_elements_option(),
			);

			$array['price_range'] = array(
				'name' => self::___get_title_option(),
				'desc' => self::___get_desc_option(),
				'design' => self::___get_range_style_option(),
				'grid' => self::___get_range_grid_option(),
				'start' => self::___get_range_start_option(),
				'end' => self::___get_range_end_option(),
				'prefix' => self::___get_range_prefix_option(),
				'postfix' => self::___get_range_postfix_option(),
				'step' => self::___get_range_step_option(),
				'grid_num' => self::___get_range_grid_num_option(),
				'hide_elements' => self::___get_hide_elements_for_range_option(),
			);

			$array['per_page'] = array(
				'name' => self::___get_title_option(),
				'desc' => self::___get_desc_option(),
				'selection_reset' => self::___get_selection_reset_option(),
				'term_display' => self::___get_term_display_option(),
				'none_text' => self::___get_none_text(),
				'hide_elements' => self::___get_hide_elements_option(),
			);

			$array = apply_filters( 'prdctflr_supported_filters', $array );

			return $array;

		}

		public static function get_key() {
			return get_option( 'xforwc_key_product_filter' ) === false ? 'false' : 'true';
		}

		public static function get_settings( $plugins ) {
 
			self::$settings['options'] = Prdctfltr()->get_default_options();

			self::$settings['preset'] = Prdctfltr()->___get_preset( 'default' );

			$saved = isset( self::$settings['options']['presets'] ) && is_array ( self::$settings['options']['presets'] ) ? self::$settings['options']['presets'] : array();
			foreach( $saved as $preset ) {
				self::$presets[$preset['slug']] = $preset['name'];
			}

			if ( empty( self::$presets ) ) {
				self::$presets = false;
			}

			$attributes = get_object_taxonomies( 'product' );
			foreach( $attributes as $k ) {
				if ( !in_array( $k, array() ) ) {
					if ( substr( $k, 0, 3 ) == 'pa_' ) {
						$ready_attributes[$k] = wc_attribute_label( $k );
					}
					else {
						$taxonomy = get_taxonomy( $k );
						$ready_attributes[$k] = $taxonomy->label;
					}
				}
			}

			if ( empty( $ready_attributes ) ) {
				$ready_attributes = false;
			}

			include_once( 'class-themes.php' );
			$ajax = XforWC_Product_Filters_Themes::get_theme();

			$plugins[self::$plugin['label']] = array(
				'slug' => self::$plugin['label'],
				'name' => esc_html( function_exists( 'XforWC' ) ? self::$plugin['xforwc'] : self::$plugin['name'] ),
				'desc' => esc_html( function_exists( 'XforWC' ) ? self::$plugin['name'] . ' v' . self::$plugin['version'] : esc_html__( 'Settings page for', 'prdctfltr' ) . ' ' . self::$plugin['name'] ),
				'link' => esc_url( 'https://xforwoocommerce.com/store/product-filters/' ),
				'imgs' => esc_url( Prdctfltr()->plugin_url() ),
				'ref' => array(
					'name' => esc_html__( 'Visit XforWooCommerce.com', 'prdctfltr' ),
					'url' => 'https://xforwoocommerce.com'
				),
				'doc' => array(
					'name' => esc_html__( 'Get help', 'prdctfltr' ),
					'url' => 'https://help.xforwoocommerce.com'
				),
				'sections' => array(
					'dashboard' => array(
						'name' => esc_html__( 'Dashboard', 'prdctfltr' ),
						'desc' => esc_html__( 'Dashboard Overview', 'prdctfltr' ),
					),
					'presets' => array(
						'name' => esc_html__( 'Filter Presets', 'prdctfltr' ),
						'desc' => esc_html__( 'Filter Presets Options', 'prdctfltr' ),
					),
					'manager' => array(
						'name' => esc_html__( 'Presets Manager', 'prdctfltr' ),
						'desc' => esc_html__( 'Presets Manager Options', 'prdctfltr' ),
					),
					'integration' => array(
						'name' => esc_html__( 'Shop Integration', 'prdctfltr' ),
						'desc' => esc_html__( 'Shop Integration Options', 'prdctfltr' ),
					),
					'ajax' => array(
						'name' => esc_html__( 'AJAX', 'prdctfltr' ),
						'desc' => esc_html__( 'AJAX Options', 'prdctfltr' ),
					),
					'general' => array(
						'name' => esc_html__( 'Advanced', 'prdctfltr' ),
						'desc' => esc_html__( 'Advanced Options', 'prdctfltr' ),
					),
					'analytics' => array(
						'name' => esc_html__( 'Analytics', 'prdctfltr' ),
						'desc' => esc_html__( 'Filtering Analytics', 'prdctfltr' ),
					),
				),
				'extras' => array(
					'product_attributes' => $ready_attributes,
					'more_titles' => array(
						'orderby' => esc_html__( 'Order by', 'prdctfltr' ),
						'per_page' => esc_html__( 'Per page', 'prdctfltr' ),
						'vendor' => esc_html__( 'Vendor', 'prdctfltr' ),
						'search' => esc_html__( 'Search', 'prdctfltr' ),
						'instock' => esc_html__( 'Availability', 'prdctfltr' ),
						'price' => esc_html__( 'Price', 'prdctfltr' ),
						'price_range' => esc_html__( 'Price range', 'prdctfltr' ),
						'meta' => esc_html__( 'Meta filter', 'prdctfltr' ),
						'rating_filter' => esc_html__( 'Rating', 'prdctfltr' ),
					),
					'options' => self::$settings['options'],
					'presets' => array(
						'loaded' => 'default',
						'loaded_settings' => self::$settings['preset'],
						'set' => self::$presets,
					),
					'terms' => array(
						'orderby' => array(
							array(
								'id' => 'menu_order',
								'slug' => 'menu_order',
								'default_name' => 'Default',
							),
							array(
								'id' => 'comment_count',
								'slug' => 'comment_count',
								'default_name' => 'Review Count',
							),
							array(
								'id' => 'popularity',
								'slug' => 'popularity',
								'default_name' => 'Popularity',
							),
							array(
								'id' => 'rating',
								'slug' => 'rating',
								'default_name' => 'Average rating',
							),
							array(
								'id' => 'date',
								'slug' => 'date',
								'default_name' => 'Newness',
							),
							array(
								'id' => 'price',
								'slug' => 'price',
								'default_name' => 'Price: low to high',
							),
							array(
								'id' => 'price-desc',
								'slug' => 'price-desc',
								'default_name' => 'Price: high to low',
							),
							array(
								'id' => 'rand',
								'slug' => 'rand',
								'default_name' => 'Random Products',
							),
							array(
								'id' => 'title',
								'slug' => 'title',
								'default_name' => 'Product Name',
							),
						),
						'instock' => array(
							array(
								'id' => 'out',
								'slug' => 'out',
								'default_name' => 'Out of stock',
							),
							array(
								'id' => 'in',
								'slug' => 'in',
								'default_name' => 'In stock',
							),
							array(
								'id' => 'both',
								'slug' => 'both',
								'default_name' => 'All products',
							),
						),
						'rating' => array(
							array(
								'id' => '1',
								'slug' => '1',
								'default_name' => '1 Star',
							),	
							array(
								'id' => '2',
								'slug' => '2',
								'default_name' => '2 Stars',
							),	
							array(
								'id' => '3',
								'slug' => '3',
								'default_name' => '3 Stars',
							),	
							array(
								'id' => '4',
								'slug' => '4',
								'default_name' => '4 Stars',
							),	
							array(
								'id' => '5',
								'slug' => '5',
								'default_name' => '5 Stars',
							),	
						),
					),
				),
				'settings' => array(),
			);

			$plugins['product_filter']['settings']['wcmn_dashboard'] = array(
				'type' => 'html',
				'id' => 'wcmn_dashboard',
				'desc' => '
				<img src="' . Prdctfltr()->plugin_url() . '/includes/images/product-filter-for-woocommerce-shop.png" class="svx-dashboard-image" />
				<h3><span class="dashicons dashicons-store"></span> XforWooCommerce</h3>
				<p>' . esc_html__( 'Visit XforWooCommerce.com store, demos and knowledge base.', 'prdctfltr' ) . '</p>
				<p><a href="https://xforwoocommerce.com" class="xforwc-button-primary x-color" target="_blank">XforWooCommerce.com</a></p>

				<br /><hr />

				<h3><span class="dashicons dashicons-admin-tools"></span> ' . esc_html__( 'Help Center', 'prdctfltr' ) . '</h3>
				<p>' . esc_html__( 'Need support? Visit the Help Center.', 'prdctfltr' ) . '</p>
				<p><a href="https://help.xforwoocommerce.com" class="xforwc-button-primary red" target="_blank">XforWooCommerce.com HELP</a></p>
				
				<br /><hr />

				<h3><span class="dashicons dashicons-update"></span> ' . esc_html__( 'Automatic Updates', 'prdctfltr' ) . '</h3>
				<p>' . esc_html__( 'Get automatic updates, by downloading and installing the Envato Market plugin.', 'prdctfltr' ) . '</p>
				<p><a href="https://envato.com/market-plugin/" class="svx-button" target="_blank">Envato Market Plugin</a></p>
				
				<br />',
				'section' => 'dashboard',
			);

			$plugins['product_filter']['settings']['wcmn_utility'] = array(
				'name' => esc_html__( 'Plugin Options', 'prdctfltr' ),
				'type' => 'utility',
				'id' => 'wcmn_utility',
				'desc' => esc_html__( 'Quick export/import, backup and restore, or just reset your optons here', 'prdctfltr' ),
				'section' => 'dashboard',
			);

			$plugins['product_filter']['settings'] = array_merge( $plugins['product_filter']['settings'], array(

				'_filter_preset_manager' => array(
					'name' => esc_html__( 'Filter Preset', 'prdctfltr' ),
					'type' => 'select',
					'id' => '_filter_preset_manager',
					'desc' => esc_html__( 'Editing selected filter preset', 'prdctfltr' ),
					'section' => 'presets',
					'options' => 'function:__make_presets',
					'default' => 'default',
					'class' => '',
					'val' => 'default',
				),

				'_filter_preset_options' => array(
					'name' => esc_html__( 'Filter Options', 'prdctfltr' ),
					'type' => 'select',
					'id' => '_filter_preset_options',
					'desc' => esc_html__( 'Select options group for the current preset', 'prdctfltr' ),
					'section' => 'presets',
					'options' => array(
						'filters' => esc_html__( 'Filters' , 'prdctfltr' ),
						'general' => esc_html__( 'General' , 'prdctfltr' ),
						'style' => esc_html__( 'Style' , 'prdctfltr' ),
						'adoptive' => esc_html__( 'Adoptive' , 'prdctfltr' ),
						'responsive' => esc_html__( 'Responsive' , 'prdctfltr' ),
					),
					'default' => 'filters',
					'class' => 'svx-make-group svx-refresh-active-tab',
					'val' => 'filters',
				),

				'g_instant' => array(
					'name' => esc_html__( 'Filter on Click', 'prdctfltr' ),
					'type' => 'checkbox',
					'desc' => esc_html__( 'Check this option to filter on click', 'prdctfltr' ),
					'section' => 'presets',
					'id'   => 'g_instant',
					'default' => 'no',
					'condition' => '_filter_preset_options:general',
				),

				'g_step_selection' => array(
					'name' => esc_html__( 'Stepped Selection', 'prdctfltr' ),
					'type' => 'checkbox',
					'desc' => esc_html__( 'Check this option to use stepped selection', 'prdctfltr' ),
					'section' => 'presets',
					'id'   => 'g_step_selection',
					'default' => 'no',
					'condition' => '_filter_preset_options:general',
				),

				'g_collectors' => array(
					'name' => esc_html__( 'Show Selected Terms In', 'prdctfltr' ),
					'type' => 'multiselect',
					'desc' => esc_html__( 'Select areas where to show the selected terms', 'prdctfltr' ),
					'section' => 'presets',
					'id'   => 'g_collectors',
					'options'   => array(
						'topbar' => esc_html__( 'Top bar', 'prdctfltr' ),
						'collector' => esc_html__( 'Collector', 'prdctfltr' ),
						'intitle' => esc_html__( 'Filter title', 'prdctfltr' ),
						'aftertitle' => esc_html__( 'After filter title', 'prdctfltr' ),
					),
					'default' => array( 'collector' ),
					'condition' => '_filter_preset_options:general',
					'class' => 'svx-selectize',
				),

				'g_collector_style' => array(
					'name' => esc_html__( 'Selected Terms Style', 'prdctfltr' ),
					'type' => 'select',
					'desc' => esc_html__( 'Select selected terms style', 'prdctfltr' ),
					'section' => 'presets',
					'id'   => 'g_collector_style',
					'options'   => array(
						'flat' => esc_html__( 'Flat', 'prdctfltr' ),
						'border' => esc_html__( 'Border', 'prdctfltr' ),
					),
					'default' => 'flat',
					'condition' => '_filter_preset_options:general',
				),

				'g_reorder_selected' => array(
					'name' => esc_html__( 'Reorder Selected', 'prdctfltr' ),
					'type' => 'checkbox',
					'desc' => esc_html__( 'Check this option to bring selected terms to the top', 'prdctfltr' ),
					'section' => 'presets',
					'id'   => 'g_reorder_selected',
					'default' => 'no',
					'condition' => '_filter_preset_options:general',
				),

				'g_form_action' => array(
					'name' => esc_html__( 'Filter Form Action', 'prdctfltr' ),
					'type' => 'text',
					'desc' => esc_html__( 'Enter custom filter form action="" parameter', 'prdctfltr' ),
					'section' => 'presets',
					'id'   => 'g_form_action',
					'default' => '',
					'condition' => '_filter_preset_options:general',
				),

				's_style' => array(
					'name' => esc_html__( 'Select Design', 'prdctfltr' ),
					'type' => 'select',
					'desc' => esc_html__( 'Select filter design style', 'prdctfltr' ),
					'section' => 'presets',
					'id'   => 's_style',
					'options'   => array(
						'pf_default' => esc_html__( 'Default', 'prdctfltr' ),
						'pf_arrow' => esc_html__( 'Pop up', 'prdctfltr' ),
						'pf_sidebar' => esc_html__( 'Left fixed sidebar', 'prdctfltr' ),
						'pf_sidebar_right' => esc_html__( 'Right fixed sidebar', 'prdctfltr' ),
						'pf_sidebar_css' => esc_html__( 'Left fixed sidebar with overlay', 'prdctfltr' ),
						'pf_sidebar_css_right' => esc_html__( 'Right fixed sidebar with overlay', 'prdctfltr' ),
						'pf_fullscreen' => esc_html__( 'Full screen filters', 'prdctfltr' ),
						'pf_select' => esc_html__( 'Filters inside select boxes', 'prdctfltr' ),
					),
					'default' => '',
					'condition' => '_filter_preset_options:style',
				),

				's_always_visible' => array(
					'name' => esc_html__( 'Always Visible', 'prdctfltr' ),
					'type' => 'checkbox',
					'desc' => esc_html__( 'Disable slide in/out animation', 'prdctfltr' ),
					'section' => 'presets',
					'id'   => 's_always_visible',
					'default' => 'no',
					'condition' => '_filter_preset_options:style',
				),

				's_hide_elements' => array(
					'name' => esc_html__( 'Hide Elements', 'prdctfltr' ),
					'type' => 'multiselect',
					'desc' => esc_html__( 'Select elements to hide', 'prdctfltr' ),
					'section' => 'presets',
					'id'   => 's_hide_elements',
					'options' => array(
						'hide_icon' => esc_html__( 'Filter icon', 'prdctfltr' ),
						'hide_top_bar' => esc_html__( 'The whole top bar', 'prdctfltr' ),
						'hide_showing' => esc_html__( 'Showing text in top bar', 'prdctfltr' ),
						'hide_sale_button' => esc_html__( 'Sale button', 'prdctfltr' ),
						'hide_instock_button' => esc_html__( 'Instock button', 'prdctfltr' ),
						'hide_reset_button' => esc_html__( 'Reset button', 'prdctfltr' ),
					),
					'default' => '',
					'condition' => '_filter_preset_options:style',
					'class' => 'svx-selectize',
				),

				's_mode' => array(
					'name' => esc_html__( 'Row Display', 'prdctfltr' ),
					'type' => 'select',
					'desc' => esc_html__( 'Select row display mode', 'prdctfltr' ),
					'section' => 'presets',
					'id'   => 's_mode',
					'options'   => array(
						'pf_mod_row' => esc_html__( 'One row', 'prdctfltr' ),
						'pf_mod_multirow' => esc_html__( 'Multiple rows', 'prdctfltr' ),
						'pf_mod_masonry' => esc_html__( 'Masonry Filters', 'prdctfltr' ),
					),
					'default' => 'pf_mod_multirow',
					'condition' => '_filter_preset_options:style',
				),

				's_columns' => array(
					'name' => esc_html__( 'Max Columns', 'prdctfltr' ),
					'type' => 'number',
					'desc' => esc_html__( 'Set max filter columns', 'prdctfltr' ),
					'section' => 'presets',
					'id'   => 's_columns',
					'default' => '',
					'condition' => '_filter_preset_options:style',
				),

				's_max_height' => array(
					'name' => esc_html__( 'Max Height', 'prdctfltr' ),
					'type' => 'number',
					'desc' => esc_html__( 'Set max filter height', 'prdctfltr' ),
					'section' => 'presets',
					'id'   => 's_max_height',
					'default' => '',
					'condition' => '_filter_preset_options:style',
				),

				's_js_scroll' => array(
					'name' => esc_html__( 'Scroll Bar Style', 'prdctfltr' ),
					'type' => 'checkbox',
					'desc' => esc_html__( 'Enable enhanced scroll bars display', 'prdctfltr' ),
					'section' => 'presets',
					'id'   => 's_js_scroll',
					'default' => 'no',
					'condition' => '_filter_preset_options:style',
				),

				's_checkbox_style' => array(
					'name' => esc_html__( 'Checkbox Style', 'prdctfltr' ),
					'type' => 'select',
					'desc' => esc_html__( 'Select term checkbox style', 'prdctfltr' ),
					'section' => 'presets',
					'id'   => 's_checkbox_style',
					'options'   => array(
						'prdctfltr_bold' => esc_html__( 'Hide', 'prdctfltr' ),
						'prdctfltr_round' => esc_html__( 'Round', 'prdctfltr' ),
						'prdctfltr_square' => esc_html__( 'Square', 'prdctfltr' ),
						'prdctfltr_checkbox' => esc_html__( 'Checkbox', 'prdctfltr' ),
						'prdctfltr_system' => esc_html__( 'System Checkboxes', 'prdctfltr' ),
					),
					'default' => 'prdctfltr_round',
					'condition' => '_filter_preset_options:style',
				),

				's_hierarchy_style' => array(
					'name' => esc_html__( 'Hierarchy Style', 'prdctfltr' ),
					'type' => 'select',
					'desc' => esc_html__( 'Select hierarchy style', 'prdctfltr' ),
					'section' => 'presets',
					'id'   => 's_hierarchy_style',
					'options'   => array(
						'prdctfltr_hierarchy_hide' => esc_html__( 'Hide', 'prdctfltr' ),
						'prdctfltr_hierarchy_circle' => esc_html__( 'Circle', 'prdctfltr' ),
						'prdctfltr_hierarchy_filled' => esc_html__( 'Circle Solid', 'prdctfltr' ),
						'prdctfltr_hierarchy_lined' => esc_html__( 'Lined', 'prdctfltr' ),
						'prdctfltr_hierarchy_arrow' => esc_html__( 'Arrows', 'prdctfltr' ),
					),
					'default' => 'prdctfltr_hierarchy_lined',
					'condition' => '_filter_preset_options:style',
				),

				's_filter_icon' => array(
					'name' => esc_html__( 'Filter Icon', 'prdctfltr' ),
					'type' => 'text',
					'desc' => esc_html__( 'Enter icon class. Use icon class e.g. prdctfltr-filter or FontAwesome fa fa-shopping-cart or any other', 'prdctfltr' ),
					'section' => 'presets',
					'id'   => 's_filter_icon',
					'default' => '',
					'condition' => '_filter_preset_options:style',
				),

				's_filter_title' => array(
					'name' => esc_html__( 'Filter Title Text', 'prdctfltr' ),
					'type' => 'text',
					'desc' => esc_html__( 'Enter title text', 'prdctfltr' ),
					'section' => 'presets',
					'id'   => 's_filter_title',
					'default' => '',
					'condition' => '_filter_preset_options:style',
				),

				's_filter_button' => array(
					'name' => esc_html__( 'Filter Button Text', 'prdctfltr' ),
					'type' => 'text',
					'desc' => esc_html__( 'Enter filter button text', 'prdctfltr' ),
					'section' => 'presets',
					'id'   => 's_filter_button',
					'default' => '',
					'condition' => '_filter_preset_options:style',
				),

				's_button_position' => array(
					'name' => esc_html__( 'Button Position', 'prdctfltr' ),
					'type' => 'select',
					'desc' => esc_html__( 'Select button position', 'prdctfltr' ),
					'section' => 'presets',
					'id'   => 's_button_position',
					'options'   => array(
						'bottom' => esc_html__( 'Bottom', 'prdctfltr' ),
						'top' => esc_html__( 'Top', 'prdctfltr' ),
						'both' => esc_html__( 'Both', 'prdctfltr' ),
					),
					'default' => '',
					'condition' => '_filter_preset_options:style',
				),

				's_content_align' => array(
					'name' => esc_html__( 'Content Align', 'prdctfltr' ),
					'type' => 'select',
					'desc' => esc_html__( 'Set content align', 'prdctfltr' ),
					'section' => 'presets',
					'id'   => 's_content_align',
					'options'   => array(
						'left' => esc_html__( 'Left', 'prdctfltr' ),
						'center' => esc_html__( 'Center', 'prdctfltr' ),
						'right' => esc_html__( 'Right', 'prdctfltr' ),
					),
					'default' => '',
					'condition' => '_filter_preset_options:style',
				),

				's_loading_animation' => array(
					'name' => esc_html__( 'Loader Animation', 'prdctfltr' ),
					'type' => 'select',
					'desc' => esc_html__( 'Select loader animation', 'prdctfltr' ),
					'section' => 'presets',
					'id'   => 's_loading_animation',
					'options'   => array(
						'css-spinner-full' => sprintf( esc_html__( 'Overlay #%s', 'prdctfltr' ), '1' ),
						'css-spinner-full-01' => sprintf( esc_html__( 'Overlay #%s', 'prdctfltr' ), '2' ),
						'css-spinner-full-02' => sprintf( esc_html__( 'Overlay #%s', 'prdctfltr' ), '3' ),
						'css-spinner-full-03' => sprintf( esc_html__( 'Overlay #%s', 'prdctfltr' ), '4' ),
						'css-spinner-full-04' => sprintf( esc_html__( 'Overlay #%s', 'prdctfltr' ), '5' ),
						'css-spinner-full-05' => sprintf( esc_html__( 'Overlay #%s', 'prdctfltr' ), '6' ),
						'css-spinner' => sprintf( esc_html__( 'In title #%s', 'prdctfltr' ), '1' ),
						'css-spinner-01' => sprintf( esc_html__( 'In title #%s', 'prdctfltr' ), '2' ),
						'css-spinner-02' => sprintf( esc_html__( 'In title #%s', 'prdctfltr' ), '3' ),
						'css-spinner-03' => sprintf( esc_html__( 'In title #%s', 'prdctfltr' ), '4' ),
						'css-spinner-04' => sprintf( esc_html__( 'In title #%s', 'prdctfltr' ), '5' ),
						'css-spinner-05' => sprintf( esc_html__( 'In title #%s', 'prdctfltr' ), '6' ),
						'none' => esc_html__( 'None', 'prdctfltr' ),
					),
					'default' => 'css-spinner-full',
					'condition' => '_filter_preset_options:style',
				),

				's__tx_sale' => array(
					'name' => esc_html__( 'Sale Text', 'prdctfltr' ),
					'type' => 'text',
					'desc' => esc_html__( 'Enter sale button text', 'prdctfltr' ),
					'section' => 'presets',
					'id'   => 's__tx_sale',
					'default' => '',
					'condition' => '_filter_preset_options:style',
				),

				's__tx_instock' => array(
					'name' => esc_html__( 'Instock Text', 'prdctfltr' ),
					'type' => 'text',
					'desc' => esc_html__( 'Enter instock button text', 'prdctfltr' ),
					'section' => 'presets',
					'id'   => 's__tx_instock',
					'default' => '',
					'condition' => '_filter_preset_options:style',
				),

				's__tx_clearall' => array(
					'name' => esc_html__( 'Clear All Text', 'prdctfltr' ),
					'type' => 'text',
					'desc' => esc_html__( 'Enter clear all button text', 'prdctfltr' ),
					'section' => 'presets',
					'id'   => 's__tx_clearall',
					'default' => '',
					'condition' => '_filter_preset_options:style',
				),


				'a_enable' => array(
					'name' => esc_html__( 'Enable', 'prdctfltr' ),
					'type' => 'checkbox',
					'desc' => esc_html__( 'Check this option to use adoptive filtering in current preset', 'prdctfltr' ),
					'section' => 'presets',
					'id'   => 'a_enable',
					'default' => 'no',
					'condition' => '_filter_preset_options:adoptive',
				),

				'a_active_on' => array(
					'name' => esc_html__( 'Active On', 'prdctfltr' ),
					'type' => 'select',
					'desc' => esc_html__( 'Select when to activate adoptive filtering', 'prdctfltr' ),
					'section' => 'presets',
					'id'   => 'a_active_on',
					'options'   => array(
						'always' => esc_html__( 'Always active', 'prdctfltr' ),
						'permalink' => esc_html__( 'Active on permalinks and filters', 'prdctfltr' ),
						'filter' => esc_html__( 'Active on filters', 'prdctfltr' ),
					),
					'default' => 'no',
					'condition' => '_filter_preset_options:adoptive',
				),

				'a_depend_on' => array(
					'name' => esc_html__( 'Depend On', 'prdctfltr' ),
					'type' => 'select',
					'desc' => esc_html__( 'Select taxonomy terms can depend on', 'prdctfltr' ),
					'section' => 'presets',
					'id'   => 'a_depend_on',
					'options' => 'ajax:product_taxonomies:has_none',
					'default' => '',
					'condition' => '_filter_preset_options:adoptive',
				),

				'a_term_counts' => array(
					'name' => esc_html__( 'Product Count', 'prdctfltr' ),
					'type' => 'select',
					'desc' => esc_html__( 'Select adoptive product count display', 'prdctfltr' ),
					'section' => 'presets',
					'id'   => 'a_term_counts',
					'options'   => array(
						'default' => esc_html__( 'Filtered count / Total', 'prdctfltr' ),
						'count' => esc_html__( 'Filtered count', 'prdctfltr' ),
						'total' => esc_html__( 'Total', 'prdctfltr' ),
					),
					'default' => 'default',
					'condition' => '_filter_preset_options:adoptive',
				),

				'a_reorder_selected' => array(
					'name' => esc_html__( 'Reorder Terms', 'prdctfltr' ),
					'type' => 'checkbox',
					'desc' => esc_html__( 'Reorder remaining terms to the top', 'prdctfltr' ),
					'section' => 'presets',
					'id'   => 'a_reorder_selected',
					'default' => 'no',
					'condition' => '_filter_preset_options:adoptive',
				),

				'r_behaviour' => array(
					'name' => esc_html__( 'Responsive Behaviour', 'prdctfltr' ),
					'type' => 'select',
					'desc' => esc_html__( 'Set filter preset behaviour on defined resolution', 'prdctfltr' ),
					'section' => 'presets',
					'id'   => 'r_behaviour',
					'options'   => array(
						'none' => esc_html__( 'Do not do a thing', 'prdctfltr' ),
						'switch' => esc_html__( 'Switch with filter preset', 'prdctfltr' ),
						'hide' => esc_html__( 'Show on screen resolution smaller than', 'prdctfltr' ),
						'show' => esc_html__( 'Show on screen resolution larger than', 'prdctfltr' ),
					),
					'default' => 'none',
					'condition' => '_filter_preset_options:responsive',
					'class' => 'svx-refresh-active-tab',
				),

				'r_resolution' => array(
					'name' => esc_html__( 'Responsive Resolution', 'prdctfltr' ),
					'type' => 'number',
					'desc' => esc_html__( 'Set screen resolution in pixels that will trigger the responsive behaviour', 'prdctfltr' ),
					'section' => 'presets',
					'id'   => 'r_resolution',
					'default' => '768',
					'condition' => '_filter_preset_options:responsive',
				),

				'r_preset' => array(
					'name' => esc_html__( 'Preset', 'prdctfltr' ),
					'type' => 'select',
					'desc' => esc_html__( 'Set filter preset', 'prdctfltr' ),
					'section' => 'presets',
					'id'   => 'r_preset',
					'options' => 'function:__make_presets',
					'default' => '',
					'condition' => '_filter_preset_options:responsive&&r_behaviour:switch',
				),

				'filters' => array(
					'name' => esc_html__( 'Filters', 'prdctfltr' ),
					'type' => 'list-select',
					'id'   => 'filters',
					'desc' => esc_html__( 'Add more filters to the current preset', 'prdctfltr' ),
					'section' => 'presets',
					'title' => esc_html__( 'Filter', 'prdctfltr' ),
					'supports' => array( 'customizer' ),
					'options' => 'list',
					'selects' => array(
						'taxonomy' => esc_html__( 'Taxonomy', 'prdctfltr' ),
						'range' => esc_html__( 'Taxonomy Range', 'prdctfltr' ),
						'meta' => esc_html__( 'Meta', 'prdctfltr' ),
						'meta_range' => esc_html__( 'Meta Range', 'prdctfltr' ),
						'vendor' => esc_html__( 'Vendor', 'prdctfltr' ),
						'orderby' => esc_html__( 'Order by', 'prdctfltr' ),
						'search' => esc_html__( 'Search', 'prdctfltr' ),
						'instock' => esc_html__( 'Availability', 'prdctfltr' ),
						'price' => esc_html__( 'Price', 'prdctfltr' ),
						'price_range' => esc_html__( 'Price Range', 'prdctfltr' ),
						'per_page' => esc_html__( 'Per page', 'prdctfltr' ),
						'rating_filter' => esc_html__( 'Rating', 'prdctfltr' ),
					),
					'settings' => self::__build_filters(),
					'condition' => '_filter_preset_options:filters',
					'val' => '',
				)

			) );

			$plugins['product_filter']['settings'] = array_merge( $plugins['product_filter']['settings'], self::__build_overrides() );

			$plugins['product_filter']['settings']['supported_overrides'] = array(
				'name' => esc_html__( 'Select Taxonomies', 'prdctfltr' ),
				'type' => 'multiselect',
				'desc' => esc_html__( 'Select supported taxonomies for Presets Manager (needs a Save and page refresh to take effect!)', 'prdctfltr' ),
				'section' => 'manager',
				'id'   => 'supported_overrides',
				'options' => 'ajax:product_taxonomies',
				'default' => '',
				'class' => '',
			);

			$plugins['product_filter']['settings'] = array_merge( $plugins['product_filter']['settings'], array(

				'variable_images' => array(
					'name' => esc_html__( 'Switch Variable Images', 'prdctfltr' ),
					'type' => 'checkbox',
					'desc' => esc_html__( 'Check this option to switch variable images when filtering attributes', 'prdctfltr' ),
					'section' => 'general',
					'id'   => 'variable_images',
					'default' => 'no',
				),

				'clear_all' => array(
					'name' => esc_html__( 'Clear All', 'prdctfltr' ),
					'type' => 'multiselect',
					'desc' => esc_html__( 'Select taxonomies which Clear All button cannot clear', 'prdctfltr' ),
					'section' => 'general',
					'id'   => 'clear_all',
					'options' => 'ajax:product_taxonomies',
					'default' => '',
				),

				'register_taxonomy' => array(
					'name' => esc_html__( 'Register Taxonomy', 'prdctfltr' ),
					'type' => 'list',
					'id'   => 'register_taxonomy',
					'desc' => esc_html__( 'Register custom product taxonomies (needs a Save and page refresh to take effect!)', 'prdctfltr' ),
					'section' => 'general',
					'title' => esc_html__( 'Name', 'prdctfltr' ),
					'options' => 'list',
					'default' => '',
					'settings' => array(
						'name' => array(
							'name' => esc_html__( 'Plural name', 'prdctfltr' ),
							'type' => 'text',
							'id' => 'name',
							'desc' => esc_html__( 'Enter plural taxonomy name', 'prdctfltr' ),
							'default' => '',
						),
						'single_name' => array(
							'name' => esc_html__( 'Singular name', 'prdctfltr' ),
							'type' => 'text',
							'id' => 'single_name',
							'desc' => esc_html__( 'Enter singular taxonomy name', 'prdctfltr' ),
							'default' => '',
						),
						'hierarchy' => array(
							'name' => esc_html__( 'Use hierarchy', 'prdctfltr' ),
							'type' => 'checkbox',
							'id'   => 'hierarchy',
							'desc' => esc_html__( 'Enable hierarchy for this taxonomy', 'prdctfltr' ),
							'default' => 'no',
						),
					),
				),

				'hide_empty' => array(
					'name' => esc_html__( 'Hide Empty Terms', 'prdctfltr' ),
					'type' => 'checkbox',
					'desc' => esc_html__( 'Hide empty terms', 'prdctfltr' ),
					'section' => 'general',
					'id'   => 'hide_empty',
					'default' => 'no',
				),

				'enable' => array(
					'name' => esc_html__( 'Use AJAX', 'prdctfltr' ),
					'type' => 'checkbox',
					'desc' => esc_html__( 'Check this option to use AJAX in Shop', 'prdctfltr' ),
					'section' => 'ajax',
					'id'   => 'enable',
					'default' => 'no',
					'class' => 'svx-refresh-active-tab',
				),

				'automatic' => array(
					'name' => esc_html__( 'Automatic AJAX', 'prdctfltr' ),
					'type' => 'checkbox',
					'desc' => esc_html__( 'Check this option to use automatic AJAX installation.', 'prdctfltr' ) . ' <strong>' . ( isset( $ajax['recognized'] ) ? esc_html__( 'Theme supported! AJAX is set for', 'prdctfltr' ) . ' ' . esc_html( $ajax['name'] ) : esc_html__( 'Theme not found in database. Using default settings.', 'prdctfltr' ) ) . '</strong>',
					'section' => 'ajax',
					'id'   => 'automatic',
					'default' => 'yes',
					'class' => 'svx-refresh-active-tab',
					'condition' => 'enable:yes',
				),

				'wrapper' => array(
					'name' => esc_html__( 'Product Wrapper', 'prdctfltr' ),
					'type' => 'text',
					'desc' => esc_html__( 'Enter product wrapper jQuery selector.', 'prdctfltr' ) . ' ' . esc_html( 'Currently set to', 'prdctfltr' ) . ': ' . ( isset( $ajax['wrapper'] ) ? esc_html( $ajax['wrapper'] ) : '' ),
					'section' => 'ajax',
					'id'   => 'wrapper',
					'default' => '',
					'condition' => 'enable:yes&&automatic:no',
				),

				'category' => array(
					'name' => esc_html__( 'Product Category', 'prdctfltr' ),
					'type' => 'text',
					'desc' => esc_html__( 'Enter product category jQuery selector.', 'prdctfltr' ) . ' ' . esc_html( 'Currently set to', 'prdctfltr' ) . ': ' . ( isset( $ajax['category'] ) ? esc_html( $ajax['category'] ) : '' ),
					'section' => 'ajax',
					'id'   => 'category',
					'default' => '',
					'condition' => 'enable:yes&&automatic:no',
				),

				'product' => array(
					'name' => esc_html__( 'Product', 'prdctfltr' ),
					'type' => 'text',
					'desc' => esc_html__( 'Enter product jQuery selector.', 'prdctfltr' ) . ' ' . esc_html( 'Currently set to', 'prdctfltr' ) . ': ' . ( isset( $ajax['product'] ) ? esc_html( $ajax['product'] ) : '' ),
					'section' => 'ajax',
					'id'   => 'product',
					'default' => '',
					'condition' => 'enable:yes&&automatic:no',
				),

				'columns' => array(
					'name' => esc_html__( 'Columns', 'prdctfltr' ),
					'type' => 'number',
					'desc' => esc_html__( 'Fix columns problems', 'prdctfltr' ),
					'section' => 'ajax',
					'id'   => 'columns',
					'default' => '',
					'condition' => 'enable:yes&&automatic:no',
				),

				'rows' => array(
					'name' => esc_html__( 'Rows', 'prdctfltr' ),
					'type' => 'number',
					'desc' => esc_html__( 'Fix rows problems', 'prdctfltr' ),
					'section' => 'ajax',
					'id'   => 'rows',
					'default' => '',
					'condition' => 'enable:yes&&automatic:no',
				),

				'result_count' => array(
					'name' => esc_html__( 'Result Count', 'prdctfltr' ),
					'type' => 'text',
					'desc' => esc_html__( 'Enter result count jQuery selector.', 'prdctfltr' ) . ' ' . esc_html( 'Currently set to', 'prdctfltr' ) . ': ' . ( isset( $ajax['result_count'] ) ? esc_html( $ajax['result_count'] ) : '' ),
					'section' => 'ajax',
					'id'   => 'result_count',
					'default' => '',
					'condition' => 'enable:yes&&automatic:no',
				),

				'order_by' => array(
					'name' => esc_html__( 'Order By', 'prdctfltr' ),
					'type' => 'text',
					'desc' => esc_html__( 'Enter order by jQuery selector.', 'prdctfltr' ) . ' ' . esc_html( 'Currently set to', 'prdctfltr' ) . ': ' . ( isset( $ajax['order_by'] ) ? esc_html( $ajax['order_by'] ) : '' ),
					'section' => 'ajax',
					'id'   => 'order_by',
					'default' => '',
					'condition' => 'enable:yes&&automatic:no',
				),

				'pagination' => array(
					'name' => esc_html__( 'Pagination', 'prdctfltr' ),
					'type' => 'text',
					'desc' => esc_html__( 'Enter pagination jQuery selector.', 'prdctfltr' ) . ' ' . esc_html( 'Currently set to', 'prdctfltr' ) . ': ' . ( isset( $ajax['pagination'] ) ? esc_html( $ajax['pagination'] ) : '' ),
					'section' => 'ajax',
					'id'   => 'pagination',
					'default' => '',
					'condition' => 'enable:yes&&automatic:no',
				),

				'pagination_function' => array(
					'name' => esc_html__( 'Pagination Function', 'prdctfltr' ),
					'type' => 'text',
					'desc' => esc_html__( 'Enter pagination function.', 'prdctfltr' ) . ' ' . esc_html( 'Currently set to', 'prdctfltr' ) . ': ' . ( isset( $ajax['pagination_function'] ) ? esc_html( $ajax['pagination_function'] ) : 'woocommerce_pagination' ),
					'section' => 'ajax',
					'id'   => 'pagination_function',
					'default' => '',
					'condition' => 'enable:yes&&automatic:no',
				),

				'pagination_type' => array(
					'name' => esc_html__( 'Pagination Type', 'prdctfltr' ),
					'type' => 'select',
					'desc' => esc_html__( 'Select pagination type', 'prdctfltr' ),
					'section' => 'ajax',
					'id'   => 'pagination_type',
					'options' => array(
						'default' => esc_html__( 'Default (Theme)', 'prdctfltr' ),
						'prdctfltr-pagination-default' => esc_html__( 'Custom pagination (Product Filter)', 'prdctfltr' ),
						'prdctfltr-pagination-load-more' => esc_html__( 'Load more (Product Filter)', 'prdctfltr' ),
						'prdctfltr-pagination-infinite-load' => esc_html__( 'Infinite load (Product Filter)', 'prdctfltr' ),
					),
					'default' => 'default',
					'condition' => 'enable:yes',
				),

				'failsafe' => array(
					'name' => esc_html__( 'Failsafe', 'prdctfltr' ),
					'type' => 'multiselect',
					'desc' => esc_html__( 'Select which missing element will not trigger AJAX and will reload the page', 'prdctfltr' ),
					'section' => 'ajax',
					'id'   => 'failsafe',
					'options' => array(
						'wrapper' => esc_html__( 'Products wrapper', 'prdctfltr' ),
						'product' => esc_html__( 'Products', 'prdctfltr' ),
						'pagination' => esc_html__( 'Pagination', 'prdctfltr' ),
					),
					'default' => '',
					'condition' => 'enable:yes&&automatic:no',
				),

				'js' => array(
					'name' => esc_html__( 'After AJAX JS', 'prdctfltr' ),
					'type' => 'textarea',
					'desc' => esc_html__( 'Enter JavaScript or jQuery code to execute after AJAX', 'prdctfltr' ),
					'section' => 'ajax',
					'id'   => 'js',
					'default' => '',
					'condition' => 'enable:yes&&automatic:no',
				),

				'animation' => array(
					'name' => esc_html__( 'Product Animation', 'prdctfltr' ),
					'type' => 'select',
					'desc' => esc_html__( 'Select product animation after AJAX', 'prdctfltr' ),
					'section' => 'ajax',
					'id'   => 'animation',
					'options' => array(
						'none' => esc_html__( 'No animation', 'prdctfltr' ),
						'default' => esc_html__( 'Fade in products', 'prdctfltr' ),
						'random' => esc_html__( 'Fade in random products', 'prdctfltr' ),
					),
					'default' => '',
					'condition' => 'enable:yes',
				),

				'scroll_to' => array(
					'name' => esc_html__( 'Scroll To', 'prdctfltr' ),
					'type' => 'select',
					'desc' => esc_html__( 'Select scroll to after AJAX', 'prdctfltr' ),
					'section' => 'ajax',
					'id'   => 'scroll_to',
					'options' => array(
							'none' => esc_html__( 'Disable scroll', 'prdctfltr' ),
							'filter' => esc_html__( 'Filter', 'prdctfltr' ),
							'products' => esc_html__( 'Products', 'prdctfltr' ),
							'top' => esc_html__( 'Page top', 'prdctfltr' ),
					),
					'default' => '',
					'condition' => 'enable:yes',
				),

				'permalinks' => array(
					'name' => esc_html__( 'Browser URL/Permalinks', 'prdctfltr' ),
					'type' => 'select',
					'desc' => esc_html__( 'Select how to display browser URLs or permalinks on AJAX', 'prdctfltr' ),
					'section' => 'ajax',
					'id'   => 'permalinks',
					'options' => array(
						'no' => esc_html__( 'Use Product Filter redirects', 'prdctfltr' ),
						'query' => esc_html__( 'Only add query parameters', 'prdctfltr' ),
						'yes' => esc_html__( 'Disable URL changes', 'prdctfltr' ),
					),
					'default' => '',
					'condition' => 'enable:yes',
				),

				'dont_load' => array(
					'name' => esc_html__( 'Disable Elements', 'prdctfltr' ),
					'type' => 'multiselect',
					'desc' => esc_html__( 'Select which elements will not be used with AJAX', 'prdctfltr' ),
					'section' => 'ajax',
					'id'   => 'dont_load',
					'options' => array(
						'breadcrumbs' => esc_html__( 'Breadcrumbs', 'prdctfltr' ),
						'title' => esc_html__( 'Shop title', 'prdctfltr' ),
						'desc' => esc_html__( 'Shop description', 'prdctfltr' ),
						'result' => esc_html__( 'Result count', 'prdctfltr' ),
						'orderby' => esc_html__( 'Order By', 'prdctfltr' ),
					),
					'default' => '',
					'condition' => 'enable:yes&&automatic:no',
				),

				'force_product' => array(
					'name' => esc_html__( 'Post Type', 'prdctfltr' ),
					'type' => 'checkbox',
					'desc' => esc_html__( 'Check this option to add the ?post_type=product parameter when filtering', 'prdctfltr' ),
					'section' => 'ajax',
					'id'   => 'force_product',
					'default' => 'no',
					'condition' => 'enable:no',
				),

				'force_action' => array(
					'name' => esc_html__( 'Stay on Permalink ', 'prdctfltr' ),
					'type' => 'checkbox',
					'desc' => esc_html__( 'Check this option to force filtering on same permalink (URL)', 'prdctfltr' ),
					'section' => 'ajax',
					'id'   => 'force_action',
					'default' => 'no',
					'condition' => 'enable:no',
				),

				'force_redirects' => array(
					'name' => esc_html__( 'Permalink Structure', 'prdctfltr' ),
					'type' => 'select',
					'desc' => esc_html__( 'Set permalinks structure', 'prdctfltr' ),
					'section' => 'ajax',
					'id'   => 'force_redirects',
					'options' => array(
						'no' => esc_html__( 'Use Product Filter redirects', 'prdctfltr' ),
						'yes' => esc_html__( 'Use .htaccess and native WordPress redirects', 'prdctfltr' ),
					),
					'default' => 'no',
					'condition' => 'enable:no',
				),

				'remove_single_redirect' => array(
					'name' => esc_html__( 'Single Product', 'prdctfltr' ),
					'type' => 'checkbox',
					'desc' => esc_html__( 'Check this option to remove redirect when only one product is found', 'prdctfltr' ),
					'section' => 'ajax',
					'id'   => 'remove_single_redirect',
					'default' => 'no',
					'condition' => 'enable:no',
				),

				'actions' => array(
					'name' => esc_html__( 'Integration Hooks', 'prdctfltr' ),
					'type' => 'list',
					'id'   => 'actions',
					'desc' => esc_html__( 'Add filter presets to hooks', 'prdctfltr' ),
					'section' => 'integration',
					'title' => esc_html__( 'Name', 'prdctfltr' ),
					'options' => 'list',
					'default' => '',
					'settings' => array(
						'name' => array(
							'name' => esc_html__( 'Name', 'prdctfltr' ),
							'type' => 'text',
							'id' => 'name',
							'desc' => esc_html__( 'Enter name', 'prdctfltr' ),
							'default' => '',
						),
						'hook' => array(
							'name' => esc_html__( 'Common Hooks', 'prdctfltr' ),
							'type' => 'select',
							'id'   => 'hook',
							'desc' => esc_html__( 'Select a common hook', 'prdctfltr' ),
							'options' => array(
								'' => esc_html__( 'Use custom hook', 'prdctfltr' ),
								'woocommerce_before_main_content' => 'woocommerce_before_main_content',
								'woocommerce_archive_description' => 'woocommerce_archive_description',
								'woocommerce_before_shop_loop' => 'woocommerce_before_shop_loop',
								'woocommerce_after_shop_loop' => 'woocommerce_after_shop_loop',
								'woocommerce_after_main_content' => 'woocommerce_after_main_content',
							),
							'default' => '',
						),
						'action' => array(
							'name' => esc_html__( 'Custom Hook', 'prdctfltr' ),
							'type' => 'text',
							'id'   => 'action',
							'desc' => esc_html__( 'If you use custom hook, rather than common hooks, please enter it here', 'prdctfltr' ),
							'default' => '',
						),
						'priority' => array(
							'name' => esc_html__( 'Priority', 'prdctfltr' ),
							'type' => 'number',
							'id'   => 'priority',
							'desc' => esc_html__( 'Set hook priority', 'prdctfltr' ),
							'default' => '',
						),
						'preset' => array(
							'name' => esc_html__( 'Preset', 'prdctfltr' ),
							'type' => 'select',
							'id'   => 'preset',
							'desc' => esc_html__( 'Set filter preset', 'prdctfltr' ),
							'options' => 'function:__make_presets',
							'default' => '',
							'class' => 'svx-selectize',
						),
						'disable_overrides' => array(
							'name' => esc_html__( 'Presets Manager', 'prdctfltr' ),
							'type' => 'checkbox',
							'id'   => 'disable_overrides',
							'desc' => esc_html__( 'Disable presets manager settings', 'prdctfltr' ),
							'default' => '',
						),
						'id' => array(
							'name' => esc_html__( 'ID', 'prdctfltr' ),
							'type' => 'text',
							'id'   => 'id',
							'desc' => esc_html__( 'Enter filter element ID attribute', 'prdctfltr' ),
							'default' => '',
						),
						'class' => array(
							'name' => esc_html__( 'Class', 'prdctfltr' ),
							'type' => 'text',
							'id'   => 'class',
							'desc' => esc_html__( 'Enter filter element class attribute', 'prdctfltr' ),
							'default' => '',
						),
					),
				),

				'el_result_count' => array(
					'name' => esc_html__( 'Result Count Integration', 'prdctfltr' ),
					'type' => 'select',
					'desc' => esc_html__( 'Replace WooCommerce result count element with a product filter', 'prdctfltr' ),
					'section' => 'integration',
					'id'   => 'el_result_count',
					'options' => 'function:__make_presets:template',
					'default' => '_do_not',
				),

				'el_orderby' => array(
					'name' => esc_html__( 'Order By Integration', 'prdctfltr' ),
					'type' => 'select',
					'desc' => esc_html__( 'Replace WooCommerce order by element with a product filter', 'prdctfltr' ),
					'section' => 'integration',
					'id'   => 'el_orderby',
					'options' => 'function:__make_presets:template',
					'default' => '_do_not',
				),

				'widget_notice' => array(
					'name' => esc_html__( 'Widget Integration', 'prdctfltr' ),
					'type' => 'html',
					'desc' => '
					<div class="svx-option-header"><h3>' . esc_html__( 'Widget Integration', 'prdctfltr' ) . '</h3></div><div class="svx-option-wrapper"><div class="svx-notice svx-info">' . esc_html__( 'Looking for widget integration options? Product Filter widgets are added to sidebars in the WordPress Widgets screen.', 'prdctfltr' ) . ' <a href="' . admin_url( 'widgets.php' ) . '">' . esc_html__( 'Click here to navigate to WordPress Widgets', 'prdctfltr' ) . '</a><br /><br />' . esc_html__( 'If theme that you are using has limited sidebar options, try plugins such as', 'prdctfltr' ) . ' ' . '<a href="https://wordpress.org/plugins/woosidebars/">WooSidebars</a>, <a href="https://wordpress.org/plugins/custom-sidebars/">Custom Sidebars</a></div></div>',
					'section' => 'integration',
					'id'   => 'widget_notice',
				),

				'analytics' => array(
					'name' => esc_html__( 'Use Analytics', 'prdctfltr' ),
					'type' => 'checkbox',
					'desc' => esc_html__( 'Check this option to use filtering analytics', 'prdctfltr' ),
					'section' => 'analytics',
					'id'   => 'analytics',
					'default' => 'no',
				),

				'analytics_ui' => array(
					'name' => esc_html__( 'Filtering Analytics', 'prdctfltr' ),
					'type' => 'html',
					'desc' => '
					<div class="svx-option-header"><h3>' . esc_html__( 'Filtering Analytics', 'prdctfltr' ) . '</h3></div><div class="svx-option-wrapper">' . self::filtering_ananlytics() . '</div>',
					'section' => 'analytics',
					'id'   => 'analytics_ui',
				),

			) );

			return SevenVX()->_do_options( $plugins, self::$plugin['label'] );
		}

		public static function __build_overrides() {
			if ( empty( self::$settings['options']['general']['supported_overrides'] ) ) {
				return array();
			}

			$array = array();

			foreach( self::$settings['options']['general']['supported_overrides'] as $taxonomy ) {

				if ( taxonomy_exists( $taxonomy ) ) {

					$taxonomy = get_taxonomy( $taxonomy );

					$array['_pf_manager_' . $taxonomy->name] = array(
						'name' => $taxonomy->label . ' ' . esc_html__( 'Presets', 'prdctfltr' ),
						'type' => 'list',
						'id'   => '_pf_manager_' . $taxonomy->name,
						'desc' => esc_html__( 'Add filter presets for', 'prdctfltr' ) . ' ' . $taxonomy->label,
						'section' => 'manager',
						'title' => esc_html__( 'Name', 'prdctfltr' ),
						'options' => 'list',
						'default' => '',
						'settings' => array(
							'name' => array(
								'name' => esc_html__( 'Name', 'prdctfltr' ),
								'type' => 'text',
								'id' => 'name',
								'desc' => esc_html__( 'Enter name', 'prdctfltr' ),
								'default' => '',
							),
							'term' => array(
								'name' => esc_html__( 'Term', 'prdctfltr' ),
								'type' => 'select',
								'id'   => 'term',
								'desc' => esc_html__( 'Choose term, that when selected, will show the set filter preset', 'prdctfltr' ),
								'options' => 'ajax:taxonomy:' . $taxonomy->name . ':has_none:no_lang',
								'default' => '',
								'class' => 'svx-selectize',
							),
							'preset' => array(
								'name' => esc_html__( 'Preset', 'prdctfltr' ),
								'type' => 'select',
								'id'   => 'preset',
								'desc' => esc_html__( 'Set filter preset', 'prdctfltr' ),
								'options' => 'function:__make_presets:has_none',
								'default' => '',
								'class' => 'svx-selectize',
							),
						),
					);
				}
			}

			return $array;

		}

		public static function filtering_ananlytics() {
			ob_start();

			$stats = get_option( '_prdctfltr_analytics', array() );

			if ( empty( $stats ) ) {
			?>
				<div class="svx-notice svx-info">
				<?php
					esc_html_e( 'Filtering analytics are empty!', 'prdctfltr' );
				?>
				</div>
			<?php
			}
			else {
			?>
				<div class="pf-analytics-wrapper">
			<?php
				foreach( $stats as $k => $v ) {
					$show = array();
				?>
					<div class="pf-analytics">
					<?php
						$mode = 'default';
						if ( taxonomy_exists( $k ) ) {
							$mode = 'taxonomy';
							if ( substr( $k, 0, 3 ) == 'pa_' ) {
								$label = wc_attribute_label( $k );
							}
							else {
								if ( $k == 'product_cat' ) {
									$label = esc_html__( 'Categories', 'prdctfltr' );
								}
								else if ( $k == 'product_tag' ) {
									$label = esc_html__( 'Tags', 'prdctfltr' );
								}
								else if ( $k == 'characteristics' ) {
									$label = esc_html__( 'Characteristics', 'prdctfltr' );
								}
								else {
									$curr_term = get_taxonomy( $k );
									$label = $curr_term->name;
								}
							}
						}

						if ( $mode == 'taxonomy' ) {
							if ( !empty( $v ) && is_array( $v ) ) {
								foreach( $v as $vk => $vv ) {
									$term = get_term_by( 'slug', $vk, $k );

									if ( isset( $term->name ) ) {
										$term_name = ucfirst( $term->name ) . ' ( ' . $v[$vk] .' )';
									}
									else {
										$term_name = 'Unknown';
									}

									$show[$term_name] = $v[$vk];
								}
								$title = ucfirst( $label );
							}
						}
						else {
							$title = ucfirst( $k );
						}

					?>
					<div class="pf-analytics-info">
						<strong><?php echo esc_html( $title ); ?></strong>
					</div>
					<div id="<?php echo uniqid( 'pf-analytics-chart-' ); ?>" class="pf-analytics-chart" data-chart="<?php echo esc_attr( json_encode( $show ) ); ?>"></div>
				</div>
			<?php
				}
		?>
			</div>
			<div class="pf-analytics-settings">
				<div class="svx-notice svx-info">
					<?php esc_html_e( 'Click the button to reset filtering analytics.', 'prdctfltr' ); ?><br /><br />
					<span id ="pf-analytics-reset" class="svx-button"><?php esc_html_e( 'Reset analytics', 'prdctfltr' ); ?></span>
				</div>
			</div>
		<?php
			}
			return ob_get_clean();
		}

		public static function analytics_reset() {
			delete_option( '_prdctfltr_analytics' );

			wp_die(1);
			exit;
		}

		public static function stripslashes_deep( $value ) {
			$value = is_array($value) ? array_map('stripslashes_deep', $value) : stripslashes($value);
			return $value;
		}

	}

	XforWC_Product_Filters_Settings::init();

