<?php

namespace Duppy\Bootstrapper;

use DI\DependencyException;
use DI\NotFoundException;
use Duppy\Entities\WebUser;
use Exception;
use JetBrains\PhpStorm\Pure;
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

final class TokenManager {

    /**
     * Decrypted and verified auth token (from JWT)
     *
     * @var array|null
     */
    public static ?array $authToken;

    /**
     * Creates a new signed and encrypted token with the payload
     *
     * @param array $payload
     * @return string
     */
    public static function createToken(array $payload): string {
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

        $encrypt = TokenManager::isEncryptionEnabled();

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
     */
    public static function createTokenFill(array $payload): string {
        $defaults = [
            "iss" => DUPPY_URI,
            "aud" => getenv("CLIENT_URL"),
            "iat" => time(),
        ];

        // Merge payload over defaults so it overwrites them
        $payload = array_merge($defaults, $payload);

        return TokenManager::createToken($payload);
    }

    /**
     * Creates a user's JWT token
     *
     * @param WebUser $user
     * @return string
     */
    public static function createTokenFromUser(WebUser $user): string {
        $data = [
            "id" => $user->get("id"),
            "username" => $user->get("username"),
            "avatarUrl" => $user->get("avatarUrl"),
        ];

        return TokenManager::createTokenFill($data);
    }

    /**
     * Creates a user's JWT token from their user ID
     *
     * @param int $userId
     * @return string
     * @throws DependencyException
     * @throws NotFoundException
     */
    public static function createTokenFromUserId(int $userId): string {
        $userObj = UserService::getUser($userId);
        return TokenManager::createTokenFromUser($userObj);
    }

    /**
     * Attempts to load an encrypted and signed token. Returns null on failure
     *
     * @param string $token
     * @return array|null
     */
    public static function loadToken(string $token): ?string {
        $jweDecrypter = Bootstrapper::getJWEDecrypter();
        $jwsVerifier = Bootstrapper::getJWSVerifier();
        $jweKey = Bootstrapper::getJWEKey();
        $jwsKey = Bootstrapper::getJWSKey();

        $isEncrypted = TokenManager::isEncryptionEnabled();

        $jwsToken = $token;

        // Decrypt
        if ($isEncrypted) {
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
            $pl = json_decode($jws->getPayload());

            $claimChecker = new ClaimCheckerManager([
                new IssuedAtChecker(),
                new NotBeforeChecker(),
                new ExpirationTimeChecker(),
                new AudienceChecker(getenv("CLIENT_URL")),
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
     * @return array|null
     */
    public static function getAuthToken(): ?array {
        if (!array_key_exists("authToken", $_POST)) {
            return null;
        }

        if (TokenManager::$authToken != null) {
            return TokenManager::$authToken;
        }

        $token = $_POST["authToken"];
        return TokenManager::$authToken = TokenManager::loadToken($token);
    }

    /**
     * Convenience function to get the bool eval of JWT_ENCRYPT
     *
     * @return bool
     */
    #[Pure]
    public static function isEncryptionEnabled(): bool {
        return getenv("JWT_ENCRYPT") !== "false";
    }

}