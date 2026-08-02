<?php

declare(strict_types=1);

namespace SesameWare\RbtAgent;

use RuntimeException;

final class GatewayKeyStore
{
    public function __construct(
        private readonly string $directory,
        private readonly GatewayCommandRunnerInterface $runner,
        private readonly ?string $publicDirectory = null,
    ) {
    }

    /** @return array{privateKeyPath:string,publicKey:string,created:bool} */
    public function ensure(string $interface, string $tool = 'wg'): array
    {
        $path = $this->path($interface);
        $created = false;
        if (!is_file($path)) {
            if (!is_dir($this->directory) && !mkdir($this->directory, 0700, true) && !is_dir($this->directory)) {
                throw new RuntimeException("cannot create gateway key directory {$this->directory}");
            }
            chmod($this->directory, 0700);
            $privateKey = trim($this->runner->run($tool, ['genkey']));
            $this->validateKey($privateKey, 'private');
            $temporary = $path . '.tmp.' . getmypid();
            if (file_put_contents($temporary, $privateKey . "\n", LOCK_EX) === false) {
                throw new RuntimeException("cannot write gateway private key $temporary");
            }
            chmod($temporary, 0600);
            if (!rename($temporary, $path)) {
                @unlink($temporary);
                throw new RuntimeException("cannot install gateway private key $path");
            }
            $created = true;
        }
        $key = $this->load($interface, $tool);
        $key['created'] = $created;
        return $key;
    }

    /** @return array{privateKeyPath:string,publicKey:string,created:bool} */
    public function load(string $interface, string $tool = 'wg'): array
    {
        $path = $this->path($interface);
        if (!is_file($path)) {
            throw new RuntimeException("gateway private key is missing for interface $interface");
        }
        chmod($path, 0600);
        $contents = file_get_contents($path);
        if ($contents === false) {
            throw new RuntimeException("cannot read gateway private key $path");
        }
        $privateKey = trim($contents);
        $this->validateKey($privateKey, 'private');
        $publicKey = trim($this->runner->run($tool, ['pubkey'], $privateKey . "\n"));
        $this->validateKey($publicKey, 'public');
        $this->persistPublicKey($interface, $publicKey);
        return ['privateKeyPath' => $path, 'publicKey' => $publicKey, 'created' => false];
    }

    public function publicKey(string $interface): string
    {
        $path = $this->publicPath($interface);
        $contents = is_file($path) ? file_get_contents($path) : false;
        if ($contents === false) {
            throw new RuntimeException("gateway public key is missing for interface $interface");
        }
        $publicKey = trim($contents);
        $this->validateKey($publicKey, 'public');
        return $publicKey;
    }

    public function path(string $interface): string
    {
        if (!preg_match('/^[A-Za-z0-9_.-]{1,15}$/', $interface)) {
            throw new RuntimeException('gateway interface name is invalid');
        }
        return rtrim($this->directory, '/') . '/' . $interface . '.key';
    }

    public function publicPath(string $interface): string
    {
        $this->path($interface);
        return rtrim($this->publicDirectory ?? ($this->directory . '/public'), '/') . '/' . $interface . '.pub';
    }

    private function persistPublicKey(string $interface, string $publicKey): void
    {
        $path = $this->publicPath($interface);
        $directory = dirname($path);
        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new RuntimeException("cannot create gateway public key directory $directory");
        }
        chmod($directory, 0755);
        $temporary = $path . '.tmp.' . getmypid();
        if (file_put_contents($temporary, $publicKey . "\n", LOCK_EX) === false) {
            throw new RuntimeException("cannot write gateway public key $temporary");
        }
        chmod($temporary, 0644);
        if (!rename($temporary, $path)) {
            @unlink($temporary);
            throw new RuntimeException("cannot install gateway public key $path");
        }
    }

    private function validateKey(string $key, string $kind): void
    {
        $decoded = base64_decode($key, true);
        if ($decoded === false || strlen($decoded) !== 32) {
            throw new RuntimeException("WireGuard $kind key is invalid");
        }
    }
}
