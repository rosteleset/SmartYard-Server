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
        $this->apiCall('/cgi-bin/configManager.cgi', 'GET', [
            'action' => 'setConfig',
            'MotionDetect[0].Enable' => $left || $top || $width || $height ? 'true' : 'false',
            'MotionDetect[0].MotionDetectWindow[0].ROI' => "{$left}x{$top}x{$width}x$height",
        ]);
    }

    public function getCamshot(): string
    {
        return $this->apiCall('/image.jpg', 'GET', ['width' => 1920, 'height' => 1080]);
    }

    public function setOsdText(string $text = '')
    {
        $modifiedText = str_replace(' ', '%20', $text);

        $this->apiCall('/cgi-bin/configManager.cgi', 'GET', [
            'action' => 'setConfig',
            'Locales.TimeFormat' => "%22%d.%m.%y%20%H:%M:%S%20$modifiedText%22",
            'VideoWidget[0].TimeTitle.Rect[0]' => 0,
            'VideoWidget[0].TimeTitle.Rect[1]' => 0,
        ]);
    }

    public function syncData()
    {
        // Empty implementation
    }

    public function transformDbConfig(array $dbConfig): array
    {
        return $dbConfig;
    }

    protected function getMotionDetectionConfig(): array
    {
        $rawParams = $this->apiCall('/cgi-bin/configManager.cgi', 'GET', [
            'action' => 'getConfig',
            'name' => 'MotionDetect',
        ]);

        $params = $this->convertResponseToArray($rawParams);
        $coordinates = explode('x', $params['ROI'] ?? '0x0x0x0');

        return [
            'left' => $coordinates[0],
            'top' => $coordinates[1],
            'width' => $coordinates[2],
            'height' => $coordinates[3],
        ];
    }

    protected function getOsdText(): string
    {
        $rawParams = $this->apiCall('/cgi-bin/configManager.cgi', 'GET', [
            'action' => 'getConfig',
            'name' => 'Locales'
        ]);

        $params = $this->convertResponseToArray($rawParams);
        $timeFormat = $params['TimeFormat'] ?? '';

        return explode(' ', $timeFormat, 3)[2] ?? '';
    }
}
