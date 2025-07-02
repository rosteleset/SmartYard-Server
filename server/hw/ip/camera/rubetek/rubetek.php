<?php

namespace hw\ip\camera\rubetek;

use hw\ip\camera\camera;
use hw\ip\camera\entities\DetectionZone;

/**
 * Class representing a Rubetek camera.
 */
class rubetek extends camera
{
    use \hw\ip\common\rubetek\rubetek {
        transformDbConfig as protected commonTransformDbConfig;
    }

    public function configureMotionDetection(array $detectionZones): void
    {
        $this->apiCall('/settings/face_detection', 'PATCH', [
            // Server
            'address' => '1', // Not used
            'reserved_address' => '1', // Not used
            'token' => '1', // Not used

            // Detection settings
            'detection_mode' => (int)$detectionZones, // Detection on/off
            'threshold' => 80, // Confidence threshold
            'liveness_frame_num' => 0, // Not used
            'frame_interval' => 500, // Doesn't work
            'face_presence_time' => 0, // Not used
            'min_dimension' => 50, // Minimum face size px
            'max_dimension' => 500, // Maximum face size px
            'rect_image_format' => 1, // Not used

            // Detection area
            'rec_area_top' => 10,
            'rec_area_bottom' => 10,
            'rec_area_left' => 10,
            'rec_area_right' => 10,
            'outMargin' => 50, // Detection indent
        ]);
    }

    public function getCamshot(): string
    {
        if ($this->isLegacyVersion()) {
            return $this->apiCall('/image', 'GET', [], 5);
        }

        $context = stream_context_create([
            'http' => [
                'timeout' => 3.0,
            ],
        ]);

        $auth = base64_encode($this->login . ':' . $this->password);
        return file_get_contents("$this->url/snap.jpg?auth=$auth", false, $context);
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
        $dbConfig = $this->commonTransformDbConfig($dbConfig);

        if ($dbConfig['motionDetection']) {
            $dbConfig['motionDetection'] = [new DetectionZone(0, 0, 100, 100)];
        }

        return $dbConfig;
    }

    protected function getMotionDetectionConfig(): array
    {
        ['detection_mode' => $detectionMode] = $this->getConfiguration()['face_detection'];

        if ($detectionMode === 1) {
            return [new DetectionZone(0, 0, 100, 100)];
        }

        return [];
    }

    protected function getOsdText(): string
    {
        return $this->getConfiguration()['osd']['name'];
    }
}
