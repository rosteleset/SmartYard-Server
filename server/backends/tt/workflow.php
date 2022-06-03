<?php

    namespace tt\workflow {

        abstract class workflow {

            /**
             * @var object $config link to config structute
             * @var object $db link to default PDO database object
             * @var object $redis link to redis object
             */

            protected $config, $db, $redis;

            /**
             * default constructor
             *
             * @param object $config link to config structute
             * @param object $db link to default PDO database object
             * @param object $redis link to redis object
             *
             * @return void
             */

            public function __construct($config, $db, $redis) {
                $this->config = $config;
                $this->db = $db;
                $this->redis = $redis;
            }

            abstract public function initProject($projectId);

            abstract public function initIssue($issueId);

            abstract public function getStatuses();

            abstract public function getResolutions();

            abstract public function getCustomFields();
        }
    }