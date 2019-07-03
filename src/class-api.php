<?php
/**
 * Author: Code2Prog
 * Date: 2019-06-07
 * Time: 16:03
 */

namespace WhatArmy\Watchtower;

use WP_REST_Request as WP_REST_Request;
use WP_REST_Response as WP_REST_Response;

/**
 * Class Api
 * @package WhatArmy\Watchtower
 */
class Api
{
    protected $access_token;

    const API_VERSION = 'v1';
    const API_NAMESPACE = 'wht';

    /**
     * Api constructor.
     */
    public function __construct()
    {
        $this->access_token = get_option('watchtower')['access_token'];

        add_action('rest_api_init', function () {
            $this->routes();
        });
    }

    /**
     * Routing List
     */
    private function routes()
    {
        register_rest_route($this->route_namespace(), 'test', $this->resolve_action('test_action'));
        register_rest_route($this->route_namespace(), 'get/core', $this->resolve_action('get_core_action'));
        register_rest_route($this->route_namespace(), 'get/plugins', $this->resolve_action('get_plugins_action'));
        register_rest_route($this->route_namespace(), 'get/themes', $this->resolve_action('get_themes_action'));
        register_rest_route($this->route_namespace(), 'get/all', $this->resolve_action('get_all_action'));
        register_rest_route($this->route_namespace(), 'user_logs', $this->resolve_action('get_user_logs_action'));

        /**
         * Password Less Access
         */
        register_rest_route($this->route_namespace(), 'access/generate_ota',
            $this->resolve_action('access_generate_ota_action'));

        /**
         * Backups
         */
        register_rest_route($this->route_namespace(), 'backup/file/run',
            $this->resolve_action('run_backup_file_action'));
        register_rest_route($this->route_namespace(), 'backup/mysql/run',
            $this->resolve_action('run_backup_db_action'));
    }


    /**
     * @return WP_REST_Response
     */
    public function access_generate_ota_action()
    {
        $access = new Password_Less_Access;
        return $this->make_response($access->generate_ota());
    }

    /**
     * @param  WP_REST_Request  $request
     * @return WP_REST_Response
     */
    public function run_backup_db_action(WP_REST_Request $request)
    {
        $backup = new Backup;
        $backup->mysqlBackup($request->get_param('callbackUrl'));

        return $this->make_response('scheduled');
    }


    /**
     * @param  WP_REST_Request  $request
     * @return WP_REST_Response
     */
    public function run_backup_file_action(WP_REST_Request $request)
    {
        $backup = new Backup;
        $filename = $backup->fileBackup($request->get_param('callbackUrl'));

        return $this->make_response(['filename' => $filename.'.zip']);
    }


    /**
     * @return WP_REST_Response
     */
    public function get_user_logs_action()
    {
        $user_logs = new User_Logs;
        return $this->make_response($user_logs->get());
    }

    /**
     * @return WP_REST_Response
     */
    public function get_all_action()
    {
        $core = new Core;
        $plugins = new Plugin;
        $themes = new Theme;

        return $this->make_response([
            'client_version' => $core->wht_plugin_version(),
            'core'           => $core->get(),
            'plugins'        => $plugins->get(),
            'themes'         => $themes->get(),
        ]);
    }

    /**
     * @return WP_REST_Response
     */
    public function get_themes_action()
    {
        $themes = new Theme;
        return $this->make_response($themes->get());
    }

    /**
     * @return WP_REST_Response
     */
    public function test_action()
    {
        $core = new Core;
        return $this->make_response($core->test());
    }

    /**
     * @return WP_REST_Response
     */
    public function get_core_action()
    {
        $core = new Core;
        return $this->make_response($core->get());
    }

    /**
     * @return WP_REST_Response
     */
    public function get_plugins_action()
    {
        $plugins = new Plugin;
        return $this->make_response($plugins->get());
    }

    /**
     * @param  array  $data
     * @param  int  $status_code
     * @return WP_REST_Response
     */
    private function make_response($data = [], $status_code = 200)
    {
        $response = new WP_REST_Response($data);
        $response->set_status($status_code);

        return $response;
    }

    /**
     * @param  WP_REST_Request  $request
     * @return bool
     */
    public function check_permission(WP_REST_Request $request)
    {
        return $request->get_param('access_token') == $this->access_token;
    }

    /**
     * @param  WP_REST_Request  $request
     * @return bool
     */
    public function check_ota(WP_REST_Request $request)
    {
        return $request->get_param('access_token') == get_option('watchtower_ota_token');
    }

    /**
     * @param  callable  $_action
     * @param  string  $method
     * @return array
     */
    private function resolve_action($_action, $method = 'POST')
    {
        return [
            'methods'             => $method,
            'callback'            => [$this, $_action],
            'permission_callback' => [$this, ($_action == 'access_login_action') ? 'check_ota' : 'check_permission']
        ];
    }

    /**
     * @return string
     */
    private function route_namespace()
    {
        return join('/', [self::API_NAMESPACE, self::API_VERSION]);
    }
}