<?php

namespace hw\ip\common\rubetek;

use DateTime;
use DateTimeZone;
use Exception;

/**
 * Trait providing common functionality related to Rubetek devices.
 */
trait rubetek
{
    /**
     * @var string Default WEB interface password.
     */
    protected string $defaultWebPassword = 'Rubetek34';

    public function configureEventServer(string $url): void
    {
        ['host' => $server, 'port' => $port] = parse_url_ext($url);

        $this->apiCall('/settings/syslog', 'PATCH', [
            'address' => "$server:$port",
            'protocol' => 'udp',

            /*
             * 0 - Emergency
             * 1 - Alert
             * 2 - Critical
             * 3 - Error
             * 4 - Warning
             * 5 - Notice
             * 6 - Informational
             * 7 - Debug
             */
            'level' => 6, // Only for fw >= 2024.10
        ]);
    }

    public function configureNtp(string $server, int $port = 123, string $timezone = 'Europe/Moscow'): void
    {
        $timeSettings = $this->getConfiguration()['time'];
        $timeSettings['ntp_pool'] = "$server:$port";
        $timeSettings['timezone'] = $this->getOffsetByTimezone($timezone);
        $this->apiCall('/configuration', 'PATCH', ['time' => $timeSettings]);
    }

    public function getSysinfo(): array
    {
        $version = $this->apiCall('/version', 'GET', [], 3) ?? [];

        $sysinfo['DeviceID'] = $version['serial_number'] ?? null;
        $sysinfo['DeviceModel'] = $version['model'] ?? null;
        $sysinfo['HardwareVersion'] = $version['hardware_version'] ?? null;
        $sysinfo['SoftwareVersion'] = $version['firmware_version'] ?? null;

        $this->softwareVersion = $sysinfo['SoftwareVersion'];

        return $sysinfo;
    }

    public function reboot(): void
    {
        $this->apiCall('/reboot', 'POST');
    }

    public function reset(): void
    {
        $this->apiCall('/reset', 'POST');
    }

    public function setAdminPassword(string $password): void
    {
        // Without sleep() the following calls can respond "access is forbidden" or "account not found"
        $this->apiCall('/settings/account/password', 'PATCH', [
            'account' => 'admin',
            'current_password' => $this->defaultWebPassword,
            'new_password' => $password,
        ]);
        sleep(10);

        $this->apiCall('/settings/account/password', 'PATCH', [
            'account' => 'api_user',
            'current_password' => $this->defaultPassword,
            'new_password' => $password,
        ]);
        sleep(10);
    }

    public function syncData(): void
    {
        // Empty implementation
    }

    public function transformDbConfig(array $dbConfig): array
    {
        $timezone = $dbConfig['ntp']['timezone'];
        $dbConfig['ntp']['timezone'] = $this->getOffsetByTimezone($timezone);
        return $dbConfig;
    }

    /**
     * Make an API call.
     *
     * @param string $resource API endpoint.
     * @param string $method (Optional) HTTP method. Default is "GET".
     * @param array $payload (Optional) Request body as an array. Empty array by default.
     * @param int $timeout (Optional) The maximum number of seconds to allow cURL functions to execute.
     *
     * @return array|string API response.
     */
    protected function apiCall(
        string $resource,
        string $method = 'GET',
        array  $payload = [],
        int    $timeout = 0,
    ): array|string
    {
        $req = $this->url . $this->apiPrefix . $resource;

        $ch = curl_init($req);

        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_USERPWD, "$this->login:$this->password");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_VERBOSE, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);

        if ($payload) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Expect:', // Workaround for the 100-continue expectation
            ]);
        }

        $res = curl_exec($ch);
        curl_close($ch);

        return json_decode($res, true) ?? $res;
    }

    /**
     * Get device configuration.
     *
     * @return array Device configuration.
     */
    protected function getConfiguration(): array
    {
        return $this->apiCall('/configuration');
    }

    protected function getEventServer(): string
    {
        $syslogUrl = $this->getConfiguration()['syslog']['address'];
        [$server, $port] = array_pad(explode(':', $syslogUrl), 2, 514);

        return 'syslog.udp' . ':' . $server . ':' . $port;
    }

    protected function getNtpConfig(): array
    {
        [
            'timezone' => $offset,
            'ntp_pool' => $ntpPool,
        ] = $this->getConfiguration()['time'];

        [$server, $port] = array_pad(explode(':', $ntpPool), 2, 0);

        return [
            'server' => $server,
            'port' => $port,
            'timezone' => $offset,
        ];
    }

    /**
     * Get timezone representation for Rubetek.
     *
     * @param string $timezone Timezone identifier.
     *
     * @return string GMT offset without zeros (GMT+3 for example).
     */
    protected function getOffsetByTimezone(string $timezone): string
    {
        try {
            $time = new DateTime('now', new DateTimeZone($timezone));
            $offset = $time->format('P');
            return 'GMT' . preg_replace('/(?<=\+|)(0)(?=\d:\d{2})|:00/', '', $offset);
        } catch (Exception) {
            return 'GMT+3';
        }
    }

    protected function initializeProperties(): void
    {
        $this->login = 'api_user';
        $this->defaultPassword = 'api_password';
        $this->apiPrefix = '/api/v1';
    }

    /**
     * Determines if the software version is considered legacy.
     *
     * @return bool True if the software version is legacy, false otherwise.
     */
    protected function isLegacyVersion(): bool
    {
        return $this->getSoftwareVersion() !== null && $this->getSoftwareVersion() < '2025.04.171131928';
    }
}
