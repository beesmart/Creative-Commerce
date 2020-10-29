<?php
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class UserList extends WP_List_Table {

	/** Class constructor */
	public function __construct() {
		//session_start();
		
		parent::__construct( [
			'singular' => __( 'All List', GEOMTV_TEXT_DOMAIN ), //singular name of the listed records
			'plural'   => __( 'All List', GEOMTV_TEXT_DOMAIN ), //plural name of the listed records
			'ajax'     => false //should this table support ajax?

		] );

	}
    
    
   /* public static function record_count() {
      global $wpdb;
        $table_name = $wpdb->prefix . "geo_perks";
        
        $query = "SELECT * FROM $table_name";
        
        if(isset($_POST['s']) && $_POST['s'] != '') {
            $query .= ' WHERE name LIKE \'%'.$_POST['s'].'%\'';   
        }
        $result = $wpdb->get_results($query);
        
      return count($result);
    }*/
    
    public function no_items() {
      _e( 'No Data avaliable.', GEOMTV_TEXT_DOMAIN );
    }
    
    function column_name( $item ) {

      // create a nonce
      //$delete_nonce = wp_create_nonce( 'sp_delete_customer' );

      $title = '<strong>' . $item['name'] . '</strong>';

      /*$actions = [
        'delete' => sprintf( '<a href="?page=%s&action=%s&customer=%s&_wpnonce=%s">Delete</a>', esc_attr( $_REQUEST['page'] ), 'delete', absint( $item['ID'] ), $delete_nonce )
      ];*/

      //return $title . $this->row_actions( $actions );
      return $title;
    }


    public function column_default( $item, $column_name ) {
      switch ( $column_name ) {
		  case 'user_id':
          $ID = $item['ID'];
		  
           echo '<a href="'.get_edit_user_link($item['ID']).'" target="_blank">#'.$ID.'</a>';
        break;   
        case 'user_name':
          $user_name = $item['user_login']; 
           return $user_name;
        break;      
        case 'email':
          $user_email = $item['user_email']; 
           return $user_email;
        break;
        case 'display_name':     
			$display_name = $item['display_name']; 
           return $display_name;
		case 'registered_date':     
       $user_registered = $item['user_registered']; 
	   return $user_registered;
	   
	   case 'nearest_stockist':
			$check_address=aslc_check_address();
			$latitude = get_user_meta($item['ID'], $check_address . '_latitude', true);
			$longitude = get_user_meta($item['ID'], $check_address . '_longitude', true);
			$nearest_stockist =get_nearest_stockist($latitude,$longitude);
			return $nearest_stockist;
        break;
		case 'last_order':
			$last_order = get_user_meta($item['ID'], 'user_last_order_date', true);
			$last_order_date='';
			if(!empty($last_order)){
            
           $date2=date('Y-m-d');
				$date1=$last_order;
				$datetime1 = date_create($date1);
				$datetime2 = date_create($date2);
				$interval = date_diff($datetime1, $datetime2);
				$last_order_month= $interval->format('%m');
				$last_order_year= $interval->format('%y');
                $last_order_month=$last_order_month+($last_order_year*12);
                if($last_order_month >0)
				{
					$last_order_date=$last_order_month.' month';
				}
				else{
					$last_order_date='Less than a month';
				}
                
				/*if($last_order_month >0 && $last_order_month<=12)
				{
					$last_order_date=$last_order_month.' month';
				}
				else if($last_order_year >0){
					$last_order_date=$last_order_year.' year';
				}
				else{
					$last_order_date='Less than a month';
				}*/
            
            }
            return $last_order_date;
        break;
		
		case 'status':
			$ID = $item['ID'];
		  
           echo '<a onclick="get_user_active('.$item['ID'].')" style="cursor:pointer;">Approve</a>';
           
        break;
        default:
          return print_r( $item, true ); //Show the whole array for troubleshooting purposes
      }
    }
    
    function column_cb( $item ) {
      /*return sprintf(
        '<input type="checkbox" name="bulk-delete[]" value="%s" />', $item['ID']
      );*/
    }

    function get_columns() {
          $columns = array( 
            'user_id'        	=> 'User Id',
            'user_name'      	=> 'User Name',
            'email'			  	=> 'Email Address',
            'display_name'      => 'Display Name',           
            'registered_date'   => 'Registered Date',
            'nearest_stockist'  => 'Nearest Stockist',
            'last_order'        => 'Last Order',
            'status'        => 'Status'
          );

      return $columns;
    }
    
    public function get_sortable_columns() {
      $sortable_columns = array(
      //  'order_date' => array( 'post_date', true ),
      );

      return $sortable_columns;
    }
    
    public function get_bulk_actions() {
      $actions = [
        //'bulk-delete' => 'Delete'
      ];
      return $actions;
    }

    public function prepare_items() {

        $columns = $this->get_columns();

        $hidden = array();
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array($columns, $hidden, $sortable);    

        /** Process bulk action */
        $this->process_bulk_action();

        /*$per_page     = $this->get_items_per_page( 'customers_per_page', 5 ); */
        $current_page = $this->get_pagenum();
        if(isset($_REQUEST['per_page']) && !empty($_REQUEST['per_page'])){ $per_page     = $_REQUEST['per_page']; } else{
			$per_page =25;
		}
		if(isset($_REQUEST['s'])){
        $search=$_REQUEST['s'];
		}
		else{
			$search='';
		}
		
        $args = array('limit'=> $per_page, 'offset' => 0,'page'=>$current_page,'search'=>$search);
        if(isset($_REQUEST['id']) && isset($_REQUEST['status'])){
        $user_id=$_REQUEST['id'];
		$status=$_REQUEST['status'];
		user_activate($user_id,$status);
		}
		$user_list =get_user_list($args);
        $total_items = $user_list['total'];
        $this->set_pagination_args( array(
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil($total_items / $per_page) 
        ) );
        
        $this->items = $user_list['waiting_user'];       
    }  
}

$obj = new UserList();

?>
<h1><?php echo __('Waiting user list', GEOMTV_TEXT_DOMAIN);?></h1>
<?php if(isset($_SESSION['message_active'])){ ?>
<div class="updated">
<p>
 <?php echo $_SESSION['message_active']; unset($_SESSION['message_active']); ?>
</p>
</div>
<?php }  ?>
<div class="wrap">
    <div id="poststuff">			
        <form method="get" id="waiting_form">
	<input type="hidden" name="page" value="<?php echo $_REQUEST['page']; ?>" />
           <select name="per_page" id="per_page" >
		   <option <?php if(isset($_REQUEST['per_page']) && $_REQUEST['per_page'] == 25){ echo "selected"; } ?> value="25">25</option>
		   <option <?php if(isset($_REQUEST['per_page']) && $_REQUEST['per_page'] == 50){ echo "selected"; } ?> value="50">50</option>
		   <option <?php if(isset($_REQUEST['per_page']) && $_REQUEST['per_page'] == 100){ echo "selected"; } ?> value="100">100</option>
		   </select> 
           <?php
			
            $obj->prepare_items();
            $obj->search_box('Search', 'search');
            $obj->display(); ?>
        </form>					
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
</style>
 <script type="text/javascript">
    jQuery(document).ready(function(){
       // jQuery(".chzn-select").chosen();
	  
		//location.reload();
        jQuery("#per_page").change(function(){ 
			jQuery("#waiting_form").submit();
		});
    });
	function get_user_active(id){
		if (confirm('Do you want to approve this user?')) {
			var url='users.php?page=user_waiting&id='+id+'&status=active';
		location.href="<?php echo admin_url()  ?>"+url;
	} 
	}
	
</script>