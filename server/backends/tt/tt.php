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
                            return [ array_values(explode(...$args)) ];
                        },
                        "implode" => function (...$args) {
                            return [ implode(...$args) ];
                        },
                        "time" => function (...$args) {
                            try {
                                return [ time(...$args) ];
                            } catch (\Exception) {
                                return [ "" ];
                            }
                        },
                        "date" => function (...$args) {
                            try {
                                return [ date(...$args) ];
                            } catch (\Exception $e) {
                                return [ "" ];
                            }
                        },
                        "strtotime" => function (...$args) {
                            return [ strtotime(...$args) ];
                        },
                        "json_decode" => function (...$args) {
                            $args[] = true;
                            return [ json_decode(...$args) ];
                        },
                        "json_encode" => function (...$args) {
                            return [ json_encode(...$args) ];
                        },
                        "sprintf" => function (...$args) {
                            return [ sprintf(...$args) ];
                        },
                        "preg_replace" => function (...$args) {
                            return [ preg_replace(...$args) ];
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
                        "login" => function () {
                            return [ $this->login ];
                        },
                        "myRoles" => function () {
                            return [ $this->myRoles() ];
                        },
                        "myGroups" => function () {
                            return [ $this->myGroups() ];
                        },
                        "myPrimaryGroup" => function () {
                            return [ $this->myPrimaryGroup() ];
                        },
                        "myself" => function () {
                            return [ loadBackend("users")->getUser($this->uid) ];
                        },
                        "matchFilter" => function (...$args) {
                            return [ $this->matchFilter(...$args) ];
                        }
                    ]);

                    $sandbox->registerLibrary("users", [
                        "notify" => function ($login, $subject, $body) {
                            $users = loadBackend("users");
                            return [ $users->notify($users->getUidByLogin($login), $subject, $body) ];
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

                            $params["_config"] = $this->config;
                            $params["_redis"] = $this->redis;
                            $params["_db"] = $this->db;

                            return [ $custom->GET($params) ];
                        },
                        "POST" => function ($params) {
                            $custom = loadBackend("custom");

                            $params["_config"] = $this->config;
                            $params["_redis"] = $this->redis;
                            $params["_db"] = $this->db;

                            return [ $custom->POST($params) ];
                        },
                        "PUT" => function ($params) {
                            $custom = loadBackend("custom");

                            $params["_config"] = $this->config;
                            $params["_redis"] = $this->redis;
                            $params["_db"] = $this->db;

                            return [ $custom->PUT($params) ];
                        },
                        "DELETE" => function ($params) {
                            $custom = loadBackend("custom");

                            $params["_config"] = $this->config;
                            $params["_redis"] = $this->redis;
                            $params["_db"] = $this->db;

                            return [ $custom->DELETE($params) ];
                        },
                    ]);

                    $sandbox->registerLibrary("mb", [
                        "substr" => function (...$args) {
                            return [ mb_substr(...$args) ];
                        },
                        "trim" => function ($str) {
                            if (function_exists("mb_trim")) {
                                return [ mb_trim(preg_replace('~^\s+|\s+$~u', '', $str)) ];
                            } else {
                                return [ trim(preg_replace('~^\s+|\s+$~u', '', $str)) ];
                            }
                        }
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

            public function getWorkflowLib($lib) {
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

            public function putWorkflowLib($lib, $body) {
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

            abstract public function addStatus($status, $fianl);

            /**
             * @param $statusId
             * @param $status
             * @return boolean
             */

            abstract public function modifyStatus($statusId, $status, $fianl);

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
             * @param $fieldDisplayList
             *
             * @return false|integer
             */

            abstract public function addCustomField($catalog, $type, $field, $fieldDisplay, $fieldDisplayList);

            /**
             * @param $projectId
             * @param $customFields
             * @return boolean
             */

            abstract public function setProjectCustomFields($projectId, $customFields);

            /**
             * @param $projectId
             * @param $customFields
             * @return boolean
             */

            abstract public function setProjectCustomFieldsNoJournal($projectId, $customFields);

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
             * @param $fieldDisplayList
             * @param $fieldDescription
             * @param $regex
             * @param $format
             * @param $link
             * @param $options
             * @param $indx
             * @param $search
             * @param $required
             * @param $editor
             * @param $float
             * @param $readonly
             *
             * @return boolean
             */

            abstract public function modifyCustomField($customFieldId, $catalog, $fieldDisplay, $fieldDisplayList, $fieldDescription, $regex, $format, $link, $options, $indx, $search, $required, $editor, $float, $readonly);

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
                        $f = @json_decode($this->getFilter($filter["metadata"]["filter"]), true);
                        $_list[$filter["metadata"]["filter"]] = [
                            "name" => @$f["name"] ? : $filter["metadata"]["filter"],
                            "shortName" => @$f["shortName"],
                            "sort" => @$f["sort"],
                            "hide" => @$f["hide"],
                            "disableCustomSort" => !!@$f["disableCustomSort"],
                            "pipeline" => !!@$f["pipeline"],
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

                $this->deleteFavoriteFilter($filter, true);

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
             * @param $projectId
             * @param $comments
             * @return mixed
             */

            abstract public function setProjectComments($projectId, $comments);

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

            private static function an($a) {
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

            public function checkIssue(&$issue) {
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
                    if (!is_array($issue["assigned"])) {
                        $issue["assigned"] = [ $issue["assigned"] ];
                    }
                    $issue["assigned"] = array_values($issue["assigned"]);
                }

                if (@$issue["watchers"]) {
                    if (!is_array($issue["watchers"])) {
                        $issue["watchers"] = [ $issue["watchers"] ];
                    }
                    $issue["watchers"] = array_values($issue["watchers"]);
                }

                if (@$issue["tags"]) {
                    if (!is_array($issue["tags"])) {
                        $issue["tags"] = [ $issue["tags"] ];
                    }
                    $issue["tags"] = array_values($issue["tags"]);
                }

                if (@$issue["links"]) {
                    if (!is_array($issue["links"])) {
                        $issue["links"] = [ $issue["links"] ];
                    }
                    $issue["links"] = array_values($issue["links"]);
                }

                return $issue;
            }

            /**
             * @param $issueId
             * @return void
             */

            public function getIssue($issueId) {
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
             * @param array $preprocess
             * @param array $types
             * @return mixed
             */

            abstract public function getIssues($collection, $query, $fields = [], $sort = [ "issueId" => 1 ], $skip = 0, $limit = 100, $preprocess = [], $types = [], $byPipeline = false);

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
             * @param $returnGid
             * @return mixed
             */

            abstract public function myPrimaryGroup($returnGid = false);

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

            public function addJournalRecord($issueId, $action, $old, $new, $workflowAction = false, $silent = false) {
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

                return $this->journal($issueId, $action, $old, $new, $workflowAction);
            }

            /**
             * @param string $issueId
             * @return mixed
             */

            public function getJournal($issueId, $limit = false) {
                if (!$this->myRoles()[explode("-", $issueId)[0]]) {
                    return false;
                }

                return $this->journalGet($issueId, $limit);
            }

            /**
             * @param $issue
             * @return mixed
             */

            public function assignToMe($issue) {
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

            public function watch($issue) {
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
             * @param $types
             * @return mixed
             */

            public function preprocessFilter($query, $params, $types) {
                if (!is_array($query)) {
                    error_log(print_r($query, true));
                } else {
                    if ($query) {
                        array_walk_recursive($query, function (&$item, $key, $params) use ($types) {
                            if (array_key_exists($item, $params)) {
                                if (@$types[$item]) {
                                    $cast = $types[$item];
                                } else {
                                    $cast = false;
                                }
                                if (is_callable($params[$item])) {
                                    $item = $params[$item]();
                                } else {
                                    $item = $params[$item];
                                }
                                if ($cast) {
                                    if ($cast == "date") {
                                        $item = date("Y-m-d", (int)$item);
                                    } else
                                    settype($item, $cast);
                                }
                            }
                        }, $params);
                    }

                    return $query;
                }
            }

            /**
             * @param $issue1
             * @param $issue2
             * @return mixed
             */

            public function linkIssues($issue1, $issue2) {
                if (gettype($issue2) == "array") {
                    $success = true;

                    foreach ($issue2 as $is2) {
                        $success = $success && $this->linkIssues($issue1, $is2);
                    }

                    return $success;
                }

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

            public function unLinkIssues($issue1, $issue2) {
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
             * @param $formName
             * @param $extension
             * @param $description
             * @return mixed
             */

            public function addPrint($formName, $extension, $description) {
                $this->clearCache();

                return $this->db->insert("insert into tt_prints (form_name, extension, description) values (:form_name, :extension, :description)", [
                    "form_name" => $formName,
                    "extension" => $extension,
                    "description" => $description,
                ]);
            }

            /**
             * @param $id
             * @return mixed
             */

            public function printGetData($id) {
                $print = $this->getPrint($id);

                if ($print) {
                    $files = loadBackend("files");

                    $data = $files->searchFiles([
                        "metadata.type" => "print-data",
                        "metadata.name" => $print["formName"],
                    ]);

                    if ($data) {
                        return $files->streamToContents($files->getFileStream($data[0]["id"])) ? : "//function data (issue, callback) {\n\tcallback(issue);\n//}\n";
                    } else {
                        return "//function data (issue, callback) {\n\tcallback(issue);\n//}\n";
                    }
                }

                return false;
            }

            /**
             * @param $id
             * @param $file
             * @return mixed
             */

            public function printSetData($id, $file) {
                if (!checkInt($id)) {
                    return false;
                }

                $print = $this->getPrint($id);

                if ($print) {
                    $files = loadBackend("files");

                    if ($files) {
                        return $files->deleteFiles([
                            "metadata.type" => "print-data",
                            "metadata.name" => $print["formName"],
                        ]) && $files->addFile($print["formName"] . "-data.js", $files->contentsToStream($file), [
                            "type" => "print-data",
                            "name" => $print["formName"],
                        ]);
                    }
                }

                return false;
            }

            /**
             * @param $id
             * @return mixed
             */

            public function printGetFormatter($id) {
                $print = $this->getPrint($id);

                if ($print) {
                    $files = loadBackend("files");

                    $formatter = $files->searchFiles([
                        "metadata.type" => "print-formatter",
                        "metadata.name" => $print["formName"],
                    ]);

                    if ($formatter) {
                        return $files->streamToContents($files->getFileStream($formatter[0]["id"])) ? : "";
                    } else {
                        return "";
                    }
                }

                return false;
            }

            /**
             * @param $id
             * @param $file
             * @return mixed
             */

            public function printSetFormatter($id, $file) {
                if (!checkInt($id)) {
                    return false;
                }

                $print = $this->getPrint($id);

                if ($print) {
                    $files = loadBackend("files");

                    if ($files) {
                        return $files->deleteFiles([
                            "metadata.type" => "print-formatter",
                            "metadata.name" => $print["formName"],
                        ]) && $files->addFile($print["formName"] . "-formatter.js", $files->contentsToStream($file), [
                            "type" => "print-formatter",
                            "name" => $print["formName"],
                        ]);
                    }
                }

                return false;
            }

            /**
             * @param $id
             * @return mixed
             */

            public function printGetTemplate($id)
            {
                $print = $this->getPrint($id);

                if ($print) {
                    $files = loadBackend("files");

                    $template = $files->searchFiles([
                        "metadata.type" => "print-template",
                        "metadata.name" => $print["formName"],
                    ]);

                    if ($template) {
                        return [
                            "body" => $files->streamToContents($files->getFileStream($template[0]["id"])),
                            "name" => $template[0]["filename"],
                            "size" => $template[0]["length"],
                        ];
                    } else {
                        return false;
                    }
                }

                return false;
            }

            /**
             * @param $id
             * @param $file
             * @return mixed
             */

            public function printSetTemplate($id, $fileName, $fileBody)
            {
                if (!checkInt($id)) {
                    return false;
                }

                $print = $this->getPrint($id);

                if ($print) {
                    $files = loadBackend("files");

                    if ($files) {
                        $this->clearCache();

                        return $files->deleteFiles([
                            "metadata.type" => "print-template",
                            "metadata.name" => $print["formName"],
                        ]) && $files->addFile($fileName, $files->contentsToStream(base64_decode($fileBody)), [
                            "type" => "print-template",
                            "name" => $print["formName"],
                        ]);
                    }
                }

                return false;
            }

            /**
             * @param $id
             * @return mixed
             */

            public function printDeleteTemplate($id)
            {
                if (!checkInt($id)) {
                    return false;
                }

                $print = $this->getPrint($id);

                if ($print) {
                    $files = loadBackend("files");

                    if ($files) {
                        $this->clearCache();

                        return $files->deleteFiles([
                            "metadata.type" => "print-template",
                            "metadata.name" => $print["formName"],
                        ]);
                    }
                }

                return false;
            }

            /**
             * @param $id
             * @param $formName
             * @param $extension
             * @param $description
             * @return mixed
             */

            public function modifyPrint($id, $formName, $extension, $descripton)
            {
                if (!checkInt($id)) {
                    return false;
                }

                $this->clearCache();

                return $this->db->modify("update tt_prints set form_name = :form_name, extension = :extension, description = :description where tt_print_id = $id", [
                    "form_name" => $formName,
                    "extension" => $extension,
                    "description" => $descripton,
                ]);
            }

            /**
             * @param $id
             * @return mixed
             */

            public function getPrint($id)
            {
                if (!checkInt($id)) {
                    return false;
                }

                $prints = $this->getPrints();

                foreach ($prints as $p) {
                    if ($id == $p["printId"]) {
                        return $p;
                    }
                }

                return false;
            }

            /**
             * @return mixed
             */

            public function getPrints()
            {
                $cache = $this->cacheGet("PRINTS");
                if ($cache) {
                    return $cache;
                }

                $_prints = $this->db->get("select * from tt_prints order by form_name", false, [
                    "tt_print_id" => "printId",
                    "form_name" => "formName",
                    "extension" => "extension",
                    "description" => "description",
                ]);

                $files = loadBackend("files");

                if ($files) {
                    foreach ($_prints as &$p) {
                        $template = $files->searchFiles([
                            "metadata.type" => "print-template",
                            "metadata.name" => $p["formName"],
                        ]);
                        $p["hasTemplate"] = !!$template;
                        if ($p["hasTemplate"]) {
                            $p["templateName"] = $template[0]["filename"];
                            $p["templateSize"] = $template[0]["length"];
                            $p["templateUploadDate"] = $template[0]["uploadDate"]['$date'];
                        }
                    }
                }

                $this->cacheSet("PRINTS", $_prints);

                return $_prints;
            }

            /**
             * @param $id
             * @return mixed
             */

            public function deletePrint($id)
            {
                if (!checkInt($id)) {
                    return false;
                }

                $print = $this->getPrint($id);

                if ($print) {
                    $files = loadBackend("files");

                    if ($files) {
                        $files->deleteFiles([
                            "metadata.type" => "print-data",
                            "metadata.name" => $print["formName"],
                        ]);
                        $files->deleteFiles([
                            "metadata.type" => "print-formatter",
                            "metadata.name" => $print["formName"],
                        ]);
                        $files->deleteFiles([
                            "metadata.type" => "print-template",
                            "metadata.name" => $print["formName"],
                        ]);
                    }
                }

                $this->clearCache();

                return $this->db->modify("delete from tt_prints where tt_print_id = $id");
            }

            /**
             * @param $id
             * @param $data
             * @return mixed
             */

            public function printExec($id, $data) {
                $tmp = md5(time() . rand());

                $print = $this->getPrint($id);

                if (!$print) {
                    setLastError("printNotFound");
                    return false;
                }

                foreach ($data as $i => $v) {
                    $data[$i] = (string)$v;
                }

                $path = rtrim(@$this->config["document_builder"]["tmp"] ?: "/tmp/print", "/");
                $bin = @$this->config["document_builder"]["bin"] ?: "/opt/onlyoffice/documentbuilder/docbuilder";
                $user = @$this->config["document_builder"]["www_user"] ?: "www-data";
                $group = @$this->config["document_builder"]["www_group"] ?: "www-data";

                if (!is_dir($path)) {
                    mkdir($path);
                    chmod($path, 0775);
                    @chown($path, $user);
                    @chgrp($path, $group);
                }

                $template = @$this->printGetTemplate($id);
                $formatter = @$this->printGetFormatter($id);

                if (!$formatter) {
                    setLastError("formatterNotDefined");
                    return false;
                }

                if ($template) {
                    $templateExt = explode(".", $template["name"]);
                    $templateExt = $templateExt[count($templateExt) - 1];
                    $templateName = "$path/$tmp-tmp.$templateExt";
                    file_put_contents($templateName, $template["body"]);
                    $formatter = str_replace('${templateName}', $templateName, $formatter);
                }

                $outFile = "$path/$tmp-out.{$print["extension"]}";

                $formatter = str_replace('${outFile}', $outFile, $formatter);
                $formatter = str_replace('${extension}', $print["extension"], $formatter);
                $formatter = str_replace('${tmp}', $path, $formatter);

                $formatter = "var data = " . json_encode($data). ";\n\n" . $formatter;

                file_put_contents($path . "/" . $tmp . "-bld.docbuilder", $formatter);

                $log = [];
                exec("$bin $path/$tmp-bld.docbuilder 2>&1", $log);

                file_put_contents("$path/$tmp-log.log", implode("\n", $log));

                if (file_exists($outFile)) {
                    return "$tmp-out.{$print["extension"]}";
                } else {
                    setLastError("outFileMissing");
                    return false;
                }
            }

            /**
             * @param $project
             * @param $field
             * @param $query
             * @return mixed
             */

            abstract public function getSuggestions($project, $field, $query);

            /**
             * @param string $issueId
             * @param string $action
             * @param object $old
             * @param object $new
             * @param string $workflowAction
             * @return boolean
             */

            public abstract function journal($issueId, $action, $old, $new, $workflowAction);

            /**
             * @param string $issueId
             * @param mixed $limit
             * @return mixed
             */

            public abstract function journalGet($issueId, $limit = false);

            /**
             * @param string $login
             * @param integer $limit
             * @return mixed
             */

            public abstract function journalLast($login, $limit = 4096);

            /**
             * @return array
             */

            public abstract function getFavoriteFilters();

            /**
             * @param string $filter
             * @param string $project
             * @param string $leftSide
             * @param string $icon
             * @param string $color
             *
             * @return mixed
             */

            public abstract function addFavoriteFilter($filter, $project, $leftSide, $icon, $color);

            /**
             * @param string $filter
             * @param boolean $all
             *
             * @return mixed
             */

            public abstract function deleteFavoriteFilter($filter, $all = false);

            /**
             * @param string $filter
             * @param string $issueId
             *
             * return boolean
             */

            public abstract function matchFilter($project, $filter, $issueId);

            /**
             * @inheritDoc
             */

            public function cron($part) {
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
                        $filter = @json_decode($this->getFilter($task["filter"]), true);
                        if ($filter) {
                            $skip = 0;
                            do {
                                $issues = $this->getIssues($task["acronym"], @$filter["filter"], @$filter["fields"], [ "created" => 1 ], $skip, 5);
                                $skip += 5;
                                for ($i = 0; $i < count($issues["issues"]); $i++) {
                                    $issue = $this->getIssue($issues["issues"][$i]["issueId"]);

                                    if ($issue) {
                                        $set = [
                                            "issueId" => $issue["issueId"],
                                        ];
                                        $this->loadWorkflow($issue["workflow"])->action($set, $task["action"], $issue);
                                    }
                                }
                            } while (count($issues["issues"]));
                        }
                    } catch (\Exception $e) {
                        $success = false;
                    }
                }

                try {
                    if ($part == "minutely") {
                        $path = @$this->config["document_builder"]["tmp"] ? : "/tmp/print";
                        $user = @$this->config["document_builder"]["www_user"] ? : "www-data";
                        $group = @$this->config["document_builder"]["www_group"] ? : "www-data";
                        if (is_dir($path)) {
                            $fileSystemIterator = new \FilesystemIterator($path);
                            $threshold = strtotime('-15 min');
                            foreach ($fileSystemIterator as $file) {
                                if ($threshold >= $file->getCTime()) {
                                    unlink($file->getRealPath());
                                }
                            }
                        } else {
                            mkdir($path);
                            chmod($path, 0775);
                            @chown($path, $user);
                            @chgrp($path, $group);
                        }
                    }
                } catch (\Exception $e) {
                    $success = false;
                }

                return $success && parent::cron($part);
            }

            /**
             * @param string $issueId
             *
             * @return mixed
             */

            abstract public function get($issueId);

            /**
             * @param object $issue
             *
             * @return mixed
             */

            abstract public function store($issue);

            /**
             * @inheritDoc
             */

            public function cliUsage() {
                $usage = parent::cliUsage();

                if (!@$usage["files"]) {
                    $usage["files"] = [];
                }

                $usage["files"]["export-filters"] = [
                    "description" => "Export all TT filters to JSON files"
                ];

                $usage["files"]["export-viewers"] = [
                    "description" => "Export all TT viewers to JS files"
                ];

                $usage["files"]["replace-viewer"] = [
                    "value" => "string",
                    "placeholder" => "filename",
                    "description" => "Replace existing viewer"
                ];

                $usage["files"]["replace-all-viewers"] = [
                    "description" => "Replace existing viewer"
                ];

                $usage["files"]["export-workflows"] = [
                    "description" => "Export all TT workflows to LUA files"
                ];

                return $usage;
            }

            /**
             * @inheritDoc
             */

            public function cli($args) {
                if (array_key_exists("--export-workflows", $args)) {
                    $workflows = $this->getWorkflows();

                    foreach ($workflows as $w => $m) {
                        echo "export: $w ... ";

                        try {
                            $l = $this->getWorkflow($w);
                            $dir = __DIR__ . "/../../data/files/workflows/";

                            if (!file_exists($dir)) {
                                mkdir($dir, 0777, true);
                            }

                            file_put_contents($dir . "$w" . ".lua", $l);

                            echo "success\n";
                        } catch (\Exception $e) {
                            echo "fail\n";
                        }
                    }

                    exit(0);
                }

                if (array_key_exists("--export-filters", $args)) {
                    $filters = $this->getFilters();

                    foreach ($filters as $f => $m) {
                        echo "export: $f ... ";

                        try {
                            $l = $this->getFilter($f);

                            $dir = __DIR__ . "/../../data/files/filters/";

                            if (!file_exists($dir)) {
                                mkdir($dir, 0777, true);
                            }

                            file_put_contents($dir . "$f" . ".json", $l);

                            echo "success\n";
                        } catch (\Exception $e) {
                            echo "fail\n";
                        }
                    }

                    exit(0);
                }

                if (array_key_exists("--export-viewers", $args)) {
                    $viewers = $this->getViewers();

                    foreach ($viewers as $v) {
                        echo "export: {$v['filename']} ... ";

                        try {
                            $dir = __DIR__ . "/../../data/files/viewers/";

                            if (!file_exists($dir)) {
                                mkdir($dir, 0777, true);
                            }

                            file_put_contents($dir . "{$v['filename']}" . ".js", $v['code']);

                            echo "success\n";
                        } catch (\Exception $e) {
                            echo "fail\n";
                        }
                    }

                    exit(0);
                }

                if (array_key_exists("--replace-viewer", $args)) {
                    $viewers = $this->getViewers();

                    $f = false;

                    foreach ($viewers as $v) {
                        try {
                            $dir = __DIR__ . "/../../data/files/viewers/";

                            if ($args["--replace-viewer"] == "{$v['filename']}" . ".js" && file_exists($dir . "{$v['filename']}" . ".js")) {
                                $c = @file_get_contents($dir . "{$v['filename']}" . ".js");

                                if ($c) {
                                    $f = true;

                                    $this->putViewer($v["field"], $v["name"], $c);
                                }
                            }
                        } catch (\Exception $e) {
                            echo "fail\n";
                            exit(0);
                        }
                    }

                    if ($f) {
                        echo "success\n";
                    } else {
                        echo "file not found \"" . $dir . $args["--replace-viewer"] . ".js\"\n";
                    }

                    exit(0);
                }

                if (array_key_exists("--replace-all-viewers", $args)) {
                    $viewers = $this->getViewers();

                    $dir = __DIR__ . "/../../data/files/viewers/";

                    $l = scandir($dir);

                    $r = 0;

                    foreach ($l as $f) {
                        foreach ($viewers as $v) {
                            try {
                                if ($f == "{$v['filename']}" . ".js") {
                                    $c = @file_get_contents($dir . "{$v['filename']}" . ".js");

                                    if ($c) {
                                        $r++;

                                        $this->putViewer($v["field"], $v["name"], $c);
                                    }
                                }
                            } catch (\Exception $e) {
                                echo "fail\n";
                                exit(0);
                            }
                        }
                    }

                    echo "$r viewers replaced\n";

                    exit(0);
                }

                parent::cli($args);
            }
        }
    }