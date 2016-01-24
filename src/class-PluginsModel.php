<?php
/**
 * Created by PhpStorm.
 * User: Myszczyszyn Dawid - Code2Prog
 * Date: 24.01.2016
 * Time: 15:43
 */

namespace Whatarmy_Watchtower;


class PluginsModel {

	/**
	 * @return mixed
	 */
	static function getStat() {
		$plugins      = get_plugins();
		$plugins_list = array();
		foreach ( $plugins as $path => $plugin ) {
			$is_active = false;
			if ( is_plugin_active( $path ) ) {
				$is_active = true;
			}
			array_push( $plugins_list, array(
				'name'      => $plugin['Name'],
				'version'   => $plugin['Version'],
				'is_active' => $is_active,
				'updates'   => self::checkUpdates( $path )
			) );
		}

		return $plugins_list;
	}

	/**
	 * @param $plugin
	 *
	 * @return array
	 */
	static function checkUpdates( $plugin ) {
		$list = get_option( '_site_transient_update_plugins' );
		if ( array_key_exists( $plugin, $list->response ) ) {
			return array(
				'required' => true,
				'version'  => $list->response[ $plugin ]->new_version
			);
		} else {
			return array(
				'required' => false,
			);
		}

	}
}