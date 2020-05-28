<?php
namespace Duppy\Abstracts;

use Duppy\Bootstrapper\ModCfg;
use Duppy\Bootstrapper\Router;
use Duppy\Util;

abstract class AbstractMod
{

    /**
     * ModCfg Object associated with this mod
     *
     * @var ModCfg
     */
    public static ModCfg $modInfo;

    /**
     * This mod's router (If any)
     *
     * @var Router|null
     */
    public static Router $router;

    /**
     * Called when the plugin is loaded / started
     */
    abstract public static function start();

    /**
     * Creates and builds a new router object for this mod
     *
     * @param string $rootAppUri
     * @param string $endpointsFolder
     * @return Router
     */
    protected static function createRouter(string $rootAppUri, string $endpointsFolder = 'Endpoints'): Router {
        self::$router = new Router($rootAppUri);
        self::$router->endpointsSrc = Util::combinePath(self::$modInfo->srcPath, $endpointsFolder);
        self::$router->build();

        return self::$router;
    }

}