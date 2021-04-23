<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy\MailEngines;

use DI\DependencyException;
use DI\NotFoundException;
use Duppy\Util;
use Duppy\DuppyException;
use Duppy\Abstracts\AbstractMailEngine;
use Duppy\Bootstrapper\DCache;
use Duppy\DuppyServices\Env;
use Duppy\DuppyServices\Logging;
use Duppy\DuppyServices\Settings;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class ElasticEmailEngine extends AbstractMailEngine {

    /**
     * Unique name for this engine
     * @var string
     */
    public static string $name = "elasticemail";

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

        $defaultApiUrl = "https://api.elasticemail.com";

        $engineSettings = (new Settings)->getSettings([
            "elasticEmail.url",
            "email.from", "email.fromName", "email.replyTo", "email.replyToName",
        ]);

        if (!$this->checkFromSettings($engineSettings)) {
            return false;
        }

        if (empty($engineSettings["elasticEmail.url"])) {
            $engineSettings["elasticEmail.url"] = $defaultApiUrl;
        }

        return $this->engineSettings->setObject($engineSettings);
    }

    /**
     * Sends a POST request to send a transactional email using ElasticEmail.
     *
     * $params already contains:
     * apikey from env, isTransactional = true, msgTo = ($to)
     * from and fromName also auto filled with settings
     *
     * Documentation:
     * https://api.elasticemail.com/public/help#Email_Send
     *
     * @param string|array $to
     * @param array $params
     * @param bool $allowReplyTo
     * @return bool
     * @throws DependencyException
     * @throws DuppyException
     * @throws GuzzleException
     * @throws NotFoundException
     */
    public function sendElasticEmail(string|array $to, array $params, bool $allowReplyTo = false): bool {
        $apiKey = Env::G("ELASTICEMAIL_APIKEY");

        if (empty($apiKey)) {
            return false;
        }

        $stgs = $this->getEngineSettings();

        if ($stgs == false) {
            return false;
        }

        $fromEmail = $stgs["email.from"];
        $fromName = $stgs["email.fromName"];
        $replyTo = $stgs["email.replyTo"];
        $replyToName = $stgs["email.replyToName"];

        $apiUrl = $stgs["elasticEmail.url"];

        $cl = new Client;

        if (is_array($to)) {
            $to = implode(";", $to);
        }

        $params["apikey"] = $apiKey;
        $params["isTransactional"] = true;
        $params["msgTo"] = $to;

        // From email
        $params["from"] = $fromEmail;
        $params["sender"] = $fromEmail;
        $params["msgFrom"] = $fromEmail;

        $params["fromName"] = $fromName;
        $params["senderName"] = $fromName;
        $params["msgFromName"] = $fromName;

        if ($allowReplyTo) {
            $params["replyTo"] = $replyTo;
            $params["replyToName"] = $replyToName;
        }

        $response = $cl->post("$apiUrl/v2/email/send", [
            "form_params" => $params,
        ]);

        $body = $response->getBody();
        $bodyStr = $body->getContents();
        $json = json_decode($bodyStr);

        if ($json == null || !is_array($json)) {
            $log = (new Logging)->inst()->Error("Failed to send Email using ElasticEmail, JSON contents missing or invalid");
            $log->setLogNote("ElasticEmail API");

            return false;
        }

        $success = Util::indArrayNull($json, "success") ?? false;

        if (!$success) {
            $error = Util::indArrayNull($json, "error") ?? "";

            $log = (new Logging)->inst()->Error("Failed to send Email using ElasticEmail: $error");
            $log->setLogNote("ElasticEmail API");

            return false;
        }

        return true;
    }

    /**
     * Send regular mail using elastic mail
     *
     * @param array|string $recipients
     * @param string $subject
     * @param string $content
     * @param string $altContent
     * @param bool $allowReply
     * @return bool
     * @throws DependencyException
     * @throws DuppyException
     * @throws GuzzleException
     * @throws NotFoundException
     */
    public function sendMail(array|string $recipients, string $subject, string $content, string $altContent = "", bool $allowReply = false): bool {
        if (is_array($recipients) && count($recipients) > 1) {
            return false;
        }

        $params = [
            "bodyHtml" => $content,
            "bodyText" => $altContent,
            "subject" => $subject,
        ];

        return $this->sendElasticEmail($recipients, $params, $allowReply);
    }

    /**
     * Send a template email using elastic email
     *
     * @param string|array $recipients
     * @param string $subject
     * @param string $template
     * @param array $share
     * @param string $altContent
     * @param bool $allowReply
     * @return bool
     * @throws DependencyException
     * @throws DuppyException
     * @throws GuzzleException
     * @throws NotFoundException
     */
    public function sendMailTemplate(string|array $recipients, string $subject, string $template, array $share = [], string $altContent = "", bool $allowReply = false): bool {
        if (is_array($recipients) && count($recipients) > 1) {
            return false;
        }

        $params = [
            "subject" => $subject,
            "template" => $template,
        ];

        // https://help.elasticemail.com/en/articles/2376732-how-to-manage-transactional-templates
        foreach ($share as $key => $val) {
            $params["merge_$key"] = $val;
        }

        return $this->sendElasticEmail($recipients, $params, $allowReply);
    }

}