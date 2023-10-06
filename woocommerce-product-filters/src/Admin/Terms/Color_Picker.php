<?php
/**
 * @package   Barn2\woocommerce-product-filters
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */

namespace Barn2\Plugin\WC_Filters\Admin\Terms;

use Barn2\Plugin\WC_Filters\Plugin;
use Barn2\Plugin\WC_Filters\Utils\Terms;

/**
 * Display a color picker on product attributes.
 */
class Color_Picker extends React_Term {

	public $file_name = 'wcf-attribute-color';

	/**
	 * Hook into WP.
	 *
	 * @return void
	 */
	public function register() {
		parent::register();

		add_action( 'admin_init', [ $this, 'register_into_attribute_forms' ] );
	}

	/**
	 * Enqueue assets for this specific field type.
	 *
	 * @return void
	 */
	public function assets() {

		if ( ! $this->should_enqueue() ) {
			return;
		}

		wp_enqueue_style( 'wp-color-picker' );

		wp_enqueue_script(
			'iris',
			admin_url( 'js/iris.min.js' ),
			[ 'jquery-ui-draggable', 'jquery-ui-slider', 'jquery-touch-punch' ],
			$this->plugin->get_version(),
			true
		);
		wp_enqueue_script(
			'wp-color-picker',
			admin_url( 'js/color-picker.min.js' ),
			[ 'iris' ],
			$this->plugin->get_version(),
			true
		);
		$colorpicker_l10n = [
			'clear'         => __( 'Clear', 'woocommerce-product-filters' ),
			'defaultString' => __( 'Default', 'woocommerce-product-filters' ),
			'pick'          => __( 'Select Color', 'woocommerce-product-filters' ),
			'current'       => __( 'Current Color', 'woocommerce-product-filters' ),
		];
		wp_localize_script( 'wp-color-picker', 'wpColorPickerL10n', $colorpicker_l10n );

		parent::assets();
	}

	/**
	 * Find WC attributes and display out custom field for the picker.
	 *
	 * @return void
	 */
	public function register_into_attribute_forms() {
		$attributes = wc_get_attribute_taxonomies();

		if ( empty( $attributes ) || ! is_array( $attributes ) ) {
			return;
		}

		foreach ( $attributes as $attr ) {
			add_action( 'pa_' . $attr->attribute_name . '_add_form_fields', [ $this, 'output' ] );
			add_action( 'pa_' . $attr->attribute_name . '_edit_form_fields', [ $this, 'output_edit' ], 10, 2 );
		}
	}

	/**
	 * Determine if the react assets should enqueue.
	 *
	 * @return boolean
	 */
	public function should_enqueue() {
		$screen = get_current_screen();

		if ( $screen->base === 'edit-tags' || $screen->base === 'term' ) {
			$taxonomy = $screen->taxonomy;

			return taxonomy_is_product_attribute( $taxonomy );
		}

		return false;
	}

	/**
	 * Print the color picker field.
	 *
	 * @param string $value optional input value
	 * @return void
	 */
	private function input( $value = null, $label = true ) {

		if ( empty( $value ) ) {
			$value = '#fff';
		}

		?>
		<div class="wcf-color-picker">
			<?php if ( $label ) : ?>
				<label id="wcf-color-picker-label">
				<?php
					echo wc_help_tip( __( 'If you are using this attribute in a color filter, select the exact color which will appear in the filter.', 'woocommerce-product-filters' ) );
					esc_html_e( 'Assign color', 'woocommerce-product-filters' );
				?>
				</label>
			<?php endif; ?>
			<span class="colorpickpreview" style="background:<?php echo esc_attr( $value ); ?>;">&nbsp;</span>
			<input class="colorpick" type="text" name="term_color_picker" id="term_color_picker" value="<?php echo esc_attr( $value ); ?>" />
			<div id="colorPickerDiv_term_color_picker" class="colorpickdiv"></div>
		</div>
		<?php
	}

	/**
	 * Renders the picker dom element.
	 *
	 * @return void
	 */
	public function output() {
		$this->input();
	}

	/**
	 * Renders the picker dom element on the term edit.
	 *
	 * @param \WP_Term $term
	 * @param string $taxonomy
	 * @return void
	 */
	public function output_edit( $term, $taxonomy ) {
		$term_id = $term->term_id;
		$color   = Terms::get_color( $term_id );

		?>
		<tr class="form-field wcf-color-picker-table">
			<th scope="row">
				<?php
					echo '<span>' . esc_html__( 'Assign color', 'woocommerce-product-filters' ) . '</span>';
					echo wc_help_tip( __( 'If you are using this attribute in a color filter, select the exact color which will appear in the filter.', 'woocommerce-product-filters' ) );
				?>
			</th>
			<td>
				<?php $this->input( $color, false ); ?>
			</td>
		</tr>
		<?php
	}

	/**
	 * When the attribute is saved, save the color details into the database.
	 *
	 * @param string $term_id
	 * @param string $tt_id
	 * @param string $taxonomy
	 * @return void
	 */
	public function created_term( $term_id, $tt_id, $taxonomy ) {
		if ( ! isset( $_POST['term_color_picker'] ) ) {
			return;
		}

		$color = wc_clean( $_POST['term_color_picker'] );

		$meta_key = Plugin::META_PREFIX;

		if ( empty( $color ) ) {
			delete_term_meta( $term_id, $meta_key . 'term_color', $color );
			return;
		}

		update_term_meta( $term_id, $meta_key . 'term_color', $color );

	}

}
