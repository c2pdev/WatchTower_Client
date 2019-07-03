<?php
/**
 * Author: Code2Prog
 * Date: 2019-06-07
 * Time: 15:16
 */

namespace WhatArmy\Watchtower;


/**
 * Class Watchtower
 * @package WhatArmy\Watchtower
 */
class Watchtower
{
    /**
     * Watchtower constructor.
     */
    public function __construct()
    {
        $this->load_wp_plugin_class();
        new Password_Less_Access();
        new Download();
        new Api();
        new Backup();
        new Self_Update();
        new Updates_Monitor();

        add_action('admin_menu', [$this, 'add_plugin_page']);
        add_action('admin_init', [$this, 'page_init']);
        add_action('plugins_loaded', [$this, 'check_db']);
        register_activation_hook(WHT_MAIN, [$this, 'install_hook']);
        register_activation_hook(WHT_MAIN, [$this, 'check_db']);
    }

    /**
     *
     */
    public function install_hook()
    {
        wp_clear_scheduled_hook('WHT_cron_hook');
        $token = new Token;
        add_option('watchtower', [
            'access_token' => $token->generate(),
        ]);
        flush_rewrite_rules();
    }

    /**
     *
     */
    public function load_wp_plugin_class()
    {
        if (!function_exists('get_plugins')) {
            require_once ABSPATH.'wp-admin/includes/plugin.php';
        }
    }

    /**
     * @param $version
     */
    public function create_db($version)
    {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix.'watchtower_logs';


        $sql = "CREATE TABLE $table_name (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		action  VARCHAR(255) NOT NULL,
		who smallint(5) NOT NULL,
		created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		UNIQUE KEY id (id)
	) $charset_collate;";

        require_once(ABSPATH.'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        update_option('watchtower_db_version', $version);
    }

    /**
     *
     */
    public function check_db()
    {
        if (get_option('watchtower_db_version') != WHT_DB_VERSION) {
            $this->create_db(WHT_DB_VERSION);
        }
    }

    /**
     * Add options page
     */
    public function add_plugin_page()
    {
        add_options_page(
            'Settings Watchtower',
            'Watchtower Settings',
            'manage_options',
            'watchtower-setting-admin',
            [$this, 'create_admin_page']
        );
    }

    /**
     * Options page callback
     */
    public function create_admin_page()
    {
        $this->options = get_option('watchtower');
        ?>
        <script src="<?php echo plugin_dir_url(__FILE__).'../assets/js/clipboard.js'; ?>"></script>
        <link href="<?php echo plugin_dir_url(__FILE__).'../assets/css/wht_dashboard.css'; ?>" rel="stylesheet"
              type="text/css" media="all">
        <div class="wrap">
            <div class="wht-wrap">
                <h1 class="titled">Watchtower Settings</h1>
                <form method="post" action="options.php">
                    <?php
                    settings_fields('watchtower');
                    do_settings_sections('watchtower-settings');
                    submit_button('Update settings');
                    ?>
                </form>
            </div>
        </div>
        <script>
            let clipboard = new ClipboardJS('.clip');

            clipboard.on('success', function (e) {
                jQuery('#wht-copied').css("display", "flex");
                setTimeout(function () {
                    jQuery('#wht-copied').css("display", "none");
                }, 2000);
            });
        </script>
        <?php
    }

    /**
     * Register and add settings
     */
    public function page_init()
    {
        register_setting(
            'watchtower',
            'watchtower',
            [$this, 'sanitize']
        );

        add_settings_section(
            'access_token_section',
            '',
            [$this, 'access_token_info'],
            'watchtower-settings'
        );

        add_settings_field(
            'access_token',
            'Refresh Token',
            [$this, 'access_token_callback'],
            'watchtower-settings',
            'access_token_section',
            []
        );
    }

    /**
     * @param $input
     *
     * @return array
     */
    public function sanitize(
        $input
    ) {
        $token = new Token;
        $new_input = array();

        if (isset($input['access_token']) && $input['access_token'] == 'true') {
            $new_input['access_token'] = $token->generate();
        } else {
            $new_input['access_token'] = get_option('watchtower')['access_token'];
        }

        return $new_input;
    }

    /**
     *
     */
    public function access_token_info()
    {
        print '<h1 class="centered">Access Token</h1><span class="watchtower_token_area"><span class="watchtower_token_field clip" data-clipboard-text="'.get_option('watchtower')['access_token'].'">'.get_option('watchtower')['access_token'].'<span id="wht-copied">Copied!</span></span></span>';
    }

    /**
     *
     */
    public function access_token_callback()
    {
        printf(
            '<input type="checkbox" value="true" name="watchtower[access_token]" />',
            isset($this->options['access_token']) ? esc_attr($this->options['access_token']) : ''
        );
    }
}