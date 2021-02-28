<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy\Entities;

use DateInterval;
use DateTime;
use Duppy\Enum\LogType;
use JetBrains\PhpStorm\Pure;

/**
 * Log Entity
 *
 * @ORM\Entity
 * @ORM\Table(name="logs")
 */
class Log {

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    protected int $id;

    /**
     * @ORM\Column(type="datetime", nullable=false)
     * @ORM\Version
     */
    protected DateTime $time;

    /**
     * Some logs can be optionally cleaned
     * (Debug logs, rate limiting)
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected ?DateTime $expiry = null;

    /**
     * User associated, if any
     *
     * @ORM\ManyToOne(targetEntity="WebUser", nullable=true)
     */
    protected ?WebUser $user = null;

    /**
     * String identifier for logs
     *
     * @ORM\Column(type="string")
     */
    protected string $message;

    /**
     * String identifier for logs
     *
     * @ORM\Column(type="string")
     */
    protected string $logNote = "";

    /**
     * This could stand being a custom type but its not going to be used anywhere else.
     * A standard "enum" type maybe
     *
     * @ORM\Column(type="string")
     */
    protected string $logType;

    /**
     * @return LogType
     */
    #[Pure]
    public function getLogType(): LogType {
        return new LogType($this->logType);
    }

    /**
     * @param DateTime $time
     */
    public function setTime(DateTime $time) {
        $this->time = $time;
    }

    /**
     * @param DateTime $time
     */
    public function setExpiry(DateTime $time) {
        $this->time = $time;
    }

    /**
     * @param DateInterval $interval
     * @return DateTime Returns the expiry
     */
    public function expireFromNow(DateInterval $interval): DateTime {
        $time = new DateTime;
        $this->expiry = $time->add($interval);
        return $this->expiry;
    }

    /**
     * @param WebUser $user
     */
    public function setUser(WebUser $user) {
        $this->user = $user;
    }

    /**
     * @param string $message
     */
    public function setMessage(string $message) {
        $this->message = $message;
    }

    /**
     * @param LogType $logType
     */
    public function setLogType(LogType $logType) {
        $this->logType = $logType->value;
    }

    /**
     * @param string $logNote
     */
    public function setLogNote(string $logNote) {
        $this->logNote = $logNote;
    }

}