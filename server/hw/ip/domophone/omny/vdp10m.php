<?php

namespace hw\ip\domophone\omny;

use hw\Interface\LanguageInterface;
use hw\ip\domophone\akuvox\{
    akuvox,
    Entities\Dialplan,
    Entities\User,
};

class vdp10m extends akuvox implements LanguageInterface
{
    protected const ITEMS_CHUNK_SIZE = 1000;
    private const USER_ID_PREFIX_FLAT = 'FLAT';

    /** @var array<int|string, User>|null */
    protected ?array $usersToAdd = null;

    /** @var array<int|string, User>|null */
    protected ?array $usersToDelete = null;

    /** @var array<int|string, User>|null */
    protected ?array $usersToUpdate = null;

    /** @var array<int|string, Dialplan>|null */
    private ?array $dialplans = null;

    /** @var array<int|string, User>|null */
    private ?array $users = null;

    /** @var string[]|null */
    private ?array $rfidsToAdd = null;

    protected static function getMaxUsers(): int
    {
        return 5000; // Found by Codex
    }

    public function addRfids(array $rfids): void
    {
        if (!$rfids) {
            return;
        }

        $neededSlots = ceil((count($this->rfidsToAdd ?? []) + count($rfids)) / self::MAX_RFIDS_PER_USER);
        if (count($this->getUsers()) + $neededSlots > static::getMaxUsers()) {
            $rfids = array_merge($this->getRfids(), $rfids);
            $this->deleteRfid();
        }

        $this->pushRfids($rfids);
    }

    public function configureApartment(
        int   $apartment,
        int   $code = 0,
        array $sipNumbers = [],
        bool  $cmsEnabled = true,
        array $cmsLevels = [],
    ): void
    {
        $sipNumbers = array_values(array_filter(
            array_map('strval', $sipNumbers),
            static fn(string $number) => $number !== '',
        ));

        $dialplan = $this->findDialplan($apartment);

        if (!$sipNumbers) {
            if ($dialplan !== null) {
                $this->deleteDialplan($dialplan);
            }
        } else {
            $changed = false;
            if ($dialplan === null) {
                $dialplan = new Dialplan($apartment);
            }

            foreach (['replace1', 'replace2', 'replace3', 'replace4', 'replace5'] as $index => $property) {
                $number = $sipNumbers[$index] ?? '';
                if ($dialplan->$property !== $number) {
                    $dialplan->$property = $number;
                    $changed = true;
                }
            }

            if ($dialplan->id === '-1') {
                $this->addDialplan($dialplan);
            } elseif ($changed) {
                $this->updateDialplan($dialplan);
            }
        }

        $this->setFlatCode($apartment, $code);
    }

    public function deleteApartment(int $apartment = 0): void
    {
        if ($apartment === 0) {
            $this->clearDialplans();
            $this->clearApartmentUsers();
            return;
        }

        $dialplan = $this->findDialplan($apartment);

        if ($dialplan !== null) {
            $this->deleteDialplan($dialplan);
        }

        $user = $this->findFlatUser($apartment);
        if ($user !== null) {
            $this->deleteUser($user);
        }
    }

    public function deleteRfid(string $code = ''): void
    {
        if ($code === '') {
            $this->rfidsToAdd = null;
            foreach ($this->getUsers() as $user) {
                if ($user->cardCode !== '') {
                    $this->deleteUser($user);
                }
            }

            return;
        }

        $normalizedCode = self::getNormalizedRfid($code);

        if ($this->rfidsToAdd !== null) {
            $this->rfidsToAdd = array_filter(
                $this->rfidsToAdd,
                static fn(string $rfid) => self::getNormalizedRfid($rfid) !== $normalizedCode,
            );
        }

        foreach ($this->getUsers() as $user) {
            $codes = array_filter(explode(';', $user->cardCode));
            $index = array_search($normalizedCode, $codes, true);

            if ($index === false) {
                continue;
            }

            unset($codes[$index]);

            $this->deleteUser($user);
            $this->pushRfids($codes);

            return;
        }
    }

