<?php

namespace hw\ip\common\ufanet;

/**
 * Trait providing common functionality related to Ufanet devices.
 */
trait ufanet
{

    public function configureEventServer(string $url)
    {
        // TODO: Implement configureEventServer() method.
    }

    public function configureNtp(string $server, int $port = 123, string $timezone = 'Europe/Moscow')
    {
        // TODO: Implement configureNtp() method.
    }

    public function getSysinfo(): array
    {
        $serialNumber = $this->apiCall('/cgi-bin/magicBox.cgi', ['action' => 'getSerialNo']);

        if ($serialNumber !== null) {
            return ['DeviceID' => $serialNumber];
        }

        return [];
    }

    public function reboot()
    {
        $this->apiCall('/cgi-bin/magicBox.cgi', ['action' => 'reboot']);
    }

    public function reset()
    {
        $this->apiCall('/cgi-bin/magicBox.cgi', ['action' => 'resetSystemEx']);
    }

    public function setAdminPassword(string $password)
    {
        $this->apiCall('/cgi-bin/userManager.cgi', [
            'action' => 'modifyPassword',
            'name' => $this->login,
            'pwd' => $password,
            'pwdOld' => $this->defaultPassword,
        ]);

        sleep(5);
    }

    public function syncData()
    {
        // TODO: Implement syncData() method.
    }

    /**
     * Make an API call.
     *
     * @param string $resource API endpoint.
     * @param array $payload (Optional) Query params or request body. Empty array by default.
     * @param string $method (Optional) HTTP method. Default is "GET".
     *
     * @return array|string|null API response or null if an error occurred.
     */
    protected function apiCall(string $resource, array $payload = [], string $method = 'GET')
    {
        if (!empty($payload) && $method === 'GET') {
            $queryString = http_build_query($payload);
            $resource .= '?' . $queryString;
        }

        $req = $this->url . $resource;
        $ch = curl_init($req);

        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_USERPWD, "$this->login:$this->password");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_VERBOSE, false);

        if (!empty($payload) && $method !== 'GET') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE));
            curl_setopt($ch, CURLOPT_HTTPHEADER, 'Content-Type: application/json');
        }

        $res = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 400) {
            return null;
        }

        return json_decode($res, true) ?? trim($res);
    }

    protected function getEventServer(): string
    {
        // TODO: Implement getEventServer() method.
        return '';
    }

    protected function getNtpConfig(): array
    {
        // TODO: Implement getNtpConfig() method.
        return [];
    }

    protected function initializeProperties()
    {
        $this->login = 'admin';
        $this->defaultPassword = '123456';
    }
}
