<?php

namespace hw\ip\common\is;

/**
 * Trait providing common functionality related to Intersvyaz (IS) devices.
 */
trait is
{

    public function configureNtp(string $server, int $port = 123, string $timezone = 'Europe/Moscow')
    {
        $this->apiCall('/system/settings', 'PUT', [
            'tz' => $timezone,
            'ntp' => [$server],
        ]);
    }

    public function getSysinfo(): array
    {
        $sysinfo = [];
        $info = $this->apiCall('/system/info');
        $versions = $this->apiCall('/v2/system/versions');

        if ($info && $versions) {
            $sysinfo['DeviceID'] = $info['deviceID'];
            $sysinfo['DeviceModel'] = $info['model'];
            $sysinfo['HardwareVersion'] = $versions['opt']['versions']['hw']['name'];
            $sysinfo['SoftwareVersion'] = $versions['opt']['name'];
        }

        return $sysinfo;
    }

    public function reboot()
    {
        $this->apiCall('/system/reboot', 'PUT');
    }

    public function reset()
    {
        $this->apiCall('/system/factory-reset', 'PUT');
    }

    public function setAdminPassword(string $password)
    {
        $this->apiCall('/user/change_password', 'PUT', ['newPassword' => $password]);
    }

    public function syncData()
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
    protected function apiCall(string $resource, string $method = 'GET', array $payload = [], int $timeout = 0)
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

    protected function getNtpConfig(): array
    {
        $settings = $this->apiCall('/system/settings');

        return [
            'server' => $settings['ntp'][0],
            'port' => 123,
            'timezone' => $settings['tz'],
        ];
    }

    protected function initializeProperties()
    {
        $this->login = 'root';
        $this->defaultPassword = '123456';
    }
}
