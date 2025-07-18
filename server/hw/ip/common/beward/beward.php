<?php

namespace hw\ip\common\beward;

/**
 * Trait providing common functionality related to Beward devices.
 */
trait beward
{
    public function configureEventServer(string $url): void
    {
        ['host' => $server, 'port' => $port] = parse_url_ext($url);

        $this->apiCall('cgi-bin/rsyslog_cgi', [
            'action' => 'set',
            'Enable' => 'on',
            'Protocol' => 'udp',
            'ServerAddress' => $server,
            'ServerPort' => $port,
            'LogLevel' => 6,
        ]);
    }

    public function configureNtp(string $server, int $port = 123, string $timezone = 'Europe/Moscow'): void
    {
        /*
         * Depending on the device model, the auto mode can be 'on'/'off' or '1'/'0'.
         * This must be determined before calling the NTP configuration, otherwise the call will fail.
         */
        $automode = $this->getParams('ntp_cgi')['AutoMode'] ?? null;
        $automodeIsNumeric = is_numeric($automode);

        $this->apiCall('cgi-bin/ntp_cgi', [
            'action' => 'set',
            'Enable' => 'on',
            'ServerAddress' => $server,
            'ServerPort' => $port,
            'Timezone' => Timezone::getIdByTimezone($timezone),
            'AutoMode' => $automodeIsNumeric ? '0' : 'off',
        ]);
    }

    /**
     * Force save the settings to the flash memory of the device.
     *
     * @return void
     */
    public function forceSave(): void
    {
        $this->apiCall('cgi-bin/config_cgi', ['action' => 'forcesave']);
    }

    public function getSysinfo(): array
    {
        return $this->parseParamValue($this->apiCall('cgi-bin/systeminfo_cgi', ['action' => 'get'], false, 3));
    }

    public function reboot(): void
    {
        $this->apiCall('webs/btnHitEx', ['flag' => 21]);
    }

    public function reset(): void
    {
        $this->apiCall('cgi-bin/hardfactorydefault_cgi');
    }

    public function setAdminPassword(string $password): void
    {
        $this->apiCall('webs/umanageCfgEx', [
            'uflag' => 0,
            'uname' => $this->login,
            'passwd' => $password,
            'passwd1' => $password,
            'newpassword' => '',
        ], true, 0, "$this->url/umanage.asp", CURLAUTH_BASIC | CURLAUTH_DIGEST);

        $this->apiCall('cgi-bin/pwdgrp_cgi', [
            'action' => 'update',
            'username' => 'admin',
            'password' => $password,
            'blockdoors' => 1,
        ]);
    }

    public function syncData(): void
    {
        // Empty implementation
    }

    public function transformDbConfig(array $dbConfig): array
    {
        $timezone = $dbConfig['ntp']['timezone'];
        $dbConfig['ntp']['timezone'] = (string)Timezone::getIdByTimezone($timezone);
        return $dbConfig;
    }

    /**
     * Make an API call.
     *
     * @param string $resource API endpoint.
     * @param array $params (Optional) Query params or request body. Empty array by default.
     * @param bool $post (Optional) Add $params as request body if true. Default is false.
     * @param int $timeout (Optional) The maximum number of seconds to allow cURL functions to execute.
     * @param string $referer (Optional) Add referer header to query. Default is empty string.
     * @param int $authType (Optional) Authentication type. Default is CURLAUTH_BASIC.
     * @return string API response.
     */
    protected function apiCall(
        string $resource,
        array  $params = [],
        bool   $post = false,
        int    $timeout = 0,
        string $referer = '',
        int    $authType = CURLAUTH_BASIC,
    ): string
    {
        $query = '';

        foreach ($params as $param => $value) {
            $query .= $param . '=' . urlencode($value) . '&';
        }

        if ($query) {
            $query = substr($query, 0, -1);
        }

        if (!$post && $query) {
            $req = $this->url . '/' . $resource . '?' . $query;
        } else {
            $req = $this->url . '/' . $resource;
        }

        $ch = curl_init($req);

        curl_setopt($ch, CURLOPT_HTTPAUTH, $authType);
        curl_setopt($ch, CURLOPT_USERPWD, "$this->login:$this->password");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt(
            $ch,
            CURLOPT_USERAGENT,
            'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/88.0.4324.96 Safari/537.36',
        );
        curl_setopt($ch, CURLOPT_VERBOSE, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);

        if ($post) {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($query) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $query);
            }
        }

        if ($referer) {
            curl_setopt($ch, CURLOPT_REFERER, $referer);
        }

        $r = curl_exec($ch);
        curl_close($ch);

        return $r;
    }

    protected function getEventServer(): string
    {
        ['ServerAddress' => $server, 'ServerPort' => $port] = $this->getParams('rsyslog_cgi');
        return 'syslog.udp' . ':' . $server . ':' . $port;
    }

    protected function getNtpConfig(): array
    {
        $ntp = $this->getParams('ntp_cgi');

        return [
            'server' => $ntp['ServerAddress'],
            'port' => $ntp['ServerPort'],
            'timezone' => $ntp['Timezone'],
        ];
    }

    /**
     * Get params from specified section with "action=get".
     *
     * @param string $resource Section from which to get parameters (like "sip_cgi", "ntp_cgi", etc.).
     * @return array
     */
    protected function getParams(string $resource): array
    {
        return $this->parseParamValue($this->apiCall("cgi-bin/$resource", ['action' => 'get']));
    }

    protected function initializeProperties(): void
    {
        $this->login = 'admin';
        $this->defaultPassword = 'admin';
    }

    /**
     * Parse response string to array.
     *
     * @param string $res Response string.
     * @return array Associative array with parsed parameters.
     */
    protected function parseParamValue(string $res): array
    {
        $ret = [];
        $res = explode("\n", trim($res));

        foreach ($res as $r) {
            $r = explode('=', trim($r));
            $ret[$r[0]] = @$r[1];
        }

        return $ret;
    }
}
