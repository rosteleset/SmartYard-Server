<?php

namespace hw\ip\camera\qtech;

use hw\ip\camera\camera;

/**
 * Class representing a Qtech camera.
 */
class qtech extends camera
{

    use \hw\ip\common\qtech\qtech;

    public function configureMotionDetection(
        int $left = 0,
        int $top = 0,
        int $width = 0,
        int $height = 0,
        int $sensitivity = 4
    )
    {
        $this->setParams([
            'Config.DoorSetting.MOTION_DETECT.MotionDectect' => (int)($left || $top || $width || $height),
            'Config.DoorSetting.MOTION_DETECT.DetectDelay' => 3,
            'Config.DoorSetting.MOTION_DETECT.MDTimeWeekDay' => '0123456',
            'Config.DoorSetting.MOTION_DETECT.MDTimeStart' => '00:00',
            'Config.DoorSetting.MOTION_DETECT.MDTimeEnd' => '23:59',
            // 'Config.DoorSetting.MOTION_DETECT.AreaStartWidth' => $left,
            // 'Config.DoorSetting.MOTION_DETECT.AreaEndWidth' => $width,
            // 'Config.DoorSetting.MOTION_DETECT.AreaStartHeight' => $top,
            // 'Config.DoorSetting.MOTION_DETECT.AreaEndHeight' => $height,
            'Config.DoorSetting.MOTION_DETECT.DetectAccuracy' => $sensitivity,
            'Config.DoorSetting.MOTION_DETECT.FTPEnable' => 1, // for syslog event
        ]);
    }

    public function getCamshot(): string
    {
        $host = parse_url($this->url)['host'];
        return file_get_contents("http://$this->login:$this->password@$host:8080/picture.jpg");
    }

    public function setOsdText(string $text = '')
    {
        $this->setParams(['Config.DoorSetting.GENERAL.VideoWaterMark2' => $text]);
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

        $dbConfig['ntp']['timezone'] = $this->getOffsetByTimezone($dbConfig['ntp']['timezone']);

        return $dbConfig;
    }

    protected function getMotionDetectionConfig(): array
    {
        $mdEnabled = (bool)$this->getParam('Config.DoorSetting.MOTION_DETECT.MotionDectect');

        return [
            'left' => ($mdEnabled) ? 1 : 0,
            'top' => ($mdEnabled) ? 1 : 0,
            'width' => ($mdEnabled) ? 1 : 0,
            'height' => ($mdEnabled) ? 1 : 0,
        ];
    }

    protected function getOsdText(): string
    {
        return $this->getParam('Config.DoorSetting.GENERAL.VideoWaterMark2');
    }
}
