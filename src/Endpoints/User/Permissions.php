<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy\Endpoints\User;

use DI\DependencyException;
use DI\NotFoundException;
use Duppy\DuppyServices\UserService;
use Slim\Psr7\Request;
use Slim\Psr7\Response;

use Duppy\Abstracts\AbstractEndpoint;

class Permissions extends AbstractEndpoint {

    /**
     * GET /user/0/permissions returns the user's set permissions
     * GET /user/0/permissions/inherited returns ALL the user's permissions
     * Permissions based on environment
     *
     * @var string[]
     */
    public static ?array $uri = [ '/permissions', '/permissions/inherited', ];

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
    public static ?array $uriFuncNames = [ 1 => 'inherited' ];

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
     * @throws NotFoundException
     */
    public function inherited(Request $request, Response $response, array $args = []): Response {
        $userId = $args["id"];
        $user = (new UserService)->inst()->getUser($userId);



        return $response;
    }

}