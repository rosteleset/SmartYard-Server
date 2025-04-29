<?php

namespace hw\ip\common\soyuz;

/**
 * Trait providing common functionality related to Soyuz devices.
 */
trait soyuz
{

    public function configureEventServer(string $url): void
    {
        ['host' => $server, 'port' => $port] = parse_url_ext($url);

        $this->apiCall('/v2/log', 'PUT', ['syslog' => [
            'server' => $server.':'.$port,
            'enable' => true,
            ]
        ]);

    }

    public function configureNtp(string $server, int $port = 123, string $timezone = 'Europe/Moscow'): void
    {
        $this->apiCall('/v2/system/tz', 'PUT', ['tz' => $timezone]);
        $this->apiCall('/v2/system/ntp', 'PUT', ['ntp'=>[$server]);
    }

    public function getSysinfo(): array
    {
        $sysinfo = [];
        $info = $this->apiCall('/v2/system/info', 'GET', [], 3);
        $versions = $this->apiCall('/v2/system/versions', 'GET', [], 3);

        if ($info && $versions) {
            $sysinfo['DeviceID'] = $info['deviceID'];
            $sysinfo['DeviceModel'] = $info['model'];
            $sysinfo['HardwareVersion'] = '1.1.0';
            $sysinfo['SoftwareVersion'] = $versions['sw'].'b'.$versions['sw_sub'];
        }

        return $sysinfo;
    }

    public function reboot(): void
    {
        $this->apiCall('/v2/system/reboot', 'PUT');
    }

    public function reset(): void
    {
        $this->apiCall('/v2/system/factory-reset', 'PUT');
    }

    public function setAdminPassword(string $password): void
    {
        $this->apiCall('/v2/auth/change_api_password', 'PUT', ['newPassword' => $password]);
    }

    public function syncData(): void
    {
        // Empty implementation
    }

    /**
     * Make an API call.
     *
     * @param string $resource API endpoint.
     * @param string $method (Optional) HTTP method. Default is "GET".
     * @param array $payload (Optional) Request body as an array. Empty array by default.
     * @param int $timeout (Optional) The maximum number of seconds to allow cURL functions to execute.
     *
     * @return array|bool|string API response.
     */
    protected function apiCall(
        string $resource,
        string $method = 'GET',
        array  $payload = [],
        int    $timeout = 0,
    ): bool|array|string
    {
        $req = $this->url . $resource;

        $ch = curl_init($req);

        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_USERPWD, "$this->login:$this->password");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_VERBOSE, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);

        if ($payload) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        }

        $res = curl_exec($ch);
        curl_close($ch);

        $array_res = json_decode($res, true);

        if ($array_res === null) {
            return $res;
        }

        return $array_res;
    }

    protected function getEventServer(): string
    {
        $syslog = $this->apiCall('/v2/log');
        return 'syslog.udp' . ':' . $syslog['syslog']['server'];

    }

    protected function getNtpConfig(): array
    {
        $general = $this->apiCall('/v2/system/general');
        $ntp = $this->apiCall('/v2/system/ntp');
        return [
            'server' => $ntp[0],
            'port' => 123,
            'timezone' => $general['tz'],
        ];
    }

    protected function initializeProperties(): void
    {
        $this->login = 'api';
        $this->defaultPassword = '123456';
    }
}
