<?php
/**
 * This file contains only a single class.
 *
 * @file
 * @package CMS
 */

namespace WordPress\CMS\Controllers;

use \WordPress\CMS\DB\Database;
use \WordPress\CMS\DB\Reports;

/**
 * The reports controller is responsible for displaying reports.
 * Editing of reports is done in the usual Cms fashion.
 */
class ReportsController extends ControllerBase {

	/**
	 * View a report.
	 *
	 * @param string[] $args The request arguments.
	 * @return type
	 */
	public function index( $args ) {
		$db = new Database( $this->wpdb );
		$id = isset( $args['id'] ) ? $args['id'] : Reports::DEFAULT_REPORT_ID;
		$reports = new Reports( $db );
		$template = $reports->get_template( $id );
		$out = $template->render();
		if ( $template->file_extension ) {
			$this->send_file( $template->file_extension, $template->mime_type, $out, $template->title );
		} else {
			return $out;
		}
	}
}
