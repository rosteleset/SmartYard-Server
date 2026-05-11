<?php

namespace hw\ip\domophone\basip\Models;

use hw\ip\domophone\basip\{
    Basip,
    Traits\HttpsConfigTrait,
    Traits\IdentifierValidTrait,
};

/**
 * Represents a BasIP AV-08FB intercom.
 */
class AV08FB extends Basip
{
    use \hw\ip\common\basip\Models\AA07FB;
    use HttpsConfigTrait;
    use IdentifierValidTrait;

    public function prepare(): void
    {
        $this->setHttpsEnabled(false);
        parent::prepare();
    }
}
