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
use Duppy\DuppyServices\Logging;

abstract class AbstractMailEngine {

    /**
     * Unique name for this engine
     * @var string
     */
    public static string $name;

    /**
     * Sends mail to recipient(s) using mail engine
     * Returns success
     *
     * @param string|array $recipients
     * @param string $subject
     * @param string $content
     * @param string $altContent
     * @param bool $allowReply
     * @return bool
     */
    public abstract function sendMail(string|array $recipients, string $subject, string $content, string $altContent = "", bool $allowReply = false): bool;

    /**
     * Renders and sends an email to recipient(s) using mail engine
     * Returns success
     *
     * @param string|array $recipients
     * @param string $subject
     * @param string $template
     * @param array $share
     * @param string $altContent
     * @param bool $allowReply
     * @return bool
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function sendMailTemplate(string|array $recipients, string $subject, string $template, array $share = [], string $altContent = "", bool $allowReply = false): bool {
        $res = $this->renderTemplate($template, $share);
        return $this->sendMail($recipients, $subject, $res, $altContent, $allowReply);
    }

    /**
     * Renders a template for use of email sending.
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

    /**
     * @param array $settings
     * @return bool
     */
    protected function checkFromSettings(array &$settings): bool {
        $fromEmail = $settings["email.from"];
        $fromName = $settings["email.fromName"];

        if (empty($fromEmail)) {
            $log = (new Logging)->inst()->Error("email.from setting is empty");
            $log->setLogNote("Email");

            return false;
        }

        if (empty($fromName)) {
            $settings["email.fromName"] = $settings["email.from"];
        }

        $replyTo = $settings["email.replyTo"];
        $replyToName = $settings["email.replyToName"];

        if (!empty($replyTo) && empty($replyToName)) {
            $settings["email.replyToName"] = $replyTo;
        }

        return true;
    }

}