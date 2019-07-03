<?php
/**
 * Author: Code2Prog
 * Date: 2019-06-07
 * Time: 17:31
 */

namespace WhatArmy\Watchtower;

/**
 * Class Core
 * @package WhatArmy\Watchtower
 */
class Core
{
    public $plugin_data;

    /**
     * Core constructor.
     */
    public function __construct()
    {
        $this->plugin_data = $this->plugin_data();
    }

    private function plugin_data()
    {
        $main_file = explode('/', plugin_basename(WHT_MAIN))[1];

        return get_plugin_data(plugin_dir_path(WHT_MAIN).$main_file);
    }

    public function wht_plugin_version()
    {
        return $this->plugin_data['Version'];
    }

    public function test()
    {
        return [
            'version' => $this->wht_plugin_version(),
        ];
    }

    public function get()
    {
        return [
            'site_name'         => get_option('blogname'),
            'site_description'  => get_option('blogdescription'),
            'site_url'          => get_site_url(),
            'is_multisite'      => (is_multisite() == true ? 'true' : 'false'),
            'template'          => get_option('template'),
            'wp_version'        => get_bloginfo('version'),
            'admin_email'       => get_option('admin_email'),
            'php_version'       => Utils::php_version(),
            'updates'           => $this->check_updates(),
            'is_public'         => get_option('blog_public'),
            'installation_size' => $this->installation_file_size(),
            'comments'          => wp_count_comments(),
            'comments_allowed'  => (get_default_comment_status() == 'open') ? true : false,
            'site_ip'           => $this->external_ip(),
            'db_size'           => $this->db_size(),
            'timezone'          => [
                'gmt_offset'      => get_option('gmt_offset'),
                'string'          => get_option('timezone_string'),
                'server_timezone' => date_default_timezone_get(),
            ],
            'admins_list'       => $this->admins_list(),
            'admin_url'         => admin_url(),
            'content_dir'       => (defined('WP_CONTENT_DIR')) ? WP_CONTENT_DIR : false,
            'pwp_name'          => (defined('PWP_NAME')) ? PWP_NAME : false,
        ];
    }

    private function check_updates()
    {
        global $wp_version;
        do_action("wp_version_check"); // force WP to check its core for updates
        $update_core = get_site_transient("update_core"); // get information of updates

        if ('upgrade' == $update_core->updates[0]->response) {
            require_once(ABSPATH.WPINC.'/version.php');
            $new_core_ver = $update_core->updates[0]->current; // The new WP core version

            return array(
                'required'    => true,
                'new_version' => $new_core_ver,
            );

        } else {
            return array(
                'required' => false,

            );
        }
    }

    public function installation_file_size($path = ABSPATH, $humanReadable = true)
    {
        $bytesTotal = 0;
        $path = realpath($path);
        if ($path !== false && $path != '' && file_exists($path)) {
            foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path,
                \FilesystemIterator::SKIP_DOTS)) as $object) {
                $bytesTotal += $object->getSize();
            }
        }
        if ($humanReadable == true) {
            $bytesTotal = Utils::size_human_readable($bytesTotal);
        }
        return $bytesTotal;
    }

    public function external_ip()
    {
        $curl = new \Curl();
        $curl->options['CURLOPT_SSL_VERIFYPEER'] = false;
        $curl->options['CURLOPT_SSL_VERIFYHOST'] = false;
        $ip = json_decode($curl->get('https://api.ipify.org?format=json'))->ip;

        return $ip;
    }

    public function db_size()
    {
        global $wpdb;

        $queryStr = 'SELECT  ROUND(SUM(((DATA_LENGTH + INDEX_LENGTH)/1024/1024)),2) AS "MB"
        FROM INFORMATION_SCHEMA.TABLES
	WHERE TABLE_SCHEMA = "'.$wpdb->dbname.'";';


        $query = $wpdb->get_row($queryStr);

        return $query->MB;
    }

    public function admins_list()
    {
        $admins_list = get_users('role=administrator');
        $admins = [];
        foreach ($admins_list as $admin) {
            array_push($admins, array(
                'login' => $admin->user_login,
                'email' => $admin->user_email,
            ));
        }

        return $admins;
    }
}