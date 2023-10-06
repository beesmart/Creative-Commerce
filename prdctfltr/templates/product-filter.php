<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<?php
	if ( apply_filters( 'prdctfltr_show_filter', true ) === false ) {
		return false;
	}
?>

<?php do_action( 'prdctfltr_filter_hooks' ); ?>

<?php XforWC_Product_Filters_Frontend::get_filter_appearance(); ?>

<?php do_action( 'prdctfltr_filter_before' ); ?>

<div <?php XforWC_Product_Filters_Frontend::get_filter_tag_parameters(); ?>>

	<?php do_action( 'prdctfltr_filter_wrapper_before' ); ?>

	<form <?php echo XforWC_Product_Filters_Frontend::get_action_tag(); ?> class="prdctfltr_woocommerce_ordering" method="get">

		<?php do_action( 'prdctfltr_filter_form_before' ); ?>

		<div <?php XforWC_Product_Filters_Frontend::get_wrapper_tag_parameters(); ?>>

			<div class="prdctfltr_filter_inner">

			<?php

				foreach ( XforWC_Product_Filters_Frontend::$settings['instance']['filters'] as $filterElement ) :

					do_action( 'prdctfltr_before_filter' );

					XforWC_Product_Filters_Frontend::get_filter( $filterElement );

					do_action( 'prdctfltr_after_filter' );

				endforeach;

			?>

			</div>

		</div>

		<?php do_action( 'prdctfltr_filter_form_after' ); ?>

	</form>

	<?php do_action( 'prdctfltr_output_css' ); ?>

</div>

<?php
	do_action( 'prdctfltr_filter_after' );