<?php
/**
 * Side Cart Container
 *
 * This template can be overridden by copying it to yourtheme/templates/side-cart-woocommerce/xoo-wsc-container.php.
 *
 * HOWEVER, on occasion we will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen.
 * @see     https://docs.xootix.com/side-cart-woocommerce/
 * @version 3.0
 */


if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

extract( Xoo_Wsc_Template_Args::cart_container() );

?>



<div class="xoo-wsc-container">

	<?php if( $showBasket !== 'always_hide' ): ?>

	<div class="xoo-wsc-basket">

		<?php if( $showCount === "yes" ): ?>
			<span class="xoo-wsc-items-count"><?php echo xoo_wsc_cart()->get_cart_count() ?></span>
		<?php endif; ?>

		<?php if( $customBasketIcon ): ?>
			<span class="xoo-wsc-bki"><img src="<?php echo $customBasketIcon ?>"></span>
		<?php else: ?>
			<span class="xoo-wsc-bki <?php echo $basketIcon ?>"></span>
		<?php endif; ?>

		<?php do_action( 'xoo_wsc_basket_content' ); ?>

	</div>

<?php endif; ?>


	<div class="xoo-wsc-header">

		<?php do_action( 'xoo_wsc_header_start' ); ?>

		<?php xoo_wsc_helper()->get_template( 'xoo-wsc-header.php' ); ?>

		<?php do_action( 'xoo_wsc_header_end' ); ?>

	</div>


	<div class="xoo-wsc-body">

		<?php do_action( 'xoo_wsc_body_start' ); ?>

		<?php xoo_wsc_helper()->get_template( 'xoo-wsc-body.php' ); ?>

		<?php do_action( 'xoo_wsc_body_end' ); ?>

	</div>

	<div class="xoo-wsc-footer">

		<?php do_action( 'xoo_wsc_footer_start' ); ?>

		<?php xoo_wsc_helper()->get_template( 'xoo-wsc-footer.php' ); ?>

		<?php do_action( 'xoo_wsc_footer_end' ); ?>

	</div>

	<span class="xoo-wsc-loader"></span>

</div>