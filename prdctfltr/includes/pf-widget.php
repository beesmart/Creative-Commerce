<?php

	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	class prdctfltr extends WP_Widget {

		public static $settings;

		function __construct() {

			$widget_ops = array(
				'classname' => 'prdctfltr-widget',
				'description' => esc_html__( 'Product Filter widget version.', 'prdctfltr' )
			);

			parent::__construct(
				'prdctfltr',
				'+ Product Filter',
				$widget_ops
			);

		}

		function pf_title( $args ) {

			$args['before'] = '<div class="pf-help-title">' . self::$settings['before'] . $args['before'];
			$args['after'] = $args['after'] . self::$settings['after'] . '</div>';

			return $args;

		}

		function widget( $args, $instance ) {

			if ( class_exists( 'XforWC_Product_Filters_Frontend' ) ) {

				extract( $args, EXTR_SKIP );

				self::$settings = array(
					'before' => $before_title,
					'after' => $after_title
				);

				add_filter( 'prdctfltr_filter_title_args', array( $this, 'pf_title' ) );

				global $prdctfltr_global;

				$prdctfltr_global['widget_search'] = true;
				$prdctfltr_global['unique_id'] = wp_doing_ajax() && isset( $prdctfltr_global['unique_id'] ) ? $prdctfltr_global['unique_id'] : uniqid( 'prdctfltr-' );

				$widget_opt = array(
					'style' => ( isset( $instance['preset'] ) ? $instance['preset'] : '' ),
					'preset' => ( isset( $instance['template'] ) ? $instance['template'] : '' ),
					'disable_overrides' => ( isset( $instance['disable_overrides'] ) && $instance['disable_overrides'] == 'yes' ? 'yes' : '' ),
					'id' => ( isset( $instance['id'] ) ? $instance['id'] : '' ),
					'class' => ( isset( $instance['class'] ) ? $instance['class'] : '' ),
				);

				XforWC_Product_Filters_Frontend::$settings['widget'] = $widget_opt;
				$prdctfltr_global['widget_options'] = $widget_opt;
				$prdctfltr_global['preset'] = $widget_opt['preset'];
				$prdctfltr_global['disable_overrides'] = $widget_opt['disable_overrides'];

				if ( !wp_doing_ajax() && !isset( $prdctfltr_global['done_filters'] ) ) {
					XforWC_Product_Filters_Frontend::make_global( $_REQUEST, 'FALSE' );
				}

				echo wp_kses_post( $before_widget );

				if ( !empty( $widget_opt['id'] ) || !empty( $widget_opt['class'] ) ) {
					printf( '<div%s%s>', !empty( $widget_opt['id'] ) ? ' id="' . esc_attr( $widget_opt['id'] ) . '"' : '', !empty( $widget_opt['class'] ) ? ' class="' . esc_attr( $widget_opt['class'] ) . '"' : '' );
					include( XforWC_Product_Filters_Frontend::$dir . 'templates/product-filter.php' );
					echo '</div>';
				}
				else {
					include( XforWC_Product_Filters_Frontend::$dir . 'templates/product-filter.php' );
				}

				echo wp_kses_post( $after_widget );

				XforWC_Product_Filters_Frontend::$settings['widget'] = null;

				$prdctfltr_global['widget_search'] = null;
				$prdctfltr_global['widget_options'] = array();

				unset( $prdctfltr_global['unique_id'] );
				unset( $prdctfltr_global['preset'] );
				unset( $prdctfltr_global['disable_overrides'] );

				remove_filter( 'prdctfltr_filter_title_args', array( $this, 'pf_title' ) );

			}

		}

		function update( $new_instance, $old_instance ) {

			$instance = $old_instance;

			$instance['preset'] = $new_instance['preset'];
			$instance['template'] = $new_instance['template'];
			$instance['disable_overrides'] = isset( $new_instance['disable_overrides'] ) ? 'yes' : '';
			$instance['id'] = esc_attr( $new_instance['id'] );
			$instance['class'] = esc_attr( $new_instance['class'] );

			return $instance;

		}

		function form( $instance ) {

			$vars = array(
				'preset' => 'pf_inherit',
				'template' => '',
				'disable_overrides' => 'no',
				'id' => '',
				'class' => '',
			);

			$instance = wp_parse_args( (array) $instance, $vars );

			$preset = strip_tags($instance['preset']);
			$template = strip_tags($instance['template']);
			$disable_overrides = strip_tags($instance['disable_overrides']);
			$id = strip_tags($instance['id']);
			$class = strip_tags($instance['class']);
		?>
			<div>

				<p class="prdctfltr-box">
					<label for="<?php echo esc_attr( $this->get_field_id('template') ); ?>" class="prdctfltr-label"><?php esc_html_e('Preset', 'prdctfltr' ); ?></label>
					<select name="<?php echo esc_attr( $this->get_field_name('template') ); ?>" id="<?php echo esc_attr( $this->get_field_id('template') ); ?>" class="widefat">
						<option value="default"<?php echo ( $template == 'default' ? ' selected="selected"' : '' ); ?>><?php esc_html_e('Default', 'prdctfltr' ); ?></option>
					<?php
						$presets = Prdctfltr()->__get_presets();
						if ( is_array( $presets ) ) {
							foreach ( $presets as $k => $v ) {
							?>
								<option value="<?php echo esc_attr( $v['slug'] ); ?>"<?php echo ( $template == $v['slug'] ? ' selected="selected"' : '' ); ?>><?php echo esc_html( $v['name'] ); ?></option>
							<?php
							}
						}
					?>
					</select>
				</p>

				<p class="prdctfltr-box">
					<input type="checkbox" name="<?php echo esc_attr( $this->get_field_name('disable_overrides') ); ?>" id="<?php echo esc_attr( $this->get_field_id('disable_overrides') ); ?>" value="yes" <?php echo ( $disable_overrides == 'yes' ? ' checked' : '' ); ?> /> <label for="<?php echo esc_attr( $this->get_field_id('disable_overrides') ); ?>" class="prdctfltr-label"><?php esc_html_e( 'Disable presets manager settings', 'prdctfltr' ); ?></label>
				</p>

				<p class="prdctfltr-box">
					<label for="<?php echo esc_attr( $this->get_field_id('id') ); ?>" class="prdctfltr-label"><?php esc_html_e('ID', 'prdctfltr' ); ?></label>
					<input type="text" name="<?php echo esc_attr( $this->get_field_name('id') ); ?>" id="<?php echo esc_attr( $this->get_field_id('id') ); ?>" value="<?php echo esc_attr( $id ); ?>" class="widefat" />
				</p>

				<p class="prdctfltr-box">
					<label for="<?php echo esc_attr( $this->get_field_id('class') ); ?>" class="prdctfltr-label"><?php esc_html_e('Class', 'prdctfltr' ); ?></label>
					<input type="text" name="<?php echo esc_attr( $this->get_field_name('class') ); ?>" id="<?php echo esc_attr( $this->get_field_id('class') ); ?>" value="<?php echo esc_attr( $class ); ?>" class="widefat" />
				</p>

				<p class="prdctfltr-box">
					<label for="<?php echo esc_attr( $this->get_field_id('preset') ); ?>" class="prdctfltr-label"><?php esc_html_e('Style', 'prdctfltr' ); ?></label>
					<select name="<?php echo esc_attr( $this->get_field_name('preset') ); ?>" id="<?php echo esc_attr( $this->get_field_id('preset') ); ?>" class="widefat">
						<option value="pf_inherit"<?php echo ( $preset == 'pf_inherit' ? ' selected="selected"' : '' ); ?>><?php esc_html_e('Inherit from preset (Styles are set within presets)', 'prdctfltr' ); ?></option>
						<option value="pf_default_inline"<?php echo ( $preset == 'pf_default_inline' ? ' selected="selected"' : '' ); ?>><?php esc_html_e('Flat inline (DEPRECATED)', 'prdctfltr' ); ?></option>
						<option value="pf_default"<?php echo ( $preset == 'pf_default' ? ' selected="selected"' : '' ); ?>><?php esc_html_e('Flat block (DEPRECATED)', 'prdctfltr' ); ?></option>
						<option value="pf_default_select"<?php echo ( $preset == 'pf_default_select' ? ' selected="selected"' : '' ); ?>><?php esc_html_e('Flat select (DEPRECATED)', 'prdctfltr' ); ?></option>
					</select>
				</p>

			</div>

	<?php

		}

	}

	function prdctfltr_register_widgets() {
		register_widget( 'prdctfltr' );
	}
	add_action( 'widgets_init', 'prdctfltr_register_widgets' );
