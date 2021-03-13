<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy\Interfaces;

use DateTime;
use Duppy\Entities\Ban;
use Duppy\Entities\PermissionAssignment;
use JetBrains\PhpStorm\Pure;

interface IDuppyUser {

    /**
     * Returns if the user has access to the permission
     *
     * @param string $permission
     * @return bool
     */
    #[Pure]
    public function hasPermission(string $permission): bool;

    /**
     * Returns all permissions set to this user, including inherited
     * 
     * Returns an array as a dictionary, with keys as the permission name equating to a boolean if the user has that permission
     * If $dictionary is false, it returns a regular array of all permissions that the user has
     *
     * @param bool $dictionary = true
     * @return array
     */
    #[Pure]
    public function getPermissions(bool $dictionary = true): array;

    /**
     * Returns only permissions explicitly set/given to this user.
     *
     * @return PermissionAssignment[]
     */
    #[Pure]
    public function getExplicitPermissions(): array;

    /**
     * Returns if the user is the currently logged in one
     *
     * @return bool
     */
    #[Pure]
    public function isMe(): bool;

    /**
     * Returns the user's weight as an integer. (Usually the highest weight out of their groups)
     *
     * @return int
     */
    #[Pure]
    public function getWeight(): int;

    /**
     * Returns true if the user has more weight (or equal, if enabled) than $otherUser
     *
     * @param IDuppyUser $otherUser
     * @return bool
     */
    #[Pure]
    public function weightCheck(IDuppyUser $otherUser): bool;

    /**
     * Returns all active bans on this user
     *
     * @return Ban[]
     */
    #[Pure]
    public function getActiveBans(): array;

    /**
     * Returns if the user is banned within the context
     *
     * @return bool
     */
    #[Pure]
    public function banned(): bool;

    /**
     * Returns if the user is banned within the current environment
     *
     * @return bool
     */
    #[Pure]
    public function environmentBanned(): bool;

    /**
     * Returns if the user is global banned. (Automatically or Manually)
     *
     * @return bool
     */
    #[Pure]
    public function globalBanned(): bool;

    /**
     * Returns if the user is manually global banned
     *
     * @return bool
     */
    #[Pure]
    public function hasDirectGlobalBan(): bool;

    /**
     * Returns if the user has any permanent bans
     *
     * @return bool
     */
    #[Pure]
    public function permaBanned(): bool;

    /**
     * Returns if the user has any permanent bans in this environment
     *
     * @return bool
     */
    #[Pure]
    public function permaBannedEnvironment(): bool;

    /**
     * Returns the earliest date the user will be unbanned from the current context
     * Returns null if no ban or false if permanent
     *
     * @return DateTime|null|false
     */
    #[Pure]
    public function unbanTime(): DateTime|bool|null;

    /**
     * Returns the earliest date the user will be unbanned from this environment (not affected by global ban)
     * Returns null if no ban or false if permanent
     *
     * @return DateTime|null|false
     */
    #[Pure]
    public function environmentUnbanTime(): DateTime|bool|null;

    /**
     * Returns the earliest date the user will be unbanned globally
     * Returns null if no ban or false if permanent
     *
     * @return DateTime|null|false
     */
    #[Pure]
    public function globalUnbanTime(): DateTime|bool|null;

}