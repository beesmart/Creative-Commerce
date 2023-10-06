<?php
/**
 * @package   Barn2\woocommerce-product-filters
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */

namespace Barn2\Plugin\WC_Filters;

use Barn2\Plugin\WC_Filters\Utils\Settings;
use Barn2\Plugin\WC_Filters\Dependencies\Lib\Registerable;

/**
 * Filter Group Widget
 */
class Widget extends \WP_Widget implements Registerable {

	/**
	 * Initialize the widget.
	 */
	public function __construct() {
		$widget_ops = [
			'classname'   => 'wcf-filter-group',
			'description' => __( 'Display a filter group created with the WooCommerce Product Filters plugin.', 'woocommerce-product-filters' ),
		];
		parent::__construct( 'wcf_filter_group', __( 'Product Filters', 'woocommerce-product-filters' ), $widget_ops ); //phpcs:ignore
	}

	/**
	 * Hook the widget.
	 *
	 * @return void
	 */
	public function register() {
		add_action(
			'widgets_init',
			function() {
				register_widget( __CLASS__ );
			}
		);
	}

	/**
	 * Widget ouput.
	 *
	 * @param array $args
	 * @param array $instance
	 * @return void
	 */
	public function widget( $args, $instance ) {

		global $pagenow;

		extract( $args ); //phpcs:ignore

		//if ( current_user_can( 'edit_theme_options' ) && $pagenow === 'index.php' ) {
		//	return;
		//}

		$title = apply_filters( 'widget_title', $instance['title'] );

		echo $before_widget; //phpcs:ignore

		if ( ! empty( $title ) ) {
			echo $before_title . $title . $after_title; //phpcs:ignore
		}

		// Grab filter group ID
		$group = $instance['group'];

		$title_opening_tag = $args['before_title'];
		$title_closing_tag = $args['after_title'];
		$layout            = 'vertical';

		// Bypass preview of the widget when in editor mode in Elementor.
		if ( class_exists( '\Elementor\Plugin' ) && \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
			echo esc_html__( 'Your product filters will appear here.', 'woocommerce-product-filters' );
			return;
		}

		if ( is_admin() ) {
			echo '<p class="woocommerce-info">' . esc_html__( 'Filters are only visible on the frontend', 'woocommerce-product-filters' ) . '</p>';
		} else {
			if ( is_numeric( $group ) ) {
				echo do_shortcode( '[wpf-filters id="' . absint( $group ) . '" layout="' . esc_attr( $layout ) . '" widget="yes" opening="' . htmlentities( $title_opening_tag ) . '" closing="' . htmlentities( $title_closing_tag ) . '"]' );
			}
		}

		echo $after_widget; //phpcs:ignore
	}

	/**
	 * Widget configuration form.
	 *
	 * @param array $instance
	 * @return void
	 */
	public function form( $instance ) {
		$defaults = [
			'title'  => '',
			'group'  => '',
			'layout' => 'vertical',
		];

		$instance = wp_parse_args( $instance, $defaults );

		$groups = Settings::get_groups_for_dropdown();

		?>
			<p>
				<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title', 'woocommerce-product-filters' ); ?></label>
				<input type="text" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" value="<?php echo esc_attr( $instance['title'] ); ?>" />
			</p>

			<p>
				<label for="<?php echo esc_attr( $this->get_field_id( 'group' ) ); ?>"><?php esc_html_e( 'Filter group', 'woocommerce-product-filters' ); ?></label>
				<select name="<?php echo esc_attr( $this->get_field_name( 'group' ) ); ?>" class="widefat">
					<?php foreach ( $groups as $group_id => $group_name ) : ?>
						<option value="<?php echo esc_attr( $group_id ); ?>" <?php selected( absint( $instance['group'] ), absint( $group_id ) ); ?>><?php echo esc_html( $group_name ); ?></option>
					<?php endforeach; ?>
				</select>
			</p>
		<?php
	}

	/**
	 * Update widget instance.
	 *
	 * @param array $new_instance
	 * @param array $old_instance
	 * @return array
	 */
	public function update( $new_instance, $old_instance ) {
		$instance          = $old_instance;
		$instance['title'] = sanitize_text_field( $new_instance['title'] );
		$instance['group'] = sanitize_text_field( $new_instance['group'] );
		return $instance;
	}

}
