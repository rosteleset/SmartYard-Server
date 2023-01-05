<?php

    namespace tt\workflow {

        class base extends workflow {

            /**
             * @inheritDoc
             */
            public function __construct($config, $db, $redis, $tt) {
                parent::__construct($config, $db, $redis, $tt);
            }

            /**
             * @inheritDoc
             */
            public function initProject($projectId)
            {
                error_log("******* BASE *************" . $projectId . "######################");
                return true;
            }

            /**
             * @inheritDoc
             */
            public function createIssueTemplate()
            {
                return [
                    "fields" => [
                        "subject",
                        "description",
                        "[cf]usr",
                        "attachments",
                        "tags"
                    ],
                ];
            }

            /**
             * @inheritDoc
             */
            public function availableActions($issueId)
            {
                // TODO: Implement availableActions() method.
            }

            /**
             * @inheritDoc
             */
            public function actionTemplate($issueId, $action)
            {
                // TODO: Implement actionTemplate() method.
            }

            /**
             * @inheritDoc
             */
            public function doAction($issueId, $action, $fields)
            {
                // TODO: Implement doAction() method.
            }

            /**
             * @inheritDoc
             */
            public function createIssue($issue)
            {
                // TODO: Implement createIssue() method.
            }
        }
    }