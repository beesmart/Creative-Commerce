<?php
/**
 * Side Cart Slider
 *
 * This template can be overridden by copying it to yourtheme/templates/side-cart-woocommerce/xoo-wsc-slider.php.
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

extract( Xoo_Wsc_Template_Args::slider() );

?>
<div class="xoo-wsc-slider">

	<?php if( $showNotifications ): ?>
		<?php xoo_wsc_cart()->print_notices_html('slider'); ?>
	<?php endif; ?>

	<?php if( $showShipping ): ?>

		<div class="xoo-wsc-sl-content xoo-wsc-sl-shipping" data-slider="shipping">

			<?php do_action( 'xoo_wsc_slider_shipping_start' ); ?>

			<?php xoo_wsc_helper()->get_template( 'global/slider/calculate-shipping.php' ); ?>

			<?php do_action( 'xoo_wsc_slider_shipping_end' ); ?>

		</div>

	<?php endif; ?>

	<?php if( $showCoupon ): ?>

		<div class="xoo-wsc-sl-content xoo-wsc-sl-coupon"  data-slider="coupon">

			<?php do_action( 'xoo_wsc_slider_coupon_start' ); ?>

			<?php xoo_wsc_helper()->get_template( 'global/slider/apply-coupon.php' ); ?>

			<?php do_action( 'xoo_wsc_slider_coupon_end' ); ?>

		</div>

	<?php endif; ?>

	<?php do_action( 'xoo_wsc_slider_end' ); ?>
	
	<span class="xoo-wsc-loader"></span>
	
</div>