<?php

namespace hw\ip\domophone\basip;

use hw\Interface\{
    FreePassInterface,
    LanguageInterface,
};
use hw\ip\domophone\domophone;

/**
 * Abstract class representing an BASIP intercom.
 */
abstract class basip extends domophone implements FreePassInterface, LanguageInterface
{
    use \hw\ip\common\basip\basip {
        transformDbConfig as protected commonTransformDbConfig;
    }

    protected const DISABLED_STUN_ADDRESS = '127.0.0.1';

    public function addRfid(string $code, int $apartment = 0): void
    {
        $formattedCode = implode('-', str_split($code, 2)); // 0000001A2B3C4D => 00-00-00-1A-2B-3C-4D
        $this->addIdentifier($code, $formattedCode, IdentifierType::Card);
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
        $this->apiCall("/v1/forward/item/$apartment", 'POST', [
            'forward_entity_list' => [$sipNumbers[0] ?? ''],
        ]);

        $personalCodeUid = $this->getUidByIdentifierName($apartment);

        if ($personalCodeUid !== null) {
            $this->deleteIdentifiers([$personalCodeUid]);
        }

        if ($code !== 0) {
            $this->addIdentifier($apartment, $code, IdentifierType::PersonalCode);
        }
    }

    public function configureEncoding(): void
    {
        $this->apiCall('/v1/device/settings/video', 'POST', [
            'fps' => 25, // No way to change this via WEB, so let it be the default value from the POST payload
            'video_resolution' => '1280x720',
        ]);

        $this->apiCall('/v1/device/settings/payload', 'POST', ['payload_codec_h264' => 102]);
    }

    public function configureMatrix(array $matrix): void
    {
        // Empty implementation
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
        $this->apiCall('/v1/device/sip/settings', 'POST', [
            'outbound' => '',
            'password' => $password,
            'proxy' => "sip:$server:$port",
            'realm' => "$server:$port",
            'registration_interval' => 900, // Max allowed value
            'transport' => 'udp',
            'user' => $login,
            'user_id' => $password, // Use this field to store password. The password field always says "WebPass".
            'stun' => [
                'ip' => $stunEnabled ? $stunServer : self::DISABLED_STUN_ADDRESS,
                'port' => $stunPort,
            ],
        ]);

        $this->apiCall('/v1/device/sip/enable', 'POST', ['sip_enable' => $login !== '']);
        $this->setConciergeNumber(9999); // Need to set a new concierge URL, the SIP server address may have changed
    }

    public function configureUserAccount(string $password): void
    {
        // Empty implementation
    }

    public function deleteApartment(int $apartment = 0): void
    {
        if ($apartment === 0) {
            return; // TODO, or not TODO
        } else {
            $this->deleteForwards([$apartment]);

            $personalCodeUid = $this->getUidByIdentifierName($apartment);
            if ($personalCodeUid !== null) {
                $this->deleteIdentifiers([$personalCodeUid]);
            }
        }
    }

    public function deleteRfid(string $code = ''): void
    {
        if ($code === '') {
            $uids = $this->getUidByIdentifierName();
        } else {
            $uid = $this->getUidByIdentifierName($code);

            if ($uid === null) {
                return;
            }

            $uids = [$uid];
        }

        $this->deleteIdentifiers($uids);
    }

    public function getLineDiagnostics(int $apartment): string|int|float
    {
        // Empty implementation
        return 0;
    }

    public function isFreePassEnabled(): bool
    {
        return $this->apiCall('/v1/access/freeaccess')['enable'] ?? true;
    }

    public function openLock(int $lockNumber = 0): void
    {
        $this->apiCall('/v1/access/general/lock/open/remote/accepted/' . $lockNumber + 1);
    }

    public function prepare(): void
    {
        parent::prepare();
        $this->configureInternalReader();
    }

    public function setAudioLevels(array $levels): void
    {
        if (count($levels) === 2) {
            $this->apiCall('/v1/device/settings/volume', 'POST', ['volume_level' => $levels[0]]);
            $this->apiCall('/v1/device/settings/mic', 'POST', ['mic_gain_level' => $levels[1]]);
        }
    }

    public function setCallTimeout(int $timeout): void
    {
        $this->apiCall('/v1/device/call/dial/timeout', 'POST', [
            'dial_timeout' => $timeout,
            'forwarding_timeout' => 25,
        ]);
    }

    public function setCmsModel(string $model = ''): void
    {
        // Empty implementation
    }

    public function setConciergeNumber(int $sipNumber): void
    {
        ['server' => $sipServer, 'port' => $sipPort] = $this->getSipConfig();

        $this->apiCall('/v1/device/call/concierge', 'POST', [
            'number_enable' => true,
            'number_url' => "sip:$sipNumber@$sipServer:$sipPort",
        ]);
    }

