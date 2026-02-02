<?php

namespace hw\ip\common\basip;

use DateTime;
use DateTimeZone;
use Exception;

/**
 * Trait providing common functionality related to BASIP devices.
 */
trait basip
{
    /**
     * Get timezone representation for BASIP
     *
     * @param string $timezone Timezone identifier.
     * @return string UTC offset (UTC+03:00 for example).
     */
    protected static function getOffsetByTimezone(string $timezone): string
    {
        try {
            $time = new DateTime('now', new DateTimeZone($timezone));
            $offset = $time->format('P');

            if ($offset === '+00:00') {
                return 'UTC±00:00';
            }

            return 'UTC' . $offset;
        } catch (Exception) {
            return 'UTC+03:00';
        }
    }

    public function configureEventServer(string $url): void
    {
        ['host' => $server, 'port' => $port] = parse_url_ext($url);

        $this->client->call('/v1/syslog/settings', 'POST', [
            'enabled' => $url !== '',
            'server' => [
                'port' => $port,
                'server' => $server,
                'severity' => 6,
            ],
            'tag' => '',
        ]);
    }

    public function configureNtp(string $server, int $port = 123, string $timezone = 'Europe/Moscow'): void
    {
        $this->client->call('/v1/network/ntp', 'POST', [
            'custom_server' => $server,
            'enabled' => true,
            'use_default' => false,
        ]);

        $this->client->call('/v1/network/timezone', 'POST', [
            'timezone' => self::getOffsetByTimezone($timezone),
        ]);
    }

    public function getSysinfo(): array
    {
        $info = $this->client->call('/info', 'GET', [], 3);

        return [
            'DeviceID' => $info['device_serial_number'] ?? null,
            'DeviceModel' => $info['device_model'] ?? null,
            'HardwareVersion' => $info['mcu_version'] ?? null,
            'SoftwareVersion' => $info['firmware_version'] ?? null,
        ];
    }

    public function reboot(): void
    {
        $this->client->call('/v1/system/reboot/run');
    }

    public function reset(): void
    {
        $this->client->call('/v1/system/settings/default', 'POST');
    }

    public function setAdminPassword(string $password): void
    {
        $params = [
            'oldPassword' => $this->defaultPassword,
            'newPassword' => $password,
        ];

        $this->client->call('/v1/device/settings/rtsp', 'POST', [
            'username' => $this->login,
            'password' => $password,

            /*
             * Perhaps this parameter should be removed from here.
             * Bad audio in SIP if it's enabled.
             */
            'is_audio_enabled' => false,
        ]);

        $this->client->call('/v1/security/password/admin?' . http_build_query($params), 'POST');
    }

    public function syncData(): void
    {
        // Empty implementation
    }

    public function transformDbConfig(array $dbConfig): array
    {
        $timezone = $dbConfig['ntp']['timezone'];
        $dbConfig['ntp']['port'] = 123;
        $dbConfig['ntp']['timezone'] = self::getOffsetByTimezone($timezone);
        return $dbConfig;
    }

    protected function getEventServer(): string
    {
        $settings = $this->client->call('/v1/syslog/settings')['server'];
        return 'http://' . $settings['server'] . ':' . $settings['port'];
    }

    protected function getNtpConfig(): array
    {
        $ntp = $this->client->call('/v1/network/ntp');
        $timezone = $this->client->call('/v1/network/timezone');

        return [
            'server' => $ntp['custom_server'] ?? '',
            'port' => 123,
            'timezone' => $timezone['timezone'],
        ];
    }

    protected function initializeProperties(): void
    {
        $this->login = 'admin';
        $this->defaultPassword = '123456';
        $this->apiPrefix = '/api';
    }
}
