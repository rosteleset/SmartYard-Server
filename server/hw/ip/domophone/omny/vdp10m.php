<?php

namespace hw\ip\domophone\omny;

use hw\ip\domophone\akuvox\{
    akuvox,
    Entities\Dialplan,
};

class vdp10m extends akuvox
{
    /** @var array<int|string, Dialplan>|null */
    private ?array $dialplans = null;

    protected static function getMaxUsers(): int
    {
        return 5000; // Found by Codex
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

            return;
        }

        if ($dialplan === null) {
            $dialplan = new Dialplan($apartment);
        }

        foreach (['replace1', 'replace2', 'replace3', 'replace4', 'replace5'] as $index => $property) {
            $dialplan->$property = $sipNumbers[$index] ?? '';
        }

        if ($dialplan->id === '-1') {
            $this->addDialplan($dialplan);
        } else {
            $this->updateDialplan($dialplan);
        }
    }

    public function deleteApartment(int $apartment = 0): void
    {
        if ($apartment === 0) {
            $this->clearDialplans();
            return;
        }

        $dialplan = $this->findDialplan($apartment);

        if ($dialplan !== null) {
            $this->deleteDialplan($dialplan);
        }
    }

    public function setConciergeNumber(int $sipNumber): void
    {
        $this->setConfigParams([
            'Config.Programable.SOFTKEY01.LocalParam1' => $sipNumber . str_repeat(';', 7),
        ]);
    }

    public function syncData(): void
    {
        $this->dialplans = null;
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

    private function updateDialplan(Dialplan $dialplan): void
    {
        $this->apiCall('', 'POST', [
            'target' => 'dialreplace',
            'action' => 'set',
            'data' => ['item' => [$dialplan->toArray()]],
        ]);

        $this->dialplans[$dialplan->prefix] = $dialplan;
    }
}
