<?php

    /**
    * backends households namespace
    */

    namespace backends\households {

        use backends\backend;

        /**
         * base addresses class
         */
        abstract class households extends backend {

            /**
             * @param $houseId
             * @param $entranceType
             * @param $entrance
             * @param $lat
             * @param $lon
             * @param $shared
             * @param $plog
             * @param $prefix
             * @param $callerId
             * @param $domophoneId
             * @param $domophoneOutput
             * @param $cms
             * @param $cmsType
             * @param $cameraId
             * @param $altCameraIds
             * @param $cmsLevels
             * @param $path
             *
             * @return boolean|integer
             */

            abstract function createEntrance($houseId, $entranceType, $entrance, $lat, $lon, $shared, $plog, $prefix, $callerId, $domophoneId, $domophoneOutput, $cms, $cmsType, $cameraId, $altCameraIds, $cmsLevels, $path);

            /**
             * @param $entranceId
             *
             * @return false|array
             */

            abstract function getEntrance($entranceId);

            /**
             * @param $by
             * @param $query
             *
             * @return false|array
             */

            abstract function getEntrances($by, $query);

            /**
             * @param $houseId
             * @param $entranceId
             * @param $prefix
             * @return boolean
             */

            abstract function addEntrance($houseId, $entranceId, $prefix);

            /**
             * @param $entranceId
             * @param $houseId
             * @param $entranceType
             * @param $entrance
             * @param $lat
             * @param $lon
             * @param $shared
             * @param $plog
             * @param $prefix
             * @param $callerId
             * @param $domophoneId
             * @param $domophoneOutput
             * @param $cms
             * @param $cmsType
             * @param $cameraId
             * @param $altCameraIds
             * @param $cmsLevels
             * @param $path
             *
             * @return boolean
             */

            abstract function modifyEntrance($entranceId, $houseId, $entranceType, $entrance, $lat, $lon, $shared, $plog, $prefix, $callerId, $domophoneId, $domophoneOutput, $cms, $cmsType, $cameraId, $altCameraIds, $cmsLevels, $path);

            /**
             * @param $entranceId
             * @param $houseId
             * @return boolean
             */
            abstract function deleteEntrance($entranceId, $houseId);

            /**
             * @param $entranceId
             * @return boolean
             */
            abstract function destroyEntrance($entranceId);

            /**
             * @param $flatId
             * @return boolean|array
             */
            abstract function getFlat($flatId);

            /**
             * @param $by
             * @param $params
             * @return boolean|array
             */
            abstract function getFlats($by, $params);

            /**
             * @param $houseId
             * @param $floor
             * @param $flat
             * @param $code
             * @param $entrances
             * @param $apartmentsAndLevels
             * @param $manualBlock
             * @param $adminBlock
             * @param $openCode
             * @param $plog
             * @param $autoOpen
             * @param $whiteRabbit
             * @param $sipEnabled
             * @param $sipPassword
             *
             * @return boolean|integer
             */

            abstract function addFlat($houseId, $floor, $flat, $code, $entrances, $apartmentsAndLevels, $manualBlock, $adminBlock, $openCode, $plog, $autoOpen, $whiteRabbit, $sipEnabled, $sipPassword);

            /**
             * @param $flatId
             * @param $params
             * @return boolean
             */

            abstract function modifyFlat($flatId, $params);

            /**
             * @param $flatId
             * @return boolean
             */

            abstract function deleteFlat($flatId);

            /**
             * @param $flatId
             * @return boolean
             */
            abstract function doorOpened($flatId);

            /**
             * @param $houseId
             * @return false|array
             */
            abstract function getSharedEntrances($houseId = false);

            /**
             * @param $entranceId
             * @return false|array
             */
            abstract public function getCms($entranceId);

            /**
             * @param $entranceId
             * @param $cms
             * @return boolean
             */
            abstract public function setCms($entranceId, $cms);

            /**
             * @param $by
             * @param $query
             *
             * @return mixed
             */

            abstract public function getDomophones($by = "all", $query = -1);

            /**
             * @param $enabled
             * @param $model
             * @param $server
             * @param $url
             * @param $credentials
             * @param $dtmf
             * @param $nat
             * @param $comments
             * @param $name
             * @param $display
             * @param $video
             * @param $monitoring
             * @param $ext
             *
             * @return false|integer
             */

            abstract public function addDomophone($enabled, $model, $server, $url, $credentials, $dtmf, $nat, $comments, $name, $display, $video, $monitoring, $ext);

            /**
             * @param $domophoneId
             * @param $enabled
             * @param $model
             * @param $server
             * @param $url
             * @param $credentials
             * @param $dtmf
             * @param $firstTime
             * @param $nat
             * @param $locksAreOpen
             * @param $comments
             * @param $name
             * @param $display
             * @param $video
             * @param $monitoring
             * @param $ext
             *
             * @return boolean
             */

            abstract public function modifyDomophone($domophoneId, $enabled, $model, $server, $url, $credentials, $dtmf, $firstTime, $nat, $locksAreOpen, $comments, $name, $display, $video, $monitoring, $ext);

            /**
             * @param $domophoneId
             * @param $firstTime
             * @return boolean
             */

            abstract public function autoconfigureDomophone($domophoneId, $firstTime);

            /**
             * @param $domophoneId
             */

            abstract public function autoconfigDone($domophoneId);

            /**
             * @param $domophoneId
             * @return boolean
             */

            abstract public function deleteDomophone($domophoneId);

            /**
             * @param $domophoneId
             * @return false|array
             */

            abstract public function getDomophone($domophoneId);

            /**
             * @param $by - "id", "mobile", "flat", "...?"
             * @param $query
             * @param $options
             *
             * @return false|array
             */

            abstract public function getSubscribers($by, $query, $options = []);

            /**
             * @param $mobile
             * @param $name
             * @param $patronymic
             * @param $last
             * @param bool $flatId
             * @param null $message
             * @return boolean|integer
             */

            abstract public function addSubscriber($mobile, $name = '', $patronymic = '', $last = '', $flatId = false, $message = false);

            /**
             * @param $subscriberId
             * @param $params
             * @return boolean
             */

            abstract public function modifySubscriber($subscriberId, $params = []);

            /**
             * @param $subscriberId
             * @return boolean
             */

            abstract public function deleteSubscriber($subscriberId);

            /**
             * @param $flatId
             * @param $subscriberId
             * @return mixed
             */

            abstract public function removeSubscriberFromFlat($flatId, $subscriberId);

            /**
             * @param $subscriberId
             * @param $flats
             * @param $limitCheck
             *
             * @return boolean
             */

            abstract public function setSubscriberFlats($subscriberId, $flats, $limitCheck = false);

            /**
             * @param $by
             * @param $query
             * @return mixed
             */

            abstract public function getKeys($by, $query);

            /**
             * @param $rfId
             * @param $accessType
             * @param $accessTo
             * @param $comments
             *
             * @return false|integer
             */

            abstract public function addKey($rfId, $accessType, $accessTo, $comments);

            /**
             * @param $keyId
             * @param $comments
             *
             * @return boolean
             */

            abstract public function modifyKey($keyId, $comments);

            /**
             * @param $keyId
             * @return boolean
             */

            abstract public function deleteKey($keyId);

            /**
             * @param $rfId
             * @return boolean
             */

            abstract public function lastSeenKey($rfId);

            /**
             * @param $token
             * @return boolean
             */

            abstract public function dismissToken($token);

            /**
             * @param $by
             * @param $params
             * @return array|false
             */

            abstract public function getCameras($by, $params);

            /**
             * @param $to
             * @param $id
             * @param $cameraId
             * @return mixed
             */

            abstract public function addCamera($to, $id, $cameraId);

            /**
             * @param $from
             * @param $id
             * @param $cameraId
             * @return mixed
             */

            abstract public function unlinkCamera($from, $id, $cameraId);

            /**
             * @param $from
             * @param $id
             * @param $cameraId
             * @param $path
             *
             * @return mixed
             */

            abstract public function modifyCamera($from, $id, $cameraId, $path);

            /**
             * @param $by - "id", "subscriber", "flat", "authToken"
             * @param $query
             * @return false|array
             */

            abstract public function getDevices($by, $query);

            /**
             * @param $subscriber
             * @param $deviceToken
             * @param $platformauthToken
             * @param $authToken
             * @return boolean|integer
             */
            abstract public function addDevice($subscriber, $deviceToken, $platform, $authToken);

            /**
             * @param $deviceId
             * @param $params
             * @return boolean
             */

            abstract public function modifyDevice($deviceId, $params = []);

            /**
             * @param $deviceId
             * @return boolean
             */

            abstract public function deleteDevice($deviceId);

            /**
             * @param integer $deviceId
             * @param integer $flatId
             * @param integer $voipEnabled
             *
             * @return boolean
             */

            abstract public function setDeviceFlat($deviceId, $flatId, $voipEnabled);

            /**
             * @param string search
             *
             * @return mixed
             */

            abstract function searchSubscriber($search);

            /**
             * @param string search
             *
             * @return mixed
             */

            abstract function searchFlat($search);

            /**
             * @param string search
             *
             * @return mixed
             */

            abstract function searchRf($search);

            /**
             * @param array $paths
             *
             * @return mixed
             */

            function mergePaths($paths) {
                $nodes = [];

                foreach ($paths as $p) {
                    $f = false;
                    foreach ($nodes as &$n) {
                        if ($p["id"] == $n["id"]) {
                            $f = true;
                            if (is_array($n["children"]) && is_array($p["children"])) {
                                $n["children"] = array_merge($n["children"], $p["children"]);
                            }
                            if (!is_array($n["children"]) && is_array($p["children"])) {
                                $n["children"] = $p["children"];
                            }
                        }
                    }
                    if (!$f) {
                        $nodes[] = $p;
                    } else {
                        foreach ($nodes as &$n) {
                            if (is_array($n["children"])) {
                                $n["children"] = $this->mergePaths($n["children"]);
                            }
                        }
                    }
                }

                return $nodes;
            }

            /**
             * @param string tree
             * @param string search
             *
             * @return mixed
             */

            abstract function searchPath($tree, $search);

            /**
             * @param mixed treeOrFrom
             *
             * @return mixed
             */

            abstract function getPath($treeOrFrom, $withParents = false);

            /**
             * @param string tree
             * @param string text
             * @param string icon
             *
             * @return mixed
             */

            abstract function addRootPathNode($tree, $text, $icon);

            /**
             * @param string parentId
             * @param string text
             * @param string icon
             *
             * @return mixed
             */

            abstract function addPathNode($parentId, $text, $icon);

            /**
             * @param string nodeId
             * @param string text
             * @param string icon
             *
             * @return mixed
             */

            abstract function modifyPathNode($nodeId, $text, $icon);

            /**
             * @param string deviceToken
             * @param string pushToken
             *
             * @return mixed
             */

            abstract function dedupDevices($deviceToken, $pushToken);

            /**
             * @param string nodeId
             * @param string name
             * @param string icon
             *
             * @return mixed
             */

            abstract function deletePathNode($nodeId);

            /**
             * @param string domophoneIp
             * @param string subId
             * @param string output
             * @param string by
             * @param mixed detail
             *
             * or
             *
             * @param integer entranceId
             * @param string by
             * @param mixed detail
             *
             * @return boolean
             */

            abstract function paranoidEvent();

            /**
             * @param $deviceId
             * @param $flatId
             * @param $eventType
             * @param $eventDetail
             * @param $comments
             *
             * @return boolean
             */

            abstract function watch($deviceId, $flatId, $eventType, $eventDetail, $comments);

            /**
             * @param $houseWatcherId
             * @param $deviceId
             *
             * or
             *
             * @param $houseWatcherId
             *
             * @return boolean
             */

            abstract function unwatch();

            /**
             * @param integer $deviceId
             * @param integer $flatId
             *
             * @return mixed
             */

            abstract function watchers($deviceId, $flatId);

            /**
             * @param string by [ flat, entrance, house, houses ]
             * @param mixed query [ id(s) ]
             */

            abstract public function broadcast($by, $query, $title, $msg, $action = "inbox");
        }
    }
