<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * When populating this file, consider the following flow
 * of control:
 *
 * - This method should be static
 * - Check if the $_REQUEST content actually is the plugin name
 * - Run an admin referrer check to make sure it goes through authentication
 * - Verify the output of $_GET makes sense
 * - Repeat with other user roles. Best directly by using the links/query string parameters.
 * - Repeat things for multisite. Once for a single site in the network, once sitewide.
 *
 *
 * @link       http://auracreativemedia.co.uk
 * @since      1.0.0
 *
 * @package    Aura_Supercommerce
 */


// If uninstall not called from WordPress, then exit.


if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}


global $wpdb;

$table_name_config = $wpdb->prefix . 'aurasc_config';
$table_name_plugins = $wpdb->prefix . 'aurasc_plugins';

$wpdb->query( "DROP TABLE IF EXISTS $table_name_config" );
$wpdb->query( "DROP TABLE IF EXISTS $table_name_plugins" );

delete_option("aura_supercommerce_db_ver");
delete_option("aura_status_flags");
