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
                        $this->db->insert("insert into tasks_changes (object_type, object_id) values (:object_type, :object_id)", [
                            "object_type" => $objectType,
                            "object_id" => $objectId,
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
