<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://github.com/sandervdwnl
 * @since      1.0.0
 *
 * @package    Admin_Notes_Anywhere
 * @subpackage Admin_Notes_Anywhere/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Admin_Notes_Anywhere
 * @subpackage Admin_Notes_Anywhere/admin
 * @author     Sander van der WIndt <sander@bonwp.com>
 */
class Admin_Notes_Anywhere_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string $plugin_name       The name of this plugin.
	 * @param      string $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Admin_Notes_Anywhere_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Admin_Notes_Anywhere_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/admin-notes-anywhere-admin.css', array(), $this->version, 'all' );
		wp_enqueue_style( 'quill-css', plugin_dir_url( __FILE__ ) . 'css/quill.css', array(), '2.0', 'all' );
		wp_enqueue_style( 'dashicons' );
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Admin_Notes_Anywhere_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Admin_Notes_Anywhere_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		if ( ! is_admin() ) {
			return;
		}

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/admin-notes-anywhere-admin.js', array( 'jquery' ), $this->version, false );
		wp_enqueue_script( 'quill-js', plugin_dir_url( __FILE__ ) . 'js/quill.js', array(), '2.0', false );

		// Pass nonce to Save button.
		wp_localize_script(
			$this->plugin_name,
			'ana_data_object',
			array(
				'check_ana_get_nonce'  => wp_create_nonce( 'ana_get_nonce' ),
				'check_ana_save_nonce' => wp_create_nonce( 'ana_save_nonce' ),
				'is_admin'             => current_user_can( 'manage_options' ),
				'ajax_url'             => admin_url( 'admin-ajax.php' ),
			)
		);
	}

	/**
	 * Adds a menu item to the WP admin bar.
	 *
	 * @param WP_Admin_Bar $admin_bar The WP admin bar.
	 * @return void
	 */
	public function ana_add_admin_bar_item( WP_Admin_Bar $admin_bar ) {
		if ( ! is_admin() ) {
			return;
		}
		$admin_bar->add_menu(
			array(
				'id'     => 'admin-notes-anywhere',
				'parent' => null,
				'group'  => null,
				'title'  => 'Notes',
				'href'   => '#',
				'meta'   => array(
					'title' => __( 'Open Admin Notes Anywhere', 'admin-notes-anywhere' ),
				),
			)
		);
	}

	/**
	 * Saves notes to the db.
	 *
	 * @return void
	 */
	public function ana_save_content() {

		check_ajax_referer( 'ana_save_nonce', 'nonce' );

		// Only admins can add notes.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'error' => 'Permission denied' ) );
		}

		global $wpdb;

		$parsed_url = isset( $_SERVER['HTTP_REFERER'] ) ? wp_parse_url( sanitize_url( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) ) : array();
		$page       = isset( $parsed_url['path'] ) ? $parsed_url['path'] : '';
		// Save page and first query arg (example.php--example_type=example).
		if ( isset( $parsed_url['query'] ) && str_contains( $parsed_url['query'], '&' ) ) {
			$page .= '--' . substr( $parsed_url['query'], 0, strpos( $parsed_url['query'], '&' ) );
		} else {
			$page .= '--' . $parsed_url['query'];
		}
		$content          = isset( $_POST['content'] ) ? wp_kses_post( wp_unslash( $_POST['content'] ) ) : '';
		$public           = isset( $_POST['public'] ) ? absint( wp_unslash( $_POST['public'] ) ) : 0;
		$uid              = get_current_user_id();
		$current_datetime = current_datetime();
		$sql_datetime     = $current_datetime->format( 'Y-m-d H:i:s' );

		$table_name = $wpdb->prefix . 'ana_notes';

		// Check if there is a note for this page.
		$count_rows = $wpdb->get_var( //phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				'SELECT COUNT(*) from %i WHERE page = %s',
				$table_name,
				$page
			)
		);

		if ( '0' === $count_rows ) {
			// If not, create new row.
			$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$table_name,
				array(
					'creator_id'   => $uid,
					'page'         => $page,
					'content'      => $content,
					'public'       => $public,
					'date_updated' => $sql_datetime,
					'date_created' => $sql_datetime,
				),
				array( '%d', '%s', '%s', '%d', '%s', '%s' ),
			);
		} else {
			// If so, update existing row with new content.
			$wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$table_name,
				array(
					'content'      => $content,
					'public'       => $public,
					'date_updated' => $sql_datetime,
				),
				array(
					'creator_id' => $uid,
					'page'       => $page,
				),
				array( '%s', '%d', '%s' ),
				array( '%d', '%s' ),
			);
		}
		// Clear cache fot this page.
		wp_cache_delete( "note_{$page}" );

		wp_send_json_success( array( 'message' => 'Note saved successfully' ) );
	}

	/**
	 * Retrieves notes from the db.
	 *
	 * @return void
	 */
	public function ana_get_content() {

		$nonce_verified = check_ajax_referer( 'ana_get_nonce', 'nonce' );

		if ( 1 !== $nonce_verified ) {
			$data['error'] = 'Invalid nonce';
		} else {

			global $wpdb;

			$data = array();

			$parsed_url = isset( $_SERVER['HTTP_REFERER'] ) ? wp_parse_url( sanitize_url( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) ) : array();
			$page       = isset( $parsed_url['path'] ) ? $parsed_url['path'] : '';
			// Save page and first query arg (example.php--example_type=example).
			if ( isset( $parsed_url['query'] ) && str_contains( $parsed_url['query'], '&' ) ) {
				$page .= '--' . substr( $parsed_url['query'], 0, strpos( $parsed_url['query'], '&' ) );
			} else {
				$page .= '--' . $parsed_url['query'];
			}
			$table_name = $wpdb->prefix . 'ana_notes';
			$cache_key  = "note_{$page}";

			$row = wp_cache_get( $cache_key );

			if ( false === $row ) {
				$row = $wpdb->get_row( //phpcs:ignore WordPress.DB.DirectDatabaseQuery
					$wpdb->prepare(
						'SELECT content, creator_id, public from %i WHERE page = %s',
						$table_name,
						$page
					)
				);
				wp_cache_set( $cache_key, $row );
			}

			if ( $row ) {
				$data['content']         = $row->content;
				$data['creator_id']      = $row->creator_id;
				$data['public']          = $row->public;
				$data['current_user_id'] = get_current_user_id();
				$data['is_admin']        = current_user_can( 'manage_options' );
				$data['is_creator']      = get_current_user_id() == $row->creator_id ? 1 : 0;
			} else {
				$data['current_user_id'] = get_current_user_id();
				$data['is_admin']        = current_user_can( 'manage_options' );
			}
			wp_send_json_success( $data );
		}
	}
}
