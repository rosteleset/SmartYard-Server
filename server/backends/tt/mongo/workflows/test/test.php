<?php

    namespace tt\workflow {

        class test extends workflow {

            /**
             * @inheritDoc
             */
            public function initProject($projectId)
            {
                error_log("******* TEST *************" . $projectId . "######################");
                return true;
            }

            /**
             * @inheritDoc
             */
            public function initIssue($issueId)
            {
                error_log("------- TEST -------------" . $issueId . "++++++++++++++++++++++");
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
                        "tags"
                    ],
                    "customFields" => [

                    ]
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