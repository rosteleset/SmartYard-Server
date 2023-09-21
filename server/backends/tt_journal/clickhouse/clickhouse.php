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
                    @$config['clickhouse']['host']?:'127.0.0.1',
                    @$config['clickhouse']['port']?:8123,
                    @$config['clickhouse']['username']?:'default',
                    @$config['clickhouse']['password']?:'qqq',
                    @$config['clickhouse']['database']?:'default'
                );
            }

            /**
             * @inheritDoc
             */
            public function journal($issueId, $action, $old, $new, $workflowAction)
            {
                if ($old && $new) {
                    foreach ($old as $key => $field) {
                        if (!array_key_exists($key, $new)) {
                            unset($old[$key]);
                        }
                        if (@is_array(@$old[$key]) && @is_array(@$new[$key])) {
                            if (!count(array_diff($old[$key], $new[$key]))) {
                                unset($old[$key]);
                                unset($new[$key]);
                            }
                        } else
                        if (@$old[$key] == @$new[$key]) {
                            unset($old[$key]);
                            unset($new[$key]);
                        }
                    }
                }
                if (!$old && $new) {
                    foreach ($new as $key => $field) {
                        if (!$field) {
                            unset($new[$key]);
                        }
                    }
                }
                if (!$new && $old) {
                    foreach ($old as $key => $field) {
                        if (!$field) {
                            unset($old[$key]);
                        }
                    }
                }

                if ($workflowAction) {
                    $new["workflowAction"] = $workflowAction;
                }

                if ($new || $old) {
                    return $this->clickhouse->insert("ttlog", [ [ "date" => time(), "issue" => $issueId, "login" => $this->login, "action" => $action, "old" => json_encode($old), "new" => json_encode($new) ] ]);
                } else {
                    return true;
                }
            }

            /**
             * @inheritDoc
             */
            public function get($issueId, $limit = false)
            {
                if ($limit) {
                    $journal = $this->clickhouse->select("select * from default.ttlog where issue='$issueId' order by date limit $limit");
                } else {
                    $journal = $this->clickhouse->select("select * from default.ttlog where issue='$issueId' order by date");
                }

                foreach ($journal as &$record) {
                    $record["old"] = json_decode($record["old"], true);
                    $record["new"] = json_decode($record["new"], true);
                }

                return $journal;
            }
        }
    }
