<?php

namespace Whatarmy_Watchtower;

class Watchtower_API_Endpoint {

	public function __construct() {
		add_filter( 'query_vars', array( $this, 'add_query_vars' ), 0 );
		add_action( 'parse_request', array( $this, 'sniff_requests' ), 0 );
		add_action( 'init', array( $this, 'add_endpoint' ), 0 );
	}


	public function add_query_vars( $vars ) {
		$vars[] = '__watchtower-api';
		$vars[] = 'query';
		$vars[] = 'access_token';
		$vars[] = 'backup_name';

		return $vars;
	}

	// Add API Endpoint
	public function add_endpoint() {
		add_rewrite_rule( '^watchtower-api/?([a-zA-Z0-9]+)?/?([a-zA-Z]+)?/?', 'index.php?__watchtower-api=1&access_token=$matches[1]&query=$matches[2]', 'top' );

	}


	/**
	 * @param $token
	 *
	 * @return bool
	 */
	protected static function haveAccess( $token ) {
		if ( $token == get_option( 'watchtower' )['access_token'] ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 *
	 */
	public function sniff_requests() {
		global $wp;
		if ( isset( $wp->query_vars['__watchtower-api'] ) ) {
			$this->handle_request();
			exit;
		}
	}

	/**
	 *
	 */
	protected function handle_request() {
		global $wp;
		$query          = $wp->query_vars['query'];
		$access_token   = self::haveAccess( $wp->query_vars['access_token'] );
		$plugin_data    = get_plugin_data( plugin_dir_path( __FILE__ ) . '../watchtower.php' );
		$plugin_version = $plugin_data['Version'];
		switch ( true ) {
			case ( $query === 'login' && $access_token ):
				$this->send_response( array(
					'status' => '200 OK',
					'data'   => WPCore_Model::sign_in()
				) );
				break;
			case( $query === 'download_backup' && $access_token && $wp->query_vars['backup_name'] ):
				$this->serveBackup( $wp->query_vars['backup_name'] );
				break;
			case ( $query === 'test' && $access_token ):
				$this->send_response( array(
					'status' => '200 OK',
				) );
				break;
			case ( $query === 'core' && $access_token ):
				$this->send_response(
					array(
						'status'         => '200 OK',
						'client_version' => $plugin_version,
						'plugins'        => WPCore_Model::getStat(),
					) );
				break;
			case ( $query === 'plugins' && $access_token ):
				$this->send_response( array(
					'status'         => '200 OK',
					'client_version' => $plugin_version,
					'plugins'        => Plugin_Model::getStat(),
				) );
				break;
			case ( $query === 'themes' && $access_token ):
				$this->send_response(
					array(
						'status'         => '200 OK',
						'client_version' => $plugin_version,
						'plugins'        => Theme_Model::getStat(),
					) );
				break;
			case ( $query === 'all' && $access_token ):
				$this->send_response( array(
					'status'         => '200 OK',
					'client_version' => $plugin_version,
					'core'           => WPCore_Model::getStat(),
					'plugins'        => Plugin_Model::getStat(),
					'themes'         => Theme_Model::getStat(),
				) );
				break;
			default:
				$this->send_response( 'Error', 'Invalid request or access token' );
				break;
		}

	}

	/**
	 * @param $filename
	 */
	protected function serveBackup( $filename ) {
		header( 'Content-type: application/gzip' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );

		readfile( WHT_BACKUP_DIR . '/' . $filename );
	}

	/**
	 * @param $response
	 */
	protected function send_response( $response ) {
		header( 'content-type: application/json; charset=utf-8' );
		echo json_encode( $response ) . "\n";
		exit;
	}

}