<?php
/*
Plugin Name: Product Filter for WooCommerce
Plugin URI: https://xforwoocommerce.com
Description: XforWooCommerce Themes and Plugins! Visit https://xforwoocommerce.com
Author: XforWooCommerce
License: Codecanyon Split Licence
Version: 8.3.0
Requires at least: 4.5
Tested up to: 6.9.9
WC requires at least: 3.5.0
WC tested up to: 6.9.9
Author URI: https://xforwoocommerce.com
Text Domain: prdctfltr
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( !function_exists( 'XforWC' ) ) {
	require_once( 'includes/svx-settings/load.php' );

	if ( xforwccb() ) {	return false; }
}

$GLOBALS['svx'] = isset( $GLOBALS['svx'] ) && version_compare( $GLOBALS['svx'], '1.6.1') == 1 ? $GLOBALS['svx'] : '1.6.1';

if ( !class_exists( 'XforWC_Product_Filters' ) ) :

	final class XforWC_Product_Filters {

		public static $version = '8.3.0';

		protected static $_instance = null;
		public static $lang = null;
		public static $settings = null;

		public static function instance() {

			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}

		public function __construct() {
			do_action( 'prdctfltr_loading' );

			$this->includes();

			if ( !function_exists( 'XforWC' ) ) {
				$this->single_plugin();
			}

			do_action( 'prdctfltr_loaded' );
		}

		private function single_plugin() {
			if ( is_admin() ) {
				register_activation_hook( __FILE__, array( $this, 'activate' ) );
			}

			include_once( 'includes/svx-settings/svx-get.php' );
			add_action( 'init', array( $this, 'load_svx' ), 100 );

			// Texdomain only used if out of XforWC
			add_action( 'init', array( $this, 'textdomain' ), 0 );
		}

		public function activate() {
			if ( !class_exists( 'WooCommerce' ) ) {
				deactivate_plugins( plugin_basename( __FILE__ ) );

				wp_die( esc_html__( 'This plugin requires WooCommerce. Download it from WooCommerce official website', 'prdctfltr' ) . ' &rarr; https://woocommerce.com' );
				exit;
			}
		}

		public function load_svx() {
			if ( $this->is_request( 'admin' ) ) {
				include_once( 'includes/svx-settings/svx-settings.php' );
			}
		}

		private function is_request( $type ) {
			switch ( $type ) {
				case 'admin' :
					return is_admin();
				case 'ajax' :
					return defined( 'DOING_AJAX' );
				case 'cron' :
					return defined( 'DOING_CRON' );
				case 'frontend' :
					return ( ! is_admin() || defined( 'DOING_AJAX' ) ) && ! defined( 'DOING_CRON' );
			}
		}

		public function includes() {

			$this->__get_default_options();

			add_action( 'init', array( $this, 'register_taxonomies' ), 9999 );

			include_once( 'includes/pf-widget.php' );

			if ( $this->is_request( 'admin' ) ) {
				$this->admin_includes();
			}

			if ( $this->is_request( 'frontend' ) ) {
				$this->frontend_includes();
			}

		}

		public function admin_includes() {
			add_action( 'vc_before_init', array( $this, 'composer' ) );
			include_once( 'includes/pf-settings.php' );
		}

		public function frontend_includes() {
			include_once( 'includes/pf-frontend.php' );
			include_once( 'includes/pf-shortcode.php' );
		}

		function __get_default_options() {

			self::$settings = get_option( '_prdctfltr_autoload', false );

			if ( empty( self::$settings ) && get_option( 'wc_settings_prdctfltr_version', false ) !== false ) {
				include_once( 'includes/compatibility/pf-compatible.php' );
				self::$settings = XforWC_Product_Filters_Compatible_Settings::fix_options();

				if ( empty( self::$settings['manager'] ) && class_exists( 'XforWC_Product_Filters_Compatible_Settings' ) ) {
					self::$settings['manager'] = XforWC_Product_Filters_Compatible_Settings::_fix_overrides();
				}
			}

			include_once( 'includes/class-default.php' );
			self::$settings = XforWC_Product_Filters_Defaults::__get_default_autoload( self::$settings );

		}

		public function get_default_options() {
			if ( !empty( self::$settings ) ) {
				return self::stripslashes_deep( self::$settings );
			}
			return array();
		}

		public function ___get_preset( $key ) {

			$option = array();

			if ( isset( $key ) && is_string( $key ) ) {

				$lang = $this->get_language() === false ? '' : $this->get_language();

				$preset = '_prdctfltr_preset_' . $key . $lang;

				$option = get_option( $preset, false );

				if ( $option === false && $lang !== '' ) {
					$option = get_option( '_prdctfltr_preset_' . $key, array() );
				}

				if ( $option === false && get_option( 'wc_settings_prdctfltr_version', false ) !== false ) {
					$preset = $key !== 'default' ? 'prdctfltr_wc_template_' . $key . $lang : 'prdctfltr_wc_default' . $lang;
					if ( get_option( $preset ) === false && $lang !== '' ) {
						$preset = $key !== 'default' ? 'prdctfltr_wc_template_' . $key : 'prdctfltr_wc_default';
					}

					include_once( 'includes/compatibility/pf-compatible-preset.php' );
					$option = XforWC_Product_Filters_Compatible_Preset::fix_preset( $preset );
				}

				if ( empty( $option ) && $key != 'default' ) {
					$this->___get_preset( 'default' );
				}

			}

			if ( empty( $option ) ) {
				$option = array();
			}

			include_once( 'includes/class-default.php' );
			$option = XforWC_Product_Filters_Defaults::__get_default_preset( $option );

			if ( !empty( $option ) ) {
				$option['preset'] = $key;
			}

			return self::stripslashes_deep( $option );

		}

		public function __get_presets() {
			if ( !empty( self::$settings ) && !empty( self::$settings['presets'] ) ) {
				return self::$settings['presets'];
			}
			return array();
		}

		public static function stripslashes_deep( $value ) {
			$value = is_array($value) ? array_map('stripslashes_deep', $value) : stripslashes($value);
			return $value;
		}

		public function textdomain() {

			$this->load_plugin_textdomain();

		}

		public function load_plugin_textdomain() {

			$domain = 'prdctfltr';
			$dir = untrailingslashit( WP_LANG_DIR );
			$locale = apply_filters( 'plugin_locale', get_locale(), $domain );

			if ( $loaded = load_textdomain( $domain, $dir . '/plugins/' . $domain . '-' . $locale . '.mo' ) ) {
				return $loaded;
			}
			else {
				load_plugin_textdomain( $domain, FALSE, basename( dirname( __FILE__ ) ) . '/lang/' );
			}

		}

		public function register_taxonomies() {
			$taxonomies = false;

			if ( !empty( self::$settings['general']['register_taxonomy'] ) ) {
				$taxonomies = isset( self::$settings['general']['register_taxonomy'] ) ? self::$settings['general']['register_taxonomy'] : false;
			}

			if ( is_array( $taxonomies ) ) {
				foreach ( $taxonomies as $taxonomy ) {
					$this->register_taxonomy( $taxonomy );
				}
			}
		}

		public function register_taxonomy( $taxonomy ) {

			$taxonomy_plural = isset( $taxonomy['name'] ) ? substr( sanitize_text_field( $taxonomy['name'] ), 0, 32 ) : 'Taxonomy';
			$taxonomy_single = isset( $taxonomy['single_name'] ) ? substr( sanitize_text_field( $taxonomy['single_name'] ), 0, 32 ) : 'Taxonomies';
			$taxonomy_hierarchy = isset( $taxonomy['hierarchy'] ) && $taxonomy['hierarchy'] == 'yes' ? true : false;

			$taxonomy_slug = sanitize_title( $taxonomy_plural );

			if ( !empty( $taxonomy_slug ) && !taxonomy_exists( $taxonomy_slug ) ) {
				$labels = array(
					'name'                       => $taxonomy_plural,
					'singular_name'              => $taxonomy_single,
					'search_items'               => esc_html__( 'Search', 'prdctfltr' ) . ' ' . $taxonomy_plural,
					'popular_items'              => esc_html__( 'Popular', 'prdctfltr' ) . ' ' . $taxonomy_plural,
					'all_items'                  => esc_html__( 'All', 'prdctfltr' ) . ' ' . $taxonomy_plural,
					'parent_item'                => null,
					'parent_item_colon'          => null,
					'edit_item'                  => esc_html__( 'Edit', 'prdctfltr' ) . ' ' . $taxonomy_plural,
					'update_item'                => esc_html__( 'Update', 'prdctfltr' ) . ' ' . $taxonomy_plural,
					'add_new_item'               => esc_html__( 'Add New', 'prdctfltr' ) . ' ' . $taxonomy_single,
					'new_item_name'              => esc_html__( 'New', 'prdctfltr' ) . ' ' . $taxonomy_single . ' ' . esc_html__( 'name', 'prdctfltr' ),
					'separate_items_with_commas' => esc_html__( 'Separate', 'prdctfltr' ) . ' ' . $taxonomy_plural . ' ' . esc_html__( 'with commas', 'prdctfltr' ),
					'add_or_remove_items'        => esc_html__( 'Add or remove', 'prdctfltr' ) . ' ' . $taxonomy_plural,
					'choose_from_most_used'      => esc_html__( 'Choose from the most used', 'prdctfltr' ) . ' ' . $taxonomy_plural,
					'not_found'                  => esc_html__( 'No', 'prdctfltr' ) . ' ' . $taxonomy_plural . ' ' . esc_html__( 'found', 'prdctfltr' ),esc_html__( 'No Characteristics found', 'prdctfltr' ),
					'menu_name'                  => $taxonomy_plural
				);

				$args = array(
					'hierarchical'          => $taxonomy_hierarchy,
					'labels'                => $labels,
					'show_ui'               => true,
					'show_admin_column'     => true,
					'update_count_callback' => '_update_post_term_count',
					'query_var'             => true,
					'rewrite'               => array( 'slug' => $taxonomy_slug ),
				);

				register_taxonomy( $taxonomy_slug, array( 'product' ), apply_filters( 'prdctfltr_taxonomy_' . $taxonomy_slug, $args ) );
			}

		}

		public function plugin_url() {
			return untrailingslashit( plugins_url( '/', __FILE__ ) );
		}

		public function template_path() {
			return apply_filters( 'prdctfltr_template_path', '/templates/' );
		}

		public function plugin_path() {
			return untrailingslashit( plugin_dir_path( __FILE__ ) );
		}

		public function plugin_basename() {
			return untrailingslashit( plugin_basename( __FILE__ ) );
		}

		public function ajax_url() {
			return admin_url( 'admin-ajax.php', 'relative' );
		}

		public function version() {
			return self::$version;
		}

		public function composer() {
			require_once( 'includes/pf-composer.php' );
		}

		public static function version_check( $version = '3.0.0' ) {
			if ( class_exists( 'WooCommerce' ) ) {
				global $woocommerce;
				if( version_compare( $woocommerce->version, $version, ">=" ) ) {
					return true;
				}
			}
			return false;
		}

		public function get_language() {

			if ( self::$lang ) {
				return self::$lang;
			}

			self::$lang = false;

			if ( class_exists( 'SitePress' ) ) {
				$default = '_' . apply_filters( 'wpml_default_language', NULL );
				$language = '_' . apply_filters( 'wpml_current_language', NULL );
				if ( $default !== $language ) {
					$doit = $language;
				}
			}

			if ( function_exists( 'qtranxf_getLanguageDefault' ) ) {
				$default = '_' . qtranxf_getLanguageDefault();
				$language = '_' . qtranxf_getLanguage();
				if ( $default !== $language ) {
					$doit = $language;
				}
			}

			if ( function_exists( 'pll_default_language' ) ) {
				$default = '_' . pll_default_language();
				$language = '_' . pll_current_language();
				if ( $default !== $language ) {
					$doit = $language;
				}
			}

			if ( isset( $doit ) ) {
				self::$lang = $doit;
			}

			return self::$lang;

		}

		public static function esc_color( $color ) {
			if ( empty( $color ) || is_array( $color ) ) {
				return 'rgba(0,0,0,0.0625)';
			}

			if ( false === strpos( $color, 'rgba' ) ) {
				return sanitize_hex_color( $color );
			}

			$color = str_replace( ' ', '', $color );
			sscanf( $color, 'rgba(%d,%d,%d,%f)', $red, $green, $blue, $alpha );
			return 'rgba('.$red.','.$green.','.$blue.','.$alpha.')';
		}

	}

	function Prdctfltr() {
		return XforWC_Product_Filters::instance();
	}

	XforWC_Product_Filters::instance();

endif;
