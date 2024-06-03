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
            'MotionDetect[0].MotionDetectWindow[0].Sensitive' => 50, // Max sensitive
            'MotionDetect[0].EventHandler.Dejitter' => 1,
        ]);
    }

    public function getCamshot(): string
    {
        return $this->apiCall('/image.jpg');
    }

    public function setOsdText(string $text = '')
    {
        // Datetime OSD
        $this->apiCall('/cgi-bin/configManager.cgi', 'GET', [
            'action' => 'setConfig',
            'Locales.TimeFormat' => "%22%d.%m.%y %H:%M:%S%22", // dd.mm.yy HH:MM:SS
            'VideoWidget[0].TimeTitle.Rect[0]' => 0,
            'VideoWidget[0].TimeTitle.Rect[1]' => 0,
        ]);

        // Text OSD
        // TODO: wait for fix
        $osdTextParams = [
            'OSD[0].Enable' => $text !== '' ? 'true' : 'false',
            'OSD[0].Text' => $text,
            'OSD[0].PosX' => 4,
            'OSD[0].PosY' => 684,
            'OSD[0].Size' => 32,
        ];

        foreach ($osdTextParams as $key => $param) {
            $this->apiCall('/cgi-bin/osd.cgi', 'GET', ['action' => 'setConfig', $key => $param]);
        }

        $this->reboot();
        $this->wait();
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
        $rawParams = $this->apiCall('/cgi-bin/osd.cgi', 'GET', [
            'action' => 'getConfig',
            'OSD[0]' => '',
        ]);

        $params = $this->convertResponseToArray($rawParams);
        return $params['Text'] ?? '';
    }
}
