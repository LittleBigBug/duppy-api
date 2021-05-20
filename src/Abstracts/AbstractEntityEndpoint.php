<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy\Abstracts;

use DI\DependencyException;
use DI\NotFoundException;
use Doctrine\Common\Collections\Criteria;
use Duppy\Bootstrapper\Bootstrapper;
use Duppy\DuppyException;
use Duppy\DuppyServices\UserService;
use Duppy\Interfaces\IEndpoint;
use Duppy\Util;
use Slim\Psr7\Request;
use Slim\Psr7\Response;

abstract class AbstractEntityEndpoint implements IEndpoint {

    /**
     * Entity classname
     *
     * @var string
     */
    public static string $entityClass;

    /**
     * Only allow logged in users to view
     *
     * @var bool
     */
    public static bool $entityPublic = false;

    /**
     * Permission to read.
     * If null and entityPublic is true, anybody can read public properties of entities
     * If null and entityPublic is false, only logged in users can view
     *
     * @var ?string
     */
    public static ?string $readPermission;

    /**
     * Permission to read individual entity properties marked as 'private'
     * If null, nobody can read unless they have an admin ('admin', '*') permission
     *
     * @var ?string
     */
    public static ?string $readPrivatePermission;

    /**
     * Permission to write (create, update, delete)
     *
     * @var string|null
     */
    public static ?string $writePermission;

    /**
     * Route prefix to add (should use groups but this is still an option)
     *
     * ex: my/prefix/
     *
     * @var string
     */
    public static string $uriPrefix = "";

    /**
     * Endpoint's group parent
     * All $uri s will be relative to parent(s)
     * Classname of a class that inherits the abstract class AbstractEndpointGroup
     *
     * @var string|null
     */
    public static ?string $parentGroup = null;

    /**
     * Route middleware
     *
     * @var array
     */
    public static array $middleware = [];

    /**
     * Properties of the Entity that the endpoint can search for or read in GET requests
     * (Admins, ['admin', '*'] can search by any property)
     *
     * If empty or null, no properties are queryable
     * If true, all properties are queryable
     *
     * @var ?array|true|null
     */
    public static array|bool|null $queryableProperties = null;
    
    /**
     * @var bool 
     */
    public static bool $idPubliclyQueryable = false;

    /**
     * @var ?string
     */
    private static ?string $uri = null;

    /**
     * Fallback invoke (Interface implementation function, not used because of mapped get functions)
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function __invoke(Request $request, Response $response, array $args = []): Response {
        return $response;
    }

    /**
     * Retrieve info about the Entity or retrieve Entities by searches
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     * @throws DependencyException
     * @throws DuppyException
     * @throws NotFoundException
     */
    public function get(Request $request, Response $response, array $args = []): Response {
        $userSrv = (new UserService)->inst();
        $user = $userSrv->getLoggedInUser();

        if (!static::$entityPublic && $user == null) {
            return Util::responseError($response, "Authentication required", 401);
        }

        if (static::$readPermission != null) {
            $hasPerm = $user->hasPermission(static::$readPermission);

            if (!$hasPerm) {
                return Util::responseError($response, "You don't have permission to read this type of resource.", 403);
            }
        }

        $getParams = $request->getQueryParams();

        if (empty($getParams)) {
            return Util::responseError($response, "No query parameters given.", 400);
        }

        // Entity Properties queryable
        $allQueryable = false;
        $queryable = [];

        if (static::$queryableProperties === true) {
            $allQueryable = true;
        } elseif (!empty(static::$queryableProperties)) {
            $queryable = static::$queryableProperties;
        }

        // Search string in GET params
        // (Will also search for allowed properties below)
        $searchString = Util::indArrayNull($getParams, "search");

        // Built search params from request
        $searchParams = [];

        // Non-allowed search params
        $bypass = [
            "search" => true,
        ];

        // Check & parse the search string and combine it with get query params to loop through
        $kvSearch = empty($searchString) ? [] : Util::parseSeparatedKeyValueString($searchString);
        $searchCheck = array_merge($kvSearch, $getParams);

        // Search through all get param keys for properties to search
        foreach ($searchCheck as $key => $param) {
            if (Util::indArrayNull($bypass, $key) == true // Skip all in bypass
                || ($allQueryable && !property_exists(static::$entityClass, $key)) // If all queryable, skip if not a property
                || (!$allQueryable && !in_array($key, $queryable))) { // If not all queryable, skip if not in the queryable array
                continue;
            }

            $searchParams[$key] = $param;
        }

        $dbo = Bootstrapper::getDatabase();
        $repo = $dbo->getRepository(static::$entityClass);

        $expr = Criteria::expr();
        $crt = new Criteria;

        $first = true;

        // Build search criteria
        // Todo; allow different expressions (>= or <= etc)
        foreach ($searchParams as $property => $value) {
            if ($first) {
                $first = false;

                $crt->where($expr->eq($property, $value));
                continue;
            }

            $crt->andWhere($expr->eq($property, $value));
        }

        $objectsMatch = $repo->matching($crt)->toArray();
        return Util::responseJSON($response, [
            "success" => true,
            "data" => $objectsMatch,
        ]);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function put(Request $request, Response $response, array $args = []): Response {

    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function delete(Request $request, Response $response, array $args = []): Response {

    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function patch(Request $request, Response $response, array $args = []): Response {

    }

    /**
     * POST in this case is the same handler as GET
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     * @throws DependencyException
     * @throws DuppyException
     * @throws NotFoundException
     */
    public function post(Request $request, Response $response, array $args = []): Response {
        return self::get($request, $response, $args);
    }

    public static function setUri(array|string|null $newUris) {
        if (is_array($newUris)) {
            $newUris = $newUris[0];
        }

        static::$uri = $newUris;
    }

    public static function getTypes(): array {
        return [ "GET", "PUT", "DELETE", "PATCH", "POST", ];
    }

    /**
     * Returns generated Uri from entity
     *
     * @return ?string[]
     */
    public static function getUri(): ?array {
        if (static::$uri == null) {
            preg_match("/[\/](\w)+$/", static::$entityClass, $matches);

            if (empty($matches)) {
                return null;
            }

            $className = $matches[0];
            $uri = "/";

            if (static::$uriPrefix) {
                $uri .= static::$uriPrefix;
            }

            // Convert PascalCase to kebab-case
            $uri .= strtolower(preg_replace('/([A-Z]+)/', '-$1', $className));

            static::$uri = $uri;
        }

        return [ static::$uri ];
    }

    /**
     * No Uri Func Map
     *
     * @return ?array
     */
    public static function getUriFuncMap(): ?array {
        return null;
    }

    /**
     * No redirecting
     *
     * @return ?array
     */
    public static function getUriRedirectMap(): ?array {
        return null;
    }

    /**
     * Map all HTTP types separately
     *
     * @return true
     */
    public static function getUriMapTypes(): bool {
        return true;
    }

    /**
     * Return specified custom middleware & required middleware
     *
     * @return array
     */
    public static function getMiddleware(): array {
        return static::$middleware;
    }

    /**
     * No mapped middleware (single uri)
     *
     * @return array
     */
    public static function getMappedMiddleware(): array {
        return [];
    }

    /**
     * Returns the assigned parent group if any
     *
     * @return ?string
     */
    public static function getParentGroupEndpoint(): ?string {
        return static::$parentGroup;
    }

}