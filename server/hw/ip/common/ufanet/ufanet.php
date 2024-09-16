<?php

namespace hw\ip\common\ufanet;

/**
 * Trait providing common functionality related to Ufanet devices.
 */
trait ufanet
{

    public function configureEventServer(string $url)
    {
        ['host' => $server, 'port' => $port] = parse_url_ext($url);

        $this->apiCall('/cgi-bin/configManager.cgi', 'GET', [
            'action' => 'setConfig',
            'Syslog.Address' => "$server:$port",
            'Syslog.Level' => 8,
        ]);
    }

    public function configureNtp(string $server, int $port = 123, string $timezone = 'Europe/Moscow')
    {
        $this->apiCall('/cgi-bin/configManager.cgi', 'GET', [
            'action' => 'setConfig',
            'NTP.Address' => "$server:$port",
            'NTP.TimeZone' => $timezone,
        ]);

        $this->reboot();
        $this->wait();

        // Sync time now
        $this->apiCall('/cgi-bin/j/sync-time.cgi');
    }

    public function getSysinfo(): array
    {
        $serialNumber = $this->apiCall('/cgi-bin/magicBox.cgi', 'GET', ['action' => 'getSerialNo'], 3);

        if ($serialNumber !== null) {
            return ['DeviceID' => $serialNumber];
        }

        return [];
    }

    public function reboot()
    {
        $this->apiCall('/cgi-bin/magicBox.cgi', 'GET', ['action' => 'reboot']);
    }

    public function reset()
    {
        $this->apiCall('/cgi-bin/magicBox.cgi', 'GET', ['action' => 'resetSystemEx']);
    }

    public function setAdminPassword(string $password)
    {
        $this->apiCall('/cgi-bin/userManager.cgi', 'GET', [
            'action' => 'modifyPassword',
            'name' => $this->login,
            'pwd' => $password,
            'pwdOld' => $this->defaultPassword,
        ]);

        sleep(5);
    }

    /**
     * Make an API call.
     *
     * @param string $resource API endpoint.
     * @param string $method (Optional) HTTP method. Default is "GET".
     * @param array|null $payload (Optional) Query params or request body. Empty array by default.
     * @param int $timeout (Optional) The maximum number of seconds to allow cURL functions to execute.
     *
     * @return array|string|null API response or null if an error occurred.
     */
    protected function apiCall(string $resource, string $method = 'GET', ?array $payload = null, int $timeout = 0)
    {
        if ($payload !== null && $method === 'GET') {
            $payload = array_map(fn($value) => str_replace(' ', '%20', $value), $payload); // Replace spaces with %20
            $queryString = urldecode(http_build_query($payload));
            $resource .= '?' . $queryString;
        }

        $req = $this->url . $resource;
        $ch = curl_init($req);

        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_USERPWD, "$this->login:$this->password");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_VERBOSE, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);

        if ($payload !== null && $method !== 'GET') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        }

        $res = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 400) {
            return null;
        }

        return json_decode($res, true) ?? trim($res);
    }

    /**
     * Convert response string to array.
     *
     * @param string $response Response string.
     *
     * @return array Associative array with parsed parameters.
     */
    protected function convertResponseToArray(string $response): array
    {
        $responseArray = [];

        if (!empty($response)) {
            $lines = explode("\n", trim($response));

            foreach ($lines as $line) {
                [$longKey, $value] = explode('=', trim($line), 2);
                $longKeyArray = explode('.', $longKey);
                $responseArray[end($longKeyArray)] = $value;
            }
        }

        return $responseArray;
    }

    protected function getEventServer(): string
    {
        $rawParams = $this->apiCall('/cgi-bin/configManager.cgi', 'GET', ['action' => 'getConfig', 'name' => 'Syslog']);
        $address = $this->convertResponseToArray($rawParams)['Address'];
        return "syslog.udp:$address";
    }

    protected function getNtpConfig(): array
    {
        $rawParams = $this->apiCall('/cgi-bin/configManager.cgi', 'GET', ['action' => 'getConfig', 'name' => 'NTP']);
        ['Address' => $address, 'TimeZone' => $timezone] = $this->convertResponseToArray($rawParams);
        $addressParts = explode(':', $address, 2);

        return [
            'server' => $addressParts[0],
            'port' => $addressParts[1] ?? 123,
            'timezone' => $timezone,
        ];
    }

    protected function initializeProperties()
    {
        $this->login = 'admin';
        $this->defaultPassword = '123456';
    }
}
