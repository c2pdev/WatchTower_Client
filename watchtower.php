<?php
/**
 * Plugin Name: Whatarmy Watchtower
 * Plugin URI: https://github.com/c2pdev/WatchTower_Client
 * Description: The WhatArmy WordPress plugin allows us to monitor, backup, upgrade, and manage your site!
 * Author: Whatarmy
 * Version: 1.5.2
 * Author URI: http://whatarmy.com
 **/

define('WATCHTOWER_DB_VERSION', '1.0');

if (version_compare(PHP_VERSION, '5.4', '<')) {
    wp_die('PHP 5.4 or better is required.');
}
/**
 * Disable Editing files in dashboard
 */
define('DISALLOW_FILE_EDIT', true);

/**
 * Composer dependencies autoload
 */
require 'vendor/autoload.php';

define('WHT_BACKUP_DIR', wp_upload_dir()['basedir'] . '/watchtower_backups');
define('WHT_HEADQUARTER_BACKUP_ENDPOINT', 'https://watchtower.whatarmy.com/backup');
define('WHT_HEADQUARTER_BACKUP_EX', 'https://watchtower.whatarmy.com/backupExclusions');

define("WHT_CHUNK_SIZE", 1024*1024);
/**
 * UPDATE Plugin
 */
require 'plugin-update-checker/plugin-update-checker.php';
$myUpdateChecker = Puc_v4p3_Factory::buildUpdateChecker(
    'https://github.com/c2pdev/WatchTower_Client',
    __FILE__,
    'whatarmy-watchtower-plugin'
);

$myUpdateChecker->setBranch('master');

/**
 * Register autoload SRC
 */
register_wp_autoload('Whatarmy_Watchtower\\', __DIR__ . '/src');

/**
 * Include Plugin Class
 */
if (!function_exists('get_plugins')) {
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
}
/**
 * Activation Hook
 */
register_activation_hook(__FILE__, array('\Whatarmy_Watchtower\Install_Uninstall', 'install'));
register_activation_hook(__FILE__, array('\Whatarmy_Watchtower\Install_Uninstall', 'watchtower_create_db'));

function WHT_update_db_check()
{
    if (get_site_option('watchtower_db_version') != WATCHTOWER_DB_VERSION) {
        \Whatarmy_Watchtower\Install_Uninstall::watchtower_create_db(WATCHTOWER_DB_VERSION);
    }
}

add_action('plugins_loaded', 'WHT_update_db_check');

/**
 * RUN API ENDPOINT
 */
new \Whatarmy_Watchtower\Watchtower_API_Endpoint();

/**
 * Run Watchtower main class
 */
new \Whatarmy_Watchtower\Watchtower();

/**
 * Hooks Logs
 */
new \Whatarmy_Watchtower\Watchtower_Hook_Base();


/**
 * @param $update
 * @param $item
 *
 * @return bool
 */
function auto_update_specific_plugins($update, $item)
{
    $plugins = array(
        'whatarmy_watchtower/watchtower.php',
    );
    if (in_array($item->slug, $plugins)) {
        return true;
    } else {
        return $update;
    }
}

add_filter('auto_update_plugin', 'auto_update_specific_plugins', 10, 2);

/**
 * BACKUPS
 */

/**
 * @param int $length
 *
 * @return string
 */
function WhtGenerateRandomString($length = 12)
{
    $characters = '0123456789abcdefghijklmnopqrstuvwxyz';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }

    return $randomString;
}

