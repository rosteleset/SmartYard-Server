<?php

namespace hw\ip\common\basip;

use DateTime;
use DateTimeZone;
use Exception;

/**
 * Trait providing common functionality related to BasIP devices.
 */
trait Basip
{
    /**
     * Get timezone representation for BasIP devices.
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

    /**
     * Returns the name of the parameter that contains the timezone value.
     *
     * @return string The timezone parameter name.
     */
    protected static function getTimezoneParamName(): string
    {
        return 'timezone';
    }

    public function configureEventServer(string $url): void
    {
        ['host' => $server, 'port' => $port] = parse_url_ext($url);

        $this->client->request('/v1/syslog/settings', 'POST', [
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
        $this->client->request('/v1/network/ntp', 'POST', [
            'custom_server' => $server,
            'enabled' => true,
            'use_default' => false,
        ]);

        $this->client->request('/v1/network/timezone', 'POST', [
            static::getTimezoneParamName() => self::getOffsetByTimezone($timezone),
        ]);
    }

    public function getSysinfo(): array
    {
        $info = $this->client->request('/info', 'GET', [], 3);

        return [
            'DeviceID' => $info['device_serial_number'] ?? null,
            'DeviceModel' => $info['device_model'] ?? null,
            'HardwareVersion' => $info['mcu_version'] ?? null,
            'SoftwareVersion' => $info['firmware_version'] ?? null,
        ];
    }

    public function reboot(): void
    {
        $this->client->request('/v1/system/reboot/run');
    }

    public function reset(): void
    {
        $this->client->request('/v1/system/settings/default', 'POST');
    }

    public function setAdminPassword(string $password): void
    {
        $this->setDevicePassword($password);
        $this->client->setPassword($password);
        $this->password = $password;

        $this->setRtspPassword($password);
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
        $settings = $this->client->request('/v1/syslog/settings')['server'];
        return 'http://' . $settings['server'] . ':' . $settings['port'];
    }

    protected function getNtpConfig(): array
    {
        $ntp = $this->client->request('/v1/network/ntp');
        $timezone = $this->client->request('/v1/network/timezone');

        return [
            'server' => $ntp['custom_server'] ?? '',
            'port' => 123,
            'timezone' => $timezone[static::getTimezoneParamName()],
        ];
    }

    protected function initializeProperties(): void
    {
        $this->login = 'admin';
        $this->defaultPassword = '123456';
    }

    /**
     * Changes the password for RTSP.
     *
     * @param string $password New password.
     * @return void
     */
    protected function setRtspPassword(string $password): void
    {
        $this->client->request('/v1/device/settings/rtsp', 'POST', [
            'username' => $this->login,
            'password' => $password,

            /*
             * Perhaps this parameter should be removed from here.
             * Bad audio in SIP if it's enabled.
             */
            'is_audio_enabled' => false,
        ]);
    }

    /**
     * Changes the administrator password used for WEB and API access.
     *
     * @param string $password New password.
     * @return void
     */
    abstract protected function setDevicePassword(string $password): void;
}
