<?php

namespace hw\ip\camera\hikvision;

use hw\ip\camera\camera;

/**
 * Class representing a Hikvision camera.
 */
class hikvision extends camera
{

    use \hw\ip\common\hikvision\hikvision;

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
        return $this->apiCall('/Streaming/channels/101/picture', 'GET', ['snapShotImageType' => 'JPEG']);
    }

    public function setOsdText(string $text = '')
    {
        $this->apiCall(
            '/System/Video/inputs/channels/1',
            'PUT',
            [],
            "<VideoInputChannel>
                <id>1</id>
                <inputPort>1</inputPort>
                <name>$text</name>
            </VideoInputChannel>"
        );
        $this->apiCall(
            '/System/Video/inputs/channels/1/overlays',
            'PUT',
            [],
            '<VideoOverlay>
                <DateTimeOverlay>
                    <enabled>true</enabled>
                    <positionY>540</positionY>
                    <positionX>0</positionX>
                    <dateStyle>MM-DD-YYYY</dateStyle>
                    <timeStyle>24hour</timeStyle>
                    <displayWeek>true</displayWeek>
                </DateTimeOverlay>
                <channelNameOverlay>
                    <enabled>true</enabled>
                    <positionY>700</positionY>
                    <positionX>0</positionX>
                </channelNameOverlay>
            </VideoOverlay>'
        );
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
        // TODO: Implement getOsdText() method.
        return '';
    }
}
