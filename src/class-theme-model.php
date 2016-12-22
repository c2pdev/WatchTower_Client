<?php
/**
 * Created by PhpStorm.
 * User: Myszczyszyn Dawid - Code2Prog
 * Date: 24.01.2016
 * Time: 15:43
 */

namespace Whatarmy_Watchtower;


class Theme_Model {

	/**
	 * @return mixed
	 */
	static function getStat() {
		$themes      = wp_get_themes();
		$themes_list = array();
		foreach ( $themes as $theme_shortname => $theme ) {
			array_push( $themes_list, array(
				'name'    => $theme['Name'],
				'version' => $theme['Version'],
				'theme'   => $theme_shortname,
				'updates' => self::checkUpdates( $theme_shortname ),
			) );
		}

		return $themes_list;
	}

	/**
	 * @param $theme
	 *
	 * @return array
	 */
	private static function checkUpdates( $theme ) {
		$list = get_option( '_site_transient_update_themes' );

		if ( is_array( $list->response ) ) {
			if ( array_key_exists( $theme, $list->response ) ) {
				return array(
					'required' => true,
					'version'  => $list->response[ $theme ]['new_version']
				);
			} else {
				return array(
					'required' => false,
				);
			}
		} else {
			return array(
				'required' => false,
			);
		}

	}

}