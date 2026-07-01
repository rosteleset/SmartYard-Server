<?php

namespace hw\ip\domophone\ufanet;

use hw\ip\domophone\domophone;
use hw\ip\domophone\ufanet\HttpClient\HttpClient;

/**
 * Represents an Ufanet Secret Mole controller.
 */
class secretMole extends domophone
{
    private const MAX_RFID_BATCH_SIZE_ADD = 18;
    private const MAX_RFID_BATCH_SIZE_DELETE = 32;
    private const MAX_RFID_PAGE_SIZE = 20;

    private HttpClient $client;
    private array $rfidsToDelete = [];

    public function __construct(string $url, string $password, bool $firstTime = false, bool $lazy = false)
    {
        $this->client = new HttpClient($url, $firstTime ? '123456' : $password);
        parent::__construct($url, $password, $firstTime, $lazy);
    }

    private static function normalizeRfid(string $code): string
    {
        $trimmedCode = ltrim($code, '0');
        $normalizedCode = strtoupper(strlen($trimmedCode) % 2 ? '0' . $trimmedCode : $trimmedCode);

        return implode('', array_reverse(str_split($normalizedCode, 2)));
    }

    public function addRfid(string $code, int $apartment = 0): void
    {
        $this->addRfids([$code]);
    }

    public function addRfids(array $rfids): void
    {
        if ($rfids === []) {
            return;
        }

        foreach (array_chunk(array_unique($rfids), self::MAX_RFID_BATCH_SIZE_ADD) as $rfidChunk) {
            $keys = [];

            foreach ($rfidChunk as $rfid) {
                $keys[] = [
                    'key' => self::normalizeRfid($rfid),
                    'rssi' => 0,
                    'group' => 0,
                    'descr' => '',
                ];
            }

            $this->client->request('/api/v1/rfids', 'PUT', ['keys' => $keys]);
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
        ['host' => $server, 'port' => $port] = parse_url_ext($url);

        $this->client->request('/api/v1/conn-config', 'PATCH', [
            'syslog' => [
                'servers' => ["$server:$port", ''],
            ],
        ]);
    }

    public function configureMatrix(array $matrix): void
    {
        // Empty implementation
    }

    public function configureNtp(string $server, int $port = 123, string $timezone = 'Europe/Moscow'): void
    {
        $this->client->request('/api/v1/conn-config', 'PATCH', [
            'time' => [
                'timezone' => $timezone,
                'ntp_servers' => [$server, ''],
            ],
        ]);
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
            $this->client->request('/api/v1/rfids', 'DELETE', []);
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
        $response = $this->client->request('/api/v1/status', timeout: 3);

        return [
            'DeviceID' => $response['eth']['ip'],
            'HardwareVersion' => $response['hw_ver'],
        ];
    }

    public function openLock(int $lockNumber = 0): void
    {
        $lockNumber++;
        $this->client->request("/api/v1/doors/$lockNumber/open", timeout: 3);
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
        $this->client->request('/api/v1/auth-config', 'PATCH', [
            'http' => [
                'password' => $password,
            ],
        ]);

        $this->client->setPassword($password);
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
        // Empty implementation
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
        $response = $this->client->request('/api/v1/conn-config');
        $server = $response['syslog']['servers'][0];
        return "syslog.udp:$server";
    }

    protected function getMatrix(): array
    {
        // Empty implementation
        return [];
    }

    protected function getNtpConfig(): array
    {
        $response = $this->client->request('/api/v1/conn-config');

        return [
            'server' => $response['time']['ntp_servers'][0],
            'port' => 123,
            'timezone' => $response['time']['timezone'],
        ];
    }

    protected function getRfids(): array
    {
        $response = $this->client->request('/api/v1/rfids-count');
        $count = (int)$response['count'];
        $rfids = [];

        for ($page = 0; $page * self::MAX_RFID_PAGE_SIZE < $count; $page++) {
            $response = $this->client->request('/api/v1/rfids', 'POST', [
                'page' => $page,
                'count' => self::MAX_RFID_PAGE_SIZE,
                'short' => true,
            ]);

            foreach ($response['keys_short'] as $rfid) {
                $rfid = strlen($rfid) % 2 ? '0' . $rfid : $rfid;
                $rfid = implode('', array_reverse(str_split($rfid, 2)));
                $rfids[] = str_pad(strtoupper($rfid), 14, '0', STR_PAD_LEFT);
            }
        }

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
        $this->defaultPassword = '123456';
    }

    private function deleteRfids(): void
    {
        foreach (array_chunk(array_unique($this->rfidsToDelete), self::MAX_RFID_BATCH_SIZE_DELETE) as $rfidChunk) {
            $this->client->request('/api/v1/rfids', 'DELETE', ['keys' => $rfidChunk]);
        }

        $this->rfidsToDelete = [];
    }
}
