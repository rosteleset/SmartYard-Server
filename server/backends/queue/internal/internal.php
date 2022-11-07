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
                switch ($objectType) {
                    case "domophone":
                        $this->db->insert("insert into tasks_changes (object_type, object_id) values ('domophone', :object_id)", [
                            "object_id" => checkInt($objectId),
                        ]);
                        break;
                    case "house":
                        //
                        break;
                    case "entrance":
                        //
                        break;
                    case "flat":
                        //
                        break;
                    case "subscriber":
                        //
                        break;
                }
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
