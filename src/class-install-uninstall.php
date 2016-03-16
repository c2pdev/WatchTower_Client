<?php
/**
 * Created by PhpStorm.
 * User: Myszczyszyn Dawid - Code2Prog
 * Date: 24.01.2016
 * Time: 18:41
 */
namespace Whatarmy_Watchtower;

class Install_Uninstall {
	static function install() {
		$token = Token::generateToken();
		add_option( 'watchtower', array(
			'access_token' => $token,
		) );
		flush_rewrite_rules();
	}
}