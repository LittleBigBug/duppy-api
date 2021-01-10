<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy\Abstracts;

use DirectoryIterator;
use Duppy\Util;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Scans a folder (optionally recursively) for PHP classes and provides tools for callbacks and filters
 *
 * Class AbstractFileBuilder
 * @package Duppy\Abstracts
 */
abstract class AbstractFileBuilder {

    /**
     * @var string
     */
    protected string $buildSrc = "";

    /**
     * AbstractFileBuilder constructor.
     * @param string $buildSrc
     */
    public function __construct(string $buildSrc) {
        $this->buildSrc = $buildSrc;
    }

    abstract public function build();

    /**
     * DirectoryIterator helper
     *
     * @param bool $recursive
     * @param string|null $src
     * @param callable|null $callback
     * @param callable|null $filter
     * @return array|null
     */
    public function directoryIterator(bool $recursive = false, callable $callback = null, callable $filter = null, ?string $src = null): array|null {
        $src = $src ?? $this->buildSrc;
        $srcFullPath = Util::combinePaths([DUPPY_PATH, "src", $src], true);

        $iterator = null;

        // Create iterators based on if recursive is defined
        if ($recursive) {
            $dirIterator = new RecursiveDirectoryIterator($srcFullPath);
            $recursiveIterator = new RecursiveIteratorIterator($dirIterator);

            $iterator = &$recursiveIterator;
        } else {
            $dirIterator = new DirectoryIterator($srcFullPath);

            $iterator = &$dirIterator;
        }

        $returnArray = [];

        // Default callback function if callback is null
        $callbackNull = $callback == null;
        $callback = $callback ?? function(string $className, string $path) use (&$returnArray) {
            // By default, return results in an array.
            $returnArray[] = [
                "path" => $path,
                "className" => $className,
            ];
        };

        // Default filter function if filter is null (just allow everything)
        $filter = $filter ?? function(string $className, string $path): bool { return true; };

        // Iterate over everything
        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }

            // Get pathname
            $path = $file->getRealPath() ?: $file->getPathname();

            // Check file extension
            if (pathinfo($path, PATHINFO_EXTENSION) !== "php") {
                continue;
            }

            $path = str_replace(".php", "", $path);

            // Get class name from the path
            $classPath = substr(Util::toProjectPath($path), strlen("src/"));
            $class = "Duppy\\" . str_replace("/", "\\", $classPath);

            // Ask filter
            if (!$filter($class, $classPath)) {
                continue;
            }

            $callback($class, $classPath);
        }

        // If the callback was specified, it won't ever return a populated array
        if (!$callbackNull) {
            return null;
        }

        return $returnArray;
    }

}