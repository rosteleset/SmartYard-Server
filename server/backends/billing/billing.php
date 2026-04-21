<?php

/**
 * backends billing namespace
 */

namespace backends\billing {

    use backends\backend;

    /**
     * base billing class
     */

    abstract class billing extends backend {

        /**
         * returns basic subscriber account info from billing by login/password
         *
         * @param $login subscriber login in billing
         * @param $password subscriber password in billing
         * @return false|array
         * false if request/auth/parsing failed (error details are available via setLastError/getLastError)
         *
        */

        public abstract function getSubscriberAccountInfo($login, $password);

        /**
         * returns additional services details from billing by login/password
         *
         * @param $login subscriber login in billing
         * @param $password subscriber password in billing
         * @param $agrmid agreement id in billing (required for getUsboxServices)
         * @return false|array
         * false if request/auth/parsing failed (error details are available via setLastError/getLastError)
         */

        public abstract function getSubscriberAdditionalServices($login, $password, $agrmid);

        /**
         * @param $items array of flat-contract links from billing
         * each item:
         * - houseId (required)
         * - flat (required)
         * - address (optional, debug)
         * - contract (required)
         * - login (optional)
         * - password (optional)
         * - autoBlock (optional, if set should be applied; if omitted should not be changed)
         *
         * @return boolean|array
         */
        function setContractsBindings($items) {
            if (!is_array($items)) {
                setLastError("invalidParams");
                return false;
            }

            $households = loadBackend("households");

            if (!$households) {
                return false;
            }

            $result = [
                "processed" => 0,
                "updated" => 0,
                "invalid" => 0,
                "notFound" => 0,
                "failed" => 0,
                "errors" => [],
            ];

            $houseFlats = [];

            foreach ($items as $index => $item) {
                $result["processed"]++;

                if (!is_array($item)) {
                    $result["invalid"]++;
                    $result["errors"][] = [
                        "index" => $index,
                        "error" => "invalidItem",
                    ];
                    continue;
                }

                $houseId = @$item["houseId"];
                $flat = @$item["flat"];
                $contract = @$item["contract"];

                if (!checkInt($houseId) || !$houseId || !checkStr($flat) || !$flat || !checkStr($contract) || !$contract) {
                    $result["invalid"]++;
                    $result["errors"][] = [
                        "index" => $index,
                        "error" => "invalidParams",
                        "houseId" => @$item["houseId"],
                        "flat" => @$item["flat"],
                        "address" => @$item["address"],
                        "contract" => @$item["contract"],
                    ];
                    continue;
                }

                if (!array_key_exists($houseId, $houseFlats)) {
                    $houseFlats[$houseId] = $households->getFlats("houseId", $houseId);

                    if (!is_array($houseFlats[$houseId])) {
                        $houseFlats[$houseId] = [];
                    }
                }

                $flatIds = [];

                foreach ($houseFlats[$houseId] as $houseFlat) {
                    $_flat = @$houseFlat["flat"];
                    $_flatId = @$houseFlat["flatId"];

                    if (!checkStr($_flat) || !checkInt($_flatId)) {
                        continue;
                    }

                    if ($_flat === $flat) {
                        $flatIds[] = $_flatId;
                    }
                }

                if (!count($flatIds)) {
                    $result["notFound"]++;
                    $result["errors"][] = [
                        "index" => $index,
                        "error" => "flatNotFound",
                        "houseId" => $houseId,
                        "flat" => $flat,
                        "address" => @$item["address"],
                        "contract" => $contract,
                    ];
                    continue;
                }

                foreach ($flatIds as $flatId) {
                    $params = [
                        "contract" => $contract,
                    ];

                    if (array_key_exists("autoBlock", $item)) {
                        $params["autoBlock"] = $item["autoBlock"];
                    }

                    if (array_key_exists("login", $item) || array_key_exists("password", $item)) {
                        $currentFlat = $households->getFlat($flatId);

                        if (!$currentFlat) {
                            $result["failed"]++;
                            $result["errors"][] = [
                                "index" => $index,
                                "error" => "cantGetFlat",
                                "flatId" => $flatId,
                                "houseId" => $houseId,
                                "flat" => $flat,
                                "address" => @$item["address"],
                                "contract" => $contract,
                            ];
                            continue;
                        }

                        $params["login"] = array_key_exists("login", $item) ? $item["login"] : @$currentFlat["login"];
                        $params["password"] = array_key_exists("password", $item) ? $item["password"] : @$currentFlat["password"];
                    }

                    if ($households->modifyFlat($flatId, $params) === false) {
                        $result["failed"]++;
                        $result["errors"][] = [
                            "index" => $index,
                            "error" => "cantModifyFlat",
                            "flatId" => $flatId,
                            "houseId" => $houseId,
                            "flat" => $flat,
                            "address" => @$item["address"],
                            "contract" => $contract,
                        ];
                        continue;
                    }

                    $result["updated"]++;
                }
            }

            return $result;
        }

