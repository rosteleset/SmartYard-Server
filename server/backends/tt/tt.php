<?php

    /**
     * backends tt namespace
     */

    namespace backends\tt {

        require_once "workflow.php";

        use backends\backend;

        /**
         * base tt class
         */

        abstract class tt extends backend {

            /**
             * get available workflows
             *
             * @return false|array
             */

            public function getWorkflows() {
                $class = get_class($this);
                $ns = __NAMESPACE__;

                if (strpos($class, $ns) === 0) {
                    $class = substr($class, strlen($ns) + 1);
                }

                $base = dirname(__FILE__) . "/" . $class . "/workflows/";
                $dir = scandir($base);

                $w = [];
                foreach ($dir as $f) {
                    if ($f != "." && $f != ".." && file_exists($base . $f ."/" . $f . ".php")) {
                        $w[] = $f;
                    }
                }

                return $w;
            }

            /**
             * load workflow
             *
             * @param $workflow
             * @return false|object
             */

            public function loadWorkflow($workflow) {
                $workflow = trim($workflow);

                if (!preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*(\\[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)*$/', $workflow)) {
                    error_log("preg_match fail!");
                    return false;
                }

                $class = get_class($this);
                $ns = __NAMESPACE__;

                if (strpos($class, $ns) === 0) {
                    $class = substr($class, strlen($ns) + 1);
                }

                $file = dirname(__FILE__) . "/" . $class . "/workflows/" . $workflow . "/" . $workflow . ".php";

                if (file_exists($file)) {
                    require_once $file;
                    if (class_exists("tt\\workflow\\" . $workflow)) {
                        return new ("tt\\workflow\\" . $workflow)($this->config, $this->db, $this->redis);
                    } else {
                        error_log("class not found!");
                        return false;
                    }
                } else {
                    error_log("file not found!");
                    return false;
                }
            }

            /**
             * get projects
             *
             * @return false|array[]
             */

            abstract public function getProjects();

            /**
             * @param $acronym
             * @param $project
             *
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
             * get workflow aliases
             *
             * @return false|array
             */

            abstract public function getWorkflowAliases();

            /**
             * set workflow alias
             *
             * @param $workflow
             * @param $alias
             * @return boolean
             */

            abstract public function setWorkflowAlias($workflow, $alias);

            /**
             * @param $projectId
             * @param $workflows
             * @return boolean
             */

            abstract public function setProjectWorkflows($projectId, $workflows);

            /**
             * @return false|array
             */

            abstract public function getStatuses();

            /**
             * @param $statusId
             * @param $display
             * @return boolean
             */

            abstract public function moodifyStatus($statusId, $display);

            /**
             * @return false|array
             */

            abstract public function getResolutions();

            /**
             * @param $resolution
             * @return false|integer
             */

            abstract public function addResolution($resolution);

            /**
             * @param $resolutionId
             * @param $resolution
             * @return boolean
             */
            abstract public function modifyResolution($resolutionId, $resolution);

            /**
             * @param $resolutionId
             * @return boolean
             */

            abstract public function deleteResolution($resolutionId);

            /**
             * @param $projectId
             * @param $resolutions
             * @return boolean
             */

            abstract public function setProjectResolutions($projectId, $resolutions);

            /**
             * @return array
             */

            abstract public function getCustomFields();

            /**
             * @param $type
             * @param $field
             * @param $fieldDisplay
             * @param $fieldDescription
             * @param $regex
             * @param $link
             * @return false|integer
             */

            abstract public function addCustomField($type, $field, $fieldDisplay, $fieldDescription, $regex, $link);

            /**
             * @param $projectId
             * @param $customFields
             * @return boolean
             */

            abstract public function setProjectCustomFields($projectId, $customFields);

            /**
             * @param $projectId
             * @param $uid
             * @param $roleId
             * @return false|integer
             */

            abstract public function addUserRole($projectId, $uid, $roleId);

            /**
             * @param $projectId
             * @param $gid
             * @param $roleId
             * @return false|integer
             */

            abstract public function addGroupRole($projectId, $gid, $roleId);

            /**
             * @return false|array
             */

            abstract public function getRoles();

            /**
             * @param $projectRoleId
             * @return boolean
             */
            abstract public function deleteRole($projectRoleId);

            /**
             * @param $roleId
             * @param $nameDisplay
             * @return boolean
             */
            abstract public function setRoleDisplay($roleId, $nameDisplay);
        }
    }