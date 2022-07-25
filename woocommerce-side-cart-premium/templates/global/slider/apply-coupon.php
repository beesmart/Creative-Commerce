<?php
/**
 * Apply Coupon
 *
 * This template can be overridden by copying it to yourtheme/templates/side-cart-woocommerce/global/slider/apply-coupon.php.
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

?>

<div class="xoo-wsc-sl-heading">
	<span class="xoo-wsc-toggle-slider xoo-wsc-slider-close xoo-wsc-icon-arrow-thin-left"></span>
	<?php _e( 'Apply Coupon', 'side-cart-woocommerce' ); ?>
</div>

<div class="xoo-wsc-sl-body">
	
	<form class="xoo-wsc-sl-apply-coupon">
		<input type="text" name="xoo-wsc-slcf-input" placeholder="<?php _e( 'Enter Promo Code', 'side-cart-woocommerce' ); ?>">
		<button class="button btn" type="submit"><?php _e( 'Submit', 'side-cart-woocommerce' ); ?></button>
	</form>

	<?php if( !empty( WC()->cart->get_coupons() ) ): ?>
		<div class="xoo-wsc-sl-applied">
			<?php foreach ( WC()->cart->get_coupons() as $code => $coupon ): ?>
				<div>
					<span class="xoo-wsc-slc-saved"><?php  echo __( 'Saved', 'side-cart-woocommerce' ). ' '. wc_price( WC()->cart->get_coupon_discount_amount( $coupon->get_code(), WC()->cart->display_cart_ex_tax ) ) ?></span>
					<span class="xoo-wsc-slc-remove">
						<?php echo $code ?>
						<span class="xoo-wsc-remove-coupon" data-code="<?php echo $code ?>"><?php _e( '[Remove]', 'side-cart-woocommerce' ) ?></span>
					</span>
				</div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>


	<?php

	$listHTML 		= '<div class="xoo-wsc-clist-cont">%s</div>';;

	$sections = '';

	foreach (  xoo_wsc_cart()->get_coupons() as $section => $coupons ){

		if( empty( $coupons ) ) continue;

		$sectionContainer = '<div class="xoo-wsc-clist-section xoo-wsc-clist-section-%1$s">%2$s</div>';
		
		$label 	= sprintf( '<span class="xoo-wsc-clist-label">%s</span>', $section === "valid" ? __( 'Available Coupons', 'side-cart-woocommerce' ) : __( 'Unavailable Coupons', 'side-cart-woocommerce' ) );

		$rows = '';

		ob_start();

		?>

		<?php foreach ( $coupons as $coupon_data ): ?>

			<?php $coupon = $coupon_data['coupon']; ?>

			<div class="xoo-wsc-coupon-row">
				<span class="xoo-wsc-cr-code"><?php echo $coupon->get_code(); ?></span>
				<span class="xoo-wsc-cr-off"><?php printf( __( 'Get %s off', 'side-cart-woocommerce' ), $coupon_data['off_value'] )  ?></span>
				<span class="xoo-wsc-cr-desc"><?php echo $coupon->get_description() ?></span>
				<?php if( $section === 'valid' ): ?>
					<button class="xoo-wsc-coupon-apply-btn button btn" value="<?php echo $coupon->get_code() ?>"><?php _e( 'Apply Coupon', 'side-cart-woocommerce' ); ?></button>
				<?php endif; ?>
			</div>

		<?php endforeach; ?>

		<?php

		$rows .= ob_get_clean();

		$sections .= sprintf( $sectionContainer, $section, $label.$rows );

	}

	printf( $listHTML, $sections );

	?>

</div>