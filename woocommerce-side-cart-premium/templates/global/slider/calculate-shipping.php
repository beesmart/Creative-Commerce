<?php
/**
 * Calculate Shipping
 *
 * This template can be overridden by copying it to yourtheme/templates/side-cart-woocommerce/global/slider/calculate-shipping.php.
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
	<?php _e( 'Calculate Shipping', 'side-cart-woocommerce' ); ?>
</div>


<?php

$show_shipping_calculator 	= in_array( 'shipping_calc' , xoo_wsc_helper()->get_general_option('scf-show') );
$calculator_text 			= '';
$packages 					= WC()->shipping()->get_packages();

ob_start();

if( !empty( $packages ) ){

	//Support for 1 package only
	$package = $packages[0];

	$chosen_method 				= isset( WC()->session->chosen_shipping_methods[ 0 ] ) ? WC()->session->chosen_shipping_methods[ 0 ] : '';
	$formatted_destination    	= WC()->countries->get_formatted_address( $package['destination'], ', ' );
	$has_calculated_shipping  	= ! empty( WC()->customer->has_calculated_shipping() );
	$available_methods 			= $package['rates'];
	$index 						= 0;

	?>

	<?php if ( $available_methods ) : ?>

		<div class="xoo-wsc-shipping-destination">
			<?php
			if ( $formatted_destination ) {
				printf( '<span>%1$s</span><span>%2$s</span>', __( 'Shipping to:', 'side-cart-woocommerce' ) ,esc_html( $formatted_destination ) );
				$calculator_text = esc_html__( 'Change address', 'woocommerce' );
			} else {
				echo wp_kses_post( apply_filters( 'woocommerce_shipping_estimate_html', __( 'Shipping options will be updated during checkout.', 'woocommerce' ) ) );
			}
			?>
		</div>

		<ul class="xoo-wsc-shipping-methods">
			<?php foreach ( $available_methods as $method ) : ?>
				<li>
					<?php
					if ( 1 < count( $available_methods ) ) {
						printf( '<label><input type="radio" name="xoo-wsc-shipping_method[%1$d]" data-index="%1$d" value="%2$s" class="xoo-wsc-shipping-method" %3$s />%4$s</label>', $index, esc_attr( $method->id ), checked( $method->id, $chosen_method, false ), wc_cart_totals_shipping_method_label( $method ) ); // WPCS: XSS ok.
					} else {
						printf( '<input type="hidden" name="xoo-wsc-shipping_method[%1$d]" data-index="%1$d" value="%2$s" class="xoo-wsc-shipping-method" />', $index, esc_attr( $method->id ) ); // WPCS: XSS ok.
					}

					do_action( 'woocommerce_after_shipping_rate', $method, $index );

					?>
				</li>
			<?php endforeach; ?>
		</ul>

	<?php

	elseif ( ! $has_calculated_shipping || ! $formatted_destination ) :
		if ( !$show_shipping_calculator ) {
			echo wp_kses_post( apply_filters( 'woocommerce_shipping_not_enabled_on_cart_html', __( 'Shipping costs are calculated during checkout.', 'woocommerce' ) ) );
		} else {
			echo wp_kses_post( apply_filters( 'woocommerce_shipping_may_be_available_html', __( 'Enter your address to view shipping options.', 'woocommerce' ) ) );
		}
	else :
		echo wp_kses_post( apply_filters( 'woocommerce_cart_no_shipping_available_html', sprintf( esc_html__( 'No shipping options were found for %s.', 'woocommerce' ) . ' ', '<strong>' . esc_html( $formatted_destination ) . '</strong>' ) ) );
		$calculator_text = esc_html__( 'Enter a different address', 'woocommerce' );
	endif;

}

?>

<?php

if ( $show_shipping_calculator ){
	woocommerce_shipping_calculator( $calculator_text );
}

?>

<?php printf( '<div class="xoo-wsc-sl-body">%s</div>', ob_get_clean() ) ?>