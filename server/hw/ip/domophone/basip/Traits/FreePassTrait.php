<?php

namespace hw\ip\domophone\basip\Traits;

use hw\ip\common\basip\HttpClient\HttpClientInterface;

/**
 * Provides functionality to enable/disable and check free access mode on BasIP intercom devices.
 *
 * @property HttpClientInterface $client
 */
trait FreePassTrait
{
    public function isFreePassEnabled(): bool
    {
        return $this->client->call('/v1/access/freeaccess')['enable'];
    }

    public function setFreePassEnabled(bool $enabled): void
    {
        $days = array_map(fn($day) => [
            'lock' => 'all',
            'enable' => true,
            'time_from' => 0,
            'time_to' => 86340,
            'day' => $day,
        ], ['MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT', 'SUN']);

        $this->client->call('/v1/access/freeaccess', 'POST', [
            'enable' => $enabled,
            'days' => $days,
        ]);
    }
}
