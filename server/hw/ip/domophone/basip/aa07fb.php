<?php

namespace hw\ip\domophone\basip;

use hw\ip\common\basip\HttpClient\BasicHttpClient;

/**
 * Represents a BasIP AA-07FB intercom.
 */
class aa07fb extends basip
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
}
