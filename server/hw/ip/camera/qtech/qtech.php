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
        $params = $this->paramsToString([
            'Config.DoorSetting.MOTION_DETECT.MotionDectect' => 1,
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
        $this->setParams($params);
    }

    public function getCamshot(): string
    {
        $host = parse_url($this->url)['host'];
        return file_get_contents("http://$this->login:$this->password@$host:8080/picture.jpg");
    }

    public function setOsdText(string $text = '')
    {
        $params = $this->paramsToString([
            'Config.DoorSetting.GENERAL.VideoWaterMark2' => $text,
        ]);
        $this->setParams($params);
    }

    public function transformDbConfig(array $dbConfig): array
    {
        return $dbConfig;
    }

    protected function getMotionDetectionConfig(): array
    {
        // TODO: Implement getMotionDetectionConfig() method.
        return [];
    }

    protected function getOsdText(): string
    {
        // TODO: Implement getOsdText() method.
        return '';
    }
}
