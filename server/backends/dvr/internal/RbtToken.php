<?php

namespace backends\dvr;

/** R1 token generation. Keep the wire format in sync with SesamePortal/app/RbtAuthBackend.php. */
final class RbtToken
{
    const PREFIX = 'R1.';

    public static function streamerKey($url)
    {
        $parts = is_string($url) ? parse_url($url) : false;
        $scheme = strtolower($parts['scheme'] ?? '');
        if (!$parts || !in_array($scheme, ['http', 'https'], true) || empty($parts['host'])) {
            throw new \InvalidArgumentException('invalid_dvr_url');
        }
        $host = self::hostnameFromUrl($url);
        if (strpos($host, ':') !== false) {
            $host = '[' . $host . ']';
        }
        $port = $parts['port'] ?? ($scheme === 'https' ? 443 : 80);
        if ($port < 1 || $port > 65535) {
            throw new \InvalidArgumentException('invalid_dvr_port');
        }
        return $host . ':' . $port;
    }

    public static function hostnameFromUrl($url)
    {
        $parts = is_string($url) ? parse_url($url) : false;
        if (!$parts || !in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true)) {
            throw new \InvalidArgumentException('invalid_stream_url');
        }
        return self::canonicalHostname($parts['host'] ?? '');
    }

    private static function canonicalHostname($host)
    {
        if (!is_string($host)) {
            throw new \InvalidArgumentException('invalid_dvr_host');
        }
        if (substr($host, 0, 1) === '[' && substr($host, -1) === ']') {
            $host = substr($host, 1, -1);
        }
        if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return inet_ntop(inet_pton($host));
        }
        $host = strtolower(rtrim($host, '.'));
        if (strlen($host) > 253 || !preg_match('/\A[a-z0-9](?:[a-z0-9.-]*[a-z0-9])?\z/', $host)) {
            throw new \InvalidArgumentException('invalid_dvr_host');
        }
        return $host;
    }

    public static function validStreamName($name)
    {
        return is_string($name) && strlen($name) <= 128
            && preg_match('/\A[\p{L}\p{N}][\p{L}\p{N}._-]*\z/u', $name) === 1;
    }

    public static function streamFromUrl($url)
    {
        $path = parse_url($url, PHP_URL_PATH);
        if (!is_string($path) || preg_match('/%(?![0-9a-f]{2})/i', $path)) {
            throw new \InvalidArgumentException('invalid_stream_url');
        }
        $name = rawurldecode(explode('/', ltrim($path, '/'))[0]);
        if (!self::validStreamName($name)) {
            throw new \InvalidArgumentException('invalid_stream_name');
        }
        return $name;
    }

    public static function create($secret, $secretId, $subscriberId, $hostname, $streamName)
    {
        if (PHP_INT_SIZE < 8 || !is_string($secret) || strlen($secret) !== 32
            || !is_int($secretId) || $secretId < 1 || $secretId > 4294967295
            || !self::validStreamName($streamName)) {
            throw new \InvalidArgumentException('invalid_rbt_token_parameters');
        }
        // All ones (uint64) is reserved for RBT's existing service subscriberId=-1.
        $uid = filter_var($subscriberId, FILTER_VALIDATE_INT);
        if ($uid === false || $uid < -1) {
            throw new \InvalidArgumentException('invalid_subscriber_id');
        }
        $header = pack('N', $secretId) . pack('J', $uid);
        $mac = self::mac($secret, $header, self::canonicalHostname($hostname), $streamName);
        return self::PREFIX . self::base64Url($header . $mac);
    }

    private static function mac($secret, $header, $hostname, $streamName)
    {
        $message = self::PREFIX . $header
            . pack('n', strlen($hostname)) . $hostname
            . pack('n', strlen($streamName)) . $streamName;
        return substr(hash_hmac('sha256', $message, $secret, true), 0, 16);
    }

    private static function base64Url($raw)
    {
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }
}
