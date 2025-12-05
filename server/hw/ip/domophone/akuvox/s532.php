<?php

namespace hw\ip\domophone\akuvox;

use hw\Interface\DisplayTextInterface;
use hw\ip\domophone\akuvox\Entities\{
    Dialplan,
    Group,
    User,
};
use hw\ip\domophone\akuvox\Enums\AnalogType;

/**
 * Represents an Akuvox S532 intercom.
 */
class s532 extends akuvox implements DisplayTextInterface
{
    /**
     * @var array<string, AnalogType> Mapping of CMS model codes from the DB to their corresponding AnalogType enums.
     */
    protected const CMS_MODEL_MAP = [
        '' => AnalogType::None,
        'BK-100' => AnalogType::Vizit,
        'BK-400' => AnalogType::Vizit,
        'COM-25U' => AnalogType::Metakom,
        'COM-100U' => AnalogType::Metakom,
        'COM-220U' => AnalogType::Metakom,
        'DIGITAL' => AnalogType::Laskomex,
        'KM20-1' => AnalogType::Eltis,
        'KM100-7.1' => AnalogType::Eltis,
        'KM100-7.2' => AnalogType::Eltis,
        'KM100-7.3' => AnalogType::Eltis,
        'KM100-7.5' => AnalogType::Eltis,
        'KMG-100' => AnalogType::Cyfral,
    ];

    /**
     * @var int The maximum number of user records processed in a single chunk.
     * Larger chunks (up to ~5000) are possible but may cause the device to freeze.
     */
    protected const USERS_CHUNK_SIZE = 1000;

    protected const USER_ID_PREFIX_RFID = 'RFID';
    protected const USER_ID_PREFIX_FLAT = 'FLAT';
    protected const USER_ID_PREFIX_CMS = 'CMS';

    /**
     * @var array|null Users scheduled to be added during data sync.
     */
    protected ?array $usersToAdd = null;

    /**
     * @var array|null Users scheduled to be deleted during data sync.
     */
    protected ?array $usersToDelete = null;

    /**
     * @var array|null Users scheduled to be updated during data sync.
     */
    protected ?array $usersToUpdate = null;

    protected static function getMaxUsers(): int
    {
        return 10000; // Uploading 10,000 users takes about 22 minutes :(
    }

    /**
     * Converts an RFID code to the device's standard format.
     *
     * @param string $code The raw RFID code.
     * @return string The normalized RFID code.
     */
    protected static function getNormalizedRfid(string $code): string
    {
        $trimmedCode = ltrim($code, '0');
        return strlen($trimmedCode) % 2 ? '0' . $trimmedCode : $trimmedCode;
    }

    public function addRfid(string $code, int $apartment = 0): void
    {
        $user = new User(self::USER_ID_PREFIX_RFID . '_' . $code);
        $user->name = self::USER_ID_PREFIX_RFID;
        $user->cardCode = self::getNormalizedRfid($code);
        $this->usersToAdd[] = $user;
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
        // First, add a group for the flat
        $group = new Group($apartment);
        $group->number = $apartment;
        $this->addGroup($group);

        $userId = self::USER_ID_PREFIX_FLAT . '_' . $apartment;

        // Create new or use existing user
        $existingUser = $this->getUserUnique($userId);
        $user = $existingUser ?? new User($userId);

        $user->name = self::USER_ID_PREFIX_FLAT;
        $user->privatePin = $code === 0 ? '' : $code;
        $user->phoneNum = $sipNumbers[0] ?? '';
        $user->group = $apartment;

        if ($existingUser === null) {
            $this->usersToAdd[] = $user;
        } else {
            $this->usersToUpdate[] = $user;
        }
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
        // With STUN enabled, the device doesn't register with the SIP server (it shows "Failed" in the web interface)
        $encodedPassword = base64_encode($password);
        parent::configureSip($login, $encodedPassword, $server, $port, $stunEnabled, $stunServer, $stunPort);
    }

    public function deleteApartment(int $apartment = 0): void
    {
        if ($apartment === 0) {
            // Not sure if this is necessary, full cleaning is not used
        } else {
            $user = $this->getUserUnique(self::USER_ID_PREFIX_FLAT . '_' . $apartment);
            if ($user !== null) {
                $this->usersToDelete[] = $user;
            }
        }
    }

