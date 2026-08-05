<?php

declare(strict_types=1);

namespace SesameWare\RbtAgent;

use InvalidArgumentException;

final class ManagedAuthorization
{
    public const DOMAIN = 'rbt-agent-managed-authorization-v2';

    /** @param list<string> $scopes */
    public static function canonical(
        string $agentId,
        string $controllerId,
        int $revision,
        string $challenge,
        array $scopes,
    ): string {
        return implode("\n", [
            self::DOMAIN,
            $agentId,
            $controllerId,
            (string) $revision,
            $challenge,
            implode(',', self::normalizeScopes($scopes)),
        ]);
    }

    /** @param list<string> $scopes */
    public static function proof(
        string $secret,
        string $agentId,
        string $controllerId,
        int $revision,
        string $challenge,
        array $scopes,
    ): string {
        if ($secret === '') {
            throw new InvalidArgumentException('managed secret is required');
        }
        $key = hash('sha256', $secret, true);
        return Protocol::encodeBase64(hash_hmac(
            'sha256',
            self::canonical($agentId, $controllerId, $revision, $challenge, $scopes),
            $key,
            true,
        ));
    }

    /** @param list<mixed> $scopes @return list<string> */
    public static function normalizeScopes(array $scopes): array
    {
        $allowed = ['lan.scan', 'mapping.configure', 'network.diagnose', 'overlay.configure'];
        $normalized = [];
        foreach ($scopes as $scope) {
            if (!is_string($scope)) {
                throw new InvalidArgumentException('managed scope must be a string');
            }
            $scope = trim($scope);
            if ($scope === '') {
                continue;
            }
            if (!in_array($scope, $allowed, true)) {
                throw new InvalidArgumentException("unsupported managed scope $scope");
            }
            $normalized[$scope] = true;
        }
        $result = array_keys($normalized);
        sort($result, SORT_STRING);
        return $result;
    }
}
