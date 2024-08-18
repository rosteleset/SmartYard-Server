<?php

namespace utils\SmartConfigurator\ConfigurationBuilder;

/**
 * This class is responsible for building camera configuration.
 */
class CameraConfigurationBuilder extends ConfigurationBuilder
{

    /**
     * Construct a new CameraConfigurationBuilder instance.
     */
    public function __construct()
    {
        $this->config = [
            'eventServer' => [],
            'motionDetection' => [],
            'ntp' => [],
            'osdText' => '',
        ];
    }

    /**
     * Add motion detection parameters to the camera configuration.
     *
     * @param int $left The left coordinate of the motion detection area.
     * @param int $top The top coordinate of the motion detection area.
     * @param int $width The width of the motion detection area.
     * @param int $height The height of the motion detection area.
     *
     * @return self
     */
    public function addMotionDetection(int $left, int $top, int $width, int $height): self
    {
        $this->config['motionDetection'] = compact('left', 'top', 'width', 'height');

        return $this;
    }

    /**
     * Add on-screen display (OSD) text to the camera configuration.
     *
     * @param string $text The text to display on-screen.
     *
     * @return self
     */
    public function addOsdText(string $text): self
    {
        $this->config['osdText'] = $text;

        return $this;
    }
}
