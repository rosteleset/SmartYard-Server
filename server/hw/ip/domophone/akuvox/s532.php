<?php

namespace hw\ip\domophone\akuvox;

use CURLFile;
use hw\Interface\{
    DisplayTextInterface,
    FreePassInterface,
    LanguageInterface,
};
use hw\ip\domophone\akuvox\Entities\{
    Group,
    User,
};
use hw\ip\domophone\akuvox\Enums\AnalogType;

/**
 * Represents an Akuvox S532 intercom.
 */
class s532 extends akuvox implements DisplayTextInterface, FreePassInterface, LanguageInterface
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
     * @var int The maximum number of records processed in a single chunk.
     * Larger chunks (up to ~5000) are possible but may cause the device to freeze.
     */
    protected const ITEMS_CHUNK_SIZE = 1000;

    protected const USER_ID_PREFIX_RFID = 'RFID';
    protected const USER_ID_PREFIX_FLAT = 'FLAT';
    protected const USER_ID_PREFIX_CMS = 'CMS';

    /**
     * @var User[]|null Users scheduled to be added during data sync.
     */
    protected ?array $usersToAdd = null;

    /**
     * @var User[]|null Users scheduled to be deleted during data sync.
     */
    protected ?array $usersToDelete = null;

    /**
     * @var User[]|null Users scheduled to be updated during data sync.
     */
    protected ?array $usersToUpdate = null;

    /**
     * @var Group[]|null Groups scheduled to be added during data sync.
     */
    protected ?array $groupsToAdd = null;

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

    /**
     * Extracts apartment number from {@see User} object.
     *
     * @param User $user User instance containing userId.
     * @return int
     */
    protected static function extractFlatNumber(User $user): int
    {
        $parts = explode('_', $user->userId, 2);
        return isset($parts[1]) ? (int)$parts[1] : 0;
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
        $this->ensureGroupExists($apartment);

        // Create a new user or use an existing one
        $userId = self::USER_ID_PREFIX_FLAT . '_' . $apartment;
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

    public function configureMatrix(array $matrix): void
    {
        $this->clearMatrix();

        foreach ($matrix as $matrixCell) {
            [
                'hundreds' => $hundreds,
                'tens' => $tens,
                'units' => $units,
                'apartment' => $apartment,
            ] = $matrixCell;

            $this->ensureGroupExists($apartment);

            // Create a new user
            $user = new User(self::USER_ID_PREFIX_CMS . '_' . $apartment);

            $user->name = self::USER_ID_PREFIX_CMS;
            $user->analogSystem = '1'; // Enable CMS
            $user->analogNumber = $hundreds * 100 + $tens * 10 + $units; // TODO: digital matrix
            $user->group = $apartment;

            $this->usersToAdd[] = $user;
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

    public function isFreePassEnabled(): bool
    {
        $response = $this->getConfigParams([
            'Config.DoorSetting.RELAYSCHEDULE.RelayAEnable',
            'Config.DoorSetting.RELAYSCHEDULE.RelayBEnable',
        ]);

        return empty($response) || in_array('1', $response, true);
    }

    public function prepare(): void
    {
        parent::prepare();
        $this->setHttpsEnabled(false);
        $this->setRelayInversion(true, true);
        $this->setInputTriggerLevel(onHighC: true, onHighD: true);
        $this->setExternalReader(openRelayB: true);
        $this->setAccessGrantedSound();
        $this->setDirectoryEnabled(false);
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

    public function setConciergeNumber(int $sipNumber): void
    {
        $this->setConfigParams(['Config.Programable.SOFTKEY01.LocalParam1' => $sipNumber . str_repeat(';', 7)]);
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

    public function setFreePassEnabled(bool $enabled): void
    {
        $this->setConfigParams([
            'Config.DoorSetting.RELAYSCHEDULE.RelayAEnable' => $enabled ? '1' : '0',
            'Config.DoorSetting.RELAYSCHEDULE.RelayASchedule' => '1001/',
            'Config.DoorSetting.RELAYSCHEDULE.RelayBEnable' => $enabled ? '1' : '0',
            'Config.DoorSetting.RELAYSCHEDULE.RelayBSchedule' => '1001/',
        ]);
    }

    public function setLanguage(string $language): void
    {
        $lang = match ($language) {
            'ru' => '3', // Russian
            'es' => '6', // Spanish
            'fr' => '9', // French
            'pl' => '13', // Polish
            'tr' => '14', // Turkish
            'et' => '18', // Estonian
            default => '1', // English
        };

        $this->setConfigParams(['Config.Settings.LANGUAGE.Type' => $lang]); // LCD language
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

        if ($this->groupsToAdd !== null) {
            $this->addGroups($this->groupsToAdd);
        }

        $this->cleanupGroups();
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
     * Adds new groups to the intercom.
     *
     * @param Group[] $groups Array of {@see Group} entities to be added.
     */
    protected function addGroups(array $groups): void
    {
        $this->executeChunkOperation('group', 'add', $groups, fn(Group $group) => $group->toArray());
    }

    /**
     * Adds new users to the intercom.
     *
     * @param User[] $users Array of {@see User} entities to be added.
     */
    protected function addUsers(array $users): void
    {
        $this->executeChunkOperation('user', 'add', $users, fn(User $user) => $user->toArray());
    }

    /**
     * Removes groups that are not associated with any user.
     *
     * @return void
     */
    protected function cleanupGroups(): void
    {
        $groups = $this->getGroups();
        $users = array_merge(
            $this->findUsers(self::USER_ID_PREFIX_FLAT),
            $this->findUsers(self::USER_ID_PREFIX_CMS),
        );

        $unusedGroups = array_filter($groups, function (Group $group) use ($users) {
            foreach ($users as $user) {
                if ($user->group === $group->number) {
                    return false;
                }
            }

            return true;
        });

        $this->executeChunkOperation('group', 'del', $unusedGroups, fn(Group $group) => ['ID' => $group->id]);
    }

    /**
     * Deletes all CMS users from the intercom
     *
     * @return void
     */
    protected function clearMatrix(): void
    {
        $cmsUsers = $this->findUsers(self::USER_ID_PREFIX_CMS);
        $this->deleteUsers($cmsUsers);
    }

    /**
     * Deletes users from the intercom.
     *
     * @param User[] $users Array of {@see User} entities to be deleted.
     */
    protected function deleteUsers(array $users): void
    {
        $this->executeChunkOperation('user', 'del', $users, fn(User $user) => ['ID' => $user->id]);
    }

    /**
     * Adds a group for the given flat if it doesn't exist yet.
     *
     * @param int $flatNumber Flat number or group name.
     */
    protected function ensureGroupExists(int $flatNumber): void
    {
        // Check if the group already exists on the device
        $existingGroup = $this->getGroupByName($flatNumber);
        if ($existingGroup !== null) {
            return;
        }

        // Check if the group is already scheduled for addition
        if ($this->groupsToAdd !== null) {
            foreach ($this->groupsToAdd as $group) {
                if ($group->name === (string)$flatNumber) {
                    return;
                }
            }
        }

        // Add new group
        $group = new Group($flatNumber);
        $group->number = $flatNumber;
        $this->groupsToAdd[] = $group;
    }

    /**
     * Executes an API operation on entities in chunked batches.
     *
     * @param string $target API entity target, e.g. "user" or "group".
     * @param string $action API operation, e.g. "add", "del", "set".
     * @param User[]|Group[] $entities List of entities.
     * @param callable $mapper A function that transforms an entity into an array suitable for the API payload.
     * @return void
     */
    protected function executeChunkOperation(string $target, string $action, array $entities, callable $mapper): void
    {
        foreach (array_chunk($entities, self::ITEMS_CHUNK_SIZE) as $chunk) {
            $this->apiCall('', 'POST', [
                'target' => $target,
                'action' => $action,
                'data' => [
                    'item' => array_map($mapper, $chunk),
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
            $flatNumber = self::extractFlatNumber($flatUser);

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
     * Returns a group by its name.
     *
     * @param string $name Group name to search for.
     * @return Group|null The matched {@see Group} object, or null if not found.
     */
    protected function getGroupByName(string $name): ?Group
    {
        $groups = $this->getGroups();

        foreach ($groups as $group) {
            if ($group->name === $name) {
                return $group;
            }
        }

        return null;
    }

    /**
     * Returns all groups.
     *
     * @return Group[] An array of {@see Group} objects.
     */
    protected function getGroups(): array
    {
        $response = $this->apiCall('/group/get'); // TODO: add caching
        $items = $response['data']['item'] ?? [];
        return array_map(static fn(array $item) => Group::fromArray($item), $items);
    }

    protected function getMatrix(): array
    {
        $matrix = [];
        $cmsUsers = $this->findUsers(self::USER_ID_PREFIX_CMS);

        foreach ($cmsUsers as $cmsUser) {
            $analogNumber = $cmsUser->analogNumber;

            $hundreds = intdiv($analogNumber, 100);
            $tens = intdiv($analogNumber % 100, 10);
            $units = $analogNumber % 10;

            $matrix[$hundreds . $tens . $units] = [
                'hundreds' => $hundreds,
                'tens' => $tens,
                'units' => $units,
                'apartment' => self::extractFlatNumber($cmsUser),
            ];
        }

        return $matrix;
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
     * Uploads and sets the access-granted sound.
     *
     * @param string|null $pathToSound Path to the sound file to upload. If null, a default path is used.
     * @return void
     */
    protected function setAccessGrantedSound(?string $pathToSound = null): void
    {
        if ($pathToSound === null) {
            $pathToSound = __DIR__ . '/assets/sounds/access_granted.wav';
        }

        if (!file_exists($pathToSound) || !is_file($pathToSound)) {
            return;
        }

        $this->apiCall(
            '/filetool/import?' . http_build_query(['destFile' => 'VoicePrompt', 'index' => 0]),
            'POST',
            ['file' => new CURLFile($pathToSound)],
        );
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
     * Enables or disables the directory view on the intercom screen.
     *
     * @param bool $enabled Whether the directory content should be visible or hidden.
     * @return void
     */
    protected function setDirectoryEnabled(bool $enabled = true): void
    {
        $this->apiCall('', 'POST', [
            'target' => 'config',
            'action' => 'set',
            'data' => ['Config.DoorSetting.GENERAL.ContactViewShowChild' => $enabled ? '3' : '4'],
        ]);
    }

    /**
     * Sets the logic levels that trigger the inputs.
     *
     * @param bool $onHighA Whether input A should trigger on high level.
     * @param bool $onHighB Whether input B should trigger on high level.
     * @param bool $onHighC Whether input C should trigger on high level.
     * @param bool $onHighD Whether input D should trigger on high level.
     * @return void
     */
    protected function setInputTriggerLevel(
        bool $onHighA = false,
        bool $onHighB = false,
        bool $onHighC = false,
        bool $onHighD = false,
    ): void
    {
        $this->apiCall('', 'POST', [
            'target' => 'input',
            'action' => 'set',
            'data' => [
                'Config.DoorSetting.INPUT.InputTrigger' => $onHighA ? '1' : '0',
                'Config.DoorSetting.INPUT.InputBTrigger' => $onHighB ? '1' : '0',
                'Config.DoorSetting.INPUT.InputCTrigger' => $onHighC ? '1' : '0',
                'Config.DoorSetting.INPUT.InputDTrigger' => $onHighD ? '1' : '0',
            ],
        ]);
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
        $this->executeChunkOperation('user', 'set', $users, fn(User $user) => $user->toArray());
    }
}
