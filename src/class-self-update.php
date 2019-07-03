<?php
/**
 * Author: Code2Prog
 * Date: 2019-06-07
 * Time: 18:26
 */

namespace WhatArmy\Watchtower;

/**
 * Class Self_Update
 * @package WhatArmy\Watchtower
 */
class Self_Update
{

    /**
     * Self_Update constructor.
     */
    public function __construct()
    {
        $myUpdateChecker = \Puc_v4_Factory::buildUpdateChecker(
            WHT_REPO_URL,
            WHT_MAIN,
            'whatarmy-watchtower-plugin'
        );

        $myUpdateChecker->setBranch('master');
    }

}