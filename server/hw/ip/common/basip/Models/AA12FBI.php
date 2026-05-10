<?php

namespace hw\ip\common\basip\Models;

/**
 * Trait providing common functionality related to BasIP AA-12FBI devices.
 */
trait AA12FBI
{
    protected static function getTimezoneParamName(): string
    {
        return 'timezone';
    }

    public function configureEventServer(string $url): void
    {
        ['host' => $server, 'port' => $port] = parse_url_ext($url);

        $this->client->call('/v1/syslog/settings', 'POST', [
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
        $settings = $this->client->call('/v1/syslog/settings')['server'];
        return 'http://' . $settings['server'] . ':' . $settings['port'];
    }

    protected function setDevicePassword(string $password): void
    {
        $this->client->call('/v1/security/password/admin?', 'POST', [
            'old_password' => $this->defaultPassword,
            'new_password' => $password,
        ]);
    }
}
