<?php
/**
 * This file contains only one class.
 *
 * @package CMS
 * @file
 */

namespace WordPress\CMS\Controllers;

use \WordPress\CMS\DB\Database;
use \WordPress\CMS\DB\Table;
use \WordPress\CMS\DB\Column;
use \WordPress\CMS\Template;

/**
 * This controller handles creating, modifying, and deleting tables in the database.
 */
class SchemaController extends ControllerBase {

	/**
	 * View and edit the structure of the given table.
	 *
	 * @param string[] $args The request arguments.
	 * @return string
	 */
	public function index( $args ) {
		$template = new Template( 'table/schema.html' );
		if ( ! current_user_can( 'promote_users' ) ) {
			$template->add_notice( 'error', 'Only administrators are allowed to edit table structure.' );
		}

		$db = new Database( $this->wpdb );
		$template->action = 'structure';
		$template->tables = $db->get_tables();
		if ( isset( $args['table'] ) ) {
			$template->table = $db->get_table( $args['table'] );
			$template->record = $template->table->get_default_record();
		}
		$template->xtypes = Column::get_xtypes();
		return $template->render();
	}

	/**
	 * Add a new table and redirect to its schema-editing page.
	 *
	 * @param string[] $args The request arguments.
	 */
	public function newtable( $args ) {
		// Create table.
    $name = strtolower($args['new_table_name']);
    $name = preg_replace('/\s/', '_', $name);

		$db = new Database( $this->wpdb );
		$table = $db->create_table( $name );

		if (gettype($table) !== 'boolean') {
      $name = $table->get_name();
    }

		// Redirect user with message.
		$template = new Template( 'table/schema.html' );
		$template->add_notice( 'updated', 'New table created' );
		$url = 'admin.php?page=cms&controller=schema&table=' .$name;
		wp_safe_redirect( admin_url( $url ) );
		exit;
	}

	/**
	 * Save modifications to a table's schema.
	 *
	 * @param string[] $args The request arguments.
	 */
	public function save( $args ) {
		if ( ! isset( $args['table'] ) || ! current_user_can( 'promote_users' ) ) {
			$url = admin_url( 'admin.php?page=cms' );
			wp_safe_redirect( $url );
			exit;
		}
		$db = new Database( $this->wpdb );
		$table = $db->get_table( $args['table'] );
		if ( isset( $args['delete'] ) ) {
			wp_safe_redirect( $table->get_url( 'delete', null, 'schema' ) );
			exit;
		}

		// Rename.
		$new_name = $args['table'];
		if ( $table instanceof Table && ! empty( $args['new_name'] ) ) {
			$table->rename( $args['new_name'] );
			$new_name = $table->get_name();
		}

		// Set comment.
		if ( isset( $args['new_comment'] ) ) {
			$table->set_comment( $args['new_comment'] );
		}

		// Update columns.
		foreach ( $args['columns'] as $col_info ) {
			// Validate inputs.
			$old_col_name = isset( $col_info['old_name'] ) ? $col_info['old_name'] : null;
			$new_col_name = isset( $col_info['new_name'] ) ? $col_info['new_name'] : null;
			$xtype = isset( $col_info['xtype'] ) ? $col_info['xtype'] : null;
			$size = isset( $col_info['size'] ) ? $col_info['size'] : null;
			$nullable = isset( $col_info['nullable'] );
			$default = isset( $col_info['default'] ) ? $col_info['default'] : null;
			$auto_increment = isset( $args['auto_increment'] ) && $args['auto_increment'] === $old_col_name;
			$unique = isset( $col_info['unique'] );
			$comment = isset( $col_info['comment'] ) ? $col_info['comment'] : null;
			$target_table = isset( $col_info['target_table'] ) ? $db->get_table( $col_info['target_table'] ) : null;
			$after = isset( $col_info['after'] ) ? $col_info['after'] : null;

			// Change existing or insert new column.
			$altered = false;
			if ( $old_col_name ) {
				$col = $table->get_column( $col_info['old_name'] );
				if ( $col instanceof Column ) {
					$col->alter( $new_col_name, $xtype, $size, $nullable, $default, $auto_increment, $unique, $comment, $target_table, $after );
					$altered = true;
				}
			}
			if ( ! $altered && ! empty( $new_col_name ) ) {
				$table->add_column( $new_col_name, $xtype, $size, $nullable, $default, $auto_increment, $unique, $comment, $target_table, $after );
			}
		}

		// Finish up.
		$template = new Template( 'table/schema.html' );
		$template->add_notice( 'updated', 'Schema updated.' );
		$url = admin_url( 'admin.php?page=cms&controller=schema&table=' . $new_name );
		wp_safe_redirect( $url );
		exit;
	}

	/**
	 * Delete (drop) a table.
	 *
	 * @param string[] $args The request arguments.
	 */
	public function delete( $args ) {
		$template = new Template( 'table/delete.html' );
		$db = new Database( $this->wpdb );
		$table = $db->get_table( $args['table'] );

		// Ask for confirmation.
		if ( ! isset( $args['confirm_deletion'] ) ) {
			$template->table = $table;
			return $template->render();
		}

		// Carry out deletion.
		$table->drop();
		$template->add_notice( 'updated', 'Table dropped.' );
		wp_safe_redirect( admin_url( 'admin.php?page=cms' ) );
		exit;
	}
}
