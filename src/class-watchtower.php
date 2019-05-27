<?php
/**
 * Created by PhpStorm.
 * User: Myszczyszyn Dawid - Code2Prog
 * Date: 23.01.2016
 * Time: 16:31
 */

namespace Whatarmy_Watchtower;


class Watchtower
{
    /**
     * Holds the values to be used in the fields callbacks
     */
    private $options;

    /**
     * Start up
     */
    public function __construct()
    {
        add_action('admin_menu', array($this, 'add_plugin_page'));
        add_action('admin_init', array($this, 'page_init'));
        //add_action( 'wp_dashboard_setup', array( $this, 'add_dashboard_widgets' ) );
    }

    public function dashboard_widget_function($post, $callback_args)
    {
        $rss = fetch_feed('https://whatarmy.com/feed/');

        $maxitems = 0;

        if (!is_wp_error($rss)) :
            $maxitems = $rss->get_item_quantity(8);
            $rss_items = $rss->get_items(0, $maxitems);

        endif;
        ?>
        <ul>
        <?php if ($maxitems == 0) : ?>
        <li><?php _e('Nothing to show!', 'my-text-domain'); ?></li>
    <?php else : ?>
        <?php foreach ($rss_items as $item) : ?>
            <li>
                <a href="<?php echo esc_url($item->get_permalink()); ?>" target="_blank"
                   title="<?php printf(__('Posted %s', 'my-text-domain'), $item->get_date('j F Y | g:i a')); ?>">
                    <?php echo esc_html($item->get_title()); ?>
                </a>
            </li>
        <?php endforeach; ?>
    <?php endif; ?>
        </ul><?php
    }

    public function add_dashboard_widgets()
    {
        wp_add_dashboard_widget('dashboard_widget', 'Whatarmy Tips & Tricks', array(
            $this,
            'dashboard_widget_function'
        ));
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
            array($this, 'create_admin_page')
        );
    }

    /**
     * Options page callback
     */
    public function create_admin_page()
    {

        $this->options = get_option('watchtower');
        ?>
        <script src="https://cdn.jsdelivr.net/npm/clipboard@2/dist/clipboard.min.js"></script>
        <div class="wrap">
            <style>
                .centered {
                    text-align: center;
                }

                .titled {
                    font-size: 30px !important;
                    color #333 !important;
                    margin-bottom: 30px!important;
                }

                td {
                    text-align: right;
                }

                #wht-copied {
                    color: #fff;
                    position: absolute;
                    top: 0;
                    left: 0;
                    width: 100%;
                    text-align: center;
                    background: rgba(0, 0, 0, 0.39);
                    font-weight: bold;
                    -webkit-border-radius: 10px;
                    -moz-border-radius: 10px;
                    border-radius: 10px;
                    height: 100%;
                    display: none;
                    align-items: center;
                    justify-content: center;
                }

                .wrap {
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }

                .wht-wrap {
                    max-width: 700px;
                    background: #fff;
                    padding: 10px 30px 10px 30px;
                }

                .watchtower_token_field {
                    position: relative;
                    display: inline-block;
                    background: #ffea96 !important;
                    border: solid 1px #333;
                    -webkit-border-radius: 10px;
                    -moz-border-radius: 10px;
                    border-radius: 10px;
                    padding: 6px 20px;
                !important;
                    cursor: pointer;
                    margin-left: 20px;

                    display: flex;
                    flex-direction: column;
                    justify-content: center;
                }

                .watchtower_token_area, .watchtower_token_field {
                    font-size: 20px;
                    padding: 10px;
                }

                .watchtower_token_area {
                    margin: auto;
                    float: left;
                    padding-left: 0px;
                }
            </style>
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
            var clipboard = new ClipboardJS('.clip');

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
            array($this, 'sanitize')
        );

        add_settings_section(
            'access_token_section',
            '',
            array($this, 'access_token_info'),
            'watchtower-settings'
        );

        add_settings_section(
            'backups_section',
            'Backups',
            array($this, 'backups_info'),
            'watchtower-settings'
        );

        add_settings_field(
            'file_backup',
            'File Backup',
            array($this, 'backups_callback'),
            'watchtower-settings',
            'backups_section',
            array()
        );

        add_settings_field(
            'access_token',
            'Refresh Token',
            array($this, 'access_token_callback'),
            'watchtower-settings',
            'access_token_section',
            array()
        );
    }

    /**
     * @param $input
     *
     * @return array
     */
    public function sanitize($input)
    {
        $new_input = array();
        if (isset($input['file_backup'])) {
            $new_input['file_backup'] = $input['file_backup'];
        }

        if (isset($input['access_token']) && $input['access_token'] == 'true') {
            $new_input['access_token'] = Token::generateToken();
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

    public function backups_info()
    {
//        $state = (get_option('watchtower')['file_backup'] == 1) ? "Enabled" : "Disabled";
//        print '<span class="watchtower_token_area">File Backup: <span class="">'.$state.'</span></span>';
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

    public function backups_callback()
    {
        $one = (get_option('watchtower')['file_backup'] == 1) ? "selected" : "";
        $two = (get_option('watchtower')['file_backup'] == 0) ? "selected" : "";
        printf(
            '<select name="watchtower[file_backup]">
<option value="1" '.$one.'>Enabled</option>
<option value="0" '.$two.'>Disabled</option>
</select>',
            isset($this->options['file_backup']) ? esc_attr($this->options['file_backup']) : ''
        );
    }
}
