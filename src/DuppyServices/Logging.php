<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy\DuppyServices;

use DateInterval;
use DateTime;
use DI\DependencyException;
use DI\NotFoundException;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Duppy\Abstracts\AbstractService;
use Duppy\Bootstrapper\Bootstrapper;
use Duppy\Entities\Log;
use Duppy\Entities\WebUser;
use Duppy\Enum\LogType;

/**
 * Logging service
 *
 * Class Logging
 * @package Duppy\DuppyServices
 */
class Logging extends AbstractService {

    /**
     * Log queue
     *
     * @var Log[]
     */
    private array $queue;

    /**
     * Flush the queue if anything
     *
     * @param bool $force
     * @throws DependencyException
     * @throws NotFoundException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function clean(bool $force = false) {
        if (empty($queue)) {
            return;
        }

        $this->Flush();
    }

    /**
     * @param string $message
     * @return Log
     */
    public function Info(string $message): Log {
        $log = $this->Basic($message);
        $log->setLogType(LogType::info());

        return $log;
    }

    /**
     * @param string $message
     * @return Log
     */
    public function Error(string $message): Log {
        $log = $this->Basic($message);
        $log->setLogType(LogType::error());

        return $log;
    }

    /**
     * Creates a basic log and adds it to the queue
     *
     * @param string $message
     * @return Log
     */
    public function Basic(string $message): Log {
        $log = new Log;

        $log->setMessage($message);
        $log->setLogNote("basic");

        $this->queue[] = $log;

        return $log;
    }

    /**
     * Log a user action
     * "Minor actions" are for smaller actions that may be used when errors occur to aid debugging.
     * Its only used to track to reproduce errors to fix them, they auto delete in an hour.
     * Adds the log to the queue
     *
     * @param WebUser $user
     * @param string $action
     * @param bool $minorAction (false) used for debugging purposes and will be deleted soon
     * @return Log
     */
    public function UserAction(WebUser $user, string $action, bool $minorAction = false): Log {
        $log = new Log;

        $log->setMessage("User action: $action");
        $log->setUser($user);
        $log->setLogNote("userAction");
        $log->setLogType(LogType::info());

        if ($minorAction) {
            $interval = new DateInterval("1H");
            $log->expireFromNow($interval);
            $log->setLogType(LogType::temp());
        }

        $this->queue[] = $log;
        return $log;
    }

    /**
     * Flush queue and save it
     *
     * @throws DependencyException
     * @throws NotFoundException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function Flush() {
        $dbo = Bootstrapper::getDatabase();

        foreach ($this->queue as $log) {
            $dbo->persist($log);
        }

        $dbo->flush();
        $this->queue = [];
    }

    /**
     * Clean up old and expired logs
     *
     * @throws DependencyException
     * @throws NotFoundException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function CleanLogs() {
        $dbo = Bootstrapper::getDatabase();
        $logRepo = $dbo->getRepository(Log::class);

        $now = new DateTime;

        $expr = Criteria::expr();
        $crt = new Criteria;
        $crt->where($expr->gte("expiry", $now));
        $crt->orWhere($expr->eq("logType", "temp")); // Or any temp logs

        $match = $logRepo->matching($crt)?->toArray() ?? [];

        foreach ($match as $log) {
            // Double check expiry
            $expire = $log->get("expiry");

            if ($expire == null && $log->getLogType() == LogType::temp()) {
                $time = $log->get("time");
                $interval = new DateInterval("7D"); // Temporary logs only last a week at most
                $tempExpire = $time->add($interval);

                if ($now < $tempExpire) {
                    continue;
                }
            } elseif ($expire == null || $now < $expire) {
                continue;
            }

            $dbo->remove($log);
        }

        $dbo->flush();
    }

}