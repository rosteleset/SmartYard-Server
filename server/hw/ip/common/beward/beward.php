<?php

namespace hw\ip\common\beward;

use DateTimeImmutable;
use DateTimeZone;
use Exception;

/**
 * Trait providing common functionality related to Beward devices.
 */
trait beward
{

    public function configureEventServer(string $url)
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

    public function configureNtp(string $server, int $port = 123, string $timezone = 'Europe/Moscow')
    {
        $this->apiCall('cgi-bin/ntp_cgi', [
            'action' => 'set',
            'Enable' => 'on',
            'ServerAddress' => $server,
            'ServerPort' => $port,
            'Timezone' => $this->getIdByTimezone($timezone),
            'AutoMode' => 'off',
        ]);
    }

    /**
     * Force save the settings to the flash memory of the device.
     *
     * @return void
     */
    public function forceSave()
    {
        $this->apiCall('cgi-bin/config_cgi', ['action' => 'forcesave']);
    }

    public function getSysinfo(): array
    {
        return $this->parseParamValue($this->apiCall('cgi-bin/systeminfo_cgi', ['action' => 'get']));
    }

    public function reboot()
    {
        $this->apiCall('webs/btnHitEx', ['flag' => 21]);
    }

    public function reset()
    {
        $this->apiCall('cgi-bin/hardfactorydefault_cgi');
    }

    public function setAdminPassword(string $password)
    {
        $this->apiCall('webs/umanageCfgEx', [
            'uflag' => 0,
            'uname' => $this->login,
            'passwd' => $password,
            'passwd1' => $password,
            'newpassword' => '',
        ], true, "http://$this->url/umanage.asp");

        $this->apiCall('cgi-bin/pwdgrp_cgi', [
            'action' => 'update',
            'username' => 'admin',
            'password' => $password,
            'blockdoors' => 1,
        ]);
    }

    public function syncData()
    {
        // Empty implementation
    }

    public function transformDbConfig(array $dbConfig): array
    {
        $timezone = $dbConfig['ntp']['timezone'];
        $dbConfig['ntp']['timezone'] = "{$this->getIdByTimezone($timezone)}";
        return $dbConfig;
    }

    /**
     * Make an API call.
     *
     * @param string $resource API endpoint.
     * @param array $params (Optional) Query params or request body. Empty array by default.
     * @param bool $post (Optional) Add $params as request body if true. Default is false.
     * @param string $referer (Optional) Add referer header to query. Default is empty string.
     *
     * @return string API response.
     */
    protected function apiCall(string $resource, array $params = [], bool $post = false, string $referer = ''): string
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

        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, "$this->login:$this->password");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt(
            $ch,
            CURLOPT_USERAGENT,
            'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/88.0.4324.96 Safari/537.36'
        );
        curl_setopt($ch, CURLOPT_VERBOSE, false);

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

    /**
     * Get the ID corresponding to the given timezone.
     *
     * @param string $timezone Timezone identifier.
     *
     * @return int ID associated with the timezone.
     */
    protected function getIdByTimezone(string $timezone): int
    {
        /** Map of time zone offsets to corresponding Beward identifiers */
        $tzIdMap = [
            '-12' => 0,
            '-11' => 1,
            '-10' => 2,
            '-9' => 3,
            '-8' => 4,
            '-7' => 5,
            '-6' => 6,
            '-5' => 7,
            '-4' => 9,
            '-3.5' => 10,
            '-3' => 11,
            '-2' => 12,
            '-1' => 13,
            '0' => 14,
            '1' => 15,
            '2' => 19,
            '3' => 21,
            '3.5' => 22,
            '4' => 23,
            '4.5' => 24,
            '5' => 25,
            '5.5' => 26,
            '6' => 27,
            '7' => 28,
            '8' => 29,
            '9' => 30,
            '9.5' => 31,
            '10' => 32,
            '11' => 33,
            '12' => 34,
        ];

        try {
            $now = new DateTimeImmutable('now', new DateTimeZone($timezone));
            $gmtOffset = strval($now->getOffset() / 3600);
            return $tzIdMap[$gmtOffset] ?? 21;
        } catch (Exception $e) {
            return 21; // ID for Europe/Moscow timezone
        }
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
     *
     * @return void
     */
    protected function getParams(string $resource): array
    {
        return $this->parseParamValue($this->apiCall("cgi-bin/$resource", ['action' => 'get']));
    }

    protected function initializeProperties()
    {
        $this->login = 'admin';
        $this->defaultPassword = 'admin';
    }

    /**
     * Parse response string to array.
     *
     * @param string $res Response string.
     *
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

    /**
     * Set parameter in the "alarm" section.
     *
     * @param string $name Parameter name.
     * @param string $value Parameter value.
     *
     * @return void
     */
    protected function setAlarm(string $name, string $value)
    {
        $this->apiCall('cgi-bin/intercom_alarm_cgi', ['action' => 'set', $name => $value]);
    }

    /**
     * Set parameter in the "intercom" section.
     *
     * @param string $name Parameter name.
     * @param string $value Parameter value.
     *
     * @return void
     */
    protected function setIntercom(string $name, string $value)
    {
        $this->apiCall('cgi-bin/intercom_cgi', ['action' => 'set', $name => $value]);
    }
}
