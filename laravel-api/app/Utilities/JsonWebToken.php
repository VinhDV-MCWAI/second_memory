<?php

namespace App\Utilities;

use App\Constants\CommonVal;
use App\Constants\Messages;

class JsonWebToken
{
    public const ALGORITHM_HS256 = 'HS256';

    // public const TTL_ACCESS = 3600 * 5;   // Time to live (second)
    public const TTL_ACCESS = CommonVal::MAX_ACCESS_TTL;   // Time to live (second)
    //    public const TTL_REFRESH = 3600 * 60 * 24; // Time to live (second)

    public const TTL_REFRESH = CommonVal::MAX_REFRESH_TTL; // Time to live (second)

    /**
     * Generate JWT header
     */
    private static function JWTHeader(string $algorithm = self::ALGORITHM_HS256): array
    {
        return [
            'typ' => 'JWT',
            'alg' => $algorithm,
        ];
    }

    /**
     * Generate JWT payload
     */
    public static function JWTPayload(array $payload, bool $isRefresh = false, ?int $iat = null): array
    {
        $iat = $iat ?? time();

        return [
            'id' => $payload['id'] ?? '',                                        // ID of the token (member id)
            'type' => $payload['type'] ?? '',                                      // Type of the token (member type)
            'iat' => $iat,                                                        // Time when JWT was issued.
            'exp' => $iat + ($isRefresh ? self::TTL_REFRESH : self::TTL_ACCESS),   // Expiration time
        ];
    }

    /**
     * Encode JWT
     */
    public static function encode(array $params, string $key, string $alg = self::ALGORITHM_HS256): string
    {
        // Encrypt header
        $header = json_encode(self::JWTHeader($alg));
        $base64UrlHeader = str_replace(
            ['+', '/', '='],
            ['-', '_', ''],
            base64_encode($header)
        );

        // Encrypt payload
        $payload = json_encode($params);
        $base64UrlPayload = str_replace(
            ['+', '/', '='],
            ['-', '_', ''],
            base64_encode($payload)
        );

        // Encrypt signature
        $signature = hash_hmac(
            'sha256',
            $base64UrlHeader.'.'.$base64UrlPayload,
            $key,
            true
        );
        $base64UrlSignature = str_replace(
            ['+', '/', '='],
            ['-', '_', ''],
            base64_encode($signature)
        );

        return $base64UrlHeader.'.'.$base64UrlPayload.'.'.$base64UrlSignature;
    }

    /**
     * Decode JWT
     */
    public static function decode(string $jwt, string $key, bool $isRefresh = false): array
    {
        $tks = explode('.', $jwt);
        if (count($tks) != 3) {
            throw new \UnexpectedValueException(Messages::E0600);
        }
        [$headB64, $bodyB64, $cryptoB64] = $tks;

        /**
         * Verify header
         */
        $header = json_decode(base64_decode($headB64), true);
        if ($header === null) {
            throw new \UnexpectedValueException(Messages::E0601);
        }

        /**
         * Verify payload
         */
        $payload = json_decode(base64_decode($bodyB64), true);
        if ($payload === null) {
            throw new \UnexpectedValueException(Messages::E0603);
        }

        // if (!Tmp::areSameKeysAndType($payload, self::JWTPayload([]))) {
        //   throw new \UnexpectedValueException(Messages::E0604);
        // }

        // Check expiration time
        if (
            $payload['exp'] < time()
            || $payload['iat'] !== $payload['exp'] - ($isRefresh ? self::TTL_REFRESH : self::TTL_ACCESS)
        ) {
            throw new \UnexpectedValueException(Messages::E0607);
        }

        /**
         * Verify signature
         */
        $revertStrReplace = str_replace(
            ['-', '_', ''],
            ['+', '/', '='],
            $cryptoB64
        );
        $sig = base64_decode($revertStrReplace);
        if ($sig === false) {
            throw new \UnexpectedValueException(Messages::E0605);
        }

        $newSig = hash_hmac(
            'sha256',
            $headB64.'.'.$bodyB64,
            $key,
            true
        );

        if (! hash_equals($sig, $newSig)) {
            throw new \UnexpectedValueException(Messages::E0606);
        }

        return [
            'body' => $payload,
            'signature' => $cryptoB64,
        ];
    }
}
