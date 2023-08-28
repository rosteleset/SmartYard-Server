<?php

/**
 * backends queue namespace
 */

namespace backends\queue {
    class internal extends queue
    {
        private $tasks = [];

        /**
         * @inheritDoc
         */
        function changed($objectType, $objectId)
        {
            $households = loadBackend("households");
            $domophones = [];

            switch ($objectType) {
                case "domophone":
                    $this->db->insert("insert into tasks_changes (object_type, object_id) values ('domophone', :object_id)", ["object_id" => check_int($objectId),], ["silent"]);

                    return true;

                case "house":
                case "entrance":
                case "flat":
                case "subscriber":
                    if ($households)
                        $domophones = $households->getDomophones($objectType, $objectId);

                    break;
            }

            foreach ($domophones as $domophone)
                $this->db->insert("insert into tasks_changes (object_type, object_id) values ('domophone', :object_id)", ["object_id" => check_int($domophone["domophoneId"]),], ["silent"]);

            return true;
        }

        /**
         * @inheritDoc
         */
        function cron($part)
        {
            $this->db->modify("delete from core_running_processes where done is not null and expire < :expire", ["expire" => time()]);

            if (@$this->tasks[$part]) {
                foreach ($this->tasks[$part] as $task)
                    $this->$task();

                $this->wait();

                return true;
            } else return parent::cron($part);
        }

        /**
         * @inheritDoc
         */
        function wait()
        {
            $pid = getmypid();

            while (true) {
                $running = @(int)$this->db->get("select count(*) from core_running_processes where (done is null or done = 0) and ppid = $pid", [], [], ["fieldlify"]);

                if ($running) sleep(1);
                else break;
            }
        }
    }
}
