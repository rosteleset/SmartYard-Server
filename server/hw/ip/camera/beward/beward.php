<?php

namespace hw\ip\camera\beward;

use hw\Interface\NtpServerInterface;
use hw\ip\camera\camera;
use hw\ip\camera\entities\DetectionZone;
use hw\ip\camera\utils\DetectionZoneUtils;

/**
 * Class representing a Beward camera.
 */
class beward extends camera implements NtpServerInterface
{
    use \hw\ip\common\beward\beward;

    protected const DETECTION_ZONE_MAX_X = 704;
    protected const DETECTION_ZONE_MAX_Y = 576;

    public function configureMotionDetection(array $detectionZones): void
    {
        $pixelZone = isset($detectionZones[0])
            ? DetectionZoneUtils::convertCoordinates(
                zone: $detectionZones[0],
                maxX: self::DETECTION_ZONE_MAX_X,
                maxY: self::DETECTION_ZONE_MAX_Y,
                direction: 'toPixel',
            )
            : null;

        $params = [
            'sens' => 3,
            'ckdetect' => $pixelZone ? '1' : '0',
            'ckevery' => $pixelZone ? '1' : '0',
            'ckevery2' => '0',
            'begh1' => '0',
            'begm1' => '0',
            'endh1' => 23,
            'endm1' => 59,
            'ckhttp' => '0',
            'alarmoutemail' => '0',
            'ckcap' => '0',
            'ckalarmrecdev' => '0',
            'nLeft1' => $pixelZone->x ?? 0,
            'nTop1' => $pixelZone->y ?? 0,
            'nWidth1' => $pixelZone->width ?? 0,
            'nHeight1' => $pixelZone->height ?? 0,
        ];

        $this->apiCall('webs/motionCfgEx', $params);
    }

    public function getCamshot(): string
    {
        return $this->apiCall('cgi-bin/images_cgi', ['channel' => 0], false, 3);
    }

    public function setOsdText(string $text = ''): void
    {
        $this->apiCall('cgi-bin/textoverlay_cgi', [
            'action' => 'set',
            'Title' => $text,
            'TitleValue' => $text ? 1 : 0,
            'DateValue' => 1,
            'TimeValue' => 1,
            'TimeFormat12' => 'False',
            'DateFormat' => 2,
            'WeekValue' => 0,
            'BitrateValue' => 0,
            'Color' => 0,
            'ClientNum' => 0,
        ]);
    }

    protected function getMotionDetectionConfig(): array
    {
        // TODO: add rounding
        $mdParams = $this->getParams('motion_cgi');

        if ($mdParams['MotionSwitch'] === 'close') {
            return [];
        }

        // Values in coordinates of the real current resolution
        $pixelZone = new DetectionZone(
            x: $mdParams['DetectArea0_x'],
            y: $mdParams['DetectArea0_y'],
            width: $mdParams['DetectArea0_w'],
            height: $mdParams['DetectArea0_h'],
        );

        // Get max X and max Y for current resolution
        [$maxX, $maxY] = explode('*', $this->getResolution());

        return [DetectionZoneUtils::convertCoordinates($pixelZone, $maxX, $maxY, 'toPercent')];
    }

    protected function getOsdText(): string
    {
        return $this->getParams('textoverlay_cgi')['Title'];
    }

    /**
     * Retrieves the current camera resolution.
     *
     * @return string The resolution as a string in the format "width\*height".
     * Defaults to "1280*720" if the parameter is not found.
     */
    protected function getResolution(): string
    {
        return $this->getParams('videocoding_cgi')['Resolution1'] ?? '1280*720';
    }
}
