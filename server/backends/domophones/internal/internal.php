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
                    "server" => "server",
                    "ip" => "ip",
                    "port" => "port",
                    "credentials" => "credentials",
                    "caller_id" => "callerId",
                    "dtmf" => "dtmf",
                    "comment" => "comment"
                ]);
            }

            /**
             * @inheritDoc
             */
            public function addDomophone($enabled, $model, $server, $ip, $port,  $credentials, $callerId, $dtmf, $comment)
            {
                if (!$model) {
                    setLastError("moModel");
                    return false;
                }

                $models = $this->getModels();

                if (!@$models[$model]) {
                    setLastError("modelUnknown");
                    return false;
                }

                if (!trim($server)) {
                    setLastError("noServer");
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

                if (in_array(trim($dtmf), [ "*", "#", "0", "1", "2", "3", "4", "5", "6", "7", "8", "9" ]) === false) {
                    setLastError("dtmf");
                    return false;
                }

                return $this->db->insert("insert into domophones (enabled, model, server, ip, port, credentials, caller_id, dtmf, comment) values (:enabled, :model, :server, :ip, :port, :credentials, :caller_id, :dtmf, :comment)", [
                    "enabled" => (int)$enabled,
                    "model" => $model,
                    "server" => $server,
                    "ip" => $ip,
                    "port" => $port,
                    "credentials" => $credentials,
                    "caller_id" => $callerId,
                    "dtmf" => $dtmf,
                    "comment" => $comment,
                ]);
            }

            /**
             * @inheritDoc
             */
            public function modifyDomophone($domophoneId, $enabled, $model, $server, $ip, $port, $credentials, $callerId, $dtmf, $comment)
            {
                if (!checkInt($domophoneId)) {
                    setLastError("noId");
                    return false;
                }

                if (!$model) {
                    setLastError("noModel");
                    return false;
                }

                if (!trim($server)) {
                    setLastError("noServer");
                    return false;
                }

                $models = $this->getModels();

                if (!@$models[$model]) {
                    setLastError("modelUnknown");
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

                if (in_array(trim($dtmf), [ "*", "#", "0", "1", "2", "3", "4", "5", "6", "7", "8", "9" ]) === false) {
                    setLastError("dtmf");
                    return false;
                }

                return $this->db->modify("update domophones set enabled = :enabled, model = :model, server = :server, ip = :ip, port = :port, credentials = :credentials, caller_id = :caller_id, dtmf = :dtmf, comment = :comment where domophone_id = $domophoneId", [
                    "enabled" => (int)$enabled,
                    "model" => $model,
                    "server" => $server,
                    "ip" => $ip,
                    "port" => $port,
                    "credentials" => $credentials,
                    "caller_id" => $callerId,
                    "dtmf" => $dtmf,
                    "comment" => $comment,
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

            /**
             * @inheritDoc
             */
            public function getDomophone($domophoneId)
            {
                if (!checkInt($domophoneId)) {
                    return false;
                }

                return $this->db->get("select * from domophones where domophone_id = $domophoneId", false, [
                    "domophone_id" => "domophoneId",
                    "enabled" => "enabled",
                    "model" => "model",
                    "server" => "server",
                    "ip" => "ip",
                    "port" => "port",
                    "credentials" => "credentials",
                    "caller_id" => "callerId",
                    "dtmf" => "dtmf",
                    "comment" => "comment"
                ], [
                    "singlify"
                ]);
            }
        }
    }
