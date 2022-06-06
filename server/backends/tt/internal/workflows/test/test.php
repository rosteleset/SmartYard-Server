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
        }
    }