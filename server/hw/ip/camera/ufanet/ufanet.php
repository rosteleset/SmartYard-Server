<?php

namespace hw\ip\camera\ufanet;

use hw\ip\camera\camera;
use hw\ip\camera\entities\DetectionZone;
use hw\ip\camera\utils\DetectionZoneUtils;

/**
 * Class representing an Ufanet camera.
 */
class ufanet extends camera
{
    use \hw\ip\common\ufanet\ufanet {
        transformDbConfig as protected commonTransformDbConfig;
    }

    public function configureMotionDetection(array $detectionZones): void
    {
        // Get max X and max Y for current resolution
        [$maxX, $maxY] = explode('x', $this->getResolution());

        $pixelZone = isset($detectionZones[0])
            ? DetectionZoneUtils::convertCoordinates(
                zone: $detectionZones[0],
                maxX: $maxX,
                maxY: $maxY,
                direction: 'toPixel'
            )
            : null;

        $x = $pixelZone->x ?? 0;
        $y = $pixelZone->y ?? 0;
        $width = $pixelZone->width ?? 0;
        $height = $pixelZone->height ?? 0;

        $this->apiCall('/cgi-bin/configManager.cgi', 'GET', [
            'action' => 'setConfig',
            'MotionDetect[0].Enable' => $pixelZone ? 'true' : 'false',
            'MotionDetect[0].MotionDetectWindow[0].ROI' => "{$x}x{$y}x{$width}x$height",
            'MotionDetect[0].MotionDetectWindow[0].Sensitive' => 50, // Max sensitive
            'MotionDetect[0].EventHandler.Dejitter' => 1,
        ]);
    }

    public function getCamshot(): string
    {
        return $this->apiCall('/image.jpg', 'GET', null, 3);
    }

    public function setOsdText(string $text = ''): void
    {
        // Text OSD
        $this->apiCall('/cgi-bin/osd.cgi', 'GET', [
            'action' => 'setConfig',
            'OSD[0].Enable' => $text !== '' ? 'true' : 'false',
            'OSD[0].Text' => $text,
            'OSD[0].PosX' => 4,
            'OSD[0].PosY' => 684,
            'OSD[0].Size' => 32,
        ]);

        /*
         * Set field "FrontColor" to 0 before setting the datetime OSD with "FrontColor" field equal to 1.
         * This maneuver (probably?) triggers an internal mechanism that restarts the streamer or something else
         * and avoids rebooting the camera to apply OSD settings.
         */
        $this->apiCall('/cgi-bin/configManager.cgi', 'GET', [
            'action' => 'setConfig',
            'VideoWidget[0].TimeTitle.FrontColor[3]' => 0,
        ]);

        // Datetime OSD
        $this->apiCall('/cgi-bin/configManager.cgi', 'GET', [
            'action' => 'setConfig',
            'VideoWidget[0].TimeTitle.FrontColor[3]' => 1,
            'Locales.TimeFormat' => "%22%d.%m.%y %H:%M:%S%22", // dd.mm.yy HH:MM:SS
            'VideoWidget[0].TimeTitle.Rect[0]' => 0,
            'VideoWidget[0].TimeTitle.Rect[1]' => 0,
        ]);
    }

    public function syncData(): void
    {
        // Empty implementation
    }

    public function transformDbConfig(array $dbConfig): array
    {
        $dbConfig = $this->commonTransformDbConfig($dbConfig);

        // Convert detection zone from database to pixel
        if ($dbConfig['motionDetection']) {
            [$maxX, $maxY] = explode('x', $this->getResolution());

            $dbConfig['motionDetection'] = [
                DetectionZoneUtils::convertCoordinates(
                    zone: $dbConfig['motionDetection'][0],
                    maxX: $maxX,
                    maxY: $maxY,
                    direction: 'toPixel')
            ];
        }

        return $dbConfig;
    }

    protected function getMotionDetectionConfig(): array
    {
        $rawParams = $this->apiCall('/cgi-bin/configManager.cgi', 'GET', [
            'action' => 'getConfig',
            'name' => 'MotionDetect',
        ]);

        $params = $this->convertResponseToArray($rawParams);

        if (($params['Enable'] ?? 'false') === 'false') {
            return [];
        }

        $coordinates = explode('x', $params['ROI'] ?? '0x0x0x0');
        return [new DetectionZone(...$coordinates)];
    }

    protected function getOsdText(): string
    {
        $rawParams = $this->apiCall('/cgi-bin/osd.cgi', 'GET', [
            'action' => 'getConfig',
            'OSD[0]' => '',
        ]);

        $params = $this->convertResponseToArray($rawParams);
        return $params['Text'] ?? '';
    }

    /**
     * Retrieves the current camera resolution.
     *
     * @return string The resolution as a string in the format "{width}x{height}".
     * Defaults to "1280x720" if the parameter is not found.
     */
    protected function getResolution(): string
    {
        $rawParams = $this->apiCall('/cgi-bin/configManager.cgi', 'GET', [
            'action' => 'getConfig',
            'name' => 'Encode',
        ]);

        $params = $this->convertResponseToArray($rawParams);
        return $params['Resolution'] ?? '1280x720';
    }
}
