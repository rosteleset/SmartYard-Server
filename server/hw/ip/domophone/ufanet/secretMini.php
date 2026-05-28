<?php

namespace hw\ip\domophone\ufanet;

/**
 * Represents an Ufanet Secret Mini intercom.
 */
class secretMini extends ufanet
{
    public function configureMatrix(array $matrix): void
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

        return $dbConfig;
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
