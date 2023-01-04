<?php

/**
 * Snippet Name: Filter WooCommerce Orders
 * Version: 1.0.0
 * Description: Allows admin to filter orders by Payment and Role
 *
 * @link              https://auracreativemedia.co.uk
 * @since             1.0.0
 * @package           Aura_Supercommerce
 * 
 *
**/


	/**
	 * Add bulk filter for orders by payment method
	 *
	 * @since 1.0.0
	 */
    function add_filter_by_payment_method_orders() {
        global $typenow;
        if ( 'shop_order' === $typenow ) {
            // get all payment methods
            $gateways = WC()->payment_gateways->get_available_payment_gateways();
            ?>
            <select name="_shop_order_payment_method" id="dropdown_shop_order_payment_method">
                <option value=""><?php esc_html_e( 'All Payment Methods' ); ?></option>
                <?php foreach ( $gateways as $id => $gateway ) : ?>
                <option value="<?php echo esc_attr( $id ); ?>" <?php echo esc_attr( isset( $_GET['_shop_order_payment_method'] ) ? selected( $id, $_GET['_shop_order_payment_method'], false ) : '' ); ?>>
                    <?php echo esc_html( $gateway->get_method_title() ); ?>
                </option>
                <?php endforeach; ?>
            </select>
            <?php
        }
    }
    add_action( 'restrict_manage_posts', 'add_filter_by_payment_method_orders', 99 );


/**
	 * Process bulk filter order payment method
	 *
	 * @since 1.0.0
	 *
	 * @param array $vars query vars without filtering
	 * @return array $vars query vars with (maybe) filtering
	 */
	 function filter_orders_by_payment_method_query( $vars ) {
		global $typenow;

		if ( 'shop_order' === $typenow && isset( $_GET['_shop_order_payment_method'] ) && ! empty( $_GET['_shop_order_payment_method'] ) ) {

			$vars['meta_key']   = '_payment_method';
			$vars['meta_value'] = wc_clean( $_GET['_shop_order_payment_method'] );
		}

		return $vars;
	}
    add_filter( 'request', 'filter_orders_by_payment_method_query', 99 );







/**
 * 
 * Allows admin to filter orders by USer Role
 *
 * @link https://jeroensormani.com/order-woocommerce-orders-per-user-role/
 *
**/


add_action( 'restrict_manage_posts', 'shop_order_user_role_filter' );
function shop_order_user_role_filter() {

	global $typenow, $wp_query;

	if ( in_array( $typenow, wc_get_order_types( 'order-meta-boxes' ) ) ) :
		$user_role	= '';

		// Get all user roles
		$user_roles = array();
		foreach ( get_editable_roles() as $key => $values ) :
			$user_roles[ $key ] = $values['name'];
		endforeach;

		// Set a selected user role
		if ( ! empty( $_GET['_user_role'] ) ) {
			$user_role	= sanitize_text_field( $_GET['_user_role'] );
		}

		// Display drop down
		?><select name='_user_role'>
			<option value=''><?php _e( 'Select a user role', 'woocommerce' ); ?></option><?php
			foreach ( $user_roles as $key => $value ) :
				?><option <?php selected( $user_role, $key ); ?> value='<?php echo $key; ?>'><?php echo $value; ?></option><?php
			endforeach;
		?></select><?php
	endif;
}


add_filter( 'pre_get_posts', 'shop_order_user_role_posts_where' );
function shop_order_user_role_posts_where( $query ) {

	if ( ! $query->is_main_query() || ! isset( $_GET['_user_role'] ) ) {
		return;
	}

	$ids    = get_users( array( 'role' => sanitize_text_field( $_GET['_user_role'] ), 'fields' => 'ID' ) );
	$ids    = array_map( 'absint', $ids );

	$query->set( 'meta_query', array(
		array(
			'key' => '_customer_user',
			'compare' => 'IN',
			'value' => $ids,
		)
	) );

	if ( empty( $ids ) ) {
		$query->set( 'posts_per_page', 0 );
	}

}