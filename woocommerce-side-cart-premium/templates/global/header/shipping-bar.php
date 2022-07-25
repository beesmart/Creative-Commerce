<?php
/**
 * Shipping Bar
 *
 * This template can be overridden by copying it to yourtheme/templates/side-cart-woocommerce/global/header/shipping-bar.php.
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

extract( Xoo_Wsc_Template_Args::shipping_bar() );

if( !$showBar || empty( $data ) ) return;

?>

<div class="xoo-wsc-ship-bar-cont">
	<span class="xoo-wsc-sb-txt"><?php echo $text; ?></span>
	<div class="xoo-wsc-sb-bar">
		<span style="width: <?php esc_attr_e( $data['fill_percentage'] ); ?>%"></span>
	</div>
</div>