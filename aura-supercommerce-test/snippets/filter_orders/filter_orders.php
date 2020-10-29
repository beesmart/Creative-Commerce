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
 * 
 * Allows admin to filter orders by Payment
 *
 * @link 			  http://skyverge.com/ - WC-Filter-Orders-By-Payment
 *
**/


// fire it up!
add_action( 'plugins_loaded', 'wc_filter_orders_by_payment' );


/** 
 * Main plugin class
 *
 * @since 1.0.0
 */
class WC_Filter_Orders_By_Payment {


	const VERSION = '1.0.0';

	/** @var WC_Filter_Orders_By_Payment single instance of this plugin */
	protected static $instance;

	/**
	 * Main plugin class constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		if ( is_admin() ) {

			// add bulk order filter for exported / non-exported orders
			add_action( 'restrict_manage_posts', array( $this, 'filter_orders_by_payment_method') , 20 );
			add_filter( 'request',               array( $this, 'filter_orders_by_payment_method_query' ) );		
		}
	}


	/** Plugin methods ***************************************/


	/**
	 * Add bulk filter for orders by payment method
	 *
	 * @since 1.0.0
	 */
	public function filter_orders_by_payment_method() {
		global $typenow;

		if ( 'shop_order' === $typenow ) {

			// get all payment methods, even inactive ones
			$gateways = WC()->payment_gateways->payment_gateways();

			?>
			<select name="_shop_order_payment_method" id="dropdown_shop_order_payment_method">
				<option value="">
					<?php esc_html_e( 'All Payment Methods', 'wc-filter-orders-by-payment' ); ?>
				</option>

				<?php foreach ( $gateways as $id => $gateway ) : ?>
				<option value="<?php echo esc_attr( $id ); ?>" <?php echo esc_attr( isset( $_GET['_shop_order_payment_method'] ) ? selected( $id, $_GET['_shop_order_payment_method'], false ) : '' ); ?>>
					<?php echo esc_html( $gateway->get_method_title() ); ?>
				</option>
				<?php endforeach; ?>
			</select>
			<?php
		}
	}


	/**
	 * Process bulk filter order payment method
	 *
	 * @since 1.0.0
	 *
	 * @param array $vars query vars without filtering
	 * @return array $vars query vars with (maybe) filtering
	 */
	public function filter_orders_by_payment_method_query( $vars ) {
		global $typenow;

		if ( 'shop_order' === $typenow && isset( $_GET['_shop_order_payment_method'] ) && ! empty( $_GET['_shop_order_payment_method'] ) ) {

			$vars['meta_key']   = '_payment_method';
			$vars['meta_value'] = wc_clean( $_GET['_shop_order_payment_method'] );
		}

		return $vars;
	}


	/** Helper methods ***************************************/


	/**
	 * Main WC_Filter_Orders_By_Payment Instance, ensures only one instance is/can be loaded
	 *
	 * @since 1.0.0
	 * @see wc_filter_orders_by_payment()
	 * @return WC_Filter_Orders_By_Payment
 	*/
	public static function instance() {

		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}


}

/**
 * Returns the One True Instance of WC_Filter_Orders_By_Payment
 *
 * @since 1.0.0
 * @return WC_Filter_Orders_By_Payment
 */
function wc_filter_orders_by_payment() {
    return WC_Filter_Orders_By_Payment::instance();
}





/**
 * 
 * Allows admin to filter orders by USer Role
 *
 * @link 			 https://jeroensormani.com/order-woocommerce-orders-per-user-role/﻿
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