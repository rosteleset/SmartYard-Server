<?php

namespace hw\ip\camera\beward;

use hw\ip\camera\camera;

/**
 * Class representing a Beward camera.
 */
class beward extends camera
{

    use \hw\ip\common\beward\beward;

    public function configureMotionDetection(
        int $left = 0,
        int $top = 0,
        int $width = 704,
        int $height = 576,
        int $sensitivity = 4
    )
    {
        $params = [
            'sens' => $sensitivity ? ($sensitivity - 1) : 0,
            'ckdetect' => $sensitivity ? '1' : '0',
            'ckevery' => $sensitivity ? '1' : '0',
            'ckevery2' => '0',
            'begh1' => '0',
            'begm1' => '0',
            'endh1' => 23,
            'endm1' => 59,
            'ckhttp' => '0',
            'alarmoutemail' => '0',
            'ckcap' => '0',
            'ckalarmrecdev' => '0',
        ];
        if ($left) {
            $params['nLeft1'] = $left;
        }
        if ($top) {
            $params['nTop1'] = $top;
        }
        if ($width) {
            $params['nWidth1'] = $width;
        }
        if ($height) {
            $params['nHeight1'] = $height;
        }
        $this->apiCall('webs/motionCfgEx', $params);
    }

    public function getCamshot(): string
    {
        return $this->apiCall('cgi-bin/images_cgi', ['channel' => 0]);
    }

    public function setOsdText(string $text = '')
    {
        $this->apiCall('cgi-bin/textoverlay_cgi', [
            'action' => 'set',
            'Title' => $text,
            'TitleValue' => $text ? 1 : 0,
            'DateValue' => 1,
            'TimeValue' => 1,
            'TimeFormat12' => 'False',
            'DateFormat' => 2,
            'WeekValue' => 1,
            'BitrateValue' => 0,
            'Color' => 0,
            'ClientNum' => 0,
        ]);
    }

    protected function getMotionDetectionConfig(): array
    {
        $md = $this->getParams('motion_cgi');

        return [
            // 'sensitivity' => $md['Sensitivity'] / 20,
            // Returns values in coordinates of the real current resolution
            'left' => $md['DetectArea0_x'],
            'top' => $md['DetectArea0_y'],
            'width' => $md['DetectArea0_w'],
            'height' => $md['DetectArea0_h'],
        ];
    }

    protected function getOsdText(): string
    {
        return $this->getParams('textoverlay_cgi')['Title'];
    }
}
