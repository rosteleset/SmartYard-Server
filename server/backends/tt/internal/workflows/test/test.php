<?php

    namespace tt\workflow {

        class test extends workflow {

            public function getStatuses()
            {
                // TODO: Implement getStatuses() method.
            }

            public function getResolutions()
            {
                // TODO: Implement getResolutions() method.
            }

            public function getCustomFields()
            {
                // TODO: Implement getCustomFields() method.
            }

            public function initProject($projectId)
            {
                error_log("******* TEST *************" . $projectId . "######################");
            }

            public function initIssue($issueId)
            {
                error_log("------- TEST -------------" . $issueId . "++++++++++++++++++++++");
            }
        }
    }