<?php

    /**
     * backends queue namespace
     */

    namespace backends\queue
    {
        class internal extends queue
        {
            private const RFID_ACCESS_TYPES = [
                0 => 'all',
                1 => 'subscriber',
                2 => 'flat',
                3 => 'entrance',
                4 => 'house',
                5 => 'company',
            ];

            private array $tasks = [
                "minutely" => [
                    "autoconfigureDevices",
                ]
            ];

            /**
             * @inheritDoc
             */
            public function getTasks(): array
            {
                return $this->db->get("select * from tasks_changes", false, [
                    "task_change_id" => "taskId",
                    "object_type" => "objectType",
                    "object_id" => "objectId",
                ]);
            }

            /**
             * @inheritDoc
             */
            public function changed($objectType, $objectId)
            {
                $households = loadBackend("households");
                $domophones = [];

                switch ($objectType) {
                    case "domophone":
                    case "camera":
                        $this->db->insert("insert into tasks_changes (object_type, object_id) values (:object_type, :object_id) on conflict (object_type, object_id) do nothing", [
                            "object_type" => $objectType,
                            "object_id" => $objectId,
                        ], [
                            "silent"
                        ]);
                        return true;

                    case "company":
                    case "house":
                    case "entrance":
                    case "flat":
                    case "subscriber":
                        $domophones = $households->getDomophones($objectType, $objectId);
                        break;

                    case "key":
                        $rfidQuery = "select access_type, access_to from houses_rfids where house_rfid_id = $objectId";

                        [
                            'access_type' => $accessType,
                            'access_to' => $accessTo,
                        ] = $this->db->get($rfidQuery, false, false, ['singlify']);

                        $domophones = $households->getDomophones(self::RFID_ACCESS_TYPES[$accessType], $accessTo);
                        break;
                }

                foreach ($domophones as $domophone) {
                    $this->db->insert("insert into tasks_changes (object_type, object_id) values ('domophone', :object_id) on conflict (object_type, object_id) do nothing", [
                        "object_id" => $domophone["domophoneId"],
                    ], [
                        "silent"
                    ]);
                }

                return true;
            }

            /**
             * @inheritDoc
             */
            public function cron($part)
            {
                $this->db->modify("delete from core_running_processes where (done is not null and expire < :expire) or (done is not null and start < :start)", [
                    "expire" => time(),
                    "start" => time() - 7 * 24 * 60 * 60,
                ]);

                if (@$this->tasks[$part]) {
                    foreach ($this->tasks[$part] as $task) {
                        $this->$task();
                    }
                    $this->wait();
                    return true;
                } else {
                    return parent::cron($part);
                }
            }

            /**
             * @inheritDoc
             */
            public function autoconfigureDevices()
            {
                $deviceTypes = ['domophone', 'camera'];
                $pid = getmypid();

                foreach ($deviceTypes as $deviceType) {
                    $tasks = $this->getTasksForDeviceType($deviceType);

                    foreach ($tasks as $task) {
                        $taskChangeId = $task['taskChangeId'];
                        $objectType = $task['objectType'];
                        $deviceId = $task['deviceId'];

                        $this->db->modify("delete from tasks_changes where task_change_id = $taskChangeId");
                        $isUnavailable = false;

                        if ($objectType === 'domophone') {
                            $isUnavailable = !$this->autoconfigureDomophone($deviceId, $pid);
                        } elseif ($objectType === 'camera') {
                            $isUnavailable = !$this->autoconfigureCamera($deviceId, $pid);
                        }

                        // TODO: [Temporary] solution that recreates tasks if the device is unavailable
                        if ($isUnavailable) {
                            $this->changed($deviceType, $deviceId);
                        }
                    }
                }

                $this->wait();
            }

            /**
             * @inheritDoc
             */
            public function wait()
            {
                $pid = getmypid();

                while (true) {
                    $running = @(int)$this->db->get("select count(*) from core_running_processes where (done is null or done = 0) and ppid = $pid", [], [], [ "fieldlify" ]);
                    if ($running) {
                        sleep(1);
                    } else {
                        break;
                    }
                }
            }

            private function getTasksForDeviceType($deviceType)
            {
                $query = "select * from tasks_changes where object_type = '$deviceType' limit 25";

                return $this->db->get($query, [], [
                    'task_change_id' => 'taskChangeId',
                    'object_type' => 'objectType',
                    'object_id' => 'deviceId',
                ]);
            }

            private function autoconfigureDomophone($domophoneId, $pid): bool
            {
                $households = loadBackend('households');
                $domophone = $households->getDomophone($domophoneId);

                if (!$domophone || !($domophone['json']['useSmartConfigurator'] ?? true)) {
                    return true;
                }

                $command = (int)$domophone['firstTime']
                    ? "--autoconfigure-device=domophone --id={$domophone["domophoneId"]} --first-time --parent-pid=$pid"
                    : "--autoconfigure-device=domophone --id={$domophone["domophoneId"]} --parent-pid=$pid";

                return $this->executeCommand($command);
            }

            private function autoconfigureCamera($cameraId, $pid): bool
            {
                $cameras = loadBackend('cameras');
                $camera = $cameras->getCamera($cameraId);

                if (!$camera || !($camera['json']['useSmartConfigurator'] ?? true)) {
                    return true;
                }

                $command = "--autoconfigure-device=camera --id={$camera["cameraId"]} --parent-pid=$pid";
                return $this->executeCommand($command);
            }

            private function executeCommand(string $command): bool
            {
                global $script_filename;

                exec(PHP_BINARY . ' ' . $script_filename . ' ' . $command, $output, $resultCode);

                if ($resultCode !== 0 && str_contains($output[0], 'Device is unavailable')) {
                    return false;
                }

                return true;
            }
        }
    }
