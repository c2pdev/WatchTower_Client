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
		foreach ( $plugins as $plugin_path => $plugin ) {
			array_push( $plugins_list, array(
				'name'      => $plugin['Name'],
				'version'   => $plugin['Version'],
				'is_active' => self::isActive( $plugin_path ),
				'updates'   => self::checkUpdates( $plugin_path ),
			) );
		}

		return $plugins_list;
	}

	/**
	 * @param $plugin
	 *
	 * @return array
	 */
	private static function checkUpdates( $plugin ) {
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

	/**
	 * @param $plugin_path
	 *
	 * @return bool
	 */
	private static function isActive( $plugin_path ) {
		$is_active = false;
		if ( is_plugin_active( $plugin_path ) ) {
			$is_active = true;
		}

		return $is_active;
	}

}