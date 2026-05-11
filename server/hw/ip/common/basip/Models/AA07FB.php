<?php

namespace hw\ip\common\basip\Models;

/**
 * Trait providing common functionality related to BasIP AA-07FB devices.
 */
trait AA07FB
{
    protected static function getTimezoneParamName(): string
    {
        return 'timezone';
    }

    public function configureEventServer(string $url): void
    {
        ['host' => $server, 'port' => $port] = parse_url_ext($url);

        $this->client->request('/v1/syslog/settings', 'POST', [
            'enabled' => $url !== '',
            'server' => [
                'port' => $port,
                'server' => $server,
                'severity' => 6,
            ],
            'tag' => '',
        ]);
    }

    protected function getEventServer(): string
    {
        $settings = $this->client->request('/v1/syslog/settings')['server'];
        return 'http://' . $settings['server'] . ':' . $settings['port'];
    }

    protected function setDevicePassword(string $password): void
    {
        $params = [
            'oldPassword' => $this->defaultPassword,
            'newPassword' => $password,
        ];

        $this->client->request('/v1/security/password/admin?' . http_build_query($params), 'POST');
    }
}
