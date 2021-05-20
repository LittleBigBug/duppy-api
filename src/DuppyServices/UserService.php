<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy\DuppyServices;

use DateTime;
use Exception;
use DateInterval;
use DI\DependencyException;
use DI\NotFoundException;
use Slim\Psr7\Response;
use Hybridauth\User\Profile;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\TransactionRequiredException;
use Duppy\Util;
use Duppy\DuppyException;
use Duppy\Enum\DuppyError;
use Duppy\Bootstrapper\Bootstrapper;
use Duppy\Abstracts\AbstractEmailWhitelist;
use Duppy\Abstracts\AbstractService;
use Duppy\Abstracts\DuppyUser;
use Duppy\Entities\ApiClient;
use Duppy\Entities\Environment;
use Duppy\Entities\PasswordResetRequest;
use Duppy\Entities\PermissionAssignment;
use Duppy\Entities\UserGroup;
use Duppy\Entities\WebUser;
use Duppy\Entities\WebUserVerification;

/**
 * User helper and utility functions
 *
 * Class UserService
 * @package Duppy\DuppyServices
 */
final class UserService extends AbstractService {

    /**
     * Convenience function to get a user by their ID
     *
     * @param null $id
     * @param bool $apiClient = false
     * @return ?DuppyUser
     * @throws DependencyException
     * @throws DuppyException
     * @throws NotFoundException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws TransactionRequiredException
     */
    public function getUser($id = null, $apiClient = false): ?DuppyUser {
        if ($id == "me" || $id == null) {
            return $this->inst()->getLoggedInUser();
        }

        $dbo = Bootstrapper::getDatabase();
        return $dbo->find($apiClient ? ApiClient::class : WebUser::class, $id);
    }

    /**
     * Convenience function to get an APIClient by its ID
     *
     * @param $id
     * @return ?ApiClient
     * @throws DependencyException
     * @throws NotFoundException
     * @throws DuppyException
     */
    public function getApiClient($id = null): ?ApiClient {
        $user = $this->getUser($id, true);

        if (!($user instanceof ApiClient)) {
            return null;
        }

        return $user;
    }

    /**
     * Convenience function to get a user by their Username
     *
     * @param string $username
     * @return ?WebUser
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function getUserByName(string $username): ?WebUser {
        $dbo = Bootstrapper::getDatabase();
        return $dbo->getRepository(WebUser::class)->findOneBy([ "username" => $username ]);
    }

    /**
     * Convenience function to get a user by their Email
     *
     * @param string $email
     * @return ?WebUser
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function getUserByEmail(string $email): ?WebUser {
        $dbo = Bootstrapper::getDatabase();
        return $dbo->getRepository(WebUser::class)->findOneBy([ "email" => $email ]);
    }

    /**
     * Create a new user with their email and hashed password.
     * The username will automatically be created from the email before the @
     *
     * @param string $email
     * @param string $password
     * @param bool $persist
     * @return ?WebUser
     * @throws DependencyException
     * @throws NotFoundException
     * @throws ORMException
     */
    public function createUser(string $email, string $password, bool $persist = true): ?WebUser {
        $user = new WebUser;

        // Steam style
        // bob.minecraft2006
        $lastAt = strrpos($email, "@");
        $genUsername = substr($email, 0, $lastAt);

        $user->setUsername($genUsername);

        $user->setEmail($email);
        $user->setPassword($password);
        $user->setCrumb("");

        if ($persist) {
            $dbo = Bootstrapper::getDatabase();
            $dbo->persist($user);
            $dbo->flush();
        }

        return $user;
    }

    /**
     * Returns a response with the WebUser's credentials or a redirect to login
     *
     * @param Response $response
     * @param WebUser $user
     * @param bool $redirect
     * @return Response
     * @throws DependencyException
     * @throws DuppyException
     * @throws NotFoundException
     * @throws ORMException
     */
    public function loginUser(Response $response, WebUser $user, bool $redirect = false): Response {
        if ($user == null) {
            $error = "No matching user";
            $url = "login/error/$error";

            return Util::responseRedirectClient($response, $url, dontRedirect: !$redirect, error: $error);
        }

        $userId = $user->get("id");
        $username = $user->get("username");
        $avatar = $user->get("avatarUrl");

        $data = [
            "id" => $userId,
            "username" => $username,
            "avatarUrl" => $avatar,
        ];

        $token = (new TokenManager)->inst()->createTokenFill($data);
        $crumb = hash("sha256", $token);

        $user->setCrumb($crumb);

        $dbo = Bootstrapper::getDatabase();
        $dbo->persist($user);
        $dbo->flush();

        (new Logging)->inst()->UserAction($user, "Login");

        $url = "login/success/$token/$crumb/$userId";
        return Util::responseRedirectClient($response, $url, [
            "token" => $token,
            "crumb" => $crumb,
            "user" => $data,
        ], dontRedirect: !$redirect);
    }