        /**
         * imports address hierarchy from billing into RBT
         * required minimum is hierarchy up to house
         * optional: flats list for each house
         *
         * each item:
         * - regionUuid, region (required)
         * - areaUuid, area (optional)
         * - cityUuid, city (optional, at least one of area/city must be provided)
         * - settlementUuid, settlement (optional)
         * - streetUuid, street (optional, at least one of settlement/street must be provided)
         *   if settlement is omitted and street is provided, city is required
         * - houseUuid, house (required)
         * - houseFull (optional)
         * - services (optional) array of:
         *   "internet","iptv","ctv","phone","cctv","domophone","gsm"
         * - flats (optional) array of items:
         *   - flatNumber (required, string)
         *   - floor (optional)
         * - flatRanges (optional) array of numeric ranges:
         *   - fromFlat/toFlat (required, positive integers)
         *   - floor (optional)
         *
         * @return boolean|array
         */
        function importAddressHierarchy($items) {
            if (!is_array($items)) {
                setLastError("invalidParams");
                return false;
            }

            $addresses = loadBackend("addresses");
            $households = loadBackend("households");
            $customFields = loadBackend("customFields");

            if (!$addresses || !$households || !$customFields) {
                return false;
            }

            $allowedServices = [
                "internet",
                "iptv",
                "ctv",
                "phone",
                "cctv",
                "domophone",
                "gsm",
            ];

            $result = [
                "processed" => 0,
                "invalid" => 0,
                "failed" => 0,
                "created" => [
                    "regions" => 0,
                    "areas" => 0,
                    "cities" => 0,
                    "settlements" => 0,
                    "streets" => 0,
                    "houses" => 0,
                    "flats" => 0,
                ],
                "skipped" => [
                    "regions" => 0,
                    "areas" => 0,
                    "cities" => 0,
                    "settlements" => 0,
                    "streets" => 0,
                    "houses" => 0,
                    "flats" => 0,
                ],
                "servicesUpdated" => 0,
                "uuidMismatches" => 0,
                "errors" => [],
            ];

            $cache = [
                "regions" => null,
                "areas" => [],
                "cities" => [],
                "settlements" => [],
                "streets" => [],
                "houses" => [],
                "flats" => [],
            ];

            foreach ($items as $index => $item) {
                $result["processed"]++;

                if (!is_array($item)) {
                    $result["invalid"]++;
                    $result["errors"][] = [
                        "index" => $index,
                        "error" => "invalidItem",
                    ];
                    continue;
                }

                $regionUuid = @$item["regionUuid"];
                $region = @$item["region"];
                $areaUuid = @$item["areaUuid"];
                $area = @$item["area"];
                $cityUuid = @$item["cityUuid"];
                $city = @$item["city"];
                $settlementUuid = @$item["settlementUuid"];
                $settlement = @$item["settlement"];
                $streetUuid = @$item["streetUuid"];
                $street = @$item["street"];
                $houseUuid = @$item["houseUuid"];
                $house = @$item["house"];

                if (!checkStr($regionUuid) || $regionUuid === "" || !checkStr($region) || $region === "" ||
                    !checkStr($houseUuid) || $houseUuid === "" || !checkStr($house) || $house === "") {
                    $result["invalid"]++;
                    $result["errors"][] = [
                        "index" => $index,
                        "error" => "invalidRequiredFields",
                    ];
                    continue;
                }

                $hasArea = checkStr($areaUuid) && $areaUuid !== "" && checkStr($area) && $area !== "";
                $hasCity = checkStr($cityUuid) && $cityUuid !== "" && checkStr($city) && $city !== "";
                $hasSettlement = checkStr($settlementUuid) && $settlementUuid !== "" && checkStr($settlement) && $settlement !== "";
                $hasStreet = checkStr($streetUuid) && $streetUuid !== "" && checkStr($street) && $street !== "";

                if (!$hasArea && !$hasCity) {
                    $result["invalid"]++;
                    $result["errors"][] = [
                        "index" => $index,
                        "error" => "missingAreaOrCity",
                        "houseUuid" => $houseUuid,
                    ];
                    continue;
                }

                if (!$hasSettlement && !$hasStreet) {
                    $result["invalid"]++;
                    $result["errors"][] = [
                        "index" => $index,
                        "error" => "missingSettlementOrStreet",
                        "houseUuid" => $houseUuid,
                    ];
                    continue;
                }

                if ($hasStreet && !$hasSettlement && !$hasCity) {
                    $result["invalid"]++;
                    $result["errors"][] = [
                        "index" => $index,
                        "error" => "streetRequiresCityOrSettlement",
                        "houseUuid" => $houseUuid,
                    ];
                    continue;
                }

                $regionIsoCode = @$item["regionIsoCode"];
                $hasTimezone = array_key_exists("timezone", $item);
                $regionWithType = @$item["regionWithType"];
                $regionType = @$item["regionType"];
                $regionTypeFull = @$item["regionTypeFull"];
                $timezone = @$item["timezone"];

                if (!checkStr($regionIsoCode)) {
                    $regionIsoCode = "";
                }
                if (!checkStr($regionWithType) || $regionWithType === "") {
                    $regionWithType = $region;
                }
                if (!checkStr($regionType)) {
                    $regionType = "";
                }
                if (!checkStr($regionTypeFull)) {
                    $regionTypeFull = "";
                }
                if (!checkStr($timezone) || $timezone === "") {
                    $timezone = "-";
                }

                $areaWithType = @$item["areaWithType"];
                $areaType = @$item["areaType"];
                $areaTypeFull = @$item["areaTypeFull"];
                if (!checkStr($areaWithType) || $areaWithType === "") {
                    $areaWithType = $hasArea ? $area : "";
                }
                if (!checkStr($areaType)) {
                    $areaType = "";
                }
                if (!checkStr($areaTypeFull)) {
                    $areaTypeFull = "";
                }

                $cityWithType = @$item["cityWithType"];
                $cityType = @$item["cityType"];
                $cityTypeFull = @$item["cityTypeFull"];
                if (!checkStr($cityWithType) || $cityWithType === "") {
                    $cityWithType = $hasCity ? $city : "";
                }
                if (!checkStr($cityType)) {
                    $cityType = "";
                }
                if (!checkStr($cityTypeFull)) {
                    $cityTypeFull = "";
                }

                $settlementWithType = @$item["settlementWithType"];
                $settlementType = @$item["settlementType"];
                $settlementTypeFull = @$item["settlementTypeFull"];
                if (!checkStr($settlementWithType) || $settlementWithType === "") {
                    $settlementWithType = $hasSettlement ? $settlement : "";
                }
                if (!checkStr($settlementType)) {
                    $settlementType = "";
                }
                if (!checkStr($settlementTypeFull)) {
                    $settlementTypeFull = "";
                }

                $streetWithType = @$item["streetWithType"];
                $streetType = @$item["streetType"];
                $streetTypeFull = @$item["streetTypeFull"];
                if (!checkStr($streetWithType) || $streetWithType === "") {
                    $streetWithType = $hasStreet ? $street : "";
                }
                if (!checkStr($streetType)) {
                    $streetType = "";
                }
                if (!checkStr($streetTypeFull)) {
                    $streetTypeFull = "";
                }

                $houseType = @$item["houseType"];
                $houseTypeFull = @$item["houseTypeFull"];
                $houseFull = @$item["houseFull"];
                $hasCompanyId = array_key_exists("companyId", $item);
                $companyId = $hasCompanyId ? $item["companyId"] : 0;

                if (!checkStr($houseType)) {
                    $houseType = "";
                }
                if (!checkStr($houseTypeFull)) {
                    $houseTypeFull = "";
                }
                if (!checkStr($houseFull) || $houseFull === "") {
                    $houseFull = $house;
                }
                if ($companyId === null || $companyId === "") {
                    $companyId = 0;
                }
                if (!checkInt($companyId)) {
                    $result["invalid"]++;
                    $result["errors"][] = [
                        "index" => $index,
                        "error" => "invalidCompanyId",
                        "houseUuid" => $houseUuid,
                    ];
                    continue;
                }

                $services = null;
                if (array_key_exists("services", $item)) {
                    if (!is_array($item["services"])) {
                        $result["invalid"]++;
                        $result["errors"][] = [
                            "index" => $index,
                            "error" => "invalidServices",
                            "houseUuid" => $houseUuid,
                        ];
                        continue;
                    }

                    $services = [];

                    foreach ($item["services"] as $service) {
                        if (!checkStr($service) || $service === "") {
                            $result["invalid"]++;
                            $result["errors"][] = [
                                "index" => $index,
                                "error" => "invalidServiceValue",
                                "houseUuid" => $houseUuid,
                            ];
                            continue 2;
                        }

                        $service = mb_strtolower($service);

                        if (!in_array($service, $allowedServices, true)) {
                            $result["invalid"]++;
                            $result["errors"][] = [
                                "index" => $index,
                                "error" => "unknownService",
                                "houseUuid" => $houseUuid,
                                "service" => $service,
                            ];
                            continue 2;
                        }

                        $services[$service] = $service;
                    }

                    $services = array_values($services);
                    sort($services, SORT_STRING);
                }

                if ($cache["regions"] === null) {
                    $cache["regions"] = $addresses->getRegions();

                    if (!is_array($cache["regions"])) {
                        $result["failed"]++;
                        $result["errors"][] = [
                            "index" => $index,
                            "error" => "cantGetRegions",
                        ];
                        continue;
                    }
                }

                $regionMatch = $this->findAddressItemMatch($cache["regions"], "regionUuid", $regionUuid, "region", $region);

                if ($regionMatch === false) {
                    $regionId = $addresses->addRegion($regionUuid, $regionIsoCode, $regionWithType, $regionType, $regionTypeFull, $region, $timezone);

                    if ($regionId === false || !checkInt($regionId) || !$regionId) {
                        $result["failed"]++;
                        $result["errors"][] = [
                            "index" => $index,
                            "error" => "cantAddRegion",
                            "regionUuid" => $regionUuid,
                            "region" => $region,
                        ];
                        continue;
                    }

                    $regionRow = $addresses->getRegion($regionId);

                    if (!is_array($regionRow)) {
                        $result["failed"]++;
                        $result["errors"][] = [
                            "index" => $index,
                            "error" => "cantGetRegion",
                            "regionId" => $regionId,
                        ];
                        continue;
                    }

                    $cache["regions"][] = $regionRow;
                    $result["created"]["regions"]++;
                } else {
                    $regionRow = $regionMatch["row"];
                    $regionId = @$regionRow["regionId"];

                    if (!checkInt($regionId) || !$regionId) {
                        $result["failed"]++;
                        $result["errors"][] = [
                            "index" => $index,
                            "error" => "invalidRegionId",
                            "regionUuid" => $regionUuid,
                        ];
                        continue;
                    }

                    $result["skipped"]["regions"]++;

                    if ($regionMatch["by"] === "name") {
                        $result["uuidMismatches"]++;
                        $result["errors"][] = [
                            "index" => $index,
                            "error" => "regionUuidMismatch",
                            "expectedUuid" => $regionUuid,
                            "actualUuid" => @$regionRow["regionUuid"],
                            "regionId" => $regionId,
                        ];
                    }

                    if ($hasTimezone) {
                        $updatedRegionRow = $this->syncImportAddressExistingTimezone($addresses, "region", $regionRow, $timezone, $result, $index);
                        if ($updatedRegionRow === false) {
                            continue;
                        }

                        $regionRow = $updatedRegionRow;

                        foreach ($cache["regions"] as $regionCacheIndex => $regionCacheRow) {
                            if ((int)@$regionCacheRow["regionId"] === (int)$regionId) {
                                $cache["regions"][$regionCacheIndex] = $regionRow;
                                break;
                            }
                        }
                    }
                }

                $areaId = 0;
                if ($hasArea) {
                    if (!array_key_exists($regionId, $cache["areas"])) {
                        $cache["areas"][$regionId] = $addresses->getAreas($regionId);

                        if (!is_array($cache["areas"][$regionId])) {
                            $result["failed"]++;
                            $result["errors"][] = [
                                "index" => $index,
                                "error" => "cantGetAreas",
                                "regionId" => $regionId,
                            ];
                            continue;
                        }
                    }

                    $areaMatch = $this->findAddressItemMatch($cache["areas"][$regionId], "areaUuid", $areaUuid, "area", $area);

                    if ($areaMatch === false) {
                        $areaId = $addresses->addArea($regionId, $areaUuid, $areaWithType, $areaType, $areaTypeFull, $area, $timezone);

                        if ($areaId === false || !checkInt($areaId) || !$areaId) {
                            $result["failed"]++;
                            $result["errors"][] = [
                                "index" => $index,
                                "error" => "cantAddArea",
                                "areaUuid" => $areaUuid,
                                "area" => $area,
                                "regionId" => $regionId,
                            ];
                            continue;
                        }

                        $areaRow = $addresses->getArea($areaId);

                        if (!is_array($areaRow)) {
                            $result["failed"]++;
                            $result["errors"][] = [
                                "index" => $index,
                                "error" => "cantGetArea",
                                "areaId" => $areaId,
                            ];
                            continue;
                        }

                        $cache["areas"][$regionId][] = $areaRow;
                        $result["created"]["areas"]++;
                    } else {
                        $areaRow = $areaMatch["row"];
                        $areaId = @$areaRow["areaId"];

                        if (!checkInt($areaId) || !$areaId) {
                            $result["failed"]++;
                            $result["errors"][] = [
                                "index" => $index,
                                "error" => "invalidAreaId",
                                "areaUuid" => $areaUuid,
                            ];
                            continue;
                        }

                        $result["skipped"]["areas"]++;

                        if ($areaMatch["by"] === "name") {
                            $result["uuidMismatches"]++;
                            $result["errors"][] = [
                                "index" => $index,
                                "error" => "areaUuidMismatch",
                                "expectedUuid" => $areaUuid,
                                "actualUuid" => @$areaRow["areaUuid"],
                                "areaId" => $areaId,
                            ];
                        }

                        if ($hasTimezone) {
                            $updatedAreaRow = $this->syncImportAddressExistingTimezone($addresses, "area", $areaRow, $timezone, $result, $index);
                            if ($updatedAreaRow === false) {
                                continue;
                            }

                            $areaRow = $updatedAreaRow;

                            foreach ($cache["areas"][$regionId] as $areaCacheIndex => $areaCacheRow) {
                                if ((int)@$areaCacheRow["areaId"] === (int)$areaId) {
                                    $cache["areas"][$regionId][$areaCacheIndex] = $areaRow;
                                    break;
                                }
                            }
                        }
                    }
                }

                $cityId = 0;
                if ($hasCity) {
                    $citiesKey = $areaId ? "area:$areaId" : "region:$regionId";

                    if (!array_key_exists($citiesKey, $cache["cities"])) {
                        $cache["cities"][$citiesKey] = $areaId ? $addresses->getCities(false, $areaId) : $addresses->getCities($regionId, false);

                        if (!is_array($cache["cities"][$citiesKey])) {
                            $result["failed"]++;
                            $result["errors"][] = [
                                "index" => $index,
                                "error" => "cantGetCities",
                                "parent" => $citiesKey,
                            ];
                            continue;
                        }
                    }

                    $cityMatch = $this->findAddressItemMatch($cache["cities"][$citiesKey], "cityUuid", $cityUuid, "city", $city);

                    if ($cityMatch === false) {
                        $cityId = $addresses->addCity($areaId ? 0 : $regionId, $areaId ?: 0, $cityUuid, $cityWithType, $cityType, $cityTypeFull, $city, $timezone);

                        if ($cityId === false || !checkInt($cityId) || !$cityId) {
                            $result["failed"]++;
                            $result["errors"][] = [
                                "index" => $index,
                                "error" => "cantAddCity",
                                "cityUuid" => $cityUuid,
                                "city" => $city,
                                "parent" => $citiesKey,
                            ];
                            continue;
                        }

                        $cityRow = $addresses->getCity($cityId);

                        if (!is_array($cityRow)) {
                            $result["failed"]++;
                            $result["errors"][] = [
                                "index" => $index,
                                "error" => "cantGetCity",
                                "cityId" => $cityId,
                            ];
                            continue;
                        }

                        $cache["cities"][$citiesKey][] = $cityRow;
                        $result["created"]["cities"]++;
                    } else {
                        $cityRow = $cityMatch["row"];
                        $cityId = @$cityRow["cityId"];

                        if (!checkInt($cityId) || !$cityId) {
                            $result["failed"]++;
                            $result["errors"][] = [
                                "index" => $index,
                                "error" => "invalidCityId",
                                "cityUuid" => $cityUuid,
                            ];
                            continue;
                        }

                        $result["skipped"]["cities"]++;

                        if ($cityMatch["by"] === "name") {
                            $result["uuidMismatches"]++;
                            $result["errors"][] = [
                                "index" => $index,
                                "error" => "cityUuidMismatch",
                                "expectedUuid" => $cityUuid,
                                "actualUuid" => @$cityRow["cityUuid"],
                                "cityId" => $cityId,
                            ];
                        }

                        if ($hasTimezone) {
                            $updatedCityRow = $this->syncImportAddressExistingTimezone($addresses, "city", $cityRow, $timezone, $result, $index);
                            if ($updatedCityRow === false) {
                                continue;
                            }

                            $cityRow = $updatedCityRow;

                            foreach ($cache["cities"][$citiesKey] as $cityCacheIndex => $cityCacheRow) {
                                if ((int)@$cityCacheRow["cityId"] === (int)$cityId) {
                                    $cache["cities"][$citiesKey][$cityCacheIndex] = $cityRow;
                                    break;
                                }
                            }
                        }
                    }
                }

                $settlementId = 0;
                if ($hasSettlement) {
                    $settlementsKey = $cityId ? "city:$cityId" : "area:$areaId";

                    if (!array_key_exists($settlementsKey, $cache["settlements"])) {
                        $cache["settlements"][$settlementsKey] = $cityId ? $addresses->getSettlements(false, $cityId) : $addresses->getSettlements($areaId, false);

                        if (!is_array($cache["settlements"][$settlementsKey])) {
                            $result["failed"]++;
                            $result["errors"][] = [
                                "index" => $index,
                                "error" => "cantGetSettlements",
                                "parent" => $settlementsKey,
                            ];
                            continue;
                        }
                    }

                    $settlementMatch = $this->findAddressItemMatch($cache["settlements"][$settlementsKey], "settlementUuid", $settlementUuid, "settlement", $settlement);

                    if ($settlementMatch === false) {
                        $settlementId = $addresses->addSettlement($cityId ? 0 : $areaId, $cityId ?: 0, $settlementUuid, $settlementWithType, $settlementType, $settlementTypeFull, $settlement);

                        if ($settlementId === false || !checkInt($settlementId) || !$settlementId) {
                            $result["failed"]++;
                            $result["errors"][] = [
                                "index" => $index,
                                "error" => "cantAddSettlement",
                                "settlementUuid" => $settlementUuid,
                                "settlement" => $settlement,
                                "parent" => $settlementsKey,
                            ];
                            continue;
                        }

                        $settlementRow = $addresses->getSettlement($settlementId);

                        if (!is_array($settlementRow)) {
                            $result["failed"]++;
                            $result["errors"][] = [
                                "index" => $index,
                                "error" => "cantGetSettlement",
                                "settlementId" => $settlementId,
                            ];
                            continue;
                        }

                        $cache["settlements"][$settlementsKey][] = $settlementRow;
                        $result["created"]["settlements"]++;
                    } else {
                        $settlementRow = $settlementMatch["row"];
                        $settlementId = @$settlementRow["settlementId"];

                        if (!checkInt($settlementId) || !$settlementId) {
                            $result["failed"]++;
                            $result["errors"][] = [
                                "index" => $index,
                                "error" => "invalidSettlementId",
                                "settlementUuid" => $settlementUuid,
                            ];
                            continue;
                        }

                        $result["skipped"]["settlements"]++;

                        if ($settlementMatch["by"] === "name") {
                            $result["uuidMismatches"]++;
                            $result["errors"][] = [
                                "index" => $index,
                                "error" => "settlementUuidMismatch",
                                "expectedUuid" => $settlementUuid,
                                "actualUuid" => @$settlementRow["settlementUuid"],
                                "settlementId" => $settlementId,
                            ];
                        }
                    }
                }

                $streetId = 0;
                if ($hasStreet) {
                    $streetsKey = $settlementId ? "settlement:$settlementId" : "city:$cityId";

                    if (!array_key_exists($streetsKey, $cache["streets"])) {
                        $cache["streets"][$streetsKey] = $settlementId ? $addresses->getStreets(false, $settlementId) : $addresses->getStreets($cityId, false);

                        if (!is_array($cache["streets"][$streetsKey])) {
                            $result["failed"]++;
                            $result["errors"][] = [
                                "index" => $index,
                                "error" => "cantGetStreets",
                                "parent" => $streetsKey,
                            ];
                            continue;
                        }
                    }

                    $streetMatch = $this->findAddressItemMatch($cache["streets"][$streetsKey], "streetUuid", $streetUuid, "street", $street);

                    if ($streetMatch === false) {
                        $streetId = $addresses->addStreet($settlementId ? 0 : $cityId, $settlementId ?: 0, $streetUuid, $streetWithType, $streetType, $streetTypeFull, $street);

                        if ($streetId === false || !checkInt($streetId) || !$streetId) {
                            $result["failed"]++;
                            $result["errors"][] = [
                                "index" => $index,
                                "error" => "cantAddStreet",
                                "streetUuid" => $streetUuid,
                                "street" => $street,
                                "parent" => $streetsKey,
                            ];
                            continue;
                        }

                        $streetRow = $addresses->getStreet($streetId);

                        if (!is_array($streetRow)) {
                            $result["failed"]++;
                            $result["errors"][] = [
                                "index" => $index,
                                "error" => "cantGetStreet",
                                "streetId" => $streetId,
                            ];
                            continue;
                        }

                        $cache["streets"][$streetsKey][] = $streetRow;
                        $result["created"]["streets"]++;
                    } else {
                        $streetRow = $streetMatch["row"];
                        $streetId = @$streetRow["streetId"];

                        if (!checkInt($streetId) || !$streetId) {
                            $result["failed"]++;
                            $result["errors"][] = [
                                "index" => $index,
                                "error" => "invalidStreetId",
                                "streetUuid" => $streetUuid,
                            ];
                            continue;
                        }

                        $result["skipped"]["streets"]++;

                        if ($streetMatch["by"] === "name") {
                            $result["uuidMismatches"]++;
                            $result["errors"][] = [
                                "index" => $index,
                                "error" => "streetUuidMismatch",
                                "expectedUuid" => $streetUuid,
                                "actualUuid" => @$streetRow["streetUuid"],
                                "streetId" => $streetId,
                            ];
                        }
                    }
                }

                $housesKey = $streetId ? "street:$streetId" : "settlement:$settlementId";

                if (!array_key_exists($housesKey, $cache["houses"])) {
                    $cache["houses"][$housesKey] = $streetId ? $addresses->getHouses(false, $streetId) : $addresses->getHouses($settlementId, false);

                    if (!is_array($cache["houses"][$housesKey])) {
                        $result["failed"]++;
                        $result["errors"][] = [
                            "index" => $index,
                            "error" => "cantGetHouses",
                            "parent" => $housesKey,
                        ];
                        continue;
                    }
                }

                $houseMatch = $this->findAddressItemMatch($cache["houses"][$housesKey], "houseUuid", $houseUuid, "house", $house);

                if ($houseMatch === false) {
                    $houseId = $addresses->addHouse($streetId ? 0 : $settlementId, $streetId ?: 0, $houseUuid, $houseType, $houseTypeFull, $houseFull, $house, $companyId);

                    if ($houseId === false || !checkInt($houseId) || !$houseId) {
                        $result["failed"]++;
                        $result["errors"][] = [
                            "index" => $index,
                            "error" => "cantAddHouse",
                            "houseUuid" => $houseUuid,
                            "house" => $house,
                            "parent" => $housesKey,
                        ];
                        continue;
                    }

                    $houseRow = $addresses->getHouse($houseId);

                    if (!is_array($houseRow)) {
                        $result["failed"]++;
                        $result["errors"][] = [
                            "index" => $index,
                            "error" => "cantGetHouse",
                            "houseId" => $houseId,
                        ];
                        continue;
                    }

                    $cache["houses"][$housesKey][] = $houseRow;
                    $result["created"]["houses"]++;
                } else {
                    $houseRow = $houseMatch["row"];
                    $houseId = @$houseRow["houseId"];

                    if (!checkInt($houseId) || !$houseId) {
                        $result["failed"]++;
                        $result["errors"][] = [
                            "index" => $index,
                            "error" => "invalidHouseId",
                            "houseUuid" => $houseUuid,
                        ];
                        continue;
                    }

                    $result["skipped"]["houses"]++;

                    if ($houseMatch["by"] === "name") {
                        $result["uuidMismatches"]++;
                        $result["errors"][] = [
                            "index" => $index,
                            "error" => "houseUuidMismatch",
                            "expectedUuid" => $houseUuid,
                            "actualUuid" => @$houseRow["houseUuid"],
                            "houseId" => $houseId,
                        ];
                    }

                    if ($hasCompanyId && (int)@$houseRow["companyId"] !== (int)$companyId) {
                        if ($addresses->modifyHouse(
                            $houseId,
                            @$houseRow["settlementId"],
                            @$houseRow["streetId"],
                            @$houseRow["houseUuid"],
                            @$houseRow["houseType"],
                            @$houseRow["houseTypeFull"],
                            @$houseRow["houseFull"],
                            @$houseRow["house"],
                            $companyId
                        ) === false) {
                            $result["failed"]++;
                            $result["errors"][] = [
                                "index" => $index,
                                "error" => "cantModifyHouseCompanyId",
                                "houseId" => $houseId,
                                "houseUuid" => $houseUuid,
                                "companyId" => $companyId,
                            ];
                            continue;
                        }

                        $houseRow = $addresses->getHouse($houseId);

                        if (!is_array($houseRow)) {
                            $result["failed"]++;
                            $result["errors"][] = [
                                "index" => $index,
                                "error" => "cantGetHouseAfterCompanyIdUpdate",
                                "houseId" => $houseId,
                                "houseUuid" => $houseUuid,
                            ];
                            continue;
                        }

                        foreach ($cache["houses"][$housesKey] as $houseCacheIndex => $houseCacheRow) {
                            if ((int)@$houseCacheRow["houseId"] === (int)$houseId) {
                                $cache["houses"][$housesKey][$houseCacheIndex] = $houseRow;
                                break;
                            }
                        }
                    }
                }

                if ($services !== null) {
                    $servicesValue = implode(",", $services);

                    if ($customFields->modifyValues("house", $houseId, [
                        "services" => $servicesValue,
                    ], "patch") === false) {
                        $result["failed"]++;
                        $result["errors"][] = [
                            "index" => $index,
                            "error" => "cantModifyHouseServices",
                            "houseId" => $houseId,
                            "houseUuid" => $houseUuid,
                        ];
                    } else {
                        $result["servicesUpdated"]++;
                    }
                }

                $flatItems = $this->normalizeImportAddressFlatItems($item, $index, $houseId, $houseUuid, $result);
                if ($flatItems === false) {
                    continue;
                }

                if ($flatItems !== null) {
                    if (!array_key_exists($houseId, $cache["flats"])) {
                        $cache["flats"][$houseId] = $households->getFlats("houseId", $houseId);

                        if (!is_array($cache["flats"][$houseId])) {
                            $result["failed"]++;
                            $result["errors"][] = [
                                "index" => $index,
                                "error" => "cantGetFlats",
                                "houseId" => $houseId,
                            ];
                            continue;
                        }
                    }

                    $houseFlatNumbers = [];
                    foreach ($cache["flats"][$houseId] as $houseFlat) {
                        $_flat = @$houseFlat["flat"];
                        if (checkStr($_flat) && $_flat !== "") {
                            $houseFlatNumbers[$_flat] = $_flat;
                        }
                    }

                    foreach ($flatItems as $flatIndex => $flatItem) {
                        $flatNumber = $flatItem["flatNumber"];
                        $floor = $flatItem["floor"];

                        if (array_key_exists($flatNumber, $houseFlatNumbers)) {
                            $result["skipped"]["flats"]++;
                            continue;
                        }

                        $flatId = $households->addFlat($houseId, $floor, $flatNumber, "", [], [], 0, 0, false, 0, 0, 0, 0, "", "");

                        if ($flatId === false || !checkInt($flatId) || !$flatId) {
                            $result["failed"]++;
                            $result["errors"][] = [
                                "index" => $index,
                                "flatIndex" => $flatIndex,
                                "error" => "cantAddFlat",
                                "houseId" => $houseId,
                                "houseUuid" => $houseUuid,
                                "flat" => $flatNumber,
                            ];
                            continue;
                        }

                        $houseFlatNumbers[$flatNumber] = $flatNumber;
                        $result["created"]["flats"]++;
                    }

                    $cache["flats"][$houseId] = $households->getFlats("houseId", $houseId);
                }
            }

            return $result;
        }

