<?php
/**
 * Created by JetBrains PhpStorm.
 * User: maciek
 * Date: 15.02.14
 * Time: 22:11
 * To change this template use File | Settings | File Templates.
 */

namespace Src\Main\Lib;


class GravatarClient
{
    public static function getGravatar()
    {
        $gravatar = new \emberlabs\GravatarLib\Gravatar();
        $gravatar->setDefaultImage('mm');
        $gravatar->setAvatarSize(50);
        $gravatar->setMaxRating('pg');

        return $gravatar;
    }
}