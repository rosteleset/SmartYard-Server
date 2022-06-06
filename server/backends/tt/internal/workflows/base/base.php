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
        }
    }