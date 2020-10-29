<?php
if (isset($_REQUEST['export']) && $_REQUEST['export'] == 1) {

    wcrs_generate_csv_cor();
}

function wcrs_generate_csv_cor() {

    global $wpdb, $users_data;

    header('Content-type: application/csv');
    header('Content-Disposition: attachment; filename=Order Management Report - Comission-' . date("Y-m-d H:i:s") . '.csv');

    $inner_join = " LEFT JOIN {$wpdb->prefix}postmeta ON ({$wpdb->prefix}posts.ID = {$wpdb->prefix}postmeta.post_id AND {$wpdb->prefix}postmeta.meta_key = '_date_dispatched' ) LEFT JOIN {$wpdb->prefix}postmeta AS mt1 ON ( {$wpdb->prefix}posts.ID = mt1.post_id ) ";
    $inner_condition = " AND ( {$wpdb->prefix}postmeta.post_id IS NULL OR mt1.meta_key = '_date_dispatched' ) ";
    $order_by = '';

    if ($dateto != '' || $datefrom != '') {
        $start_date = date("Y-m-d", strtotime($datefrom));
        $end_date = date("Y-m-d", strtotime($dateto));

        if ($dateto != '' && $datefrom != '') {
            $inner_condition = " AND date_format(post_date,'%Y-%m-%d')  BETWEEN '" . $start_date . "' AND '" . $end_date . "' ";
        } elseif ($datefrom != '') {

            $inner_condition = " AND date_format(post_date,'%Y-%m-%d')  >= '" . $start_date . "' ) ) ";
        } elseif ($dateto != '') {

            $inner_condition = " AND date_format(post_date,'%Y-%m-%d')  <= '" . $end_date . "' ) ) ";
        }
    }
    $sql = "SELECT * FROM {$wpdb->prefix}posts " . $inner_join . " WHERE 1=1 $inner_condition AND `post_type` = 'shop_order' ";
    $sql .= " GROUP BY {$wpdb->prefix}posts.ID {$order_by} ";


    $result = $wpdb->get_results($sql, 'ARRAY_A');

    $report = array();

    if (!empty($result)) {
        foreach ($result as $order) {
            $temp = array();
            $agent_title = "";
            $user_firstname = "";
            $user_lastname = "";

            $_customer_user = get_post_meta($order['ID'], "_customer_user", true);
            $_agent = unserialize(get_post_meta($order['ID'], 'order_commission_detail')[0])['agent'];

            //User Detail
            $user = get_user_by('ID', $_customer_user);
            $user_firstname = $user->user_firstname;
            $user_lastname = $user->user_lastname;
            //User Detail
            //$agent = get_user_by('ID', $_agent);
			$agent=get_user_meta($_customer_user,'_agent_user',true);
            
            $agent = get_user_by('ID', $agent);
            $agent_name = !empty($agent->data->display_name) ? ucfirst($agent->data->display_name) : '---';
            $comission_detail = unserialize(get_post_meta($order['ID'], 'order_commission_detail')[0])['total'];
            $temp['order_number'] = "#" . $order['ID'];
            $temp['agent'] = $agent_name;
            $temp['comission'] = !empty($comission_detail) ? ''.$comission_detail : '0.00';
            $temp['comission_status'] = get_post_meta($order['ID'], 'commission_pay_status', true) == 0 ? " Pending" : 'Paid';
            $temp['customer'] = $user_firstname . ' ' . $user_lastname;
            //Get Comission INFO
            $comission_info = unserialize(get_post_meta($order['ID'], 'order_commission_detail')[0]);
            $htm = '';
            $comission = '';
            if (!empty($comission_info)) {
//                print_r($comission_info);
                for ($i = 0; $i < count($comission_info); $i++) {
                    if (!empty($comission_info[$i]['commission_rate'])) {
                        $comission = $comission_info[$i]['product_price'] * $comission_info[$i]['commission_rate'] / 100;
                        $comission= number_format($comission,2);
                        $htm .= $comission_info[$i]['product_name'] . " " . $comission_info[$i]['product_price'] . ") X  (" . $comission_info[$i]['commission_rate'] . "%) = $comission ";
                    }
                }
            } else {
                $htm = '---';
            }
            $order_date = explode(" ", $order['post_date']);
            $date = DateTime::createFromFormat("Y-m-d", $order_date[0]);
            $temp['order_date'] = $date->format("d F y");
            $temp['comission_detail'] = $htm;



            array_push($report, $temp);
        }
    }


    $columns = Order_Management_Completed::get_columns();
    unset($columns['cb']); //remove action column...

    $filter_fields = isset($_REQUEST['filter_fields']) ? $_REQUEST['filter_fields'] : '';

    if ($filter_fields != 'null' && filter_fields != '') {

        $filter_fields = explode(",", $filter_fields);

        foreach ($columns as $kys => $val) {

            if (!in_array($kys, $filter_fields)) {
                unset($columns[$kys]);
                echo $kys . "=";
            }
        }
    }

    //echo "<pre>"; print_r($columns); echo "</pre>";
    //exit;
    $fp = fopen('php://output', 'w');
    $header = array();
    if (!empty($columns)) {
        foreach ($columns as $key => $col) {

            $header[] = $col;
        }
    }



    ob_end_clean();
    fputcsv($fp, $header);

    if (!empty($report)) {

        foreach ($report as $key => $val) {

            $row = array();
            if (!empty($columns)) {

                foreach ($columns as $innerkey => $innerval) {

                    $row[] = $val[$innerkey];
                }
            }

            fputcsv($fp, $row);
        }
    }


    //echo "<pre>"; print_r($columns); echo "</pre>";
    //echo "<pre>"; print_r($row); echo "</pre>"; exit;

    exit();
}

