<?php

namespace hw\ip\camera\ipera;

use DateTime;
use DateTimeZone;
use Exception;

use hw\ip\camera\camera;
use hw\ip\camera\entities\DetectionZone;
use hw\ip\camera\utils\DetectionZoneUtils;

function arrayToXml(array $data, \DOMDocument $xml_doc, \DOMElement $parent_node = null): void
{
    if ($parent_node === null) {
        $parent_node = $xml_doc->createElement('root'); // Default root element
        $xml_doc->appendChild($parent_node);
    }

    foreach ($data as $key => $value) {
        // Handle numeric keys (e.g., array elements without explicit names)
        if (is_numeric($key)) {
            arrayToXml($value, $xml_doc, $parent_node); // Recursive call for numeric array item
        } else {
            if (is_array($value)) {
                $child_node = $xml_doc->createElement($key);
                $parent_node->appendChild($child_node);
                arrayToXml($value, $xml_doc, $child_node); // Recursive call for nested arrays
            } else {
                $child_node = $xml_doc->createElement($key, htmlspecialchars($value));
                $parent_node->appendChild($child_node);
            }
        }
    }
}

/**
 * Class representing a iFLOW camera.
 */
class iflow extends camera
{
    const X_MOTION_MAX = 1000;  // max x coordinate for motion detection region
    const Y_MOTION_MAX = 1000;  // max y coordinate for motion detection region

