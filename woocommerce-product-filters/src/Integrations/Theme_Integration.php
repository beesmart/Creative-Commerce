<?php
/**
 * @package   Barn2\woocommerce-product-filters
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */

namespace Barn2\Plugin\WC_Filters\Integrations;

use Barn2\Plugin\WC_Filters\Display;
use Barn2\Plugin\WC_Filters\Dependencies\Lib\Registerable;

/**
 * Base theme integration class.
 *
 * A theme integration class takes care of adding inline css styling
 * to fix minor graphical glitches across themes.
 */
abstract class Theme_Integration implements Registerable {

	/**
	 * The name of the current template.
	 * Use wp_get_theme to get it.
	 *
	 * @var string
	 */
	public $template = '';

	/**
	 * Hook into WP.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'wp_enqueue_scripts', [ $this, 'theme_inline_styling' ], 30 );
	}

	/**
	 * Load the inline styling when needed.
	 *
	 * @return void
	 */
	public function theme_inline_styling() {

		$enqueued = wp_script_is( Display::IDENTIFIER, 'enqueued' );

		if ( $enqueued && $this->should_enqueue() ) {
			$this->enqueue_dummy_handle();
			$this->enqueue_fix();
		}
	}

	/**
	 * Determine if the inline styling should enqueue or not.
	 * Usually you check that the specific theme is enabled here.
	 *
	 * @return boolean
	 */
	public function should_enqueue() {
		return wp_get_theme()->template === $this->template;
	}

	/**
	 * Theme-specific inline css styling.
	 *
	 * @return void
	 */
	abstract public function enqueue_fix();

	/**
	 * Generate an handle name for the dummy stylesheet
	 * to which we'll attach the custom inline styling.
	 *
	 * @return string
	 */
	public function get_dummy_handle() {
		return $this->template . '-dummy-handle';
	}

	/**
	 * Enqueue dummt handle.
	 *
	 * @return void
	 */
	public function enqueue_dummy_handle() {
		wp_register_style( $this->get_dummy_handle(), false );
		wp_enqueue_style( $this->get_dummy_handle() );
	}

}
