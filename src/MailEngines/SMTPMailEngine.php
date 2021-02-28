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
use Duppy\DuppyServices\Env;

class SMTPMailEngine extends AbstractMailEngine {

    /**
     * Unique name for this engine
     * @var string
     */
    public static string $name = "smtp";

    /**
     * @param array|string $recipients
     * @param string $subject
     * @param string $content
     * @param string $altContent
     * @param bool $allowReply
     * @throws DependencyException
     * @throws NotFoundException
     */
    function sendMail(array|string $recipients, string $subject, string $content, string $altContent = "", bool $allowReply = false) {
        if (!is_array($recipients)) {
            $recipients = [ $recipients ];
        }

        $mailer = Bootstrapper::getContainer()->get("mailer");

        foreach ($recipients as $recipient) {
            $mailer->addAddress($recipient);
        }

        if ($allowReply) {
            $mailer->addReplyTo(Env::G("EMAIL_REPLYTO"));
        }

        if (str_contains($content, "<") || !empty($altContent)) {
            $mailer->isHTML(true);
        }

        $mailer->Subject = $subject;
        $mailer->Body = $content;
        $mailer->AltBody = $altContent ?? $content;

        $mailer->send();
    }

}