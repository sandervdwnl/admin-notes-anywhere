<?php

/**
 * Fired during plugin activation
 *
 * @link       https://github.com/sandervdwnl
 * @since      1.0.0
 *
 * @package    Admin_Notes_Anywhere
 * @subpackage Admin_Notes_Anywhere/includes
 */

	global $ana_db_version;
	$ana_db_version = '1.0';

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Admin_Notes_Anywhere
 * @subpackage Admin_Notes_Anywhere/includes
 * @author     Sander van der WIndt <sander@bonwp.com>
 */
class Admin_Notes_Anywhere_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {

		global $wpdb;
		global $ana_db_version;

		$table_name = $wpdb->prefix . 'ana_notes';

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			note_id mediumint(9) NOT NULL AUTO_INCREMENT,
			creator_id mediumint(9) NOT NULL,
			page tinytext NOT NULL,
			content text,
			public BOOLEAN NOT NULL DEFAULT FALSE,
			date_created datetime NOT NULL,
			date_updated datetime NOT NULL,
			PRIMARY KEY (note_id)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		add_option( 'ana_db_version', $ana_db_version );
	}
}
