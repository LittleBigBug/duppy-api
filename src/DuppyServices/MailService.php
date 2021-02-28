<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy\DuppyServices;

use DI\DependencyException;
use DI\NotFoundException;
use Duppy\Abstracts\AbstractMailEngine;
use Duppy\Abstracts\AbstractService;
use Duppy\Builders\MailEngineBuilder;
use Duppy\DuppyException;
use Duppy\Enum\LogType;
use Duppy\Util;

final class MailService extends AbstractService {

    /**
     * Current mail engine (cache)
     * @var ?AbstractMailEngine
     */
    protected ?AbstractMailEngine $mailEngine = null;

    /**
     * Dictionary of AbstractMailEngine (string) by their ::$name
     * @var array
     */
    protected array $mailEngines = [];

    /**
     * @return AbstractMailEngine
     * @throws DependencyException
     * @throws NotFoundException
     * @throws DuppyException
     */
    public function getMailEngine(): AbstractMailEngine {
        if ($this->mailEngine != null) {
            return $this->mailEngine;
        }

        (new MailEngineBuilder)->buildOnce();

        $useEngine = (new Settings)->getSetting("email.engine", "smtp");
        $val = Util::indArrayNull($this->mailEngines, $useEngine);

        $engine = new $val;
        return $this->mailEngine = $engine;
    }

    /**
     * @param string $name
     * @param string $className
     */
    public function addMailEngine(string $name, string $className) {
        $this->mailEngines[$name] = $className;
    }

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
     * @throws DuppyException
     */
    public function sendMail(string|array $recipients, string $subject, string $content, string $altContent = "", bool $allowReply = false) {
        $mailEngine = $this->getMailEngine();
        $mailEngine->sendMail($recipients, $subject, $content, $altContent, $allowReply);

        $recipStr = implode(", ", $recipients);
        (new Logging)->inst()->Info("Subject: $subject To $recipStr")->setLogType(LogType::mail());
    }

    /**
     * Renders a template for use of email sending
     *
     * @param string $template
     * @param array $share
     * @return string
     * @throws DependencyException
     * @throws NotFoundException
     * @throws DuppyException
     */
    public function renderTemplate(string $template, array $share = []): string {
        $mailEngine = $this->getMailEngine();
        return $mailEngine->renderTemplate($template, $share);
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
     * @throws DuppyException
     */
    public function sendMailTemplate(string|array $recipients, string $subject, string $template, array $share = [], string $altContent = "", bool $allowReply = false) {
        $res = $this->renderTemplate($template, $share);
        $this->sendMail($recipients, $subject, $res, $altContent, $allowReply);
    }

}