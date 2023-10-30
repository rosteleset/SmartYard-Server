<?php

    /**
     * backends queue namespace
     */

    namespace backends\queue
    {
        class internal extends queue
        {
            private array $tasks = [
                "minutely" => [
                    "autoconfigureDevices",
                ]
            ];

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
                        $this->db->insert("insert into tasks_changes (object_type, object_id) values (:object_type, :object_id)", [
                            "object_type" => $objectType,
                            "object_id" => $objectId,
                        ], [
                            "silent"
                        ]);
                        return true;

                    case "house":
                    case "entrance":
                    case "flat":
                    case "subscriber":
                    case "key":
                        if ($households) {
                            $domophones = $households->getDomophones($objectType, $objectId);
                        }
                        break;
                }

                foreach ($domophones as $domophone) {
                    $this->db->insert("insert into tasks_changes (object_type, object_id) values ('domophone', :object_id)", [
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
                $this->db->modify("delete from core_running_processes where done is not null and expire < :expire", [
                    "expire" => time(),
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
                global $script_filename;

                $deviceTypes = ['domophone', 'camera'];
                $pid = getmypid();

                foreach ($deviceTypes as $deviceType) {
                    $tasks = $this->getTasksForDeviceType($deviceType);

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

            private function autoconfigureDomophone($domophoneId, $script_filename, $pid)
            {
                $households = loadBackend('households');
                $domophone = $households->getDomophone($domophoneId);

                if (!$domophone || !$domophone['json']['useSmartConfigurator']) {
                    return;
                }

                $command = (int)$domophone['firstTime']
                    ? " --autoconfigure-device=domophone --id={$domophone["domophoneId"]} --first-time --parent-pid=$pid"
                    : " --autoconfigure-device=domophone --id={$domophone["domophoneId"]} --parent-pid=$pid";

                shell_exec(PHP_BINARY . ' ' . $script_filename . $command . " 1>/dev/null 2>&1 &");
            }

            private function autoconfigureCamera($cameraId, $script_filename, $pid)
            {
                $cameras = loadBackend('cameras');
                $camera = $cameras->getCamera($cameraId);

                if (!$camera || !$camera['json']['useSmartConfigurator']) {
                    return;
                }

                $command = " --autoconfigure-device=camera --id={$camera["cameraId"]} --parent-pid=$pid";
                shell_exec(PHP_BINARY . ' ' . $script_filename . $command . " 1>/dev/null 2>&1 &");
            }
        }
    }
