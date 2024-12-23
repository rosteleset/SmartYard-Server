<?php

namespace hw\ip\camera\qtech;

use hw\ip\camera\camera;

/**
 * Class representing a Qtech camera.
 */
class qtech extends camera
{

    use \hw\ip\common\qtech\qtech;

    public function configureMotionDetection(array $detectionZones): void
    {
        $isEnabled = (int)$detectionZones;

        // Motion detection params
        $this->setParams([
            'Config.DoorSetting.MOTION_DETECT.MotionDectect' => $isEnabled,
            'Config.DoorSetting.MOTION_DETECT.DetectDelay' => 3, // delay between sending motion detection events
            'Config.DoorSetting.MOTION_DETECT.MDTimeWeekDay' => '0123456',
            'Config.DoorSetting.MOTION_DETECT.MDTimeStart' => '00:00',
            'Config.DoorSetting.MOTION_DETECT.MDTimeEnd' => '23:59',
            'Config.DoorSetting.MOTION_DETECT.DetectAccuracy' => 4, // feels like it doesn't affect anything
            'Config.DoorSetting.MOTION_DETECT.FTPEnable' => 1, // for syslog event
        ]);

        // Face detection params
        $this->setParams([
            'Config.DoorSetting.FACEDETECT.Enable' => $isEnabled,
            'Config.DoorSetting.FACEDETECT.IP' => 'fake', // no syslog messages without this
            'Config.DoorSetting.FACEDETECT.Timeout' => 3, // ???
            'Config.DoorSetting.FACEDETECT.ResetTime' => 60, // ???
            'Config.DoorSetting.FACEDETECT.FaceLiveness' => 0, // feels like it doesn't affect anything
            'Config.DoorSetting.FACEDETECT.SendInterval' => 2, // ???
        ]);
    }

    public function getCamshot(): string
    {
        $host = parse_url($this->url)['host'];

        $context = stream_context_create([
            'http' => [
                'timeout' => 3.0,
            ],
        ]);

        return file_get_contents("http://$this->login:$this->password@$host:8080/picture.jpg", false, $context);
    }

    public function prepare(): void
    {
        parent::prepare();
        $this->configureImage();
    }

    public function setOsdText(string $text = ''): void
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

    /**
     * Apply optimal image settings.
     *
     * @return void
     */
    protected function configureImage(): void
    {
        $this->setParams([
            'Config.DoorSetting.GENERAL.IRSetting' => 1, // IR brightness (1 - 99)
            'Config.DoorSetting.RTSP.ColorMode' => 0, // Auto
            'Config.DoorSetting.RTSP.NoiseReduction' => 1, // Enabled
            'Config.DoorSetting.RTSP.BLC' => 1, // Enabled
            'Config.DoorSetting.RTSP.ExposureMode' => 1, // Auto mode
        ]);
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
