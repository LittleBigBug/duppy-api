<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy\Abstracts;

use DateTime;
use DI\DependencyException;
use DI\NotFoundException;
use Duppy\DuppyException;
use Duppy\DuppyServices\Settings;
use Duppy\DuppyServices\UserService;
use Duppy\Entities\ApiClient;
use Duppy\Entities\WebUser;
use Duppy\Interfaces\IDuppyUser;
use JetBrains\PhpStorm\Pure;

abstract class DuppyUser implements IDuppyUser {


    /**
     * Returns if the User is an API Client
     *
     * @return bool
     */
    #[Pure]
    public function isWebUser(): bool {
        return get_class($this) instanceof WebUser;
    }

    /**
     * Returns if the User is an API Client
     *
     * @return bool
     */
    #[Pure]
    public function isAPIClient(): bool {
        return get_class($this) instanceof ApiClient;
    }

    /**
     * If the user is the current logged in user, or owns the logged in api client
     *
     * @return bool
     * @throws DependencyException
     * @throws DuppyException
     * @throws NotFoundException
     */
    #[Pure]
    public function isMe(): bool {
        $loggedInUser = (new UserService)->inst()->getLoggedInUser();

        if ($this == $loggedInUser) {
            return true;
        }

        if ($loggedInUser->isAPIClient()) {
            $owner = $loggedInUser->getOwner();

            if ($owner == $this) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param IDuppyUser $otherUser
     * @return bool
     * @throws DependencyException
     * @throws NotFoundException
     * @throws DuppyException
     */
    #[Pure]
    public function weightCheck(IDuppyUser $otherUser): bool {
        $oWeight = $otherUser->getWeight();
        $myWeight = $this->getWeight();

        $eq = (new Settings)->inst()->getSetting("equalWeightPasses") && ($myWeight >= $oWeight);
        return $eq || $myWeight > $oWeight;
    }

    /**
     * If the user is banned at all (within the current environment or globally)
     *
     * @return bool
     */
    #[Pure]
    public function banned(): bool {
        return $this->environmentBanned() || $this->globalBanned();
    }

    /**
     * Check if the user has an active ban within the environment
     *
     * @return bool
     */
    #[Pure]
    public function environmentBanned(): bool {
        $bans = $this->getActiveBans();

        foreach ($bans as $ban) {
            if (!$ban->isActive() || !$ban->inThisEnvironment()) {
                continue;
            }

            return true;
        }

        return false;
    }

    /**
     * Check if the user has an active global ban
     *
     * @return bool
     * @throws DependencyException
     * @throws NotFoundException
     * @throws DuppyException
     */
    #[Pure]
    public function globalBanned(): bool {
        $anyGlobal = $this->hasDirectGlobalBan();

        if ($anyGlobal) {
            return true;
        }

        $activeBans = $this->getActiveBans();
        $active = count($activeBans);

        if ($active < 1) { return false; }

        $stgManager = (new Settings)->inst();

        if ($this->permaBanned() && $stgManager->getSetting("permaGlobalBan", false)) {
            return true;
        }

        // If the amount of active bans (on this environment + others)
        $max = $stgManager->getSetting("autoGlobalBan", 2);
        return $active >= $max;
    }

    /**
     * @return bool
     */
    #[Pure]
    public function hasDirectGlobalBan(): bool {
        $bans = $this->getActiveBans();

        foreach ($bans as $ban) {
            if ($ban->isGlobal()) {
                return true;
            }
        }

        return false;
    }

    /**
     * If the user has any permanent bans.
     *
     * @return bool
     */
    #[Pure]
    public function permaBanned(): bool {
        $bans = $this->getActiveBans();

        foreach ($bans as $ban) {
            if ($ban->isPermanent()) {
                return true;
            }
        }

        return false;
    }

    /**
     * If the user has any permanent bans in this environment.
     *
     * @return bool
     */
    #[Pure]
    public function permaBannedEnvironment(): bool {
        $bans = $this->getActiveBans();

        foreach ($bans as $ban) {
            if ($ban->isPermanent() && $ban->inThisEnvironment()) {
                return true;
            }
        }

        return false;
    }

    /**
     * If the user has any permanent bans in this environment.
     *
     * @return bool
     */
    #[Pure]
    public function permaBannedGlobal(): bool {
        $bans = $this->getActiveBans();

        foreach ($bans as $ban) {
            if ($ban->isPermanent() && $ban->isGlobal()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns the earliest date the user will be unbanned from the current context
     * Returns null if no ban or false if permanent
     *
     * @return DateTime|null|false
     */
    #[Pure]
    public function unbanTime(): DateTime|bool|null {
        $environmentUnbanTime = $this->environmentUnbanTime();
        $globalUnbanTime = $this->globalUnbanTime();

        if ($environmentUnbanTime == null && $globalUnbanTime == null) {
            return null;
        } elseif ($environmentUnbanTime === false || $globalUnbanTime === false) {
            return false;
        }

        if ($environmentUnbanTime > $globalUnbanTime) {
            return $environmentUnbanTime;
        }

        return $globalUnbanTime;
    }

    /**
     * Gets the date when this user's ban will be lifted. (In this environment, not global)
     * Returns null if no ban or false if permanent
     *
     * @return DateTime|null|false
     */
    #[Pure]
    public function environmentUnbanTime(): DateTime|bool|null {
        if ($this->permaBannedEnvironment()) {
            return false;
        }

        $date = null;
        $bans = $this->getActiveBans();

        foreach ($bans as $ban) {
            $expiry = $ban->get("expiry");

            // Use this ban's date if it is within the current environment (not global)
            // and only use it if its bigger (or the first) than the stored $date (latest they can be unbanned)
            if ($ban->inThisEnvironment() && !$ban->isGlobal() && ($date == null || $expiry > $date)) {
                $date = $expiry;
            }
        }

        return $date;
    }

    /**
     * Gets the date when this user's ban will be lifted. (Globally)
     * Returns null if no ban or false if permanent
     *
     * @return DateTime|null|false
     */
    #[Pure]
    public function globalUnbanTime(): DateTime|bool|null {
        if ($this->permaBannedGlobal()) {
            return false;
        }

        $stgManager = (new Settings)->inst();

        if ($this->permaBanned() && $stgManager->getSetting("permaGlobalBan", false)) {
            return false;
        }

        $date = null;
        $bans = $this->getActiveBans();
        $ct = count($bans);

        $max = $stgManager->getSetting("autoGlobalBan", 2);
        $diffNeeded = 0;

        if ($ct >= $max) {
            // Amount of bans needed to go away for the auto globalban to go away
            $diffNeeded = 1 + ($ct - $max);
        }

        $minSort = ($diffNeeded > 0);
        $minTab = [];

        foreach ($bans as $ban) {
            $expiry = $ban->get("expiry");

            if ($minSort) {
                $minTab[] = $expiry;
            }

            // Use this ban's date if it is within the current environment (not global)
            // and only use it if its bigger (or the first) than the stored $date (latest they can be unbanned)
            if ($ban->isGlobal() && ($date == null || $expiry > $date)) {
                $date = $expiry;
            }
        }

        // Automatic ban time
        if ($minSort) {
            usort($minTab, function($a, $b) {
                if ($a == $b) {
                    return 0;
                }

                return $a < $b ? -1 : 1;
            });

            $autoGUnbanTime = $minTab[$diffNeeded];

            if ($autoGUnbanTime > $date) {
                $date = $autoGUnbanTime;
            }
        }

        return $date;
    }

}