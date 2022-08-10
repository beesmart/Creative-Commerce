<?php
/**
 * Snippet Name: Woo Tax Memberships
 * Version: 1.0.0
 * Description: Woo Tax Membership, adjusts system so all memberhip customers prices are shown excluding VAT
 * Dependency: WP Memberships
 *
 * @link              https://auracreativemedia.co.uk
 * @since             1.0.0
 * @package           Aura_Supercommerce
 * @requires          WP Memberships
 *
**/


if ( is_plugin_active( 'woocommerce-memberships/woocommerce-memberships.php' ) ) {

    function wtmem_force_woocommerce_prices_include_tax() {
        update_option('woocommerce_prices_include_tax', 'no');
    }

    function wtmem_tax_exculuding_memberships() {
        return get_option('woocommerce_exclude_tax_options');
    }


    function wtmem_get_active_membership_by_id($user_id = 0){
        
        if(empty($user_id))
            return;
        
        $args = array( 
            'status' => array( 'active', 'complimentary', 'pending' ),
        );  
        
        $active_memberships = wc_memberships_get_user_memberships( $user_id, $args );
        if ( ! empty( $active_memberships ) ) {
           return $active_memberships;
        }
        
        return;
    }

    add_action('option_woocommerce_prices_include_tax', 'option_woocommerce_prices_include_tax', 2, 10);

    function option_woocommerce_prices_include_tax($value, $option) {    
        $user_id = get_current_user_id();    
        if ( $user_id && !is_admin() ) {       
            $excluding_memberships = wtmem_tax_exculuding_memberships();        
            $active_memberships = wtmem_get_active_membership_by_id($user_id);
            if(!empty($excluding_memberships) && !empty($active_memberships) ) {            
                $is_excluding = false;
                foreach($active_memberships as $data) {
                    
                    if(in_array($data->plan_id, $excluding_memberships)) {
                        $is_excluding = true;
                    }
                    
                }
                
                if($is_excluding) {
                    $value = 'no';
                }
                
            }
            
        }
        
        return $value;
        
    }

    add_action('option_woocommerce_tax_display_shop', 'option_woocommerce_tax_display_shop', 2, 10);

    function option_woocommerce_tax_display_shop($value, $option){
        
        $user_id = get_current_user_id();
        
        if ( is_user_logged_in() && !is_admin() ) {
           
            $excluding_memberships = wtmem_tax_exculuding_memberships();
            
            $active_memberships = wtmem_get_active_membership_by_id($user_id);
            
            if(!empty($excluding_memberships) && !empty($active_memberships) ){
                
                $is_excluding = false;
                foreach($active_memberships as $data){
                    
                    if(in_array($data->plan_id, $excluding_memberships)) {
                        $is_excluding = true;
                    }
                    
                }
                
                if($is_excluding) {
                    $value = 'excl';
                }
                
            }
            
        }
        
        return $value;
        
    }


    add_action('option_woocommerce_tax_display_cart', 'option_woocommerce_tax_display_cart', 2, 10);

    function option_woocommerce_tax_display_cart($value, $option){
        
        $user_id = get_current_user_id();
        
        if ( !is_admin() ) {
           
            $excluding_memberships = wtmem_tax_exculuding_memberships();
            
            $active_memberships = wtmem_get_active_membership_by_id($user_id);
            
            if(!empty($excluding_memberships) && !empty($active_memberships) ) {
                $is_excluding = false;
                foreach($active_memberships as $data) {
                    if(in_array($data->plan_id, $excluding_memberships)) {
                        $is_excluding = true;
                    }
                }
                
                if($is_excluding) {
                    $value = 'excl';
                }
                    
                
            }
            
        }
        
        return $value;
        
    }


    add_filter( 'woocommerce_tax_settings', 'add_order_number_start_setting' );


    function add_order_number_start_setting( $settings ) {

      $updated_settings = array();

        $args = array(
            'posts_per_page'   => -1,
            'offset'           => 0,
            'orderby'          => 'post_title',
            'order'            => 'ASC',
            'post_type'        => 'wc_membership_plan',
            'post_status'      => 'publish',
            'suppress_filters' => true 
        );
        $membership_array = get_posts( $args );
        
        $memberships_arr = array();
        
        if(!empty($membership_array)){

           foreach($membership_array as $data){

               $memberships_arr[$data->ID] = $data->post_title;

           }

        }
        
      foreach ( $settings as $section ) {
        
        if ( isset( $section['id'] ) && 'tax_options' == $section['id'] &&

            isset( $section['type'] ) && 'sectionend' == $section['type'] ) {

            $updated_settings[] = array(
                'title'   => __( 'Exclude Membership Plans', 'woocommerce' ),
                'id'      => 'woocommerce_exclude_tax_options',
                'default' => 'excl',
                'type'    => 'multiselect',
                'class'   => 'wc-enhanced-select',
                'options' => $memberships_arr,
                'autoload'      => false,
                'desc'     => __( '<br>Choose which memberships these rules apply to', 'woocommerce' ),
            );

        }


        $updated_settings[] = $section;

      }

      return $updated_settings;

    }
}