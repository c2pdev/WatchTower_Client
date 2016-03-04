<?php
/*
Plugin Name: Whatarmy Watchtower
Plugin URI: https://github.com/c2pdev/WatchTower_Client
Description: WP website monitoring API
Author: Code2prog
Version: 1.1.10
Author URI: http://whatarmy.com
*/

/**
 * Composer dependencies autoload
 */
require 'vendor/autoload.php';

/**
 * UPDATE Plugin
 */
require 'plugin-update-checker/plugin-update-checker.php';
$className       = PucFactory::getLatestClassVersion( 'PucGitHubChecker' );
$myUpdateChecker = new $className(
	'https://github.com/c2pdev/WatchTower_Client',
	__FILE__,
	'master'
);

function auto_update_specific_plugins ( $update, $item ) {
	// Array of plugin slugs to always auto-update
	$plugins = array (
		'watchtower',
	);
	if ( in_array( $item->slug, $plugins ) ) {
		return true; // Always update plugins in this array
	} else {
		return $update; // Else, use the normal API response to decide whether to update or not
	}
}
add_filter( 'auto_update_plugin', 'auto_update_specific_plugins', 10, 2 );

/**
 * Register autoload SRC
 */
register_wp_autoload( 'Whatarmy_Watchtower\\', __DIR__ . '/src' );

/**
 * Include Plugin Class
 */
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

/**
 * Run Watchtower main class
 */
new \Whatarmy_Watchtower\Watchtower();
