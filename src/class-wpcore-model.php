<?php
/**
 * Created by PhpStorm.
 * User: Myszczyszyn Dawid - Code2Prog
 * Date: 24.01.2016
 * Time: 16:52
 */

namespace Whatarmy_Watchtower;


class WPCore_Model {

	/**
	 * @return array
	 */
	static function getStat() {
		$stats = array(
			'site_name'         => get_option( 'blogname' ),
			'site_description'  => get_option( 'blogdescription' ),
			'site_url'          => get_site_url(),
			'is_multisite'      => ( is_multisite() == true ? 'true' : 'false' ),
			'template'          => get_option( 'template' ),
			'wp_version'        => get_bloginfo( 'version' ),
			'admin_email'       => get_option( 'admin_email' ),
			'php_version'       => phpversion(),
			'updates'           => self::checkUpdates(),
			'is_public'         => get_option( 'blog_public' ),
			'installation_size' => self::display_size( self::filesize_recursive( ABSPATH ) ),
		);

		return $stats;

	}

	/**
	 * @return array
	 */
	private static function checkUpdates() {
		$updates = get_option( '_site_transient_update_core' );

		if ( isset( $updates->updates[0]->response ) && $updates->updates[0]->response == 'upgrade' ) {
			return array(
				'required'    => true,
				'new_version' => $updates->updates[0]->current

			);
		} else {
			return array(
				'required' => false,

			);
		}

	}


	private static function filesize_recursive( $path ) { // Function 1
		if ( ! file_exists( $path ) ) {
			return 0;
		}
		if ( is_file( $path ) ) {
			return filesize( $path );
		}
		$ret = 0;
		foreach ( glob( $path . "/*" ) as $fn ) {
			$ret += self::filesize_recursive( $fn );
		}

		return $ret;
	}

	private static function display_size( $size ) { // Function 2
		$sizes     = array( 'B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB' );
		$retstring = '%01.2f %s';
		if ( $retstring === null ) {
			$retstring = '%01.2f %s';
		}
		$lastsizestring = end( $sizes );
		foreach ( $sizes as $sizestring ) {
			if ( $size < 1024 ) {
				break;
			}
			if ( $sizestring != $lastsizestring ) {
				$size /= 1024;
			}
		}
		if ( $sizestring == $sizes[0] ) {
			$retstring = '%01d %s';
		} // Bytes aren't normally fractional
		return sprintf( $retstring, $size, $sizestring );
	}

}