<?php
/**
 * Author: Code2Prog
 * Date: 2019-06-07
 * Time: 16:05
 */

namespace WhatArmy\Watchtower;

/**
 * Class Theme
 * @package WhatArmy\Watchtower
 */
class Theme
{
    public function get()
    {
        do_action("wp_update_themes");
        $themes = wp_get_themes();
        $themes_list = array();
        foreach ($themes as $theme_short_name => $theme) {
            array_push($themes_list, array(
                'name'    => $theme['Name'],
                'version' => $theme['Version'],
                'theme'   => $theme_short_name,
                'updates' => $this->check_updates($theme_short_name, $theme['Version']),
            ));
        }

        return $themes_list;
    }

    /**
     * @param $theme
     * @param $current
     * @return array
     */
    private function check_updates($theme, $current)
    {
        $list = get_site_transient('update_themes');

        if (is_array($list->response)) {
            if (array_key_exists($theme, $list->response)) {
                if ($list->response[$theme]['new_version'] != $current) {
                    return array(
                        'required' => true,
                        'version'  => $list->response[$theme]['new_version']
                    );
                } else {
                    return array(
                        'required' => false,
                    );
                }
            } else {
                return array(
                    'required' => false,
                );
            }
        } else {
            return array(
                'required' => false,
            );
        }
    }
}