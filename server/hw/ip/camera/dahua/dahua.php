<?php

namespace hw\ip\camera\dahua;

use DateTime;
use DateTimeZone;
use Exception;

use hw\ip\camera\camera;

/**
 * Class representing a Dahua camera with a static image.
 */
class dahua extends camera
{
    // Dahua represents a motion detection region as an 18 x 22-bit grid.
    const ROWS = 18;
    const COLS = 22;

    protected function apiCall(string $resource, string $method = 'GET', array $params = [], int $timeout = 3): string
    {
        $req = $this->url . $resource;
        if ($params) {
            $req .= '?' . http_build_query($params);
        }

        $ch = curl_init($req);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
        curl_setopt($ch, CURLOPT_USERPWD, "$this->login:$this->password");
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_VERBOSE, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);

        $res = curl_exec($ch);
        curl_close($ch);

        return (string)$res;
    }

    protected function getOffsetByTimezone(string $timezone): int
    {
        $offset_to_timezone = [
            0 => 0,       // GMT+00:00
            3600 => 1,    // GMT+01:00
            7200 => 2,    // GMT+02:00
            10800 => 3,   // GMT+03:00
            12600 => 4,   // GMT+03:30
            14400 => 5,   // GMT+04:00
            16200 => 6,   // GMT+04:30
            18000 => 7,   // GMT+05:00
            19800 => 8,   // GMT+05:30
            20700 => 9,   // GMT+05:45
            21600 => 10,  // GMT+06:00
            23400 => 11,  // GMT+06:30
            25200 => 12,  // GMT+07:00
            28800 => 13,  // GMT+08:00
            32400 => 14,  // GMT+09:00
            34200 => 15,  // GMT+09:30
            36000 => 16,  // GMT+10:00
            39600 => 17,  // GMT+11:00
            43200 => 18,  // GMT+12:00
            46800 => 19,  // GMT+13:00

            -3600 => 20,  // GMT-01:00
            -7200 => 21,  // GMT-02:00
            -10800 => 22, // GMT-03:00
            -12600 => 23, // GMT-03:30
            -14400 => 24, // GMT-04:00
            -18000 => 25, // GMT-05:00
            -21600 => 26, // GMT-06:00
            -25200 => 27, // GMT-07:00
            -28800 => 28, // GMT-08:00
            -32400 => 29, // GMT-09:00
            -36000 => 30, // GMT-10:00
            -39600 => 31, // GMT-11:00
            -43200 => 32, // GMT-12:00
        ];

        try {
            $zone = new DateTimeZone($timezone);
            $time = new DateTime('now', $zone);
            $offset = $zone->getOffset($time);

            return $offset_to_timezone[$offset] ?? $offset_to_timezone[10800];
        } catch (Exception) {
            return $offset_to_timezone[10800];
        }
    }

    protected function parseRegion(string $config): array
    {
        $region = array_fill(0, dahua::ROWS, 0);

        $pattern =
            '/^table\.MotionDetect\[0\]\.MotionDetectWindow\[0\]' .
            '\.Region\[(\d+)\]=(\d+)/m';

        if (preg_match_all($pattern, $config, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $row = (int) $match[1];

                if ($row >= 0 && $row < dahua::ROWS) {
                    $region[$row] = (int) $match[2];
                }
            }
        }

        return $region;
    }

    protected function zonesToRegion(array $zones): array
    {
        $region = array_fill(0, dahua::ROWS, 0);

        foreach ($zones as $zone) {
            $x1 = max(0, $zone->x1);
            $y1 = max(0, $zone->y1);
            $x2 = min(100, $zone->x1 + $zone->width);
            $y2 = min(100, $zone->y1 + $zone->height);

            $col1 = (int) floor($x1 / 100 * dahua::COLS);
            $row1 = (int) floor($y1 / 100 * dahua::ROWS);

            $col2 = (int) ceil($x2 / 100 * dahua::COLS);
            $row2 = (int) ceil($y2 / 100 * dahua::ROWS);

            $col1 = max(0, min(dahua::COLS, $col1));
            $col2 = max(0, min(dahua::COLS, $col2));

            $row1 = max(0, min(dahua::ROWS, $row1));
            $row2 = max(0, min(dahua::ROWS, $row2));

            for ($row = $row1; $row < $row2; $row++) {
                for ($col = $col1; $col < $col2; $col++) {
                    // Dahua stores the leftmost cell in the highest bit.
                    $bit = dahua::COLS - 1 - $col;
                    $region[$row] |= (1 << $bit);
                }
            }
        }

        return $region;
    }

    protected function parseNtp(string $config): array
    {
        $ntp = [];
        foreach (explode("\n", trim($config)) as $line) {
            if (preg_match('/^table\.NTP\.([^=]+)=(.*)$/', trim($line), $matches)) {
                $ntp[$matches[1]] = $matches[2];
            }
        }

        return $ntp;
    }

    protected function parseOsdText(string $config): string
    {
        $key = "table.VideoWidget[0].UserDefinedTitle[0].Text=";

        foreach (explode("\n", $config) as $line) {
            if (str_starts_with($line, $key)) {
                return substr($line, strlen($key));
            }
        }

        return '';
    }

    protected function getMotionDetectionConfig(): array
    {
        $config = $this->apiCall('/cgi-bin/configManager.cgi', 'GET', ['action' => 'getConfig', 'name' => 'MotionDetect']);
        return $this->parseRegion($config);
    }

    public function configureMotionDetection(array $detectionZones): void
    {
        $region = $this->zonesToRegion($detectionZones);
        $params = [
            'action' => 'setConfig',
            'MotionDetect[0].Enable' => 'true',

            // Required when setting MotionDetectWindow regions.
            //'MotionDetect[0].DetectVersion' => 'V3.0',
        ];
        foreach ($region as $row => $mask) {
            $params[
            "MotionDetect[0].MotionDetectWindow[0].Region[$row]"
            ] = (string)$mask;
        }

        $this->apiCall('/cgi-bin/configManager.cgi', 'GET', $params);
    }

    protected function getNtpConfig(): array
    {
        $config = $this->apiCall('/cgi-bin/configManager.cgi', 'GET', ['action' => 'getConfig', 'name' => 'NTP']);
        $ntp = $this->parseNtp($config);
        if (!empty($ntp)) {
            return [
                'server' => $ntp['Address'],
                'port' => $ntp['Port'],
                'timezone' => $ntp['TimeZone'],
            ];
        }

        return [];
    }

    public function configureNtp(string $server, int $port = 123, string $timezone = 'Europe/Moscow'): void
    {
        $tz = $this->getOffsetByTimezone($timezone);

        $params = [
            'action' => 'setConfig',
            'NTP.Enable' => 'true',
            'NTP.Address' => $server,
            'NTP.Port' => $port,
            'NTP.TimeZone' => $tz,
        ];

        $this->apiCall('/cgi-bin/configManager.cgi', 'GET', $params);
    }

    protected function getOsdText(): string
    {
        $config = $this->apiCall('/cgi-bin/configManager.cgi', 'GET', ['action' => 'getConfig', 'name' => 'VideoWidget']);
        return $this->parseOsdText($config);
    }

    public function setOsdText(string $text = ''): void
    {
        $params = [
            'action' => 'setConfig',
            'VideoWidget[0].UserDefinedTitle[0].Text' => $text,
        ];

        $this->apiCall('/cgi-bin/configManager.cgi', 'GET', $params);
    }

    protected function initializeProperties(): void
    {
        $this->login = 'admin';
        $this->defaultPassword = 'admin';
    }

    public function setAdminPassword(string $password): void
    {
        $params = [
            'action' => 'modifyPassword',
            'name' => $this->login,
            'pwdOld' => $this->password,
            'pwd' => $password,
        ];

        $this->apiCall('/cgi-bin/userManager.cgi', 'GET', $params);
    }

    public function transformDbConfig(array $dbConfig): array
    {
        $dbConfig['ntp']['timezone'] = $this->getOffsetByTimezone($dbConfig['ntp']['timezone']);

        if ($dbConfig['motionDetection']) {
            $dbConfig['motionDetection'] = $this->zonesToRegion($dbConfig['motionDetection']);
        }

        return $dbConfig;
    }

    protected function getEventServer(): string
    {
        return '';
    }

    public function configureEventServer(string $url): void
    {
        // Empty implementation
    }

    public function syncData(): void
    {
        // Empty implementation
    }

    public function getCamshot(): string
    {
        return $this->apiCall('/cgi-bin/snapshot.cgi', 'GET', ['channel' => '1', 'type' => '0']);
    }

    public function getSysinfo(): array
    {
        return [];
    }

    public function ping(): bool
    {
        return true;
    }

    public function reboot(): void
    {
        // Empty implementation
    }

    public function reset(): void
    {
        // Empty implementation
    }
}
