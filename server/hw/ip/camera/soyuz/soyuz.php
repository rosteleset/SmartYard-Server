<?php

namespace hw\ip\camera\soyuz;

use hw\ip\camera\camera;
use hw\ip\camera\entities\DetectionZone;

/**
 * Class representing a Soyuz camera.
 */
class soyuz extends camera
{

    use \hw\ip\common\soyuz\soyuz;

    public function configureMotionDetection(array $detectionZones): void
    {
        $pixelZone = isset($detectionZones[0])
                    ? DetectionZoneUtils::convertCoordinates(
                        zone: $detectionZones[0],
                        maxX: $maxX,
                        maxY: $maxY,
                        direction: 'toPixel'
                      ) : null;
        $x = $pixelZone->x ?? 0;
        $y = $pixelZone->y ?? 0;
        $width = $pixelZone->width ?? 0;
        $height = $pixelZone->height ?? 0;
        $this->apiCall('/v2/camera/md', 'PUT', [
            'md_enable' => !empty($detectionZones[0]),
            'md_roi'    => "{$x}x{$y}x{$width}x$height",
            'md_skipIn' => ""
        ]);
    }

    public function getCamshot(): string
    {
        return $this->apiCall('/v2/camera/snapshot', 'GET', [], 3);
    }

    public function setOsdText(string $text = ''): void
    {

    }

    public function transformDbConfig(array $dbConfig): array
    {
        if ($dbConfig['motionDetection']) {
            $dbConfig['motionDetection'] = [new DetectionZone(0, 0, 100, 100)];
        }

        return $dbConfig;
    }
	
    protected function convertResponseToArray(string $response): array
    {
        $responseArray = [];

        if (!empty($response)) {
            $lines = explode("\n", trim($response));

            foreach ($lines as $line) {
                [$longKey, $value] = explode('=', trim($line), 2);
                $longKeyArray = explode('.', $longKey);
                $responseArray[end($longKeyArray)] = $value;
            }
        }

        return $responseArray;
    }


    protected function getMotionDetectionConfig(): array
    {
        $rawParams = $this->apiCall('/v2/camera/md', 'GET', []);
        $params = $this->convertResponseToArray($rawParams);
        if (($params['md_enable'] ?? 'false') === 'false') {
            return [];
        }
        $coordinates = explode('x', $params['md_roi'] ?? '0x0x0x0');
        return [new DetectionZone(...$coordinates)];
    }

    protected function getOsdText(): string
    {
	return '';
    }
}
