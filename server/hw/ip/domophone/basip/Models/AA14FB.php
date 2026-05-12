<?php

namespace hw\ip\domophone\basip\Models;

use hw\Interface\FreePassInterface;
use hw\ip\domophone\basip\{
    Basip,
    Traits\FreePassTrait,
    Traits\HttpsConfigTrait,
};

/**
 * Represents a BasIP AA-14FB intercom.
 */
class AA14FB extends Basip implements FreePassInterface
{
    use \hw\ip\common\basip\Models\AA07FB;
    use FreePassTrait;
    use HttpsConfigTrait;

    public function prepare(): void
    {
        $this->setHttpsEnabled(false);
        parent::prepare();
    }
}
