<?php

namespace hw\ip\camera;

use hw\ip\camera\entities\DetectionZone;
use hw\ip\ip;
use hw\SmartConfigurator\ConfigurationBuilder\CameraConfigurationBuilder;

/**
 * Abstract class representing a camera.
 */
abstract class camera extends ip
{

    final public function getConfig(): array
    {
        $builder = new CameraConfigurationBuilder();

        $builder
            ->addEventServer($this->getEventServer())
            ->addMotionDetection($this->getMotionDetectionConfig())
            ->addNtp(...$this->getNtpConfig())
            ->addOsdText($this->getOsdText())
        ;

        return $builder->getConfig();
    }

    public function prepare(): void
    {
        // $this->configureEncoding();
    }

    /**
     * Get motion detection configuration.
     *
     * @return DetectionZone[] An array containing motion detection objects configured on the device.
     */
    abstract protected function getMotionDetectionConfig(): array;

    /**
     * Get OSD text.
     *
     * @return string OSD overlay text configured on the device.
     */
    abstract protected function getOsdText(): string;

    /**
     * Configure motion detection parameters.
     *
     * @param DetectionZone[] $detectionZones Array of DetectionZone objects.
     *
     * @return void
     */
    abstract public function configureMotionDetection(array $detectionZones): void;

    /**
     * Retrieves a snapshot from the camera.
     *
     * @return string Image data.
     */
    abstract public function getCamshot(): string;

    /**
     * Set OSD overlay text.
     *
     * @param string $text (Optional) Text that is displayed over the video stream.
     * Default is an empty string.
     *
     * @return void
     */
    abstract public function setOsdText(string $text = ''): void;
}
