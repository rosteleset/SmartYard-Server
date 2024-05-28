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
        $this->apiCall('/v2/camera/osd', 'PUT', [
            [
                'size' => 1,
                'text' => '',
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
            [
                'size' => 1,
                'text' => $text,
                'color' => '0xFFFFFF',
                'date' => [
                    'enable' => false,
                    'format' => '%d-%m-%Y',
                ],
                'time' => [
                    'enable' => false,
                    'format' => '%H:%M:%S',
                ],
                'position' => [
                    'x' => 2,
                    'y' => 702,
                ],
                'background' => [
                    'enable' => true,
                    'color' => '0x000000',
                ],
            ],
        ]);
    }
}
