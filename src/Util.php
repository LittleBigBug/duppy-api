<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy;

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
        $table["runtime"] = $now - DUPPY_START;

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
        return static::responseJSON($resp, ["success" => false, "err" => $error]);
    }

    /**
     * Converts a boolean dictionary to a regular array by mapping the keys to values if their value is true
     *
     * @param array $dict
     * @return array
     */
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

}
