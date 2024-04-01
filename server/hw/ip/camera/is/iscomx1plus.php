<?php

namespace hw\ip\camera\is;

/**
 * Class representing a Sokol ISCom X1 Plus (rev.5) intercom.
 */
class iscomx1plus extends is
{

    use \hw\ip\common\is\iscomx1plus;

    public function setOsdText(string $text = '')
    {
        $this->setOsdTextWithPos($text, 1046);
    }
}