    public function setDtmfCodes(
        string $code1 = '1',
        string $code2 = '2',
        string $code3 = '3',
        string $codeCms = '1',
    ): void
    {
        $this->apiCall('/v1/access/general/lock/dtmf/1', 'POST', ['dtmf_code' => $code1]);
        $this->apiCall('/v1/access/general/lock/dtmf/2', 'POST', ['dtmf_code' => $code2]);
    }

    public function setFreePassEnabled(bool $enabled): void
    {
        $days = array_map(fn($day) => [
            'lock' => 'all',
            'enable' => true,
            'time_from' => 0,
            'time_to' => 86340,
            'day' => $day,
        ], ['MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT', 'SUN']);

        $this->apiCall('/v1/access/freeaccess', 'POST', [
            'enable' => $enabled,
            'days' => $days,
        ]);
    }

    public function setLanguage(string $language): void
    {
        $lang = match ($language) {
            'es' => 'Spanish',
            'ru' => 'Russian',
            'uk' => 'Ukrainian',
            'pl' => 'Polish',
            'nl' => 'Dutch',
            'tr' => 'Turkish',
            'fr' => 'French',
            'da' => 'Danish',
            'pt' => 'Portuguese',
            'de' => 'Deutsch',
            default => 'English',
        };

        $this->apiCall("/v1/device/language?language=$lang", 'POST');
    }

    public function setPublicCode(int $code = 0): void
    {
        $this->apiCall('/v1/access/general/unlock/input/code', 'POST', [
            'input_code_enable' => $code !== 0,
            'input_code_number' => $code,
        ]);
    }

    public function setSosNumber(int $sipNumber): void
    {
        // Empty implementation
    }

    public function setTalkTimeout(int $timeout): void
    {
        $this->apiCall('/v1/device/call/talk/timeout', 'POST', ['talk_timeout' => $timeout]);
    }

    public function setUnlockTime(int $time = 3): void
    {
        $this->apiCall('/v1/access/general/lock/timeout/1', 'POST', ['lock_timeout' => $time]);
        $this->apiCall('/v1/access/general/lock/timeout/2', 'POST', ['lock_timeout' => $time]);
    }

    public function transformDbConfig(array $dbConfig): array
    {
        $dbConfig = $this->commonTransformDbConfig($dbConfig);

        if ($dbConfig['sip']['stunEnabled'] === false) {
            $dbConfig['sip']['stunServer'] = self::DISABLED_STUN_ADDRESS;
        }

        foreach ($dbConfig['apartments'] as &$apartment) {
            $apartment['cmsEnabled'] = false;
        }

        return $dbConfig;
    }

    /**
     * Adds a new identifier.
     *
     * @param string $name The identifier name.
     * @param string $number The identifier number.
     * @param IdentifierType $identifierType The identifier type enum value.
     * @return void
     */
    protected function addIdentifier(string $name, string $number, IdentifierType $identifierType): void
    {
        $this->apiCall('/v1/access/identifier', 'POST', [
            'identifier_number' => $number,
            'identifier_owner' => [
                'name' => $name,
                'type' => 'owner',
            ],
            'identifier_type' => $identifierType->value,
            'lock' => 'first',
            'valid' => [
                'passes' => [
                    'is_permanent' => true,
                    'max_passes' => null,
                    'time' => [
                        'from' => null,
                        'is_permanent' => true,
                        'to' => null,
                    ],
                ],
            ],
        ]);
    }

    /**
     * Configures the internal reader.
     *
     * @return void
     */
    protected function configureInternalReader(): void
    {
        $this->apiCall('/v1/access/general/wiegand/type', 'POST', [
            'identifier_representation' => 'hex',
            'type' => 'wiegand_58', // Also need to reconfigure the reader mode using the "BAS-IP UKEY Config" app
        ]);
    }

    /**
     * Deletes forwards by their flat number.
     *
     * @param int[] $flatNumbers Array of flat numbers to delete.
     * @return void
     */
    protected function deleteForwards(array $flatNumbers): void
    {
        $this->apiCall('/v1/forward/items', 'DELETE', ['uid_items' => $flatNumbers]);
    }

    /**
     * Deletes identifiers by their UIDs.
     *
     * @param int[] $uids Array of identifier UIDs to delete.
     * @return void
     */
    protected function deleteIdentifiers(array $uids): void
    {
        $this->apiCall('/v1/access/identifier/items', 'DELETE', ['uid_items' => $uids]);
    }

