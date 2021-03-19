<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy\MailEngines;

use DI\DependencyException;
use DI\NotFoundException;
use Duppy\Bootstrapper\Bootstrapper;
use Duppy\Abstracts\AbstractMailEngine;
use Duppy\Bootstrapper\DCache;
use Duppy\DuppyException;
use Duppy\DuppyServices\Settings;

class SMTPMailEngine extends AbstractMailEngine {

    /**
     * Unique name for this engine
     * @var string
     */
    public static string $name = "smtp";

    /**
     * @var DCache
     */
    protected DCache $engineSettings;

    /**
     * ElasticEmailEngine constructor.
     */
    public function __construct() {
        $this->engineSettings = new DCache;
    }

    /**
     * Returns cached mail engine settings
     *
     * @return array|bool
     * @throws DependencyException
     * @throws NotFoundException
     * @throws DuppyException
     */
    public function getEngineSettings(): array|bool {
        if (($engineSettings = $this->engineSettings->get()) != null) {
            return $engineSettings;
        }

        $engineSettings = (new Settings)->getSettings([
            "email.smtp.host", "email.smtp.port", "email.smtp.username", "email.smtp.password",
            "email.from", "email.fromName", "email.replyTo", "email.replyToName",
        ]);

        if (!$this->checkFromSettings($engineSettings)) {
            return false;
        }

        if (empty("email.smtp.host"))

        return $this->engineSettings->setObject($engineSettings);
    }

    /**
     * @param array|string $recipients
     * @param string $subject
     * @param string $content
     * @param string $altContent
     * @param bool $allowReply
     * @return bool
     * @throws DependencyException
     * @throws NotFoundException
     * @throws DuppyException
     */
    function sendMail(array|string $recipients, string $subject, string $content, string $altContent = "", bool $allowReply = false): bool {
        if (!is_array($recipients)) {
            $recipients = [ $recipients ];
        }

        $mailer = Bootstrapper::getContainer()->get("mailer");
        $stgs = $this->getEngineSettings();

        if ($stgs == false) {
            return false;
        }

        $fromEmail = $stgs["email.from"];
        $fromName = $stgs["email.fromName"];
        $replyTo = $stgs["email.replyTo"];
        $replyToName = $stgs["email.replyToName"];

        $mailer->setFrom($fromEmail, $fromName);

        foreach ($recipients as $recipient) {
            $mailer->addAddress($recipient);
        }

        if ($allowReply && !empty($replyTo)) {
            $mailer->addReplyTo($replyTo, $replyToName);
        }

        if (str_contains($content, "<") || !empty($altContent)) {
            $mailer->isHTML(true);
        }

        $mailer->Subject = $subject;
        $mailer->Body = $content;
        $mailer->AltBody = $altContent ?? $content;

        $mailer->send();

        return true;
    }

}