<?php

namespace hw\ip\domophone\rubetek;

use hw\ip\domophone\domophone;
use hw\ip\domophone\rubetek\Clients\{
    JsonRpcClient,
    WebSocketClient,
};
use RuntimeException;

/**
 * Represents a Rubetek RACS-1101 access controller.
 */
class racs1101 extends domophone
{
    private ?WebSocketClient $webSocketClient = null;
    private ?JsonRpcClient $jsonRpcClient = null;

    public function addRfid(string $code, int $apartment = 0): void
    {
        // TODO: Implement addRfid() method.
    }

    public function addRfids(array $rfids): void
    {
        // TODO: Implement addRfids() method.
    }

    public function configureApartment(
        int   $apartment,
        int   $code = 0,
        array $sipNumbers = [],
        bool  $cmsEnabled = true,
        array $cmsLevels = [],
    ): void
    {
        // Empty implementation
    }

    public function configureEncoding(): void
    {
        // Empty implementation
    }

    public function configureEventServer(string $url): void
    {
        // TODO: Implement configureEventServer() method.
    }

    public function configureMatrix(array $matrix): void
    {
        // Empty implementation
    }

    public function configureNtp(string $server, int $port = 123, string $timezone = 'Europe/Moscow'): void
    {
        // TODO: Implement configureNtp() method.
    }

    public function configureSip(
        string $login,
        string $password,
        string $server,
        int    $port = 5060,
        bool   $stunEnabled = false,
        string $stunServer = '',
        int    $stunPort = 3478,
    ): void
    {
        // Empty implementation
    }

    public function configureUserAccount(string $password): void
    {
        // Empty implementation
    }

    public function deleteApartment(int $apartment = 0): void
    {
        // Empty implementation
    }

    public function deleteRfid(string $code = ''): void
    {
        // TODO: Implement deleteRfid() method.
    }

    public function getLineDiagnostics(int $apartment): string|int|float
    {
        // Empty implementation
        return 0;
    }

    public function getSysinfo(): array
    {
        $info = $this->apiCall('get_device_info');

        return [
            'DeviceID' => $info['sn'],
            'DeviceModel' => $info['model'],
            'SoftwareVersion' => $info['fw_ver'],
        ];
    }

    public function openLock(int $lockNumber = 0): void
    {
        // TODO: Implement openLock() method.
    }

    public function reboot(): void
    {
        // TODO: Implement reboot() method.
    }

    public function reset(): void
    {
        // TODO: Implement reset() method.
    }

    public function setAdminPassword(string $password): void
    {
        // TODO: Implement setAdminPassword() method.
    }

    public function setAudioLevels(array $levels): void
    {
        // Empty implementation
    }

    public function setCallTimeout(int $timeout): void
    {
        // Empty implementation
    }

    public function setCmsModel(string $model = ''): void
    {
        // Empty implementation
    }

    public function setConciergeNumber(int $sipNumber): void
    {
        // Empty implementation
    }

    public function setDtmfCodes(
        string $code1 = '1',
        string $code2 = '2',
        string $code3 = '3',
        string $codeCms = '1',
    ): void
    {
        // Empty implementation
    }

    public function setPublicCode(int $code = 0): void
    {
        // Empty implementation
    }

    public function setSosNumber(int $sipNumber): void
    {
        // Empty implementation
    }

    public function setTalkTimeout(int $timeout): void
    {
        // Empty implementation
    }

    public function setUnlockTime(int $time = 3): void
    {
        // TODO: Implement setUnlockTime() method.
    }

    public function syncData(): void
    {
        // TODO: Implement syncData() method.
    }

    public function transformDbConfig(array $dbConfig): array
    {
        $dbConfig['dtmf'] = $this->getDtmfConfig();
        $dbConfig['sip'] = $this->getSipConfig();

        $dbConfig['cmsModel'] = '';
        $dbConfig['matrix'] = [];
        $dbConfig['apartments'] = [];

        return $dbConfig;
    }

    protected function getApartments(): array
    {
        // Empty implementation
        return [];
    }

    protected function getAudioLevels(): array
    {
        // Empty implementation
        return [];
    }

    protected function getCmsModel(): string
    {
        // Empty implementation
        return '';
    }

    protected function getDtmfConfig(): array
    {
        // Empty implementation
        return [
            'code1' => '1',
            'code2' => '2',
            'code3' => '3',
            'codeCms' => '1',
        ];
    }

    protected function getEventServer(): string
    {
        // TODO: Implement getEventServer() method.
        return '';
    }

    protected function getMatrix(): array
    {
        // Empty implementation
        return [];
    }

    protected function getNtpConfig(): array
    {
        // TODO: Implement getNtpConfig() method.
        return [];
    }

    protected function getRfids(): array
    {
        // TODO: Implement getRfids() method.
        return [];
    }

    protected function getSipConfig(): array
    {
        // Empty implementation
        return [
            'server' => '',
            'port' => 5060,
            'login' => '',
            'password' => '',
            'stunEnabled' => false,
            'stunServer' => '',
            'stunPort' => 3478,
        ];
    }

    protected function initializeProperties(): void
    {
        $this->login = 'admin';
        $this->defaultPassword = 'admin';
    }

    /**
     * Calls a JSON-RPC method over the device's local WebSocket API.
     *
     * A null $params value omits the params member from the request.
     *
     * @throws RuntimeException If the connection, protocol, or RPC call fails.
     */
    private function apiCall(string $method, ?array $params = null, bool $waitForResponse = true): array
    {
        $this->connect();

        if (!$waitForResponse) {
            $this->getJsonRpcClient()->send($method, $params);
            return [];
        }

        return $this->validateResult(
            $method,
            $this->getJsonRpcClient()->call($method, $params),
        );
    }

    /**
     * Opens and authorizes a WebSocket session if one is not active yet.
     */
    private function connect(): void
    {
        $webSocket = $this->getWebSocketClient();

        if ($webSocket->isConnected()) {
            return;
        }

        $webSocket->connect();

        try {
            $result = $this->validateResult(
                'authorise',
                $this->getJsonRpcClient()->call('authorise', [
                    'login' => $this->login,
                    'password' => $this->password,
                ]),
            );

            if (($result['status'] ?? null) !== 'success') {
                throw new RuntimeException('RACS-1101 authorization failed');
            }
        } catch (RuntimeException $e) {
            $webSocket->disconnect();
            throw $e;
        }
    }

    private function getJsonRpcClient(): JsonRpcClient
    {
        return $this->jsonRpcClient ??= new JsonRpcClient($this->getWebSocketClient());
    }

    private function getWebSocketClient(): WebSocketClient
    {
        $url = preg_replace('/^http(s?):\/\//i', 'ws$1://', $this->url);
        if ($url === null || $url === $this->url) {
            throw new RuntimeException("Invalid RACS-1101 URL: $this->url");
        }

        return $this->webSocketClient ??= new WebSocketClient($url);
    }

    /**
     * Applies RACS-1101-specific response validation on top of generic JSON-RPC.
     *
     * @return array<string, mixed>
     */
    private function validateResult(string $method, mixed $result): array
    {
        if (!is_array($result)) {
            throw new RuntimeException("RACS-1101 method $method returned an invalid result");
        }

        if (isset($result['status']) && $result['status'] !== 'success') {
            throw new RuntimeException("RACS-1101 method $method returned status {$result['status']}");
        }

        return $result;
    }
}
