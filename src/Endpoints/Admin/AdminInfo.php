<?php

namespace Duppy\Endpoints\Admin;

use Duppy\Abstracts\AbstractEndpoint;
use Duppy\Bootstrapper\Settings;
use Duppy\Util;
use Slim\Psr7\Request;
use Slim\Psr7\Response;

class AdminInfo extends AbstractEndpoint {

    /**
     * Set uri to /info relative to parent
     *
     * @var ?array
     */
    public static ?array $uri = [ '/info' ];

    /**
     * Set the parent group classname to 'GroupAdmin'
     *
     * @var ?string
     */
    public static ?string $parentGroup = "Duppy\Endpoints\Admin\GroupAdmin";

    /**
     * Array of manually created admin pages
     *
     * @var array
     */
    public static array $adminPages = [
        "system" => [
            "dashboard" => [],
            "mods" => [],
            "users" => [
                "users" => [],
                "groups" => [],
            ],
        ],
    ];

    /**
     * Handles info request
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function __invoke(Request $request, Response $response, array $args = []): Response {
        // Merge setting categories with built in categories
        $categories = array_merge(Settings::$categories, static::$adminPages);

        return Util::responseJSON($response, [
            "success" => true,
            "data" => [
                "categories" => $categories,
            ]
        ]);
    }

}