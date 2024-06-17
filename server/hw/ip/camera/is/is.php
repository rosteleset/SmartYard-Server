<?php

namespace hw\ip\camera\is;

use hw\ip\camera\camera;

/**
 * Class representing an Intersvyaz (IS) camera.
 */
class is extends camera
{

    use \hw\ip\common\is\is;

    public function configureMotionDetection(
        int $left = 0,
        int $top = 0,
        int $width = 0,
        int $height = 0,
        int $sensitivity = 0
    )
    {
        $this->apiCall('/camera/md', 'PUT', [
            'md_enable' => $left || $top || $width || $height,
            'md_frame_shift' => 1,
            'md_area_thr' => 100000, // For people at close range
            'md_rect_color' => '0xFF0000',
            'md_frame_int' => 30,
            'md_rects_enable' => false,
            'md_logs_enable' => true,
            'md_send_snapshot_enable' => false,
            'md_send_snapshot_interval' => 1,
            'snap_send_url' => '',
        ]);
    }

    public function getCamshot(): string
    {
        return $this->apiCall('/camera/snapshot', 'GET', [], 3);
    }

    public function setOsdText(string $text = '')
    {
        $hwVer = floor($this->getSysinfo()['HardwareVersion'] ?? 0);

        $firstStringParams = [
            'size' => 1,
            'text' => $hwVer == 5 ? $text : '',
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
        ];

        $secondStringParams = $hwVer == 5 ? [] : [
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
        ];

        $this->apiCall('/v2/camera/osd', 'PUT', [$firstStringParams, $secondStringParams]);
    }

    public function transformDbConfig(array $dbConfig): array
    {
        $md = $dbConfig['motionDetection'];

        $md_enable = ($md['left'] || $md['top'] || $md['width'] || $md['height']) ? 1 : 0;

        $dbConfig['motionDetection'] = [
            'left' => $md_enable,
            'top' => $md_enable,
            'width' => $md_enable,
            'height' => $md_enable,
        ];

        return $dbConfig;
    }

    protected function getMotionDetectionConfig(): array
    {
        ['md_enable' => $mdEnabled] = $this->apiCall('/camera/md');

        return [
            'left' => ($mdEnabled) ? 1 : 0,
            'top' => ($mdEnabled) ? 1 : 0,
            'width' => ($mdEnabled) ? 1 : 0,
            'height' => ($mdEnabled) ? 1 : 0,
        ];
    }

    protected function getOsdText(): string
    {
        $osdParams = $this->apiCall('/v2/camera/osd');
        return $osdParams[0]['text'] ?: $osdParams[1]['text'];
    }
}
