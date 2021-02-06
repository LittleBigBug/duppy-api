<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy\Abstracts;

use Duppy\Bootstrapper\Bootstrapper;
use DI\DependencyException;
use DI\NotFoundException;

abstract class AbstractMailEngine {

    /**
     * Unique name for this engine
     * @var string
     */
    public static string $name;

    /**
     * Sends mail to recipient(s) using mail engine
     *
     * @param string|array $recipients
     * @param string $subject
     * @param string $content
     * @param string $altContent
     * @param bool $allowReply
     */
    abstract function sendMail(string|array $recipients, string $subject, string $content, string $altContent = "", bool $allowReply = false);

    /**
     * Renders a template for use of email sending.
     * Can be overridden to use an API or something instead.
     *
     * @param string $template
     * @param array $share
     * @return string
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function renderTemplate(string $template, array $share = []): string {
        $blade = Bootstrapper::getContainer()->get("templateHandler");
        return $blade->setView($template)->share($share)->run();
    }

}