    public function deleteRfid(string $code = ''): void
    {
        if ($code === '') {
            // Not sure if this is necessary, full cleaning is not used
        } else {
            $user = $this->getUserUnique(self::getNormalizedRfid($code));
            if ($user !== null) {
                $this->usersToDelete[] = $user;
            }
        }
    }

    public function getDisplayText(): array
    {
        $text = $this->getConfigParams(['Config.DoorSetting.CUSTOMIZED.Text'])[0] ?? null;
        return $text ? [$text] : [];
    }

    public function getDisplayTextLinesCount(): int
    {
        return 1;
    }

    public function getRfids(): array
    {
        $rfidUsers = $this->findUsers(self::USER_ID_PREFIX_RFID);
        $codes = array_map(static fn(User $user) => str_pad($user->cardCode, 14, '0', STR_PAD_LEFT), $rfidUsers);
        return array_combine($codes, $codes);
    }

    public function prepare(): void
    {
        parent::prepare();
        $this->setHttpsEnabled(false);
        $this->setRelayInversion(true, true);
        $this->setExternalReader(openRelayB: true);
    }

    public function setAdminPassword(string $password): void
    {
        $this->setWebPassword($password);
        $this->setRtspPassword($password);
        $this->setApiPassword($password);
    }

    public function setCmsModel(string $model = ''): void
    {
        $this->setConfigParams(['Config.DoorSetting.ANALOG.Type' => self::CMS_MODEL_MAP[$model]->value]);
    }

    public function setDisplayText(array $textLines): void
    {
        $this->setConfigParams([
            'Config.DoorSetting.GENERAL.Theme' => '1',
            'Config.DoorSetting.CUSTOMIZED.Text' => $textLines[0] ?? '',
        ]);
    }

    public function setDtmfCodes(
        string $code1 = '1',
        string $code2 = '2',
        string $code3 = '3',
        string $codeCms = '1',
    ): void
    {
        parent::setDtmfCodes($code1, $code2, $code3, $codeCms);
        $this->setConfigParams(['Config.DoorSetting.ANALOG.DTMF' => $codeCms]);
    }

    public function syncData(): void
    {
        if ($this->usersToAdd !== null) {
            $this->addUsers($this->usersToAdd);
        }

        if ($this->usersToDelete !== null) {
            $this->deleteUsers($this->usersToDelete);
        }

        if ($this->usersToUpdate !== null) {
            $this->updateUsers($this->usersToUpdate);
        }
    }

    public function transformDbConfig(array $dbConfig): array
    {
        $dbConfig['cmsModel'] = self::CMS_MODEL_MAP[$dbConfig['cmsModel']]->value;

        // TODO: delete after implementing matrix methods
        foreach ($dbConfig['apartments'] as &$apartment) {
            $apartment['cmsEnabled'] = false;
        }

        return $dbConfig;
    }

    /**
     * Adds a new dialplan with the provided data.
     *
     * @param Dialplan $dialplan The dialplan entity to create.
     */
    protected function addDialplan(Dialplan $dialplan): void
    {
        $this->apiCall('', 'POST', [
            'target' => 'dialreplace',
            'action' => 'add',
            'data' => [
                'item' => [$dialplan->toArray()],
            ],
        ]);
    }

    /**
     * Adds a new group with the provided data.
     *
     * @param Group $group The group entity to create.
     */
    protected function addGroup(Group $group): void
    {
        $this->apiCall('', 'POST', [
            'target' => 'group',
            'action' => 'add',
            'data' => [
                'item' => [$group->toArray()],
            ],
        ]);
    }

    /**
     * Adds new users to the intercom.
     *
     * @param User[] $users Array of {@see User} entities to be added.
     */
    protected function addUsers(array $users): void
    {
        foreach (array_chunk($users, self::USERS_CHUNK_SIZE) as $chunk) {
            $this->apiCall('', 'POST', [
                'target' => 'user',
                'action' => 'add',
                'data' => [
                    'item' => array_map(fn(User $user) => $user->toArray(), $chunk),
                ],
            ]);

            sleep(1);
        }
    }

    /**
     * Deletes users from the intercom.
     *
     * @param User[] $users Array of {@see User} entities to be deleted.
     */
    protected function deleteUsers(array $users): void
    {
        foreach (array_chunk($users, self::USERS_CHUNK_SIZE) as $chunk) {
            $this->apiCall('', 'POST', [
                'target' => 'user',
                'action' => 'del',
                'data' => [
                    'item' => array_map(fn(User $user) => ['ID' => $user->id], $chunk),
                ],
            ]);

            sleep(1);
        }
    }

