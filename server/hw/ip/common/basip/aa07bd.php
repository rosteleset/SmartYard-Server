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

    protected static function getAdminPasswordEndpoint(): string
    {
        return '/v1/security/password/web/admin';
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
