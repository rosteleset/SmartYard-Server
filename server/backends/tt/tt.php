<?php

    require_once "workflow.php";

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

            /**
             * get types
             *
             * @return false|array[]
             */

            abstract public function getIssueTypes();

            /**
             * get type
             *
             * @param $typeId integer typeId
             * @return false|array
             */

            abstract public function getIssueType($typeId);

            /**
             * @param $type
             * @return false|integer
             */

            abstract public function addIssueType($type);

            /**
             * @param $typeId integer
             * @param $type string
             * @return boolean
             */

            abstract public function modifyIssueType($typeId, $type);

            /**
             * delete type and all it derivatives
             *
             * @param $typeId
             * @return boolean
             */

            abstract public function deleteIssueType($typeId);

            /**
             * get type to projects
             *
             * @param $typeId integer
             *
             * @return boolean|array[]
             */

            abstract public function getIssueTypeProjects($typeId);

            /**
             * set type to projects
             *
             * @param $typeId integer
             * @param $projects array[]
             *
             * @return boolean
             */

            abstract public function setIssueTypeProjects($typeId, $projects);
        }
    }