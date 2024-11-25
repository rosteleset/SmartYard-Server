<?php

namespace hw\ip\camera\sputnik;

use hw\ip\camera\camera;

/**
 * Class representing a Sputnik camera.
 */
class sputnik extends camera
{

    use \hw\ip\common\sputnik\sputnik;

    /**
     * OSD field name for custom text.
     */
    protected const OSD_FIELD_NAME = 'osdUpLeft';

    /**
     * @var string|null Cached camera UUID or null if unset.
     */
    protected ?string $cameraUUID = null;

    public function configureMotionDetection(
        int $left = 0,
        int $top = 0,
        int $width = 0,
        int $height = 0,
        int $sensitivity = 0
    )
    {
        // Empty implementation
    }

    public function getCamshot(): string
    {
        // TODO: Implement getCamshot() method.
        return '';
    }

    public function setOsdText(string $text = ''): void
    {
        $this->apiCall('mutation', 'updateCameraOsdConfig', [
            'camera' => ['uuid' => $this->getCameraUUID()],
            self::OSD_FIELD_NAME => $text,
        ]);
    }

    public function syncData()
    {
        // Empty implementation
    }

    public function transformDbConfig(array $dbConfig): array
    {
        $dbConfig['ntp']['server'] = '';
        $dbConfig['ntp']['port'] = 123;
        $dbConfig['ntp']['timezone'] = $this->getOffsetByTimezone($dbConfig['ntp']['timezone']);

        $dbConfig['motionDetection'] = [
            'left' => 0,
            'top' => 0,
            'width' => 0,
            'height' => 0,
        ];

        return $dbConfig;
    }

    /**
     * Gets the UUID of the camera.
     *
     * Fetches and caches the camera UUID via an API if not already set.
     *
     * @return string|null The camera UUID, or null if unavailable.
     */
    protected function getCameraUUID(): ?string
    {
        if ($this->cameraUUID == null) {
            $intercom = $this->apiCall('query', 'intercom', ['uuid' => $this->uuid], ['camera' => ['uuid']]);
            $this->cameraUUID = $intercom['data']['intercom']['camera']['uuid'] ?? null;
        }

        return $this->cameraUUID;
    }

    protected function getMotionDetectionConfig(): array
    {
        return [
            'left' => 0,
            'top' => 0,
            'width' => 0,
            'height' => 0,
        ];
    }

    protected function getOsdText(): string
    {
        $intercom = $this->apiCall('query', 'camera', ['uuid' => $this->getCameraUUID()], [
            'configShadow' => ['osd' => [self::OSD_FIELD_NAME]],
        ]);

        return $intercom['data']['camera']['configShadow']['osd'][self::OSD_FIELD_NAME] ?? '';
    }
}
