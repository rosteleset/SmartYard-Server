<?php

namespace hw\ip\camera\hikvision;

/**
 * Class representing a Hikvision camera with a broken snapshot API.
 */
class hikvisionRtsp extends hikvision
{

    public function getCamshot(): string
    {
        $filename = uniqid('hikvision_');
        $host = parse_url($this->url, PHP_URL_HOST);
        $snapshotFile = "/tmp/$filename.jpg";
        $rtspUrl = "rtsp://$this->login:$this->password@$host:554/Streaming/Channels/101";

        exec("ffmpeg -y -i $rtspUrl -vframes 1 $snapshotFile 2>&1", $output, $returnCode);

        if ($returnCode === 0 && is_file($snapshotFile)) {
            $shot = file_get_contents($snapshotFile);
            unlink($snapshotFile);
            return $shot;
        } else {
            return '';
        }
    }
}