    public function getRfids(): array
    {
        $rfids = [];

        foreach ($this->rfidsToAdd ?? [] as $code) {
            $code = str_pad(self::getNormalizedRfid($code), 14, '0', STR_PAD_LEFT);
            $rfids[$code] = $code;
        }

        foreach ($this->getUsers() as $user) {
            foreach (array_filter(explode(';', $user->cardCode)) as $code) {
                $code = str_pad($code, 14, '0', STR_PAD_LEFT);
                $rfids[$code] = $code;
            }
        }

        return $rfids;
    }

    public function prepare(): void
    {
        parent::prepare();
        $this->setRelayInversion(true, true);
    }

    public function setConciergeNumber(int $sipNumber): void
    {
        $this->setConfigParams([
            'Config.Programable.SOFTKEY01.LocalParam1' => $sipNumber . str_repeat(';', 7),
        ]);
    }

    public function setLanguage(string $language): void
    {
        $this->setConfigParams([
            'Config.Settings.LANGUAGE.WebLang' => $language === 'ru' ? '3' : '0',
        ]);
    }

    public function syncData(): void
    {
        if ($this->usersToDelete !== null) {
            $this->deleteUsers($this->usersToDelete);
        }

        if ($this->usersToUpdate !== null) {
            $this->updateUsers($this->usersToUpdate);
        }

        if ($this->usersToAdd !== null) {
            $this->addUsers($this->usersToAdd);
        }

        if ($this->rfidsToAdd) {
            parent::pushRfids($this->rfidsToAdd);
        }

        $this->dialplans = null;
        $this->users = null;
        $this->rfidsToAdd = null;
        $this->usersToAdd = null;
        $this->usersToDelete = null;
        $this->usersToUpdate = null;
    }

    public function transformDbConfig(array $dbConfig): array
    {
        unset($dbConfig['apartments'][9999]);

        foreach ($dbConfig['apartments'] as &$apartment) {
            $apartment['cmsEnabled'] = false;
        }

        return $dbConfig;
    }

    /** @param User[] $users */
    protected function addUsers(array $users): void
    {
        $this->executeChunkOperation('user', 'add', $users, static fn(User $user) => $user->toArray());
    }

    /** @param User[] $users */
    protected function deleteUsers(array $users): void
    {
        $this->executeChunkOperation('user', 'del', $users, static fn(User $user) => ['ID' => $user->id]);
    }

    /** @param User[] $entities */
    protected function executeChunkOperation(string $target, string $action, array $entities, callable $mapper): void
    {
        foreach (array_chunk($entities, self::ITEMS_CHUNK_SIZE) as $chunk) {
            $this->apiCall('', 'POST', [
                'target' => $target,
                'action' => $action,
                'data' => ['item' => array_map($mapper, $chunk)],
            ]);

            sleep(1);
        }
    }

    protected function findDialplan(string $prefix): ?Dialplan
    {
        return $this->getDialplans()[$prefix] ?? null;
    }

    protected function getApartments(): array
    {
        $apartments = [];

        foreach ($this->getDialplans() as $dialplan) {
            $prefix = $dialplan->prefix;

            if (!ctype_digit($prefix) || (int)$prefix <= 0) {
                continue;
            }

            $sipNumbers = array_values(array_filter([
                $dialplan->replace1,
                $dialplan->replace2,
                $dialplan->replace3,
                $dialplan->replace4,
                $dialplan->replace5,
            ], static fn(string $number) => $number !== ''));

            $apartment = (int)$prefix;
            $apartments[$apartment] = [
                'apartment' => $apartment,
                'code' => 0,
                'sipNumbers' => $sipNumbers,
                'cmsEnabled' => false,
                'cmsLevels' => [],
            ];
        }

        foreach ($this->getUsers() as $user) {
            $apartment = $this->getApartmentFromUser($user);

            if ($apartment === null) {
                continue;
            }

            $apartments[$apartment] ??= [
                'apartment' => $apartment,
                'code' => 0,
                'sipNumbers' => [],
                'cmsEnabled' => false,
                'cmsLevels' => [],
            ];

            $apartments[$apartment]['code'] = (int)$user->privatePin;
        }

        return $apartments;
    }

    /** @return array<int|string, Dialplan> */
    protected function getDialplans(): array
    {
        if ($this->dialplans === null) {
            $response = $this->apiCall('/dialreplace/get');

            $this->dialplans = [];
            foreach ($response['data']['item'] ?? [] as $item) {
                $dialplan = Dialplan::fromArray($item);
                $this->dialplans[$dialplan->prefix] = $dialplan;
            }
        }

        return $this->dialplans;
    }

