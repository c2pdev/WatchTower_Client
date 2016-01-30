<?php
/*
Plugin Name: Whatarmy Watchtower
Plugin URI:
Description: Data about website
Author: Code2prog
Version: 1.0.5
Author URI:
*/
require 'plugin_update_check.php';
$MyUpdateChecker = new PluginUpdateChecker_2_0 (
	'https://kernl.us/api/v1/updates/56ac30fbdf35162478330b0f/',
	__FILE__,
	'whatarmy-watchtower',
	1
);
require 'vendor/autoload.php';
register_wp_autoload( 'Whatarmy_Watchtower\\', __DIR__ . '/src' );
if ( ! function_exists( 'get_plugins' ) ) {
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
}
/**
 * Activation Hook
 */
register_activation_hook( __FILE__, array( '\Whatarmy_Watchtower\Install_Uninstall', 'install' ) );

/**
 * RUN API ENDPOINT
 */
new \Whatarmy_Watchtower\Watchtower_API_Endpoint();
new \Whatarmy_Watchtower\Watchtower();
