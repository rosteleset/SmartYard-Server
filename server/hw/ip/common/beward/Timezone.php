<?php

namespace hw\ip\common\beward;

use DateTimeImmutable;
use DateTimeZone;
use Exception;

/**
 * Class representing timezones of Beward devices.
 */
final class Timezone
{
    /** @var int Default timezone ID. */
    private const TZ_ID_DEFAULT = 21;

    /** @var array<string, int> Map of UTC offset relative to the device's internal timezone ID. */
    private const TZ_ID_MAP = [
        '-12' => 0,
        '-11' => 1,
        '-10' => 2,
        '-9' => 3,
        '-8' => 4,
        '-7' => 5,
        '-6' => 6,
        '-5' => 7,
        '-4' => 9,
        '-3.5' => 10,
        '-3' => 11,
        '-2' => 12,
        '-1' => 13,
        '0' => 14,
        '1' => 15,
        '2' => 19,
        '3' => 21,
        '3.5' => 22,
        '4' => 23,
        '4.5' => 24,
        '5' => 25,
        '5.5' => 26,
        '6' => 27,
        '7' => 28,
        '8' => 29,
        '9' => 30,
        '9.5' => 31,
        '10' => 32,
        '11' => 33,
        '12' => 34,
    ];

    /**
     * Returns the ID corresponding to the given TZ identifier.
     *
     * @param string $timezone TZ identifier.
     * @return int ID associated with the TZ identifier.
     */
    public static function getIdByTimezone(string $timezone): int
    {
        try {
            $now = new DateTimeImmutable('now', new DateTimeZone($timezone));

            /*
             * IANA timezone database that provides PHP's timezone support uses POSIX style signs,
             * which results in the Etc/GMT+n and Etc/GMT-n time zones being reversed from common usage.
             * https://www.php.net/manual/en/timezones.others.php
             */
            $offset = (string)($now->getOffset() / 3600 * (str_contains($timezone, 'GMT') ? -1 : 1));

            return self::TZ_ID_MAP[$offset] ?? self::TZ_ID_DEFAULT;
        } catch (Exception) {
            return self::TZ_ID_DEFAULT;
        }
    }
}
