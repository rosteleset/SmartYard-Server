<?php

    /**
     * backends tt_journal namespace
     */

    namespace backends\tt_journal {

        use backends\backend;

        /**
         * base tt_journal class
         */

        abstract class tt_journal extends backend {

            /**
             * @param string $issueId
             * @param string $action
             * @param object $old
             * @param object $new
             * @param string $workflowAction
             * @return boolean
             */
            public abstract function journal($issueId, $action, $old, $new, $workflowAction);

            /**
             * @param string $issueId
             * @param mixed $limit
             * @return mixed
             */
            public abstract function get($issueId, $limit = false);
        }
    }

