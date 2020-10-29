<?php
/**
 * Plugin Name: Aura - Commission Report
 * Plugin URI: 
 * Description: Order Management Report, Agent Commission Report | Added on 31/05/19 as part of Advanced Features.
 * Version: 1.0.0
 * Author: Paul Taylor
 * License: GPL2
 */

//Admin Menu for report
function woo_add_report_menu() {
    add_menu_page(
            'Reports', 'Reports', 'manage_options', 'order_management', 'woo_show_order_comission', plugins_url('aura-custom-commission-report/yy.png'), 4
    );
    add_submenu_page(
            'order_management', 'Comission report', 'Order Comission report', 'manage_options', 'order_management', 'woo_show_order_comission'
    );
}

add_action('admin_menu', 'woo_add_report_menu');

function woo_show_order_comission() {
    require_once 'woo_show_order_comission.php';
}