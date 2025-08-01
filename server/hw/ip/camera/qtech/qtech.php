<?php

namespace hw\ip\camera\qtech;

use hw\Interface\NtpServerInterface;
use hw\ip\camera\camera;
use hw\ip\camera\entities\DetectionZone;

/**
 * Class representing a Qtech camera.
 */
class qtech extends camera implements NtpServerInterface
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
        if ($dbConfig['motionDetection']) {
            $dbConfig['motionDetection'] = [new DetectionZone(0, 0, 100, 100)];
        }

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

        if ($mdEnabled) {
            return [new DetectionZone(0, 0, 100, 100)];
        }

        return [];
    }

    protected function getOsdText(): string
    {
        return $this->getParam('Config.DoorSetting.GENERAL.VideoWaterMark2');
    }
}
