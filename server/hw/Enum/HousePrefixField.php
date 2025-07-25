<?php

namespace hw\Enum;

/**
 * Enum representing supported fields of a HousePrefix.
 * Used to define which HousePrefix fields a device supports.
 */
enum HousePrefixField
{
    case Address;
    case FirstFlat;
    case LastFlat;
}
