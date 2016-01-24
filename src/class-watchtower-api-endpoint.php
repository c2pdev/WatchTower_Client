<?php

namespace Whatarmy_Watchtower;

use Whatarmy_Watchtower\PluginsModel as Plugins;

class Watchtower_API_Endpoint {

	public function __construct() {
		add_filter( 'query_vars', array( $this, 'add_query_vars' ), 0 );
		add_action( 'parse_request', array( $this, 'sniff_requests' ), 0 );
		add_action( 'init', array( $this, 'add_endpoint' ), 0 );
	}


	public function add_query_vars( $vars ) {
		$vars[] = '__watchtower-api';
		$vars[] = 'query';

		return $vars;
	}

	// Add API Endpoint
	public function add_endpoint() {
		add_rewrite_rule( '^watchtower-api/?([^/]+)?/?', 'index.php?__watchtower-api=1&query=$matches[1]', 'top' );

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
		$query = $wp->query_vars['query'];
		if ( $query === 'core' ) {
			$this->send_response( WPCoreModel::get_stats() );
		} else if ( $query === 'pages' ) {
			$this->send_response( $this->api_get_pages() );
		} else if ( $query === 'plugins' ) {
			$this->send_response( Plugins::getPluginsStat() );
		} else {
			$this->send_response( 'Error', 'Invalid request' );
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