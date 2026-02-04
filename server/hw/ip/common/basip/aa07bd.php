<?php

namespace hw\ip\common\basip;

/**
 * Trait providing common functionality related to BasIP AA-07BD devices.
 */
trait aa07bd
{
    protected static function getTimezoneParamName(): string
    {
        return 'current_timezone';
    }

    public function configureEventServer(string $url): void
    {
        // Empty implementation
    }

    public function transformDbConfig(array $dbConfig): array
    {
        $dbConfig['eventServer'] = '';
        return $dbConfig;
    }

    protected function getEventServer(): string
    {
        return '';
    }
}
