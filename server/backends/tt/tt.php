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
                $files = loadBackend("files");

                if (!$files) {
                    return false;
                }
                
                $workflows = $files->searchFiles([ "metadata.type" => "workflow" ]);

                $list = [];
                foreach ($workflows as $workflow) {
                    try {
                        $list[$workflow["metadata"]["workflow"]] = $this->loadWorkflow($workflow["metadata"]["workflow"])->getWorkflowName();
                    } catch (\Exception $e) {
                        $list[$workflow["metadata"]["workflow"]] = $workflow["metadata"]["workflow"];
                    }
                }

                return $list;
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

                try {
                    $sandbox = new \LuaSandbox;

                    $sandbox->registerLibrary("utils", [
                        "error_log" => function (...$args) {
                            return [ error_log(...$args) ];
                        },
                        "print_r" => function (...$args) {
                            $args[] = true;
                            return [ print_r(...$args) ];
                        },
                        "array_values" => function (...$args) {
                            return [ array_values(...$args) ];
                        },
                        "explode" => function (...$args) {
                            return [ explode(...$args) ];
                        },
                        "implode" => function (...$args) {
                            return [ implode(...$args) ];
                        },
                    ]);

                    $sandbox->registerLibrary("rbt", [
                        "setLastError" => function (...$args) {
                            return [ setLastError(...$args) ];
                        },
                        "i18n" => function (...$args) {
                            return [ i18n(...$args) ];
                        },
                    ]);

                    $sandbox->registerLibrary("tt", [
                        "createIssue" => function (...$args) {
                            return [ $this->createIssue(...$args) ];
                        },
                        "getIssues" => function (...$args) {
                            return [ $this->getIssues(...$args) ];
                        },
                        "modifyIssue" => function (...$args) {
                            return [ $this->modifyIssue(...$args) ];
                        },
                        "addComment" => function (...$args) {
                            return [ $this->addComment(...$args) ];
                        },
                    ]);

                    return $this->workflows[$workflow] = new \tt\workflow\workflow($this->config, $this->db, $this->redis, $this, $workflow, $sandbox);
                } catch (\Exception $e) {
                    error_log(print_r($e, true));
                    return false;
                }
            }

            /**
             * @param $workflow
             * @return string
             */

            public function getWorkflow($workflow) {
                $files = loadBackend("files");

                if (!$files) {
                    return false;
                }
                
                $workflows = $files->searchFiles([
                    "metadata.type" => "workflow",
                    "metadata.workflow" => $workflow,
                ]);

                $workflow = false;
                foreach ($workflows as $w) {
                    $workflow = $w;
                    break;
                }

                if (!$workflow) {
                    return "";
                }

                return $files->streamToContents($files->getFileStream($workflow["id"]));
            }

            /**
             * @param $workflow
             * @param $body
             * @return boolean
             */
            public function putWorkflow($workflow, $body) {
                $files = loadBackend("files");

                if (!$files) {
                    return false;
                }

                if (!$workflow) {
                    return false;
                }
                
                $workflows = $files->searchFiles([
                    "metadata.type" => "workflow",
                    "metadata.workflow" => $workflow,
                ]);

                foreach ($workflows as $w) {
                    $files->deleteFile($w["id"]);
                }

                return $files->addFile($workflow . ".lua", $files->contentsToStream($body), [
                    "type" => "workflow",
                    "workflow" => $workflow,
                ]);
            }

            /**
             * @param $workflow
             * @return boolean
             */
            public function deleteWorkflow($workflow) {
                $files = loadBackend("files");

                if (!$files) {
                    return false;
                }
                
                $workflows = $files->searchFiles([
                    "metadata.type" => "workflow",
                    "metadata.workflow" => $workflow,
                ]);

                foreach ($workflows as $w) {
                    $files->deleteFile($w["id"]);
                    break;
                }

                return true;
            }

            /**
             * get projects
             *
             * @return false|array[]
             */
            abstract public function getProjects($acronym = false);

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
             * @param $searchSubject
             * @param $searchDescription
             * @param $searchComments
             * @return boolean
             */
            abstract public function modifyProject($projectId, $acronym, $project, $maxFileSize, $searchSubject, $searchDescription, $searchComments);

            /**
             * delete project and all it derivatives
             *
             * @param $projectId
             * @return boolean
             */

            abstract public function deleteProject($projectId);

            /**
             * @param $projectId
             * @param $workflows
             * @return boolean
             */

            abstract public function setProjectWorkflows($projectId, $workflows);

            /**
             * @param $projectId
             * @param $filter
             * @param $personal
             * @return boolean
             */

             abstract public function addProjectFilter($projectId, $filter, $personal);

            /**
             * @param $projectFilterId
             * @return boolean
             */

             abstract public function deleteProjectFilter($projectFilterId);

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
             * @param $indx
             * @param $search
             * @param $required
             * @param $editor
             * @return boolean
             */
            abstract public function modifyCustomField($customFieldId, $fieldDisplay, $fieldDescription, $regex, $format, $link, $options, $indx, $search, $required, $editor);

            /**
             * @param $customFieldId
             * @return boolean
             */
            abstract public function deleteCustomField($customFieldId);

            /**
             * @param $projectId
             * @return false|array
             */
            abstract public function getTags($projectId = false);

            /**
             * @param $projectId
             * @param $tag
             * @param $foreground
             * @param $background
             * @return false|integer
             */
            abstract public function addTag($projectId, $tag, $foreground, $background);

            /**
             * @param $tagId
             * @param $tag
             * @param $foreground
             * @param $background
             * @return boolean
             */
            abstract public function modifyTag($tagId, $tag, $foreground, $background);

            /**
             * @return boolean
             */
            abstract public function deleteTag($tagId);

            /**
             * @return false|array
             */
            public function getFilters() {
                $files = loadBackend("files");

                if (!$files) {
                    return false;
                }
                
                $filters = $files->searchFiles([ "metadata.type" => "filter" ]);

                $list = [];
                foreach ($filters as $filter) {
                    try {
                        $list[$filter["metadata"]["filter"]] = @json_decode($this->getFilter($filter["metadata"]["filter"]), true)["name"];
                    } catch (\Exception $e) {
                        $list[$filter["metadata"]["filter"]] = $filter["metadata"]["filter"];
                    }
                }

                return $list;
            }

            /**
             * @param $filter
             * @return false|string
             */
            public function getFilter($filter) {
                $files = loadBackend("files");

                if (!$files) {
                    return false;
                }
                
                $filters = $files->searchFiles([
                    "metadata.type" => "filter",
                    "metadata.filter" => $filter,
                ]);

                $filter = false;
                foreach ($filters as $f) {
                    $filter = $f;
                    break;
                }

                if (!$filter) {
                    return "{}";
                }

                return $files->streamToContents($files->getFileStream($filter["id"]));
            }

            /**
             * @param $filter
             * @param $body
             * @return boolean
             */
            public function putFilter($filter, $body) {
                $files = loadBackend("files");

                if (!$files) {
                    return false;
                }

                if (!$filter) {
                    return false;
                }
                
                $filters = $files->searchFiles([
                    "metadata.type" => "filter",
                    "metadata.filter" => $filter,
                ]);

                foreach ($filters as $f) {
                    $files->deleteFile($f["id"]);
                }

                return $files->addFile($filter . ".json", $files->contentsToStream($body), [
                    "type" => "filter",
                    "filter" => $filter,
                ]);
            }

            /**
             * @param $filter
             * @return boolean
             */
            public function deleteFilter($filter) {
                $files = loadBackend("files");

                if (!$files) {
                    return false;
                }
                
                $filters = $files->searchFiles([
                    "metadata.type" => "filter",
                    "metadata.filter" => $filter,
                ]);

                foreach ($filters as $f) {
                    $files->deleteFile($f["id"]);
                }

                return true;
            }

            /**
             * @param $field
             * @param $name
             * @param $code
             * @return mixed
             */
            public function putViewer($field, $name, $code) {
                $files = loadBackend("files");

                if (!$files) {
                    return false;
                }

                if (!$code) {
                    return false;
                }

                $viewers = $files->searchFiles([
                    "metadata.type" => "viewer",
                    "metadata.field" => $field,
                    "metadata.name" => $name,
                ]);

                foreach ($viewers as $v) {
                    $files->deleteFile($v["id"]);
                }

                return $files->addFile($field . "_" . $name . ".js", $files->contentsToStream($code), [
                    "type" => "viewer",
                    "field" => $field,
                    "name" => $name,
                    "viewer" => $field . "_" . $name,
                ]);
            }

            /**
             * @param $field
             * @param $name
             * @return mixed
             */
            public function deleteViewer($field, $name) {
                $files = loadBackend("files");

                if (!$files) {
                    return false;
                }

                $viewers = $files->searchFiles([
                    "metadata.type" => "viewer",
                    "metadata.field" => $field,
                    "metadata.name" => $name,
                ]);

                foreach ($viewers as $v) {
                    $files->deleteFile($v["id"]);
                }

                return true;
            }

            /**
             * @return mixed
             */
            public function getViewers() {
                $files = loadBackend("files");

                if (!$files) {
                    return false;
                }

                $viewers = $files->searchFiles([
                    "metadata.type" => "viewer",
                ]);

                $vs = [];
                foreach ($viewers as $v) {
                    $vs[] = [
                        "filename" => $v["metadata"]["viewer"],
                        "name" => $v["metadata"]["name"],
                        "field" => $v["metadata"]["field"],
                        "code" => $files->streamToContents($files->getFileStream($v["id"])) ? : "//function subject_v1 (value, field, issue) {\n\treturn val;\n//}\n",
                    ]; 
                }

                return $vs;
            }

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
             * @param $projectId
             * @param $filter
             * @param $uid
             * @param $action
             * @return mixed
             */
            abstract public function addCrontab($crontab, $projectId, $filter, $uid, $action);

            /**
             * @param $crontabId
             * @return mixed
             */
            abstract public function deleteCrontab($crontabId);

            /**
             * @param $issue
             * @return mixed
             */
            public function checkIssue(&$issue)
            {
                $acr = explode("-", $issue["issueId"])[0];

                $customFields = $this->getCustomFields();
                $validFields = [];

//                $users = loadBackend("users");

                $project = false;
                $projects = $this->getProjects();
                foreach ($projects as $p) {
                    if ($p["acronym"] == $acr) {
                        $project = $p;
                        break;
                    }
                }

                $customFieldsByName = [];

                foreach ($project["customFields"] as $cfId) {
                    foreach ($customFields as $cf) {
                        if ($cf["customFieldId"] == $cfId) {
                            $validFields[] = "_cf_" . $cf["field"];
                            $customFieldsByName["_cf_" . $cf["field"]] = $cf;
                            break;
                        }
                    }
                }

                $validFields[] = "issueId";
                $validFields[] = "project";
                $validFields[] = "workflow";
                $validFields[] = "subject";
                $validFields[] = "description";
                $validFields[] = "resolution";
                $validFields[] = "status";
                $validFields[] = "tags";
                $validFields[] = "assigned";
                $validFields[] = "watchers";
                $validFields[] = "attachments";
                $validFields[] = "comments";
                $validFields[] = "journal";

                $validTags = [];

                foreach ($project["tags"] as $t) {
                    $validTags[] = $t["tag"];
                }

                foreach ($issue as $field => $dumb) {
                    if (!in_array($field, $validFields)) {
                        unset($issue[$field]);
                    } else {
                        if (array_key_exists($field, $customFieldsByName) && strpos($customFieldsByName[$field]["format"], "multiple") !== false) {
                            $issue[$field] = array_values($dumb);
                        }
                    }
                }

                foreach ($issue["tags"] as $indx => $tag) {
                    if (!in_array($tag, $validTags)) {
                        unset($issue["tags"][$indx]);
                    }
                }

                if ($issue["assigned"]) {
                    $issue["assigned"] = array_values($issue["assigned"]);
                }

                if ($issue["watchers"]) {
                    $issue["watchers"] = array_values($issue["watchers"]);
                }

                if ($issue["tags"]) {
                    $issue["tags"] = array_values($issue["tags"]);
                }

                return $issue;
            }

            /**
             * @param $issueId
             * @return void
             */
            public function getIssue($issueId)
            {
                $acr = explode("-", $issueId)[0];

                $projects = $this->getProjects($acr);

                if (!$projects || !$projects[0]) {
                    return false;
                }

                $issues = $this->getIssues($acr, [ "issueId" => $issueId ], true);

                if (!$issues || !$issues["issues"] || !$issues["issues"][0]) {
                    return false;
                }

                return $issues["issues"][0];
            }

            /**
             * @param $issue
             * @return mixed
             */
            abstract protected function createIssue($issue);

            /**
             * @param $issue
             * @return mixed
             */
            abstract public function modifyIssue($issue);

            /**
             * @param $issueId
             * @return mixed
             */
            abstract public function deleteIssue($issueId);

            /**
             * @param $collection
             * @param $query
             * @param array $fields
             * @param int[] $sort
             * @param int $skip
             * @param int $limit
             * @return mixed
             */
            abstract public function getIssues($collection, $query, $fields = [], $sort = [ "issueId" => 1 ], $skip = 0, $limit = 100);

            /**
             * @param $issueId
             * @param $comment
             * @param $private
             * @return mixed
             */
            abstract public function addComment($issueId, $comment, $private);

            /**
             * @param $issueId
             * @param $commentIndex
             * @param $comment
             * @param $private
             * @return mixed
             */
            abstract public function modifyComment($issueId, $commentIndex, $comment, $private);

            /**
             * @param $issueId
             * @param $commentIndex
             * @return mixed
             */
            abstract public function deleteComment($issueId, $commentIndex);

            /**
             * @param $issueId
             * @param $attachments
             * @return mixed
             */
            abstract public function addAttachments($issueId, $attachments);

            /**
             * @param $issueId
             * @param $filename
             * @return mixed
             */
            abstract public function deleteAttachment($issueId, $filename);

            /**
             * @param $uid
             * @return mixed
             */
            abstract public function myRoles($uid = false);

            /**
             * @param $returnGids
             * @return mixed
             */
            abstract public function myGroups($returnGids = false);

            /**
             * @return mixed
             */
            abstract public function reCreateIndexes();

            /**
             * @param string $issue
             * @param string $action
             * @param object $old
             * @param object $new
             * @return void
             */
            public function addJournalRecord($issue, $action, $old, $new)
            {
                $journal = loadBackend("tt_journal");

                if ($journal) {
                    $journal->journal($issue, $action, $old, $new);
                }
            }

            /**
             * @param $issue
             * @return mixed
             */
            public function assignToMe($issue)
            {
                $acr = explode("-", $issue)[0];

                $myRoles = $this->myRoles();

                if ((int)$myRoles[$acr] < 50) {
                    setLastError("insufficentRights");
                    return false;
                }

                $issue = $this->getIssue($issue);

                if (!$issue) {
                    setLastError("issueNotFound");
                    return false;
                }

                if (!in_array($this->login, $issue["assigned"])) {
                    $issue["assigned"] = [ $this->login ];
                    return $this->modifyIssue($issue);
                }

                return true;
            }

            /**
             * @param $issue
             * @return mixed
             */
            public function watch($issue)
            {
                $acr = explode("-", $issue)[0];

                $myRoles = $this->myRoles();

                if ((int)$myRoles[$acr] < 30) {
                    setLastError("insufficentRights");
                    return false;
                }

                $issue = $this->getIssue($issue);

                if (!$issue) {
                    setLastError("issueNotFound");
                    return false;
                }

                if (!$issue["watchers"]) {
                    $issue["watchers"] = [];
                }

                if (!in_array($this->login, $issue["watchers"])) {
                    $issue["watchers"][] = $this->login;
                    return $this->modifyIssue($issue);
                }

                return true;
            }

            /**
             * @param $issue
             * @param $linkTo
             * @param $linkType
             * @return mixed
             */
            public function linkIssue($issue, $linkTo, $linkType)
            {
                return true;
            }

            /**
             * @param $query
             * @param $params
             * @return mixed
             */
            public function preprocessFilter($query, $params)
            {
                array_walk_recursive($query, function (&$item, $key, $params) {
                    if (array_key_exists($item, $params)) {
                        $item = $params[$item];
                    }
                }, $params);
                return $query;
            }
        }
    }