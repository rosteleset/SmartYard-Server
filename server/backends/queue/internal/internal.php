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
            function change($object_type, $object_id)
            {
                // TODO: Implement change() method.
            }

            /**
             * @inheritDoc
             */
            function queue($object_type, $object_id, $task, $params)
            {
                // TODO: Implement queue() method.
            }
        }
    }
