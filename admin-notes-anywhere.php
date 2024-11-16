<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://github.com/sandervdwnl
 * @since             1.0.0
 * @package           Admin_Notes_Anywhere
 *
 * @wordpress-plugin
 * Plugin Name:       Admin Notes Anywhere
 * Plugin URI:        https://bonwp.net
 * Description:       Add a note on any admin page to keep track of all your tasks.
 * Version:           1.0.0-alpha.4
 * Author:            Sander van der WIndt
 * Author URI:        https://github.com/sandervdwnl/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       admin-notes-anywhere
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'ADMIN_NOTES_ANYWHERE_VERSION', '1.0.0-alpha.4' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-admin-notes-anywhere-activator.php
 */
function activate_admin_notes_anywhere() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-admin-notes-anywhere-activator.php';
	Admin_Notes_Anywhere_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-admin-notes-anywhere-deactivator.php
 */
function deactivate_admin_notes_anywhere() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-admin-notes-anywhere-deactivator.php';
	Admin_Notes_Anywhere_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_admin_notes_anywhere' );
register_deactivation_hook( __FILE__, 'deactivate_admin_notes_anywhere' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-admin-notes-anywhere.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_admin_notes_anywhere() {

	$plugin = new Admin_Notes_Anywhere();
	$plugin->run();

}
run_admin_notes_anywhere();
