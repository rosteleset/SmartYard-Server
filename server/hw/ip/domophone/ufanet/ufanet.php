<?php

namespace hw\ip\domophone\ufanet;

use hw\ip\domophone\domophone;

/**
 * Abstract class representing an Ufanet intercom.
 */
abstract class ufanet extends domophone
{

    use \hw\ip\common\ufanet\ufanet;

    /**
     * @var array|null $dialplans An array that holds dialplan information,
     * which may be null if not loaded.
     */
    protected ?array $dialplans = null;

    public function addRfid(string $code, int $apartment = 0)
    {
        $decCode = hexdec(substr($code, 8));
        $this->apiCall("/api/v1/rfids/$decCode", 'POST', '');
    }

    public function addRfids(array $rfids) // TODO: PUT
    {
        foreach ($rfids as $rfid) {
            $this->addRfid($rfid);
        }
    }

    public function configureApartment(
        int   $apartment,
        int   $code = 0,
        array $sipNumbers = [],
        bool  $cmsEnabled = true,
        array $cmsLevels = []
    )
    {
        $this->loadDialplans();

        $this->dialplans[$apartment] = [
            'sip_number' => $sipNumbers[0] ?? '',
            'sip' => true,
            'analog' => $cmsEnabled,
            'map' => $this->dialplans[$apartment]['map'] ?? 0,
        ];
    }

    public function configureEncoding()
    {
        // TODO: Implement configureEncoding() method.
    }

    public function configureGate(array $links = [])
    {
        // TODO: Implement configureGate() method.
    }

    public function configureMatrix(array $matrix)
    {
        $this->loadDialplans();

        foreach ($matrix as ['hundreds' => $hundreds, 'tens' => $tens, 'units' => $units, 'apartment' => $apartment]) {
            $analogNumber = $hundreds * 100 + $tens * 10 + $units;

            if (isset($this->dialplans[$apartment])) {
                $this->dialplans[$apartment]['map'] = $analogNumber;
            } else {
                $this->dialplans[$apartment] = [
                    'sip_number' => '',
                    'sip' => false,
                    'analog' => true,
                    'map' => $analogNumber,
                ];
            }
        }
    }

    public function configureSip(
        string $login,
        string $password,
        string $server,
        int    $port = 5060,
        bool   $stunEnabled = false,
        string $stunServer = '',
        int    $stunPort = 3478
    )
    {
        $this->apiCall('/api/v1/configuration', 'PATCH', [
            'sip' => [
                'domain' => "$server:$port",
                'user' => $login,
                'password' => $password,
            ],
        ]);
    }

    public function configureUserAccount(string $password)
    {
        // Empty implementation
    }

    public function deleteApartment(int $apartment = 0)
    {
        $this->loadDialplans();
        unset($this->dialplans[$apartment]);
    }

    public function deleteRfid(string $code = '')
    {
        $decCode = hexdec(substr($code, 8));
        $this->apiCall("/api/v1/rfids/$decCode", 'DELETE');
    }

    public function getLineDiagnostics(int $apartment): string|int|float
    {
        return 0;
    }

    public function openLock(int $lockNumber = 0)
    {
        $lockNumber++;
        $this->apiCall("/api/v1/doors/$lockNumber/open", 'POST');
    }

    public function prepare()
    {
        $this->setNetwork();
        $this->setDisplayLocalization();
    }

    public function setAudioLevels(array $levels)
    {
        if (count($levels) === 2) {
            $this->apiCall('/api/v1/configuration', 'PATCH', [
                'volume' => [
                    'speaker' => $levels[0],
                    'mic' => $levels[1],
                ],
            ]);
        }
    }

    public function setCallTimeout(int $timeout)
    {
        // Empty implementation
    }

    public function setCmsLevels(array $levels)
    {
        // Empty implementation
    }

    public function setCmsModel(string $model = '')
    {
        // TODO: Implement setCmsModel() method.
    }

    public function setConciergeNumber(int $sipNumber)
    {
        $this->apiCall('/api/v1/apartments/CONS', 'DELETE');
        $this->apiCall('/api/v1/apartments/CONS', 'POST', [
            'sip_number' => "$sipNumber",
            'analog' => false,
            'sip' => true,
        ]);
    }

    public function setDtmfCodes(
        string $code1 = '1',
        string $code2 = '2',
        string $code3 = '3',
        string $codeCms = '1'
    )
    {
        // Empty implementation
    }

    public function setLanguage(string $language = 'ru')
    {
        // Empty implementation
    }

    public function setPublicCode(int $code = 0)
    {
        // Empty implementation
    }

    public function setSosNumber(int $sipNumber)
    {
        $this->apiCall('/api/v1/apartments/SOS', 'DELETE');
        $this->apiCall('/api/v1/apartments/SOS', 'POST', [
            'sip_number' => "$sipNumber",
            'analog' => false,
            'sip' => true,
        ]);
    }

    public function setTalkTimeout(int $timeout)
    {
        // Empty implementation
    }

    public function setTickerText(string $text = '')
    {
        $this->apiCall('/api/v1/configuration', 'PATCH', ['display' => ['labels' => [$text, '', '']]]);
    }

    public function setUnlockTime(int $time = 3)
    {
        $this->apiCall('/api/v1/configuration', 'PATCH', ['door' => ['open_time' => $time]]);
    }

    public function setUnlocked(bool $unlocked = true)
    {
        $this->apiCall('/api/v1/configuration', 'PATCH', [
            'door' => [
                'unlock' => $unlocked ? '3000-01-01 00:00:00' : '',
            ],
        ]);
    }

    public function syncData()
    {
        $this->uploadDialplans();
    }

    public function transformDbConfig(array $dbConfig): array
    {
        return $dbConfig;
    }

