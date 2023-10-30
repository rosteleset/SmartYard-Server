<?php

namespace hw\ip\camera\rubetek;

use hw\ip\camera\camera;

/**
 * Class representing a Rubetek camera.
 */
class rubetek extends camera
{

    use \hw\ip\common\rubetek\rubetek;

    public function configureMotionDetection(
        int $left = 0,
        int $top = 0,
        int $width = 705,
        int $height = 576,
        int $sensitivity = 1
    )
    {
        $detectionSettings = $this->getConfig()['face_detection'];

        // Server
        $detectionSettings['address'] = '1'; // Not used
        $detectionSettings['reserved_address'] = '1'; // Not used
        $detectionSettings['token'] = '1'; // Not used

        // Detection settings
        $detectionSettings['detection_mode'] = $sensitivity ? 1 : 0; // Detection on
        $detectionSettings['threshold'] = 42; // Threshold of confidence
        $detectionSettings['liveness_frame_num'] = 0; // Not used
        $detectionSettings['frame_interval'] = 500; // Doesn't work
        $detectionSettings['face_presence_time'] = 0; // Not used
        $detectionSettings['min_dimension'] = 50; // Minimum face size px
        $detectionSettings['max_dimension'] = 500; // Maximum face size px
        $detectionSettings['rect_image_format'] = 1; // Not used

        // Detection area
        $detectionSettings['rec_area_top'] = $top;
        $detectionSettings['rec_area_bottom'] = $height;
        $detectionSettings['rec_area_left'] = $left;
        $detectionSettings['rec_area_right'] = $width;
        $detectionSettings['outMargin'] = 50; // Detection indent

        $this->apiCall('/configuration', 'PATCH', ['face_detection' => $detectionSettings]);
    }

    public function getCamshot(): string
    {
        return $this->apiCall('/image');
    }

    public function setOsdText(string $text = '')
    {
        $this->apiCall('/settings/osd', 'PATCH', [
            'show_name' => true,
            'name' => $text,
            'show_datetime' => true,
            'date_format' => 'DD.MM.YYYY',
            'use_24h_clock' => true,
            'weekdays' => true,
        ]);
    }

    public function transformDbConfig(array $dbConfig): array
    {
        $timezone = $dbConfig['ntp']['timezone'];
        $dbConfig['ntp']['timezone'] = $this->getOffsetByTimezone($timezone);
        return $dbConfig;
    }

    protected function getMotionDetectionConfig(): array
    {
        [
            // 'threshold' => $sensitivity,
            'rec_area_top' => $top,
            'rec_area_bottom' => $height,
            'rec_area_left' => $left,
            'rec_area_right' => $width,
        ] = $this->getConfig()['face_detection'];

        return [
            'left' => $left,
            'top' => $top,
            'width' => $width,
            'height' => $height,
            // 'sensitivity' => $sensitivity,
        ];
    }

    protected function getOsdText(): string
    {
        return $this->getConfig()['osd']['name'];
    }
}