    /**
     * Checks if an email address is in use by someone or not.
     *
     * @param string $email
     * @return bool
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function emailTaken(string $email): bool {
        $dbo = Bootstrapper::getDatabase();
        $ct = $dbo->getRepository(WebUser::class)->count([ 'email' => $email, ]);
        $ct += (new UserService)->inst()->emailNeedsVerification($email);

        return $ct > 0;
    }

    /**
     * Checks if an email address is registered but needs verification.
     *
     * @param string $email
     * @return bool
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function emailNeedsVerification(string $email): bool {
        $dbo = Bootstrapper::getDatabase();
        $vCt = $dbo->getRepository(WebUserVerification::class)->count([ 'email' => $email, ]);

        return $vCt > 0;
    }

    /**
     * Convenience function to get the current logged in user or API Client
     *
     * @return ?DuppyUser
     * @throws DependencyException
     * @throws NotFoundException
     * @throws DuppyException
     */
    public function getLoggedInUser(): ?DuppyUser {
        $tokenManager = (new TokenManager)->inst();

        // Try JWT (WebUser) first
        $authToken = $tokenManager->getJWToken();

        if ($authToken == null || !array_key_exists("id", $authToken)) {
            // Try APIClient authentication
            $apiClient = $tokenManager->getAPIClient();

            if ($apiClient != null) {
                return $apiClient;
            }

            return null;
        }

        return $this->getUser($authToken["id"]);
    }

    /**
     * Gets the currently used EmailWhitelist class name, or null if its not enabled.
     *
     * @return ?string
     * @throws DependencyException
     * @throws NotFoundException
     * @throws DuppyException
     */
    public static function getEmailWhitelist(): ?string {
        $whitelistClass = (new Settings)->inst()->getSetting("auth.emailWhitelist");
        $subclass = is_subclass_of($whitelistClass, AbstractEmailWhitelist::class);

        return $subclass ? $whitelistClass : null;
    }

    /**
     * Returns if the user email is on the whitelist
     * @param string $email
     * @return bool
     * @throws DependencyException
     * @throws NotFoundException
     * @throws DuppyException
     */
    public function emailWhitelisted(string $email): bool {
        $whitelistClass = $this->getEmailWhitelist();

        // Whitelist not enabled
        if (!is_subclass_of($whitelistClass, AbstractEmailWhitelist::class)) {
            return true;
        }

        return $whitelistClass::check($email);
    }

    /**
     * Takes a string reference to a provider name and corrects it, and also checks if it is enabled.
     *
     * @param string $provider
     * @return boolean
     * @throws DependencyException
     * @throws NotFoundException
     * @throws DuppyException
     */
    public function enabledProvider(string &$provider): bool {
        if (!isset($provider) || empty($provider)) {
            $provider = "password";
        }

        return (new Settings)->inst()->getSetting("auth.$provider.enable") == true;
    }

    /**
     * Authenticate with hybridauth, either directly or using a pre-given oAuth token
     *
     * @param string $provider
     * @param array|null $postArgs
     * @return Profile|string
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function authenticateHybridAuth(string $provider, ?array $postArgs = []): Profile|string {
        $authHandler = Bootstrapper::getContainer()->get('authHandler');

        $oAuthToken = Util::indArrayNull($postArgs, "oAuthToken");
        $oAuthTokenSecret = Util::indArrayNull($postArgs, "oAuthTokenSecret");
        $refreshToken = Util::indArrayNull($postArgs, "refreshToken");
        $expiry = Util::indArrayNull($postArgs, "tokenExpiry");

        if ($authHandler::class !== "Hybridauth\Hybridauth") {
            return "Dependency error";
        }

        $adapter = $authHandler->getAdapter($provider);

        if ($oAuthToken !== null) {
            $adapter->setAccessToken([
                "expires_at" => $expiry,
                "access_token" => $oAuthToken,
                "access_token_secret" => $oAuthTokenSecret,
                "refresh_token" => $refreshToken,
            ]);
        } else {
            $authHandler->authenticate($provider);
            $connected = $authHandler->isConnectedWith($provider);

            if (!$connected) {
                return "Provider auth error";
            }
        }

        $profile = null;

        if ($adapter->isConnected()) {
            $profile = $adapter->getUserProfile();
        }

        return $profile;
    }

    /**
     * A unique 6-digit code for verifying registrations (or other things)
     *
     * @param $checker
     * @param int $loopProtection
     * @return ?int
     */
    public function generateUniqueTempCode($checker = null, int $loopProtection = 0): ?int {
        if (++$loopProtection > 200) {
            return null;
        }

        $min = 100000;
        $max = 999999;

        try {
            $intGen = random_int($min, $max);
        } catch (Exception) {
            return null;
        }

        if ($checker == null) {
            $checker = function(int $check) use ($min, $max) {
                return $check >= $min && $check <= $max;
            };
        }

        // elp
        if (!$checker($intGen)) {
            return $this->generateUniqueTempCode($checker, $loopProtection);
        }

        return $intGen;
    }

