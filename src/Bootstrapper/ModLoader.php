<?php
namespace Duppy\Bootstrapper;

use Duppy\Util;
use Yosymfony\Toml\Exception\ParseException;
use Yosymfony\Toml\Toml;

final class ModLoader {

    /**
     * Map of mods
     *
     * @var array
     */
    public static array $mods = [];

    /**
     * Build mods
     */
    public static function build(): void {
        $iterator = new \DirectoryIterator(Util::combinePaths([DUPPY_PATH, "src", "Mods"], true));

        foreach ($iterator as $file) {
            // Check if file is a directory
            if (!$file->isDir() || $file->isDot()) {
                continue;
            }

            // Get pathname
            $path = $file->getRealPath() ?: $file->getPathname();

            // Parse Toml cfg
            try {
                $modInfo = Toml::parseFile(Util::combinePath($path, 'info.toml'));
            } catch (ParseException $exception) {
                error_log('AbstractMod info.toml not found or invalid -> ' . $exception->getMessage(), 0);
                continue;
            }

            if (!is_array($modInfo) || empty($modInfo) || !array_key_exists('mod', $modInfo)) {
                error_log('AbstractMod info.toml not found or invalid', 0);
                continue;
            }

            $name = $modInfo['mod']['name'];
            $class = $modInfo['mod']['class'];
            $version = $modInfo['mod']['version'];

            if (array_key_exists('info', $modInfo)) {
                $desc = $modInfo['info']['description'];
                $author = $modInfo['info']['author'];
            }

            if (!is_subclass_of($class, 'Duppy\Abstracts\AbstractMod')) {
                error_log('AbstractMod info specified an invalid class name', 0);
                continue;
            }

            $modCfg = new ModCfg;

            $modCfg->path = $path;
            $modCfg->srcPath = Util::combinePath('Mods', $file->getFilename());

            $modCfg->name = $name;
            $modCfg->mainClass = $class;
            $modCfg->version = $version;

            $modCfg->description = $desc ?? '';
            $modCfg->author = $author ?? 'Unknown';

            self::$mods[] = $modCfg;
            $class::$modInfo = $modCfg;

            $class::start();
        }
    }

}
