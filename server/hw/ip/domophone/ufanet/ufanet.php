<?php

namespace hw\ip\domophone\ufanet;

use hw\Interface\LanguageInterface;
use hw\ip\domophone\domophone;

/**
 * Abstract base class for Ufanet intercoms.
 */
abstract class ufanet extends domophone implements LanguageInterface
{
    use \hw\ip\common\ufanet\ufanet {
        transformDbConfig as protected commonTransformDbConfig;
    }

    /**
     * @var array|null $dialplans An array that holds dialplan information, which may be null if not loaded.
     */
    protected ?array $dialplans = null;

    /**
     * @var array|null $keys An array that holds keys (RFID, personal code, BLE) information,
     * which may be null if not loaded.
     */
    protected ?array $keys = null;

    /**
     * Converts an RFID code to the device's standard format.
     *
     * @param string $code The raw RFID code.
     * @return string The normalized RFID code.
     */
    protected static function getNormalizedRfid(string $code): string
    {
        $trimmedCode = ltrim($code, '0');
        return strtolower(strlen($trimmedCode) % 2 ? '0' . $trimmedCode : $trimmedCode);
    }

    public function addRfid(string $code, int $apartment = 0): void
    {
        $this->addKey(
            value: self::getNormalizedRfid($code),
            flat: 0,
            type: KeyType::RfidPersonal,
        );
    }

