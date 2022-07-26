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
            public function progressAction($issueId, $action, $fields)
            {
                // TODO: Implement progressAction() method.
            }

            /**
             * @inheritDoc
             */
            public function getIssues($filter)
            {
                // TODO: Implement getIssues() method.
            }

            /**
             * @inheritDoc
             */
            public function searchIssues($by, $query)
            {
                // TODO: Implement searchIssues() method.
            }
        }
    }