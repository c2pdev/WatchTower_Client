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

		return $vars;
	}

	// Add API Endpoint
	public function add_endpoint() {
		add_rewrite_rule( '^watchtower-api/?([a-zA-Z0-9]+)?/?([a-zA-Z]+)?/?', 'index.php?__watchtower-api=1&access_token=$matches[1]&query=$matches[2]', 'top' );

	}


	protected static function haveAccess( $token ) {
		if ( $token == get_option( 'watchtower' )['access_token'] ) {
			return true;
		} else {
			return false;
		}
	}

	public function sniff_requests() {
		global $wp;
		if ( isset( $wp->query_vars['__watchtower-api'] ) ) {
			$this->handle_request();
			exit;
		}
	}

	protected function handle_request() {
		global $wp;
		$query        = $wp->query_vars['query'];
		$access_token = self::haveAccess( $wp->query_vars['access_token'] );

		if ( $query === 'core' && $access_token ) {
			$this->send_response( WPCoreModel::getStat() );
		} else if ( $query === 'pages' && $access_token ) {
			$this->send_response( $this->api_get_pages() );
		} else if ( $query === 'plugins' && $access_token ) {
			$this->send_response( PluginsModel::getStat() );
		} else {
			$this->send_response( 'Error', 'Invalid request or access token' );
		}

	}

	protected function send_response( $response ) {
		header( 'content-type: application/json; charset=utf-8' );
		echo json_encode( $response ) . "\n";
		exit;
	}

	protected function api_get_pages() {
		$args   = array(
			'sort_order'  => 'ASC',
			'sort_column' => 'post_title',
			'post_type'   => 'page',
			'post_status' => 'publish'
		);
		$pages  = get_pages( $args );
		$result = array();
		foreach ( $pages as $page => $value ) {
			$result[] = $value;
		}

		return $result;

	}
}