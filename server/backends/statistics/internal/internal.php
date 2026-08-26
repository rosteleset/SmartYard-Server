<?php

    /**
     * backends statistics namespace
     */

    namespace backends\statistics {

        /**
         * internal statistics class
         */

        class internal extends statistics {

            /**
             * @inheritDoc
             */

            public function statistics() {
                $households = loadBackend("households");
                $cameras = loadBackend("cameras");
                $addresses = loadBackend("addresses");

                if (!$households || !$cameras) {
                    return false;
                }

                $houses = $addresses ? ($addresses->getHouses() ?: []) : [];

                $flatsById = [];
                $subscribersById = [];
                $flatsWithSubscriber = [];

                foreach ($houses as $house) {
                    $houseId = $house["houseId"];

                    foreach ($households->getFlats("houseId", $houseId) ?: [] as $flat) {
                        $flatsById[$flat["flatId"]] = $flat;
                    }

                    foreach ($households->getSubscribers("houseId", $houseId, [ "withoutHouses" ]) ?: [] as $subscriber) {
                        $subscribersById[$subscriber["subscriberId"]] = true;

                        foreach ($subscriber["flats"] ?: [] as $flatLink) {
                            if (isset($flatLink["flatId"])) {
                                $flatsWithSubscriber[$flatLink["flatId"]] = true;
                            }
                        }
                    }
                }

                $blockedFlats = 0;
                foreach ($flatsById as $flat) {
                    if ((int)$flat["manualBlock"] || (int)$flat["autoBlock"] || (int)$flat["adminBlock"]) {
                        $blockedFlats++;
                    }
                }

                $flatsWithSubscribers = 0;
                foreach ($flatsById as $flatId => $_) {
                    if (isset($flatsWithSubscriber[$flatId])) {
                        $flatsWithSubscribers++;
                    }
                }
                $flatsWithoutSubscribers = count($flatsById) - $flatsWithSubscribers;

                $devicesById = [];
                foreach (array_keys($subscribersById) as $subscriberId) {
                    foreach ($households->getDevices("subscriber", $subscriberId) ?: [] as $device) {
                        $devicesById[$device["deviceId"]] = $device;
                    }
                }

                $devicesAndroid = 0;
                $devicesIos = 0;
                $devicesWeb = 0;
                $devicesOther = 0;
                $devicesWithoutFlats = 0;

                foreach ($devicesById as $device) {
                    if (!is_array(@$device["flats"]) || !count($device["flats"])) {
                        $devicesWithoutFlats++;
                    }

                    if ($device["platform"] === null || $device["platform"] === "") {
                        $devicesOther++;
                        continue;
                    }

                    switch ((int)$device["platform"]) {
                        case 0:
                            $devicesAndroid++;
                            break;
                        case 1:
                            $devicesIos++;
                            break;
                        case 2:
                            $devicesWeb++;
                            break;
                        default:
                            $devicesOther++;
                            break;
                    }
                }

                $domophones = $households->getDomophones("all") ?: [];
                $cameraList = $cameras->getCameras() ?: [];

                $keysById = [];
                $addKeys = function ($keys) use (&$keysById) {
                    foreach ($keys ?: [] as $key) {
                        if (isset($key["keyId"])) {
                            $keysById[$key["keyId"]] = $key;
                        }
                    }
                };

                $addKeys($households->getKeys(0, 0));

                foreach ($flatsById as $flatId => $_) {
                    $addKeys($households->getKeys("flatId", $flatId));
                }

                foreach (array_keys($subscribersById) as $subscriberId) {
                    $addKeys($households->getKeys("subscriberId", $subscriberId));
                }

                foreach ($houses as $house) {
                    $houseId = $house["houseId"];
                    $addKeys($households->getKeys(4, $houseId));

                    foreach ($households->getEntrances("houseId", $houseId) ?: [] as $entrance) {
                        $addKeys($households->getKeys(3, $entrance["entranceId"]));
                    }
                }

                $companies = loadBackend("companies");
                if ($companies) {
                    foreach ($companies->getCompanies() ?: [] as $company) {
                        $addKeys($households->getKeys(5, $company["companyId"]));
                    }
                }

                $keysUniversal = 0;
                $keysSubscriber = 0;
                $keysFlat = 0;
                $keysEntrance = 0;
                $keysHouse = 0;
                $keysCompany = 0;

                foreach ($keysById as $key) {
                    switch ((int)$key["accessType"]) {
                        case 0:
                            $keysUniversal++;
                            break;
                        case 1:
                            $keysSubscriber++;
                            break;
                        case 2:
                            $keysFlat++;
                            break;
                        case 3:
                            $keysEntrance++;
                            break;
                        case 4:
                            $keysHouse++;
                            break;
                        case 5:
                            $keysCompany++;
                            break;
                    }
                }

                return [
                    "subscribers" => count($subscribersById),
                    "flats" => count($flatsById),
                    "flatsWithSubscribers" => $flatsWithSubscribers,
                    "flatsWithoutSubscribers" => $flatsWithoutSubscribers,
                    "blockedFlats" => $blockedFlats,
                    "domophones" => count($domophones),
                    "cameras" => count($cameraList),
                    "keys" => count($keysById),
                    "keysUniversal" => $keysUniversal,
                    "keysSubscriber" => $keysSubscriber,
                    "keysFlat" => $keysFlat,
                    "keysEntrance" => $keysEntrance,
                    "keysHouse" => $keysHouse,
                    "keysCompany" => $keysCompany,
                    "devices" => count($devicesById),
                    "devicesAndroid" => $devicesAndroid,
                    "devicesIos" => $devicesIos,
                    "devicesWeb" => $devicesWeb,
                    "devicesOther" => $devicesOther,
                    "devicesWithoutFlats" => $devicesWithoutFlats,
                ];
            }
        }
    }
