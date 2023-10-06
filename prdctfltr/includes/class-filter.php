<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class XforWC_Product_Filters_Hooks {

	function __construct( $data ) {
		$this->data = $data;
		$this->start();
	}

	public function start() {
		$this->data['hook'] = isset( $this->data['hook'] ) ? $this->data['hook'] : false;

		if ( empty( $this->data['hook'] ) && !empty( $this->data['action'] ) ) {
			$this->data['hook'] = $this->data['action'];
		}
		
		if ( $this->data['hook'] !== false ) {
			$this->data['priority'] = isset( $this->data['priority'] ) && intval( $this->data['priority'] ) ? $this->data['priority'] : 10;
			$this->data['preset'] = isset( $this->data['preset'] ) ? $this->data['preset'] : false;
			$this->data['id'] = !empty( $this->data['id'] ) ? sanitize_title( $this->data['id'] ) : false;
			$this->data['class'] = !empty( $this->data['class'] ) ? sanitize_title( $this->data['class'] ) : false;
			$this->data['disable_overrides'] = !empty( $this->data['disable_overrides'] ) && $this->data['disable_overrides'] == 'yes' ? 'yes' : false;

			$this->data['has_wrapper'] = $this->data['id'] !== false || $this->data['class'] ? true : false;

			add_action( $this->data['hook'], array( $this, 'get_filter' ), $this->data['priority'] );
		}
	}

	public function get_filter() {
		global $prdctfltr_global;

		if ( !empty( $this->data['preset'] ) ) {
			$prdctfltr_global['preset'] = $this->data['preset'];
		}
		if ( !empty( $this->data['disable_overrides'] ) ) {
			$prdctfltr_global['disable_overrides'] = 'yes';
		}

		$this->get_before();

		include( Prdctfltr()->plugin_path() . '/templates/product-filter.php' );

		$this->get_after();
	}

	public function get_before() {
		if ( $this->data['has_wrapper'] ) {
			printf( '<div%s%s>', $this->data['id'] ? ' id="' . esc_attr( $this->data['id'] ) . '"' : '', $this->data['class'] ? ' class="' . esc_attr( $this->data['class'] ) . '"' : '' );
		}
	}

	public function get_after() {
		if ( $this->data['has_wrapper'] ) {
			echo '</div>';
		}
	}

}