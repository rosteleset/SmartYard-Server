<?php

namespace hw\ip\domophone\rubetek;

use hw\Interface\FreePassInterface;
use hw\ip\domophone\domophone;
use hw\ip\domophone\rubetek\Clients\{
    JsonRpcClient,
    WebSocketClient,
};
use JsonException;
use RuntimeException;

/**
 * Represents a Rubetek RACS-1101 access controller.
 */
class racs1101 extends domophone implements FreePassInterface
{
    private const RFID_ACCESS_ALL_RELAYS = 3;
    private const RFID_READ_BATCH_SIZE = 50;
    private const RFID_ADD_BATCH_SIZE = 50;
    private const RFID_DELETE_BATCH_SIZE = 80;

    private const RELAY_MODE_NORMAL = 0;
    private const RELAY_MODE_FREE_PASS = 1;

    private const TIME_SYNC_TOLERANCE = 30;
    private const VIRTUAL_NTP_CONFIG = [
        'server' => '',
        'port' => 0,
        'timezone' => '',
    ];

    private ?WebSocketClient $webSocketClient = null;
    private ?JsonRpcClient $jsonRpcClient = null;

    /** @var string[] */
    private array $rfidsToDelete = [];

    private static function normalizeRfid(string $code): string
    {
        return strtoupper(ltrim($code, '0')) ?: '0';
    }

    public function addRfid(string $code, int $apartment = 0): void
    {
        $this->addRfids([$code]);
    }

    public function addRfids(array $rfids): void
    {
        $keys = array_map(
            static fn(string $code): array => [
                'key' => self::normalizeRfid($code),
                'access' => self::RFID_ACCESS_ALL_RELAYS,
            ],
            array_values($rfids),
        );

        foreach (array_chunk($keys, self::RFID_ADD_BATCH_SIZE) as $keyList) {
            $this->apiCall('add_keys', ['key_list' => $keyList]);
        }
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
        $this->apiCall('set_time', ['time' => time()]);
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
        if ($code === '') {
            $this->apiCall('del_all_keys', waitForResponse: false);
            return;
        }

        $this->rfidsToDelete[] = self::normalizeRfid($code);
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

    public function isFreePassEnabled(): bool
    {
        $relayModes = $this->apiCall('get_config', [
            'var_list' => ['main.access.relay_mode'],
        ])['var_list']['main.access.relay_mode'] ?? null;

        if (!is_array($relayModes) || count($relayModes) !== 2) {
            throw new RuntimeException('RACS-1101 returned invalid relay modes');
        }

        return $relayModes === [self::RELAY_MODE_FREE_PASS, self::RELAY_MODE_FREE_PASS];
    }

    public function openLock(int $lockNumber = 0): void
    {
        $this->apiCall('unlock', ['relay_index' => $lockNumber + 1]);
    }

    public function reboot(): void
    {
        $this->connect();

        try {
            $this->getWebSocketClient()->send(json_encode(
                ['cmd' => 'reboot'],
                JSON_THROW_ON_ERROR,
            ));
            $response = json_decode(
                $this->getWebSocketClient()->receive(),
                true,
                512,
                JSON_THROW_ON_ERROR,
            );

            if (($response['cmd'] ?? null) !== 'reboot_start') {
                throw new RuntimeException('RACS-1101 rejected reboot command');
            }
        } catch (JsonException $e) {
            throw new RuntimeException('Invalid RACS-1101 reboot response', 0, $e);
        } finally {
            $this->getWebSocketClient()->disconnect();
        }
    }

    public function reset(): void
    {
        // Empty implementation
    }

    public function setAdminPassword(string $password): void
    {
        $this->apiCall('set_config', [
            'var_list' => [
                'main' => [
                    'local' => [
                        'user' => $this->login,
                        'pass' => $password,
                    ],
                ],
            ],
        ]);

        $this->password = $password;
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

    public function setFreePassEnabled(bool $enabled): void
    {
        $relayMode = $enabled ? self::RELAY_MODE_FREE_PASS : self::RELAY_MODE_NORMAL;

        $this->apiCall('set_config', [
            'var_list' => [
                'main' => [
                    'access' => [
                        'relay_mode' => [$relayMode, $relayMode],
                    ],
                ],
            ],
        ]);
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
        $this->apiCall('set_config', [
            'var_list' => [
                'main' => [
                    'access' => [
                        'unlock_time' => $time,
                    ],
                ],
            ],
        ]);
    }

    public function syncData(): void
    {
        $this->deleteRfids();
    }

    public function transformDbConfig(array $dbConfig): array
    {
        $dbConfig['dtmf'] = $this->getDtmfConfig();
        $dbConfig['sip'] = $this->getSipConfig();

        $dbConfig['cmsModel'] = '';
        $dbConfig['matrix'] = [];
        $dbConfig['apartments'] = [];
        $dbConfig['ntp'] = self::VIRTUAL_NTP_CONFIG;

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
        $deviceTime = $this->apiCall('get_time', (object)[])['time'] ?? null;
        if (!is_int($deviceTime)) {
            throw new RuntimeException('RACS-1101 returned an invalid time');
        }

        if (abs($deviceTime - time()) <= self::TIME_SYNC_TOLERANCE) {
            return self::VIRTUAL_NTP_CONFIG;
        }

        return array_replace(self::VIRTUAL_NTP_CONFIG, ['server' => '__OUT_OF_SYNC__']);
    }

    protected function getRfids(): array
    {
        $rfids = [];
        $previousKey = null;

        do {
            $params = $previousKey === null ? (object)[] : ['prev_key' => $previousKey];
            $keyList = $this->apiCall('get_keys', $params)['key_list'] ?? [];

            foreach ($keyList as $keyData) {
                $code = $keyData['key'] ?? null;
                if (!is_string($code) || $code === '') {
                    throw new RuntimeException('RACS-1101 returned an invalid RFID key');
                }

                $normalizedCode = str_pad(strtoupper($code), 14, '0', STR_PAD_LEFT);
                $rfids[$normalizedCode] = $normalizedCode;
            }

            $lastKey = $keyList === [] ? null : end($keyList)['key'] ?? null;
            if ($lastKey !== null && $lastKey === $previousKey) {
                throw new RuntimeException('RACS-1101 returned a repeated RFID page');
            }

            $previousKey = $lastKey;
        } while (count($keyList) === self::RFID_READ_BATCH_SIZE);

        return $rfids;
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
    private function apiCall(
        string            $method,
        array|object|null $params = null,
        bool              $waitForResponse = true,
    ): array
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

    private function deleteRfids(): void
    {
        $keys = array_map(
            static fn(string $code): array => ['key' => $code],
            array_values(array_unique($this->rfidsToDelete)),
        );

        foreach (array_chunk($keys, self::RFID_DELETE_BATCH_SIZE) as $keyList) {
            $this->apiCall('del_keys', ['key_list' => $keyList]);
        }

        $this->rfidsToDelete = [];
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
