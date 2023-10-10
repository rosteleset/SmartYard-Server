<?php

namespace hw\ip\common\qtech;

/**
 * Trait providing common functionality related to Qtech devices.
 */
trait qtech
{

    public function configureEventServer(string $server, int $port)
    {
        $params = $this->paramsToString([
            'Config.DoorSetting.SysLog.SysLogServer' => $server,
            'Config.DoorSetting.SysLog.SysLogServerPort' => $port,
            'Config.DoorSetting.SysLog.SysLogServerTransportType' => 0,
            'Config.DoorSetting.SysLog.SysLogServerHeartBeat' => 5,
        ]);
        $this->setParams($params);
        $this->configureDebugServer($server, $port);
    }

    public function configureNtp(string $server, int $port = 123, string $timezone = 'Europe/Moscow')
    {
        $params = $this->paramsToString([
            'Config.Settings.SNTP.TimeZone' => '+03:00',
            'Config.Settings.SNTP.Name' => 'Russia(Moscow)',
            'Config.Settings.SNTP.NTPServer1' => $server,
            'Config.Settings.SNTP.NTPServer2' => null,
            'Config.Settings.SNTP.Interval' => 3600,
            'Config.Settings.SNTP.Port' => $port,
        ]);
        $this->setParams($params);
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
        $params = $this->paramsToString([
            'Config.DoorSetting.APIFCGI.AuthMode' => 3,
            'Config.Settings.WEB_LOGIN.Password' => $password, // WEB
            'Config.DoorSetting.APIFCGI.Password' => $password, // API
            'Config.DoorSetting.RTSP.Password' => $password, // RTSP
        ]);
        $this->setParams($params);
    }

    /** Make an API call */
    protected function apiCall(string $target, string $action, array $data = null)
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

    /** Configure remote debug server */
    protected function configureDebugServer(string $server, int $port, bool $enabled = true)
    {
        $params = $this->paramsToString([
            'Config.DoorSetting.REMOTEDEBUG.Enable' => $enabled,
            'Config.DoorSetting.REMOTEDEBUG.IP' => $server,
            'Config.DoorSetting.REMOTEDEBUG.Port' => $port,
        ]);
        $this->setParams($params);
    }

    protected function getEventServerConfig(): array
    {
        // TODO: Implement getSyslogConfig() method.
        return [];
    }

    protected function getNtpConfig(): array
    {
        // TODO: Implement getNtpConfig() method.
        return [];
    }

    protected function initializeProperties()
    {
        $this->login = 'admin';
        $this->defaultPassword = 'httpapi';
        $this->apiPrefix = '/api';
    }

    /** Convert an array with parameters to a string */
    protected function paramsToString(array $arr): string
    {
        $str = '';

        foreach ($arr as $key => $value) {
            $str .= "$key:$value;";
        }

        return $str;
    }

    /** Set params is config section */
    protected function setParams(string $params)
    {
        return $this->apiCall('config', 'set', ['config_key_value' => $params]);
    }
}
