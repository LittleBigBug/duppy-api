<?php
namespace Duppy\Bootstrapper;


class ModCfg
{

    /**
     * Path to the plugin (absolute)
     *
     * @var string
     */
    public string $path;

    /**
     * Path to the plugin relative to src/
     *
     * @var string
     */
    public string $srcPath;

    /**
     * Name of the plugin
     *
     * @var string
     */
    public string $name;

    /**
     * Plugin version
     *
     * @var string
     */
    public string $version;

    /**
     * Plugin author
     *
     * @var string
     */
    public string $author;

    /**
     * Plugin description
     *
     * @var string
     */
    public string $description;

    /**
     * Main mod class that extends from Duppy\Bootstrapper\AbstractMod
     *
     * @var string
     */
    public string $mainClass;

}