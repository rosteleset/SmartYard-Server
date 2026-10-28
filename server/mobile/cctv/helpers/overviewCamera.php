<?php

    if (!function_exists("mobile_overview_camera_url")) {
        /**
         * DVR stream url of a city camera listed by cctv/overview
         *
         * @param array|false $camera camera from the cameras backend
         * @param object $dvr dvr backend
         * @return string|false url, false when the camera is not listed (not common, disabled or without a valid DVR url)
         */
        function mobile_overview_camera_url($camera, $dvr) {
            if (!$camera || !$camera["common"] || !$camera["enabled"]) {
                return false;
            }

            $url = $dvr->getDVRStreamURLForCam($camera);

            // skip not valid url
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                return false;
            }

            return $url;
        }
    }

    if (!function_exists("mobile_overview_camera")) {
        /**
         * city camera as it is listed by cctv/overview
         *
         * @param array $camera camera from the cameras backend
         * @param object $dvr dvr backend
         * @param int $subscriberId
         * @return array|false overview item, false when the camera is not listed (see mobile_overview_camera_url())
         */
        function mobile_overview_camera($camera, $dvr, $subscriberId) {
            $url = mobile_overview_camera_url($camera, $dvr);

            if (!$url) {
                return false;
            }

            $dvrServer = $dvr->getDVRServerForCam($camera);

            $item = [
                "id" => $camera["cameraId"],
                "name" => $camera["name"],
                "lat" => (string)$camera['lat'],
                "lon" => (string)$camera['lon'],
                "url" => $url,
                "token" => $dvr->getDVRTokenForCam($camera, $subscriberId),
                "serverType" => $dvrServer["type"],
                "hasSound" => (bool)$camera['sound'],
            ];

            if (array_key_exists("hlsMode", $dvrServer)) {
                $item["hlsMode"] = $dvrServer['hlsMode'];
            }

            return $item;
        }
    }
