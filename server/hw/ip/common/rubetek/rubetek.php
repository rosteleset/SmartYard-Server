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

    public function configureEventServer(string $server, int $port)
    {
        $this->apiCall('/settings/syslog', 'PATCH', [
            'address' => "$server:$port",
            'protocol' => 'udp',
        ]);
    }

    public function configureNtp(string $server, int $port = 123, string $timezone = 'Europe/Moscow')
    {
        $timeSettings = $this->getConfig()['time'];
        $timeSettings['ntp_pool'] = "$server:$port";
        $timeSettings['timezone'] = $this->getOffsetByTimezone($timezone);
        $this->apiCall('/configuration', 'PATCH', ['time' => $timeSettings]);
    }

    public function getSysinfo(): array
    {
        $version = $this->apiCall('/version');

        $sysinfo['DeviceID'] = $version['serial_number'];
        $sysinfo['DeviceModel'] = $version['model'];
        $sysinfo['HardwareVersion'] = $version['hardware_version'];
        $sysinfo['SoftwareVersion'] = $version['firmware_version'];

        return $sysinfo;
    }

    public function reboot()
    {
        $this->apiCall('/reboot', 'POST');
    }

    public function reset()
    {
        $this->apiCall('/reset', 'POST');
    }

    public function setAdminPassword(string $password)
    {
        // TODO: without sleep() the following calls can response "access is forbidden" or "account not found"
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

    /**
     * Make an API call.
     *
     * @param string $resource API endpoint.
     * @param string $method (Optional) HTTP method. Default is "GET".
     * @param array $payload (Optional) Request body as an array. Empty array by default.
     *
     * @return array|string API response.
     */
    protected function apiCall($resource, $method = 'GET', $payload = [])
    {
        $req = $this->url . $this->apiPrefix . $resource;

//        echo $method . PHP_EOL;
//        echo $req . PHP_EOL;
//        echo 'Payload: ' . json_encode($payload, JSON_UNESCAPED_UNICODE) . PHP_EOL;
//        echo '---------------------------------' . PHP_EOL;

        $ch = curl_init($req);

        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_USERPWD, "$this->login:$this->password");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_VERBOSE, false);

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
    protected function getConfig()
    {
        return $this->apiCall('/configuration');
    }

    protected function getEventServerConfig(): array
    {
        $syslogUrl = $this->getConfig()['syslog']['address'];
        [$server, $port] = array_pad(explode(':', $syslogUrl), 2, 514);

        return [
            'server' => $server,
            'port' => $port,
        ];
    }

    protected function getNtpConfig(): array
    {
        [
            'timezone' => $offset,
            'ntp_pool' => $ntpPool,
        ] = $this->getConfig()['time'];

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
    protected function getOffsetByTimezone(string $timezone)
    {
        try {
            $time = new DateTime('now', new DateTimeZone($timezone));
            $offset = $time->format('P');
            return 'GMT' . preg_replace('/(?<=\+|)(0)(?=\d:\d{2})|:00/', '', $offset);
        } catch (Exception $e) {
            return 'GMT+3';
        }
    }

    protected function initializeProperties()
    {
        $this->login = 'api_user';
        $this->defaultPassword = 'api_password';
        $this->apiPrefix = '/api/v1';
    }
}
