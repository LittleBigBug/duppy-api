<?php

namespace Duppy\Endpoints;

use Duppy\Abstracts\AbstractEndpoint;
use Duppy\Bootstrapper\Settings;
use Duppy\Util;
use Slim\Psr7\Request;
use Slim\Psr7\Response;

class Info extends AbstractEndpoint {

    /**
     * Handles info request
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function __invoke(Request $request, Response $response, array $args = []): Response {
        $settings = Settings::getAppSettings();

        return Util::responseJSON($response, [
            "success" => true,
            "data" => [
                $settings,
            ],
        ]);
    }

}
