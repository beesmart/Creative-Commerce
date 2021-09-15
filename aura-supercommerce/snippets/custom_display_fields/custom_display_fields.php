<?php

/**
 * Snippet Name: Custom Display Fields
 * Version: 1.0.0
 * Description: Setups some requested display fields for users, like 'Envelope' etc.
 *
 * @link              https://auracreativemedia.co.uk
 * @since             1.0.0
 * @package           Aura_Supercommerce
 *
**/



/**
 * Display the custom text field
 * @since 1.0.0
 * @see https://pluginrepublic.com/add-custom-fields-woocommerce-product/
 */

function aura_create_custom_field_wc() {
	 $args = array(
	 'id' => 'envelope_custom_field',
	 'label' => __( 'Envelope (Cards Only)', 'aura-supercommerce' ),
	 'class' => 'asc-custom-field',
	 'desc_tip' => true,
	 'description' => __( 'Enter the colour of the envelope here.', 'aura-supercommerce' ),
	 );

	 woocommerce_wp_text_input( $args );
}

add_action( 'woocommerce_product_options_general_product_data', 'aura_create_custom_field_wc' );




/**
 * Save the custom field
 * @since 1.0.0
 */
function aura_save_custom_field( $post_id ) {

	 $product = wc_get_product( $post_id );
	 $title = isset( $_POST['envelope_custom_field'] ) ? $_POST['envelope_custom_field'] : '';
	 $product->update_meta_data( 'envelope_custom_field', sanitize_text_field( $title ) );
	 $product->save();

}
add_action( 'woocommerce_process_product_meta', 'aura_save_custom_field' );




/**
 * Display custom field on the front end
 * @since 1.0.0
 */
function aura_display_custom_field() {

	 global $post;
	 // Check for the custom field value
	 $product = wc_get_product( $post->ID );
	 $title = $product->get_meta( 'envelope_custom_field' );

	 if( $title ) {
		 // Only display our field if we've got a value for the field title
		 printf(
		 '<div class="aura-custom-field-wrapper product-meta">Envelope: <span>%s</span></div>',
		 esc_html( $title )
		 );
	 }
}

add_action( 'woocommerce_before_add_to_cart_button', 'aura_display_custom_field' );


