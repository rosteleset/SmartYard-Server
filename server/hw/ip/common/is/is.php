<?php

namespace hw\ip\common\is;

/**
 * Trait providing common functionality related to Intersvyaz (IS) devices.
 */
trait is
{

    /**
     * @var int|null Caches the hardware version once fetched.
     */
    protected ?int $hardwareVersion = null;

    /**
     * @var string|null Caches the software version once fetched.
     */
    protected ?string $softwareVersion = null;

    public function configureEventServer(string $url)
    {
        if ($this->isLegacyVersion()) {
            $this->configureEventServerLegacy($url);
            return;
        }

        ['host' => $server, 'port' => $port] = parse_url_ext($url);

        $this->apiCall('/v1/network/syslog', 'PUT', [
            'addr' => $server,
            'port' => (int)$port,
        ]);
    }

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
        $info = $this->apiCall('/system/info', 'GET', [], 3);
        $versions = $this->apiCall('/v2/system/versions', 'GET', [], 3);

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

    /**
     * @param string $url
     *
     * @return void
     *
     * @deprecated
     * @see configureEventSever()
     */
    protected function configureEventServerLegacy(string $url)
    {
        ['host' => $server, 'port' => $port] = parse_url_ext($url);

        $template = file_get_contents(__DIR__ . '/templates/custom.conf');
        $template .= "*.*;cron.none     @$server:$port;ProxyForwardFormat";
        $host = parse_url($this->url)['host'];
        exec(__DIR__ . "/scripts/upload_syslog_conf $host $this->login $this->password '$template'");
    }

    protected function getEventServer(): string
    {
        if ($this->isLegacyVersion()) {
            return $this->getEventServerLegacy();
        }

        ['addr' => $server, 'port' => $port] = $this->apiCall('/v1/network/syslog');
        return 'syslog.udp' . ':' . $server . ':' . $port;
    }

    /**
     * @return string
     *
     * @deprecated
     * @see getEventServer()
     */
    protected function getEventServerLegacy(): string
    {
        $host = parse_url($this->url)['host'];
        exec(__DIR__ . "/scripts/get_syslog_conf $host $this->login $this->password", $output);
        [$server, $port] = explode(':', explode(';', explode('@', $output[7])[1])[0]);

        return 'syslog.udp' . ':' . $server . ':' . $port;
    }

    /**
     * Retrieves the hardware version from system information, with caching.
     *
     * @return int|null The hardware version or null if not available.
     */
    protected function getHardwareVersion(): ?int
    {
        if ($this->hardwareVersion === null) {
            $this->hardwareVersion = $this->getSysinfo()['HardwareVersion'] ?? null;
        }

        return $this->hardwareVersion;
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

    /**
     * Retrieves the software version from system information, with caching.
     *
     * @return string|null The software version or null if not available.
     */
    protected function getSoftwareVersion(): ?string
    {
        if ($this->softwareVersion === null) {
            $this->softwareVersion = $this->getSysinfo()['SoftwareVersion'] ?? null;
        }

        return $this->softwareVersion;
    }

    protected function initializeProperties()
    {
        $this->login = 'root';
        $this->defaultPassword = '123456';
    }

    /**
     * Determines if the current hardware and software version combination is considered legacy.
     *
     * @return bool True if the combination is legacy, false otherwise.
     */
    protected function isLegacyVersion(): bool
    {
        $hardwareVersion = $this->getHardwareVersion();
        $softwareVersion = $this->getSoftwareVersion();

        $isLegacyVersion2 = $hardwareVersion === 2 && $softwareVersion < '2.2.5.15.7';
        $isLegacyVersion5 = $hardwareVersion === 5 && $softwareVersion < '2.5.0.10.13';

        return $isLegacyVersion2 || $isLegacyVersion5;
    }
}
