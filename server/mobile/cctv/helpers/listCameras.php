<?php

    $cameras = loadBackend("cameras");
    $houses = [];

    /**
     * Get stub url's from config
     * payment_require_url - stub if flat is blocked
     * service_url - stub if camera  disabled
     * fallback_url - stub if set not valid DVR url
     */
    $stub = $config['backends']['dvr']['stub'];

    /**
     * Replace DVR url handler.
     * - Flat is blocked - replace DVR stream url to paymentRequireUrl stub
     * - IP camera disabled in admin panel - replace DVR stream url to serviceUrl stub
     * - DVR stream invalid - replace DVR stream url to fallbackUrl stub
     * @param array $cams
     * @param bool $flatIsBlocked
     * @param string $paymentRequireUrl
     * @param string $serviceUrl
     * @param string $fallbackUrl
     * @return array
     */
    function replace_url(array $cams, bool $flatIsBlocked, string $paymentRequireUrl, string $serviceUrl, string $fallbackUrl ): array
    {
        $result = [];
        foreach ($cams as $cam) {
            if ($cam['enabled'] === 0) {
                $cam['dvrStream'] = $serviceUrl;
            }
            if (filter_var($cam['dvrStream'], FILTER_VALIDATE_URL) === false) {
                $cam['dvrStream'] = $fallbackUrl;
            }
            if ($flatIsBlocked ) {
                $cam['dvrStream'] = $paymentRequireUrl;
            }
            $result[] = $cam;
        }
        return $result;
    };

    foreach ($subscriber['flats'] as $flat) {
        $houseId = $flat['addressHouseId'];
        if ($house_id != $houseId && $house_id != 0)
            continue;

        $flatDetail = $households->getFlat($flat['flatId']);
        $flatIsBlock = $flatDetail['adminBlock'] || $flatDetail['manualBlock'] || $flatDetail['autoBlock'];

        if (array_key_exists($houseId, $houses)) {
            $house = &$houses[$houseId];

        } else {
            $houses[$houseId] = [];
            $house = &$houses[$houseId];
            $house['houseId'] = strval($houseId);
            $house['cameras'] = $households->getCameras("houseId", $houseId);
            $house['doors'] = [];
        }

        $house['cameras'] = array_merge($house['cameras'], $households->getCameras("flatId", $flat['flatId']));

        foreach ($flatDetail['entrances'] as $entrance) {
            if (array_key_exists($entrance['entranceId'], $house['doors'])) {
                continue;
            }

            $e = $households->getEntrance($entrance['entranceId']);
            $door = [];

            if ($e['cameraId']) {
                $cam = $cameras->getCamera($e["cameraId"]);
                $house['cameras'][] = $cam;
            }

            $house['doors'][$entrance['entranceId']] = $door;
        }

        if ($stub && $stub['payment_require_url'] && $stub['service_url'] && $stub['fallback_url']) {
            $house['cameras'] = replace_url(
                $house['cameras'],
                $flatIsBlock,
                $stub['payment_require_url'],
                $stub['service_url'],
                $stub['fallback_url']
            );
        }
    }

    $ret = [];

    foreach ($houses as $house_key => $h) {
        $houses[$house_key]['doors'] = array_values($h['doors']);
        unset($houses[$house_key]['cameras']);
        foreach($h['cameras'] as $camera) {
            $dvr = loadBackend("dvr")->getDVRServerForCam($camera);
            $item = [
                "id" => $camera['cameraId'],
                "path" => $camera['path'] ?? null,
                "name" => $camera['name'],
                "lat" => strval($camera['lat']),
                "url" => loadBackend("dvr")->getDVRStreamURLForCam($camera),
                "token" => loadBackend("dvr")->getDVRTokenForCam($camera, $subscriber['subscriberId']),
                "lon" => strval($camera['lon']),
                "serverType" => $dvr['type'],
                "hasSound" => boolval($camera['sound']),
            ];
            if (array_key_exists("hlsMode", $dvr)) {
                $item["hlsMode"] = $dvr["hlsMode"];
            }
            $ret[] = $item;
        }
    }
