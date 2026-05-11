<?php

namespace hw\ip\domophone\basip\Models;

use hw\Interface\FreePassInterface;
use hw\ip\domophone\basip\{
    Basip,
    Traits\FreePassTrait,
    Traits\IdentifierValidTrait,
};

/**
 * Represents a BasIP AA-07FB intercom.
 */
class AA07FB extends Basip implements FreePassInterface
{
    use \hw\ip\common\basip\Models\AA07FB;
    use FreePassTrait;
    use IdentifierValidTrait;
}
