<?php

namespace hw\ip\domophone\ufanet;

/**
 * Represents an Ufanet Secret Mini intercom.
 */
class secretMini extends ufanet
{
    public function configureApartment(
        int   $apartment,
        int   $code = 0,
        array $sipNumbers = [],
        bool  $cmsEnabled = true,
        array $cmsLevels = [],
    ): void
    {
        $this->loadDialplans();

        $this->dialplans[$apartment] = [
            'sip_number' => (string)($sipNumbers[0] ?? ''),
            'sip' => true,
        ];
    }

    public function configureMatrix(array $matrix): void
    {
        // Empty implementation
    }

    public function deleteApartment(int $apartment = 0): void
    {
        $this->loadDialplans();
        unset($this->dialplans[$apartment]);
    }

    public function getLineDiagnostics(int $apartment): string|int|float
    {
        // Empty implementation
        return 0;
    }

    public function openLock(int $lockNumber = 0): void
    {
        $this->apiCall('/api/v1/doors/1/open', 'POST', null, 3);
    }

    public function setCmsModel(string $model = ''): void
    {
        // Empty implementation
    }

    public function setConciergeNumber(int $sipNumber): void
    {
        // Empty implementation
    }

    public function setSosNumber(int $sipNumber): void
    {
        // Empty implementation
    }

    public function transformDbConfig(array $dbConfig): array
    {
        $dbConfig = parent::transformDbConfig($dbConfig);

        $dbConfig['cmsModel'] = '';
        $dbConfig['matrix'] = [];

        foreach ($dbConfig['apartments'] as &$apartment) {
            $apartment['code'] = 0;
            $apartment['cmsEnabled'] = false;
        }

        return $dbConfig;
    }

    protected function getApartments(): array
    {
        $this->loadDialplans();
        $flats = [];

        foreach ($this->dialplans as $flatNumber => $dialplan) {
            if ($dialplan['sip'] === false || $flatNumber === 'FRSI') {
                continue;
            }

            $flats[$flatNumber] = [
                'apartment' => $flatNumber,
                'code' => 0,
                'sipNumbers' => [$dialplan['sip_number']],
                'cmsEnabled' => false,
                'cmsLevels' => [],
            ];
        }

        return $flats;
    }

    protected function getCmsModel(): string
    {
        // Empty implementation
        return '';
    }

    protected function getMatrix(): array
    {
        // Empty implementation
        return [];
    }
}