    protected function apiCall($resource, $method = 'GET', $params = [], $payload = null, int $timeout = 3)
    {
        $req = $this->url . $this->apiPrefix . $resource;

        if ($params) {
            $req .= '?' . http_build_query($params);
        }

        // echo $method . '   ' . $req . '   ' . $payload . PHP_EOL;

        $ch = curl_init($req);

        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANYSAFE);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_USERPWD, "$this->login:$this->password");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_VERBOSE, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);

        if ($payload) {
            $postfields = $payload;

            if (isset($params['format']) && $params['format'] == 'json') {
                $postfields = json_encode($payload);
            }

            curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
        }

        $res = curl_exec($ch);
        curl_close($ch);

        if (str_starts_with(curl_getinfo($ch, CURLINFO_CONTENT_TYPE), 'image')) {
            return (string)$res;
        }

        if (curl_getinfo($ch, CURLINFO_CONTENT_TYPE) == 'application/xml' || curl_getinfo($ch, CURLINFO_CONTENT_TYPE) == 'text/xml') {
            return json_decode(json_encode(simplexml_load_string($res)), true);
        }

        return json_decode($res, true);
    }

    /**
     * Get timezone representation for iFLOW.
     *
     * @param string $timezone Timezone identifier.
     *
     * @return string GMT offset (MSK-3 for example).
     */
    protected function getOffsetByTimezone(string $timezone): string
    {
        $offset_to_zone = [
            -50400 => 'CST 14:00:00',       // GMT-14
            -46800 => 'CST 13:00:00',       // GMT-13
            -43200 => 'CST 12:00:00',       // GMT-12
            -39600 => 'CST 11:00:00',        // GMT-11
            -36000 => 'CST 10:00:00',       // GMT-10
            -32400 => 'CST 9:00:00',        // GMT-09
            -28800 => 'CST 8:00:00',         // GMT-08
            -25200 => 'CST 7:00:00',         // GMT-07
            -21600 => 'CST 6:00:00',        // GMT-06
            -18000 => 'CST 5:00:00',         // GMT-05
            -16200 => 'CST 4:30:00',      // GMT-04:30
            -14400 => 'CST 4:00:00',         // GMT-04
            -12600 => 'CST 3:30:00',      // GMT-03:30
            -10800 => 'CST 3:00:00',         // GMT-03
            -7200 => 'CST 2:00:00',         // GMT-02
            -3600 => 'CST 1:00:00',        // GMT-01
            0 => 'CST 0:00:00',         // GMT
            3600 => 'CST-1:00:00',        // GMT+01
            7200 => 'CST-2:00:00',       // GMT+02
            10800 => 'CST-3:00:00',        // GMT+03
            14400 => 'CST-4:00:00',        // GMT+04
            18000 => 'CST-5:00:00',        // GMT+05
            19800 => 'CST-5:30:00',     // GMT+05:30
            20700 => 'CST-5:45:00',     // GMT+05:45
            21600 => 'CST-6:00:00',       // GMT+06
            23400 => 'CST-6:30:00',     // GMT+06:30
            25200 => 'CST-7:00:00',        // GMT+07
            28800 => 'CST-8:00:00',        // GMT+08
            32400 => 'CST-9:00:00',        // GMT+09
            34200 => 'CST-9:30:00',    // GMT+09:30
            36000 => 'CST-10:00:00',       // GMT+10
            39600 => 'CST-11:00:00',       // GMT+11
            43200 => 'CST-12:00:00',      // GMT+12
            45900 => 'CST-12:45:00',  // GMT+12:45
            46800 => 'CST-13:00:00',       // GMT+13
        ];
        try {
            $zone = new DateTimeZone($timezone);
            $time = new DateTime("now", $zone);
            $time_offset =$zone->getOffset($time);
            return $offset_to_zone[$time_offset];
        } catch (Exception) {
            return $offset_to_zone[10800];
        }
    }

    public function configureEventServer(string $url): void
    {
        ['host' => $server, 'port' => $port] = parse_url_ext($url);
        $address_format_type = filter_var($server, FILTER_VALIDATE_IP) ? "ipaddress" : "hostname";
        $xml_server_tag = filter_var($server, FILTER_VALIDATE_IP) ? "ipAddress" : "hostName";
        $this->apiCall(
            '/Event/notification/httpHosts',
            'PUT',
            [],
            "<HttpHostNotificationList>
                <HttpHostNotification>
                    <id>1</id>
                    <url>/</url>
                    <protocolType>HTTP</protocolType>
                    <parameterFormatType>XML</parameterFormatType>
                    <addressingFormatType>$address_format_type</addressingFormatType>
                    <$xml_server_tag>$server</$xml_server_tag>
                    <portNo>$port</portNo>
                    <httpAuthenticationMethod>none</httpAuthenticationMethod>
                </HttpHostNotification>
            </HttpHostNotificationList>"
        );
    }

    public function configureMotionDetection(array $detectionZones): void
    {
        $pixel_zones = [];
        foreach ($detectionZones as $zone) {
            $pixel_zones[] = DetectionZoneUtils::convertCoordinates(
                zone: $zone,
                maxX: self::X_MOTION_MAX,
                maxY: self::Y_MOTION_MAX,
                direction: 'toPixel',
                roundToEven: true
            );
        }

        $data = [
            'enabled' => 'true',
            'regionType' => 'region',
            'MotionDetectionLayout' => [
                'sensitivityLevel' => 80,
                'layout' => [
                    'RegionList' => []
                ]
            ]
        ];

        // (0, 0) - is the bottom left corner, so y coordinate transforms to y_max -y
        $region_id = 0;
        foreach ($pixel_zones as $zone) {
            ++$region_id;
            $region = [];
            $region['id'] = $region_id;
            $region['RegionCoordinatesList'][] = [
                'RegionCoordinates' => [
                    'positionX' => $zone->x,
                    'positionY' => self::Y_MOTION_MAX - $zone->y
                ]
            ];
            $region['RegionCoordinatesList'][] = [
                'RegionCoordinates' => [
                    'positionX' => $zone->x + $zone->width - 1,
                    'positionY' => self::Y_MOTION_MAX - $zone->y
                ]
            ];
            $region['RegionCoordinatesList'][] = [
                'RegionCoordinates' => [
                    'positionX' => $zone->x + $zone->width - 1,
                    'positionY' => self::Y_MOTION_MAX - $zone->y - $zone->height + 1,
                ]
            ];
            $region['RegionCoordinatesList'][] = [
                'RegionCoordinates' => [
                    'positionX' => $zone->x,
                    'positionY' => self::Y_MOTION_MAX - $zone->y - $zone->height + 1,
                ]
            ];
            $data['MotionDetectionLayout']['layout']['RegionList'][] = ['Region' => $region];
        }

        $xml_doc = new \DOMDocument('1.0', 'UTF-8');
        $xml_doc->formatOutput = true;
        $parent_node = $xml_doc->createElement('MotionDetection');
        $xml_doc->appendChild($parent_node);
        arrayToXml($data, $xml_doc, $parent_node);

        $this->apiCall(
            '/System/Video/inputs/channels/1/motionDetection',
            'PUT',
            [],
            $xml_doc->saveXML()
        );
    }

    public function configureNtp(string $server, int $port = 123, string $timezone = 'Europe/Moscow'): void
    {
        $tz = $this->getOffsetByTimezone($timezone);

        $this->apiCall(
            '/System/time',
            'PUT',
            [],
            "<Time>
                <timeMode>NTP</timeMode>
                <timeZone>$tz</timeZone>
             </Time>"
        );
        $address_format_type = filter_var($server, FILTER_VALIDATE_IP) ? "ipaddress" : "hostname";
        $xml_server_tag = filter_var($server, FILTER_VALIDATE_IP) ? "ipAddress" : "hostName";
        $this->apiCall(
            '/System/time/ntpServers/1',
            'PUT',
            [],
            "<NTPServer>
                <id>1</id>
                <addressingFormatType>$address_format_type</addressingFormatType>
                <$xml_server_tag>$server</$xml_server_tag>
                <portNo>$port</portNo>
                <synchronizeInterval>1440</synchronizeInterval>
            </NTPServer>"
        );
    }

    public function getSysinfo(): array
    {
        $res = $this->apiCall('/System/deviceInfo');

        $sysinfo['DeviceID'] = $res['deviceID'];
        $sysinfo['DeviceModel'] = $res['model'];
        $sysinfo['HardwareVersion'] = $res['hardwareVersion'];
        $sysinfo['SoftwareVersion'] = $res['firmwareVersion'] . ' ' . $res['firmwareReleasedDate'];

        return $sysinfo;
    }

    public function reboot(): void
    {
        $this->apiCall('/System/reboot', 'PUT');
    }

    public function reset(): void
    {
        $this->apiCall('/System/factoryReset', 'PUT', ['mode' => 'basic']);
    }

    public function setAdminPassword(string $password): void
    {
        $this->apiCall(
            '/Security/users/1',
            'PUT',
            [],
            "<User>
                <id>1</id>
                <userName>admin</userName>
                <password>$password</password>
                <userLevel>Administrator</userLevel>
                <loginPassword>$this->password</loginPassword>
            </User>"
        );
    }

    public function syncData(): void
    {
        // Empty implementation
    }

    protected function getEventServer(): string
    {
        $settings = $this->apiCall('/Event/notification/httpHosts')['HttpHostNotification'][0];
        return 'http://' . ($settings['hostName'] ?? $settings['ipAddress']) . ':' . $settings['portNo'];
    }

    protected function getNtpConfig(): array
    {
        $ntp = $this->apiCall('/System/time');
        if (!isset($ntp['timeZone']))
            return [];
        $time_zone = $ntp['timeZone'];

        $ntp = $this->apiCall('/System/time/ntpServers/1');
        if (!isset($ntp['hostName']) && !isset($ntp['ipAddress']))
            return [];
        if (!isset($ntp['portNo']))
            return [];
        $host_name = ($ntp['hostName'] ?? $ntp['ipAddress']);
        $port_no = $ntp['portNo'];

        return [
            'server' => $host_name,
            'port' => $port_no,
            'timezone' => $time_zone,
        ];
    }

    protected function initializeProperties(): void
    {
        $this->login = 'admin';
        $this->defaultPassword = '';
        $this->apiPrefix = '/ISAPI';
    }

    public function getCamshot(): string
    {
        return $this->apiCall('/Streaming/channels/101/picture', 'GET', ['snapShotImageType' => 'JPEG']);
    }

    public function setOsdText(string $text = ''): void
    {
        $this->apiCall(
            '/System/Video/inputs/channels/1',
            'PUT',
            [],
            "<VideoInputChannel>
                <id>1</id>
                <inputPort>1</inputPort>
                <name>$text</name>
            </VideoInputChannel>"
        );
        $this->apiCall(
            '/System/Video/inputs/channels/1/overlays',
            'PUT',
            [],
            '<VideoOverlay>
                <DateTimeOverlay>
                    <enabled>true</enabled>
                    <positionY>540</positionY>
                    <positionX>0</positionX>
                    <dateStyle>MM-DD-YYYY</dateStyle>
                    <timeStyle>24hour</timeStyle>
                    <displayWeek>true</displayWeek>
                </DateTimeOverlay>
                <channelNameOverlay>
                    <enabled>true</enabled>
                    <positionY>64</positionY>
                    <positionX>0</positionX>
                </channelNameOverlay>
            </VideoOverlay>'
        );
    }

    public function transformDbConfig(array $dbConfig): array
    {
        $dbConfig['ntp']['timezone'] = $this->getOffsetByTimezone($dbConfig['ntp']['timezone']);

        if ($dbConfig['motionDetection']) {
            foreach ($dbConfig['motionDetection'] as &$motion) {
                $motion = DetectionZoneUtils::convertCoordinates(
                    zone: $motion,
                    maxX: self::X_MOTION_MAX,
                    maxY: self::Y_MOTION_MAX,
                    direction: 'toPixel',
                    roundToEven: true);
            }
        }

        return $dbConfig;
    }

    protected function getMotionDetectionConfig(): array
    {
        $pixel_zones = [];
        $motion = $this->apiCall('/System/Video/inputs/channels/1/motionDetection')['MotionDetectionLayout']['layout']['RegionList'] ?? null;
        if (is_array($motion)) {
            if (isset($motion['Region'][0]) === false) {
                $temp = $motion['Region'];
                $motion = [];
                $motion['Region'] = [$temp];
            }
            foreach ($motion['Region'] as $region) {
                $x_min = self::X_MOTION_MAX;
                $y_min = self::Y_MOTION_MAX;
                $x_max = 0;
                $y_max = 0;
                foreach ($region['RegionCoordinatesList']['RegionCoordinates'] as $point) {
                    if ($point['positionX'] < $x_min) {
                        $x_min = $point['positionX'];
                    }
                    if ($point['positionX'] > $x_max) {
                        $x_max = $point['positionX'];
                    }
                    if ($point['positionY'] < $y_min) {
                        $y_min = $point['positionY'];
                    }
                    if ($point['positionY'] > $y_max) {
                        $y_max = $point['positionY'];
                    }
                }
                $p_zone = new DetectionZone(
                    x: $x_min,
                    y: self::Y_MOTION_MAX - $y_max,
                    width: $x_max - $x_min + 1,
                    height: $y_max - $y_min + 1,
                );
                $pixel_zones[] = $p_zone;
            }
        }

        return $pixel_zones;
    }

    protected function getOsdText(): string
    {
        $osd = $this->apiCall('/System/Video/inputs/channels/1');
        return $osd['name'] ?? '';
    }
}
