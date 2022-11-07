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
                // TODO: Implement changed() method.
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
