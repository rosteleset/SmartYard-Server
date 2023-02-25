<?php

    /**
     * "clickhouse" accounting (logging) class
     */

    namespace backends\accounting {

        /**
         * clickhouse accounting (logging) class
         */

        class clickhouse extends accounting {
            private $clickhouse;

            /**
             * @inheritDoc
             */
            function __construct($config, $db, $redis, $login = false)
            {
                parent::__construct($config, $db, $redis, $login);

                require_once __DIR__ . '/../../../utils/clickhouse.php';

                $this->clickhouse = new \clickhouse(
                    @$config['backends']['accounting']['host']?:'127.0.0.1',
                    @$config['backends']['accounting']['port']?:8123,
                    @$config['backends']['accounting']['username']?:'default',
                    @$config['backends']['accounting']['password']?:'qqq',
                    @$config['backends']['accounting']['database']?:'default'
                );
            }

            /**
             * @inheritDoc
             */
            public function journal($issue, $action, $old, $new)
            {
                return $this->clickhouse->insert("ttlog", [ [ "date" => time(), "issue" => $issue, "login" => $this->login, "action" => $action, "old" => $old, "new" => $new ] ]);
            }

            /**
             * @inheritDoc
             */
            public function get($issue)
            {
                //
            }
        }
    }
