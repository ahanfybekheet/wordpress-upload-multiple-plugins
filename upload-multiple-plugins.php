<?php
/**
 * Plugin Name: Upload Multiple Plugins
 * Plugin URI:  https://github.com/
 * Description: Fast drag-and-drop installation and activation of multiple plugins. Built for development and testing environments.
 * Version:     1.0.0
 * Author:      Dev Tools
 * License:     GPL-2.0-or-later
 * Requires at least: 5.0
 * Requires PHP: 7.0
 * Text Domain: upload-multiple-plugins
 */

defined( 'ABSPATH' ) || exit;

define( 'UMP_VERSION',  '1.0.0' );
define( 'UMP_FILE',     __FILE__ );
define( 'UMP_DIR',      plugin_dir_path( __FILE__ ) );
define( 'UMP_URL',      plugin_dir_url( __FILE__ ) );

require_once UMP_DIR . 'includes/class-ump-settings.php';
require_once UMP_DIR . 'includes/class-ump-installer.php';
require_once UMP_DIR . 'includes/class-ump-admin.php';

function ump_init() {
	if ( ! is_admin() ) {
		return;
	}
	new UMP_Settings();
	new UMP_Admin();
}
add_action( 'plugins_loaded', 'ump_init' );
