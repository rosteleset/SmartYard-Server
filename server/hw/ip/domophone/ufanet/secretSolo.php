<?php

namespace hw\ip\domophone\ufanet;

/**
 * Represents an Ufanet Secret Solo intercom.
 */
class secretSolo extends ufanet
{
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

    public function configureMatrix(array $matrix): void
    {
        // Empty implementation
    }

    public function deleteApartment(int $apartment = 0): void
    {
        // Empty implementation
    }

    public function getLineDiagnostics(int $apartment): string|int|float
    {
        // Empty implementation
        return 0;
    }

    public function openLock(int $lockNumber = 0): void
    {
        $this->openLockByNumber($lockNumber);
    }

    public function setCmsModel(string $model = ''): void
    {
        // Empty implementation
    }

    public function setConciergeNumber(int $sipNumber): void
    {
        $this->loadDialplans();

        $this->dialplans['FRSI'] = [
            'sip_number' => "$sipNumber",
            'sip' => true,
            'map' => 0,
        ];
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
        $dbConfig['apartments'] = [];

        return $dbConfig;
    }

    protected function getApartments(): array
    {
        // Empty implementation
        return [];
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
