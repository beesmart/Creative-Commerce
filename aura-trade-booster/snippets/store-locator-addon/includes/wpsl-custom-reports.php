<?php

/* -------------------------- */
/* -------------------------- */
// CRON START


register_deactivation_hook( __FILE__, 'deactivate_notifier' );

function deactivate_notifier() {
   $timestamp = wp_next_scheduled( 'cc_monthly_stockist_report' );
   wp_unschedule_event( $timestamp, 'cc_monthly_stockist_report' );
}

 // Set the cron job for the monthly cron
if( ! wp_next_scheduled ( 'cc_monthly_stockist_report' ) ) {
   // This will trigger an action that will fire the "monthly_cron" action on the last day of each month at 4:00 am UTC
   wp_schedule_event( time(), 'daily', 'cc_monthly_stockist_report');
}

add_action( 'cc_monthly_stockist_report', 'maybe_run_monthly_cron' );

// Check if we need to fire the monthly cron action "monthly_cron"
function maybe_run_monthly_cron(){
    // $now = strtotime();
    // $this_day = date( 'j', $now );
    // $days_this_month = date( 't', $now );
    // if( $this_day == $days_this_month ){
    //     do_action( 'cc_monthly_stockist_callback' );
    // }
    $date = date('d');
      if ('01' == $date) {
        do_action( 'cc_monthly_stockist_callback' );
    }
}

add_action( 'cc_monthly_stockist_callback', 'email_monthly_stockist_report' );


// CRON END
/* -------------------------- */
/* -------------------------- */



add_action( 'admin_menu', 'submenu_reports_store_locator' );

function submenu_reports_store_locator(){
    add_submenu_page( 'edit.php?post_type=wpsl_stores', 'Monthly Report', 'Email Report', 'manage_options', 'wpsl_report', 'wpsl_reports_page_content', 1 );
}


function get_date_obj($date_ref){
    $date_obj = new DateTime($date_ref);
    $date_modified = $date_obj->modify("-" . ($date_obj->format('j')-1) . " days");

    return $date_modified;
}



function user_list_data_by_date($user, $date_start, $date_end){

    $activity = get_user_meta( $user->ID, 'user_last_order_date', true );
    $last_order_date = new DateTime($activity);

    $content = "<tr>";

    if ( $date_start <= $last_order_date && $date_end >= $last_order_date):
        
        $content .= "<td><a href='" . get_edit_user_link($user->ID) . "'>" . get_userdata($user->ID)->user_login . "</a></td>";
        $content .= "<td>" . $last_order_date->format('jS F Y') . "</td>";

           $last_order_id = get_user_meta( $user->ID, 'last_order_id', true );
           $order = wc_get_order( $last_order_id );

           if ( $order ) {
             
              $content .= "<td><p><a href='" . $order->get_edit_order_url() . "'>#" .  $last_order_id . "</a> - " .  $order->get_formatted_order_total() . "</p></td>";

           }
        
    endif;

    $content .= "</tr>";

    return $content;
}


function wpsl_reports_page_content (){

    $exist_report_mailto = get_option( 'wpsl_auto_report_mailto' );
    $exist_report_from = get_option( 'wpsl_auto_report_from' );

    echo '<br><form action="" method="post">';
    echo '<h1>Stockists Without Orders - Monthly Email Reports</h1>';
    echo '<p>Choose where to send the monthly reports (last day of the month)</p>';
    echo '<table width="500" style="max-width: 500px;" class="form-table"><tbody><tr><th><label for="report_mailto">Email Monthly Report to:</label></th><td>
    <input type="email" id="email" value="' . $exist_report_mailto . '" name="report_mailto" class="regular-text ltr" /></td></tr>';
    echo '<tr><th><label for="report_from">FROM Address:</label></th><td>
    <input type="email" id="email" value="' . $exist_report_from . '" name="report_from" class="regular-text ltr" /><p class="description">If you dont know what this is, use the same address you send the report to.</p></td></tr><tr><td>';
     submit_button('Click To Send Email', 'small');
    echo '</td></tr><input type="hidden" value="1" name="report_button" />';
   
    echo '</tbody></table></form><br><hr>';


    echo stockists_wo_recent_order();

    // Check whether the button has been pressed AND also check the nonce
    if (isset($_POST['report_button'])) {
      // the button has been pressed AND we've passed the security check
      $id_report = $_POST['report_button'];
      $mailto_report = $_POST['report_mailto'];
      $from_report = $_POST['report_from'];

      report_button_action( $id_report, $mailto_report, $from_report);

    }


}

