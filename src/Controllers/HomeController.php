<?php
/**
 * This file contains only a single file.
 *
 * @file
 * @package CMS
 */

namespace WordPress\CMS\Controllers;

/**
 * The home controller displays a dashboard
 * that lists all tables, views, and reports.
 */
class HomeController extends ControllerBase {

	/**
	 * The Cms dashboard.
	 *
	 * @return string
	 */
	public function index() {

		$template = new \WordPress\CMS\Template( 'home.html' );
		$template->title = 'Cms';
		$db = new \WordPress\CMS\DB\Database( $this->wpdb );

		// Tables.
		$transient_name = CMS_SLUG . 'home_table_list';
		$table_info = '';//get_transient( $transient_name );

		//die('<pre>' . print_r($table_info, 1) . '</pre>');

		if ( ! $table_info ) {
			$table_info = array();
			foreach ( $db->get_tables() as $table ) {
			  if (!preg_match('/^' . print_r($this->wpdb->prefix, 1) . '/i', $table->get_name())) {
          $table_info[] = array(
            'title' => $table->get_title(),
            'count' => $table->count_records(),
            'url' => $table->get_url(),
          );
        }
			}
			set_transient( $transient_name, $table_info, MINUTE_IN_SECONDS * 5 );
		}
		$template->tables = $table_info;

		//die('<pre>' . print_r($table_info, 1) . '</pre>');

		// Views.
		//$template->views = $db->get_views();

		// Reports.
		//$reports_table = $db->get_table( \WordPress\CMS\DB\Reports::reports_table_name() );
		//$template->reports = ($reports_table) ? $reports_table->get_records( false ) : array();

		return $template->render();
	}
}
