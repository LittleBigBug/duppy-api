<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy\Builders;

use Duppy\Abstracts\AbstractFileBuilder;
use Duppy\Abstracts\AbstractMailEngine;
use Duppy\DuppyServices\MailService;
use JetBrains\PhpStorm\Pure;

class MailEngineBuilder extends AbstractFileBuilder {

    /**
     * Mail Engine builder constructor.
     *
     * @param string $mailEnginesSrc
     */
    #[Pure]
    public function __construct(string $mailEnginesSrc = "MailEngines") {
        parent::__construct($mailEnginesSrc);
    }

    /**
     * Mail Engine builder
     */
    public function build() {
        $mailService = (new MailService)->inst();

        $callback = function (string $class, string $path) use ($mailService) {
            if (!is_subclass_of($class, AbstractMailEngine::class)) { return; } // For IDE
            $key = $class::$name;

            if (!isset($key)) {
                return;
            }

            $mailService->addMailEngine($key, $class);
        };

        $filter = function (string $className, string $path): bool {
            return is_subclass_of($className, AbstractMailEngine::class);
        };

        $this->directoryIterator(true, $callback, $filter);
    }

}