<?php
/**
 * Runs when the plugin is uninstalled via the WordPress "Delete" action.
 * Removes all plugin options from the database.
 */
defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'ump_settings' );
