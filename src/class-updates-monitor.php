<?php
/**
 * Author: Code2Prog
 * Date: 2019-06-13
 * Time: 17:46
 */

namespace WhatArmy\Watchtower;


class Updates_Monitor
{

    /**
     * Updates_Monitor constructor.
     */
    public function __construct()
    {
        add_action('_core_updated_successfully', array(&$this, 'core_updated_successfully'));
        add_action('activated_plugin', array(&$this, 'hooks_activated_plugin'));
        add_action('deactivated_plugin', array(&$this, 'hooks_deactivated_plugin'));
        add_action('upgrader_process_complete', array(&$this, 'hooks_plugin_install_or_update'), 10, 2);
    }

    /**
     * Insert Logs to DB
     *
     * @param $data
     */
    private function insertLog($data)
    {
        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix.'watchtower_logs',
            array(
                'action'     => $data['action'],
                'who'        => $data['who'],
                'created_at' => date('Y-m-d H:i:s')
            )
        );
    }

    /**
     * Core Update
     *
     * @param $wp_version
     */
    public function core_updated_successfully($wp_version)
    {
        global $pagenow;

        // Auto updated
        if ('update-core.php' !== $pagenow) {
            $object_name = 'WordPress Auto Updated |'.$wp_version;
            $who = 0;
        } else {
            $object_name = 'WordPress Updated | '.$wp_version;
            $who = get_current_user_id();
        }

        $this->insertLog(array(
            'who'    => $who,
            'action' => $object_name,
        ));

    }


    /**
     * @param $action
     * @param $plugin_name
     */
    protected function _add_log_plugin($action, $plugin_name)
    {
        // Get plugin name if is a path
        if (false !== strpos($plugin_name, '/')) {
            $plugin_dir = explode('/', $plugin_name);
            $plugin_data = array_values(get_plugins('/'.$plugin_dir[0]));
            $plugin_data = array_shift($plugin_data);
            $plugin_name = $plugin_data['Name'];
        }

        $this->insertLog(array(
            'who'    => get_current_user_id(),
            'action' => $action.' '.$plugin_name,
        ));
    }

    /**
     * @param $plugin_name
     */
    public function hooks_deactivated_plugin($plugin_name)
    {
        $this->_add_log_plugin('Deactivated', $plugin_name);
    }

    /**
     * @param $plugin_name
     */
    public function hooks_activated_plugin($plugin_name)
    {
        $this->_add_log_plugin('Activated', $plugin_name);
    }

    /**
     * @param $upgrader
     * @param $extra
     */
    public function hooks_plugin_install_or_update($upgrader, $extra)
    {
        if (!isset($extra['type']) || 'plugin' !== $extra['type']) {
            return;
        }

        if ('install' === $extra['action']) {
            $path = $upgrader->plugin_info();
            if (!$path) {
                return;
            }

            $data = get_plugin_data($upgrader->skin->result['local_destination'].'/'.$path, true, false);

            $this->insertLog(array(
                'who'    => get_current_user_id(),
                'action' => 'Installed Plugin: '.$data['Name'].' | Ver.'.$data['Version'],
            ));
        }

        if ('update' === $extra['action']) {
            if (isset($extra['bulk']) && true == $extra['bulk']) {
                $slugs = $extra['plugins'];
            } else {
                if (!isset($upgrader->skin->plugin)) {
                    return;
                }

                $slugs = array($upgrader->skin->plugin);
            }

            foreach ($slugs as $slug) {
                $data = get_plugin_data(WP_PLUGIN_DIR.'/'.$slug, true, false);

                $this->insertLog(array(
                    'who'    => get_current_user_id(),
                    'action' => 'Updated Plugin: '.$data['Name'].' | Ver.'.$data['Version'],
                ));
            }
        }
    }
}