    /**
     * Find users by identifier.
     *
     * @param string $identifier User identifier to search for.
     * @return User[] An array of matched {@see User} objects or empty array if not found or API error occurred.
     */
    protected function findUsers(string $identifier): array
    {
        // API performs fuzzy search and may return multiple partial matches
        $response = $this->apiCall('/user/get?' . http_build_query(['NameOrCode' => $identifier]));
        $items = $response['data']['item'] ?? [];
        return array_map(static fn(array $item) => User::fromArray($item), $items);
    }

    protected function getApartments(): array
    {
        $flats = [];
        $flatUsers = $this->findUsers(self::USER_ID_PREFIX_FLAT);

        foreach ($flatUsers as $flatUser) {
            $flatNumber = (int)str_replace(self::USER_ID_PREFIX_FLAT . '_', '', $flatUser->userId);

            $flats[$flatNumber] = [
                'apartment' => $flatNumber,
                'code' => (int)$flatUser->privatePin,
                'sipNumbers' => [$flatUser->phoneNum],
                'cmsEnabled' => false,
                'cmsLevels' => [],
            ];
        }

        return $flats;
    }

    protected function getCmsModel(): string
    {
        return $this->getConfigParams(['Config.DoorSetting.ANALOG.Type'])[0] ?? '';
    }

    /**
     * Returns a unique user by identifier.
     *
     * @param string $identifier User ID, personal code, or RFID code to search for.
     * @return User|null The matched {@see User} object, or null if no users matched or if more than one user matched.
     */
    protected function getUserUnique(string $identifier): ?User
    {
        $users = $this->findUsers($identifier);

        // Filter manually to ensure strict equality
        $matches = array_filter($users, static fn(User $user) => in_array($identifier, [
            $user->userId,
            $user->privatePin,
            $user->cardCode,
        ], true));

        // Require exactly one match
        if (count($matches) !== 1) {
            return null;
        }

        return reset($matches);
    }

    /**
     * Sets the API password.
     *
     * @param string $password Raw password value.
     * @return void
     */
    protected function setApiPassword(string $password): void
    {
        $this->setConfigParams(['Config.DoorSetting.APIFCGI.Password' => base64_encode($password)]);
    }

    /**
     * Sets the inversion mode for relays.
     *
     * @param bool $invertA Whether relay A should operate in inverted mode.
     * @param bool $invertB Whether relay B should operate in inverted mode.
     * @return void
     */
    protected function setRelayInversion(bool $invertA = false, bool $invertB = false): void
    {
        $this->apiCall('', 'POST', [
            'target' => 'relay',
            'action' => 'set',
            'data' => [
                'Config.DoorSetting.RELAY.RelayAType' => $invertA ? '1' : '0',
                'Config.DoorSetting.RELAY.RelayBType' => $invertB ? '1' : '0',
            ],
        ]);
    }

    /**
     * Sets the RTSP password.
     *
     * @param string $password Raw password value.
     * @return void
     */
    protected function setRtspPassword(string $password): void
    {
        $this->setConfigParams(['Config.DoorSetting.RTSP.Password' => base64_encode($password)]);
    }

    /**
     * Sets the WEB interface password.
     *
     * @param string $password Raw password value.
     * @return void
     */
    protected function setWebPassword(string $password): void
    {
        $this->apiCall('', 'POST', [
            'target' => 'security_basic',
            'action' => 'set',
            'data' => [
                'firstLogin' => '1',
                'userName' => base64_encode($this->login),
                'newPassword' => base64_encode($password),
            ],
        ]);
    }

    /**
     * Updates users on the intercom.
     *
     * @param User[] $users Array of {@see User} entities to be updated.
     */
    protected function updateUsers(array $users): void
    {
        foreach (array_chunk($users, self::USERS_CHUNK_SIZE) as $chunk) {
            $this->apiCall('', 'POST', [
                'target' => 'user',
                'action' => 'set',
                'data' => [
                    'item' => array_map(fn(User $user) => $user->toArray(), $chunk),
                ],
            ]);

            sleep(1);
        }
    }
}