function stockists_wo_recent_order(){

    $six_months_prev = get_date_obj('-6 months');
    $twelve_months_prev = get_date_obj('-12 months');
    $prehistory_date = (new DateTime())->setTimestamp(0);

    $users = get_users(); // get array of WP_User objects

    $content = '<h2>6 Months - With No Order</h2><hr>';

    $content .= '<table class="widefat fixed" cellspacing="0"><thead><tr><th>User</th><th>Last Order - Date</th><th>Last Order - Value</th></tr></thead><tbody>';

    foreach ( $users as $user ) {

      $content .= user_list_data_by_date( $user, $twelve_months_prev, $six_months_prev);

    }

    $content .= '</tbody></table>';

    $content .= "<br><br><hr><h2>12 Months - With No Order</h2><hr>";

    $content .= '<table class="widefat fixed" cellspacing="0"><thead><tr><th>User</th><th>Last Order - Date</th><th>Last Order - Value</th></tr></thead><tbody>';

    foreach ( $users as $user ) {

      $content .= user_list_data_by_date( $user, $prehistory_date, $twelve_months_prev);

    }

    $content .= '</table>';

    return $content;

}

function email_monthly_stockist_report(){

    $email_mailto_report = get_option( 'wpsl_auto_report_mailto' );
    $email_from_report = get_option( 'wpsl_auto_report_from' );

    // auto cron function

    $headers =  array('Content-Type: text/html; charset=UTF-8', 'From:' . $from_report);
    $subject = 'Stockist Email Report';
    $body = '<html>
                <head>

                </head>
                 <body style="padding: 20px;">' . stockists_wo_recent_order() . '
                </body>
            </html>';

    wp_mail( $email_mailto_report, $subject, $body, $headers );
    
}


function report_button_action( $id_report, $mailto_report, $from_report ){

  update_option( 'wpsl_auto_report_mailto', $mailto_report );
  update_option( 'wpsl_auto_report_from', $from_report );


  $headers = array('Content-Type: text/html; charset=UTF-8', 'From:' . $from_report);
  $subject = 'Monthly Stockist Report';
  $body = '<head>
              <style>
                body {
                  width: 100% !important;
                  min-width: 100%;
                  -webkit-text-size-adjust: 100%;
                  -ms-text-size-adjust: 100%;
                  margin: 0;
                  Margin: 0;
                  padding: 0;
                  -moz-box-sizing: border-box;
                  -webkit-box-sizing: border-box;
                  box-sizing: border-box; }
                  table {
                    border: 1px solid #bfbfbf;
                    margin-bottom: 45px;
                  }
                  thead {
                    background: #e0f9ff;
                  }
                  thead th {
                    padding: 9px 25px;
                    border-left: 1px solid #6b6b6b;
                    border-bottom: 1px solid #6b6b6b;
                  }
                  tbody td {
                    padding: 9px 15px;
                    border-left: 1px solid #6b6b6b;
                  }


              </style>
          </head>
              <body style="padding: 20px;"><h1>Stockist Monthly Report - ' . date('F, Y') . '</h1><hr>
              ' . stockists_wo_recent_order() . '
              </body>';

  wp_mail( $mailto_report, $subject, $body, $headers );
  

 wp_redirect($_SERVER['HTTP_REFERER']);

 // echo '<div id="message" class="updated fade"><p>' .'The Deposit Email for #'. $id_report  .' was sent to: ' . $mailto_report . '. Please refresh the page.</p></div>';

}  

?>