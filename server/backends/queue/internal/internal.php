<?php

    /**
     * backends queue namespace
     */

    namespace backends\queue
    {
        class internal extends queue
        {
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
                        if ($households) {
                            $domophones = $households->getDomophones("house", $objectId);
                        }
                        break;

                    case "entrance":
                        $domophones = $households->getDomophones("entrance", $objectId);
                        break;

                    case "flat":
                        $domophones = $households->getDomophones("flat", $objectId);
                        break;

                    case "subscriber":
                        $domophones = $households->getDomophones("subscriber", $objectId);
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
                if ($part == "minutely") {
                    return false;
                } else {
                    return true;
                }
            }
        }
    }