function WhtCreateBackupDIR()
{
    if (!file_exists(WHT_BACKUP_DIR)) {
        mkdir(WHT_BACKUP_DIR, 0777, true);
    }
    if ((!is_dir(WHT_BACKUP_DIR) ||
            !is_file(WHT_BACKUP_DIR . '/index.html') ||
            !is_file(WHT_BACKUP_DIR . '/.htaccess')) &&
        !is_file(WHT_BACKUP_DIR . '/index.php') ||
        !is_file(WHT_BACKUP_DIR . '/web.config')) {
        @mkdir(WHT_BACKUP_DIR, 0775, true);
        @file_put_contents(WHT_BACKUP_DIR . '/index.html', "<html><body><a href=\"https://whatarmy.com\">WordPress backups by Watchtower</a></body></html>");
        if (!is_file(WHT_BACKUP_DIR . '/.htaccess')) {
            @file_put_contents(WHT_BACKUP_DIR . '/.htaccess', 'deny from all');
        }
        if (!is_file(WHT_BACKUP_DIR . '/web.config')) {
            @file_put_contents(WHT_BACKUP_DIR . '/web.config', "<configuration>\n<system.webServer>\n<authorization>\n<deny users=\"*\" />\n</authorization>\n</system.webServer>\n</configuration>\n");
        }
    }
}

/**
 * @return string
 * @throws Exception
 */
function WhtRunDbBackup()
{
    WhtCreateBackupDIR();
    $backup_name = date('Y_m_d__H_i_s') . "_" . WhtGenerateRandomString();


    $dump = new MySQLDump(new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME));
    $dump->save(WHT_BACKUP_DIR . '/' . $backup_name . '.sql.gz');

    if (get_option('watchtower')['file_backup'] == 1) {
        $file_backup = new HM_Backup();
        $file_backup->set_type('file');
        $file_backup->set_archive_filename($backup_name . '.zip');
        $file_backup->set_path(WHT_BACKUP_DIR);
        $file_backup->set_excludes(WHTgetExclusions());
        $file_backup->backup();
    }


    return $backup_name;
}

function WHTisJson($string)
{
    json_decode($string);
    return (json_last_error() == JSON_ERROR_NONE);
}

function WHTgetExclusions()
{
    $arrContextOptions = array(
        "ssl" => array(
            "verify_peer"      => false,
            "verify_peer_name" => false,
        ),
    );
    $data = file_get_contents(WHT_HEADQUARTER_BACKUP_EX, false, stream_context_create($arrContextOptions));
    $ret = array();
    if (WHTisJson($data)) {
        foreach (json_decode($data) as $d) {
            $p = '';
            if ($d->isContentDir == true) {
                $p = WP_CONTENT_DIR . '/' . $d->path;
            } else {
                $p = $d->path;
            }
            array_push($ret, $p);
        }
    }
    return $ret;
}

/**
 * @param $schedules
 *
 * @return mixed
 */
function WHT_custom_cron_schedule($schedules)
{
    $schedules['daily'] = array(
        'interval' => 86400, // Every day
        'display'  => __('Daily'),
    );

    return $schedules;
}

add_filter('cron_schedules', 'WHT_custom_cron_schedule');

if (!wp_next_scheduled('WHT_cron_hook')) {
    wp_schedule_event(time(), 'daily', 'WHT_cron_hook');
}

add_action('WHT_cron_hook', 'WHT_cron_function');

/**
 * @throws Exception
 */
function WHT_cron_function()
{
    WHTclearOldBackups();
    $backup = WhtRunDbBackup();
    callWHTHeadquarter($backup);
}

/**
 * @param $files
 */
function WHTdelDir($files)
{
    foreach ($files as $file) {
        if (is_file($file)) {
            unlink($file);
        }
    }
}

/**
 *
 */
function WHTclearOldBackups()
{
    $files = glob(WHT_BACKUP_DIR . '/*.gz');
    WHTdelDir($files);

    $files_zip = glob(WHT_BACKUP_DIR . '/*.zip');
    WHTdelDir($files_zip);
}

/**
 * @param $backup_name
 */
function callWHTHeadquarter($backup_name)
{
    $curl = new Curl();
    $curl->options['CURLOPT_SSL_VERIFYPEER'] = false;
    $curl->options['CURLOPT_SSL_VERIFYHOST'] = false;
    $curl->get(WHT_HEADQUARTER_BACKUP_ENDPOINT, array(
        'access_token' => get_option('watchtower')['access_token'],
        'backup_name'  => $backup_name
    ));
}
