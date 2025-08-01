<?php

namespace hw\ip\common\akuvox;

use DateTime;
use DateTimeZone;
use Exception;
use hw\ValueObject\{
    NtpServer,
    Port,
    ServerAddress,
};

/**
 * Trait providing common functionality related to Akuvox devices.
 */
trait akuvox
{
    public function configureEventServer(string $url): void // Need to reboot after that
    {
        ['host' => $server, 'port' => $port] = parse_url_ext($url);

        $this->setConfigParams([
            'Config.Settings.LOGLEVEL.RemoteSyslog' => '1',
            'Config.Settings.LOGLEVEL.RemoteServer' => $server,
            'Config.Settings.LOGLEVEL.RemoteServerPort' => "$port",
        ]);

        $this->reboot();
        $this->wait();
    }

    public function getNtpServer(): NtpServer
    {
        [$name, $server] = $this->getConfigParams([
            'Config.Settings.SNTP.Name',
            'Config.Settings.SNTP.NTPServer1',
        ]);

        $timezone = current(array_filter(timezone_identifiers_list(), fn($tz) => stripos($tz, $name) !== false));
        $server = explode(':', $server)[0];

        return new ntpServer(
            address: ServerAddress::fromString($server),
            port: new Port(123),
            timezone: $timezone,
        );
    }

    public function getSysinfo(): array
    {
        $info = $this->apiCall('/system/info', 'GET', [], 3)['data']['Status'] ?? [];

        $sysinfo['DeviceID'] = str_replace(':', '', $info['MAC']);
        $sysinfo['DeviceModel'] = $info['Model'];
        $sysinfo['HardwareVersion'] = $info['HardwareVersion'];
        $sysinfo['SoftwareVersion'] = $info['FirmwareVersion'];

        return $sysinfo;
    }

    public function reboot(): void
    {
        $this->apiCall('/system/reboot');
    }

    public function reset(): void
    {
        $this->apiCall('/config/reset_factory');
    }

    public function setAdminPassword(string $password): void
    {
        $this->setConfigParams([
            'Config.Settings.WEB_LOGIN.Password' => $password, // WEB
            'Config.DoorSetting.APIFCGI.Password' => $password, // API
            'Config.DoorSetting.RTSP.Password' => $password, // RTSP
        ]);

        sleep(1);
    }

    public function setNtpServer(NtpServer $server): void
    {
        ['offset' => $offset, 'name' => $name] = $this->getOffsetAndNameByTz($server->timezone);

        $this->setConfigParams([
            'Config.Settings.SNTP.Enable' => '1',
            'Config.Settings.SNTP.TimeZone' => $offset,
            'Config.Settings.SNTP.Name' => $name,
            'Config.Settings.SNTP.NTPServer1' => $server->address,
        ]);
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
     * @return array API response.
     */
    protected function apiCall(string $resource, string $method = 'GET', array $payload = [], int $timeout = 0): array
    {
        $url = explode('/#', $this->url)[0];
        $req = $url . $this->apiPrefix . $resource;

        $ch = curl_init($req);

        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_USERPWD, "$this->login:$this->password");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_VERBOSE, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);

        if ($payload) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Expect:', // Workaround for the 100-continue expectation
            ]);
        }

        $res = curl_exec($ch);
        curl_close($ch);

        return json_decode($res, true) ?? [];
    }

    /**
     * Get parameters values from config section (target=config, action=get).
     *
     * @param array $params An array of parameters that need to be obtained from the device.
     *
     * @return array An array of values.
     */
    protected function getConfigParams(array $params): array
    {
        $res = $this->apiCall('', 'POST', [
            'target' => 'config',
            'action' => 'get',
            'data' => ['item' => $params],
        ]);

        return array_values($res['data']);
    }

    protected function getEventServer(): string
    {
        [$server, $port] = $this->getConfigParams([
            'Config.Settings.LOGLEVEL.RemoteServer',
            'Config.Settings.LOGLEVEL.RemoteServerPort',
        ]);

        return 'syslog.udp' . ':' . $server . ':' . $port;
    }

    /**
     * Get timezone representation for Akuvox.
     *
     * @param string $timezone Timezone identifier.
     *
     * @return array An array with GMT offset and city name.
     */
    protected function getOffsetAndNameByTz(string $timezone): array
    {
        try {
            $now = new DateTime('now', new DateTimeZone($timezone));
            $offset = $now->format('P');

            return [
                'offset' => 'GMT' . preg_replace('/(?<=[+-])0(\d)/', '$1', $offset),
                'name' => basename($timezone),
            ];
        } catch (Exception) {
            return ['GMT+3:00', 'Moscow'];
        }
    }

    protected function initializeProperties(): void
    {
        $this->login = 'admin';
        $this->defaultPassword = 'httpapi';
        $this->apiPrefix = '/api';
    }

    /**
     * Set data in config section (target=config, action=set).
     *
     * @param array $data An associative array containing data as param => value.
     *
     * @return void
     */
    protected function setConfigParams(array $data): void
    {
        $this->apiCall('', 'POST', [
            'target' => 'config',
            'action' => 'set',
            'data' => $data,
        ]);
    }
}
