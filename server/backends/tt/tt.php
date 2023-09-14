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
                
                $cache = $this->cacheGet("WORKFLOWS");
                if ($cache) {
                    return $cache;
                }

                $workflows = $files->searchFiles([ "metadata.type" => "workflow" ]);

                $_list = [];
                foreach ($workflows as $workflow) {
                    $name = $workflow["metadata"]["workflow"];
                    $catalog = false;
                    try {
                        $name = $this->loadWorkflow($workflow["metadata"]["workflow"])->getWorkflowName();
                    } catch (\Exception $e) {
                        //
                    }
                    try {
                        $catalog = $this->loadWorkflow($workflow["metadata"]["workflow"])->getWorkflowCatalog();
                    } catch (\Exception $e) {
                        //
                    }
                    $_list[$workflow["metadata"]["workflow"]] = [
                        "name" => $name,
                        "catalog" => $catalog,
                    ];
                }

                $this->cacheSet("WORKFLOWS", $_list);
                return $_list;
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
                        "in_array" => function (...$args) {
                            return [ in_array(...$args) ];
                        },
                        "array_key_exists" => function (...$args) {
                            return [ array_key_exists(...$args) ];
                        },
                        "explode" => function (...$args) {
                            return [ explode(...$args) ];
                        },
                        "implode" => function (...$args) {
                            return [ implode(...$args) ];
                        },
                        "time" => function (...$args) {
                            return [ time(...$args) ];
                        },
                        "date" => function (...$args) {
                            return [ date(...$args) ];
                        },
                        "strtotime" => function (...$args) {
                            return [ strtotime(...$args) ];
                        }
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
                        "login" => function () {
                            return [ $this->login ];
                        },
                        "myRoles" => function () {
                            return [ $this->myRoles() ];
                        },
                        "myGroups" => function () {
                            return [ $this->myGroups() ];
                        },
                        "myself" => function () {
                            return [ loadBackend("users")->getUser($this->uid) ];
                        }
                    ]);

                    $sandbox->registerLibrary("users", [
                        "notify" => function (...$args) {
                            $users = loadBackend("users");
                            return [ $users->notify(...$args) ];
                        },
                    ]);

                    $sandbox->registerLibrary("https", [
                        "GET" => function ($url) {
                            return [
                                json_decode(
                                    file_get_contents($url, false, stream_context_create([
                                        'http' => [
                                            'header'  => [
                                                'Content-Type: application/json; charset=utf-8',
                                                'Accept: application/json; charset=utf-8',
                                            ],
                                        ],
                                    ]))
                                ),
                            ];
                        },
                        "POST" => function ($url, $data) {
                            return [
                                json_decode(
                                    file_get_contents($url, false, stream_context_create([
                                        'http' => [
                                            'method'  => 'POST',
                                            'header'  => [
                                                'Content-Type: application/json; charset=utf-8',
                                                'Accept: application/json; charset=utf-8',
                                            ],
                                            'content' => json_encode($data)
                                        ],
                                    ]))
                                ),
                            ];
                        },
                        "PUT" => function ($url, $data) {
                            return [
                                json_decode(
                                    file_get_contents($url, false, stream_context_create([
                                        'http' => [
                                            'method'  => 'PUT',
                                            'header'  => [
                                                'Content-Type: application/json; charset=utf-8',
                                                'Accept: application/json; charset=utf-8',
                                            ],
                                            'content' => json_encode($data)
                                        ],
                                    ]))
                                ),
                            ];
                        },
                        "DELETE" => function ($url, $data) {
                            return [
                                json_decode(
                                    file_get_contents($url, false, stream_context_create([
                                        'http' => [
                                            'method'  => 'DELETE',
                                            'header'  => [
                                                'Content-Type: application/json; charset=utf-8',
                                                'Accept: application/json; charset=utf-8',
                                            ],
                                            'content' => json_encode($data)
                                        ],
                                    ]))
                                ),
                            ];
                        },
                    ]);

                    $sandbox->registerLibrary("custom", [
                        "GET" => function ($params) {
                            $custom = loadBackend("custom");
                            return [ $custom->GET($params) ];
                        },
                        "POST" => function ($params) {
                            $custom = loadBackend("custom");
                            return [ $custom->POST($params) ];
                        },
                        "PUT" => function ($params) {
                            $custom = loadBackend("custom");
                            return [ $custom->PUT($params) ];
                        },
                        "DELETE" => function ($params) {
                            $custom = loadBackend("custom");
                            return [ $custom->DELETE($params) ];
                        },
                    ]);
                    
                    $sandbox->registerLibrary("mb", [
                        "substr" => function (...$args) {
                            return [ mb_substr(...$args) ];
                        },
                    ]);

                    $sandbox->registerLibrary("mqtt", [
                        "broadcast" => function ($topic, $payload) {
                            $mqtt = loadBackend("mqtt");
                            return [ $mqtt->broadcast($topic, $payload) ];
                        },
                    ]);

                    $this->workflows[$workflow] = new \tt\workflow\workflow($this->config, $this->db, $this->redis, $this, $workflow, $sandbox);
                    return $this->workflows[$workflow];
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
                    $this->unCache("WORKFLOW:$workflow");
                    return false;
                }
                
                $cache = $this->cacheGet("WORKFLOW:$workflow");
                if ($cache) {
                    return $cache;
                }

                $workflows = $files->searchFiles([
                    "metadata.type" => "workflow",
                    "metadata.workflow" => $workflow,
                ]);

                $_workflow = false;
                foreach ($workflows as $w) {
                    $_workflow = $w;
                    break;
                }

                if (!$_workflow) {
                    $this->unCache("WORKFLOW:$workflow");
                    return "";
                }

                $_workflow = $files->streamToContents($files->getFileStream($_workflow["id"]));
                $this->cacheSet("WORKFLOW:$workflow", $_workflow);
                return $_workflow;
            }

            /**
             * @param $workflow
             * @param $body
             * @return boolean
             */
            public function putWorkflow($workflow, $body) {
                $this->clearCache();

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
                $this->clearCache();

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
             * get available workflows
             *
             * @return array
             */

             public function getWorkflowLibs() {

                $files = loadBackend("files");

                if (!$files) {
                    return false;
                }
                
                $cache = $this->cacheGet("WORKFLOW:LIBS");
                if ($cache) {
                    return $cache;
                }

                $libs = $files->searchFiles([ "metadata.type" => "workflow.lib" ]);

                $_list = [];
                foreach ($libs as $lib) {
                    $_list[] = $lib["metadata"]["lib"];
                }

                $cache = $this->cacheSet("WORKFLOW:LIBS", $_list);
                return $_list;
            }

            /**
             * @return string
             */

            public function getWorkflowLibsCode()
            {
                $files = loadBackend("files");

                if (!$files) {
                    return false;
                }
                
                $libs = $files->searchFiles([
                    "metadata.type" => "workflow.lib",
                ]);

                $wls = "";

                foreach ($libs as $l) {
                    $wls .= $files->streamToContents($files->getFileStream($l["id"])) . "\n\n";
                }

                return $wls;
            }

            /**
             * @param $lib
             * @return string
             */

             public function getWorkflowLib($lib)
             {
                $files = loadBackend("files");

                if (!$files) {
                    return false;
                }
                
                $libs = $files->searchFiles([
                    "metadata.type" => "workflow.lib",
                    "metadata.lib" => $lib,
                ]);

                $lib = false;
                foreach ($libs as $l) {
                    $lib = $l;
                    break;
                }

                if (!$lib) {
                    return "";
                }

                return $files->streamToContents($files->getFileStream($lib["id"]));
            }

            /**
             * @param $lib
             * @param $body
             * @return boolean
             */
            public function putWorkflowLib($lib, $body) 
            {
                $files = loadBackend("files");

                if (!$files) {
                    return false;
                }

                if (!$lib) {
                    return false;
                }
                
                $libs = $files->searchFiles([
                    "metadata.type" => "workflow.lib",
                    "metadata.lib" => $lib,
                ]);

                foreach ($libs as $l) {
                    $files->deleteFile($l["id"]);
                }

                return $files->addFile($lib . ".lua", $files->contentsToStream($body), [
                    "type" => "workflow.lib",
                    "lib" => $lib,
                ]);
            }

            /**
             * @param $workflow
             * @return boolean
             */
            public function deleteWorkflowLib($lib) 
            {
                $files = loadBackend("files");

                if (!$files) {
                    return false;
                }
                
                $libs = $files->searchFiles([
                    "metadata.type" => "workflow.lib",
                    "metadata.lib" => $lib,
                ]);

                foreach ($libs as $l) {
                    $files->deleteFile($l["id"]);
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
             * @param $assigned
             * @return boolean
             */
            abstract public function modifyProject($projectId, $acronym, $project, $maxFileSize, $searchSubject, $searchDescription, $searchComments, $assigned);

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
             * @param $status
             * @return false|integer
             */

             abstract public function addStatus($status);

             /**
             * @param $statusId
             * @param $display
             * @return boolean
             */

             abstract public function modifyStatus($statusId, $display);

             /**
              * @param $statusId
              * @return boolean
              */
 
             abstract public function deleteStatus($statusId);
 
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
             * @param $catalog
             * @param $type
             * @param $field
             * @param $fieldDisplay
             * @return false|integer
             */

            abstract public function addCustomField($catalog, $type, $field, $fieldDisplay);

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
             * @param $catalog
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
            abstract public function modifyCustomField($customFieldId, $catalog, $fieldDisplay, $fieldDescription, $regex, $format, $link, $options, $indx, $search, $required, $editor);

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
                    $this->unCache("FILTERS");
                    return false;
                }

                $cache = $this->cacheGet("FILTERS");
                if ($cache) {
                    return $cache;
                }
                
                $filters = $files->searchFiles([ "metadata.type" => "filter" ]);

                $_list = [];
                foreach ($filters as $filter) {
                    try {
                        $_list[$filter["metadata"]["filter"]] = @json_decode($this->getFilter($filter["metadata"]["filter"]), true)["name"];
                    } catch (\Exception $e) {
                        $_list[$filter["metadata"]["filter"]] = $filter["metadata"]["filter"];
                    }
                }

                $this->cacheSet("FILTERS", $_list);
                return $_list;
            }

            /**
             * @return false|array
             */
            public function getFiltersExt() {
                $files = loadBackend("files");

                if (!$files) {
                    $this->unCache("FILTERS-EXT");
                    return false;
                }
                
                $cache = $this->cacheGet("FILTERS-EXT");
                if ($cache) {
                    return $cache;
                }

                $filters = $files->searchFiles([ "metadata.type" => "filter" ]);

                $_list = [];
                foreach ($filters as $filter) {
                    try {
                        $_list[$filter["metadata"]["filter"]] = [
                            "name" => @json_decode($this->getFilter($filter["metadata"]["filter"]), true)["name"],
                            "owner" => @$filter["metadata"]["owner"],
                        ];
                    } catch (\Exception $e) {
                        $_list[$filter["metadata"]["filter"]] = [
                            "name" => $filter["metadata"]["filter"],
                        ];
                    }
                }

                $this->cacheSet("FILTERS-EXT", $_list);
                return $_list;
            }

            /**
             * @param $filter
             * @param $owner
             * @return false|string
             */
            public function getFilter($filter, $owner = false) {
                $files = loadBackend("files");

                if (!$files) {
                    return false;
                }
                
                if ($owner) {
                    $filters = $files->searchFiles([
                        "metadata.type" => "filter",
                        "metadata.filter" => $filter,
                        "metadata.owner" => $owner,
                    ]);
                } else {
                    $filters = $files->searchFiles([
                        "metadata.type" => "filter",
                        "metadata.filter" => $filter,
                    ]);
                }

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
             * @param $owner
             * @return boolean
             */
            public function putFilter($filter, $body, $owner = false) {
                $files = loadBackend("files");

                if (!$files) {
                    return false;
                }

                if (!$filter) {
                    return false;
                }

                $this->clearCache();

                if ($owner) {
                    $filters = $files->searchFiles([
                        "metadata.type" => "filter",
                        "metadata.filter" => $filter,
                        "metadata.owner" => $owner,
                    ]);
    
                    foreach ($filters as $f) {
                        $files->deleteFile($f["id"]);
                    }
    
                    return $files->addFile($filter . ".json", $files->contentsToStream($body), [
                        "type" => "filter",
                        "filter" => $filter,
                        "owner" => $owner,
                    ]);
                } else {
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
            }

            /**
             * @param $filter
             * @param $owner
             * @return boolean
             */
            public function deleteFilter($filter, $owner = false)
            {
                $files = loadBackend("files");

                if (!$files) {
                    return false;
                }

                $this->clearCache();

                if ($owner) {
                    $filters = $files->searchFiles([
                        "metadata.type" => "filter",
                        "metadata.filter" => $filter,
                        "metadata.owner" => $owner,
                    ]);
                } else {
                    $filters = $files->searchFiles([
                        "metadata.type" => "filter",
                        "metadata.filter" => $filter,
                    ]);
                }

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

                $this->clearCache();

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

                $this->clearCache();

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
                    $this->unCache("VIEWERS");
                    return false;
                }

                $cache = $this->cacheGet("VIEWERS");
                if ($cache) {
                    return $cache;
                }

                $viewers = $files->searchFiles([
                    "metadata.type" => "viewer",
                ]);

                $_vs = [];
                foreach ($viewers as $v) {
                    $_vs[] = [
                        "filename" => $v["metadata"]["viewer"],
                        "name" => $v["metadata"]["name"],
                        "field" => $v["metadata"]["field"],
                        "code" => $files->streamToContents($files->getFileStream($v["id"])) ? : "//function subject_v1 (value, field, issue, target) {\n\treturn val;\n//}\n",
                    ]; 
                }

                $this->cacheSet("VIEWERS", $_vs);
                return $_vs;
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
             * @param $a
             * @return boolean
             */
            private static function al($a) {
                if ($a === []) {
                    return true;
                }
                return array_keys($a) === range(0, count($a) - 1);
            }
            
            /**
             * @param $a
             * @return boolean
             */
            private static function an($a){
                return ctype_digit(implode('', array_keys($a)));
            }
            
            /**
             * @param $a
             * @return mixed
             */
            private static function av($a) {
                $repeat = false;
                if (!is_array($a) && !is_object($a)) {
                    return $a;
                } else {
                    $t = [];
                    if (self::an($a)) {
                        foreach ($a as $k => $v) {
                            $t[] = self::av($v);
                        }
                    } else {
                        foreach ($a as $k => $v) {
                            $t[$k] = self::av($v);
                        }
                    }
                    return $t;
                }
            }

            /**
             * @param $issue
             * @return mixed
             */
            public function checkIssue(&$issue)
            {
                $acr = explode("-", $issue["issueId"])[0];

                $customFields = $this->getCustomFields();
                $validFields = [];

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
                $validFields[] = "catalog";
                $validFields[] = "parent";
                $validFields[] = "subject";
                $validFields[] = "description";
                $validFields[] = "resolution";
                $validFields[] = "status";
                $validFields[] = "tags";
                $validFields[] = "assigned";
                $validFields[] = "watchers";
                $validFields[] = "links";
                $validFields[] = "attachments";
                $validFields[] = "comments";

                if (!@$issue["catalog"] || $issue["catalog"] == "-") {
                    unset($issue["catalog"]);
                }

                $validTags = [];

                foreach ($project["tags"] as $t) {
                    $validTags[] = $t["tag"];
                }

                foreach ($issue as $field => $value) {
                    if (!in_array($field, $validFields)) {
                        unset($issue[$field]);
                    } else {
                        if ($value !== null) {
                            if (array_key_exists($field, $customFieldsByName)) {
                                if (strpos($customFieldsByName[$field]["format"], "multiple") !== false || $customFieldsByName[$field]["type"] == "array") {
                                    $issue[$field] = array_values($value);
                                } else {
                                    $issue[$field] = self::av($value);
                                }
                            }
                        }
                    }
                }

                if (@$issue["tags"]) {
                    foreach ($issue["tags"] as $indx => $tag) {
                        if (!in_array($tag, $validTags)) {
                            unset($issue["tags"][$indx]);
                        }
                    }
                }

                if (@$issue["assigned"]) {
                    $issue["assigned"] = array_values($issue["assigned"]);
                }

                if (@$issue["watchers"]) {
                    $issue["watchers"] = array_values($issue["watchers"]);
                }

                if (@$issue["tags"]) {
                    $issue["tags"] = array_values($issue["tags"]);
                }

                if (@$issue["links"]) {
                    $issue["links"] = array_values($issue["links"]);
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

                if (!$issues || !$issues["issues"] || count($issues["issues"]) != 1 || !$issues["issues"][0]) {
                    return false;
                }

                $issue = $issues["issues"][0];

                if (@$issue["links"]) {
                    $linkedIssues = $this->getIssues($acr, [
                        "issueId" => [
                            "\$in" => $issue["links"]
                        ],
                    ], [
                        "issueId",
                        "subject",
                        "status",
                        "resolution",
                        "created",
                        "updated",
                        "author",
                    ], [ "created" => 1 ], 0, 32768);

                    if ($linkedIssues) {
                        $issue["linkedIssues"] = $linkedIssues;
                    }
                }

                $childrens = $this->getIssues($acr, [ "parent" => $issueId ], [
                    "issueId",
                    "subject",
                    "status",
                    "resolution",
                    "created",
                    "updated",
                    "author",
                ], [ "created" => 1 ], 0, 32768);

                if ($childrens) {
                    $issue["childrens"] = $childrens;
                }

                return $issue;
            }

            /**
             * @param $issue
             * @return mixed
             */
            abstract protected function createIssue($issue);

            /**
             * @param $issue
             * @param $workflowAction
             * @return mixed
             */
            abstract protected function modifyIssue($issue, $workflowAction = false, $apUpdated = true);

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
             * @param $type
             * @param $silent
             * @return mixed
             */
            abstract public function addComment($issueId, $comment, $private, $type = false, $silent = false);

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
             * @param $issueId
             * @param $field
             * @param $value
             * @return mixed
             */
            abstract public function addArrayValue($issueId, $field, $value);

            /**
             * @param $issueId
             * @param $field
             * @param $value
             * @return mixed
             */
            abstract public function deleteArrayValue($issueId, $field, $value);

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
             * @param string $issueId
             * @param string $action
             * @param object $old
             * @param object $new
             * @param mixed $workflowAction
             * @param boolean $silent
             * @return void
             */
            public function addJournalRecord($issueId, $action, $old, $new, $workflowAction = false, $silent = false)
            {
                $journal = loadBackend("tt_journal");

                if (!$silent) {
                    try {
                        $issue = $this->getIssue($issueId);
                        if ($issue) {
                            $workflow = $this->loadWorkflow($issue["workflow"]);
                            $workflow->issueChanged($issue, $action, $old, $new, $workflowAction);
                        }
                    } catch (\Exception $e) {
                        error_log(print_r($e, true));
                    }
                }

                if ($journal) {
                    return $journal->journal($issueId, $action, $old, $new, $workflowAction);
                }

                return false;
            }

            /**
             * @param string $issueId
             * @return mixed
             */
            public function getJournal($issueId, $limit = false)
            {
                $journal = loadBackend("tt_journal");

                if (!$this->myRoles()[explode("-", $issueId)[0]]) {
                    return false;
                }

                if ($journal) {
                    return $journal->get($issueId, $limit);
                }

                return false;
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
                } else {
                    unset($issue["watchers"][array_search($this->login, $issue["watchers"])]);
                }

                return $this->modifyIssue($issue);
            }

            /**
             * @param $query
             * @param $params
             * @return mixed
             */
            public function preprocessFilter($query, $params)
            {
                if ($query) {
                    array_walk_recursive($query, function (&$item, $key, $params) {
                        if (array_key_exists($item, $params)) {
                            $item = $params[$item];
                        }
                    }, $params);
                }
                return $query;
            }

            /**
             * @param $issue1
             * @param $issue2
             * @return mixed
             */
            public function linkIssues($issue1, $issue2)
            {
                $issue1 = $this->getIssue($issue1);
                if (!$issue1) {
                    setLastError("issue1NotFound");
                    return false;
                }

                $issue2 = $this->getIssue($issue2);
                if (!$issue2) {
                    setLastError("issue2NotFound");
                    return false;
                }

                if ($issue1["issueId"] == $issue2["issueId"]) {
                    setLastError("cantLinkToItself");
                    return false;
                }

                if ($issue1["project"] !== $issue2["project"]) {
                    setLastError("issue1AndIssue2FromDifferentProjects");
                    return false;
                }

                $project = $issue1["project"];

                $me = $this->myRoles();

                if ((int)$me[$project] < 40) {
                    setLastError("insufficentRights");
                    return false;
                }

                $links1 = @$issue1["links"];
                $links2 = @$issue2["links"];

                if (!$links1) {
                    $links1 = [];
                }

                if (!$links2) {
                    $links2 = [];
                }

                $needModify1 = false;
                $needModify2 = false;

                if (!in_array($issue2["issueId"], $links1)) {
                    $needModify1 = true;
                    $links1[] = $issue2["issueId"];
                    $issue1 = [
                        "issueId" => $issue1["issueId"],
                        "links" => $links1,
                    ];
                }

                if (!in_array($issue1["issueId"], $links2)) {
                    $needModify2 = true;
                    $links2[] = $issue1["issueId"];
                    $issue2 = [
                        "issueId" => $issue2["issueId"],
                        "links" => $links2,
                    ];
                }

                $success = true;

                if ($needModify1) {
                    $success = $success && $this->modifyIssue($issue1);
                }
                
                if ($needModify2) {
                    $success = $success && $this->modifyIssue($issue2);
                }
                
                return $success;
            }

            /**
             * @param $issue1
             * @param $issue2
             * @return mixed
             */
            public function unLinkIssues($issue1, $issue2)
            {
                $issue1 = $this->getIssue($issue1);
                if (!$issue1) {
                    setLastError("issue1NotFound");
                    return false;
                }

                $issue2 = $this->getIssue($issue2);
                if (!$issue2) {
                    setLastError("issue2NotFound");
                    return false;
                }

                if ($issue1["issueId"] == $issue2["issueId"]) {
                    setLastError("cantLinkToItself");
                    return false;
                }

                if ($issue1["project"] !== $issue2["project"]) {
                    setLastError("issue1AndIssue2FromDifferentProjects");
                    return false;
                }

                $project = $issue1["project"];

                $me = $this->myRoles();

                if ((int)$me[$project] < 40) {
                    setLastError("insufficentRights");
                    return false;
                }

                $links1 = @$issue1["links"];
                $links2 = @$issue2["links"];

                if (!$links1) {
                    $links1 = [];
                }

                if (!$links2) {
                    $links2 = [];
                }

                $needModify1 = false;
                $needModify2 = false;

                if (in_array($issue2["issueId"], $links1)) {
                    $needModify1 = true;
                    $issue1 = [
                        "issueId" => $issue1["issueId"],
                        "links" => array_diff($links1, [ $issue2["issueId"] ]),
                    ];
                }

                if (in_array($issue1["issueId"], $links2)) {
                    $needModify2 = true;
                    $issue2 = [
                        "issueId" => $issue2["issueId"],
                        "links" => array_diff($links2, [ $issue1["issueId"] ]),
                    ];
                }

                $success = true;

                if ($needModify1) {
                    $success = $success && $this->modifyIssue($issue1);
                }
                
                if ($needModify2) {
                    $success = $success && $this->modifyIssue($issue2);
                }
                
                return $success;
            }

            /**
             * @inheritDoc
             */
            public function cron($part)
            {
                $success = true;

                $tasks = $this->db->get("select acronym, filter, action, uid, login from tt_crontabs left join tt_projects using (project_id) left join core_users using (uid) where crontab = :crontab and enabled = 1", [
                    "crontab" => $part,                    
                ], [
                    "acronym" => "acronym",
                    "filter" => "filter",
                    "action" => "action",
                    "uid" => "uid",
                    "login" => "login",
                ]);

                foreach ($tasks as $task) {
                    try {
                        $this->setCreds($task["uid"], $task["login"]);
                        $journal = loadBackend("tt_journal");
                        if ($journal) {
                            $journal->setCreds($task["uid"], $task["login"]);
                        }
                        $filter = @json_decode($this->getFilter($task["filter"]), true);
                        if ($filter) {
                            $skip = 0;
                            do {
                                $issues = $this->getIssues($task["acronym"], @$filter["filter"], @$filter["fields"], [ "created" => 1 ], $skip, 5, []);
                                $skip += 5;
                                for ($i = 0; $i < count($issues["issues"]); $i++) {
                                    $issue = $this->getIssue($issues["issues"][$i]["issueId"]);

                                    if ($issue) {
                                        $this->loadWorkflow($issue["workflow"])->action($issue, $task["action"], $issue);
                                    }
                                }
                            } while (count($issues["issues"]));
                        }
                    } catch (\Exception $e) {
                        $success = false;
                    }
                }

                return $success && parent::cron($part);
            }
        }
    }