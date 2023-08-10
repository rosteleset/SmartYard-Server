<?php

    /**
     * backends queue namespace
     */

    namespace backends\queue
    {
        class internal extends queue
        {
            private $tasks = [
                "minutely" => [
                    "autoconfigureDomophones",
                ]
            ];

            /**
             * @inheritDoc
             */
            function changed($objectType, $objectId)
            {
                $households = loadBackend("households");
                $domophones = [];

                switch ($objectType) {
                    case "domophone":
                        $this->db->insert("insert into tasks_changes (object_type, object_id) values ('domophone', :object_id)", [
                            "object_id" => checkInt($objectId),
                        ], [
                            "silent"
                        ]);
                        return true;

                    case "house":
                    case "entrance":
                    case "flat":
                    case "subscriber":
                        if ($households) {
                            $domophones = $households->getDomophones($objectType, $objectId);
                        }
                        break;

                    case "key":
                        // TODO
                        break;
                }

                foreach ($domophones as $domophone) {
                    $this->db->insert("insert into tasks_changes (object_type, object_id) values ('domophone', :object_id)", [
                        "object_id" => checkInt($domophone["domophoneId"]),
                    ], [
                        "silent"
                    ]);
                }

                return true;
            }

            /**
             * @inheritDoc
             */
            function cron($part)
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
            function autoconfigureDomophones()
            {
                global $script_filename;

                $pid = getmypid();

                $households = loadBackend("households");

                $tasks = $this->db->get("select task_change_id, object_id from tasks_changes where object_type = 'domophone' limit 25", [], [
                    'task_change_id' => 'taskChangeId',
                    'object_id' => 'domophoneId'
                ]);

                foreach ($tasks as $task) {
                    $this->db->modify("delete from tasks_changes where task_change_id = ${task['taskChangeId']}");

                    $domophone = $households->getDomophone($task["domophoneId"]);

                    if ($domophone) {
                        if ((int)$domophone['firstTime']) {
                            shell_exec("{PHP_BINARY} {$script_filename} --autoconfigure-domophone={$domophone["domophoneId"]} --first-time --parent-pid=$pid 1>/dev/null 2>&1 &");
                        } else {
                            shell_exec("{PHP_BINARY} {$script_filename} --autoconfigure-domophone={$domophone["domophoneId"]} --parent-pid=$pid 1>/dev/null 2>&1 &");
                        }
                    }
                }

                $this->wait();
            }

            /**
             * @inheritDoc
             */
            function wait()
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
        }
    }
