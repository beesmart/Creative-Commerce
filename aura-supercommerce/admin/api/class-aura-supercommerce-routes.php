<?php

/**
 *
 * Custom Routes for REST - Used by the PWA App
 *
 * @since      1.4.7
 *
 * @package           Aura_Supercommerce
 * @subpackage 	      Aura_Supercommerce/includes
 */


class Aura_Supercommerce_REST_Routes {


    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string    $plugin_name       The name of this plugin.
     * @param      string    $version    The version of this plugin.
     */


    public function __construct( $plugin_name, $version ) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->namespace     = 'cce';
        $this->membership_name = 'plans';
        $this->address_name = 'addresses';
        $this->status_name = 'status';
    }


    /**
     * Returns plugin for settings page
     *
     * @since       1.0.0
     * @return      string    $Filter_WP_Api       The name of this plugin
     * @link        https://github.com/ogulcan/filter-wp-api/blob/master/filter-wp-api/admin/class-filter-wp-api-admin.php
     */
    public function get_plugin() {
        return $this->Aura_Supercommerce;
    }


    public function register_routes() {

        register_rest_route( $this->namespace, '/' . $this->membership_name, array(
            array(
                'methods'   => 'GET',
                'callback'  => array( $this, 'cce_get_membership_plans' ),
                'permission_callback' => function () {
                    return (current_user_can( 'create_posts' ) || current_user_can( 'create_shop_orders' ) || current_user_can( 'edit_posts' ));
                }
            ),
  
        ) );
        register_rest_route( $this->namespace, '/' . $this->membership_name . '/(?P<id>[\d]+)', array(
            array(
                'methods'   => 'GET',
                'callback'  => array( $this, 'cce_get_member_plan' ),
                'permission_callback' => function () {
                    return (current_user_can( 'create_posts' ) || current_user_can( 'create_shop_orders' ) || current_user_can( 'edit_posts' ));
                }
            ),
    
        ) );
        
        register_rest_route( $this->namespace, '/' . $this->address_name . '/(?P<id>[\d]+)', array(
            array(
                'methods'   => 'GET',
                'callback'  => array( $this, 'cce_get_user_addresses' ),
                'permission_callback' => function () {
                    return (current_user_can( 'create_posts' ) || current_user_can( 'create_shop_orders' ) || current_user_can( 'edit_posts' ));
                }
            ),
            array(
                'methods'   => 'POST',
                'callback'  => array( $this, 'cce_set_user_addresses' ),
                'permission_callback' => function () {
                    return (current_user_can( 'create_posts' ) || current_user_can( 'create_shop_orders' ) || current_user_can( 'edit_posts' ));
                }
            ),

        ) );

        register_rest_route( $this->namespace, '/' . $this->status_name, array(
            array(
                'methods'   => 'GET',
                'callback'  => array( $this, 'cce_get_status_flags' ),
                'permission_callback' => '__return_true',
            ),
  
        ) );

    }

   /**
     * Check permissions for the posts.
     *
     * @param WP_REST_Request $request Current request.
    */

    public function get_aura_rest_permissions_check( $request ) {
        $testReq = $request->get_header('authorization');

       //wp_validate_auth_cookie($cookie, 'logged_in')
        
        if ( ! current_user_can( 'edit_posts' ) ) {
            return new WP_Error( 'rest_forbidden', esc_html__( 'You do not have the permissions required to view this resource.' ), array( 'status' => $this->authorization_status_code() ) );
        }
        return true;
    }
    

    /**
     *
     * @param WP_REST_Request $request Current request.
     */

    public function cce_get_membership_plans( $request ) {
        
        $wc_memberships_rules = get_option( 'wc_memberships_rules' );
        
        return rest_ensure_response($wc_memberships_rules);

    }
    
     /**
     *
     * @param WP_REST_Request $request Current request.
     */

    public function cce_get_member_plan( $request ) {
        
        $all_rules = get_option( 'wc_memberships_rules' );
        $rules = array();
        
        if ( empty( $all_rules ) ) {
            return rest_ensure_response( $rules );
        }
    
        for ($i = 0; $i < count($all_rules); $i++) {
            $rule = $all_rules[ $i ];

            if ($rule[ 'membership_plan_id' ] == $data[ 'id' ]) {
                array_push($rules, $rule);
            }
        }
        
        return $rules;
    }
    
    public function cce_get_user_addresses( $request ) {
        $wc_get_addresses = get_user_meta( $request[ 'id' ], 'wc_other_addresses', true );
        
        return rest_ensure_response($wc_get_addresses);
    }

    public function cce_set_user_addresses( $request ) {
        $body = $request->get_json_params();

        $wc_post_addresses = update_user_meta( $request[ 'id' ], 'wc_other_addresses', $body[ 'addresses' ] );
        
        return rest_ensure_response($wc_post_addresses);
    }


    # Echos out status flag titles for licence -supercommmerce- website to consume

    public function cce_get_status_flags( $request ) {
        
        $flags = get_option( 'aura_status_flags' );
        
        if ( !$flags ) {
            return rest_ensure_response( $flags );
        } else {
            return $flags;
        }
        
    }


    // Sets up the proper HTTP status code for authorization.
    public function authorization_status_code() {

        $status = 401;

        if ( is_user_logged_in() ) {
            $status = 403;
        }

        return $status;
    
    }


    /**
    * Function to register our new routes from the controller.
    *
    * @since       1.0.0
    * @return      string    $Filter_WP_Api       The name of this plugin
    */

    public function aura_register_my_rest_routes() {

        $aura_sc_routes = new Aura_Supercommerce_REST_Routes( $this->plugin_name, $this->version );
        $aura_sc_routes->register_routes();

    }

}


