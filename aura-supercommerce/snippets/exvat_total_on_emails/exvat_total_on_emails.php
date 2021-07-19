<?php
/**
 * Snippet Name: EX. VAT emails on orders
 * Version: 1.0.0
 * Description: Shows the subtotal without VAT on (admin) new order emails.
 * 
 *
 * @link              https://auracreativemedia.co.uk
 * @since             1.0.0
 * @package           Aura_Supercommerce
 *
**/




add_filter( 'woocommerce_get_order_item_totals', 'insert_custom_line_order_item_totals', 10, 3 );

function insert_custom_line_order_item_totals( $total_rows, $order, $tax_display ){
	
    // Only on emails notifications
    if( is_wc_endpoint_url() ) return $total_rows; // Exit
	
	// Get the currency symbol
	$currency_symbol = get_woocommerce_currency_symbol( get_woocommerce_currency() );
    $tracking_label = 'Subtotal (Ex. VAT):'; // The tracking label name
    $tracking_value = $currency_symbol . $order->get_subtotal(); // Get the tracking value (custom field).

    if( empty($tracking_value) ) return $total_rows; // Exit

    $new_total_rows  = array(); // Initializing
	
	$total_rows['cart_subtotal']['label'] = __( 'Subtotal (Inc. VAT):', 'woocommerce');
	
    // Loop through total rows
    foreach( $total_rows as $key => $value ){
        if( 'cart_subtotal' == $key && ! empty($tracking_value) ) {
            $new_total_rows['tracking_parcel'] = array(
                'label' => $tracking_label,
                'value' => $tracking_value,
            );
        }
        $new_total_rows[$key] = $total_rows[$key];
    }
	
	

    return sizeof($new_total_rows) > 0 ? $new_total_rows : $total_rows;
}
