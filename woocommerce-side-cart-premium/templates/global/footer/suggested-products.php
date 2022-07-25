<?php
/**
 * Suggested Products
 *
 * This template can be overridden by copying it to yourtheme/templates/side-cart-woocommerce/global/footer/suggested-products.php.
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

extract( Xoo_Wsc_Template_Args::suggested_products() );

if( $disable ) return;


$container = '<div class="xoo-wsc-sp-container xoo-wsc-sp-'.$style.'"><span class="xoo-wsc-sp-heading">'.$heading.'</span><ul class="xoo-wsc-sp-slider">%s</ul></div>';

ob_start();

while ( $products->have_posts() ) : $products->the_post();

	global $product;

	$product->is_wsc_sp = "yes";

	$product_permalink 	= $product->is_visible() ? $product->get_permalink() : '';
	$thumbnail 			= apply_filters( 'xoo_wsc_suggested_product_thumbnail', $product->get_image(), $product );
	$thumbnail 			= $product_permalink && $showPLink ? sprintf( '<a href="%s">%s</a>', esc_url( $product_permalink ), $thumbnail ) : $thumbnail;
	$product_name 		= $product_permalink && $showPLink ? sprintf( '<a href="%s">%s</a>', $product_permalink, $product->get_name() ) : $product->get_name();
	$product_price 		= apply_filters( 'xoo_wsc_suggested_product_price', wc_price( $product->get_price() ), $product );
?>

<li class="xoo-wsc-sp-product">

	<div class="xoo-wsc-sp-left-col">
		<?php if( $showImage ) echo $thumbnail ?>
	</div>

	<div class="xoo-wsc-sp-right-col">

		<?php do_action( 'xoo_wsc_sp_start', $product ); ?>

		<div class="xoo-wsc-sp-rc-top">

			<?php if( $showTitle ): ?>
				<span class="xoo-wsc-sp-title"><?php echo $product_name; ?></span>
			<?php endif; ?>

		</div>

		<div class="xoo-wsc-sp-rc-bottom">

			

			<?php if( $showPrice ): ?>
				<span class="xoo-wsc-sp-price"><?php echo $product_price; ?></span>
			<?php endif; ?>

			<?php if( $showATC ): ?>
				<span class="xoo-wsc-sp-atc"><?php woocommerce_template_loop_add_to_cart( array( 'is_xoo_wsc_sp' => 'yes' ) ) ?></span>
			<?php endif; ?>

		</div>


		<?php do_action( 'xoo_wsc_sp_end', $product ); ?>

	</div>

	
</li>

<?php endwhile; ?>

<?php wp_reset_postdata(); ?>

<?php printf( $container, ob_get_clean() ); ?>