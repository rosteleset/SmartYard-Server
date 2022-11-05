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
            function changed($object_type, $object_id)
            {
                // TODO: Implement changed() method.
            }

            /**
             * @inheritDoc
             */
            function addToQueue($object_type, $object_id, $task, $params)
            {
                // TODO: Implement addToQueue() method.
            }
        }
    }
