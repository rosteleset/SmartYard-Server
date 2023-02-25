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
             * @param string $issue
             * @param string $action
             * @param object $old
             * @param object $new
             * @return boolean
             */
            public abstract function journal($issue, $action, $old, $new);

            /**
             * @param string $issue
             * @return mixed
             */
            public abstract function get($issue);
        }
    }

