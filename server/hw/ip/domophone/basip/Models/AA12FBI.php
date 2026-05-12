<?php

namespace hw\ip\domophone\basip\Models;

use hw\Interface\FreePassInterface;
use hw\ip\domophone\basip\{
    Basip,
    Traits\FreePassTrait,
    Traits\HttpsConfigTrait,
};

/**
 * Represents a BasIP AA-12FBI intercom.
 */
class AA12FBI extends Basip implements FreePassInterface
{
    use \hw\ip\common\basip\Models\AA12FBI;
    use FreePassTrait;
    use HttpsConfigTrait;

    public function prepare(): void
    {
        $this->setHttpsEnabled(false);
        parent::prepare();
    }
}
