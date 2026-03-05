<?php
/**
 * Runs automatically when the plugin is deleted from the WordPress admin.
 * Removes all database options added by URL Debugger.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

delete_option( 'url_debugger_token' );
