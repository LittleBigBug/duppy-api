<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy\DuppyServices;

use DI\DependencyException;
use DI\NotFoundException;
use Duppy\Abstracts\AbstractService;
use Duppy\Bootstrapper\Bootstrapper;

final class MailService extends AbstractService {

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
    public function sendMail(string|array $recipients, string $subject, string $content, string $altContent = "", bool $allowReply = false) {
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
     * Renders a template for use of email sending
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
    public function sendMailTemplate(string|array $recipients, string $subject, string $template, array $share = [], string $altContent = "", bool $allowReply = false) {
        $res = $this->renderTemplate($template, $share);
        $this->sendMail($recipients, $subject, $res, $altContent, $allowReply);
    }

}