<?php

/**
 * Snippet Name: Trade Price Column in Admin area
 * Version: 1.0.0
 * Description: Show Trade Price Column on Product page (wp-admin)
 * Dependency: WP Memberships
 *
 * @link              https://auracreativemedia.co.uk
 * @since             1.3.4
 * @package           Aura_Supercommerce
 * @requires          WP Memberships
 *
**/


if ( is_plugin_active( 'woocommerce-memberships/woocommerce-memberships.php' ) ) {

	$aura_sc_admin = new Aura_Supercommerce_Admin( AURA_DUAL_ENGINE_TITLE, AURA_DUAL_ENGINE_VERSION );
	$trade_status = $aura_sc_admin->get_trade_status();
	
	if($trade_status) :

	add_action( 'manage_posts_custom_column', 'populate_custom_trade_price_column' );
	function populate_custom_trade_price_column( $column_name ) {


			if( $column_name  == 'trade_price' ) {
				// if you suppose to display multiple brands, use foreach();
				$product_id = get_the_ID(); // taxonomy name
				$discount_rules = wc_memberships()->get_rules_instance()->get_product_purchasing_discount_rules( $product_id );

				$product = wc_get_product($product_id);
				$price = $product->get_price(); //will give raw price
					
				foreach ( $discount_rules as $discount_rule ) {

						// only get discounts that match the current membership plan & are active
						if ( $discount_rule->is_active() /*&& $this->id === $discount_rule->get_membership_plan_id()*/ ) {

							switch( $discount_rule->get_discount_type() ) {

								case 'percentage' :
									$member_discount = abs( $discount_rule->get_discount_amount() ) . '%';
								break;

								case 'amount' :
								default :
									$member_discount = abs( $discount_rule->get_discount_amount() );
								break;
							}
						}
					}

					if (!empty( $member_discount ) && !empty( $price ) && !wc_memberships()->get_member_discounts_instance()->is_product_excluded_from_member_discounts( $product_id )) {

						if( $product->is_type( 'bundle' ) ) {
						     $discount = (float) $member_discount/100; 
						     $applied = $discount * $price; 
						     $trade_price = $price - $applied; 
						     
						     echo '£' . number_format($trade_price, 2, '.', '');
						 } else { echo 'Retail'; }
					 } 

					 else { echo '-'; }

				
			}
	 
	}

	add_filter( 'manage_edit-product_columns', 'add_custom_trade_price_column', 20 );
	function add_custom_trade_price_column( $columns_array ) {
	 
		return array_slice( $columns_array, 0, 10, true )
		+ array( 'trade_price' => 'Trade Price' )
		+ array_slice( $columns_array, 10, NULL, true );

	}

	endif;
}