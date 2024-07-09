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
		wp_enqueue_style( 'quill-css', 'https://cdn.jsdelivr.net/npm/quill@2.0.0/dist/quill.snow.css' );
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

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/admin-notes-anywhere-admin.js', array( 'jquery' ), $this->version, false );
		wp_enqueue_script( 'quill-js', 'https://cdn.jsdelivr.net/npm/quill@2.0.0/dist/quill.js' );

		// Pass nonce to Save button.
		wp_localize_script(
			$this->plugin_name,
			'ana_data_object',
			array(
				'check_ana_get_nonce'    => wp_create_nonce( 'ana_get_nonce' ),
				'check_ana_save_nonce'    => wp_create_nonce( 'ana_save_nonce' ),
				'ajax_url' => admin_url( 'admin-ajax.php' ),
			)
		);
	}

	public function ana_add_admin_bar_item( WP_Admin_Bar $admin_bar ) {
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

	public function ana_save_content() {

		check_ajax_referer( 'ana_save_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'error' => 'Permission denied' ) );
		}

		global $wpdb;

		// $data = array();

		$page             = isset( $_SERVER['HTTP_REFERER'] ) ? basename( $_SERVER['HTTP_REFERER'] ) : '';
		$content          = isset( $_POST['content'] ) ? wp_kses_post( $_POST['content'] ) : '';
		$uid              = get_current_user_id();
		$current_datetime = current_datetime();
		$sql_datetime     = $current_datetime->format( 'Y-m-d H:i:s' );

		$table_name = $wpdb->prefix . 'ana_notes';

		// Check if there is a note for this page.
		$count_rows = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) from $table_name WHERE page = %s",
				$page
			)
		);

		if ( $count_rows === '0' ) {
			// If not, create new row.
			$wpdb->insert(
				$table_name,
				array(
					'creator_id'   => $uid,
					'page'         => $page,
					'content'      => $content,
					'date_updated' => $sql_datetime,
					'date_created' => $sql_datetime,
				),
				array( '%d', '%s', '%s', '%s' ),
			);
		} else {
			// If so, update existing row with new content.
			$wpdb->update(
				$table_name,
				array(
					'content'      => $content,
					'date_updated' => $sql_datetime,
				),
				array(
					'creator_id'   => $uid,
					'page'         => $page,
				),
				array( '%s', '%s' ),
				array( '%d', '%s' ),
			);
		}
		wp_send_json_success( array( 'message' => 'Note saved successfully' ) );
	}

	public function ana_get_content() {

		$nonce_verified = check_ajax_referer( 'ana_get_nonce', 'nonce' );

		if ( $nonce_verified !== 1 ) {
			$data['error'] = 'Invalid nonce';
		} else {

			global $wpdb;

			$data = array();

			$page       = isset( $_SERVER['HTTP_REFERER'] ) ? basename( $_SERVER['HTTP_REFERER'] ) : '';
			$content    = '';
			$table_name = $wpdb->prefix . 'ana_notes';

			$table_name = $wpdb->prefix . 'ana_notes';

			$row = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT content, creator_id from $table_name WHERE page = %s",
					$page
				)
			);

			if ( $row ) {
				$data['content']         = $row->content;
				$data['creator_id']      = $row->creator_id;
				$data['current_user_id'] = get_current_user_id();
			}
			wp_send_json_success( $data );
		}
	}
}
