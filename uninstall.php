<?php
/**
 * This file removes all of Cms's database tables and WordPress options.
 *
 * @file
 * @package IPL
 */

if ( ! defined( 'ABSPATH' ) || ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	return false;
}

// Make sure all Cms classes are accessible.
require __DIR__ . '/vendor/autoload.php';

// Clear Grants' option.
$grants = new \WordPress\CMS\DB\Grants();
$grants->delete();

// Drop the ChangeTracker's and Reports' tables.
global $wpdb;
$wpdb->query( 'SET FOREIGN_KEY_CHECKS = 0' );
foreach ( \WordPress\CMS\DB\ChangeTracker::table_names() as $tbl ) {
	$wpdb->query( "DROP TABLE IF EXISTS `$tbl`;" );
}
$wpdb->query( "DROP TABLE IF EXISTS `" . \WordPress\CMS\DB\Reports::reports_table_name() . "`;" );
$wpdb->query( "DROP TABLE IF EXISTS `" . \WordPress\CMS\DB\Reports::report_sources_table_name() . "`;" );
$wpdb->query( 'SET FOREIGN_KEY_CHECKS = 1' );