        private function normalizeImportAddressFlatItems($item, $index, $houseId, $houseUuid, &$result) {
            $hasFlats = array_key_exists("flats", $item);
            $hasFlatRanges = array_key_exists("flatRanges", $item);

            if (!$hasFlats && !$hasFlatRanges) {
                return null;
            }

            if ($hasFlats && !is_array($item["flats"])) {
                $result["invalid"]++;
                $result["errors"][] = [
                    "index" => $index,
                    "error" => "invalidFlats",
                    "houseId" => $houseId,
                    "houseUuid" => $houseUuid,
                ];
                return false;
            }

            if ($hasFlatRanges && !is_array($item["flatRanges"])) {
                $result["invalid"]++;
                $result["errors"][] = [
                    "index" => $index,
                    "error" => "invalidFlatRanges",
                    "houseId" => $houseId,
                    "houseUuid" => $houseUuid,
                ];
                return false;
            }

            $flatItems = [];

            if ($hasFlats) {
                foreach ($item["flats"] as $flatIndex => $flatItem) {
                    $normalizedFlat = $this->normalizeImportAddressFlatItem($flatItem);

                    if ($normalizedFlat === false) {
                        $result["failed"]++;
                        $result["errors"][] = [
                            "index" => $index,
                            "flatIndex" => $flatIndex,
                            "error" => "invalidFlat",
                            "houseId" => $houseId,
                            "houseUuid" => $houseUuid,
                        ];
                        continue;
                    }

                    $flatItems[] = $normalizedFlat;
                }
            }

            if ($hasFlatRanges) {
                foreach ($item["flatRanges"] as $rangeIndex => $rangeItem) {
                    $expandedRange = $this->expandImportAddressFlatRange($rangeItem);

                    if ($expandedRange === false) {
                        $result["failed"]++;
                        $result["errors"][] = [
                            "index" => $index,
                            "flatRangeIndex" => $rangeIndex,
                            "error" => "invalidFlatRange",
                            "houseId" => $houseId,
                            "houseUuid" => $houseUuid,
                        ];
                        continue;
                    }

                    foreach ($expandedRange as $expandedFlat) {
                        $flatItems[] = $expandedFlat;
                    }
                }
            }

            return $flatItems;
        }

