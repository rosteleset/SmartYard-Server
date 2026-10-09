<?php

namespace backends\dvr;

require_once __DIR__ . '/RbtToken.php';

/** Only cron registers secrets. Playback requests perform Redis reads and local HMAC. */
final class RbtAuthBackend
{
    private $redis;
    private $active = [];
    private $settingsCache = [];

    public function __construct($redis)
    {
        $this->redis = $redis;
    }

    public static function enabled($server)
    {
        return is_array($server['authbackend'] ?? null)
            && trim((string)($server['authbackend']['url'] ?? '')) !== '';
    }

    public function token($server, $camera, $subscriberId)
    {
        $settings = $this->settings($server);
        $prefix = $settings['prefix'];
        $now = time();
        $secret = $this->active[$prefix] ?? null;
        if (!$this->canIssue($secret, $settings, $now)) {
            $secret = $this->readSecret($prefix . 'current', $settings);
            if (!$this->canIssue($secret, $settings, $now)) {
                // The next secret is already registered. This avoids a gap between
                // its valid_from and the next minutely cron invocation.
                $secret = $this->readSecret($prefix . 'next', $settings);
            }
            if (!$this->canIssue($secret, $settings, $now)) {
                throw new \RuntimeException('rbt_authbackend_secret_unavailable');
            }
            $this->active[$prefix] = $secret;
        }
        return RbtToken::create(
            base64_decode($secret['secret'], true),
            $secret['secret_id'],
            $subscriberId,
            RbtToken::hostnameFromUrl($camera['dvrStream']),
            RbtToken::streamFromUrl($camera['dvrStream'])
        );
    }

    public function rotate($server)
    {
        $settings = $this->settings($server);
        $prefix = $settings['prefix'];
        $now = time();
        $current = $this->readSecret($prefix . 'current', $settings);
        $next = $this->readSecret($prefix . 'next', $settings);
        unset($this->active[$prefix]);

        if ($next && (($current && $current['secret_id'] === $next['secret_id']) || $next['issue_until'] <= $now)) {
            $next = null;
        }
        if ($this->canIssue($next, $settings, $now)) {
            $this->saveSecret($prefix . 'current', $next);
            $current = $next;
            $next = null;
        }
        if (!$this->canIssue($current, $settings, $now)) {
            $current = null;
        }

        // Register ahead of time, using half the interval for short rotations.
        $lead = min(600, max(1, intdiv($settings['rotation_interval'], 2)));
        if (!$next && (!$current || $now >= $current['issue_until'] - $lead)) {
            $from = $current ? $current['issue_until'] : $now;
            $next = $this->createSecret($settings, $from);
            $this->saveSecret($prefix . 'next', $next);
        }
        if (!$next) {
            return;
        }
        if (!$next['registered']) {
            try {
                $this->register($settings, $next);
            } catch (\RuntimeException $error) {
                if ($error->getCode() === 409) {
                    // Retry a rare secret_id collision with a new id on the next cron.
                    $next['secret_id'] = random_int(1, 4294967295);
                    $this->saveSecret($prefix . 'next', $next);
                }
                throw $error;
            }
            $next['registered'] = true;
            $this->saveSecret($prefix . 'next', $next);
        }
        if ($this->canIssue($next, $settings, time())) {
            $this->saveSecret($prefix . 'current', $next);
        }
    }

