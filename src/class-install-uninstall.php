<?php
/**
 * Created by PhpStorm.
 * User: Myszczyszyn Dawid - Code2Prog
 * Date: 24.01.2016
 * Time: 18:41
 */

namespace Whatarmy_Watchtower;

class Install_Uninstall {
	/**
	 *
	 */
	static function install() {
		$token = Token::generateToken();
		add_option( 'watchtower', array(
			'access_token' => $token,
		) );
		flush_rewrite_rules();
	}

	/**
	 * @param $ver 
	 */
	static function watchtower_create_db( $ver ) {
		global $wpdb;

		$version         = get_option( 'watchtower_db_version' );
		$charset_collate = $wpdb->get_charset_collate();
		$table_name      = $wpdb->prefix . 'watchtower_logs';


		$sql = "CREATE TABLE $table_name (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		action  VARCHAR(255) NOT NULL,
		who smallint(5) NOT NULL,
		created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		UNIQUE KEY id (id)
	) $charset_collate;";


		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
		update_option( 'watchtower_db_version', $ver );
//		if ( version_compare( $version, '2.0' ) < 0 ) {
//			$sql = "CREATE TABLE $table_name (
//		  id mediumint(9) NOT NULL AUTO_INCREMENT,
//		  time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
//		  views smallint(5) NOT NULL,
//		  clicks smallint(5) NOT NULL,
//		  blog_id smallint(5) NOT NULL,
//		  UNIQUE KEY id (id)
//		) $charset_collate;";
//			dbDelta( $sql );
//
//			update_option( 'my_plugin_version', '2.0' );
//
//		}

	}
}