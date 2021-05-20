<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy\DuppyServices;

use Exception;
use DI\DependencyException;
use DI\NotFoundException;
use Duppy\Util;
use Duppy\DuppyException;
use Duppy\Enum\DuppyError;
use Duppy\Entities\WebUser;
use Duppy\Entities\ApiClient;
use Duppy\Abstracts\AbstractService;
use Duppy\Bootstrapper\Bootstrapper;
use Duppy\Bootstrapper\DCache;
use JetBrains\PhpStorm\Pure;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\TransactionRequiredException;
use Jose\Component\Checker\AlgorithmChecker;
use Jose\Component\Checker\AudienceChecker;
use Jose\Component\Checker\ClaimCheckerManager;
use Jose\Component\Checker\ExpirationTimeChecker;
use Jose\Component\Checker\HeaderCheckerManager;
use Jose\Component\Checker\IssuedAtChecker;
use Jose\Component\Checker\NotBeforeChecker;
use Jose\Component\Encryption\JWELoader;
use Jose\Component\Encryption\JWETokenSupport;
use Jose\Component\Encryption\Serializer\CompactSerializer as EncCompactSerializer;
use Jose\Component\Encryption\Serializer\JWESerializerManager;
use Jose\Component\Signature\JWSLoader;
use Jose\Component\Signature\JWSTokenSupport;
use Jose\Component\Signature\Serializer\CompactSerializer as SigCompactSerializer;
use Jose\Component\Signature\Serializer\JWSSerializerManager;

/**
 * Handles JWT and APIClient Tokens
 *
 * Class TokenManager
 * @package Duppy\DuppyServices
 */
final class TokenManager extends AbstractService {

    /**
     * Decrypted and verified auth token (from JWT)
     *
     * @var DCache
     */
    public DCache $authToken;

    /**
     * Matching APIClient from authentication headers
     *
     * @var DCache
     */
    public DCache $apiClient;

    /**
     * Auth token string of the current request
     * 
     * @var DCache
     */
    public DCache $authTokenString;

    /**
     * Optional but allows overriding env
     * 
     * @var bool|null
     */
    public ?bool $encryptionEnabled = null;

    public function __construct(bool $singleton = false) {
        $this->authToken = new DCache;
        $this->apiClient = new DCache;
        $this->authTokenString = new DCache;

        parent::__construct($singleton);
    }

    /**
     * Clean up cached stuff for user every request
     *
     * @param bool $force
     */
    public function clean(bool $force = false) {
        $this->authToken->clear();
        $this->apiClient->clear();
        $this->authTokenString->clear();
    }

    /**
     * Creates a new signed and encrypted token with the payload
     *
     * @param array $payload
     * @return string
     */
    public function createToken(array $payload): string {
        $payload = json_encode($payload);

        $jwsBuilder = Bootstrapper::getJWSBuilder();
        $jwsKey = Bootstrapper::getJWSKey();

        // Sign
        $signedSerializer = new SigCompactSerializer();

        $jws = $jwsBuilder->create()
            ->withPayload($payload)
            ->addSignature($jwsKey, ['alg' => 'HS256'])
            ->build();

        $signedToken = $signedSerializer->serialize($jws, 0);

        $encrypt = $this->isEncryptionEnabled();

        // Encrypt
        if ($encrypt) {
            $jweBuilder = Bootstrapper::getJWEBuilder();
            $jweKey = Bootstrapper::getJWEKey();

            $encryptedSerializer = new EncCompactSerializer();

            $jwe = $jweBuilder->create()
                ->withPayload($signedToken)
                ->withSharedProtectedHeader([
                    "alg" => "A256KW",
                    "enc" => "A128CBC-HS256",
                ])
                ->addRecipient($jweKey)
                ->build();

            return $encryptedSerializer->serialize($jwe, 0);
        }

        return $signedToken;
    }

    /**
     * Creates a new token and adds missing req values to the payload
     *
     * @param array $payload
     * @return string
     * @throws DependencyException
     * @throws NotFoundException
     * @throws DuppyException
     */
    public function createTokenFill(array $payload): string {
        $clientUrl = (new Settings)->inst()->getSetting("clientUrl");

        $defaults = [
            "iss" => Env::G("API_URL"),
            "aud" => $clientUrl,
            "iat" => time(),
        ];

        // Merge payload over defaults so it overwrites them
        $payload = array_merge($defaults, $payload);

        return $this->createToken($payload);
    }

    /**
     * Creates a user's JWT token
     *
     * @param WebUser $user
     * @return string
     * @throws DependencyException
     * @throws DuppyException
     * @throws NotFoundException
     */
    public function createTokenFromUser(WebUser $user): string {
        $data = [
            "id" => $user->get("id"),
            "username" => $user->get("username"),
            "avatarUrl" => $user->get("avatarUrl"),
        ];

        return $this->createTokenFill($data);
    }

    /**
     * Creates a user's JWT token from their user ID
     *
     * @param int $userId
     * @return string
     * @throws DependencyException
     * @throws DuppyException
     * @throws NotFoundException
     */
    public function createTokenFromUserId(int $userId): string {
        $userObj = (new UserService)->inst()->getUser($userId);

        if (!($userObj instanceof WebUser)) {
            throw new DuppyException(DuppyError::incorrectType());
        }

        return $this->createTokenFromUser($userObj);
    }

