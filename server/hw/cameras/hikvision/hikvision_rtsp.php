<?php

    namespace hw\cameras {

        require_once __DIR__ . '/hikvision.php';

        class hikvision_rtsp extends hikvision {

            public function camshot(): string {
                $filename = uniqid('hikvision_');
                $host = parse_url($this->url, PHP_URL_HOST);
                $snapshotFile = "/tmp/$filename.jpg";
                $rtspUrl = "rtsp://$this->user:$this->pass@$host:554/Streaming/Channels/101";

                exec("ffmpeg -y -i $rtspUrl -vframes 1 $snapshotFile 2>&1", $output, $returnCode);

                if ($returnCode === 0 && file_exists($snapshotFile)) {
                    $shot = file_get_contents($snapshotFile);
                    unlink($snapshotFile);
                    return $shot;
                } else {
                    return '';
                }
            }
        }
    }
