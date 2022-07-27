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

            /**
             * @param $projectId
             * @return boolean
             */
            abstract public function initProject($projectId);

            /**
             * @param $issueId
             * @return boolean
             */
            abstract public function initIssue($issueId);

            /**
             * @return false|array
             */
            abstract public function createIssueTemplate();

            /**
             * @param $issueId
             * @return false|array
             */
            abstract public function availableActions($issueId);

            /**
             * @param $issueId
             * @param $action
             * @return false|array
             */
            abstract public function actionTemplate($issueId, $action);

            /**
             * @param $issueId
             * @param $action
             * @param $fields
             * @return boolean
             */
            abstract public function progressAction($issueId, $action, $fields);

            /**
             * @param $filter
             * @return false|array
             */
            abstract public function getIssues($filter);

            /**
             * @param $by
             * @param $query
             * @return false|array
             */
            abstract public function searchIssues($by, $query);

            /**
             * @param $projectId
             * @return false|array
             */
            abstract public function availableFilters($projectId);

            /**
             * @param $issue
             * @return false|string
             */
            abstract public function createIssue($issue);
        }
    }