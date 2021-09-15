<?php

/**
 * Snippet Name: Media Library - File Size Column
 * Version: 1.0.0
 * Description: Display file size as a sortable column in the media library. 
 *
 * @link              https://auracreativemedia.co.uk
 * @since             1.0.0
 * @package           Aura_Supercommerce
 *
**/

// See: https://cfxdesign.com/display-file-size-as-a-sortable-column-in-the-media-library/

add_filter( 'manage_media_columns', 'sk_media_columns_filesize' );
/**
 * Filter the Media list table columns to add a File Size column.
 *
 * @param array $posts_columns Existing array of columns displayed in the Media list table.
 * @return array Amended array of columns to be displayed in the Media list table.
 */
function sk_media_columns_filesize( $posts_columns ) {
	$posts_columns['filesize'] = __( 'File Size', 'my-theme-text-domain' );

	return $posts_columns;
}

add_action( 'manage_media_custom_column', 'sk_media_custom_column_filesize', 10, 2 );
/**
 * Display File Size custom column in the Media list table.
 *
 * @param string $column_name Name of the custom column.
 * @param int    $post_id Current Attachment ID.
 */
function sk_media_custom_column_filesize( $column_name, $post_id ) {
//	if ( 'filesize' !== $column_name ) {
	//	return;
	//}
	
//	$bytes = filesize( get_attached_file( $post_id ) );
	//    echo size_format($file_size, 2);
	//echo $bytes;
	  if('filesize' == $column_name) {
    if(!get_post_meta($post_id, 'filesize', true)) {
      $file      = get_attached_file($post_id);
      $file_size = filesize($file);
      update_post_meta($post_id, 'filesize', $file_size);
    } else {
      $file_size = get_post_meta($post_id, 'filesize', true);
    }
    echo size_format($file_size, 2);
  }
  return false;
}


// Make column sortable
function add_column_sortable_file_size($columns) {
  $columns['filesize'] = 'filesize';
  return $columns;
}

add_filter('manage_upload_sortable_columns', 'add_column_sortable_file_size');

// Column sorting logic (query modification)
function sortable_file_size_sorting_logic($query) {
  global $pagenow;
  if(is_admin() && 'upload.php' == $pagenow && $query->is_main_query() && !empty($_REQUEST['orderby']) && 'filesize' == $_REQUEST['orderby']) {
    $query->set('order', 'ASC');
    $query->set('orderby', 'meta_value_num');
    $query->set('meta_key', 'filesize');
    if('desc' == $_REQUEST['order']) {
      $query->set('order', 'DSC');
    }
  }
}
add_action('pre_get_posts', 'sortable_file_size_sorting_logic');