<?php

namespace hw\ip\camera\sputnik;

use DateTime;
use DateTimeZone;
use Exception;
use hw\Interface\NtpServerInterface;
use hw\ip\camera\camera;
use hw\ip\camera\entities\DetectionZone;
use hw\ValueObject\{
    NtpServer,
    Port,
    ServerAddress,
};

/**
 * Class representing a Sputnik camera.
 */
class sputnik extends camera implements NtpServerInterface
{
    use \hw\ip\common\sputnik\sputnik;

    /**
     * OSD field name for custom text.
     */
    protected const OSD_FIELD_NAME = 'osdUpRight';

    /**
     * @var string|null Cached camera UUID or null if unset.
     */
    protected ?string $cameraUUID = null;

    public function configureMotionDetection(array $detectionZones): void
    {
        // Empty implementation
    }

    public function getCamshot(): string
    {
        // TODO: Implement getCamshot() method.
        return '';
    }

    public function getNtpServer(): NtpServer
    {
        $camera = $this->apiCall('query', 'camera', ['uuid' => $this->getCameraUUID()], [
            'configShadow' => [
                'ntp' => [
                    'ntpPort',
                    'ntpServer',
                    'timezone',
                ],
            ],
        ]);

        $ntpConfig = $camera['data']['camera']['configShadow']['ntp'] ?? [];

        return new NtpServer(
            address: ServerAddress::fromString($ntpConfig['ntpServer'] ?? ''),
            port: new Port($ntpConfig['ntpPort'] ?? 123),
            timezone: $ntpConfig['timezone'] ?? 'Europe/Moscow',
        );
    }

    public function reboot(): void
    {
        $this->apiCall('mutation', 'rebootCamera', ['cameraID' => $this->getCameraUUID()]);
    }

    public function reset(): void
    {
        $this->apiCall('mutation', 'restoreDefaultCameraConfig', ['cameraID' => $this->getCameraUUID()]);
    }

    public function setNtpServer(NtpServer $server): void
    {
        $this->apiCall('mutation', 'updateCameraNtpConfig', [
            'camera' => ['uuid' => $this->getCameraUUID()],
            'ntpPort' => $server->port,
            'ntpServer' => $server->address,
            'timezone' => $this->getOffsetByTimezone($server->timezone),
        ]);
    }

    public function setOsdText(string $text = ''): void
    {
        $this->apiCall('mutation', 'updateCameraOsdConfig', [
            'camera' => ['uuid' => $this->getCameraUUID()],
            self::OSD_FIELD_NAME => $text,
        ]);
    }

    public function syncData(): void
    {
        // Empty implementation
    }

    public function transformDbConfig(array $dbConfig): array
    {
        $dbConfig['ntp']['timezone'] = $this->getOffsetByTimezone($dbConfig['ntp']['timezone']);
        $dbConfig['motionDetection'] = [new DetectionZone(0, 0, 100, 100)];

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
        return [new DetectionZone(0, 0, 100, 100)];
    }

    /**
     * Get timezone representation for Sputnik camera.
     *
     * @param string $timezone Timezone identifier.
     *
     * @return string GMT offset (GMT+03:00 for example).
     */
    protected function getOffsetByTimezone(string $timezone): string
    {
        try {
            $time = new DateTime('now', new DateTimeZone($timezone));
            return 'GMT' . $time->format('P');
        } catch (Exception) {
            return 'GMT+03:00';
        }
    }

    protected function getOsdText(): string
    {
        $camera = $this->apiCall('query', 'camera', ['uuid' => $this->getCameraUUID()], [
            'configShadow' => ['osd' => [self::OSD_FIELD_NAME]],
        ]);

        return $camera['data']['camera']['configShadow']['osd'][self::OSD_FIELD_NAME] ?? '';
    }
}
