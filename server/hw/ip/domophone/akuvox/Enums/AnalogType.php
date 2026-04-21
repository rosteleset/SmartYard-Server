<?php

namespace hw\ip\domophone\akuvox\Enums;

/**
 * Represents the type of analog adapter used in the Akuvox intercom.
 */
enum AnalogType: string
{
    case None = '0';
    case Vizit = '1';
    case Cyfral = '2';
    case Eltis = '3';
    case Metakom = '4';
    case Laskomex = '5';
    case Akuvox = '9';
}
