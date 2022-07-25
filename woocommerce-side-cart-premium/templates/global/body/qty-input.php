<?php
/**
 * Quantity Input
 *
 * This template can be overridden by copying it to yourtheme/templates/side-cart-woocommerce/global/body/qty-input.php.
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

<div class="xoo-wsc-qty-box xoo-wsc-qtb-<?php echo $qtyDesign ?>">

	<?php do_action( 'xoo_wsc_before_quantity_input_field' ); ?>

	<span class="xoo-wsc-minus xoo-wsc-chng">-</span>

	<input
		type="number"
		class="<?php echo esc_attr( join( ' ', (array) $wsc_classes ) ); ?>"
		step="<?php echo esc_attr( $step ); ?>"
		min="<?php echo esc_attr( $min_value ); ?>"
		max="<?php echo esc_attr( 0 < $max_value ? $max_value : '' ); ?>"
		value="<?php echo esc_attr( $quantity ); ?>"
		placeholder="<?php echo esc_attr( $placeholder ); ?>"
		inputmode="<?php echo esc_attr( $inputmode ); ?>" />

	<?php do_action( 'xoo_wsc_after_quantity_input_field' ); ?>

	<span class="xoo-wsc-plus xoo-wsc-chng">+</span>

</div>