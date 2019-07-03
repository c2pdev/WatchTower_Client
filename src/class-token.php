<?php
/**
 * Author: Code2Prog
 * Date: 2019-06-07
 * Time: 18:16
 */

namespace WhatArmy\Watchtower;

/**
 * Class Token
 * @package WhatArmy\Watchtower
 */
class Token
{
    public function generate()
    {
        return md5(uniqid());
    }

    public function get()
    {
        return get_option('watchtower_token');
    }
}