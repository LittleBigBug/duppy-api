<?php
namespace Duppy\Mods\Forums;

/**
 * Duppy Forums - API for the Duppy forums module
 *
 * @package Duppy Forums
 */

use Duppy\Abstracts\AbstractMod;

class ForumsMod extends AbstractMod
{

    public static function start()
    {
        self::createRouter("forums");
    }

}