<?php
defined('ABSPATH') or die('No script kiddies please!');

/**
 * Plugin Name: WhatArmy Watchtower
 * Plugin URI: https://github.com/WhatArmy/WatchtowerWpClient
 * Description: The WhatArmy WordPress plugin allows us to monitor, backup, upgrade, and manage your site!
 * Author: WhatArmy
 * Version: 2.1.2
 * Author URI: https://whatarmy.com
 **/

/**
 * Constants
 */
define('WHT_MAIN', __FILE__);
define('WHT_DB_VERSION', '1.0');

define('WHT_CLIENT_USER_NAME', 'WatchTowerClient');
define('WHT_CLIENT_USER_EMAIL', 'wpdev@whatarmy.com');

define('WHT_BACKUP_EXCLUSIONS_ENDPOINT', '/backupExclusions');
define('WHT_BACKUP_DIR', wp_upload_dir()['basedir'].'/watchtower_backups');
define('WHT_REPO_URL', 'https://github.com/WhatArmy/WatchtowerWpClient');
define('MP_LARGE_DOWNLOADS', true);
define('WHT_BACKUP_FILES_PER_QUEUE', 650);

/**
 * Run App
 */
require_once(plugin_dir_path(WHT_MAIN).'/vendor/prospress/action-scheduler/action-scheduler.php');
require __DIR__.'/vendor/autoload.php';

use ClaudioSanches\WPAutoloader\Autoloader;
use WhatArmy\Watchtower\Watchtower;

$autoloader = new Autoloader();
$autoloader->addNamespace('WhatArmy\Watchtower', __DIR__.'/src');
$autoloader->register();

new Watchtower();
