<?php

namespace hw\ip\domophone\ufanet;

/**
 * Represents the type of key used in the Ufanet intercom.
 */
enum KeyType: int
{
    case Unspecified = 0;
    case RfidPersonal = 1;
    case RfidCommon = 2;
    case CodePersonal = 3;
    case CodeCommon = 4;
    case Ble = 5;
}