if (!class_exists('WP_List_Table')) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Order_Management_Completed extends WP_List_Table {

    /** Class constructor */
    public function __construct() {

        parent::__construct([
            'singular' => __('Order Management', 'sp'), //singular name of the listed records
            'plural' => __('Order Management', 'sp'), //plural name of the listed records
            'ajax' => false //should this table support ajax?
        ]);
    }

    public static function wcrs_get_completed_orders($per_page = 10, $date_filter) {

        global $wpdb, $users_data;


        $inner_condition = '';
        $datefrom = isset($_REQUEST['datefrom']) ? $_REQUEST['datefrom'] : '';
        $dateto = isset($_REQUEST['dateto']) ? $_REQUEST['dateto'] : '';

        if ($dateto != '' || $datefrom != '') {
            $start_date = date("Y-m-d", strtotime($datefrom));
            $end_date = date("Y-m-d", strtotime($dateto));

            if ($dateto != '' && $datefrom != '') {
                $inner_condition = " AND date_format(post_date,'%Y-%m-%d')  BETWEEN '" . $start_date . "' AND '" . $end_date . "' ";
            } elseif ($datefrom != '') {

                $inner_condition = " AND date_format(post_date,'%Y-%m-%d')  >= '" . $start_date . "' ) ) ";
            } elseif ($dateto != '') {

                $inner_condition = " AND date_format(post_date,'%Y-%m-%d')  <= '" . $end_date . "' ) ) ";
            }
        }
        $sql = "SELECT * FROM {$wpdb->prefix}posts " . $inner_join . " WHERE 1=1 $inner_condition AND `post_type` = 'shop_order' ";
        $sql .= " GROUP BY {$wpdb->prefix}posts.ID {$order_by} LIMIT $per_page";

        if (isset($_REQUEST['paged'])) {
            $sql .= ' OFFSET ' . ($_REQUEST['paged'] - 1) * $per_page;
        } else {
            $sql .= ' OFFSET ' . 0;
        }
        $result = $wpdb->get_results($sql, 'ARRAY_A');
        $report = array();
        foreach ($result as $order) {
            $temp = array();


            $agent_title = "";
            $user_firstname = "";
            $user_lastname = "";

            $_customer_user = get_post_meta($order['ID'], "_customer_user", true);
            $_agent = get_post_meta($order['ID'], 'order_commission_detail');
			//print_r($_agent);
            //User Detail
            $user = get_user_by('ID', $_customer_user);
            $user_firstname = $user->user_firstname;
            $user_lastname = $user->user_lastname;
            //User Detail
            //echo $_customer_user;
            $agent=get_user_meta($_customer_user,'_agent_user',true);
            
            $agent = get_user_by('ID', $agent);
			
            $agent_name = !empty($agent->data->display_name) ? ucfirst($agent->data->display_name) : '---';
            $comission_detail = unserialize(get_post_meta($order['ID'], 'order_commission_detail')[0])['total'];
            $temp['order_number'] = "#" . $order['ID'];
            $temp['agent'] = $agent_name;
            $temp['comission'] = !empty($comission_detail) ? '£'.$comission_detail : '£0.00';
            $temp['comission_status'] = get_post_meta($order['ID'], 'commission_pay_status', true) == 0 ? " Pending" : 'Paid';
            $temp['customer'] = $user_firstname . ' ' . $user_lastname;
            //Get Comission INFO
            $comission_info = unserialize(get_post_meta($order['ID'], 'order_commission_detail')[0]);
            $htm = '';
            $comission = '';
            if (!empty($comission_info)) {
//                print_r($comission_info);
                for ($i = 0; $i < count($comission_info); $i++) {
                    if (!empty($comission_info[$i]['commission_rate'])) {
                        $comission = $comission_info[$i]['product_price'] * $comission_info[$i]['commission_rate'] / 100;
                        $comission= number_format($comission,2);
                        $htm .= "<div class='main_conti'> <b> " . $comission_info[$i]['product_name'] . " </b> (£" . $comission_info[$i]['product_price'] . ") X  (" . $comission_info[$i]['commission_rate'] . "%) = £$comission  </div>";
                    }
                }
            } else {
                $htm = '---';
            }
            $order_date = explode(" ", $order['post_date']);
            $date = DateTime::createFromFormat("Y-m-d", $order_date[0]);
            $temp['order_date'] = $date->format("d F y");
            $temp['comission_detail'] = $htm;



            array_push($report, $temp);
        }


        return $report;
    }

    public function get_sub_total($post_id) {
        $total = 0;
        $order = new WC_Order($post_id);
        $total = $order->get_total();
        return $total;
    }

    public static function record_count($date) {

        global $wpdb;

        $inner_join = '';
        $inner_condition = '';

        $datefrom = isset($_REQUEST['datefrom']) ? $_REQUEST['datefrom'] : '';
        $dateto = isset($_REQUEST['dateto']) ? $_REQUEST['dateto'] : '';

        if ($dateto != '' || $datefrom != '') {
            $start_date = date("Y-m-d", strtotime($datefrom));
            $end_date = date("Y-m-d", strtotime($dateto));

            if ($dateto != '' && $datefrom != '') {
                $inner_condition = " AND date_format(post_date,'%Y-%m-%d')  BETWEEN '" . $start_date . "' AND '" . $end_date . "' ";
            } elseif ($datefrom != '') {

                $inner_condition = " AND date_format(post_date,'%Y-%m-%d')  >= '" . $start_date . "' ) ) ";
            } elseif ($dateto != '') {

                $inner_condition = " AND date_format(post_date,'%Y-%m-%d')  <= '" . $end_date . "' ) ) ";
            }
        }


        $sql = "SELECT COUNT(*) FROM {$wpdb->prefix}posts  WHERE 1=1 " . $inner_condition . " AND `post_type` = 'shop_order' ";

        return $wpdb->get_var($sql);
    }

    public function no_items() {
        _e('No Data avaliable.', 'sp');
    }

    function column_name($item) {

        // create a nonce
        //$delete_nonce = wp_create_nonce( 'sp_delete_customer' );

        $title = '<strong>' . $item['name'] . '</strong>';

        /* $actions = [
          'delete' => sprintf( '<a href="?page=%s&action=%s&customer=%s&_wpnonce=%s">Delete</a>', esc_attr( $_REQUEST['page'] ), 'delete', absint( $item['ID'] ), $delete_nonce )
          ]; */

        //return $title . $this->row_actions( $actions );
        return $title;
    }

    public function column_default($item, $column_name) {
        switch ($column_name) {
            case 'order_number':
            case 'customer':
            case 'agent':

            case 'comission_detail':
            case 'comission':
            case 'comission_status':
            case 'order_date':
                return $item[$column_name];
            default:
                return print_r($item, true); //Show the whole array for troubleshooting purposes
        }
    }

    function get_columns() {
        $columns = array(
            'order_number' => 'Order Number',
            'customer' => 'Customer',
            'agent' => 'Agent',
            'comission_detail' => 'Comission Info',
            'comission' => 'Comission',
            'comission_status' => 'Comission Status',
            'order_date' => 'Order Date',
        );

        return $columns;
    }

    public function get_sortable_columns() {
        $sortable_columns = array(
            'date_dispatched' => array('date_dispatched', true),
        );

        return $sortable_columns;
    }

    /* public function get_bulk_actions() {
      $actions = [
      'bulk-delete' => 'Delete'
      ];
      return $actions;
      } */

    public function prepare_items() {
        if (isset($_REQUEST['m']) && $_REQUEST['m'] != '' && $_REQUEST['m'] != 0) {
            $date['m'] = substr($_REQUEST['m'], -2);
            $date['y'] = substr($_REQUEST['m'], 0, 4);
        } else {
            $date = '';
        }
        $columns = $this->get_columns();

        $hidden = array();
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array($columns, $hidden, $sortable);

        /** Process bulk action */
        $this->process_bulk_action();

        /* $per_page     = $this->get_items_per_page( 'customers_per_page', 5 );
          $current_page = $this->get_pagenum(); */
        $per_page = 10;
        $total_items = self::record_count($date);

        $this->set_pagination_args([
            'total_items' => $total_items, //WE have to calculate the total number of items
            'per_page' => $per_page //WE have to determine how many items to show on a page
        ]);


        $this->items = self::wcrs_get_completed_orders($per_page, $date);
    }

    public function process_bulk_action() {

        //Detect when a bulk action is being triggered...
        if ('delete' === $this->current_action()) {

            // In our file that handles the request, verify the nonce.
            $nonce = esc_attr($_REQUEST['_wpnonce']);

            if (!wp_verify_nonce($nonce, 'sp_delete_customer')) {
                die('Go get a life script kiddies');
            } else {
                self::delete_customer(absint($_GET['customer']));

                wp_redirect(esc_url(add_query_arg()));
                exit;
            }
        }

        // If the delete bulk action is triggered
        if (( isset($_POST['action']) && $_POST['action'] == 'bulk-delete' ) || ( isset($_POST['action2']) && $_POST['action2'] == 'bulk-delete' )
        ) {

            $delete_ids = esc_sql($_POST['bulk-delete']);

            // loop over the array of record IDs and delete them
            foreach ($delete_ids as $id) {
                self::delete_customer($id);
            }

            wp_redirect(esc_url(add_query_arg()));
            exit;
        }
    }

    function extra_tablenav($which) {
        global $wpdb, $wp, $wp_locale;
        $move_on_url = '&cat-filter=';
        if ($which == "top") {

            //$this->months_dropdown( 'shop_order' );

            $this->date_filters();
            ?>
            <script>
                jQuery(document).ready(function () {


                    jQuery('.co_report_export').click(function () {

                        var filter_fields = jQuery('#filter_fields').val();

                        var val = jQuery(this).val();
                        var url = '<?php echo admin_url('admin.php?' . $_SERVER['QUERY_STRING'] . '&export=1'); ?>';
                        document.location = url + '&filter_fields=' + filter_fields;
                    });
                });


            </script>
            <?php
        }
        if ($which == "bottom") {
            //The code that goes after the table is there
        }
    }

    public function date_filters() {

        $from = ( isset($_GET['datefrom']) && $_GET['datefrom'] ) ? $_GET['datefrom'] : '';
        $to = ( isset($_GET['dateto']) && $_GET['dateto'] ) ? $_GET['dateto'] : '';


        echo '<input autocomplete="off" style="width:135px;vertical-align: top;" type="text" name="datefrom" placeholder="Date From" value="' . $from . '" />
        <input autocomplete="off" style="width:135px;vertical-align: top;" type="text" name="dateto" placeholder="Date To" value="' . $to . '" />
        <input type="submit" name="filter_action" id="post-query-submit" class="button" value="Filter">
        
        <select style="width:135px;" multiple="multiple" id="filter_fields">';

        $columns = Order_Management_Completed::get_columns();
        unset($columns['cb']); //remove action column...

        if (!empty($columns)) {

            foreach ($columns as $key => $val) {

                echo '<option value="' . $key . '">' . $val . '</opton>';
            }
        }

        echo '</select>
        
        <a href="javascript:void(0)" class="co_report_export button" style="display: inline-block;margin: auto;width: 60px;">Export</a>
        
        <script>
        var $=jQuery;
		jQuery( function($) {
			var from = $(\'input[name="datefrom"]\'),
			    to = $(\'input[name="dateto"]\');
 
			$( \'input[name="datefrom"], input[name="dateto"]\' ).datepicker();
			// by default, the dates look like this "April 3, 2017" but you can use any strtotime()-acceptable date format
    			// to make it 2017-04-03, add this - datepicker({dateFormat : "yy-mm-dd"});
 
 
    			// the rest part of the script prevents from choosing incorrect date interval
    			from.on( \'change\', function() {
				to.datepicker( \'option\', \'minDate\', from.val() );
			});
 
			to.on( \'change\', function() {
				from.datepicker( \'option\', \'maxDate\', to.val() );
			});
            
            $(\'input[name="filter_action"]\').click(function(){
                
                $(\'input[name="_wpnonce"]\').remove();
                $(\'input[name="_wp_http_referer"]\').remove();
                $(\'input[name="paged"]\').remove();
            
            });
 
		});
		</script>';
    }

}

$obj = new Order_Management_Completed();
?>
<link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
<style>
    a.ui-state-default {
        text-align: center !important;
        padding: 4px 0px !important;
        /* width: auto; */
    }
    .main_conti {
        margin-bottom: 9px;
    }
</style>
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
<div class="wrap">
    <h2>Order Comission Report </h2>

    <div id="poststuff">
        <div id="post-body" class="metabox-holder columns-2">
            <div id="post-body-content">
                <div class="meta-box-sortables ui-sortable">

                    <form method="get" action="<?php echo admin_url('admin.php?page=order_management'); ?>">
                        <input type="hidden" name="page" value="order_management">
                        <?php
                        $obj->prepare_items();
                        $obj->display();
                        ?>
                    </form>

                </div>
            </div>
        </div>
        <br class="clear">
    </div>
</div>
<style>
    #poststuff{
        overflow-x: scroll;
    }
    #poststuff table {
        table-layout: auto !important;
    }
    #wpfooter {
        position: unset !important;
    }
    .tablenav{height: 90px!important;}
    #poststuff #post-body.columns-2 {
        margin-right: auto!important;
    }
</style>