    public function addRfids(array $rfids): void
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
        array $cmsLevels = [],
    ): void
    {
        $this->loadDialplans();
        $this->deleteFlatPersonalCodes($apartment);

        if ($code !== 0) {
            $this->addKey($code, $apartment, KeyType::CodePersonal);
        }

        $this->dialplans[$apartment] = [
            'sip_number' => "$sipNumbers[0]" ?? '',
            'sip' => true,
            'analog' => $cmsEnabled,
            'map' => $this->dialplans[$apartment]['map'] ?? 0,
        ];
    }

    public function configureEncoding(): void
    {
        $this->apiCall('/cgi-bin/configManager.cgi', 'GET', [
            'action' => 'setConfig',

            // Audio stream
            'Encode[0].MainFormat[0].AudioEnable' => 'true',
            'Encode[0].MainFormat[0].Audio.Compression' => 'alaw',
            'Encode[0].MainFormat[0].Audio.Frequency' => 8000,

            // Video main stream
            'Encode[0].MainFormat[0].VideoEnable' => 'true',
            'Encode[0].MainFormat[0].Video.Compression' => 'h264',
            'Encode[0].MainFormat[0].Video.Profile' => 'Baseline',
            'Encode[0].MainFormat[0].Video.resolution' => '1280x720',
            'Encode[0].MainFormat[0].Video.FPS' => 15,
            'Encode[0].MainFormat[0].Video.GOP' => 1,
            'Encode[0].MainFormat[0].Video.GOPmode' => 'normal',
            'Encode[0].MainFormat[0].Video.BitRate' => 1024,
            'Encode[0].MainFormat[0].Video.BitRateControl' => 'vbr',

            // Video extra stream
            'Encode[0].ExtraFormat[0].VideoEnable' => 'false',
            'Encode[0].ExtraFormat[0].Video.Compression' => 'h264',
            'Encode[0].ExtraFormat[0].Video.resolution' => '640x352',
            'Encode[0].ExtraFormat[0].Video.FPS' => 15,
            'Encode[0].ExtraFormat[0].Video.GOP' => 1,
            'Encode[0].ExtraFormat[0].Video.GOPmode' => 'normal',
            'Encode[0].ExtraFormat[0].Video.BitRate' => 512,
            'Encode[0].ExtraFormat[0].Video.BitRateControl' => 'vbr',
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
        $this->apiCall('/api/v1/configuration', 'PATCH', [
            'sip' => [
                'domain' => "$server:$port",
                'user' => $login,
                'password' => $password,
            ],
        ]);
    }

    public function configureUserAccount(string $password): void
    {
        // Empty implementation
    }

    public function deleteApartment(int $apartment = 0): void
    {
        $this->loadDialplans();

        ['map' => $analogReplace, 'analog' => $cmsEnabled] = $this->dialplans[$apartment];

        if ($analogReplace !== 0) {
            $this->dialplans[$apartment] = [
                'sip_number' => '',
                'sip' => false,
                'analog' => $cmsEnabled,
                'map' => $analogReplace,
            ];
        } else {
            unset($this->dialplans[$apartment]);
        }

        $this->deleteFlatPersonalCodes($apartment);
    }

    public function deleteRfid(string $code = ''): void
    {
        $this->loadKeys();

        if ($code === '') {
            $this->keys = array_diff_assoc(
                $this->keys,
                Key::filterByType($this->keys, KeyType::RfidPersonal),
            );
        } else {
            $normalizedRfid = self::getNormalizedRfid($code);

            if (!isset($this->keys[$normalizedRfid])) {
                return;
            }

            if (Key::getType($this->keys[$normalizedRfid]) === KeyType::RfidPersonal) {
                unset($this->keys[$normalizedRfid]);
            }
        }
    }

    public function prepare(): void
    {
        parent::prepare();
        $this->setNetwork();
        $this->setRfidMode();
    }

    public function setAudioLevels(array $levels): void
    {
        if (count($levels) !== 5) {
            return;
        }

        $this->apiCall('/api/v1/configuration', 'PATCH', [
            'volume' => [
                'speaker' => $levels[0],
                'mic' => $levels[1],
                'sys' => $levels[2],
                'analog_speaker' => $levels[3],
                'analog_mic' => $levels[4],
            ],
        ]);
    }

    public function setCallTimeout(int $timeout): void
    {
        $this->apiCall('/api/v1/configuration', 'PATCH', ['commutator' => ['calltime' => $timeout]]);
    }

    public function setDtmfCodes(
        string $code1 = '1',
        string $code2 = '2',
        string $code3 = '3',
        string $codeCms = '1',
    ): void
    {
        $this->apiCall('/api/v1/configuration', 'PATCH', [
            'door' => [
                'dtmf_open_local' => [$code1, $code2],
                'dtmf_open_remote' => $codeCms,
            ],
        ]);
    }

    public function setLanguage(string $language): void
    {
        $payload = [
            'action' => 'locale',
            'ui_language' => $language === 'ru' ? 'ru|Russian' : 'en|English',
        ];

        $this->apiCall('/cgi-bin/webui-settings.cgi', 'POST', $payload, multipart: true);
    }

    public function setPublicCode(int $code = 0): void
    {
        // Empty implementation
    }

    public function setTalkTimeout(int $timeout): void
    {
        // Empty implementation
    }

    public function setUnlockTime(int $time = 3): void
    {
        $this->apiCall('/api/v1/configuration', 'PATCH', [
            'door' => [
                'open_time' => $time,
                'open_2_time' => $time,
            ],
        ]);
    }

    public function syncData(): void
    {
        $this->uploadDialplans();
        $this->uploadRfids();
    }

    public function transformDbConfig(array $dbConfig): array
    {
        $dbConfig = $this->commonTransformDbConfig($dbConfig);

        $dbConfig['cmsLevels'] = [];

        $dbConfig['sip']['stunEnabled'] = false;
        $dbConfig['sip']['stunServer'] = '';
        $dbConfig['sip']['stunPort'] = 3478;

        foreach ($dbConfig['apartments'] as &$apartment) {
            $apartment['cmsLevels'] = [];
        }

        return $dbConfig;
    }

    /**
     * Adds or updates a key (RFID, personal code, BLE).
     *
     * @param string $value Normalized key value.
     * @param int $flat The flat number associated with the key.
     * @param KeyType $type Enum representing the key type.
     * @return void
     */
    protected function addKey(string $value, int $flat, KeyType $type): void
    {
        $this->loadKeys();
        $this->keys[$value] = Key::buildKey($flat, $type);
    }

    /**
     * Removes personal codes of the specified flat.
     *
     * @param int $flatNumber Flat number.
     * @return void
     */
    protected function deleteFlatPersonalCodes(int $flatNumber): void
    {
        $this->loadKeys();

        $this->keys = array_filter(
            $this->keys,
            fn(string $data) => Key::buildKey($flatNumber, KeyType::CodePersonal) !== $data,
        );
    }

    protected function getApartments(): array
    {
        $this->loadDialplans();

        $flats = [];
        $personalCodes = $this->getPersonalCodes();

        foreach ($this->dialplans as $flatNumber => $dialplan) {
            if ($dialplan['sip'] === false || in_array($flatNumber, ['SOS', 'CONS', 'KALITKA', 'FRSI'])) {
                continue;
            }

            $flats[$flatNumber] = [
                'apartment' => $flatNumber,
                'code' => $personalCodes[$flatNumber] ?? 0,
                'sipNumbers' => [$dialplan['sip_number']],
                'cmsEnabled' => $dialplan['analog'],
                'cmsLevels' => [],
            ];
        }

        return $flats;
    }

    protected function getAudioLevels(): array
    {
        $volume = $this->apiCall('/api/v1/configuration')['volume'] ?? null;

        if (!is_array($volume)) {
            return [];
        }

        return array_values($volume);
    }

    protected function getDtmfConfig(): array
    {
        $doorConfig = $this->apiCall('/api/v1/configuration')['door'];

        $dtmfLocal = $doorConfig['dtmf_open_local'] ?? ['1', '2'];
        $dtmfRemote = $doorConfig['dtmf_open_remote'] ?? '1';

        return [
            'code1' => $dtmfLocal[0] ?? '1',
            'code2' => $dtmfLocal[1] ?? '2',
            'code3' => '3',
            'codeCms' => $dtmfRemote,
        ];
    }

    /**
     * Returns all personal codes.
     *
     * @return int[] The list of personal codes.
     */
    protected function getPersonalCodes(): array
    {
        $this->loadKeys();

        $filtered = Key::filterByType($this->keys, KeyType::CodePersonal);
        $codes = [];

        foreach ($filtered as $code => $value) {
            $flatNumber = Key::getFlat($value);

            if ($flatNumber === null) {
                continue;
            }

            if (!isset($codes[$flatNumber])) {
                $codes[$flatNumber] = $code;
            } elseif ($codes[$flatNumber] !== $code) {
                // Set the personal code to null if the apartment has more than one personal code
                $codes[$flatNumber] = null;
            }
        }

        return $codes;
    }

    protected function getRfids(): array
    {
        $this->loadKeys();

        $rfidsRaw = array_keys(
            Key::filterByType($this->keys, KeyType::RfidPersonal),
        );

        return array_map(
            fn(string $rfid) => str_pad(strtoupper($rfid), 14, '0', STR_PAD_LEFT),
            $rfidsRaw,
        );
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

    /**
     * Load and cache dialplans from the API if they haven't been loaded already.
     *
     * @return void
     */
    protected function loadDialplans(): void
    {
        if ($this->dialplans === null) {
            $this->dialplans = $this->apiCall('/api/v1/apartments') ?? [];
        }
    }

    /**
     * Load and cache keys (RFID, personal code, BLE) from the API if they haven't been loaded already.
     *
     * @return void
     */
    protected function loadKeys(): void
    {
        if ($this->keys === null) {
            $this->keys = $this->apiCall('/api/v1/rfids') ?? [];
        }
    }

    /**
     * Set network params.
     *
     * @return void
     */
    protected function setNetwork(): void
    {
        $this->apiCall('/cgi-bin/configManager.cgi', 'GET', [
            'action' => 'setConfig',
            'RTSP.Block' => 'false',
            'Agent.Enable' => 'false',
        ]);
    }

    /**
     * Set RFID reader mode.
     *
     * @return void
     */
    protected function setRfidMode(): void
    {
        $this->apiCall('/api/v1/configuration', 'PATCH', ['door' => ['rfid_pass_en' => false]]);
    }

    /**
     * Switches the internal relay.
     *
     * @param bool $isOn True to turn the relay on, false to turn it off.
     * @param int $duration Duration in seconds the relay should stay on before switching off automatically.
     * Use 0 to keep the state until explicitly changed.
     * @return void
     */
    protected function switchRelay(bool $isOn, int $duration = 0): void
    {
        $this->apiCall('/api/v1/relay/' . ($isOn ? 'on' : 'off'), 'POST', ['duration' => $duration], 3);
    }

    /**
     * Upload the dialplan from the cache into the intercom.
     *
     * @return void
     */
    protected function uploadDialplans(): void
    {
        if ($this->dialplans !== null) {
            $this->apiCall('/api/v1/apartments', 'PUT', $this->dialplans);
        }
    }

    /**
     * Upload RFID codes from the cache into the intercom.
     *
     * @return void
     */
    protected function uploadRfids(): void
    {
        if ($this->keys !== null) {
            $this->apiCall('/api/v1/rfids', 'PUT', $this->keys);
        }
    }
}