    protected function getApartments(): array
    {
        $this->loadDialplans();

        $apartments = [];

        foreach ($this->dialplans as $apartmentNumber => $dialplan) {
            if ($dialplan['sip'] === false) {
                continue;
            }

            $apartments[$apartmentNumber] = [
                'apartment' => $apartmentNumber,
                'code' => 0,
                'sipNumbers' => [$dialplan['sip_number']],
                'cmsEnabled' => $dialplan['analog'],
                'cmsLevels' => [],
            ];
        }

        return $apartments;
    }

    protected function getAudioLevels(): array
    {
        $volume = $this->apiCall('/api/v1/configuration')['volume'];
        return [$volume['speaker'], $volume['mic']];
    }

    protected function getCmsLevels(): array
    {
        return [];
    }

    protected function getCmsModel(): string
    {
        // TODO: Implement getCmsModel() method.
        return '';
    }

    protected function getDtmfConfig(): array
    {
        return [];
    }

    protected function getGateConfig(): array
    {
        // TODO: Implement getGateConfig() method.
        return [];
    }

    protected function getMatrix(): array
    {
        $this->loadDialplans();

        $matrix = [];

        foreach ($this->dialplans as $apartmentNumber => $dialplan) {
            $analogNumber = $dialplan['map'];

            if ($analogNumber === 0) {
                continue;
            }

            $hundreds = intdiv($analogNumber, 100);
            $tens = intdiv($analogNumber % 100, 10);
            $units = $analogNumber % 10;

            $matrix[$hundreds . $tens . $units] = [
                'hundreds' => $hundreds,
                'tens' => $tens,
                'units' => $units,
                'apartment' => $apartmentNumber,
            ];
        }

        return $matrix;
    }

    protected function getRfids(): array
    {
        $rfids = [];
        $rawRfids = $this->apiCall('/api/v1/rfids');

        foreach ($rawRfids as $rawRfid => $description) {
            $hexCode = '00000000' . strtoupper(dechex($rawRfid));
            $rfids[$hexCode] = $hexCode;
        }

        return $rfids;
    }

    protected function getSipConfig(): array
    {
        [
            'domain' => $domain,
            'user' => $user,
            'password' => $password,
        ] = $this->apiCall('/api/v1/configuration')['sip'];

        [$server, $port] = explode(':', $domain, 2);

        return [
            'server' => $server,
            'port' => $port,
            'login' => $user,
            'password' => $password,
            'stunEnabled' => false,
            'stunServer' => '',
            'stunPort' => 3478,
        ];
    }

    protected function getTickerText(): string
    {
        return $this->apiCall('/api/v1/configuration')['display']['labels'][0] ?? '';
    }

    protected function getUnlocked(): bool
    {
        return $this->apiCall('/api/v1/configuration')['door']['unlock'] !== '';
    }

    /**
     * Load and cache dialplans from the API if they haven't been loaded already.
     *
     * @return void
     */
    protected function loadDialplans()
    {
        if ($this->dialplans === null) {
            $rawApartments = $this->apiCall('/api/v1/apartments');
            $this->dialplans = array_filter($rawApartments, fn($key) => is_numeric($key), ARRAY_FILTER_USE_KEY);
        }
    }

    /**
     * Set the display text for service messages.
     *
     * @return void
     */
    protected function setDisplayLocalization()
    {
        $this->apiCall('/api/v1/configuration', 'PATCH', [
            'display' => [
                'localization' => [
                    'ENTER_APARTMENT' => 'НАБЕРИТЕ НОМЕР КВАРТИРЫ',
                    'ENTER_PREFIX' => 'НАБЕРИТЕ ПРЕФИКС',
                    'CALL' => 'ИДЁТ ВЫЗОВ',
                    'CALL_GATE' => 'ЗАНЯТО',
                    'CONNECT' => 'ГОВОРИТЕ',
                    'OPEN' => 'ОТКРЫТО',
                    'FAIL_NO_CLIENT' => 'НЕВЕРНЫЙ НОМЕР КВАРТИРЫ',
                    'FAIL_NO_APP_AND_FLAT' => 'АБОНЕНТ НЕДОСТУПЕН',
                    'FAIL_LONG_SPEAK' => 'ВРЕМЯ ВЫШЛО',
                    'FAIL_NO_ANSWER' => 'НЕ ОТВЕЧАЕТ',
                    'FAIL_UNKNOWN' => 'ОШИБКА',
                    'FAIL_BLACK_LIST' => 'АБОНЕНТ ЗАБЛОКИРОВАН',
                    'FAIL_LINE_BUSY' => 'ЛИНИЯ ЗАНЯТА',
                    'KEY_DUPLICATE_ERROR' => 'ДУБЛИКАТ КЛЮЧА ЗАБЛОКИРОВАН',
                    'KEY_READ_ERROR' => 'ОШИБКА ЧТЕНИЯ КЛЮЧА',
                    'KEY_BROKEN_ERROR' => 'КЛЮЧ ВЫШЕЛ ИЗ СТРОЯ',
                    'KEY_UNSUPPORTED_ERROR' => 'КЛЮЧ НЕ ПОДДЕРЖИВАЕТСЯ'
                ],
            ],
        ]);
    }

    /**
     * Set network params.
     *
     * @return void
     */
    protected function setNetwork()
    {
        $this->apiCall('/cgi-bin/configManager.cgi', 'GET', [
            'action' => 'setConfig',
            'RTSP.Block' => 'false',
            'Agent.Enable' => 'false',
        ]);
    }

    /**
     * Upload the dialplan from the cache into the intercom.
     *
     * @return void
     */
    protected function uploadDialplans()
    {
        $this->apiCall('/api/v1/apartments', 'PUT', $this->dialplans);
    }
}