    /**
     * Attempts to load an encrypted and signed token. Returns null on failure
     *
     * @param string $token
     * @return array|null
     * @throws DependencyException
     * @throws DuppyException
     * @throws NotFoundException
     */
    public function loadJWToken(string $token): ?array {
        $isEncrypted = $this->isEncryptionEnabled();

        $jwsToken = $token;

        // Decrypt
        if ($isEncrypted) {
            $jweDecrypter = Bootstrapper::getJWEDecrypter();
            $jweKey = Bootstrapper::getJWEKey();

            $encryptedSerializer = new JWESerializerManager([new EncCompactSerializer(),]);
            $headerCheckerEnc = new HeaderCheckerManager([
                new AlgorithmChecker(["A256KW"]),
            ], [
                new JWETokenSupport(),
            ]);

            $jweLoader = new JWELoader($encryptedSerializer, $jweDecrypter, $headerCheckerEnc);
            $jwe = $jweLoader->loadAndDecryptWithKey($token, $jweKey, $recipient);

            // Get JWS from encrypted payload
            $jwsToken = $jwe->getPayload();
        }

        $jwsVerifier = Bootstrapper::getJWSVerifier();
        $jwsKey = Bootstrapper::getJWSKey();

        $clientUrl = (new Settings)->inst()->getSetting("clientUrl");

        // Check signed token
        $signedSerializer = new JWSSerializerManager([ new SigCompactSerializer(), ]);
        $headerCheckerSig = new HeaderCheckerManager([
            new AlgorithmChecker([ "HS256" ]),
        ], [
            new JWSTokenSupport(),
        ]);

        $jwsLoader = new JWSLoader($signedSerializer, $jwsVerifier, $headerCheckerSig);

        try {
            $jws = $jwsLoader->loadAndVerifyWithKey($jwsToken, $jwsKey, $signature);
            $pl = (array) json_decode($jws->getPayload());

            $claimChecker = new ClaimCheckerManager([
                new IssuedAtChecker(),
                new NotBeforeChecker(),
                new ExpirationTimeChecker(),
                new AudienceChecker($clientUrl),
            ]);

            $claimChecker->check($pl, ["iss", "aud"]);

            return $pl;
        } catch (Exception) {
            return null;
        }
    }

    /**
     * Gets the auth token array from the submitted JWT, also caches it
     *
     * @return ?array
     * @throws DependencyException
     * @throws DuppyException
     * @throws NotFoundException
     */
    public function getJWToken(): ?array {
        if (($authToken = $this->authToken->get()) != null) {
            return $authToken;
        }

        $token = $this->getAuthTokenString();

        // Ignore empty or API Tokens
        if (empty($token) || str_starts_with($token, "apiToken ")) {
            return null;
        }

        $jwt = $this->loadJWToken($token);
        return $this->authToken->setObject($jwt);
    }

    /**
     * Returns the ID of the APIClient specified by the request headers
     * 
     * @return ?int
     */
    public function getAPIClientID(): ?int {
        $request = Bootstrapper::getCurrentRequest();
        $clientIDHeaders = $request->getHeader("X-Client-ID");

        if (count($clientIDHeaders) > 1) {
            return null;
        }

        $clientID = intval($clientIDHeaders[0]);
        
        if ($clientID == 0) {
            return null;
        }

        return $clientID;
    }

    /**
     * Returns an authenticated ApiClient with the current request
     *
     * @return ?ApiClient
     * @throws DependencyException
     * @throws NotFoundException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws TransactionRequiredException
     */
    public function getAPIClient(): ?ApiClient {
        if (($apiClient = $this->apiClient->get()) != null) {
            return $apiClient;
        }

        $clientID = $this->getAPIClientID();

        if ($clientID == null) {
            return null;
        }

        $authTokenStr = $this->getAuthTokenString();

        if ($authTokenStr == null || empty($authTokenStr) == "" || !str_starts_with($authTokenStr, "apiToken ")) {
            return null;
        }

        $authTokenStr = substr($authTokenStr, 9); // 9 is len of 'apiToken '

        // Search for ApiClient matching ClientID
        $dbo = Bootstrapper::getDatabase();
        $apiClient = $dbo->find(ApiClient::class, $clientID);

        if ($apiClient == null) {
            return null;
        }

        // Check if the token is valid against the ApiClient (Password OK)
        $result = $apiClient->checkToken($authTokenStr);

        if ($result != true) {
            return null;
        }

        return $this->apiClient->setObject($apiClient);
    }

    /**
     * Gets the Bearer token from the request (JWT or APIClient Token)
     * Null for invalid or no authorization
     * 
     * @return ?string
     */
    public function getAuthTokenString(): ?string {
        if (($authTokenStr = $this->authTokenString->get()) != null) {
            return $authTokenStr;
        }

        $request = Bootstrapper::getCurrentRequest();
        $authHeader = $request->getHeader("Authorization");

        if (count($authHeader) > 1) {
            return null;
        }

        $token = Util::indArrayNull($authHeader, 0);

        if ($token != null) {
            if (!str_starts_with($token, "Bearer ")) {
                return null;
            }

            $token = substr($token, 7); // 7 is len of 'Bearer '
        } else {
            // Deprecated: POST authToken support
            $postArgs = $request->getParsedBody();
            $token = Util::indArrayNull($postArgs, "authToken");
        }

        if ($token == null || empty($token)) {
            return null;
        }

        return $this->authTokenString->setObject($token);
    }

    /**
     * Convenience function to get the bool eval of JWT_ENCRYPT
     *
     * @return bool
     */
    #[Pure]
    public function isEncryptionEnabled(): bool {
        return $this->encryptionEnabled != null ? $this->encryptionEnabled : Env::G("JWT_ENCRYPT") !== "false";
    }

}