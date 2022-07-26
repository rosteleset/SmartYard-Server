<?php

    namespace tt\workflow {

        abstract class filter {

            protected $config, $db, $redis;

            /**
             * @param $config
             * @param $db
             * @param $redis
             */
            public function __construct($config, $db, $redis) {
                $this->config = $config;
                $this->db = $db;
                $this->redis = $redis;
            }

            /**
             * @param $projectId
             * @return false|array
             */
            abstract public function getIssues($projectId);
        }
    }
