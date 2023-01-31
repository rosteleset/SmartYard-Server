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

            private $workflows = [];

            /**
             * get available workflows
             *
             * @return array
             */

            public function getWorkflows() {
                $base = __DIR__ . "/workflows/";
                $dir = scandir($base);

                $w = [];
                foreach ($dir as $f) {
                    if ($f != "." && $f != ".." && file_exists($base . $f)) {
                        $f = pathinfo($f);
                        if ($f['extension'] === "lua") {
                            $w[$f['filename']] = 'builtIn';
                        }
                    }
                }

                $base = __DIR__ . "/workflowsCustom/";

                if (file_exists($base)) {
                    $dir = scandir($base);

                    foreach ($dir as $f) {
                        if ($f != "." && $f != ".." && file_exists($base . $f)) {
                            $f = pathinfo($f);
                            if ($f['extension'] === "lua") {
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
                $workflow = trim($workflow);

                if (array_key_exists($workflow, $this->workflows)) {
                    return $this->workflows[$workflow];
                }

                if (!preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*(\\[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)*$/', $workflow)) {
                    return false;
                }

                try {
                    return $this->workflows[$workflow] = new \tt\workflow\workflow($this->config, $this->db, $this->redis, $this, $workflow);
                } catch (\Exception $e) {
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

                $file = __DIR__ . "/workflows/" . $workflow . ".lua";
                $customDir = __DIR__ . "/workflowsCustom";
                $fileCustom = $customDir . "/" . $workflow . ".lua";

                if (file_exists($customDir) && file_exists($fileCustom)) {
                    return file_get_contents($fileCustom);
                } else
                if (file_exists($file)) {
                    return file_get_contents($file);
                } else {
                    return "";
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

                $dir = __DIR__ . "/workflowsCustom";
                $fileCustom = $dir . "/" . $workflow . ".lua";

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

                $dir = __DIR__ . "/workflowsCustom";
                $fileCustom = $dir . "/" . $workflow . ".lua";

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
             * @return false|integer
             */

            abstract public function addProject($acronym, $project);

            /**
             * @param $projectId integer
             * @param $acronym string
             * @param $project string
             * @param $maxFileSize
             * @param $allowedMimeTypes
             * @return boolean
             */

            abstract public function modifyProject($projectId, $acronym, $project, $maxFileSize, $allowedMimeTypes);

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

            abstract public function addResolution($resolution, $protected = 0);

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
             * @param $filter
             * @return mixed
             */
            abstract public function filterAvailable($filter);

            /**
             * @param $filter
             * @param $uid
             * @param $gid
             * @return mixed
             */
            abstract public function addFilterAvailable($filter, $uid, $gid);

            /**
             * @param $filter_available_id
             * @return mixed
             */
            abstract public function deleteFilterAvailable($filter_available_id);

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
             * @param $editor
             * @return boolean
             */
            abstract public function modifyCustomField($customFieldId, $fieldDisplay, $fieldDescription, $regex, $format, $link, $options, $indexes, $required, $editor);

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
             * @return false|array
             */
            public function availableFilters() {
                $filters = glob(__DIR__ . "/filters/*.json");

                $list = [];

                foreach ($filters as $filter) {
                    $filter = pathinfo($filter);

                    try {
                        $f = json_decode($this->getFilter($filter["filename"]), true);
                        $list[$filter["filename"]] = @$f["name"];
                    } catch (\Exception $e) {
                        $list[$filter["filename"]] = $filter["filename"];
                    }
                }

                return $list;
            }

            /**
             * @param $filter
             * @return false|string
             */
            public function getFilter($filter) {

                $filter = trim($filter);

                if (!preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*(\\[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)*$/', $filter)) {
                    return false;
                }

                $file = __DIR__ . "/filters/" . $filter . ".json";

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

                $dir = __DIR__ . "/filters";
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

                $dir = __DIR__ . "/filters";
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

            /**
             * @param $field
             * @param $name
             * @return mixed
             */
            abstract public function addViewer($field, $name);

            /**
             * @param $name
             * @param $code
             * @return mixed
             */
            abstract public function modifyViewer($name, $code);

            /**
             * @param $name
             * @return mixed
             */
            abstract public function deleteViewer($name);

            /**
             * @return mixed
             */
            abstract public function getViewers();

            /**
             * @param $projectId
             * @return mixed
             */
            abstract public function getProjectViewers($projectId);

            /**
             * @param $projectId
             * @param $viewers
             * @return mixed
             */
            abstract public function setProjectViewers($projectId, $viewers);

            /**
             * @return mixed
             */
            abstract public function getCrontabs();

            /**
             * @param $crontab
             * @param $filter
             * @param $uid
             * @param $action
             * @return mixed
             */
            abstract public function addCrontab($crontab, $filter, $uid, $action);

            /**
             * @param $crontabId
             * @return mixed
             */
            abstract public function deleteCrontab($crontabId);

            /**
             * @param $issue
             * @return mixed
             */
            abstract public function createIssue($issue);

            /**
             * @param $issue
             * @return mixed
             */
            abstract public function modifyIssue($issue);

            /**
             * @param $issue
             * @return mixed
             */
            abstract public function deleteIssue($issue);

            /**
             * @param $query
             * @param $fields
             * @param $sort
             * @param $skip
             * @param $limit
             * @return mixed
             */
            abstract public function getIssues($query, $fields = [], $sort = [ "issue_id" => 1 ], $skip = 0, $limit = 100);

            /**
             * @param $issue
             * @param $comment
             * @return mixed
             */
            abstract public function addComment($issue, $comment);

            /**
             * @param $issue
             * @param $comment
             * @return mixed
             */
            abstract public function modifyComment($issue, $comment);

            /**
             * @param $issue
             * @param $comment
             * @return mixed
             */
            abstract public function deleteComment($issue, $comment);

            /**
             * @param $issue
             * @param $file
             * @return mixed
             */
            abstract public function addAttachment($issue, $file);

            /**
             * @param $issue
             * @param $file
             * @return mixed
             */
            abstract public function deleteAttachment($issue, $file);

            /**
             * @return mixed
             */
            abstract public function whoAmI();

            /**
             * @return mixed
             */
            abstract public function myGroups();

            /**
             * @return mixed
             */
            abstract public function myFilters();

            /**
             * @return mixed
             */
            abstract public function reCreateIndexes();

            /**
             * @param $issue
             * @param $record
             * @return mixed
             */
            abstract public function addJournalRecord($issue, $record);

            /**
             * @param $query
             * @return mixed
             */
            abstract public function preprocessFilter($query);
        }
    }