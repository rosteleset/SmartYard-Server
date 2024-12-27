<?php

namespace hw\ip\camera\rubetek;

use hw\ip\camera\camera;
use hw\ip\camera\entities\DetectionZone;

/**
 * Class representing a Rubetek camera.
 */
class rubetek extends camera
{

    use \hw\ip\common\rubetek\rubetek;

    public function configureMotionDetection(array $detectionZones): void
    {
        $detectionSettings = $this->getConfig()['face_detection'];

        // Server
        $detectionSettings['address'] = '1'; // Not used
        $detectionSettings['reserved_address'] = '1'; // Not used
        $detectionSettings['token'] = '1'; // Not used

        // Detection settings
        $detectionSettings['detection_mode'] = (int)$detectionZones; // Detection on/off
        $detectionSettings['threshold'] = 90; // Confidence threshold
        $detectionSettings['liveness_frame_num'] = 0; // Not used
        $detectionSettings['frame_interval'] = 500; // Doesn't work
        $detectionSettings['face_presence_time'] = 0; // Not used
        $detectionSettings['min_dimension'] = 50; // Minimum face size px
        $detectionSettings['max_dimension'] = 500; // Maximum face size px
        $detectionSettings['rect_image_format'] = 1; // Not used

        // Detection area
        $detectionSettings['rec_area_top'] = 10;
        $detectionSettings['rec_area_bottom'] = 10;
        $detectionSettings['rec_area_left'] = 10;
        $detectionSettings['rec_area_right'] = 10;
        $detectionSettings['outMargin'] = 50; // Detection indent

        $this->apiCall('/configuration', 'PATCH', ['face_detection' => $detectionSettings]);
    }

    public function getCamshot(): string
    {
        return $this->apiCall('/image', 'GET', [], 5);
    }

    public function setOsdText(string $text = ''): void
    {
        $this->apiCall('/settings/osd', 'PATCH', [
            'show_name' => true,
            'name' => $text,
            'osd_position' => 'top_left',
            'show_datetime' => true,
            'date_format' => 'DD.MM.YYYY',
            'use_24h_clock' => true,
            'weekdays' => false,
        ]);
    }

    public function transformDbConfig(array $dbConfig): array
    {
        $timezone = $dbConfig['ntp']['timezone'];
        $dbConfig['ntp']['timezone'] = $this->getOffsetByTimezone($timezone);

        if ($dbConfig['motionDetection']) {
            $dbConfig['motionDetection'] = [new DetectionZone(0, 0, 100, 100)];
        }

        return $dbConfig;
    }

    protected function getMotionDetectionConfig(): array
    {
        ['detection_mode' => $detectionMode] = $this->getConfig()['face_detection'];

        if ($detectionMode === 1) {
            return [new DetectionZone(0, 0, 100, 100)];
        }

        return [];
    }

    protected function getOsdText(): string
    {
        return $this->getConfig()['osd']['name'];
    }
}
