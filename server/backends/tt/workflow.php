<?php

    namespace tt\workflow {

        class workflow {

            /**
             * @var object $config link to config structute
             * @var object $db link to default PDO database object
             * @var object $redis link to redis object
             */

            protected $config, $db, $redis, $tt, $sandbox;

            /**
             * default constructor
             *
             * @param object $config link to config structute
             * @param object $db link to default PDO database object
             * @param object $redis link to redis object
             *
             * @return void
             */

            public function __construct($config, $db, $redis, $tt, $workflow) {
                $this->config = $config;
                $this->db = $db;
                $this->redis = $redis;
                $this->tt = $tt;
                $this->sandbox = new \LuaSandbox;

                $file = __DIR__ . "/workflows/" . $workflow . ".lua";
                $customDir = __DIR__ . "/workflowsCustom";
                $fileCustom = $customDir . "/" . $workflow . ".lua";

                if (file_exists($customDir) && file_exists($fileCustom)) {
                    $file = $fileCustom;
                } else
                if (!file_exists($file)) {
                    $file = false;
                }

                if ($file) {
                    $code = $this->sandbox->loadString(file_get_contents($file));
                    $code->call();
                } else {
                    throw new Exception("workflow not found");
                }
            }

            /**
             * @param $projectId
             * @return boolean
             */
            public function initProject($projectId)
            {
                return $this->sandbox->callFunction("initProject", $projectId);
            }

            /**
             * @return false|array
             */
            public function createIssueTemplate()
            {
                return $this->sandbox->callFunction("createIssueTemplate")[0];
            }

            /**
             * @param $issueId
             * @return false|array
             */
            public function availableActions($issueId)
            {

            }

            /**
             * @param $issueId
             * @param $action
             * @return false|array
             */
            public function actionTemplate($issueId, $action)
            {

            }

            /**
             * @param $issueId
             * @param $action
             * @param $fields
             * @return boolean
             */
            public function doAction($issueId, $action, $fields)
            {

            }

            /**
             * @param $issue
             * @return false|string
             */
            public function createIssue($issue)
            {

            }
        }
    }