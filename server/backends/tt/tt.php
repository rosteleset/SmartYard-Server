<?php

    /**
     * backends tt namespace
     */

    namespace backends\tt {

        use backends\backend;

        /**
         * base tt class
         */

        abstract class tt extends backend {

            /* project(s) */

            /**
             * get projects
             *
             * @return false|array[]
             */

            abstract public function getProjects();

            /**
             * get project
             *
             * @param $projectId integer projectId
             * @return false|array
             */

            abstract public function getProject($projectId);

            /**
             * @param $acronym
             * @param $project
             * @return false|integer
             */

            abstract public function addProject($acronym, $project);

            /**
             * @param $projectId integer
             * @param $acronym string
             * @param $project string
             * @return boolean
             */

            abstract public function modifyProject($projectId, $acronym, $project);

            /**
             * delete project and all it derivatives
             *
             * @param $projectId
             * @return boolean
             */

            abstract public function deleteProject($projectId);
        }
    }