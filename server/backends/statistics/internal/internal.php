<?php

    /**
     * backends statistics namespace
     */

    namespace backends\statistics {

        /**
         * internal statistics class (fast SQL aggregates)
         *
         * Raw SQL queries: internal/queries.sql
         */

        class internal extends statistics {

            /**
             * @inheritDoc
             */

            public function statistics() {
                $inactiveDeviceDays = (int)(@$this->bconfig["inactiveDeviceDays"] ?: 30);
                if ($inactiveDeviceDays < 1) {
                    $inactiveDeviceDays = 30;
                }

                $cacheTtl = (int)($this->bconfig["cacheTtl"] ?? 300);
                $cacheKey = "CACHE:STATISTICS:DATA:" . $inactiveDeviceDays;

                if ($cacheTtl > 0) {
                    $cached = $this->redis->get($cacheKey);
                    if ($cached) {
                        $data = json_decode($cached, true);
                        if (is_array($data)) {
                            return $data;
                        }
                    }
                }

                $data = $this->collectStatistics($inactiveDeviceDays);

                if ($cacheTtl > 0 && is_array($data)) {
                    $this->redis->setex($cacheKey, $cacheTtl, json_encode($data));
                }

                return $data;
            }

            /**
             * @param int $inactiveDeviceDays
             * @return array|false
             */

            private function collectStatistics($inactiveDeviceDays) {
                if (!$this->db) {
                    return false;
                }

                $row = $this->db->get("
                    SELECT
                        (SELECT count(DISTINCT house_subscriber_id) FROM houses_flats_subscribers) AS subscribers,
                        (SELECT count(*) FROM houses_flats) AS flats,
                        (SELECT count(DISTINCT hf.house_flat_id)
                         FROM houses_flats hf
                         INNER JOIN houses_flats_subscribers hfs ON hfs.house_flat_id = hf.house_flat_id) AS flats_with_subscribers,
                        (SELECT count(*) FROM houses_flats)
                        -
                        (SELECT count(DISTINCT hf.house_flat_id)
                         FROM houses_flats hf
                         INNER JOIN houses_flats_subscribers hfs ON hfs.house_flat_id = hf.house_flat_id) AS flats_without_subscribers,
                        (SELECT count(*) FROM houses_flats
                         WHERE coalesce(manual_block, 0) <> 0
                            OR coalesce(auto_block, 0) <> 0
                            OR coalesce(admin_block, 0) <> 0) AS blocked_flats,
                        (SELECT count(*) FROM houses_domophones) AS domophones,
                        (SELECT count(*) FROM houses_domophones WHERE coalesce(enabled, 0) = 0) AS domophones_disabled,
                        (SELECT count(*) FROM cameras) AS cameras,
                        (SELECT count(*) FROM cameras WHERE coalesce(enabled, 0) = 0) AS cameras_disabled,
                        (SELECT count(*) FROM houses_rfids) AS keys,
                        (SELECT count(*) FROM houses_rfids WHERE access_type = 0) AS keys_universal,
                        (SELECT count(*) FROM houses_rfids WHERE access_type = 1) AS keys_subscriber,
                        (SELECT count(*) FROM houses_rfids WHERE access_type = 2) AS keys_flat,
                        (SELECT count(*) FROM houses_rfids WHERE access_type = 3) AS keys_entrance,
                        (SELECT count(*) FROM houses_rfids WHERE access_type = 4) AS keys_house,
                        (SELECT count(*) FROM houses_rfids WHERE access_type = 5) AS keys_company,
                        d.devices,
                        d.devices_android,
                        d.devices_ios,
                        d.devices_web,
                        d.devices_other,
                        d.devices_without_push,
                        d.devices_without_flats,
                        d.devices_inactive
                    FROM (
                        SELECT
                            count(*) AS devices,
                            count(*) FILTER (WHERE coalesce(platform, -1) = 0) AS devices_android,
                            count(*) FILTER (WHERE coalesce(platform, -1) = 1) AS devices_ios,
                            count(*) FILTER (WHERE coalesce(platform, -1) = 2) AS devices_web,
                            count(*) FILTER (WHERE platform IS NULL OR platform NOT IN (0, 1, 2)) AS devices_other,
                            count(*) FILTER (WHERE coalesce(btrim(push_token), '') = '') AS devices_without_push,
                            count(*) FILTER (WHERE NOT EXISTS (
                                SELECT 1
                                FROM houses_flats_devices fd
                                WHERE fd.subscriber_device_id = d.subscriber_device_id
                            )) AS devices_without_flats,
                            count(*) FILTER (WHERE coalesce(last_seen, 0) < :inactive_before) AS devices_inactive
                        FROM houses_subscribers_devices d
                        WHERE d.house_subscriber_id IN (
                            SELECT DISTINCT house_subscriber_id
                            FROM houses_flats_subscribers
                        )
                    ) d
                ", [
                    "inactive_before" => time() - ($inactiveDeviceDays * 86400),
                ], [
                    "subscribers" => "subscribers",
                    "flats" => "flats",
                    "flats_with_subscribers" => "flatsWithSubscribers",
                    "flats_without_subscribers" => "flatsWithoutSubscribers",
                    "blocked_flats" => "blockedFlats",
                    "domophones" => "domophones",
                    "domophones_disabled" => "domophonesDisabled",
                    "cameras" => "cameras",
                    "cameras_disabled" => "camerasDisabled",
                    "keys" => "keys",
                    "keys_universal" => "keysUniversal",
                    "keys_subscriber" => "keysSubscriber",
                    "keys_flat" => "keysFlat",
                    "keys_entrance" => "keysEntrance",
                    "keys_house" => "keysHouse",
                    "keys_company" => "keysCompany",
                    "devices" => "devices",
                    "devices_android" => "devicesAndroid",
                    "devices_ios" => "devicesIos",
                    "devices_web" => "devicesWeb",
                    "devices_other" => "devicesOther",
                    "devices_without_push" => "devicesWithoutPush",
                    "devices_without_flats" => "devicesWithoutFlats",
                    "devices_inactive" => "devicesInactive",
                ], [
                    "fieldlify",
                    "singlify",
                ]);

                if (!is_array($row)) {
                    return false;
                }

                $row["inactiveDeviceDays"] = $inactiveDeviceDays;

                return $row;
            }
        }
    }
