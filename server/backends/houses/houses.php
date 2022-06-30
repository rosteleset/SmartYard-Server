<?php

    /**
    * backends houses namespace
    */

    namespace backends\houses
    {

        use backends\backend;

        /**
         * base addresses class
         */
        abstract class houses extends backend
        {

            /**
             * @param $houseId
             * @return false|array
             */
            abstract function getHouseFlats($houseId);

            /**
             * @param $houseId
             * @return false|array
             */
            abstract function getHouseEntrances($houseId);

            /**
             * @param $houseId
             * @param $entranceType
             * @param $entrance
             * @param $lat
             * @param $lon
             * @param $shared
             * @param $prefix
             * @param $domophoneId
             * @param $domophoneOutput
             * @param $cms
             * @param $cmsType
             * @param $cameraId
             * @param $locksDisabled
             * @param $cmsLevels
             * @return boolean|integer
             */
            abstract function createEntrance($houseId, $entranceType, $entrance, $lat, $lon, $shared, $prefix, $domophoneId, $domophoneOutput, $cms, $cmsType, $cameraId, $locksDisabled, $cmsLevels);

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
             * @param $prefix
             * @param $domophoneId
             * @param $domophoneOutput
             * @param $cms
             * @param $cmsType
             * @param $cameraId
             * @param $locksDisabled
             * @param $cmsLevels
             * @return boolean
             */
            abstract function modifyEntrance($entranceId, $houseId, $entranceType, $entrance, $lat, $lon, $shared, $prefix, $domophoneId, $domophoneOutput, $cms, $cmsType, $cameraId, $locksDisabled, $cmsLevels);

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
             * @param $houseId
             * @param $floor
             * @param $flat
             * @param $entrances
             * @param $apartmentsAndFlats
             * @param $manualBlock
             * @param $openCode
             * @param $autoOpen
             * @param $whiteRabbit
             * @param $sipEnabled
             * @param $sipPassword
             * @return boolean|integer
             */
            abstract function addFlat($houseId, $floor, $flat, $entrances, $apartmentsAndFlats, $manualBlock, $openCode, $autoOpen, $whiteRabbit, $sipEnabled, $sipPassword);

            /**
             * @param $flatId
             * @param $floor
             * @param $flat
             * @param $entrances
             * @param $apartmentsAndFlats
             * @param $manualBlock
             * @param $openCode
             * @param $autoOpen
             * @param $whiteRabbit
             * @param $sipEnabled
             * @param $sipPassword
             * @return boolean
             */
            abstract function modifyFlat($flatId, $floor, $flat, $entrances, $apartmentsAndFlats, $manualBlock, $openCode, $autoOpen, $whiteRabbit, $sipEnabled, $sipPassword);

            /**
             * @param $flatId
             * @return boolean
             */
            abstract function deleteFlat($flatId);

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
             * @return false|array
             */
            abstract public function getDomophones();

            /**
             * @return mixed
             */
            public function getAsteriskServers() {
                return $this->config["asterisk_servers"];
            }

            /**
             * @return false|array
             */
            abstract public function getModels();

            /**
             * @return false|array
             */
            abstract public function getCMSes();

            /**
             * @param $enabled
             * @param $model
             * @param $server
             * @param $ip
             * @param $port
             * @param $credentials
             * @param $callerId
             * @param $dtmf
             * @param $comment
             * @return false|integer
             */
            abstract public function addDomophone($enabled, $model, $server, $ip, $port, $credentials, $callerId, $dtmf, $comment);

            /**
             * @param $domophoneId
             * @param $enabled
             * @param $model
             * @param $server
             * @param $ip
             * @param $port
             * @param $credentials
             * @param $callerId
             * @param $dtmf
             * @param $comment
             * @return boolean
             */
            abstract public function modifyDomophone($domophoneId, $enabled, $model, $server, $ip, $port, $credentials, $callerId, $dtmf, $comment);

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
             * @return false|array
             */
            abstract public function getSubscribers($by, $query);

            /**
             * @param $mobile
             * @param $name
             * @param $patronymic
             * @param $flatId
             * @return boolean|integer
             */
            abstract public function addSubscriber($mobile, $name, $patronymic, $flatId);

            /**
             * @param $subscriberId
             * @return boolean
             */
            abstract public function deleteSubscriber($subscriberId);

            /**
             * @param $subscriberId
             * @param $params
             * @return boolean
             */
            abstract public function modifySubscriber($subscriberId, $params);

            /**
             * @param $subscriberId
             * @param $flats
             * @return boolean
             */
            abstract public function setSubscriberFlats($subscriberId, $flats);

            abstract public function getKeys($by, $query);

            /**
             * @return boolean|integer
             */
            abstract public function addKey($rfId, $flatId);

            /**
             * @param $subscriberId
             * @return boolean
             */
            abstract public function deleteKey($subscriberId);

            /**
             * @param $subscriberId
             * @param $params
             * @return boolean
             */
            abstract public function modifyKey($subscriberId, $params);
        }
    }
