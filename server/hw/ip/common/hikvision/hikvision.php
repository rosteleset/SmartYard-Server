<?php

namespace hw\ip\common\hikvision;

use hw\ValueObject\{
    NtpServer,
    Port,
    ServerAddress,
};

/**
 * Trait providing common functionality related to Hikvision devices.
 */
trait hikvision
{
    public function configureEventServer(string $url): void
    {
        ['host' => $server, 'port' => $port] = parse_url_ext($url);

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
                    <addressingFormatType>ipaddress</addressingFormatType>
                    <ipAddress>$server</ipAddress>
                    <portNo>$port</portNo>
                    <httpAuthenticationMethod>none</httpAuthenticationMethod>
                </HttpHostNotification>
            </HttpHostNotificationList>",
        );
    }

    public function getNtpServer(): NtpServer
    {
        // TODO: Implement getNtpConfig() method.
        return new NtpServer(
            address: ServerAddress::fromString('127.0.0.1'),
            port: new Port(123),
            timezone: 'Europe/Moscow',
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
            </User>",
        );
    }

    public function setNtpServer(NtpServer $server): void
    {
        $this->apiCall(
            '/System/time',
            'PUT',
            [],
            '<Time>
                <timeMode>NTP</timeMode>
                <timeZone>CST-3:00:00</timeZone>
             </Time>',
        );
        $this->apiCall(
            '/System/time/ntpServers/1',
            'PUT',
            [],
            "<NTPServer>
                <id>1</id>
                <addressingFormatType>ipaddress</addressingFormatType>
                <ipAddress>$server->address</ipAddress>
                <portNo>$server->port</portNo>
                <synchronizeInterval>60</synchronizeInterval>
            </NTPServer>",
        );
    }

    public function syncData(): void
    {
        // Empty implementation
    }

    protected function apiCall($resource, $method = 'GET', $params = [], $payload = null)
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

        if (curl_getinfo($ch, CURLINFO_CONTENT_TYPE) == 'application/xml') {
            return json_decode(json_encode(simplexml_load_string($res)), true);
        }

        return json_decode($res, true);
    }

    protected function getEventServer(): string
    {
        // TODO: Implement getSyslogConfig() method.
        return '';
    }

    protected function initializeProperties(): void
    {
        $this->login = 'admin';
        $this->defaultPassword = 'password123';
        $this->apiPrefix = '/ISAPI';
    }
}
