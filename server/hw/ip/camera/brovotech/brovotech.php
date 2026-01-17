<?php

namespace hw\ip\camera\brovotech;

use DateTime;
use DateTimeZone;
use Exception;

use hw\ip\camera\camera;
use hw\ip\camera\entities\DetectionZone;
use hw\ip\camera\utils\DetectionZoneUtils;

/**
 * Class representing a fake camera with a static image.
 */
class brovotech extends camera
{
    protected const DETECTION_ZONE_COUNT = 4;

    protected function apiCall(string $resource, string $method = 'GET', array $params = [], $payload = null, int $timeout = 0)
    {
        $req = $this->url . $resource;
        if ($params) {
            $req .= '?' . http_build_query($params);
        }

        $ch = curl_init($req);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, "$this->login:$this->password");
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_VERBOSE, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);

        if (isset($payload)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        }

        $res = curl_exec($ch);
        curl_close($ch);

        if (str_starts_with(curl_getinfo($ch, CURLINFO_CONTENT_TYPE), 'image')) {
            return (string)$res;
        }

        if (curl_getinfo($ch, CURLINFO_CONTENT_TYPE) == 'application/xml' || curl_getinfo($ch, CURLINFO_CONTENT_TYPE) == 'text/xml') {
            // TODO: try this
            // return object_to_array(simplexml_load_string($res));
            return json_decode(json_encode(simplexml_load_string($res)), true);
        }

        return $res;
    }

    /**
     * Get timezone representation for Brovotech.
     *
     * @param string $timezone Timezone identifier.
     *
     * @return string GMT offset (MSK-3 for example).
     */
    protected function getOffsetByTimezone(string $timezone): string
    {
        $offset_to_zone = [
            -50400 => 'GMT+14',       // GMT-14
            -46800 => 'GMT+13',       // GMT-13
            -43200 => 'GMT+12',       // GMT-12
            -39600 => 'SST11',        // GMT-11
            -36000 => 'HAST10',       // GMT-10
            -32400 => 'AKST9',        // GMT-09
            -28800 => 'PST8',         // GMT-08
            -25200 => 'MST7',         // GMT-07
            -21600 => 'CSTA6',        // GMT-06
            -18000 => 'CST5',         // GMT-05
            -16200 => 'VET4:30',      // GMT-04:30
            -14400 => 'PYT4',         // GMT-04
            -12600 => 'NST3:30',      // GMT-03:30
            -10800 => 'BRT3',         // GMT-03
             -7200 => 'FNT2',         // GMT-02
             -3600 => 'AZOT1',        // GMT-01
                 0 => 'GMT0',         // GMT
              3600 => 'CET-1',        // GMT+01
              7200 => 'EETB-2',       // GMT+02
             10800 => 'MSK-3',        // GMT+03
             14400 => 'AZT-4',        // GMT+04
             18000 => 'PKT-5',        // GMT+05
             19800 => 'IST-5:30',     // GMT+05:30
             20700 => 'NPT-5:45',     // GMT+05:45
             21600 => 'OMST-6',       // GMT+06
             23400 => 'MMT-6:30',     // GMT+06:30
             25200 => 'WIT-7',        // GMT+07
             28800 => 'CST-8',        // GMT+08
             32400 => 'JST-9',        // GMT+09
             34200 => 'CSTA-9:30',    // GMT+09:30
             36000 => 'EST-10',       // GMT+10
             39600 => 'SBT-11',       // GMT+11
             43200 => 'NZST-12',      // GMT+12
             45900 => 'CHAST-12:45',  // GMT+12:45
             46800 => 'TOT-13',       // GMT+13
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

    protected function getResolution(): string
    {
        $motion = $this->apiCall('/action/get', 'GET', ['subject' => 'motion']);
        $resolution = '640x360';
        if (is_array($motion)) {
            if (isset($motion['motion']['resolution'])) {
                $resolution = $motion['motion']['resolution'];
            }
        }

        return $resolution;
    }

    public function configureEventServer(string $url): void
    {
        ['host' => $server, 'port' => $port] = parse_url_ext($url);

        $xml_request = "
            <request>
              <evtserver>
                <eserver>
                  <host>$server</host>
                  <port>$port</port>
                  <type>2</type>
                  <format>0</format>
                </eserver>
                <eserver>
                  <type>2</type>
                </eserver>
              </evtserver>
            </request>
        ";

        $this->apiCall("/action/set", 'POST', ['subject' => 'evtserver'], $xml_request);
    }

    public function configureMotionDetection(array $detectionZones): void
    {
        $motion = $this->apiCall('/action/get', 'GET', ['subject' => 'motion']);
        $x_max = 640;
        $y_max = 360;
        $resolution = '640x360';
        if (is_array($motion)) {
            if (isset($motion['motion']['resolution'])) {
                $resolution = $motion['motion']['resolution'];
                [$x_max, $y_max] = explode('x', $resolution);
            }
        }

        $pixel_zones = [];
        foreach ($detectionZones as $zone) {
            $pixel_zones[] = DetectionZoneUtils::convertCoordinates(
                zone: $zone,
                maxX: $x_max,
                maxY: $y_max,
                direction: 'toPixel',
                roundToEven: true
            );
        }

        for ($i = count($pixel_zones) - 1; $i < self::DETECTION_ZONE_COUNT; $i++) {
            $pixel_zones[] = new DetectionZone(
                x: 0,
                y: 0,
                width: 0,
                height: 0,
            );
        }

        $rects = [];
        for ($i = 0; $i < self::DETECTION_ZONE_COUNT; $i++) {
            $rects[] = $pixel_zones[$i]->x . ',' . $pixel_zones[$i]->y . ',' . $pixel_zones[$i]->width . ',' . $pixel_zones[$i]->height;
        }

        $xml_request = "
            <request>
              <motion ver=\"2.0\">
                <active>1</active>
                <track>0</track>
                <mdtype>0</mdtype>
                <mdsmart>1</mdsmart>
                <resolution>$resolution</resolution>
                <sensitivity>3</sensitivity>
                <threshold>10</threshold>
                <track>0</track>
                <rect>$rects[0]</rect>
                <rect>$rects[1]</rect>
                <rect>$rects[2]</rect>
                <rect>$rects[3]</rect>
              </motion>
            </request>
        ";
        $this->apiCall('/action/set', 'POST', ['subject' => 'motion'], $xml_request);

        $xml_request = "
            <request>
              <alarmevt>
                <active>1</active>
                <duration>5</duration>
                <outmask>262144</outmask>
                <schedule>
                  <day>
                    <tsection />
                  </day>
                  <day>
                    <tsection />
                  </day>
                  <day>
                    <tsection />
                  </day>
                  <day>
                    <tsection />
                  </day>
                  <day>
                    <tsection />
                  </day>
                  <day>
                    <tsection />
                  </day>
                  <day>
                    <tsection />
                  </day>
                </schedule>
              </alarmevt>
            </request>
        ";
        $this->apiCall('/action/set', 'POST', ['subject' => 'alarm', 'type' => 2], $xml_request);

        // set getting snapshot from the main stream
        $xml_request = "
            <response>
	          <snap ver=\"2.0\">
		        <interval>1</interval>
		        <stream>0</stream>
		        <path>0</path>
	          </snap>
            </response>
        ";
        $this->apiCall('/action/set', 'POST', ['subject' => 'snap'], $xml_request);
    }

    public function configureNtp(string $server, int $port = 123, string $timezone = 'Europe/Moscow'): void
    {
        $tz = $this->getOffsetByTimezone($timezone);

        $xml_request = "
            <request>
              <systime ver=\"2.0\">
                <mode>1</mode>
                <tz>$tz</tz>
                <ntp>
                  <host>$server</host>
                  <port>$port</port>
                  <interval>1</interval>
                </ntp>
              </systime>
            </request>
        ";

        $this->apiCall('/action/set', 'POST', ['subject' => 'systime'], $xml_request);
    }

    public function getCamshot(): string
    {
        return $this->apiCall('/action/snap', 'GET', [], null, 2);
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

    public function setAdminPassword(string $password): void
    {
        $pwd = base64_encode($password);
        $xml_request = "
            <request>
              <user>
                <name>$this->login</name>
                <password>$pwd</password>
              </user>
            </request>
        ";

        $this->apiCall("/action/set", 'POST', ['subject' => 'user', 'do' => 'modify'], $xml_request);
    }

    public function setOsdText(string $text = ''): void
    {
        $active = ($text === '' ? 0 : 1);

        $xml_request = "
            <request>
              <osd ver=\"2.0\">
                <system>
                  <osditem>
                    <active>0</active>
                  </osditem>
                </system>
                <datetime>
                  <osditem>
                    <active>1</active>
                    <xpos>9</xpos>
                    <ypos>16</ypos>
                  </osditem>
                </datetime>
                <picture>
                  <osditem>
                    <active>0</active>
                  </osditem>
                </picture>
                <custom>
                  <osditem>
                    <active>$active</active>
                    <xpos>11</xpos>
                    <ypos>949</ypos>
                  </osditem>
                  <ctext>$text</ctext>
                </custom>
              </osd>
            </request>
        ";

        $this->apiCall("/action/set", 'POST', ['subject' => 'osd'], $xml_request);
    }

    public function syncData(): void
    {
        // Empty implementation
    }

    public function transformDbConfig(array $dbConfig): array
    {
        $dbConfig['ntp']['timezone'] = $this->getOffsetByTimezone($dbConfig['ntp']['timezone']);

        if ($dbConfig['motionDetection']) {
            [$x_max, $y_max] = explode('x', $this->getResolution());
            foreach ($dbConfig['motionDetection'] as &$motion) {
                $motion = DetectionZoneUtils::convertCoordinates(
                    zone: $motion,
                    maxX: $x_max,
                    maxY: $y_max,
                    direction: 'toPixel',
                    roundToEven: true);
            }
            $cnt = count($dbConfig['motionDetection']);
            if ($cnt > self::DETECTION_ZONE_COUNT) {
                array_splice($dbConfig['motionDetection'], self::DETECTION_ZONE_COUNT, $cnt - self::DETECTION_ZONE_COUNT);
            }
        }

        return $dbConfig;
    }

    protected function getEventServer(): string
    {
        $event_server = $this->apiCall('/action/get', 'GET', ['subject' => 'evtserver']);
        if (is_array($event_server)) {
            $server = $event_server['evtserver']['eserver'][0]['host'];
            $port = $event_server['evtserver']['eserver'][0]['port'];
            if (isset($server) && isset($port)) {
                return 'syslog.udp' . ':' . $server . ':' . $port;
            }
        }
        return '';
    }

    protected function getMotionDetectionConfig(): array
    {
        $pixel_zones = [];
        $x_max = 640;
        $y_max = 360;

        $motion = $this->apiCall('/action/get', 'GET', ['subject' => 'motion']);
        if (is_array($motion)) {
            if (isset($motion['motion']['resolution'])) {
                $resolution = $motion['motion']['resolution'];
                [$x_max, $y_max] = explode('x', $resolution);
            }
            if (is_array($motion['motion']['rect'])) {
                foreach ($motion['motion']['rect'] as $rect) {
                    if ($rect !== '0,0,0,0') {
                        $r = explode(',', $rect);
                        $p_zone = new DetectionZone(
                            x: $r[0],
                            y: $r[1],
                            width: $r[2],
                            height: $r[3],
                        );
                        $pixel_zones[] = $p_zone;
                    }
                }
            }
        }

        return $pixel_zones;
    }

    protected function getNtpConfig(): array
    {
        $ntp = $this->apiCall('/action/get', 'GET', ['subject' => 'systime']);
        if (is_array($ntp)) {
            return [
                'server' => $ntp['systime']['ntp']['host'],
                'port' => $ntp['systime']['ntp']['port'],
                'timezone' => $ntp['systime']['tz'],
            ];
        }

        return [];
    }

    protected function getOsdText(): string
    {
        $osd = $this->apiCall('/action/get', 'GET', ['subject' => 'osd']);
        if (is_array($osd)) {
            return $osd['osd']['custom']['ctext'] ?? '';
        }
        return '';
    }

    protected function initializeProperties(): void
    {
        $this->login = 'admin';
        $this->defaultPassword = '12345';
    }

    protected function initConnection(): void
    {
        // Empty implementation
    }
}
