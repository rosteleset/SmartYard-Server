<?php

namespace hw\ip\domophone\rubetek\Enums;

/**
 * Represents Rubetek reader-to-relay access binding codes.
 */
enum DoorAccess: int
{
    case Relay1Internal = 1;
    case Relay2Internal = 2;
    case Relay3Internal = 3;
    case Relay1External = 4;
    case Relay2External = 5;
    case Relay3External = 6;
}
