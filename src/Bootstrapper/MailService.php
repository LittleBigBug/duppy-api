<?php

namespace Duppy\Bootstrapper;

use DI\DependencyException;
use DI\NotFoundException;

class MailService {

    /**
     * Basic send mail function
     *
     * @param string|array $recipients
     * @param string $subject
     * @param string $content
     * @param string $altContent
     * @param bool $allowReply
     * @throws DependencyException
     * @throws NotFoundException
     */
    public static function sendMail(string|array $recipients, string $subject, string $content, string $altContent = "", bool $allowReply = false) {
        if (!is_array($recipients)) {
            $recipients = [ $recipients ];
        }

        $mailer = Bootstrapper::getContainer()->get("mailer");

        foreach ($recipients as $recipient) {
            $mailer->addAddress($recipient);
        }

        if ($allowReply) {
            $mailer->addReplyTo(getenv("EMAIL_REPLYTO"));
        }

        if (str_contains($content, "<") || !empty($altContent)) {
            $mailer->isHTML(true);
        }

        $mailer->Subject = $subject;
        $mailer->Body = $content;
        $mailer->AltBody = $altContent ?? $content;

        $mailer->send();
    }

    /**
     * Convenience function to render a template for an email
     *
     * @param string|array $recipients
     * @param string $subject
     * @param string $template
     * @param array $share
     * @param string $altContent
     * @param bool $allowReply
     * @throws DependencyException
     * @throws NotFoundException
     */
    public static function sendMailTemplate(string|array $recipients, string $subject, string $template, array $share, string $altContent = "", bool $allowReply = false) {
        $blade = Bootstrapper::getContainer()->get("templateHandler");

        $res = $blade->setView($template)->share($share)->run();
        MailService::sendMail($recipients, $subject, $res, $altContent, $allowReply);
    }

}