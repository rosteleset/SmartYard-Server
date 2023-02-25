<?php

    /**
     * "clickhouse" tt_journal (logging) class
     */

    namespace backends\tt_journal {

        /**
         * clickhouse tt_journal (logging) class
         */

        class clickhouse extends tt_journal {
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
                if ($old && $new) {
                    foreach ($old as $key => $field) {
                        if (!array_key_exists($key, $new)) {
                            unset($old[$key]);
                        }
                        if ($old[$key] == $new[$key]) {
                            unset($old[$key]);
                            unset($new[$key]);
                        }
                    }
                }
                if (!$old) {
                    foreach ($new as $key => $field) {
                        if (!$field) {
                            unset($new[$key]);
                        }
                    }
                }
                if (!$new) {
                    foreach ($old as $key => $field) {
                        if (!$field) {
                            unset($old[$key]);
                        }
                    }
                }
                return $this->clickhouse->insert("ttlog", [ [ "date" => time(), "issue" => $issue, "login" => $this->login, "action" => $action, "old" => json_encode($old), "new" => json_encode($new) ] ]);
            }

            /**
             * @inheritDoc
             */
            public function get($issue, $limit = false)
            {
                if ($limit) {
                    $journal = $this->clickhouse->select("select * from default.ttlog where issue='$issue' order by date desc limit $limit");
                } else {
                    $journal = $this->clickhouse->select("select * from default.ttlog where issue='$issue' order by date desc");
                }

                foreach ($journal as &$record) {
                    $record["old"] = json_decode($record["old"], true);
                    $record["new"] = json_decode($record["new"], true);
                }

                return $journal;
            }
        }
    }
