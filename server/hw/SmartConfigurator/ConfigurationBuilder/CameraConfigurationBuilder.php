<?php

namespace hw\SmartConfigurator\ConfigurationBuilder;

use hw\ip\camera\entities\DetectionZone;

/**
 * The class responsible for building the camera configuration.
 */
class CameraConfigurationBuilder extends ConfigurationBuilder
{
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
     * @param DetectionZone[] $detectionZones Array of DetectionZone objects.
     * @return self
     */
    public function addMotionDetection(array $detectionZones): self
    {
        $this->config['motionDetection'] = $detectionZones;
        return $this;
    }

    /**
     * Add on-screen display (OSD) text to the camera configuration.
     *
     * @param string $text The text to display on-screen.
     * @return self
     */
    public function addOsdText(string $text): self
    {
        $this->config['osdText'] = $text;
        return $this;
    }
}
