<?php

namespace hw\ip\camera\is;

/**
 * Class representing a Sokol ISCom X1 Plus (rev.5) camera.
 */
class iscomx1plus extends is
{

    use \hw\ip\common\is\iscomx1plus;

    public function setOsdText(string $text = '')
    {
        $this->apiCall('/v2/camera/osd', 'PUT', [
            [
                'size' => 1,
                'text' => $text,
                'color' => '0xFFFFFF',
                'date' => [
                    'enable' => true,
                    'format' => '%d-%m-%Y',
                ],
                'time' => [
                    'enable' => true,
                    'format' => '%H:%M:%S',
                ],
                'position' => [
                    'x' => 2,
                    'y' => 2,
                ],
                'background' => [
                    'enable' => true,
                    'color' => '0x000000',
                ],
            ],
        ]);
    }
}
