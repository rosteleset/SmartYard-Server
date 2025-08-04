<?php

namespace hw\SmartConfigurator\DbConfigCollector;

use hw\hw;
use hw\Interface\NtpServerInterface;
use hw\ip\camera\entities\DetectionZone;
use hw\SmartConfigurator\ConfigurationBuilder\CameraConfigurationBuilder;
use hw\ValueObject\{
    NtpServer,
    Port,
    ServerAddress,
};

/**
 * Class responsible for collecting camera configuration data from the database.
 */
class CameraDbConfigCollector implements DbConfigCollectorInterface
{
    /**
     * @var array The application configuration.
     */
    private array $appConfig;

    /**
     * @var array The camera data.
     */
    private array $cameraData;

    /**
     * @var CameraConfigurationBuilder The builder used to construct camera configuration.
     */
    private CameraConfigurationBuilder $builder;

    /**
     * @var hw
     */
    private hw $device;

    /**
     * Construct a new CameraDbConfigCollector instance.
     *
     * @param array $appConfig The application configuration.
     * @param array $cameraData The camera data.
     * @param hw $device Device instance.
     */
    public function __construct(array $appConfig, array $cameraData, hw $device)
    {
        $this->appConfig = $appConfig;
        $this->cameraData = $cameraData;
        $this->device = $device;
        $this->builder = new CameraConfigurationBuilder();
    }

    public function collectConfig(): array
    {
        if ($this->device instanceof NtpServerInterface) {
            $this->addNtpServer();
        }

        $this
            ->addEventServer()
            ->addMotionDetection()
            ->addOsdText()
        ;

        return $this->builder->getConfig();
    }

    /**
     * Add the event server information to the camera configuration.
     *
     * @return self
     */
    private function addEventServer(): self
    {
        $url = $this->appConfig['syslog_servers'][$this->cameraData['json']['eventServer']][0];
        $this->builder->addEventServer($url);
        return $this;
    }

    /**
     * Add motion detection settings to the camera configuration.
     *
     * @return self
     */
    private function addMotionDetection(): self
    {
        $zones = array_map(
            fn($area) => new DetectionZone($area->x, $area->y, $area->w, $area->h),
            $this->cameraData['mdArea'] ?? [],
        );

        $this->builder->addMotionDetection($zones);
        return $this;
    }

    /**
     * Add NTP server settings to the camera configuration.
     *
     * @return void
     */
    private function addNtpServer(): void
    {
        $url = $this->appConfig['ntp_servers'][0];
        $urlParts = parse_url_ext($url);
        $timezone = $this->cameraData['timezone'];

        if ($timezone === '-') {
            $timezone = 'Europe/Moscow';
        }

        $ntpServer = new NtpServer(
            address: ServerAddress::fromString($urlParts['host']),
            port: new Port($urlParts['port']),
            timezone: $timezone,
        );

        $this->builder->addNtpServer($ntpServer);
    }

    /**
     * Add OSD text settings to the camera configuration.
     *
     * @return void
     */
    private function addOsdText(): void
    {
        $this->builder->addOsdText($this->cameraData['name']);
    }
}
