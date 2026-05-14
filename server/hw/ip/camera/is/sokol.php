<?php

namespace hw\ip\camera\is;

use hw\ip\camera\{
    camera,
    entities\DetectionZone,
};
use hw\ip\domophone\is\HttpClient\HttpClient;

class sokol extends camera
{
    protected HttpClient $client;

    public function __construct(string $url, string $password, bool $firstTime = false)
    {
        $this->client = new HttpClient(rtrim($url, '/'), $firstTime ? '123456' : $password);
        parent::__construct($url, $password, $firstTime);
    }

    public function configureEventServer(string $url): void
    {
        $syslogUrl = parse_url_ext($url);

        $this->client->request('/v1/network/syslog', 'PUT', [
            'addr' => $syslogUrl['host'],
            'port' => (int)$syslogUrl['port'],
            'severity' => 6, // Info
            'transport' => 1, // UDP
        ]);
    }

    public function configureMotionDetection(array $detectionZones): void
    {
        $this->client->request('/camera/md', 'PUT', [
            'md_enable' => !empty($detectionZones),
            'md_frame_shift' => 1,
            'md_area_thr' => 100000, // For people at close range
            'md_rect_color' => '0xFF0000',
            'md_frame_int' => 30,
            'md_rects_enable' => false,
            'md_logs_enable' => true,
            'md_send_snapshot_enable' => false,
            'md_send_snapshot_interval' => 1,
            'snap_send_url' => '',
        ]);
    }

    public function configureNtp(string $server, int $port = 123, string $timezone = 'Europe/Moscow'): void
    {
        $this->client->request('/system/settings', 'PUT', [
            'tz' => $timezone,
            'ntp' => ["$server:$port"],
        ]);
    }

    public function getCamshot(): string
    {
        return $this->client->rawRequest('/camera/snapshot', 'GET', [], 3);
    }

    public function getSysinfo(): array
    {
        $info = $this->client->request('/system/info', timeout: 3);
        $versions = $this->client->request('/v2/system/versions', timeout: 3);

        return [
            'DeviceID' => $info['deviceID'] ?? null,
            'DeviceModel' => $info['deviceModel'] ?? null,
            'HardwareVersion' => $versions['opt']['versions']['hw']['name'] ?? null,
            'SoftwareVersion' => $versions['opt']['name'] ?? null,
        ];
    }

    public function reboot(): void
    {
        $this->client->request('/system/reboot', 'PUT');
    }

    public function reset(): void
    {
        $this->client->request('/system/factory-reset', 'PUT');
    }

    public function setAdminPassword(string $password): void
    {
        $this->client->request('/user/change_password', 'PUT', ['newPassword' => $password]);
        $this->client->setPassword($password);
        $this->password = $password;
    }

    public function setOsdText(string $text = ''): void
    {
        $hwVer = floor($this->getSysinfo()['HardwareVersion'] ?? 0);

        $firstStringParams = [
            'size' => 1,
            'text' => $hwVer == 5 ? $text : '',
            'color' => '0xFFFFFF',
            'date' => [
                'enable' => true,
                'format' => '%d-%m-%Y',
            ],
            'time' => [
                'enable' => true,
                'format' => '%H:%M:%S',
            ],
            'position' => [
                'x' => 2,
                'y' => 2,
            ],
            'background' => [
                'enable' => true,
                'color' => '0x000000',
            ],
        ];

        $secondStringParams = $hwVer == 5 ? [] : [
            'size' => 1,
            'text' => $text,
            'color' => '0xFFFFFF',
            'date' => [
                'enable' => false,
                'format' => '%d-%m-%Y',
            ],
            'time' => [
                'enable' => false,
                'format' => '%H:%M:%S',
            ],
            'position' => [
                'x' => 2,
                'y' => 702,
            ],
            'background' => [
                'enable' => true,
                'color' => '0x000000',
            ],
        ];

        $this->client->request('/v2/camera/osd', 'PUT', [$firstStringParams, $secondStringParams]);
    }

    public function syncData(): void
    {
        // Empty implementation
    }

    public function transformDbConfig(array $dbConfig): array
    {
        if ($dbConfig['motionDetection']) {
            $dbConfig['motionDetection'] = [new DetectionZone(0, 0, 100, 100)];
        }

        return $dbConfig;
    }

    protected function getEventServer(): string
    {
        $syslog = $this->client->request('/v1/network/syslog');
        return 'syslog.udp' . ':' . $syslog['addr'] . ':' . $syslog['port'];
    }

    protected function getMotionDetectionConfig(): array
    {
        ['md_enable' => $mdEnabled] = $this->client->request('/camera/md');

        if ($mdEnabled) {
            return [new DetectionZone(0, 0, 100, 100)];
        }

        return [];
    }

    protected function getNtpConfig(): array
    {
        $settings = $this->client->request('/system/settings');
        $ntpUrl = $settings['ntp'][0] ?? '';
        [$server, $port] = array_pad(explode(':', $ntpUrl, 2), 2, 123);

        return [
            'server' => $server,
            'port' => $port,
            'timezone' => $settings['tz'],
        ];
    }

    protected function getOsdText(): string
    {
        $osdParams = $this->client->request('/v2/camera/osd');
        return $osdParams[0]['text'] ?: $osdParams[1]['text'];
    }

    protected function initializeProperties(): void
    {
        $this->login = 'root';
        $this->defaultPassword = '123456';
    }
}
