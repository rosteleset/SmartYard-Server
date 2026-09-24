<?php

    /**
     * backends queue namespace
     */

    namespace backends\queue {

        class internal extends queue {

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

            public function getTasks(): array {
                return $this->db->get("select * from tasks_changes", false, [
                    "task_change_id" => "taskId",
                    "object_type" => "objectType",
                    "object_id" => "objectId",
                ]);
            }

            /**
             * @inheritDoc
             */

            public function changed($objectType, $objectId) {
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

            public function cron($part) {
                if (@$this->tasks[$part]) {
                    foreach ($this->tasks[$part] as $task) {
                        $this->$task();
                    }
                    return true;
                } else {
                    return parent::cron($part);
                }
            }

            /**
             * @inheritDoc
             */

            public function autoconfigureDevices() {
                global $script_filename;

                $deviceTypes = ['domophone', 'camera'];
                $pid = getmypid();
                $maxConcurrentTasks = filter_var($this->bconfig['max_concurrent_tasks'] ?? 25, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 25;
                $running = (int)$this->db->get("select count(*) from core_running_processes where done is null and (params like '--autoconfigure-device=domophone %' or params like '--autoconfigure-device=camera %')", [], false, ['fieldlify']);
                $slots = max(0, $maxConcurrentTasks - $running);

                foreach ($deviceTypes as $deviceType) {
                    if (!$slots) {
                        break;
                    }

                    $tasks = $this->getTasksForDeviceType($deviceType, $slots);

                    foreach ($tasks as $task) {
                        $taskChangeId = $task['taskChangeId'];
                        $objectType = $task['objectType'];
                        $deviceId = $task['deviceId'];

                        $this->db->modify("delete from tasks_changes where task_change_id = $taskChangeId");

                        if ($objectType === 'domophone') {
                            $this->autoconfigureDomophone($deviceId, $script_filename, $pid);
                        } elseif ($objectType === 'camera') {
                            $this->autoconfigureCamera($deviceId, $script_filename, $pid);
                        }

                        $slots--;
                    }
                }
            }

            /**
             * @inheritDoc
             */

            public function wait() {
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

            private function getTasksForDeviceType($deviceType, $limit) {
                $query = "select task_change_id, object_type, object_id from tasks_changes tc
                    where object_type = :device_type
                    and not exists (
                        select 1 from core_running_processes rp
                        where rp.done is null
                        and (rp.params = '--autoconfigure-device=' || tc.object_type || ' --id=' || tc.object_id
                            or rp.params like '--autoconfigure-device=' || tc.object_type || ' --id=' || tc.object_id || ' %')
                    )
                    order by task_change_id limit :limit";

                return $this->db->get($query, ['device_type' => $deviceType, 'limit' => $limit], [
                    'task_change_id' => 'taskChangeId',
                    'object_type' => 'objectType',
                    'object_id' => 'deviceId',
                ]);
            }

            private function autoconfigureDomophone($domophoneId, $script_filename, $pid) {
                $households = loadBackend('households');
                $domophone = $households->getDomophone($domophoneId);

                if (!$domophone || !$domophone['json']['useSmartConfigurator']) {
                    return;
                }

                $command = (int)$domophone['firstTime']
                    ? " --autoconfigure-device=domophone --id={$domophone["domophoneId"]} --first-time --parent-pid=$pid"
                    : " --autoconfigure-device=domophone --id={$domophone["domophoneId"]} --parent-pid=$pid";

                $this->startAutoconfiguration($script_filename, $command);
            }

            private function autoconfigureCamera($cameraId, $script_filename, $pid) {
                $cameras = loadBackend('cameras');
                $camera = $cameras->getCamera($cameraId);

                if (!$camera || !$camera['json']['useSmartConfigurator']) {
                    return;
                }

                $command = " --autoconfigure-device=camera --id={$camera["cameraId"]} --parent-pid=$pid";
                $this->startAutoconfiguration($script_filename, $command);
            }

            private function startAutoconfiguration($scriptFilename, $command) {
                $timeout = filter_var($this->bconfig['task_timeout'] ?? 3600, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 3600;
                shell_exec('timeout -k 5s ' . $timeout . 's ' . PHP_BINARY . ' ' . $scriptFilename . $command . " 1>/dev/null 2>&1 &");
            }
        }
    }