        private function normalizeImportAddressFlatItem($flatItem) {
            if (!is_array($flatItem)) {
                return false;
            }

            $flatNumber = @$flatItem["flatNumber"];
            $floor = array_key_exists("floor", $flatItem) ? $flatItem["floor"] : 0;

            if (!checkStr($flatNumber) || $flatNumber === "") {
                return false;
            }

            if (!checkInt($floor)) {
                $floor = 0;
            }

            return [
                "flatNumber" => $flatNumber,
                "floor" => (int)$floor,
            ];
        }

        private function expandImportAddressFlatRange($rangeItem) {
            $from = null;
            $to = null;
            $floor = 0;

            if (is_array($rangeItem)) {
                $from = @$rangeItem["fromFlat"];
                $to = @$rangeItem["toFlat"];
                $floor = array_key_exists("floor", $rangeItem) ? $rangeItem["floor"] : 0;
            }

            $from = $this->parseImportAddressFlatRangeBound($from);
            $to = $this->parseImportAddressFlatRangeBound($to);

            if ($from === null || $to === null || $from > $to) {
                return false;
            }

            if (!checkInt($floor)) {
                $floor = 0;
            }

            $flatItems = [];
            for ($flatNumber = $from; $flatNumber <= $to; $flatNumber++) {
                $flatItems[] = [
                    "flatNumber" => (string)$flatNumber,
                    "floor" => (int)$floor,
                ];
            }

            return $flatItems;
        }

        private function parseImportAddressFlatRangeBound($value) {
            if (checkInt($value) && (int)$value > 0) {
                return (int)$value;
            }

            if (checkStr($value)) {
                $value = trim($value);
                if (preg_match('/^[1-9][0-9]*$/', $value)) {
                    return (int)$value;
                }
            }

            return null;
        }

