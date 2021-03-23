<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy;

use DI\DependencyException;
use DI\NotFoundException;
use Duppy\Bootstrapper\Bootstrapper;
use Duppy\DuppyServices\Settings;
use JetBrains\PhpStorm\Pure;
use Psr\Http\Message\ResponseInterface;
use Slim\Psr7\Response;

class Util {

    /**
     * Combines two paths
     *
     * @param string $basePath
     * @param string $combPath
     * @param bool $trailingSlash
     * @return string
     */
    #[Pure]
    public static function combinePath(string $basePath, string $combPath, bool $trailingSlash = false): string {
        return self::combinePaths([$basePath, $combPath], $trailingSlash);
    }

    /**
     * Combines multiple paths
     *
     * @param array $paths
     * @param bool $trailingSlash
     * @return string
     */
    #[Pure]
    public static function combinePaths(array $paths, bool $trailingSlash = false): string {
        $sl = DIRECTORY_SEPARATOR;

        $firstPath = $paths[0];

        for ($i = 0; $i < count($paths); $i++) {
            $path = str_replace('\\', $sl, $paths[$i]);
            $path = str_replace('/', $sl, $path);

            $paths[$i] = trim($path, $sl);
        }

        $pathJoined = join($sl, $paths);
        $pathJoined = preg_replace('~' . $sl . $sl . '+~', $sl, $pathJoined);

        if ($trailingSlash) {
            $pathJoined .= $sl;
        }

        $firstChar = substr($firstPath, 0, 1);

        if ($firstChar == $sl) {
            $pathJoined = $sl . $pathJoined;
        }

        return $pathJoined;
    }

    /**
     * Turns a full system path relative to DUPPY_PATH
     *
     * @param string $path
     * @return string
     */
    #[Pure]
    public static function toProjectPath(string $path): string {
        $newPath = str_replace(DUPPY_PATH, '', $path);
        return trim($newPath, DIRECTORY_SEPARATOR);
    }

    /**
     * Slim v4 JSON request
     *
     * @param ResponseInterface $resp
     * @param array $table
     * @param int $status
     * @return Response
     */
    public static function responseJSON(ResponseInterface &$resp, array $table, int $status = 200): ResponseInterface {
        $now = microtime(true);
        $table["runtime"] = $now - (Bootstrapper::getRequestStart() ?? $now + 1);

        $pl = json_encode($table);
        $resp->getBody()->write($pl);

        $resp = $resp->withHeader("Content-Type", "application/json")->withStatus($status);
        return $resp;
    }

    /**
     * Convenience function for error JSON
     *
     * @param ResponseInterface $resp
     * @param string $error
     * @param int $status
     * @return Response
     */
    public static function responseError(ResponseInterface &$resp, string $error, int $status = 200): ResponseInterface {
        return static::responseJSON($resp, ["success" => false, "err" => $error], $status);
    }

    /**
     * Redirect helper function handling header opt out
     *
     * @param ResponseInterface $resp
     * @param string $url
     * @param array $jsonData
     * @param int $status
     * @param bool $dontRedirect
     * @param string $error
     * @return Response
     */
    public static function responseRedirect(ResponseInterface &$resp, string $url, array $jsonData = [], int $status = 302, bool $dontRedirect = false, string $error = ""): ResponseInterface {
        if ($dontRedirect || !Bootstrapper::currentAllowsRedirect()) {
            $redirectData = [
                "redirectUrl" => $url,
                "httpCode" => $status,
            ];

            $data = array_merge($redirectData, $jsonData);
            $errored = !empty($error);

            return static::responseJSON($resp, [
                "success" => !$errored,
                "message" => "Redirect",
                "data" => $data,
                "err" => $error,
            ]);
        }

        $resp = $resp->withHeader("Location", $url)->withStatus($status);
        return $resp;
    }

    /**
     * Automatically prepend the client url to the redirect
     *
     * @param ResponseInterface $resp
     * @param string $url
     * @param array $jsonData
     * @param int $status
     * @param bool $dontRedirect
     * @param string $error
     * @return Response
     * @throws DuppyException
     * @throws DependencyException
     * @throws NotFoundException
     */
    public static function responseRedirectClient(ResponseInterface &$resp, string $url, array $jsonData = [], int $status = 302, bool $dontRedirect = false, string $error = ""): ResponseInterface {
        $clientUrl = (new Settings)->inst()->getSetting("clientUrl");
        $url = "$clientUrl#/$url";

        $resp = static::responseRedirect($resp, $url, $jsonData, $status, $dontRedirect, $error);
        return $resp;
    }

    /**
     * Converts a boolean dictionary to a regular array by mapping the keys to values if their value is true
     *
     * @param array $dict
     * @return array
     */
    #[Pure]
    public static function boolDictToNormal(array $dict): array {
        $new = [];

        foreach ($dict as $key => $val) {
            if (!$val) {
                continue;
            }

            $new[] = $key;
        }

        return $new;
    }

    /**
     * Validates a $permission against the given $permsDict
     * This will check for wildcards against the dictionary
     * 
     * For Example:
     * some.permission.here
     * some.permission.*
     * some.*
     * *
     *
     * @param array $permsDict
     * @param string $permission
     * @return bool
     */
    #[Pure]
    public static function evaluatePermissionDict(array $permsDict, string $permission): bool {
        // Optimization for tables with only the * perm (no full tree check)
        if (count($permsDict) == 1 && static::indArrayNull($permsDict, "*") === true) {
            return true;
        }

        $eval = static::indArrayNull($permsDict, $permission);

        // Check for explicit evaluations of this permission
        if ($eval != null && gettype($eval) == "boolean") {
            return $eval;
        }

        // Check for wildcards
        // Separate by dots
        $permExplode = explode(".", $permission);
        $segments = count($permExplode);

        // First loop is to reverse thru the array length and $i is the amount that will be subtracted
        // (From the top)
        for ($sub = 1; $sub <= $segments; $sub++) {
            $pCheck = "";

            for ($i = 0; $i <= ($segments - $i); $i++) {
                $str = $permExplode[$i];
                $pCheck .= "$str.";
            }

            $pCheck .= "*";
            $wildEval = static::indArrayNull($permsDict, $pCheck);

            // If any boolean response, return it
            if ($wildEval != null && gettype($wildEval) == "boolean") {
                return $wildEval;
            }
        }

        return false;
    }

    /**
     * Convenience function to get a value out of an array without the PHP warning if the index is null
     * .-.
     * @param ?array $array
     * @param mixed $key
     * @return mixed
     */
    #[Pure]
    public static function indArrayNull(?array $array, mixed $key): mixed {
        if ($array == null) {
            return null;
        }

        if (!array_key_exists($key, $array)) {
            return null;
        }

        return $array[$key];
    }

    /**
     * Returns if the $object is or is a derivative of $class
     *
     * @param object $object
     * @param object|string $class Can be a ::class string or object
     * @return bool
     */
    #[Pure]
    public static function is(object $object, object|string $class): bool {
        $className = $class;

        if (is_object($class)) {
            $className = $class::class;
        }

        return is_subclass_of($object, $className) || $className == $object::class;
    }

}
