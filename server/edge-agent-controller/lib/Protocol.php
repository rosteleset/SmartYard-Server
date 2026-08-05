<?php

declare(strict_types=1);

namespace SesameWare\RbtAgent;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use RuntimeException;

final class Protocol
{
    public const VERSION = '2';
    public const DOMAIN = 'rbt-agent-http-signature-v2';
    public const HEADER_SIGNATURE_VERSION = 'X-RBT-Agent-Signature-Version';
    public const HEADER_SIGNER_ID = 'X-RBT-Agent-Signer-ID';
    public const HEADER_KEY_ID = 'X-RBT-Agent-Key-ID';
    public const HEADER_TIMESTAMP = 'X-RBT-Agent-Timestamp';
    public const HEADER_REQUEST_ID = 'X-RBT-Agent-Request-ID';
    public const HEADER_SEQUENCE = 'X-RBT-Agent-Sequence';
    public const HEADER_CONTENT_SHA256 = 'X-RBT-Agent-Content-SHA256';
    public const HEADER_SIGNATURE = 'X-RBT-Agent-Signature';

    public static function bodySha256(string $body): string
    {
        return hash('sha256', $body);
    }

    public static function keyId(string $publicKeyBase64): string
    {
        return 'sha256:' . hash('sha256', self::decodeBase64($publicKeyBase64));
    }

    /**
     * @param array{
     *   direction:string,
     *   method:string,
     *   path:string,
     *   statusCode:int,
     *   bodySha256:string,
     *   signerId:string,
     *   keyId:string,
     *   timestamp:string,
     *   requestId:string,
     *   sequence:int
     * } $metadata
     */
    public static function canonical(array $metadata): string
    {
        $direction = (string) ($metadata['direction'] ?? '');
        $method = (string) ($metadata['method'] ?? '');
        $path = (string) ($metadata['path'] ?? '');
        $statusCode = $metadata['statusCode'] ?? null;
        $bodySha256 = (string) ($metadata['bodySha256'] ?? '');
        $signerId = (string) ($metadata['signerId'] ?? '');
        $keyId = (string) ($metadata['keyId'] ?? '');
        $timestamp = (string) ($metadata['timestamp'] ?? '');
        $requestId = (string) ($metadata['requestId'] ?? '');
        $sequence = $metadata['sequence'] ?? null;

        if ($direction !== 'request' && $direction !== 'response') {
            throw new InvalidArgumentException('invalid signature direction');
        }
        if ($method === '' || $method !== strtoupper($method)) {
            throw new InvalidArgumentException('invalid signature method');
        }
        if ($path === '' || $path[0] !== '/' || strpbrk($path, "?#") !== false) {
            throw new InvalidArgumentException('invalid signature path');
        }
        if (!is_int($statusCode)) {
            throw new InvalidArgumentException('invalid signature statusCode');
        }
        if ($direction === 'request' && $statusCode !== 0) {
            throw new InvalidArgumentException('request signature statusCode must be zero');
        }
        if ($direction === 'response' && ($statusCode < 100 || $statusCode > 599)) {
            throw new InvalidArgumentException('invalid response signature statusCode');
        }
        if (!preg_match('/^[a-f0-9]{64}$/', $bodySha256)) {
            throw new InvalidArgumentException('invalid signature body hash');
        }
        if ($signerId === '' || $keyId === '' || $requestId === '') {
            throw new InvalidArgumentException('signature identity fields are required');
        }
        if (!is_int($sequence) || $sequence <= 0) {
            throw new InvalidArgumentException('signature sequence must be positive');
        }
        if (DateTimeImmutable::createFromFormat('!Y-m-d\TH:i:s\Z', $timestamp, new DateTimeZone('UTC')) === false) {
            throw new InvalidArgumentException('invalid signature timestamp');
        }

        foreach ([$direction, $method, $path, $bodySha256, $signerId, $keyId, $timestamp, $requestId] as $value) {
            if (strpbrk($value, "\r\n") !== false) {
                throw new InvalidArgumentException('signature field contains a newline');
            }
        }

        return implode("\n", [
            self::DOMAIN,
            $direction,
            $method,
            $path,
            (string) $statusCode,
            $bodySha256,
            $signerId,
            $keyId,
            $timestamp,
            $requestId,
            (string) $sequence,
        ]);
    }

    /** @param array<string,mixed> $metadata */
    public static function verify(
        string $publicKeyBase64,
        array $metadata,
        string $signatureBase64,
        string $body,
    ): void {
        $publicKey = self::decodeBase64($publicKeyBase64);
        if (strlen($publicKey) !== SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES) {
            throw new InvalidArgumentException('invalid Ed25519 public key');
        }
        $signature = self::decodeBase64($signatureBase64);
        if (strlen($signature) !== SODIUM_CRYPTO_SIGN_BYTES) {
            throw new InvalidArgumentException('invalid Ed25519 signature');
        }
        if (!hash_equals((string) ($metadata['bodySha256'] ?? ''), self::bodySha256($body))) {
            throw new RuntimeException('signed HTTP body hash mismatch');
        }
        if (!sodium_crypto_sign_verify_detached($signature, self::canonical($metadata), $publicKey)) {
            throw new RuntimeException('invalid Ed25519 signature');
        }
    }

    /** @param array<string,mixed> $metadata */
    public static function sign(string $secretKeyBase64, array $metadata): string
    {
        $secretKey = self::decodeBase64($secretKeyBase64);
        if (strlen($secretKey) !== SODIUM_CRYPTO_SIGN_SECRETKEYBYTES) {
            throw new InvalidArgumentException('invalid Ed25519 secret key');
        }
        return self::encodeBase64(sodium_crypto_sign_detached(self::canonical($metadata), $secretKey));
    }

    /** @param array<string,mixed> $metadata */
    public static function validateTimestamp(array $metadata, DateTimeImmutable $now, int $maxSkewSeconds): void
    {
        $timestamp = DateTimeImmutable::createFromFormat(
            '!Y-m-d\TH:i:s\Z',
            (string) ($metadata['timestamp'] ?? ''),
            new DateTimeZone('UTC'),
        );
        if ($timestamp === false) {
            throw new InvalidArgumentException('invalid signature timestamp');
        }
        if (abs($now->getTimestamp() - $timestamp->getTimestamp()) > $maxSkewSeconds) {
            throw new RuntimeException('signature timestamp outside allowed window');
        }
    }

    public static function encodeBase64(string $value): string
    {
        return rtrim(base64_encode($value), '=');
    }

    public static function decodeBase64(string $value): string
    {
        $value = trim($value);
        $padding = strlen($value) % 4;
        if ($padding !== 0) {
            $value .= str_repeat('=', 4 - $padding);
        }
        $decoded = base64_decode($value, true);
        if ($decoded === false) {
            throw new InvalidArgumentException('invalid base64 value');
        }
        return $decoded;
    }
}
