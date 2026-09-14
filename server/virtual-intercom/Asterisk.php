<?php

namespace VirtualIntercom;

require_once __DIR__ . '/Service.php';

/** Adapters for the existing loopback-only Asterisk HTTP endpoint. */
final class Asterisk
{
    public static function endpoint(string $extension, string $section): array|false|null
    {
        global $redis;
        if (str_starts_with($extension, 'vi_')) return Service::configured()->endpoint($extension, $section);
        if (preg_match('/^2[0-9]{9}$/D', $extension) && $redis->get('VI:AUTH:' . $extension)) {
            return Service::configured()->mobileEndpoint($extension, $section);
        }
        return null;
    }

    public static function request(array $params): array
    {
        try {
            return Service::configured()->internal((string)($params['action'] ?? ''), $params);
        } catch (\Throwable) {
            return ['ok' => false];
        }
    }

    /** null: ordinary call; false: rejected virtual call; array: virtual media settings. */
    public static function pushOptions(array $params): array|false|null
    {
        global $redis;
        $extension = (string)$params['extension'];
        $id = $redis->get('VI:MOBILE:' . $extension);
        // Expired virtual bindings must not fall through to ordinary delivery.
        if (!$id) return $redis->get('VI:AUTH:' . $extension) ? false : null;
        $call = self::request(['action' => 'push', 'id' => $id, 'extension' => $extension]);
        if (!$call['ok']) return false;
        return ['hash' => $call['hash'], 'dtmf' => '5', 'dtmfProtocol' => 'info', 'videoType' => 'inband',
            'bundle' => $params['bundle'] ?? 'default'];
    }
}
