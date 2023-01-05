<?php

    /**
     * backends tt namespace
     */

    namespace backends\tt {

        /**
         * internal.db + mongoDB tt class
         */

        require_once __DIR__ . "/../db/db.php";

        class mongo extends tt {

            use db;

            protected $mongo, $collection;

            /**
             * @inheritDoc
             */
            public function __construct($config, $db, $redis) {
                parent::__construct($config, $db, $redis);

                require_once __DIR__ . "/../../../mzfc/mongodb/mongodb.php";

                if (@$config["backends"]["tt"]["uri"]) {
                    $this->mongo = new \MongoDB\Client($config["backends"]["tt"]["uri"]);
                } else {
                    $this->mongo = new \MongoDB\Client();
                }

                $collection = @$config["backends"]["tt"]["collection"]?:"tt";
                $this->collection = $this->mongo->tt->$collection;
            }

            /**
             * @inheritDoc
             */
            public function createIssue($issue)
            {
                // TODO: Implement createIssue() method.
            }

            /**
             * @inheritDoc
             */
            public function modifyIssue($issue)
            {
                // TODO: Implement modifyIssue() method.
            }

            /**
             * @inheritDoc
             */
            public function deleteIssue($issue)
            {
                // TODO: Implement deleteIssue() method.
            }

            /**
             * @inheritDoc
             */
            public function getIssues($query)
            {
                // TODO: Implement getIssues() method.
            }
        }
    }
