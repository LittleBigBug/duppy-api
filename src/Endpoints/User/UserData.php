<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy\Endpoints\User;

use DI\DependencyException;
use DI\NotFoundException;
use Duppy\Abstracts\AbstractEndpoint;
use Duppy\Bootstrapper\Bootstrapper;
use Duppy\DuppyServices\UserService;
use Duppy\Util;
use Slim\Psr7\Request;
use Slim\Psr7\Response;

class UserData extends AbstractEndpoint {

    /**
     * Catch / /all and /basic-info
     *
     * GET /user/0/ is an alias to GET /user/0/all and returns all available (public) user data
     * GET /user/0/basic returns basic info about the user
     *
     * @var string[]
     */
    public static ?array $uri = [ '/', '/all', '/basic' ];

    /**
     * Allow get and post
     *
     * @var string[]
     */
    public static array $types = [ 'GET', 'POST' ];

    /**
     * Map all to 1 function
     *
     * @var array|boolean
     */
    public static array|bool $uriMapTypes = false;

    /**
     * Map /basic-info the use the BasicInfo function in this class.
     * Explanation in UserData::basicInfo
     *
     * @var string[]
     */
    public static ?array $uriFuncNames = [ 2 => 'basicInfo' ];

    /**
     * Set the parent group classname to 'GroupUser'
     *
     * @var ?string
     */
    public static ?string $parentGroup = "Duppy\Endpoints\User\GroupUser";

    /**
     * If its configured to, only allow logged in users to view this
     *
     * @var array
     */
    public static array $middleware = [ "Duppy\Middleware\AuthRequiredSettingMiddleware" ];

    /**
     * Default invoke method
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function __invoke(Request $request, Response $response, array $args = []): Response {
        $userId = $args["id"];
        $user = (new UserService)->inst()->getUser($userId);



        return $response;
    }

    /**
     * This function will be called when /basic-info is requested.
     * This is because we specified the 2nd index of $uriFuncNames (same index as /basic-info in $uri)
     * to "BasicInfo". If we specified multiple types of Request types, then it would be prepended to the func name
     * getBasicInfo, postBasicInfo (unless we specify those request types to be mapped)
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     * @throws DependencyException
     */
    public function basicInfo(Request $request, Response $response, array $args = []): Response {
        $userId = $args["id"];
        $user = null;

        $userService = (new UserService)->inst();

        try {
            $user = $userService->getUser($userId);
        } catch (NotFoundException) { }

        if ($user == null) {
            return Util::responseError($response, "User not found.");
        }

        $data = [
            "success" => true,
            "data" => $userService->getBasicInfo($user),
        ];

        return Util::responseJSON($response, $data);
    }

}
