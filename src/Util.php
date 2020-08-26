<?php
namespace Duppy;

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
            $path = str_replace('/', $sl, $paths[$i]);
            $paths[$i] = trim($path, $sl);
        }

        $pathJoined = join($sl, $paths);

        if ($trailingSlash) {
            $pathJoined .= $sl;
        }

        $firstChar = substr($firstPath, 0, 1);

        if ($firstChar == $sl || $firstChar == "/") {
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
     * @param Response $resp
     * @param array $table
     * @return Response
     */
    public static function responseJSON(Response $resp, array $table): Response {
        $pl = json_encode($table);
        $resp->getBody()->write($pl);

        return $resp->withHeader("Content-Type", "application/json")->withStatus(201);
    }

}