    /**
     * Returns basic info of the user
     *
     * @param WebUser $user
     * @return array
     * @throws DependencyException
     * @throws DuppyException
     * @throws NotFoundException
     */
    public function getBasicInfo(WebUser $user): array {
        $data = [
            "id" => $user->get("id"),
            "username" => $user->get("username"),
            "avatarUrl" => $user->get("avatarUrl"),
        ];

        if ($user->isMe()) {
            $merge = [
                "email" => $user->get("email"),
            ];

            $data = array_merge($data, $merge);
        }

        return $data;
    }

    /**
     * Creates a new PermissionAssignment and adds it to a group or user
     *
     * @param WebUser|UserGroup $userOrGroup
     * @param string $permission
     * @param Environment|null $environment
     * @param bool $persistNow
     * @throws DependencyException
     * @throws NotFoundException
     * @throws ORMException
     */
    public function givePermission(WebUser|UserGroup $userOrGroup, string $permission, ?Environment $environment = null, bool $persistNow = true) {
        $permissionA = new PermissionAssignment;
        $permissionA->setPermission($permission);

        if ($environment != null) {
            $permissionA->setEnvironment($environment);
        }

        if (Util::is($userOrGroup, WebUser::class)) {
            $permissionA->setUser($userOrGroup);
        } else {
            $permissionA->setGroup($userOrGroup);
        }

        // Both classes implement this function
        $userOrGroup->addPermission($permissionA);

        if ($persistNow) {
            $dbo = Bootstrapper::getDatabase();

            $dbo->persist($permissionA);
            $dbo->flush();
        }
    }

    /**
     * Compares the logged in user to the other user $id. If they arent the same, it checks the logged in users permission and checks for
     * 'admin', '*', and $overridePerm
     *
     * If the user $id is the same as the logged in user, it checks if they have $regPerm, if not set it just returns true
     *
     * @param int $id
     * @param string $overridePerm
     * @param string $regPerm
     * @return bool
     * @throws DependencyException
     * @throws NotFoundException
     * @throws DuppyException ErrType noneFound if the $id is not associated with an existing user
     */
    public function loggedInUserAgainstUserPerm(int $id, string $overridePerm, string $regPerm = ""): bool {
        // Don't use $this here, this code is tested and needs to use the singleton for the below functions.
        // See AbstractService.php
        $userService = (new UserService)->inst();
        $user = $userService->getUser($id);
        $loggedInUser = $userService->getLoggedInUser();

        if ($user == null) {
            throw new DuppyException(DuppyError::noneFound());
        }

        // Nobody is logged in
        if ($loggedInUser == null) {
            return false;
        }

        $checkId = null;

        if ($user instanceof ApiClient) {
            $owner = $user->getOwner();

            if ($owner instanceof WebUser) {
                $checkId = $owner->get("id");
            }
        } else {
            $checkId = $user->get("id");
        }

        if ($checkId !== $loggedInUser->get("id")) {
            // Permission to check
            if (!$loggedInUser->hasPermission($overridePerm) || !$loggedInUser->weightCheck($user)) {
                return false;
            }
        } elseif ($regPerm != "" && !$loggedInUser->hasPermission($regPerm)) {
            return false;
        }

        return true;
    }

