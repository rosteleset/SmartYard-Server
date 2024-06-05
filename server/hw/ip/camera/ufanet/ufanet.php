<?php

namespace hw\ip\camera\ufanet;

use hw\ip\camera\camera;

/**
 * Class representing an Ufanet camera.
 */
class ufanet extends camera
{

    use \hw\ip\common\ufanet\ufanet;

    public function configureMotionDetection(
        int $left = 0,
        int $top = 0,
        int $width = 0,
        int $height = 0,
        int $sensitivity = 0
    )
    {
        // TODO: Implement configureMotionDetection() method.
    }

    public function getCamshot(): string
    {
        return $this->apiCall('/image.jpg', ['width' => 1920, 'height' => 1080]);
    }

    public function setOsdText(string $text = '')
    {
        $this->apiCall('/cgi-bin/configManager.cgi', [
            'action' => 'setConfig',
            'Locales.TimeFormat' => "%22%d.%m.%y%20%H:%M:%S%20$text%22",
            'VideoWidget[0].TimeTitle.Rect[0]' => 0,
            'VideoWidget[0].TimeTitle.Rect[1]' => 0,
        ]);
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
        $rawParams = $this->apiCall('/cgi-bin/configManager.cgi', [
            'action' => 'getConfig',
            'name' => 'Locales'
        ]);

        ['TimeFormat' => $timeFormat] = $this->convertResponseToArray($rawParams);
        return explode(' ', $timeFormat, 3)[2];
    }
}
