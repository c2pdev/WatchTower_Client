<?php
/**
 * Created by PhpStorm.
 * User: Myszczyszyn Dawid - Code2Prog
 * Date: 23.01.2016
 * Time: 16:32
 */

namespace Whatarmy_Watchtower;


class Token {

	static function generateToken() {
		return md5( uniqid() );
	}

	static function getToken() {
		return get_option( 'watchtower_token' );
	}
}