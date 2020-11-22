<?php
namespace Duppy\Middleware;

use Duppy\Abstracts\AbstractRouteMiddleware;
use Duppy\Bootstrapper\Settings;
use Hybridauth\Exception\InvalidArgumentException;
use Hybridauth\Hybridauth;

class AuthMiddleware extends AbstractRouteMiddleware {

    /**
     * Account authentication (HybridAuth)
     * @throws InvalidArgumentException
     */
    final public function handle() {
        $authSettings = Settings::getSettings([
            "auth.steam.enable", "auth.steam.secret",
            "auth.facebook.enable", "auth.facebook.id", "auth.facebook.secret",
            "auth.google.enable", "auth.google.id", "auth.google.secret",
        ]);

        $config = [
            'callback' => DUPPY_URI,
            'providers' => [
                'Steam' => [
                    'enabled' => $authSettings["auth.steam.enable"],
                    'keys' => [
                        'secret' => $authSettings["auth.steam.secret"],
                    ],
                ],
                'Facebook' => [
                    'enabled' => $authSettings["auth.facebook.enable"],
                    'keys' => [
                        'id' => $authSettings["auth.facebook.id"],
                        'secret' => $authSettings["auth.facebook.secret"],
                    ],
                ],
                'Google' => [
                    'enabled' => $authSettings["auth.google.enable"],
                    'keys' => [
                        'id' => $authSettings["auth.google.id"],
                        'secret' => $authSettings["auth.google.secret"],
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
