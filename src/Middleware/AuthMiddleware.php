<?php
namespace Duppy\Middleware;

use Duppy\Abstracts\AbstractRouteMiddleware;
use Duppy\Bootstrapper\Settings;
use Hybridauth\Exception\InvalidArgumentException;
use Hybridauth\Hybridauth;

class AuthMiddleware extends AbstractRouteMiddleware {

    /**
     * Steam account authentication
     * @throws InvalidArgumentException
     */
    final public function handle() {
        $config = [
            'callback' => DUPPY_URI,
            'providers' => [
                'Steam' => [
                    'enabled' => Settings::getSetting("auth.steam.enabled"),
                    'keys' => [
                        'secret' => Settings::getSetting("auth.steam.secret"),
                    ],
                ],
            ],
        ];

        /**
         * https://github.com/hybridauth/hybridauth/blob/master/src/Provider/Steam.php
         * https://github.com/hybridauth/hybridauth/blob/master/src/Adapter/OpenID.php
         *
         * $adapter->isConnected();
         */

        $adapter = new Hybridauth($config);
        self::$request = self::$request->withAttribute("authHandler", $adapter);
    }

}
