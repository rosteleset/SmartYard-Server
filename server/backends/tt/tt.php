<?php

    /**
     * backends tt namespace
     */

    namespace backends\tt {

        require_once "workflow.php";

        use api\tt\customField;
        use backends\backend;

        /**
         * base tt class
         */

        abstract class tt extends backend {

            private $workflows = [];

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
                    if ($f != "." && $f != ".." && file_exists($base . $f)) {
                        $f = pathinfo($f);
                        if ($f['extension'] === "php") {
                            $w[$f['filename']] = 'builtIn';
                        }
                    }
                }

                $base = dirname(__FILE__) . "/" . $class . "/customWorkflows/";

                if (file_exists($base)) {
                    $dir = scandir($base);

                    foreach ($dir as $f) {
                        if ($f != "." && $f != ".." && file_exists($base . $f)) {
                            $f = pathinfo($f);
                            if ($f['extension'] === "php") {
                                $w[$f['filename']] = 'custom';
                            }
                        }
                    }
                }

                $wx = [];

                foreach ($w as $workflow => $type) {
                    $wx[] = [ "file" => $workflow, "type" => $type ];
                }

                return $wx;
            }

            /**
             * load workflow
             *
             * @param $workflow
             * @return false|object
             */

            public function loadWorkflow($workflow) {

                function workflow($self, $config, $db, $redis, $workflow) {
                    if (class_exists("tt\\workflow\\" . $workflow)) {
                        $className = "tt\\workflow\\" . $workflow;
                        $w = new $className($config, $db, $redis);
                        $self->workflows[$workflow] = $w;
                        return $w;
                    } else {
                        return false;
                    }
                }

                $workflow = trim($workflow);

                if (array_key_exists($workflow, $this->workflows)) {
                    return $this->workflows[$workflow];
                }

                if (!preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*(\\[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)*$/', $workflow)) {
                    return false;
                }

                $class = get_class($this);
                $ns = __NAMESPACE__;

                if (strpos($class, $ns) === 0) {
                    $class = substr($class, strlen($ns) + 1);
                }

                $file = dirname(__FILE__) . "/" . $class . "/workflows/" . $workflow . ".php";
                $customDir = dirname(__FILE__) . "/" . $class . "/customWorkflows";
                $fileCustom = $customDir . "/" . $workflow . ".php";

                if (file_exists($customDir) && file_exists($fileCustom)) {
                    require_once $fileCustom;
                    return workflow($this, $this->config, $this->db, $this->redis, $workflow);
                } else
                if (file_exists($file)) {
                    require_once $file;
                    return workflow($this, $this->config, $this->db, $this->redis, $workflow);
                } else {
                    return false;
                }
            }

            /**
             * @param $workflow
             * @return string
             */

            public function getWorkflow($workflow) {

                $workflow = trim($workflow);

                if (!preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*(\\[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)*$/', $workflow)) {
                    return false;
                }

                $class = get_class($this);
                $ns = __NAMESPACE__;

                if (strpos($class, $ns) === 0) {
                    $class = substr($class, strlen($ns) + 1);
                }

                $file = dirname(__FILE__) . "/" . $class . "/workflows/" . $workflow . ".php";
                $customDir = dirname(__FILE__) . "/" . $class . "/customWorkflows";
                $fileCustom = $customDir . "/" . $workflow . ".php";

                if (file_exists($customDir) && file_exists($fileCustom)) {
                    return file_get_contents($fileCustom);
                } else
                if (file_exists($file)) {
                    return file_get_contents($file);
                } else {
                    return "<?php\n\n";
                }
            }

            /**
             * @param $workflow
             * @param $body
             * @return boolean
             */

            public function putWorkflow($workflow, $body) {

                $workflow = trim($workflow);

                if (!preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*(\\[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)*$/', $workflow)) {
                    return false;
                }

                $class = get_class($this);
                $ns = __NAMESPACE__;

                if (strpos($class, $ns) === 0) {
                    $class = substr($class, strlen($ns) + 1);
                }

                $dir = dirname(__FILE__) . "/" . $class . "/customWorkflows";
                $fileCustom = $dir . "/" . $workflow . ".php";

                try {
                    if (!file_exists($dir)) {
                        mkdir($dir);
                    }

                    file_put_contents($fileCustom, $body);

                    return true;
                } catch (\Exception $e) {
                    return false;
                }
            }

            /**
             * @param $workflow
             * @return boolean
             */

            public function deleteWorkflow($workflow) {
                $workflow = trim($workflow);

                if (!preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*(\\[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)*$/', $workflow)) {
                    return false;
                }

                $class = get_class($this);
                $ns = __NAMESPACE__;

                if (strpos($class, $ns) === 0) {
                    $class = substr($class, strlen($ns) + 1);
                }

                $dir = dirname(__FILE__) . "/" . $class . "/customWorkflows";
                $fileCustom = $dir . "/" . $workflow . ".php";

                try {
                    if (file_exists($fileCustom)) {
                        unlink($fileCustom);

                        return true;
                    }

                    return false;
                } catch (\Exception $e) {
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
             * @return false|integer
             */

            abstract public function addCustomField($type, $field, $fieldDisplay);

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

            /**
             * @param $customFieldId
             * @param $fieldDisplay
             * @param $fieldDescription
             * @param $regex
             * @param $format
             * @param $link
             * @param $options
             * @param $indexes
             * @param $required
             * @return boolean
             */
            abstract public function modifyCustomField($customFieldId, $fieldDisplay, $fieldDescription, $regex, $format, $link, $options, $indexes, $required);

            /**
             * @param $customFieldId
             * @return boolean
             */
            abstract public function deleteCustomField($customFieldId);

            /**
             * @return false|array
             */
            abstract public function getTags();

            /**
             * @return false|integer
             */
            abstract public function addTag($projectId, $tag);

            /**
             * @return boolean
             */
            abstract public function modifyTag($tagId, $tag);

            /**
             * @return boolean
             */
            abstract public function deleteTag($tagId);

            /**
             * @param $by
             * @param $query
             * @return false|array
             */
            abstract public function searchIssues($by, $query);

            /**
             * @return false|array
             */
            abstract public function availableFilters();

            /**
             * @param $filter
             * @return false|string
             */
            public function getFilter($filter) {

                $filter = trim($filter);

                if (!preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*(\\[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)*$/', $filter)) {
                    return false;
                }

                $class = get_class($this);
                $ns = __NAMESPACE__;

                if (strpos($class, $ns) === 0) {
                    $class = substr($class, strlen($ns) + 1);
                }

                $file = dirname(__FILE__) . "/" . $class . "/filters/" . $filter . ".json";

                if (file_exists($file)) {
                    return file_get_contents($file);
                } else {
                    return "{}";
                }
            }

            /**
             * @param $filter
             * @param $body
             * @return boolean
             */
            public function putFilter($filter, $body) {

                $filter = trim($filter);

                if (!preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*(\\[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)*$/', $filter)) {
                    return false;
                }

                $class = get_class($this);
                $ns = __NAMESPACE__;

                if (strpos($class, $ns) === 0) {
                    $class = substr($class, strlen($ns) + 1);
                }

                $dir = dirname(__FILE__) . "/" . $class . "/filters";
                $file = $dir . "/" . $filter . ".json";

                try {
                    if (!file_exists($dir)) {
                        mkdir($dir);
                    }

                    file_put_contents($file, $body);

                    return true;
                } catch (\Exception $e) {
                    return false;
                }
            }

            /**
             * @param $filter
             * @return boolean
             */
            public function deleteFilter($filter) {
                $filter = trim($filter);

                if (!preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*(\\[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)*$/', $filter)) {
                    return false;
                }

                $class = get_class($this);
                $ns = __NAMESPACE__;

                if (strpos($class, $ns) === 0) {
                    $class = substr($class, strlen($ns) + 1);
                }

                $dir = dirname(__FILE__) . "/" . $class . "/filters";
                $fileCustom = $dir . "/" . $filter . ".json";

                try {
                    if (file_exists($fileCustom)) {
                        unlink($fileCustom);

                        return true;
                    }

                    return false;
                } catch (\Exception $e) {
                    return false;
                }
            }
        }
    }