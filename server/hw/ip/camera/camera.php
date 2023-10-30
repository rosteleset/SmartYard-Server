<?php

namespace hw\ip\camera;

use hw\ip\ip;
use utils\SmartConfigurator\ConfigurationBuilder\CameraConfigurationBuilder;

/**
 * Abstract class representing a camera.
 */
abstract class camera extends ip
{

    final public function getCurrentConfig(): array
    {
        $builder = new CameraConfigurationBuilder();

        $builder
            ->addEventServer(...$this->getEventServerConfig())
            ->addMotionDetection(...$this->getMotionDetectionConfig())
            ->addNtp(...$this->getNtpConfig())
            ->addOsdText($this->getOsdText())
        ;

        return $builder->getConfig();
    }

    public function prepare()
    {
        // $this->configureEncoding();
    }

    /**
     * Get motion detection configuration.
     *
     * @return array An array containing motion detection params configured on the device.
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
     * Calling this function without parameters will enable motion detection
     * with a full-frame zone and optimal sensitivity.
     *
     * @param int $left (Optional) Left coordinate of the detection area.
     * @param int $top (Optional) Top coordinate of the detection area.
     * @param int $width (Optional) Width of the detection area.
     * @param int $height (Optional) Height of the detection area.
     * @param int $sensitivity (Optional) Sensitivity level for motion detection.
     *
     * @return void
     */
    abstract public function configureMotionDetection(
        int $left = 0,
        int $top = 0,
        int $width = 0,
        int $height = 0,
        int $sensitivity = 0
    );

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
    abstract public function setOsdText(string $text = '');
}
