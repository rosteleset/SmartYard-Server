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

    public static function push(array $params): bool
    {
        if (empty($params['virtualCallId'])) return false;
        $call = self::request(['action' => 'push', 'id' => $params['virtualCallId'], 'extension' => (string)$params['extension']]);
        if (!$call['ok']) return true;
        $sip = \loadBackend('sip');
        $server = $sip->server('extension', $params['extension']);
        $push = ['token' => $params['token'], 'type' => $params['tokenType'],
            'hash' => $call['hash'], 'extension' => $params['extension'], 'server' => $server['ip'],
            'port' => ($server['sip_tcp_port'] ?? 0) ?: 5060, 'transport' => 'tcp',
            'dtmf' => '5', 'dtmfProtocol' => 'info', 'videoType' => 'inband',
            'timestamp' => time(), 'ttl' => 30, 'platform' => (int)$params['platform'] === 1 ? 'ios' : 'android',
            'callerId' => $params['callerId'], 'flatId' => $params['flatId'],
            'domophoneId' => $params['domophoneId'], 'flatNumber' => $params['flatNumber'],
            'bundle' => $params['bundle'] ?? 'default', 'title' => \i18n('sip.incomingTitle')];
        if ($stun = $sip->stun($params['extension'])) $push['stun'] = $stun;
        \loadBackend('isdn')->push($push);
        return true;
    }
}
