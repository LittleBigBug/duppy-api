<?php
namespace Duppy\Endpoints\User;

use Duppy\Abstracts\AbstractEndpoint;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class UserData extends AbstractEndpoint
{
    /**
     * Catch / /all and /basic-info
     *
     * GET /user/0/ is an alias to GET /user/0/all and returns all available (public) user data
     * GET /user/0/basic-info returns basic info about the user
     *
     * @var string[]
     */
    public static ?array $uri = [ '/', '/all', '/basic-info' ];

    /**
     * Map /basic-info the use the BasicInfo function in this class.
     * Explanation in UserData::basicInfo
     *
     * @var string[]
     */
    public static ?array $uriFuncNames = [ 2 => 'BasicInfo' ];

    /**
     * Set the parent group classname to 'GroupUser'
     *
     * @var string
     */
    public static ?string $parentGroup = "Duppy\Endpoints\User\GroupUser";

    /**
     * Default invoke method
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function __invoke(Request $request, Response $response, array $args = []): Response
    {
        // Todo ; give full public user info
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
     */
    public function basicInfo(Request $request, Response $response, array $args = []): Response
    {
        // Todo ; give basic user info
        echo('Requested UID; ' . $args['id']);
        return $response;
    }

}