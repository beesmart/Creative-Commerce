<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class XforWC_Product_Filters_Themes {

	public static function ___get_compatibles() {
		return array(
			'avada', 'atelier', 'divi', 'salient', 'porto', 'astra', 'impreza', 'thegem', 'bb-theme', 'bila', 'shopkit', 'storefront', 'twentytwelve', 'twentythirteen', 'twentyfourteen', 'twentyfifteen', 'twentysixteen', 'twentyseventeen', 'twentynineteen', 'rehub', 'camelia', 'jupiterx', 'oceanwp', 'phlox', 'thegem', 'nantes', 'wr-nitro', 'box-shop', 'ciyashop', 'antive', 'business-hub', 'grotte', 'luchiana'
		);
	}

	public static function ___get_noajax() {
		return array(
			'blance', 'electro', 'nozama', 'uncode', 'snsvicky', 'halena', 'snsevon', 'xstore'
		);
	}

	public static function get_theme() {

		$ajax = self::___options_default();

		$ajax['name'] = wp_get_theme()->get( 'Name' );
		$ajax['template'] = sanitize_title( strtolower( get_template() ) );

		if ( in_array( $ajax['template'], self::___get_compatibles() ) ) {
			$ajax['recognized'] = true;
			return $ajax;
		} 

		if ( method_exists( 'XforWC_Product_Filters_Themes', '__options_for_' . $ajax['template'] ) ) {
			$themeAjax = call_user_func( 'XforWC_Product_Filters_Themes::__options_for_' . $ajax['template'] );
			if ( !empty( $themeAjax ) ) {
				$ajax = array_merge( $ajax, $themeAjax );
				$ajax['recognized'] = true;
			}
			return $ajax;
		}
	
		if ( in_array( $ajax['template'], self::___get_noajax() ) ) {
			return false;
		}

		return $ajax;

	}
	
	public static function __options_for_enfold() {
		return array(
			'pagination' => '.pagination',
			'pagination_function' => 'avia_pagination',
		);
	}

	public static function __options_for_flatsome() {
		return array(
			'pagination' => '.products + .container',
		);
	}

	public static function __options_for_shopkeeper() {
		return array(
			'product' => 'li',
		);
	}

	public static function __options_for_merchandiser() {
		return array(
			'product' => '.product',
		);
	}

	public static function __options_for_anakual() {
		return array(
			'pagination' => '.woo-pagination',
			'js' => '$(".product-caption").css({opacity:"0",filter:"alpha(opacity=0)"}),$(".product-wrapper").each(function(){$(this).on("mouseenter",function(){return $(this).find(".product-caption").stop().fadeTo(900,1),$(".add-cart,.add_to_cart_button",this).stop().animate({bottom:"0"},{queue:!1,duration:300}),$(".added_to_cart",this).stop().animate({bottom:"50px"},{queue:!1,duration:300}),$(".view-detail,.woocommerce-LoopProduct-link",this).stop().animate({bottom:"0"},{queue:!1,duration:500}),!1}),$(this).on("mouseleave",function(){return $(this).find(".product-caption").stop().fadeTo(900,0),$(".add-cart,.add_to_cart_button,.added_to_cart",this).stop().animate({bottom:"-50px"},{queue:!1,duration:500}),$(".view-detail,.woocommerce-LoopProduct-link",this).stop().animate({bottom:"-50px"},{queue:!1,duration:500}),!1})});',
		);
	}

	public static function __options_for_cristiano() {
		return array(
			'wrapper' => '#product-list',
			'pagination' => '.pagination',
			'pagination_function' => 'the_posts_pagination',
		);
	}

	public static function __options_for_bazar() {
		return array(
			'pagination' => '.general-pagination',
		);
	}

	public static function __options_for_airi() {
		return array(
			'wrapper' => '.products-grid',
			'pagination' => '.la-pagination',
		);
	}

	public static function __options_for_ronneby() {
		return array(
			'pagination' => '.page-nav',
		);
	}

	public static function __options_for_betheme() {
		return array(
			'pagination' => '.pager_wrapper',
		);
	}

	public static function __options_for_legenda() {
		return array(
			'wrapper' => '.product-loop',
		);
	}

	public static function __options_for_x() {
		return array(
			'pagination' => '.pagination',
		);
	}

	public static function __options_for_woodmart() {
		return array(
			'pagination' => '.products-footer',
		);
	}

	public static function __options_for_kallyas() {
		return array(
			'pagination' => '.pagination--light',
			'pagination_function' => 'zn_woocommerce_pagination',
		);
	}

	public static function __options_for_stockie() {
		return array(
			'pagination' => '.pagination',
		);
	}

	public static function __options_for_mediacenter() {
		return array(
			'js' => 'function setConformingHeight(a,b){a.data("originalHeight",void 0==a.data("originalHeight")?a.height():a.data("originalHeight")),a.height(b)}function getOriginalHeight(a){return void 0==a.data("originalHeight")?a.height():a.data("originalHeight")}function columnConform(){$(".products > .product").each(function(){var a=$(this);if(a.is(":visible")){var b=a.position().top;if(currentRowStart!=b){for(var c=0;c<rowDivs.length;c++)setConformingHeight(rowDivs[c],currentTallest);rowDivs.length=0,currentRowStart=b,currentTallest=getOriginalHeight(a),rowDivs.push(a)}else rowDivs.push(a),currentTallest=currentTallest<getOriginalHeight(a)?getOriginalHeight(a):currentTallest;for(var c=0;c<rowDivs.length;c++)setConformingHeight(rowDivs[c],currentTallest)}})}var currentTallest=0,currentRowStart=0,rowDivs=new Array;columnConform();',
		);
	}

	public static function __options_for_royal() {
		return array(
			'wrapper' => '.products-loop',
			'product' => '.product',
		);
	}

	public static function __options_for_movedo() {
		return array(
			'wrapper' => '.grve-product-container',
			'js' => 'GRVE.wooProductsLoop.init();',
		);
	}

	public static function __options_for_luchiana() {
		return array(
			'wrapper' => '.c-product-grid__list',
			'product' => '.c-product-grid__item',
		);
	}

	public static function ___options_default() {
		return array(
			'wrapper' => '.products',
			'category' => '.product-category',
			'product' => '.type-product',
			'result_count' => '.woocommerce-result-count',
			'order_by' => '.woocommerce-ordering',
			'pagination' => '.woocommerce-pagination',
		);
	}

}