        /**
         * full subscribers sync for flats contract/autoBlock/customFields
         *
         * @param $subscribers array of subscribers
         * each item:
         * - isActive (optional, bool/int, if true -> autoBlock = 0, else autoBlock = 1)
         * - subscriberID (optional, numeric, used for contract lookup and flat.contract update)
         * - buildingUUID + flatNumber (optional pair, used for direct flat lookup)
         *   at least one lookup option is required:
         *   - subscriberID
         *   - both buildingUUID and flatNumber
         * - agreement (optional, string, stored to custom field agreement only if provided and the flat is resolved either by buildingUUID + flatNumber or by unique subscriberID lookup)
         * - addressText (optional, string, stored to custom field addressText only if provided and the flat is resolved either by buildingUUID + flatNumber or by unique subscriberID lookup)
         * - login (optional, string, stored to flat.login)
         * - password (optional, string, stored to flat.password)
         * - phones (optional, array of objects: phone + type=owner|regular, linked to flat in RBT)
         * if isActive is omitted, phones must be provided and autoBlock is left unchanged
         * @param $defaultAction string
         * values:
         * - "skipMissing" (default): do not change subscribers not in list
         * - "blockMissing": block subscribers not in list (autoBlock = 1)
         * - "unblockMissing": unblock subscribers not in list (autoBlock = 0)
         *
         * @return boolean|array
         */