    /**
     * Returns if the password provided passes password requirements
     *
     * @param string $password
     * @param string &$error Set to an error explaining whats wrong with the password
     * @return bool
     * @throws DependencyException
     * @throws DuppyException
     * @throws NotFoundException
     */
    public function securePassword(string $password, string &$error = ""): bool {
        $settings = (new Settings)->inst()->getSettings([
            "auth.password.minLength", "auth.password.minSpecial",
            "auth.password.minUppercase", "auth.password.minLowercase",
        ]);

        $min = $settings["auth.password.minLength"];
        $minSpecial = $settings["auth.password.minSpecial"];
        $minUpper = $settings["auth.password.minUppercase"];
        $minLower = $settings["auth.password.minLowercase"];

        if ($minSpecial > 0) {
            $specialCt = preg_match_all("/[^\w\s]/g", $password);

            if ($specialCt < $minSpecial) {
                $error = "$minSpecial minimum special characters required.";
                return false;
            }
        }

        if ($minUpper > 0) {
            $upperCt = preg_match_all("/[A-Z]/g", $password);

            if ($upperCt < $minUpper) {
                $error = "$minUpper minimum uppercase characters required.";
                return false;
            }
        }

        if ($minLower > 0) {
            $lowerCt = preg_match_all("/[a-z]/g", $password);

            if ($lowerCt < $minLower) {
                $error = "$minLower minimum lowercase characters required.";
                return false;
            }
        }

        if (strlen($password) < $min) {
            $error = "$min password length required.";
            return false;
        }

        return true;
    }

    /**
     * Checks all matching password requests.
     * This deletes any matching request and returns if any request was valid
     *
     * @param string $code
     * @param string|int|null $userId
     * @return bool
     * @throws DependencyException
     * @throws NotFoundException
     * @throws DuppyException ErrType noneFound if the userId doesnt belong to anyone, or incorrectType if the setting somehow returns something other than a DateInterval
     * @throws ORMException
     */
    public function checkPasswordResetCode(string $code, string|int $userId = null): bool {
        $requests = $this->getActivePasswordRequests($userId);

        if (!is_array($requests)) {
            return false;
        }

        $dbo = Bootstrapper::getDatabase();

        $expireTime = (new Settings)->inst()->getSetting("auth.password.expire", "2H");

        $foundValid = false;
        $now = new DateTime;

        foreach ($requests as $request) {
            if (!Util::is($request, PasswordResetRequest::class)) {
                continue;
            }

            $reqCode = $request->get("code");
            
            if (!password_verify($code, $reqCode)) {
                continue;
            }

            // Mark any matching request for removal
            $dbo->remove($request);

            $time = $request->get("time");

            if (!Util::is($time, DateInterval::class)) {
                continue;
            }

            $expire = $time->add($expireTime);

            if ($now > $expire) {
                continue;
            }

            $foundValid = true;
        }

        return $foundValid;
    }

    /**
     * Get all pending password requests for a user (even expired)
     * Returns false on api user or no requests
     *
     * @param string|int $userId
     * @return array|bool
     * @throws DependencyException
     * @throws DuppyException
     * @throws NotFoundException
     */
    public function getUserPasswordRequests(string|int $userId): array|bool {
        $user = $this->inst()->getUser($userId);

        if ($user == null) {
            throw new DuppyException(DuppyError::noneFound());
        } elseif ($user instanceof ApiClient) {
            return false;
        }

        $dbo = Bootstrapper::getDatabase();
        $repo = $dbo->getRepository(PasswordResetRequest::class);

        $requests = $repo->findBy([ "user" => $user, ]);

        if (count($requests) < 1) {
            return false;
        }

        $expireTime = (new Settings)->inst()->getSetting("auth.password.expire", "2H");

        if (!Util::is($expireTime, DateInterval::class)) {
            throw new DuppyException(DuppyError::incorrectType());
        }

        return $requests;
    }

    /**
     * @param string|int $userId
     * @return int
     * @throws DependencyException
     * @throws DuppyException
     * @throws NotFoundException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function getActivePasswordRequests(string|int $userId): int {
        $requests = $this->getActivePasswordRequests($userId);

        if (!is_array($requests)) {
            return false;
        }

        $dbo = Bootstrapper::getDatabase();

        $expireTime = (new Settings)->inst()->getSetting("auth.password.expire", "2H");

        $now = new DateTime;
        $amtActive = 0;

        foreach ($requests as $request) {
            $time = $request->get("time");

            if (!Util::is($time, DateInterval::class)) {
                continue;
            }

            $expire = $time->add($expireTime);

            if ($now <= $expire) {
                $amtActive++;
                continue;
            }

            // Remove expired while we are here
            $dbo->remove($request);
        }

        $dbo->flush();
        return $amtActive;
    }

    /**
     * Checks if the current logged in user is banned in the current context.
     *
     * @return bool
     * @throws DependencyException
     * @throws DuppyException
     * @throws NotFoundException
     */
    function imBanned(): bool {
        $me = $this->getLoggedInUser();

        if ($me == null) {
            return false;
        }

        return $me->banned();
    }

}