    /** @return array<int|string, User> */
    protected function getUsers(): array
    {
        if ($this->users === null) {
            $response = $this->apiCall('/user/get');

            $this->users = [];
            foreach ($response['data']['item'] ?? [] as $item) {
                $scheduleRelay = rtrim($item['ScheduleRelay'] ?? '', ';');
                $item['Schedule-Relay'] = $scheduleRelay === '' ? '' : $scheduleRelay . ';';
                $user = User::fromArray($item);
                $this->users[$user->id] = $user;
            }
        }

        return $this->users;
    }

    protected function pushRfids(array $rfids): void
    {
        $this->rfidsToAdd = array_merge($this->rfidsToAdd ?? [], $rfids);
    }

    /** @param User[] $users */
    protected function updateUsers(array $users): void
    {
        $this->executeChunkOperation('user', 'set', $users, static fn(User $user) => $user->toArray());
    }

    private function addDialplan(Dialplan $dialplan): void
    {
        $response = $this->apiCall('', 'POST', [
            'target' => 'dialreplace',
            'action' => 'add',
            'data' => ['item' => [$dialplan->toArray()]],
        ]);

        $dialplan->id = $response['data']['item'][0]['ID'] ?? '-1';
        $this->dialplans[$dialplan->prefix] = $dialplan;
    }

    private function addUser(User $user): void
    {
        $this->usersToAdd[$user->userId] = $user;
        $this->users[$user->userId] = $user;
    }

    private function clearApartmentUsers(): void
    {
        foreach ($this->getUsers() as $user) {
            if ($this->getApartmentFromUser($user) !== null) {
                $this->deleteUser($user);
            }
        }
    }

    private function clearDialplans(): void
    {
        $this->apiCall('/dialreplace/clear');
        $this->dialplans = [];
    }

    private function deleteDialplan(Dialplan $dialplan): void
    {
        $this->apiCall('', 'POST', [
            'target' => 'dialreplace',
            'action' => 'del',
            'data' => ['item' => [['ID' => $dialplan->id]]],
        ]);

        unset($this->dialplans[$dialplan->prefix]);
    }

    private function deleteUser(User $user): void
    {
        if ($user->id === '-1') {
            unset($this->usersToAdd[$user->userId]);
            unset($this->users[$user->userId]);
        } else {
            $this->usersToDelete[$user->id] = $user;
            unset($this->usersToUpdate[$user->id]);
            unset($this->users[$user->id]);
        }
    }

    private function findFlatUser(int $apartment): ?User
    {
        $userId = self::USER_ID_PREFIX_FLAT . 'x' . $apartment;

        foreach ($this->getUsers() as $user) {
            if ($user->userId === $userId) {
                return $user;
            }
        }

        return null;
    }

    private function getApartmentFromUser(User $user): ?int
    {
        if (!preg_match('/^' . self::USER_ID_PREFIX_FLAT . 'x([1-9]\\d*)$/', $user->userId, $matches)) {
            return null;
        }

        return (int)$matches[1];
    }

    private function setFlatCode(int $apartment, int $code): void
    {
        $user = $this->findFlatUser($apartment);

        if ($code === 0) {
            if ($user !== null) {
                $this->deleteUser($user);
            }

            return;
        }

        if ($user === null) {
            $user = new User(self::USER_ID_PREFIX_FLAT . 'x' . $apartment);
            $user->name = self::USER_ID_PREFIX_FLAT;
            $user->privatePin = $code;
            $this->addUser($user);
            return;
        }

        if ($user->privatePin != $code) {
            $user->privatePin = $code;
            $this->updateUser($user);
        }
    }

    private function updateDialplan(Dialplan $dialplan): void
    {
        $this->apiCall('', 'POST', [
            'target' => 'dialreplace',
            'action' => 'set',
            'data' => ['item' => [$dialplan->toArray()]],
        ]);

        $this->dialplans[$dialplan->prefix] = $dialplan;
    }

    private function updateUser(User $user): void
    {
        if ($user->id !== '-1') {
            $this->usersToUpdate[$user->id] = $user;
        }
    }
}
