<?php

namespace hw\ip\domophone\rubetek\Enums;

/**
 * Represents the type of display content used in the Rubetek intercom.
 */
enum DisplayType: int
{
    case DefaultText = 0;
    case ThreeLineText = 6;
    case ScrollingText = 1;
    case CyclicText = 5;
    case DefaultImage = 2;
    case CustomImage = 3;
    case CurrentTime = 4;
}
