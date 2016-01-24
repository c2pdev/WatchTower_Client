<?php
/**
 * Created by PhpStorm.
 * User: Myszczyszyn Dawid - Code2Prog
 * Date: 23.01.2016
 * Time: 16:31
 */

namespace Whatarmy_Watchtower;


class Watchtower {
	static function install() {
		$token = Token::generateToken();
		add_option( 'watchtower_token', $token );
	}
}