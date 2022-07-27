<?php

    namespace tt\workflow {

        class base extends workflow {

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
            public function initIssue($issueId)
            {
                error_log("------- BASE -------------" . $issueId . "++++++++++++++++++++++");
                return true;
            }

            /**
             * @inheritDoc
             */
            public function createIssueTemplate()
            {
                // TODO: Implement createIssueTemplate() method.
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

            /**
             * @inheritDoc
             */
            public function availableFilters($projectId)
            {
                // TODO: Implement availableFilters() method.
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