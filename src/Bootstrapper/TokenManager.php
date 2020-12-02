<?php

namespace Duppy\Bootstrapper;

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
     * Creates a new signed and encrypted token with the payload
     *
     * @param array $payload
     * @return string
     */
    public static function createToken(array $payload): string {
        $payload = json_encode($payload);

        $jwsBuilder = Bootstrapper::getJWSBuilder();
        $jweBuilder = Bootstrapper::getJWEBuilder();
        $jwKey = Bootstrapper::getJWKey();

        // Sign
        $signedSerializer = new SigCompactSerializer();

        $jws = $jwsBuilder->create()
            ->withPayload($payload)
            ->addSignature($jwKey, ['alg' => 'HS256'])
            ->build();

        $signedToken = $signedSerializer->serialize($jws, 0);

        // Encrypt
        $encryptedSerializer = new EncCompactSerializer();

        $jwe = $jweBuilder->create()
            ->withPayload($signedToken)
            ->withSharedProtectedHeader([
                "alg" => "A256KW",
                "enc" => "A256CBC-HS512",
            ])
            ->addRecipient($jwKey)
            ->build();

        return $encryptedSerializer->serialize($jwe, 0);
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

        return static::createToken($payload);
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
        $jwKey = Bootstrapper::getJWKey();

        // Decrypt
        $encryptedSerializer = new JWESerializerManager([ new EncCompactSerializer(), ]);
        $headerCheckerEnc = new HeaderCheckerManager([
            new AlgorithmChecker([ "A256KW" ]),
        ], [
            new JWETokenSupport(),
        ]);

        $jweLoader = new JWELoader($encryptedSerializer, $jweDecrypter, $headerCheckerEnc);
        $jwe = $jweLoader->loadAndDecryptWithKey($token, $jwKey, $recipient);

        // Get JWS from encrypted payload
        $jwsToken = $jwe->getPayload();

        // Check signed token
        $signedSerializer = new JWSSerializerManager([ new SigCompactSerializer(), ]);
        $headerCheckerSig = new HeaderCheckerManager([
            new AlgorithmChecker([ "HS256" ]),
        ], [
            new JWSTokenSupport(),
        ]);

        $jwsLoader = new JWSLoader($signedSerializer, $jwsVerifier, $headerCheckerSig);

        try {
            $jws = $jwsLoader->loadAndVerifyWithKey($jwsToken, $jwKey, $signature);
            $pl = json_decode($jws->getPayload());

            $claimChecker = new ClaimCheckerManager([
                new IssuedAtChecker(),
                new NotBeforeChecker(),
                new ExpirationTimeChecker(),
                new AudienceChecker(getenv("CLIENT_URL")),
            ]);

            $claimChecker->check($pl, ["iss", "aud"]);

            return $pl;
        } catch (\Exception $e) {
            return null;
        }
    }

}