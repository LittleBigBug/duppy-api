<?php


namespace Duppy\Mods\Forums;


use Duppy\Abstracts\AbstractMod;

class ForumsMod extends AbstractMod
{

    public static function start()
    {
        self::createRouter("forums");
    }

}