    /**
     * Fetches all items from a paginated API endpoint.
     *
     * @param string $endpoint API endpoint path.
     * @param int $limit Items per page.
     * @return array Merged list of all items.
     */
    protected function fetchAllPages(string $endpoint, int $limit = 50): array
    {
        $result = [];

        for ($pageNumber = 1; ; $pageNumber++) {
            $url = "$endpoint?limit=$limit&page_number=$pageNumber";
            $items = $this->apiCall($url)['list_items'] ?? [];

            if (!is_array($items) || $items === []) {
                break;
            }

            $result = [...$result, ...$items];
        }

        return $result;
    }

    protected function getApartments(): array
    {
        $flats = [];
        $forwards = $this->getForwards();

        foreach ($forwards as $forward) {
            ['forward_entity_list' => $sipNumbers, 'forward_number' => $flatNumber] = $forward;
            $personalCode = $this->getPersonalCodeByFlatNumber($flatNumber) ?? 0;

            $flats[$flatNumber] = [
                'apartment' => $flatNumber,
                'code' => $personalCode,
                'sipNumbers' => [$sipNumbers[0]],
                'cmsEnabled' => false,
                'cmsLevels' => [],
            ];
        }

        return $flats;
    }

    protected function getAudioLevels(): array
    {
        $volumeLevel = $this->apiCall('/v1/device/settings/volume')['volume_level'];
        $micLevel = $this->apiCall('/v1/device/settings/mic')['mic_gain_level'];

        return [$volumeLevel, $micLevel];
    }

    protected function getCmsModel(): string
    {
        // Empty implementation
        return '';
    }

    protected function getDtmfConfig(): array
    {
        $code1 = $this->apiCall('/v1/access/general/lock/dtmf/1')['dtmf_code'];
        $code2 = $this->apiCall('/v1/access/general/lock/dtmf/2')['dtmf_code'];

        return [
            'code1' => $code1,
            'code2' => $code2,
            'code3' => '3',
            'codeCms' => '1',
        ];
    }

    /**
     * Returns all forward rules.
     *
     * @return array List of forward rule arrays.
     */
    protected function getForwards(): array
    {
        return $this->fetchAllPages('/v1/forward/items', 100);
    }

    /**
     * Returns all identifiers.
     *
     * @return array List of identifier arrays.
     */
    protected function getIdentifiers(): array
    {
        return $this->fetchAllPages('/v1/access/identifier/items');
    }

    protected function getMatrix(): array
    {
        // Empty implementation
        return [];
    }

    /**
     * Returns the personal code associated with a given flat number.
     *
     * @param string $flatNumber The flat number to search for.
     * @return int|null The personal code if found, or null if no match exists.
     */
    protected function getPersonalCodeByFlatNumber(string $flatNumber): ?int
    {
        $identifiers = $this->getIdentifiers() ?? [];

        $personalCodes = array_filter(
            $identifiers,
            static fn($identifier) => $identifier['identifier_type'] === IdentifierType::PersonalCode->value,
        );

        foreach ($personalCodes as $personalCode) {
            if (($personalCode['identifier_owner']['name'] ?? null) === $flatNumber) {
                return $personalCode['identifier_number'];
            }
        }

        return null;
    }

    protected function getRfids(): array
    {
        $identifiers = $this->getIdentifiers() ?? [];

        $rfids = array_filter(
            $identifiers,
            static fn($identifier) => $identifier['identifier_type'] === IdentifierType::Card->value,
        );

        return array_column(array_column($rfids, 'identifier_owner'), 'name', 'name');
    }

    protected function getSipConfig(): array
    {
        $sipSettings = $this->apiCall('/v1/device/sip/settings');

        $realmParts = explode(':', $sipSettings['realm'], 2);

        return [
            'server' => $realmParts[0],
            'port' => $realmParts[1] ?? 5060,
            'login' => $sipSettings['user'],
            'password' => $sipSettings['user_id'], // See the comment in the configureSip() method
            'stunEnabled' => $sipSettings['stun']['ip'] !== self::DISABLED_STUN_ADDRESS,
            'stunServer' => $sipSettings['stun']['ip'],
            'stunPort' => $sipSettings['stun']['port'],
        ];
    }

    /**
     * Returns UID by identifier name or all UIDs if name is null.
     *
     * @param string|null $identifierName The name of the identifier owner to search for.
     * @return int|int[]|null A single UID, an array of all UIDs, or null if not found.
     */
    protected function getUidByIdentifierName(?string $identifierName = null): int|array|null
    {
        $identifiers = $this->getIdentifiers() ?? [];

        if ($identifierName === null) {
            return array_column($identifiers, 'identifier_uid');
        }

        foreach ($identifiers as $identifier) {
            if (($identifier['identifier_owner']['name'] ?? null) === $identifierName) {
                return $identifier['identifier_uid'];
            }
        }

        return null;
    }
}