        function syncAutoBlockByContracts($subscribers, $defaultAction = "skipMissing") {
            // Step 1: validate input contract for sync mode.
            if (!is_array($subscribers) || !is_string($defaultAction) || !in_array($defaultAction, [ "skipMissing", "blockMissing", "unblockMissing" ], true)) {
                setLastError("invalidParams");
                return false;
            }

            // Step 2: load dependencies required for flat search/update.
            $households = loadBackend("households");
            $customFields = null;

            if (!$households) {
                return false;
            }

            $result = [
                "processed" => 0,
                "updated" => 0,
                "invalid" => 0,
                "notFound" => 0,
                "failed" => 0,
                "defaultAction" => $defaultAction,
                "missing" => [
                    "updated" => 0,
                    "unchanged" => 0,
                    "failed" => 0,
                ],
                "errors" => [],
            ];

            $normalizedSubscribers = [];
            $pairs = [];
            $contracts = [];
            $hasSubscribersWithoutContract = false;

            // Step 3: normalize each subscriber and build lookup indexes.
            foreach ($subscribers as $index => $subscriber) {
                $result["processed"]++;
                $normalized = $this->normalizeSyncSubscriberItem(
                    $index,
                    $subscriber,
                    $result,
                    $pairs,
                    $contracts,
                    $hasSubscribersWithoutContract
                );

                if ($normalized !== false) {
                    $normalizedSubscribers[] = $normalized;
                }
            }

            if (count($normalizedSubscribers)) {
                // Step 4: preload flats by (houseUUID, flatNumber) pair for batch processing.
                $flatByPair = $this->buildSyncFlatByPairMap($households, $pairs, $result);

                foreach ($normalizedSubscribers as $subscriber) {
                    // Step 5: resolve target flat (primary by pair, fallback by contract).
                    $flat = null;
                    if ($subscriber["hasPair"]) {
                        $pairKey = $subscriber["pairKey"];
                        $flat = @$flatByPair[$pairKey];
                    }

                    if (!is_array($flat) || !count($flat)) {
                        $fallbackFlatId = $this->resolveSyncFallbackFlatIdByContract($households, $subscriber, $result);

                        if ($fallbackFlatId !== null) {
                            $resolvedByContractOnly = !$subscriber["hasPair"];
                            if (!$resolvedByContractOnly) {
                                $warningContext = [
                                    "index" => $subscriber["index"],
                                    "subscriber" => is_array(@$subscribers[$subscriber["index"]]) ? $subscribers[$subscriber["index"]] : $subscriber,
                                    "resolvedFlatId" => $fallbackFlatId,
                                ];

                                if ($subscriber["hasIsActive"]) {
                                    $warningContext["targetAutoBlock"] = $subscriber["isActive"] ? 0 : 1;
                                }

                                error_log("[billing/syncAutoBlockByContracts] warning: fallback flat resolved by contract instead of houseUUID+flatNumber " . json_encode([
                                    "index" => $warningContext["index"],
                                    "subscriber" => $warningContext["subscriber"],
                                    "resolvedFlatId" => $warningContext["resolvedFlatId"],
                                ] + ($subscriber["hasIsActive"] ? [ "targetAutoBlock" => $warningContext["targetAutoBlock"] ] : []), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                            }

                            if (!$this->applySyncSubscriberToFlat($households, $customFields, $fallbackFlatId, $subscriber, $result, [
                                "cantModifyFlatError" => $resolvedByContractOnly ? "cantModifyFlat" : "cantModifyFlatFallbackByContract",
                                "includeTargetAutoBlock" => (!$resolvedByContractOnly && $subscriber["hasIsActive"]),
                                "updateContract" => $resolvedByContractOnly && $subscriber["hasSubscriberID"],
                                "updateCustomFields" => $resolvedByContractOnly,
                            ])) {
                                continue;
                            }

                            $result["updated"]++;
                            continue;
                        }

                        $result["notFound"]++;
                        $result["errors"][] = [
                            "index" => $subscriber["index"],
                            "error" => "flatNotFound",
                            "subscriberID" => $subscriber["subscriberID"],
                            "buildingUUID" => $subscriber["hasPair"] ? $subscriber["buildingUUID"] : null,
                            "flatNumber" => $subscriber["hasPair"] ? $subscriber["flatNumber"] : null,
                        ];
                        continue;
                    }

                    $flatId = @$flat["flatId"];

                    if (!checkInt($flatId)) {
                        $result["failed"]++;
                        $result["errors"][] = [
                            "index" => $subscriber["index"],
                            "error" => "invalidFlatId",
                            "subscriberID" => $subscriber["subscriberID"],
                        ];
                        continue;
                    }

                    // Step 6b: primary mode updates flat state, contract, credentials and custom fields.
                    if ($subscriber["hasSubscriberID"]) {
                        $rbtSubscriberId = trim((string)@$flat["contract"]);
                        $incomingSubscriberId = trim((string)$subscriber["contract"]);

                        if ($rbtSubscriberId !== "" && $rbtSubscriberId !== $incomingSubscriberId) {
                            error_log("[billing/syncAutoBlockByContracts] warning: subscriberID mismatch for flat resolved by houseUUID+flatNumber " . json_encode([
                                "index" => $subscriber["index"],
                                "flatId" => (int)$flatId,
                                "houseUUID" => $subscriber["buildingUUID"],
                                "flatNumber" => $subscriber["flatNumber"],
                                "rbtSubscriberID" => $rbtSubscriberId,
                                "incomingSubscriberID" => $incomingSubscriberId,
                                "subscriber" => is_array(@$subscribers[$subscriber["index"]]) ? $subscribers[$subscriber["index"]] : $subscriber,
                            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                        }
                    }

                    if (!$this->applySyncSubscriberToFlat($households, $customFields, $flatId, $subscriber, $result, [
                        "cantModifyFlatError" => "cantModifyFlat",
                        "includeTargetAutoBlock" => false,
                        "updateContract" => $subscriber["hasSubscriberID"],
                        "updateCustomFields" => true,
                    ])) {
                        continue;
                    }

                    $result["updated"]++;
                }
            }

            // Step 7: optional defaultAction for contracts that were not present in the payload.
            if ($defaultAction === "blockMissing" || $defaultAction === "unblockMissing") {
                if ($hasSubscribersWithoutContract) {
                    $result["missing"]["failed"]++;
                    $result["errors"][] = [
                        "scope" => "missing",
                        "error" => "defaultActionRequiresSubscriberID",
                    ];
                    return $result;
                }

                if (!count($contracts)) {
                    $result["missing"]["failed"]++;
                    $result["errors"][] = [
                        "scope" => "missing",
                        "error" => "noContractsForDefaultAction",
                    ];
                    return $result;
                }

                $missingRows = $households->getFlats("notContracts", array_values($contracts));

                if (!is_array($missingRows)) {
                    $result["missing"]["failed"]++;
                    $result["errors"][] = [
                        "scope" => "missing",
                        "error" => "cantGetFlatsByNotContracts",
                    ];
                    $missingRows = [];
                }

                $missingTargetAutoBlock = ($defaultAction === "blockMissing") ? 1 : 0;
                $this->syncAutoBlockRows($households, $missingRows, $missingTargetAutoBlock, "missing", $result);
            }

            return $result;
        }

        private function appendSyncInvalidError(&$result, $index, $error, $context = []) {
            // Helper: unified writer for "invalid" counters and payload details.
            $result["invalid"]++;
            $payload = [
                "index" => $index,
                "error" => $error,
            ];

            if (is_array($context) && count($context)) {
                $payload = array_merge($payload, $context);
            }

            $result["errors"][] = $payload;
        }

        private function normalizeSyncSubscriberItem($index, $subscriber, &$result, &$pairs, &$contracts, &$hasSubscribersWithoutContract) {
            // Helper: normalize one item and prepare pair/contract lookup structures.
            if (!is_array($subscriber)) {
                $this->appendSyncInvalidError($result, $index, "invalidItem");
                return false;
            }

            $hasIsActive = array_key_exists("isActive", $subscriber);
            $isActive = null;

            if ($hasIsActive) {
                $isActive = $subscriber["isActive"];
                if (!checkInt($isActive) || !in_array((int)$isActive, [ 0, 1 ], true)) {
                    $this->appendSyncInvalidError($result, $index, "invalidParams", [
                        "subscriberID" => @$subscriber["subscriberID"],
                        "buildingUUID" => @$subscriber["buildingUUID"],
                        "flatNumber" => @$subscriber["flatNumber"],
                    ]);
                    return false;
                }
            }

            $hasSubscriberID = false;
            $subscriberID = null;
            $contract = null;

            if (array_key_exists("subscriberID", $subscriber) && $subscriber["subscriberID"] !== null && $subscriber["subscriberID"] !== "") {
                $subscriberID = $subscriber["subscriberID"];
                if (!checkInt($subscriberID) || !$subscriberID) {
                    $this->appendSyncInvalidError($result, $index, "invalidSubscriberID", [
                        "subscriberID" => @$subscriber["subscriberID"],
                    ]);
                    return false;
                }

                $hasSubscriberID = true;
                $contract = (string)$subscriberID;
                $contracts[$contract] = $contract;
            }

            $agreement = null;
            $hasAgreement = false;
            if (array_key_exists("agreement", $subscriber)) {
                $agreement = $subscriber["agreement"];
                if (!checkStr($agreement) || $agreement === "") {
                    $this->appendSyncInvalidError($result, $index, "invalidAgreement", [
                        "agreement" => @$subscriber["agreement"],
                    ]);
                    return false;
                }
                $hasAgreement = true;
            }

            $addressText = null;
            $hasAddressText = false;
            if (array_key_exists("addressText", $subscriber)) {
                $addressText = $subscriber["addressText"];
                if (!checkStr($addressText) || $addressText === "") {
                    $this->appendSyncInvalidError($result, $index, "invalidAddressText", [
                        "addressText" => @$subscriber["addressText"],
                    ]);
                    return false;
                }
                $hasAddressText = true;
            }

            $login = null;
            $hasLogin = false;
            if (array_key_exists("login", $subscriber)) {
                $login = $subscriber["login"];
                if (!checkStr($login)) {
                    $this->appendSyncInvalidError($result, $index, "invalidLogin", [
                        "login" => @$subscriber["login"],
                    ]);
                    return false;
                }
                $hasLogin = true;
            }

            $password = null;
            $hasPassword = false;
            if (array_key_exists("password", $subscriber)) {
                $password = $subscriber["password"];
                if (!checkStr($password)) {
                    $this->appendSyncInvalidError($result, $index, "invalidPassword");
                    return false;
                }
                $hasPassword = true;
            }

            $phones = [];
            $hasPhones = false;
            if (array_key_exists("phones", $subscriber)) {
                if (!is_array($subscriber["phones"])) {
                    $this->appendSyncInvalidError($result, $index, "invalidPhones");
                    return false;
                }

                $normalizedPhones = [];
                foreach ($subscriber["phones"] as $phoneIndex => $phoneItem) {
                    if (!is_array($phoneItem)) {
                        $this->appendSyncInvalidError($result, $index, "invalidPhoneItem", [
                            "phoneIndex" => $phoneIndex,
                        ]);
                        return false;
                    }

                    if (!array_key_exists("phone", $phoneItem)) {
                        $this->appendSyncInvalidError($result, $index, "phoneRequired", [
                            "phoneIndex" => $phoneIndex,
                        ]);
                        return false;
                    }

                    $phone = $this->normalizeSyncSubscriberPhoneValue($phoneItem["phone"]);
                    if ($phone === null) {
                        $this->appendSyncInvalidError($result, $index, "invalidPhone", [
                            "phoneIndex" => $phoneIndex,
                            "phone" => @$phoneItem["phone"],
                        ]);
                        return false;
                    }

                    $type = array_key_exists("type", $phoneItem) ? $phoneItem["type"] : "regular";
                    if (!is_string($type) || !in_array($type, [ "owner", "regular" ], true)) {
                        $this->appendSyncInvalidError($result, $index, "invalidPhoneType", [
                            "phoneIndex" => $phoneIndex,
                            "phone" => $phone,
                            "type" => @$phoneItem["type"],
                        ]);
                        return false;
                    }

                    if (array_key_exists($phone, $normalizedPhones)) {
                        if ($normalizedPhones[$phone]["type"] !== $type) {
                            $this->appendSyncInvalidError($result, $index, "duplicatePhoneWithDifferentType", [
                                "phone" => $phone,
                            ]);
                            return false;
                        }

                        continue;
                    }

                    $normalizedPhones[$phone] = [
                        "phone" => $phone,
                        "type" => $type,
                        "isOwner" => ($type === "owner"),
                    ];
                }

                $phones = array_values($normalizedPhones);
                $hasPhones = count($phones) > 0;
            }

            $hasPair = false;
            $buildingUUID = "";
            $flatNumber = "";
            $pairKey = null;

            $hasBuildingUUIDField = array_key_exists("buildingUUID", $subscriber);
            $hasFlatField = array_key_exists("flatNumber", $subscriber);
            $flatValue = @$subscriber["flatNumber"];

            if ($hasBuildingUUIDField || $hasFlatField) {
                if ($hasBuildingUUIDField && $hasFlatField) {
                    $buildingUUID = $subscriber["buildingUUID"];
                    $flatNumber = $flatValue;

                    if (checkStr($buildingUUID) && $buildingUUID !== "" && checkStr($flatNumber) && $flatNumber !== "") {
                        $hasPair = true;
                        $pairKey = $buildingUUID . "\n" . $flatNumber;
                        $pairs[$pairKey] = [
                            "buildingUUID" => $buildingUUID,
                            "flatNumber" => $flatNumber,
                        ];
                    } else
                    if (!$hasSubscriberID) {
                        $this->appendSyncInvalidError($result, $index, "invalidBuildingUUIDFlat", [
                            "buildingUUID" => @$subscriber["buildingUUID"],
                            "flatNumber" => $flatValue,
                        ]);
                        return false;
                    }
                } else
                if (!$hasSubscriberID) {
                    $this->appendSyncInvalidError($result, $index, "buildingUUIDAndFlatRequiredTogether", [
                        "buildingUUID" => @$subscriber["buildingUUID"],
                        "flatNumber" => $flatValue,
                    ]);
                    return false;
                }
            }

            if (!$hasIsActive && !$hasPhones) {
                $this->appendSyncInvalidError($result, $index, "phonesRequiredWithoutIsActive");
                return false;
            }

            if (!$hasSubscriberID && !$hasPair) {
                $this->appendSyncInvalidError($result, $index, "noLookupParams");
                return false;
            }

            if (!$hasSubscriberID) {
                $hasSubscribersWithoutContract = true;
            }

            return [
                "hasIsActive" => $hasIsActive,
                "index" => $index,
                "subscriberID" => $subscriberID,
                "hasSubscriberID" => $hasSubscriberID,
                "contract" => $contract,
                "agreement" => $agreement,
                "hasAgreement" => $hasAgreement,
                "isActive" => $hasIsActive ? (int)$isActive : null,
                "addressText" => $addressText,
                "hasAddressText" => $hasAddressText,
                "login" => $login,
                "hasLogin" => $hasLogin,
                "password" => $password,
                "hasPassword" => $hasPassword,
                "phones" => $phones,
                "hasPhones" => $hasPhones,
                "buildingUUID" => $buildingUUID,
                "flatNumber" => $flatNumber,
                "hasPair" => $hasPair,
                "pairKey" => $pairKey,
            ];
        }

        private function normalizeSyncSubscriberPhoneValue($phone) {
            if (!is_scalar($phone)) {
                return null;
            }

            $rawPhone = trim((string)$phone);
            $phone = preg_replace('/\D+/', '', $rawPhone);
            if ($phone === null || $phone === "") {
                return null;
            }

            // Billing must send mobile phones with country code. Local Russian formats like
            // "912..." or "8912..." are rejected instead of being auto-converted.
            if (preg_match('/^9[0-9]{9}$/', $phone)) {
                return null;
            }

            if (preg_match('/^89[0-9]{9}$/', $phone) && strpos($rawPhone, '+') !== 0) {
                return null;
            }

            if (!preg_match('/^[0-9]{10,15}$/', $phone)) {
                return null;
            }

            return $phone;
        }

        private function hasSyncPhoneInFlat($households, $flatId, $phone, &$error = null) {
            $flatSubscribers = $households->getSubscribers("flatId", $flatId, [ "noDetail" ]);

            if ($flatSubscribers === false) {
                $error = "cantGetFlatSubscribers";
                return false;
            }

            foreach ($flatSubscribers as $flatSubscriber) {
                $flatSubscriberPhone = $this->normalizeSyncSubscriberPhoneValue(@$flatSubscriber["mobile"]);

                if ($flatSubscriberPhone !== null && $flatSubscriberPhone === $phone) {
                    return true;
                }
            }

            return false;
        }

        private function appendSyncPhoneRuntimeError(&$result, $subscriber, $flatId, $phoneItem, $errorCode, $details = null) {
            $result["failed"]++;

            $error = [
                "index" => $subscriber["index"],
                "error" => $errorCode,
                "flatId" => $flatId,
                "subscriberID" => $subscriber["subscriberID"],
                "phone" => $phoneItem["phone"],
                "phoneType" => $phoneItem["type"],
            ];

            if ($details !== null && $details !== "") {
                $error["details"] = $details;
            }

            $result["errors"][] = $error;
        }

        private function applySyncPhoneToFlat($households, $flatId, $phoneItem, $subscriber, &$result) {
            $lookupError = null;
            $subscriberAlreadyInFlat = $this->hasSyncPhoneInFlat($households, $flatId, $phoneItem["phone"], $lookupError);

            if ($lookupError !== null) {
                $this->appendSyncPhoneRuntimeError($result, $subscriber, $flatId, $phoneItem, $lookupError ?: "cantSyncSubscriberPhone");
                return false;
            }

            if ($subscriberAlreadyInFlat) {
                // Existing subscriber link for this flat is intentionally left untouched.
                return true;
            }

            $subscriberId = $households->addSubscriber($phoneItem["phone"], '', '', '', $flatId);

            if ($subscriberId === false) {
                $this->appendSyncPhoneRuntimeError($result, $subscriber, $flatId, $phoneItem, "cantAddSubscriberPhone", getLastError());
                return false;
            }

            if ($phoneItem["isOwner"]) {
                $createdSubscriber = $households->getSubscribers("id", (int)$subscriberId);

                if ($createdSubscriber === false || !is_array($createdSubscriber) || !count($createdSubscriber)) {
                    $this->appendSyncPhoneRuntimeError($result, $subscriber, $flatId, $phoneItem, "cantGetSubscriberByIdAfterAdd");
                    return false;
                }

                $createdSubscriber = $createdSubscriber[0];
                $createdFlats = is_array(@$createdSubscriber["flats"]) ? $createdSubscriber["flats"] : [];

                $subscriberFlatsPatch = [];
                foreach ($createdFlats as $createdFlat) {
                    $createdFlatId = (int)@$createdFlat["flatId"];

                    if ($createdFlatId <= 0) {
                        continue;
                    }

                    $subscriberFlatsPatch[$createdFlatId] = [
                        "role" => ((int)@$createdFlat["role"] == 0),
                    ];
                }

                $subscriberFlatsPatch[(int)$flatId] = [
                    "role" => true,
                ];

                if ($households->setSubscriberFlats((int)$subscriberId, $subscriberFlatsPatch) === false) {
                    $this->appendSyncPhoneRuntimeError($result, $subscriber, $flatId, $phoneItem, "cantSetOwnerRoleForAddedSubscriberFlat", getLastError());
                    return false;
                }
            }

            return true;
        }

        private function ensureSyncBillingFlatCustomFieldDefinitions($flatId, $subscriber, &$result) {
            $definitions = [];

            if ($subscriber["hasAgreement"]) {
                $definitions["agreement"] = [
                    "fieldDisplay" => "addresses.customFields.billing.agreement.fieldDisplay",
                    "fieldDescription" => "addresses.customFields.billing.agreement.fieldDescription",
                    "tab" => "addresses.customFields.billing.tab",
                    "weight" => 1000,
                ];
            }

            if ($subscriber["hasAddressText"]) {
                $definitions["addressText"] = [
                    "fieldDisplay" => "addresses.customFields.billing.addressText.fieldDisplay",
                    "fieldDescription" => "addresses.customFields.billing.addressText.fieldDescription",
                    "tab" => "addresses.customFields.billing.tab",
                    "weight" => 1010,
                ];
            }

            foreach ($definitions as $field => $definition) {
                $existing = $this->db->get(
                    "select
                        custom_field_id,
                        apply_to,
                        catalog,
                        type,
                        field_display,
                        field_description,
                        editor,
                        add,
                        modify,
                        tab,
                        weight
                     from custom_fields
                     where field = :field",
                    [
                        "field" => $field,
                    ],
                    [
                        "custom_field_id" => "customFieldId",
                        "apply_to" => "applyTo",
                        "catalog" => "catalog",
                        "type" => "type",
                        "field_display" => "fieldDisplay",
                        "field_description" => "fieldDescription",
                        "editor" => "editor",
                        "add" => "add",
                        "modify" => "modify",
                        "tab" => "tab",
                        "weight" => "weight",
                    ]
                );

                if ($existing === false) {
                    $result["failed"]++;
                    $result["errors"][] = [
                        "index" => $subscriber["index"],
                        "error" => "cantLoadCustomFieldConfiguration",
                        "flatId" => $flatId,
                        "subscriberID" => $subscriber["subscriberID"],
                        "field" => $field,
                    ];
                    return false;
                }

                if (is_array($existing) && count($existing)) {
                    $existing = $existing[0];

                    if (@$existing["applyTo"] !== "flat") {
                        $result["failed"]++;
                        $result["errors"][] = [
                            "index" => $subscriber["index"],
                            "error" => "billingCustomFieldAlreadyUsedByAnotherEntity",
                            "flatId" => $flatId,
                            "subscriberID" => $subscriber["subscriberID"],
                            "field" => $field,
                            "configuredApplyTo" => @$existing["applyTo"],
                        ];
                        return false;
                    }

                    if (
                        @$existing["catalog"] === "billing" &&
                        (
                            @$existing["type"] !== "text" ||
                            @$existing["fieldDisplay"] !== $definition["fieldDisplay"] ||
                            @$existing["fieldDescription"] !== $definition["fieldDescription"] ||
                            @$existing["editor"] !== "text" ||
                            (int)@$existing["add"] !== 1 ||
                            (int)@$existing["modify"] !== 1 ||
                            @$existing["tab"] !== $definition["tab"] ||
                            (int)@$existing["weight"] !== (int)$definition["weight"]
                        )
                    ) {
                        if ($this->db->modify(
                            "update custom_fields set
                                catalog = :catalog,
                                type = :type,
                                field_display = :field_display,
                                field_description = :field_description,
                                editor = :editor,
                                add = :add,
                                modify = :modify,
                                tab = :tab,
                                weight = :weight
                             where custom_field_id = :custom_field_id",
                            [
                                "catalog" => "billing",
                                "type" => "text",
                                "field_display" => $definition["fieldDisplay"],
                                "field_description" => $definition["fieldDescription"],
                                "editor" => "text",
                                "add" => 1,
                                "modify" => 1,
                                "tab" => $definition["tab"],
                                "weight" => $definition["weight"],
                                "custom_field_id" => $existing["customFieldId"],
                            ]
                        ) === false) {
                            $result["failed"]++;
                            $result["errors"][] = [
                                "index" => $subscriber["index"],
                                "error" => "cantUpdateBillingCustomField",
                                "flatId" => $flatId,
                                "subscriberID" => $subscriber["subscriberID"],
                                "field" => $field,
                            ];
                            return false;
                        }
                    }

                    continue;
                }

                if ($this->db->modify(
                    "insert into custom_fields (
                        apply_to,
                        catalog,
                        type,
                        field,
                        field_display,
                        field_description,
                        regex,
                        link,
                        format,
                        editor,
                        indx,
                        search,
                        required,
                        magic_class,
                        magic_function,
                        magic_hint,
                        add,
                        modify,
                        tab,
                        weight
                    ) values (
                        :apply_to,
                        :catalog,
                        :type,
                        :field,
                        :field_display,
                        :field_description,
                        :regex,
                        :link,
                        :format,
                        :editor,
                        :indx,
                        :search,
                        :required,
                        :magic_class,
                        :magic_function,
                        :magic_hint,
                        :add,
                        :modify,
                        :tab,
                        :weight
                    )",
                    [
                        "apply_to" => "flat",
                        "catalog" => "billing",
                        "type" => "text",
                        "field" => $field,
                        "field_display" => $definition["fieldDisplay"],
                        "field_description" => $definition["fieldDescription"],
                        "regex" => null,
                        "link" => null,
                        "format" => null,
                        "editor" => "text",
                        "indx" => 0,
                        "search" => 0,
                        "required" => 0,
                        "magic_class" => null,
                        "magic_function" => null,
                        "magic_hint" => null,
                        "add" => 1,
                        "modify" => 1,
                        "tab" => $definition["tab"],
                        "weight" => $definition["weight"],
                    ]
                ) === false) {
                    $result["failed"]++;
                    $result["errors"][] = [
                        "index" => $subscriber["index"],
                        "error" => "cantCreateBillingCustomField",
                        "flatId" => $flatId,
                        "subscriberID" => $subscriber["subscriberID"],
                        "field" => $field,
                    ];
                    return false;
                }
            }

            return true;
        }

        private function applySyncSubscriberToFlat($households, &$customFields, $flatId, $subscriber, &$result, $options = []) {
            $autoBlock = null;
            $flatPatch = [];

            if ($subscriber["hasIsActive"]) {
                $autoBlock = $subscriber["isActive"] ? 0 : 1;
                $flatPatch["autoBlock"] = $autoBlock;
            }

            if (@$options["updateContract"] && $subscriber["hasSubscriberID"]) {
                $flatPatch["contract"] = $subscriber["contract"];
            }

            if ($subscriber["hasLogin"] || $subscriber["hasPassword"]) {
                if ($subscriber["hasLogin"] && $subscriber["hasPassword"]) {
                    $flatPatch["login"] = $subscriber["login"];
                    $flatPatch["password"] = $subscriber["password"];
                } else {
                    $currentFlat = $households->getFlat($flatId);

                    if (!$currentFlat) {
                        $result["failed"]++;
                        $result["errors"][] = [
                            "index" => $subscriber["index"],
                            "error" => "cantGetFlat",
                            "flatId" => $flatId,
                            "subscriberID" => $subscriber["subscriberID"],
                        ];
                        return false;
                    }

                    $flatPatch["login"] = $subscriber["hasLogin"] ? $subscriber["login"] : @$currentFlat["login"];
                    $flatPatch["password"] = $subscriber["hasPassword"] ? $subscriber["password"] : @$currentFlat["password"];
                }
            }

            if (count($flatPatch)) {
                if ($households->modifyFlat($flatId, $flatPatch) === false) {
                    $result["failed"]++;
                    $error = [
                        "index" => $subscriber["index"],
                        "error" => @$options["cantModifyFlatError"] ?: "cantModifyFlat",
                        "flatId" => $flatId,
                        "subscriberID" => $subscriber["subscriberID"],
                    ];

                    if (@$options["includeTargetAutoBlock"] && $autoBlock !== null) {
                        $error["targetAutoBlock"] = $autoBlock;
                    }

                    $result["errors"][] = $error;
                    return false;
                }
            }

            if (@$options["updateCustomFields"] && ($subscriber["hasAgreement"] || $subscriber["hasAddressText"])) {
                // Lazy-load customFields only when optional payload fields should be persisted.
                if ($customFields === null) {
                    $customFields = loadBackend("customFields");
                }

                if (!$customFields) {
                    $result["failed"]++;
                    $result["errors"][] = [
                        "index" => $subscriber["index"],
                        "error" => "cantLoadCustomFields",
                        "flatId" => $flatId,
                        "subscriberID" => $subscriber["subscriberID"],
                    ];
                    return false;
                }

                if (!$this->ensureSyncBillingFlatCustomFieldDefinitions($flatId, $subscriber, $result)) {
                    return false;
                }

                $values = [];

                if ($subscriber["hasAgreement"]) {
                    $values["agreement"] = $subscriber["agreement"];
                }

                if ($subscriber["hasAddressText"]) {
                    $values["addressText"] = $subscriber["addressText"];
                }

                if ($customFields->modifyValues("flat", $flatId, $values, "patch") === false) {
                    $result["failed"]++;
                    $result["errors"][] = [
                        "index" => $subscriber["index"],
                        "error" => "cantModifyCustomFields",
                        "flatId" => $flatId,
                        "subscriberID" => $subscriber["subscriberID"],
                    ];
                    return false;
                }
            }

            if ($subscriber["hasPhones"]) {
                foreach ($subscriber["phones"] as $phoneItem) {
                    if (!$this->applySyncPhoneToFlat($households, $flatId, $phoneItem, $subscriber, $result)) {
                        return false;
                    }
                }
            }

            return true;
        }

        private function buildSyncFlatByPairMap($households, $pairs, &$result) {
            // Helper: fetch and map flats by "houseUUID\nflatNumber".
            if (!count($pairs)) {
                return [];
            }

            $pairRows = $households->getFlats("houseUuidFlat", array_values($pairs));

            if (!is_array($pairRows)) {
                $result["failed"]++;
                $result["errors"][] = [
                    "error" => "cantGetFlatsByHouseUuidFlat",
                ];
                return [];
            }

            $flatByPair = [];

            foreach ($pairRows as $row) {
                $flatId = @$row["flatId"];
                $flat = @$row["flat"];
                $houseUuid = @$row["houseUuid"];

                if (!checkInt($flatId) || !checkStr($flat) || $flat === "" || !checkStr($houseUuid) || $houseUuid === "") {
                    $result["failed"]++;
                    $result["errors"][] = [
                        "error" => "invalidRow",
                        "flatId" => @$row["flatId"],
                        "houseUuid" => @$row["houseUuid"],
                    ];
                    continue;
                }

                $pairKey = $houseUuid . "\n" . $flat;

                if (array_key_exists($pairKey, $flatByPair)) {
                    $result["failed"]++;
                    $result["errors"][] = [
                        "error" => "duplicateFlatByHouseUuidFlat",
                        "flatId" => $flatId,
                        "houseUuid" => $houseUuid,
                        "flatNumber" => $flat,
                    ];
                    continue;
                }

                $flatByPair[$pairKey] = $row;
            }

            return $flatByPair;
        }

        private function resolveSyncFallbackFlatIdByContract($households, $subscriber, &$result) {
            // Helper: resolve single fallback flat by contract/subscriberID.
            if (!@$subscriber["hasSubscriberID"]) {
                return null;
            }

            $fallbackRows = $households->getFlats("contract", [ "contract" => $subscriber["contract"] ]);

            if (!is_array($fallbackRows) || !count($fallbackRows)) {
                return null;
            }

            $fallbackById = [];
            foreach ($fallbackRows as $fallbackRow) {
                $fallbackRowFlatId = @$fallbackRow["flatId"];
                if (!checkInt($fallbackRowFlatId)) {
                    continue;
                }
                $fallbackById[$fallbackRowFlatId] = $fallbackRow;
            }

            if (count($fallbackById) === 1) {
                return (int)array_key_first($fallbackById);
            }

            if (count($fallbackById) > 1) {
                $result["failed"]++;
                $result["errors"][] = [
                    "index" => $subscriber["index"],
                    "error" => "multipleFlatsByContractFallback",
                    "subscriberID" => $subscriber["subscriberID"],
                    "contract" => $subscriber["contract"],
                    "flatIds" => array_values(array_map("intval", array_keys($fallbackById))),
                ];
            }

            return null;
        }

        private function findAddressItemMatch($rows, $uuidField, $uuid, $nameField, $name) {
            foreach ($rows as $row) {
                $_uuid = @$row[$uuidField];

                if (checkStr($_uuid) && $_uuid !== "" && $_uuid === $uuid) {
                    return [
                        "by" => "uuid",
                        "row" => $row,
                    ];
                }
            }

            foreach ($rows as $row) {
                $_name = @$row[$nameField];

                if (checkStr($_name) && $_name !== "" && $_name === $name) {
                    return [
                        "by" => "name",
                        "row" => $row,
                    ];
                }
            }

            return false;
        }

        private function syncImportAddressExistingTimezone($addresses, $entity, $row, $timezone, &$result, $index) {
            $idField = "";
            $getMethod = "";
            $modifyResult = false;

            switch ($entity) {
                case "region":
                    $idField = "regionId";
                    $getMethod = "getRegion";
                    break;

                case "area":
                    $idField = "areaId";
                    $getMethod = "getArea";
                    break;

                case "city":
                    $idField = "cityId";
                    $getMethod = "getCity";
                    break;

                default:
                    $result["failed"]++;
                    $result["errors"][] = [
                        "index" => $index,
                        "error" => "unknownTimezoneEntity",
                        "entity" => $entity,
                    ];
                    return false;
            }

            $currentTimezone = @$row["timezone"];
            if (!checkStr($currentTimezone) || $currentTimezone === "") {
                $currentTimezone = "-";
            }

            if ($currentTimezone === $timezone) {
                return $row;
            }

            $entityId = @$row[$idField];
            if (!checkInt($entityId) || !$entityId) {
                $result["failed"]++;
                $result["errors"][] = [
                    "index" => $index,
                    "error" => "invalidTimezoneEntityId",
                    "entity" => $entity,
                ];
                return false;
            }

            switch ($entity) {
                case "region":
                    $modifyResult = $addresses->modifyRegion(
                        @$row["regionId"],
                        @$row["regionUuid"],
                        @$row["regionIsoCode"],
                        @$row["regionWithType"],
                        @$row["regionType"],
                        @$row["regionTypeFull"],
                        @$row["region"],
                        $timezone
                    );
                    break;

                case "area":
                    $modifyResult = $addresses->modifyArea(
                        @$row["areaId"],
                        @$row["regionId"],
                        @$row["areaUuid"],
                        @$row["areaWithType"],
                        @$row["areaType"],
                        @$row["areaTypeFull"],
                        @$row["area"],
                        $timezone
                    );
                    break;

                case "city":
                    $modifyResult = $addresses->modifyCity(
                        @$row["cityId"],
                        @$row["regionId"],
                        @$row["areaId"],
                        @$row["cityUuid"],
                        @$row["cityWithType"],
                        @$row["cityType"],
                        @$row["cityTypeFull"],
                        @$row["city"],
                        $timezone
                    );
                    break;
            }

            if ($modifyResult === false) {
                $result["failed"]++;
                $result["errors"][] = [
                    "index" => $index,
                    "error" => "cantModifyEntityTimezone",
                    "entity" => $entity,
                    "timezone" => $timezone,
                ];
                return false;
            }

            $updatedRow = $addresses->$getMethod($entityId);
            if (!is_array($updatedRow)) {
                $result["failed"]++;
                $result["errors"][] = [
                    "index" => $index,
                    "error" => "cantGetEntityAfterTimezoneUpdate",
                    "entity" => $entity,
                    "timezone" => $timezone,
                ];
                return false;
            }

            return $updatedRow;
        }

        private function syncAutoBlockRows($households, $rows, $targetAutoBlock, $scope, &$result) {
            foreach ($rows as $row) {
                $flatId = @$row["flatId"];
                $currentAutoBlock = @$row["autoBlock"];
                $contract = @$row["contract"];

                if (!checkInt($flatId) || !checkInt($currentAutoBlock)) {
                    $result[$scope]["failed"]++;
                    $result["errors"][] = [
                        "scope" => $scope,
                        "error" => "invalidRow",
                        "flatId" => @$row["flatId"],
                        "contract" => $contract,
                    ];
                    continue;
                }

                if ($currentAutoBlock === (int)$targetAutoBlock) {
                    $result[$scope]["unchanged"]++;
                    continue;
                }

                if ($households->modifyFlat($flatId, [ "autoBlock" => (int)$targetAutoBlock ]) === false) {
                    $result[$scope]["failed"]++;
                    $result["errors"][] = [
                        "scope" => $scope,
                        "error" => "cantModifyFlat",
                        "flatId" => $flatId,
                        "contract" => $contract,
                        "targetAutoBlock" => (int)$targetAutoBlock,
                    ];
                    continue;
                }

                $result[$scope]["updated"]++;
            }
        }
    }
}
