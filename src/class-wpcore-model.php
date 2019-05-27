<?php
/**
 * Created by PhpStorm.
 * User: Myszczyszyn Dawid - Code2Prog
 * Date: 24.01.2016
 * Time: 16:52
 */

namespace Whatarmy_Watchtower;


use Curl;

class WPCore_Model
{

    /**
     * @return array
     */
    static function getStat()
    {
        $stats = array(
            'site_name'         => get_option('blogname'),
            'site_description'  => get_option('blogdescription'),
            'site_url'          => get_site_url(),
            'is_multisite'      => (is_multisite() == true ? 'true' : 'false'),
            'template'          => get_option('template'),
            'wp_version'        => get_bloginfo('version'),
            'admin_email'       => get_option('admin_email'),
            'php_version'       => self::phpVersion(),
            'updates'           => self::checkUpdates(),
            'is_public'         => get_option('blog_public'),
            'installation_size' => self::display_size(self::filesize_recursive(ABSPATH)),
            'comments'          => wp_count_comments(),
            'comments_allowed'  => (get_default_comment_status() == 'open') ? true : false,
            'site_ip'           => self::getExternalIp(),
            'db_size'           => self::getDBSize(),
            'timezone'          => array(
                'gmt_offset'      => get_option('gmt_offset'),
                'string'          => get_option('timezone_string'),
                'server_timezone' => date_default_timezone_get(),
            ),
            'admins_list'       => self::get_admins_list(),
            'admin_url'         => admin_url(),
            'content_dir'       => (defined('WP_CONTENT_DIR')) ? WP_CONTENT_DIR : false,
            'pwp_name'          => (defined('PWP_NAME')) ? PWP_NAME : false,
        );

        return $stats;

    }

    /**
     * @return array|null|object
     */
    static function userLogs()
    {
        global $wpdb;
        $results = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}watchtower_logs ORDER BY id DESC LIMIT 100",
            OBJECT);
        $to_ret = [];
        foreach ($results as $result) {
            $user_info = ($result->who != 0) ? get_userdata($result->who)->user_login : 'Auto Update';
            $result->user_login = $user_info;
            array_push($to_ret, $result);
        }

        return $to_ret;
    }

    static function generate_ota_token()
    {
        $ota_token = 'ota_'.md5(uniqid());
        update_option('watchtower_ota_token', $ota_token);

        return array(
            'ota_token' => $ota_token,
            'admin_url' => admin_url(),
        );
    }

    /**
     * @param $ota_token
     */
    static function sign_in($ota_token)
    {
        if ($ota_token == get_option('watchtower_ota_token')) {
            $random_password = wp_generate_password(30);

            $admins_list = get_users('role=administrator&search=wpdev@whatarmy.com');
            if ($admins_list) {
                reset($admins_list);
                $adm_id = current($admins_list)->ID;
                wp_set_password($random_password, $adm_id);
            } else {
                $adm_id = wp_create_user('WhatarmyDev', $random_password, 'wpdev@whatarmy.com');
                $wp_user_object = new \WP_User($adm_id);
                $wp_user_object->set_role('administrator');
            }

            wp_clear_auth_cookie();
            wp_set_current_user($adm_id);
            wp_set_auth_cookie($adm_id);

            $redirect_to = user_admin_url();
            update_option('watchtower_ota_token', 'not_set');
            wp_safe_redirect($redirect_to);
            exit();
        } else {
            global $wp_query;
            $wp_query->set_404();
            status_header(404);
            get_template_part(404);
            exit();
        }
    }

    /**
     * @return array
     */
    private static function get_admins_list()
    {
        $admins_list = get_users('role=administrator');
        $admins = array();
        foreach ($admins_list as $admin) {
            array_push($admins, array(
                'login' => $admin->user_login,
                'email' => $admin->user_email,
            ));
        }

        return $admins;
    }

    private static function phpVersion()
    {

        preg_match("#^\d+(\.\d+)*#", phpversion(), $match);
        return $match[0];
    }

    /**
     * @return mixed
     */
    private static function getExternalIp()
    {
        $curl = new Curl();
        $curl->options['CURLOPT_SSL_VERIFYPEER'] = false;
        $curl->options['CURLOPT_SSL_VERIFYHOST'] = false;
        $ip = json_decode($curl->get('https://api.ipify.org?format=json'))->ip;

        return $ip;
    }

    /**
     * @return int
     */
    private static function getDBSize()
    {
        global $wpdb;

        $querystr = 'SELECT  ROUND(SUM(((DATA_LENGTH + INDEX_LENGTH)/1024/1024)),2) AS "MB"
        FROM INFORMATION_SCHEMA.TABLES
	WHERE TABLE_SCHEMA = "'.$wpdb->dbname.'";';


        $query = $wpdb->get_row($querystr);

        return $query->MB;
    }

    /**
     * @return array
     */
    private static function checkUpdates()
    {
        global $wp_version;
        do_action("wp_version_check"); // force WP to check its core for updates
        $update_core = get_site_transient("update_core"); // get information of updates

        if ('upgrade' == $update_core->updates[0]->response) {
            require_once(ABSPATH.WPINC.'/version.php');
            $new_core_ver = $update_core->updates[0]->current; // The new WP core version
            $old_core_ver = $wp_version; // the old WP core versions

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

    /**
     * @param $path
     *
     * @return int
     */
    private static function filesize_recursive($path)
    { // Function 1
        $bytestotal = 0;
        $path = realpath($path);
        if ($path !== false && $path != '' && file_exists($path)) {
            foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path,
                \FilesystemIterator::SKIP_DOTS)) as $object) {
                $bytestotal += $object->getSize();
            }
        }
        return $bytestotal;
    }

    /**
     * @param $size
     *
     * @return string
     */
    private static function display_size($size)
    { // Function 2
        $sizes = array('B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
        $retstring = '%01.2f %s';
        if ($retstring === null) {
            $retstring = '%01.2f %s';
        }
        $lastsizestring = end($sizes);
        foreach ($sizes as $sizestring) {
            if ($size < 1024) {
                break;
            }
            if ($sizestring != $lastsizestring) {
                $size /= 1024;
            }
        }
        if ($sizestring == $sizes[0]) {
            $retstring = '%01d %s';
        } // Bytes aren't normally fractional

        return sprintf($retstring, $size, $sizestring);
    }

}