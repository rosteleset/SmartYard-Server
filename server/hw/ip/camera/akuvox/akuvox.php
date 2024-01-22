<?php

namespace hw\ip\camera\akuvox;

use hw\ip\camera\camera;

/**
 * Class representing an Akuvox camera.
 */
class akuvox extends camera
{

    use \hw\ip\common\akuvox\akuvox;

    public function configureMotionDetection(
        int $left = 0,
        int $top = 0,
        int $width = 100,
        int $height = 100,
        int $sensitivity = 3
    )
    {
        $this->setConfigParams([
            'Config.DoorSetting.MOTION_DETECT.Enable' => $sensitivity === 0 ? '0' : '2', // 2 - video detection
            'Config.DoorSetting.MOTION_DETECT.Interval' => '1', // Motion duration
            'Config.DoorSetting.MOTION_DETECT.TFTPEnable' => '0',
            'Config.DoorSetting.MOTION_DETECT.FTPEnable' => '1',
            'Config.DoorSetting.MOTION_DETECT.SendType' => '0',
            'Config.DoorSetting.MOTION_DETECT.DetectAccuracy' => "$sensitivity",
            'Config.DoorSetting.MOTION_DETECT.AreaStartWidth' => "$left",
            'Config.DoorSetting.MOTION_DETECT.AreaEndWidth' => "$width",
            'Config.DoorSetting.MOTION_DETECT.AreaStartHeight' => "$top",
            'Config.DoorSetting.MOTION_DETECT.AreaEndHeight' => "$height",
        ]);
    }

    public function getCamshot(): string
    {
        $host = parse_url($this->url)['host'];
        return file_get_contents("http://$this->login:$this->password@$host:8080/picture.jpg");
    }

    public function setOsdText(string $text = '') // Latin only
    {
        $this->setConfigParams([
            'Config.DoorSetting.RTSP.OSDEnable' => $text ? '1' : '0',
            'Config.DoorSetting.RTSP.OSDText' => $text,
            'Config.DoorSetting.RTSP.OSDColor' => '3', // green color for high contrast
        ]);
    }

    public function transformDbConfig(array $dbConfig): array
    {
        return $dbConfig;
    }

    protected function getMotionDetectionConfig(): array
    {
        [$left, $width, $top, $height, $sensitivity] = $this->getConfigParams([
            'Config.DoorSetting.MOTION_DETECT.AreaStartWidth',
            'Config.DoorSetting.MOTION_DETECT.AreaEndWidth',
            'Config.DoorSetting.MOTION_DETECT.AreaStartHeight',
            'Config.DoorSetting.MOTION_DETECT.AreaEndHeight',
            // 'Config.DoorSetting.MOTION_DETECT.DetectAccuracy',
        ]);

        return [
            'left' => $left,
            'top' => $top,
            'width' => $width,
            'height' => $height,
            // 'sensitivity' => $sensitivity,
        ];
    }

    protected function getOsdText(): string
    {
        return $this->getConfigParams(['Config.DoorSetting.RTSP.OSDText'])[0];
    }
}
