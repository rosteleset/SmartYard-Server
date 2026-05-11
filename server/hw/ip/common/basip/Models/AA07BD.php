<?php

namespace hw\ip\common\basip\Models;

/**
 * Trait providing common functionality related to BasIP AA-07BD devices.
 */
trait AA07BD
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

    protected function setDevicePassword(string $password): void
    {
        $params = [
            'oldPassword' => $this->defaultPassword,
            'newPassword' => $password,
        ];

        $this->client->request('/v1/security/password/web/admin?' . http_build_query($params), 'POST');
    }
}
