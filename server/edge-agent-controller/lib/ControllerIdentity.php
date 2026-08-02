<?php

declare(strict_types=1);

namespace SesameWare\RbtAgent;

use DateTimeImmutable;
use DateTimeZone;
use RuntimeException;

final class ControllerIdentity
{
    /** @param array{algorithm:string,controllerId:string,keyId:string,publicKey:string,privateKey:string,createdAt:string} $data */
    private function __construct(private readonly array $data)
    {
        $this->validate();
    }

    public static function loadOrCreate(string $path, ?string $controllerId = null): self
    {
        if (is_file($path)) {
            $data = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
            if (!is_array($data)) {
                throw new RuntimeException('controller identity file is invalid');
            }
            return new self($data);
        }

        $directory = dirname($path);
        if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
            throw new RuntimeException("cannot create controller identity directory $directory");
        }
        $keyPair = sodium_crypto_sign_keypair();
        $publicKey = sodium_crypto_sign_publickey($keyPair);
        $privateKey = sodium_crypto_sign_secretkey($keyPair);
        $publicKeyBase64 = Protocol::encodeBase64($publicKey);
        $data = [
            'algorithm' => 'ed25519',
            'controllerId' => $controllerId ?: 'rbt-' . substr(bin2hex(random_bytes(16)), 0, 24),
            'keyId' => Protocol::keyId($publicKeyBase64),
            'publicKey' => $publicKeyBase64,
            'privateKey' => Protocol::encodeBase64($privateKey),
            'createdAt' => (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d\TH:i:s\Z'),
        ];
        $identity = new self($data);
        $encoded = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n";
        $temporary = $path . '.tmp.' . bin2hex(random_bytes(6));
        if (file_put_contents($temporary, $encoded, LOCK_EX) === false) {
            throw new RuntimeException('cannot write controller identity');
        }
        chmod($temporary, 0600);
        if (!rename($temporary, $path)) {
            @unlink($temporary);
            throw new RuntimeException('cannot persist controller identity');
        }
        return $identity;
    }

    public function id(): string
    {
        return $this->data['controllerId'];
    }

    public function keyId(): string
    {
        return $this->data['keyId'];
    }

    public function publicKey(): string
    {
        return $this->data['publicKey'];
    }

    public function privateKey(): string
    {
        return $this->data['privateKey'];
    }

    /** @return array{algorithm:string,controllerId:string,keyId:string,publicKey:string,createdAt:string} */
    public function publicData(): array
    {
        return [
            'algorithm' => $this->data['algorithm'],
            'controllerId' => $this->data['controllerId'],
            'keyId' => $this->data['keyId'],
            'publicKey' => $this->data['publicKey'],
            'createdAt' => $this->data['createdAt'],
        ];
    }

    private function validate(): void
    {
        foreach (['algorithm', 'controllerId', 'keyId', 'publicKey', 'privateKey', 'createdAt'] as $field) {
            if (!isset($this->data[$field]) || !is_string($this->data[$field]) || $this->data[$field] === '') {
                throw new RuntimeException("controller identity is missing $field");
            }
        }
        if ($this->data['algorithm'] !== 'ed25519') {
            throw new RuntimeException('unsupported controller identity algorithm');
        }
        $publicKey = Protocol::decodeBase64($this->data['publicKey']);
        $privateKey = Protocol::decodeBase64($this->data['privateKey']);
        if (strlen($publicKey) !== SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES || strlen($privateKey) !== SODIUM_CRYPTO_SIGN_SECRETKEYBYTES) {
            throw new RuntimeException('controller identity has invalid key size');
        }
        if (!hash_equals($publicKey, sodium_crypto_sign_publickey_from_secretkey($privateKey))) {
            throw new RuntimeException('controller identity key pair mismatch');
        }
        if (!hash_equals($this->data['keyId'], Protocol::keyId($this->data['publicKey']))) {
            throw new RuntimeException('controller identity key ID mismatch');
        }
        if (DateTimeImmutable::createFromFormat('!Y-m-d\TH:i:s\Z', $this->data['createdAt'], new DateTimeZone('UTC')) === false) {
            throw new RuntimeException('controller identity createdAt is invalid');
        }
    }
}
