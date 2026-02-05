<?php

namespace hw\ip\domophone\basip;

use hw\Interface\FreePassInterface;
use hw\ip\common\basip\HttpClient\BasicHttpClient;

/**
 * Represents a BasIP AA-07FB intercom.
 */
class aa07fb extends basip implements FreePassInterface
{
    use \hw\ip\common\basip\aa07fb;

    public function __construct(string $url, string $password, bool $firstTime = false)
    {
        $this->client = new BasicHttpClient($url, $password);
        parent::__construct($url, $password, $firstTime);
    }

    protected static function getIdentifierValidDefaultValue(): array
    {
        return [
            'passes' => [
                'is_permanent' => true,
                'max_passes' => null,
                'time' => [
                    'from' => null,
                    'is_permanent' => true,
                    'to' => null,
                ],
            ],
        ];
    }

    public function isFreePassEnabled(): bool
    {
        return $this->client->call('/v1/access/freeaccess')['enable'] ?? true;
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
