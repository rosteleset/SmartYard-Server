<?php

namespace hw\ip\common\qtech;

use DateTime;
use DateTimeZone;
use Exception;

/**
 * Trait providing common functionality related to Qtech devices.
 */
trait qtech
{

    public function configureEventServer(string $url)
    {
        ['host' => $server, 'port' => $port] = parse_url_ext($url);

        $this->setParams([
            'Config.DoorSetting.SysLog.SysLogServer' => $server,
            'Config.DoorSetting.SysLog.SysLogServerPort' => $port,
            'Config.DoorSetting.SysLog.SysLogServerTransportType' => 0,
            'Config.DoorSetting.SysLog.SysLogServerHeartBeat' => 5,
        ]);

        // $this->configureDebugServer($server, $port);
    }

    public function configureNtp(string $server, int $port = 123, string $timezone = 'Europe/Moscow')
    {
        $this->setParams([
            'Config.Settings.SNTP.TimeZone' => $this->getOffsetByTimezone($timezone),
            // 'Config.Settings.SNTP.Name' => 'Russia(Moscow)',
            'Config.Settings.SNTP.NTPServer1' => $server,
            'Config.Settings.SNTP.NTPServer2' => null,
            'Config.Settings.SNTP.Interval' => 3600,
            'Config.Settings.SNTP.Port' => $port,
        ]);
    }

    public function getSysinfo(): array
    {
        $res = $this->apiCall('firmware', 'status');

        $sysinfo['DeviceID'] = str_replace(':', '', $res['data']['mac']);
        $sysinfo['DeviceModel'] = $res['data']['model'];
        $sysinfo['HardwareVersion'] = $res['data']['hardware'];
        $sysinfo['SoftwareVersion'] = $res['data']['firmware'];

        return $sysinfo;
    }

    public function reboot()
    {
        $this->apiCall('remote', 'reboot');
    }

    public function reset()
    {
        $this->apiCall('remote', 'reset_factory');
    }

    public function setAdminPassword(string $password)
    {
        $this->setParams([
            'Config.DoorSetting.APIFCGI.AuthMode' => 3,
            'Config.Settings.WEB_LOGIN.Password' => $password, // WEB
            'Config.DoorSetting.APIFCGI.Password' => $password, // API
            'Config.DoorSetting.RTSP.Password' => $password, // RTSP
        ]);
    }

    /**
     * Make an API call.
     *
     * @param string $target The target for the API call.
     * @param string $action The action to be performed.
     * @param array $data (Optional) An array of data to be included in the request.
     *
     * @return array|null Returns the decoded JSON response as an associative array, or null on failure.
     */
    protected function apiCall(string $target, string $action, array $data = []): ?array
    {
        $req = $this->url . $this->apiPrefix;

        $postfields = [
            'target' => $target,
            'action' => $action,
            'session' => '',
            'data' => $data,
        ];

        $ch = curl_init($req);

        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_USERPWD, "$this->login:$this->password");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_VERBOSE, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postfields));

        $res = curl_exec($ch);
        curl_close($ch);

        return json_decode($res, true);
    }

    /**
     * Configure the remote debug server settings.
     *
     * This is used to get all the necessary call events
     * because there is not enough information about this in syslog.
     *
     * @param string $server The IP address of the remote debug server.
     * @param int $port The port number on which the remote debug server is running.
     * @param bool $enabled (Optional) Whether the remote debugging server should be enabled (default is true).
     *
     * @return void
     */
    protected function configureDebugServer(string $server, int $port, bool $enabled = true)
    {
        $this->setParams([
            'Config.DoorSetting.REMOTEDEBUG.Enable' => $enabled,
            'Config.DoorSetting.REMOTEDEBUG.IP' => $server,
            'Config.DoorSetting.REMOTEDEBUG.Port' => $port,
        ]);
    }

    protected function getEventServer(): string
    {
        $server = $this->getParam('Config.DoorSetting.SysLog.SysLogServer');
        $port = $this->getParam('Config.DoorSetting.SysLog.SysLogServerPort');

        return 'syslog.udp' . ':' . $server . ':' . $port;
    }

    protected function getNtpConfig(): array
    {
        return [
            'server' => $this->getParam('Config.Settings.SNTP.NTPServer1'),
            'port' => $this->getParam('Config.Settings.SNTP.Port'),
            'timezone' => $this->getParam('Config.Settings.SNTP.Timezone'),
        ];
    }

    /**
     * Get timezone representation for Qtech.
     *
     * @param string $timezone Timezone identifier.
     *
     * @return string GMT offset (+03:00 for example).
     */
    protected function getOffsetByTimezone(string $timezone): string
    {
        try {
            $time = new DateTime('now', new DateTimeZone($timezone));
            return $time->format('P');
        } catch (Exception $e) {
            return '+03:00';
        }
    }

    /**
     * Get parameter from 'config' section.
     *
     * @param string $key The key of the parameter to retrieve.
     *
     * @return mixed|null The value of the specified parameter, or null if the parameter is not found.
     */
    protected function getParam(string $key)
    {
        $req = $this->apiCall('config', 'get', ['config_key' => $key]);
        return $req['data'][$key] ?? null;
    }

    protected function initializeProperties()
    {
        $this->login = 'admin';
        $this->defaultPassword = 'admin';
        $this->apiPrefix = '/api';
    }

    /**
     * Set parameters in the 'config' section.
     *
     * @param array $params An associative array of configuration settings and their corresponding values.
     *
     * @return void
     */
    protected function setParams(array $params)
    {
        $strParams = '';

        foreach ($params as $key => $value) {
            $strParams .= "$key:$value;";
        }

        $this->apiCall('config', 'set', ['config_key_value' => $strParams]);
    }
}
