<?php

    /**
     * backends domophones namespace
     */

    namespace backends\domophones
    {

        /**
         * internal.db domophones class
         */
        class internal extends domophones
        {
            /**
             * @inheritDoc
             */
            public function getDomophones()
            {
                return $this->db->get("select * from domophones order by domophone_id", false, [
                    "domophone_id" => "domophoneId",
                    "enabled" => "enabled",
                    "model" => "model",
                    "cms" => "cms",
                    "ip" => "ip",
                    "port" => "port",
                    "credentials" => "credentials",
                    "caller_id" => "callerId",
                    "comment" => "comment",
                    "locks_disabled" => "locksDisabled",
                    "cms_levels" => "cmsLevels"
                ]);
            }

            /**
             * @inheritDoc
             */
            public function addDomophone($enabled, $model, $cms, $ip, $port,  $credentials, $callerId, $comment, $locksDisabled, $cmsLevels)
            {
                if (!$model) {
                    return false;
                }

                $models = $this->getModels();
                $cmses = $this->getCMSes();

                if (!@$models[$model]) {
                    return false;
                }

                if (!$cms) {
                    $cms = null;
                }

                if ($cms && !@$cmses[$cms]) {
                    return false;
                }

                $ip = ip2long($ip);

                if (!$ip) {
                    return false;
                }

                $port = (int)$port;

                if ($port < 0 || $port >= 65536) {
                    return false;
                }

                if (!$port) {
                    $port = 80;
                }

                $ip = long2ip($ip);

                return $this->db->insert("insert into domophones (enabled, model, cms, ip, port, credentials, caller_id, comment, locks_disabled, cms_levels) values (:enabled, :model, :cms, :ip, :port, :credentials, :caller_id, :comment, :locks_disabled, :cms_levels)", [
                    "enabled" => (int)$enabled,
                    "model" => $model,
                    "cms" => $cms,
                    "ip" => $ip,
                    "port" => $port,
                    "credentials" => $credentials,
                    "caller_id" => $callerId,
                    "comment" => $comment,
                    "locks_disabled" => (int)$locksDisabled,
                    "cms_levels" => $cmsLevels,
                ]);
            }

            /**
             * @inheritDoc
             */
            public function modifyDomophone($domophoneId, $enabled, $model, $cms, $ip, $port, $credentials, $callerId, $comment, $locksDisabled, $cmsLevels)
            {
                if (!checkInt($domophoneId)) {
                    setLastError("noId");
                    return false;
                }

                if (!$model) {
                    setLastError("noModel");
                    return false;
                }

                $models = $this->getModels();
                $cmses = $this->getCMSes();

                if (!@$models[$model]) {
                    setLastError("modelUnknown");
                    return false;
                }

                error_log(">>>>>$cms<<<<<");

                if (!$cms) {
                    $cms = null;
                }

                if ($cms && !@$cmses[$cms]) {
                    setLastError("invalidCms");
                    return false;
                }

                $ip = ip2long($ip);

                if (!$ip) {
                    setLastError("noIp");
                    return false;
                }

                $port = (int)$port;

                if ($port < 0 || $port >= 65536) {
                    setLastError("invalidPort");
                    return false;
                }

                if (!$port) {
                    $port = 80;
                }

                $ip = long2ip($ip);

                return $this->db->modify("update domophones set enabled = :enabled, model = :model, cms = :cms, ip = :ip, port = :port, credentials = :credentials, caller_id = :caller_id, comment = :comment, locks_disabled = :locks_disabled, cms_levels = :cms_levels where domophone_id = $domophoneId", [
                    "enabled" => (int)$enabled,
                    "model" => $model,
                    "cms" => $cms,
                    "ip" => $ip,
                    "port" => $port,
                    "credentials" => $credentials,
                    "caller_id" => $callerId,
                    "comment" => $comment,
                    "locks_disabled" => (int)$locksDisabled,
                    "cms_levels" => $cmsLevels,
                ]);
            }

            /**
             * @inheritDoc
             */
            public function deleteDomophone($domophoneId)
            {
                if (!checkInt($domophoneId)) {
                    setLastError("noId");
                    return false;
                }

                return
                    $this->db->modify("delete from domophones where domophone_id = $domophoneId") !== false
                    and
                    $this->db->modify("delete from domophones_cmses where domophone_id = $domophoneId") !== false;
            }

            /**
             * @inheritDoc
             */
            public function getCms($domophoneId)
            {
                // TODO: Implement getCms() method.
            }

            /**
             * @inheritDoc
             */
            public function setCms($domophoneId, $cms)
            {
                // TODO: Implement setCms() method.
            }

            /**
             * @inheritDoc
             */
            public function getModels()
            {
                $files = scandir(__DIR__ . "/../../../hw/domophones/models");

                $models = [];

                foreach ($files as $file) {
                    if (substr($file, -5) === ".json") {
                        $models[$file] = json_decode(file_get_contents(__DIR__ . "/../../../hw/domophones/models/" . $file), true);
                    }
                }

                return $models;
            }

            /**
             * @inheritDoc
             */
            public function getCMSes()
            {
                $files = scandir(__DIR__ . "/../../../hw/domophones/cmses");

                $cmses = [];

                foreach ($files as $file) {
                    if (substr($file, -5) === ".json") {
                        $cmses[$file] = json_decode(file_get_contents(__DIR__ . "/../../../hw/domophones/cmses/" . $file), true);
                    }
                }

                return $cmses;
            }
        }
    }
