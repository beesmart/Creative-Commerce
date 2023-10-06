<?php
/**
 * @package   Barn2\woocommerce-product-filters
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */

namespace Barn2\Plugin\WC_Filters\Integrations;

use Barn2\Plugin\WC_Filters\Display;

use function Barn2\Plugin\WC_Filters\wcf;

/**
 * Avada theme-specific styling.
 */
class Theme_Avada extends Theme_Integration {

	public $template = 'Avada';

	/**
	 * @inheritdoc
	 */
	public function register() {
		parent::register();

		if ( $this->should_enqueue() ) {
			add_filter( 'wcf_bypass_loop_tag', [ $this, 'bypass_loop_tag' ], 10, 3 );
			add_filter( 'fusion_element_woo_product_grid_content', [ $this, 'inject_required_elements' ], 10, 2 );
		}
	}

	/**
	 * Avada fires custom queries on the archive page which break detection of the WC loop.
	 * We use the filter to add further checks required to validate that we're firing
	 * the loop tags in the appropriate loop.
	 *
	 * @param bool $bypass
	 * @param \WP_Query $query
	 * @param Display $instance
	 * @return bool
	 */
	public function bypass_loop_tag( $bypass, $query, Display $instance ) {
		if ( ! $query instanceof \WP_Query ) {
			return $bypass;
		}

		if ( $instance->is_product_taxonomy_page() && ! $instance->is_product_post_type( $query ) ) {
			$post_types = $query->get( 'post_type' );

			if ( is_array( $post_types ) && in_array( 'fusion_tb_layout', $post_types, true ) ) {
				return true;
			}

			if ( ! is_array( $post_types ) && $post_types === 'fusion_tb_layout' ) {
				return true;
			}
		}

		return $bypass;
	}

	/**
	 * Inject missing html elements inside the theme builder provided by Avada.
	 *
	 * @param string $html
	 * @param array $args
	 * @return string
	 */
	public function inject_required_elements( $html, $args ) {

		$display_service = wcf()->get_service( 'display' );

		ob_start();
		if ( ! is_shop() ) {
			$display_service->add_mobile_drawer();
			$display_service->add_active_filters();
			$display_service->add_sorting_bar( true );
		}
		$mobile = ob_get_clean();

		return $mobile . $html;
	}

	/**
	 * @inheritdoc
	 */
	public function enqueue_fix() {
	}

}
