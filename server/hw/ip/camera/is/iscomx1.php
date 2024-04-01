<?php

namespace hw\ip\camera\is;

/**
 * Class representing a Sokol ISCom X1 (rev.2) camera.
 */
class iscomx1 extends is
{

    use \hw\ip\common\is\iscomx1;

    public function setOsdText(string $text = '')
    {
        $this->setOsdTextWithPos($text, 693);
    }
}
