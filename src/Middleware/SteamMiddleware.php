<?php
namespace Duppy\Middleware;

use Duppy\Abstracts\AbstractRouteMiddleware;
use Hybridauth\Provider\Steam;

class SteamMiddleware extends AbstractRouteMiddleware
{

    /**
     * Steam account authentication
     */
    final public function handle()
    {
        $config = [
            'callback' => DUPPY_URI,
            'keys' => [
                'secret' => getenv('STEAM_API_KEY'),
            ],
        ];

        /**
         * https://github.com/hybridauth/hybridauth/blob/master/src/Provider/Steam.php
         * https://github.com/hybridauth/hybridauth/blob/master/src/Adapter/OpenID.php
         *
         * $adapter->isConnected();
         */

        $adapter = new Steam($config);
        self::$request = self::$request->withAttribute("steamAdapter", $adapter);
    }

}