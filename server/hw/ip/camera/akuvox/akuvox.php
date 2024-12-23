<?php

namespace hw\ip\camera\akuvox;

use hw\ip\camera\camera;
use hw\ip\camera\entities\DetectionZone;

/**
 * Class representing an Akuvox camera.
 */
class akuvox extends camera
{

    use \hw\ip\common\akuvox\akuvox;

    public function configureMotionDetection(array $detectionZones): void
    {
        $firstZone = $detectionZones[0] ?? null;

        $areaStartWidth = $firstZone->x ?? 0;
        $areaEndWidth = $areaStartWidth + ($firstZone->width ?? 0);
        $areaStartHeight = $firstZone->y ?? 0;
        $areaEndHeight = $areaStartHeight + ($firstZone->height ?? 0);

        $this->setConfigParams([
            'Config.DoorSetting.MOTION_DETECT.Enable' => $firstZone !== null ? '2' : '0', // 2 - video detection
            'Config.DoorSetting.MOTION_DETECT.Interval' => '1', // Motion duration
            'Config.DoorSetting.MOTION_DETECT.TFTPEnable' => '0',
            'Config.DoorSetting.MOTION_DETECT.FTPEnable' => '1',
            'Config.DoorSetting.MOTION_DETECT.SendType' => '0',
            'Config.DoorSetting.MOTION_DETECT.DetectAccuracy' => "3",
            'Config.DoorSetting.MOTION_DETECT.AreaStartWidth' => "$areaStartWidth",
            'Config.DoorSetting.MOTION_DETECT.AreaEndWidth' => "$areaEndWidth",
            'Config.DoorSetting.MOTION_DETECT.AreaStartHeight' => "$areaStartHeight",
            'Config.DoorSetting.MOTION_DETECT.AreaEndHeight' => "$areaEndHeight",
        ]);
    }

    public function getCamshot(): string
    {
        $url = parse_url_ext($this->url);
        $host = $url["host"];
        $port = @($url["fragmentExt"] && $url["fragmentExt"]["camshotPort"]) ? $url["fragmentExt"]["camshotPort"] : 8080;

        $context = stream_context_create([
            'http' => [
                'timeout' => 3.0,
            ],
        ]);

        return file_get_contents("http://$this->login:$this->password@$host:$port/picture.jpg", false, $context);
    }

    public function setOsdText(string $text = ''): void // Latin only
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
        [$areaStartWidth, $areaEndWidth, $areaStartHeight, $areaEndHeight] = array_map(
            'floatval',
            $this->getConfigParams([
                'Config.DoorSetting.MOTION_DETECT.AreaStartWidth',
                'Config.DoorSetting.MOTION_DETECT.AreaEndWidth',
                'Config.DoorSetting.MOTION_DETECT.AreaStartHeight',
                'Config.DoorSetting.MOTION_DETECT.AreaEndHeight',
            ])
        );

        if ($areaStartWidth === 0 && $areaEndWidth === 0 && $areaStartHeight === 0 && $areaEndHeight === 0) {
            return [];
        }

        $width = round($areaEndWidth - $areaStartWidth, 2);
        $height = round($areaEndHeight - $areaStartHeight, 2);

        return [new DetectionZone($areaStartWidth, $areaStartHeight, $width, $height)];
    }

    protected function getOsdText(): string
    {
        return $this->getConfigParams(['Config.DoorSetting.RTSP.OSDText'])[0] ?? '';
    }
}
