<?php
namespace Duppy\Abstracts;

use Duppy\Bootstrapper\ModCfg;
use Duppy\Bootstrapper\Router;
use Duppy\Bootstrapper\Settings;
use Duppy\Util;

abstract class AbstractMod {

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
    public static ?Router $router = null;

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
        static::$router = new Router('/' . $rootAppUri);
        static::$router->endpointsSrc = Util::combinePath(static::$modInfo->srcPath, $endpointsFolder);
        static::$router->build();

        return static::$router;
    }

    /**
     * Creates and builds the setting definitions for this mod
     *
     * @param string $rootAppUri
     */
    protected static function registerSettings(string $rootAppUri) {
        $settingDefinitions = new Settings("/" . $rootAppUri);
        $settingDefinitions->build();
    }

}
