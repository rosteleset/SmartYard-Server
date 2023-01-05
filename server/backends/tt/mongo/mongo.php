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

                if (@$config["backends"]["files"]["uri"]) {
                    $this->mongo = new \MongoDB\Client($config["backends"]["files"]["uri"]);
                } else {
                    $this->mongo = new \MongoDB\Client();
                }

                $this->collection = $this->mongo->tt->issues;
            }
        }
    }
