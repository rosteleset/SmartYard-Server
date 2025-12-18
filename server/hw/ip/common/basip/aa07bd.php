<?php

namespace hw\ip\common\basip;

use DateTime;
use DateTimeZone;
use Exception;
use http\Exception\RuntimeException;

/**
 * Trait providing common functionality related to BASIP devices.
 */
trait aa07bd
{

    /**
     * @var string stored bearer token used for authentication
     */
    public string $token;

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

        $this->apiCall('/v1/syslog/settings', 'POST', [
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
        $this->apiCall('/v1/network/ntp', 'POST', [
            'custom_server' => $server,
            'enabled' => true,
            'use_default' => false,
        ]);

        $this->apiCall('/v1/network/timezone', 'POST', [
            'timezone' => self::getOffsetByTimezone($timezone),
        ]);
    }

    public function getSysinfo(): array
    {
        $info = $this->apiCall('/info', 'GET', [], 3);

        if (
            empty($info['device_serial_number']) ||
            empty($info['device_model']) ||
//            empty($info['mcu_version']) ||
            empty($info['firmware_version'])
        ) {
            return [];
        }

        return [
            'DeviceID' => $info['device_serial_number'],
            'DeviceModel' => $info['device_model'],
            'HardwareVersion' => $info['mcu_version'],
            'SoftwareVersion' => $info['firmware_version'],
        ];
    }

    public function reboot(): void
    {
        $this->apiCall('/v1/system/reboot/run');
    }

    public function reset(): void
    {
        $this->apiCall('/v1/system/settings/default', 'POST');
    }

    public function setAdminPassword(string $password): void
    {
        $params = [
            'oldPassword' => $this->defaultPassword,
            'newPassword' => $password,
        ];

        $this->apiCall('/v1/device/settings/rtsp', 'POST', [
            'username' => $this->login,
            'password' => $password,

            /*
             * Perhaps this parameter should be removed from here.
             * Bad audio in SIP if it's enabled.
             */
            'is_audio_enabled' => false,
        ]);

        $this->apiCall('/v1/security/password/web/admin?' . http_build_query($params), 'POST');
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

    protected function apiCall(
        string $resource,
        string $method = 'GET',
        array  $payload = [],
        int    $timeout = 0,
    ): array|string
    {
        $req = $this->url . $this->apiPrefix . $resource;

        $ch = curl_init($req);

        curl_setopt_array($ch, [
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HTTPAUTH => CURLAUTH_BEARER,
            CURLOPT_UNRESTRICTED_AUTH => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_XOAUTH2_BEARER => $this->token,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $timeout,
        ]);

        if ($payload) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        }

        $res = curl_exec($ch);
        curl_close($ch);

        return json_decode($res, true) ?? $res;
    }

    protected function getEventServer(): string
    {
//        $settings = $this->apiCall('/v1/syslog/settings')['server'];
//        return 'http://' . $settings['server'] . ':' . $settings['port'];
        return "";
    }

    protected function getNtpConfig(): array
    {
        $ntp = $this->apiCall('/v1/network/ntp');
        $timezone = $this->apiCall('/v1/network/timezone');

        return [
            'server' => $ntp['custom_server'] ?? '',
            'port' => 123,
            'timezone' => $timezone['current_timezone'],
        ];
    }

    protected function initializeProperties(): void
    {
        $this->login = 'admin';
        $this->defaultPassword = '123456';
        $this->apiPrefix = '/api';
        $loginResult = $this->login()['token'];
        if ($loginResult && array_key_exists("token", $loginResult)) {
            $this->token = $loginResult["token"];
        } else {
            throw  new RuntimeException();
        }
    }

    protected function login(): array|string
    {
        $req = $this->url . $this->apiPrefix . "/v1/login?username=" . $this->login . "&password=" . md5($this->defaultPassword);

        $ch = curl_init($req);

        curl_setopt_array($ch, [
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_UNRESTRICTED_AUTH => true,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5,
        ]);

        $res = curl_exec($ch);
        curl_close($ch);

        return json_decode($res, true) ?? $res;
    }
}
