<?php

namespace hw\ip\camera\dahua;

use hw\ip\camera\camera;
use InvalidArgumentException;
use LogicException;
use RuntimeException;
use UnexpectedValueException;

/** Dahua IPC motion/snapshot driver for the documented four-window 22x18 CGI layout. */
class dahua extends camera
{
    public function __construct(string $url, string $password, bool $firstTime = false, bool $lazy = false)
    {
        if ($firstTime) {
            throw new LogicException('Activate Dahua and set its password in the camera UI first');
        }
        $parts = parse_url($url);
        if (!$parts || !in_array($parts['scheme'] ?? '', ['http', 'https'], true) ||
            empty($parts['host']) || isset($parts['pass'], $parts['user']) ||
            !empty($parts['query']) || !empty($parts['fragment']) || !in_array($parts['path'] ?? '', ['', '/'], true)) {
            throw new InvalidArgumentException('Use a Dahua base HTTP(S) URL; keep its password in credentials');
        }
        $login = rawurldecode($parts['user'] ?? 'admin');
        $base = $parts['scheme'] . '://' . $parts['host'] . (isset($parts['port']) ? ':' . $parts['port'] : '');
        parent::__construct($base, $password, false, true);
        $this->login = $login;
        if (!$lazy && !$this->ping()) {
            throw new RuntimeException('Dahua camera is unavailable or CGI credentials are invalid');
        }
    }

    protected function initializeProperties(): void
    {
        $this->login = 'admin';
        $this->apiPrefix = '/cgi-bin';
    }

    protected function apiCall(string $resource, array $params = []): string
    {
        $ch = curl_init($this->url . '/cgi-bin/' . $resource . '?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986));
        curl_setopt_array($ch, [
            CURLOPT_HTTPAUTH => CURLAUTH_DIGEST,
            CURLOPT_USERPWD => $this->login . ':' . $this->password,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
        ]);
        $response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $errno = curl_errno($ch);
        curl_close($ch);
        // Never include a credential-bearing URL, response body or curl diagnostic in logs.
        if ($response === false || $status !== 200 || preg_match('/^\s*Error\b/i', $response)) {
            throw new RuntimeException("Dahua CGI request failed (HTTP $status, curl $errno)");
        }
        return $response;
    }

    public static function parseConfig(string $body): array
    {
        $result = [];
        foreach (preg_split('/\r?\n/', trim($body)) as $line) {
            if (str_contains($line, '=')) {
                [$key, $value] = explode('=', $line, 2);
                $result[preg_replace('/^table\./', '', trim($key))] = trim($value);
            }
        }
        if (!$result) {
            throw new UnexpectedValueException('Dahua CGI returned no configuration');
        }
        return $result;
    }

    private function motionConfig(): array
    {
        $config = self::parseConfig($this->apiCall('configManager.cgi', ['action' => 'getConfig', 'name' => 'MotionDetect']));
        if (!in_array($config['MotionDetect[0].Enable'] ?? null, ['true', 'false'], true)) {
            throw new UnexpectedValueException('Dahua motion enable flag is missing or unsupported');
        }
        return $config;
    }

    public function configureMotionDetection(array $detectionZones): void
    {
        $windows = MotionGrid::fromZones($detectionZones);
        $config = $this->motionConfig();
        MotionGrid::readWindows($config);
        $params = ['action' => 'setConfig', 'MotionDetect[0].Enable' => $detectionZones ? 'true' : 'false'];
        foreach ($windows as $i => $rows) {
            foreach ($rows as $row => $mask) {
                $params["MotionDetect[0].MotionDetectWindow[$i].Region[$row]"] = $mask;
            }
        }
        // Some firmware exposes the legacy union as well. Do not leave old active cells there.
        for ($row = 0; $row < MotionGrid::ROWS; $row++) {
            $key = "MotionDetect[0].Region[$row]";
            if (array_key_exists($key, $config)) {
                $params[$key] = $windows[0][$row] | $windows[1][$row] | $windows[2][$row] | $windows[3][$row];
            }
        }
        if (trim($this->apiCall('configManager.cgi', $params)) !== 'OK') {
            throw new RuntimeException('Dahua did not acknowledge motion configuration');
        }
        $saved = $this->motionConfig();
        if ($saved['MotionDetect[0].Enable'] !== $params['MotionDetect[0].Enable'] ||
            MotionGrid::readWindows($saved) !== $windows) {
            throw new RuntimeException('Dahua motion read-back differs from the requested configuration');
        }
    }

    protected function getMotionDetectionConfig(): array
    {
        $config = $this->motionConfig();
        $windows = MotionGrid::readWindows($config);
        return $config['MotionDetect[0].Enable'] === 'true' ? MotionGrid::canonical($windows) : [];
    }

    public function transformDbConfig(array $dbConfig): array
    {
        $dbConfig['motionDetection'] = MotionGrid::canonical(MotionGrid::fromZones($dbConfig['motionDetection'] ?? []));
        // This driver deliberately owns only motion. Events are pulled by a separate service.
        $dbConfig['eventServer'] = '';
        $dbConfig['ntp'] = ['server' => '', 'port' => 123, 'timezone' => 'UTC'];
        $dbConfig['osdText'] = '';
        return $dbConfig;
    }

    public function getCamshot(): string
    {
        $image = $this->apiCall('snapshot.cgi', ['channel' => 1]);
        if (!str_starts_with($image, "\xff\xd8")) {
            throw new UnexpectedValueException('Dahua did not return a JPEG snapshot');
        }
        return $image;
    }

    public function getSysinfo(): array
    {
        $info = self::parseConfig($this->apiCall('magicBox.cgi', ['action' => 'getSystemInfo']));
        return [
            'DeviceID' => $info['serialNumber'] ?? '',
            'DeviceModel' => $info['deviceType'] ?? '',
            'HardwareVersion' => $info['hardwareVersion'] ?? '',
            'SoftwareVersion' => $info['softwareVersion'] ?? '',
        ];
    }

    public function ping(): bool
    {
        try {
            return $this->getSysinfo()['DeviceID'] !== '';
        } catch (\Throwable) {
            return false;
        }
    }

    protected function getEventServer(): string { return ''; }
    // The shared builder requires a three-field tuple even for an unmanaged section.
    protected function getNtpConfig(): array { return ['', 123, 'UTC']; }
    protected function getOsdText(): string { return ''; }
    public function syncData(): void { /* CGI writes are applied immediately. */ }
    public function configureEventServer(string $url): void
    {
        if ($url !== '') { throw new LogicException('Dahua events use the HTTP subscription service'); }
    }
    public function configureNtp(string $server, int $port = 123, string $timezone = 'Europe/Moscow'): void
    { throw new LogicException('Configure Dahua NTP in the camera UI'); }
    public function setOsdText(string $text = ''): void
    { throw new LogicException('Configure Dahua OSD in the camera UI'); }
    public function setAdminPassword(string $password): void
    { throw new LogicException('Change Dahua credentials in the camera UI'); }
    public function reset(): void
    { throw new LogicException('Factory reset is not supported by this Dahua driver'); }
    public function reboot(): void
    {
        if (trim($this->apiCall('magicBox.cgi', ['action' => 'reboot'])) !== 'OK') {
            throw new RuntimeException('Dahua did not acknowledge reboot');
        }
    }
}