    private function settings($server)
    {
        // Streamer configuration is fixed for the lifetime of this request/cron.
        $key = $server['url'];
        if (isset($this->settingsCache[$key])) {
            return $this->settingsCache[$key];
        }
        $settings = $server['authbackend'];
        $url = trim((string)($settings['url'] ?? ''));
        $parts = parse_url($url);
        $managementToken = $settings['management_token'] ?? '';
        $interval = filter_var($settings['rotation_interval'] ?? 43200, FILTER_VALIDATE_INT);
        $ttl = filter_var($settings['secret_ttl'] ?? 86400, FILTER_VALIDATE_INT);
        if (!in_array($server['type'] ?? '', ['sesame', 'flussonic'], true)
            || !$parts || !in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true)
            || empty($parts['host']) || isset($parts['user']) || isset($parts['pass']) || isset($parts['fragment'])
            || !is_string($managementToken) || $managementToken === '' || preg_match('/[\x00-\x20\x7f]/', $managementToken)
            || $interval === false || $interval < 120 || $ttl === false || $ttl <= $interval
            || $ttl > PHP_INT_MAX - time() - $interval || PHP_INT_SIZE < 8) {
            throw new \RuntimeException('invalid_rbt_authbackend_settings');
        }
        return $this->settingsCache[$key] = [
            'prefix' => 'rbt:dvr:' . RbtToken::streamerKey($key) . ':authbackend:',
            'url' => $url,
            'management_token' => $managementToken,
            'rotation_interval' => $interval,
            'secret_ttl' => $ttl,
            // Do not reuse a confirmed secret after changing the Portal or credentials.
            'context' => hash('sha256', "R1-hostname\0" . $url . "\0" . $managementToken),
        ];
    }

    private function readSecret($key, $settings)
    {
        $raw = $this->redis->get($key);
        $secret = is_string($raw) ? json_decode($raw, true) : null;
        if (!is_array($secret) || ($secret['context'] ?? '') !== $settings['context']
            || !is_int($secret['secret_id'] ?? null) || $secret['secret_id'] < 1 || $secret['secret_id'] > 4294967295
            || !is_int($secret['valid_from'] ?? null) || !is_int($secret['issue_until'] ?? null)
            || !is_int($secret['expires_at'] ?? null) || !is_bool($secret['registered'] ?? null)
            || !is_string($secret['secret'] ?? null) || strlen((string)base64_decode($secret['secret'], true)) !== 32
            || $secret['valid_from'] >= $secret['issue_until'] || $secret['issue_until'] >= $secret['expires_at']) {
            return null;
        }
        return $secret;
    }

    private function canIssue($secret, $settings, $now)
    {
        return $secret && $secret['context'] === $settings['context'] && $secret['registered']
            && $secret['valid_from'] <= $now && $now < $secret['issue_until'] && $now < $secret['expires_at'];
    }

    private function createSecret($settings, $from)
    {
        if ($settings['secret_ttl'] > PHP_INT_MAX - $from) {
            throw new \RuntimeException('invalid_rbt_authbackend_expiry');
        }
        return [
            'secret_id' => random_int(1, 4294967295),
            'secret' => base64_encode(random_bytes(32)),
            'valid_from' => $from,
            'issue_until' => $from + $settings['rotation_interval'],
            'expires_at' => $from + $settings['secret_ttl'],
            'registered' => false,
            'context' => $settings['context'],
        ];
    }

    private function saveSecret($key, $secret)
    {
        $ttl = $secret['expires_at'] - time();
        if ($ttl <= 0 || !$this->redis->setex($key, $ttl, json_encode($secret, JSON_THROW_ON_ERROR))) {
            throw new \RuntimeException('rbt_authbackend_redis_write_failed');
        }
    }

    private function register($settings, $secret)
    {
        $body = json_encode([
            'secret_id' => $secret['secret_id'],
            'secret' => $secret['secret'],
            'valid_from' => $secret['valid_from'],
            'ttl' => $secret['expires_at'] - $secret['valid_from'],
        ]);
        $curl = curl_init($settings['url']);
        if ($curl === false) {
            throw new \RuntimeException('rbt_authbackend_http_init_failed');
        }
        $response = '';
        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json', 'Authorization: Bearer ' . $settings['management_token']],
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_TIMEOUT => 8,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_WRITEFUNCTION => static function ($curl, $chunk) use (&$response) {
                if (strlen($response) + strlen($chunk) > 16384) {
                    return 0;
                }
                $response .= $chunk;
                return strlen($chunk);
            },
        ]);
        $success = curl_exec($curl);
        $status = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        if ($success === false || !in_array($status, [200, 201], true)) {
            // Never log the request body, credentials or a remote error body.
            throw new \RuntimeException('rbt_authbackend_registration_failed_http_' . $status, $status);
        }
        $reply = json_decode($response, true);
        if (!is_array($reply) || ($reply['registered'] ?? null) !== true
            || ($reply['secret_id'] ?? null) !== $secret['secret_id']
            || ($reply['valid_from'] ?? null) !== $secret['valid_from'] || ($reply['expires_at'] ?? null) !== $secret['expires_at']) {
            throw new \RuntimeException('rbt_authbackend_invalid_registration_response');
        }
    }
}
