<?php

    /**
     * backends tt namespace
     */

    namespace backends\tt {

        /**
         * internal tt class
         */

        class internal extends tt {

            protected $mongo, $dbName, $clickhouse;

            // mongo part

            /**
             * @inheritDoc
             */

            public function __construct($config, $db, $redis, $login = false) {
                parent::__construct($config, $db, $redis, $login);

                require_once __DIR__ . '/../../../utils/clickhouse.php';

                $this->dbName = @$config["backends"]["tt"]["db"] ?: "tt";

                if (@$config["mongo"]["uri"]) {
                    $this->mongo = new \MongoDB\Client($config["mongo"]["uri"]);
                } else {
                    $this->mongo = new \MongoDB\Client();
                }

                $this->clickhouse = new \clickhouse(
                    @$config['clickhouse']['host'] ?: '127.0.0.1',
                    @$config['clickhouse']['port'] ?: 8123,
                    @$config['clickhouse']['username'] ?: 'default',
                    @$config['clickhouse']['password'] ?: 'qqq',
                    @$config['clickhouse']['database'] ?: 'default'
                );
            }

            /**
             * @inheritDoc
             */

            protected function createIssue($issue) {
                $acr = $issue["project"];

                $issue["issueId"] = $acr;

                if (!$this->checkIssue($issue)) {
                    setLastError("invalidIssue");
                    return false;
                }

                $me = $this->myRoles();

                if (@$me[$acr] >= 30) { // 30, 'participant.senior' - can create issues
                    $db = $this->dbName;

                    $aiid = $this->redis->incr("AIID:" . $acr);
                    $issue["issueId"] = $acr . "-" . $aiid;

                    $attachments = @$issue["attachments"] ?: [];
                    unset($issue["attachments"]);

                    $issue["created"] = time();
                    $issue["updated"] = time();
                    $issue["author"] = $this->login;

                    try {
                        if ($attachments) {
                            $files = loadBackend("files");

                            $ext = $this->config["backends"]["tt"]["attachments"] === "external";

                            $meta = [
                                "date" => round($attachment["date"] / 1000),
                                "added" => time(),
                                "type" => $attachment["type"],
                                "issue" => true,
                                "project" => $acr,
                                "issueId" => $issue["issueId"],
                                "attachman" => $issue["author"],
                            ];

                            if ($ext) {
                                $meta["external"] = true;
                            }

                            foreach ($attachments as $attachment) {
                                $add = $files->addFile($attachment["name"], $files->contentsToStream(base64_decode($attachment["body"])), $meta) &&
                                $this->addJournalRecord($issue["issueId"], "addAttachment", null, [
                                    "attachmentFilename" => $attachment["name"],
                                ]);
                            }
                        }

                        if ($this->mongo->$db->$acr->insertOne($issue)->getInsertedId()) {
                            $this->addJournalRecord($issue["issueId"], "createIssue", null, $issue);
                            return $issue["issueId"];
                        } else {
                            return false;
                        }
                    } catch (\Exception $e) {
                        return false;
                    }
                } else {
                    setLastError("permissionDenied");
                    return false;
                }
            }

            /**
             * @inheritDoc
             */

            protected function modifyIssue($issue, $workflowAction = false, $apUpdated = true) {
                $db = $this->dbName;
                $project = explode("-", $issue["issueId"])[0];

                $unset = [];

                foreach ($issue as $field => $value) {
                    if ($value == "%%unset") {
                        $unset[$field] = true;
                        $issue[$field] = null;
                    }
                }

                $comment = false;
                $commentPrivate = false;
                $commentType = false;

                if (array_key_exists("comment", $issue) && $issue["comment"]) {
                    $comment = trim($issue["comment"]);
                    $commentPrivate = !!@$issue["commentPrivate"];
                    $commentType = @$issue["commentType"];
                    if (checkStr($commentType, [ "minLength" => 1, "maxLength" => 32])) {
                        $commentType = $commentType;
                    }
                    unset($issue["comment"]);
                    if (array_key_exists("commentType", $issue)) {
                        unset($issue["commentType"]);
                    }
                    if (array_key_exists("commentPrivate", $issue)) {
                        unset($issue["commentPrivate"]);
                    }
                }

                if (array_key_exists("comments", $issue)) {
                    unset($issue["comments"]);
                }

                if (array_key_exists("created", $issue)) {
                    unset($issue["created"]);
                }

                if (array_key_exists("author", $issue)) {
                    unset($issue["author"]);
                }

                if (array_key_exists("project", $issue)) {
                    unset($issue["project"]);
                }

                if (array_key_exists("parent", $issue)) {
                    unset($issue["parent"]);
                }

                if (array_key_exists("attachments", $issue)) {
                    unset($issue["attachments"]);
                }

                if (array_key_exists("journal", $issue)) {
                    unset($issue["journal"]);
                }

                if ($comment && !$this->addComment($issue["issueId"], $comment, $commentPrivate, $commentType, true)) {
                    return false;
                }

                $issue = $this->checkIssue($issue);

                if ($apUpdated) {
                    $issue["updated"] = time();
                }

                if ($issue) {
                    $old = $this->getIssue($issue["issueId"]);
                    $update = false;
                    if ($old) {
                        $update = $this->mongo->$db->$project->updateOne([ "issueId" => $issue["issueId"] ], [ '$set' => $issue ]);
                        if (count($unset)) {
                            $update = $update && $this->mongo->$db->$project->updateOne([ "issueId" => $issue["issueId"] ], [ '$unset' => $unset ]);
                        }
                    }
                    if ($update) {
                        $update = $update && $this->addJournalRecord($issue["issueId"], "modifyIssue", $old, $issue, $workflowAction);
                    }
                    return $update;
                }

                return false;
            }

            /**
             * @inheritDoc
             */

            public function deleteIssue($issueId) {
                $db = $this->dbName;

                $acr = explode("-", $issueId)[0];

                $myRoles = $this->myRoles();

                if ((int)$myRoles[$acr] < 80) {
                    setLastError("insufficentRights");
                    return false;
                }

                $files = loadBackend("files");

                $delete = true;

                if ($files) {
                    $issueFiles = $files->searchFiles([
                        "metadata.issue" => true,
                        "metadata.issueId" => $issueId,
                    ]);

                    foreach ($issueFiles as $file) {
                        $delete = $delete && $files->deleteFile($file["id"]);
                    }
                }

                if ($delete) {
                    $childrens = $this->getIssues($acr, [ "parent" => $issueId ], [ "issueId" ], [], 0, 32768);

                    if ($childrens && count($childrens["issues"])) {
                        foreach ($childrens["issues"] as $children) {
                            $delete = $delete && $this->deleteIssue($children["issueId"]);
                        }
                    }

                    return $delete && $this->mongo->$db->$acr->deleteMany([
                        "issueId" => $issueId,
                    ]) && $this->addJournalRecord($issueId, "deleteIssue", $this->getIssue($issueId), null);
                } else {
                    return false;
                }
            }

            private function standartPreprocessValues($preprocess) {
                $preprocess["%%strToday"] = date("Y-m-d");

                $preprocess["%%strToday+1day"] = date("Y-m-d", strtotime("+1 day"));
                $preprocess["%%strToday+2day"] = date("Y-m-d", strtotime("+2 day"));
                $preprocess["%%strToday+3day"] = date("Y-m-d", strtotime("+3 day"));
                $preprocess["%%strToday-1day"] = date("Y-m-d", strtotime("-1 day"));
                $preprocess["%%strToday-2day"] = date("Y-m-d", strtotime("-2 day"));
                $preprocess["%%strToday-3day"] = date("Y-m-d", strtotime("-3 day"));

                $preprocess["%%timestamp"] = time();

                $preprocess["%%timestampToday"] = strtotime(date("Y-m-d"));
                $preprocess["%%timestamp+1hour"] = strtotime(date("Y-m-d", strtotime("+1 hour")));
                $preprocess["%%timestamp+2hours"] = strtotime(date("Y-m-d", strtotime("+2 hour")));
                $preprocess["%%timestamp+4hours"] = strtotime(date("Y-m-d", strtotime("+4 hour")));
                $preprocess["%%timestamp+8hours"] = strtotime(date("Y-m-d", strtotime("+8 hour")));
                $preprocess["%%timestamp+1day"] = strtotime(date("Y-m-d", strtotime("+1 day")));
                $preprocess["%%timestamp+2days"] = strtotime(date("Y-m-d", strtotime("+2 day")));
                $preprocess["%%timestamp+3days"] = strtotime(date("Y-m-d", strtotime("+3 day")));
                $preprocess["%%timestamp+7days"] = strtotime(date("Y-m-d", strtotime("+7 day")));
                $preprocess["%%timestamp+1month"] = strtotime(date("Y-m-d", strtotime("+1 month")));
                $preprocess["%%timestamp+2month"] = strtotime(date("Y-m-d", strtotime("+2 month")));
                $preprocess["%%timestamp+3month"] = strtotime(date("Y-m-d", strtotime("+3 month")));
                $preprocess["%%timestamp+6month"] = strtotime(date("Y-m-d", strtotime("+6 month")));
                $preprocess["%%timestamp+1year"] = strtotime(date("Y-m-d", strtotime("+1 year")));
                $preprocess["%%timestamp+2years"] = strtotime(date("Y-m-d", strtotime("+2 year")));
                $preprocess["%%timestamp+3years"] = strtotime(date("Y-m-d", strtotime("+3 year")));
                $preprocess["%%timestamp-1hour"] = strtotime(date("Y-m-d", strtotime("-1 hour")));
                $preprocess["%%timestamp-2hours"] = strtotime(date("Y-m-d", strtotime("-2 hour")));
                $preprocess["%%timestamp-4hours"] = strtotime(date("Y-m-d", strtotime("-4 hour")));
                $preprocess["%%timestamp-8hours"] = strtotime(date("Y-m-d", strtotime("-8 hour")));
                $preprocess["%%timestamp-1day"] = strtotime(date("Y-m-d", strtotime("-1 day")));
                $preprocess["%%timestamp-2days"] = strtotime(date("Y-m-d", strtotime("-2 day")));
                $preprocess["%%timestamp-3days"] = strtotime(date("Y-m-d", strtotime("-3 day")));
                $preprocess["%%timestamp-7days"] = strtotime(date("Y-m-d", strtotime("-7 day")));
                $preprocess["%%timestamp-1month"] = strtotime(date("Y-m-d", strtotime("-1 month")));
                $preprocess["%%timestamp-2month"] = strtotime(date("Y-m-d", strtotime("-2 month")));
                $preprocess["%%timestamp-3month"] = strtotime(date("Y-m-d", strtotime("-3 month")));
                $preprocess["%%timestamp-6month"] = strtotime(date("Y-m-d", strtotime("-6 month")));
                $preprocess["%%timestamp-1year"] = strtotime(date("Y-m-d", strtotime("-1 year")));
                $preprocess["%%timestamp-2years"] = strtotime(date("Y-m-d", strtotime("-2 year")));
                $preprocess["%%timestamp-3years"] = strtotime(date("Y-m-d", strtotime("-3 year")));
                $preprocess["%%timestamp-startOfMonth"] = strtotime(date("Y-m-1"));

                return $preprocess;
            }

            private function standartPreprocessTypes($types) {
                $types["%%timestamp"] = "int";
                $types["%%timestampToday"] = "int";
                $types["%%timestamp+1hour"] = "int";
                $types["%%timestamp+2hours"] = "int";
                $types["%%timestamp+4hours"] = "int";
                $types["%%timestamp+8hours"] = "int";
                $types["%%timestamp+1day"] = "int";
                $types["%%timestamp+2days"] = "int";
                $types["%%timestamp+3days"] = "int";
                $types["%%timestamp+7days"] = "int";
                $types["%%timestamp+1month"] = "int";
                $types["%%timestamp+1year"] = "int";
                $types["%%timestamp+2years"] = "int";
                $types["%%timestamp+3years"] = "int";
                $types["%%timestamp-1hour"] = "int";
                $types["%%timestamp-2hours"] = "int";
                $types["%%timestamp-4hours"] = "int";
                $types["%%timestamp-8hours"] = "int";
                $types["%%timestamp-1day"] = "int";
                $types["%%timestamp-2days"] = "int";
                $types["%%timestamp-3days"] = "int";
                $types["%%timestamp-7days"] = "int";
                $types["%%timestamp-1month"] = "int";
                $types["%%timestamp-2month"] = "int";
                $types["%%timestamp-3month"] = "int";
                $types["%%timestamp-1year"] = "int";
                $types["%%timestamp-2years"] = "int";
                $types["%%timestamp-3years"] = "int";
                $types["%%timestamp-startOfMonth"] = "int";

                return $types;
            }

            private function getIssuesQuery($project, $query, $fields = [], $sort = [], $skip = 0, $limit = 100, $preprocess = [], $types = [], $byPipeline = false) {
                $me = $this->myRoles();

                if (!@$me[$project]) {
                    return [];
                }

                $my = $this->myGroups();
                $my[] = $this->login;

                $primaryGroup = $this->myPrimaryGroup();

                $groups = loadBackend("groups");
                $users = loadBackend("users");

                $inline_casts = [
                    "integer",
                    "string",
                    "double"
                ];

                foreach ($preprocess as $key => $value) {
                    if (array_key_exists(gettype($value), $inline_casts) >= 0) {
                        foreach ($inline_casts as $type) {
                            $preprocess["$key<$type>"] = $value;
                            $types["$key<$type>"] = $type;
                        }
                    }
                }

                if ($users && $groups) {
                    $gl = $groups->getGroups();

                    foreach ($gl as $g) {
                        $preprocess["%%group::{$g['acronym']}"] = function ($pp) {
                            $acr = explode("::", $pp)[1];

                            $groups = loadBackend("groups");
                            $users = loadBackend("users");
                            $users->getUser(-1);

                            $gu = [];

                            $uids = $groups->getUsers($groups->getGroupByAcronym($acr));

                            if ($uids) {
                                foreach ($uids as $uid) {
                                    if ($uid) {
                                        $gu[] = $users->getUser((int)$uid)["login"];
                                    }
                                }
                            }

                            return array_values($gu);
                        };
                    }
                }

                $preprocess["%%me"] = $this->login;
                $preprocess["%%my"] = $my;
                $preprocess["%%primaryGroup"] = $primaryGroup;

                $preprocess = $this->standartPreprocessValues($preprocess);
                $types = $this->standartPreprocessTypes($types);

                $preprocess["%%last"] = function ($pp) {
                    $last = $this->journalLast($this->login);
                    $issues = [];
                    foreach ($last as $issue) {
                        $issues[] = $issue["issue"];
                    }
                    return array_values($issues);
                };

                return $this->preprocessFilter($query, $preprocess, $types);
            }

            /**
             * @inheritDoc
             */

            public function getIssues($project, $query, $fields = [], $sort = [], $skip = 0, $limit = 100, $preprocess = [], $types = [], $byPipeline = false) {
                $db = $this->dbName;

                $query = $this->getIssuesQuery($project, $query, $fields, $sort, $skip, $limit, $preprocess, $types, $byPipeline);

                $projection = [];

                if ($fields === true) {
                    $fields = [];
                } else {
                    $projection["issueId"] = 1;

                    if ($fields) {
                        foreach ($fields as $field) {
                            $projection[$field] = 1;
                        }
                    }
                }

                if (!$sort) {
                    $sort = [];
                }

                foreach ($sort as $s => &$d) {
                    $d = (int)$d;
                }

                $options = [
                    "projection" => $projection,
                    "skip" => (int)$skip,
                    "limit" => (int)$limit,
                ];

                if ($sort) {
                    $options["sort"] = $sort;
                }

                $projection_all = [ "issueId" => 1 ];

                $options_all = [
                    "projection" => $projection_all,
                ];

                if ($sort) {
                    $options_all["sort"] = $sort;
                }

                $count = 0;

                if ($byPipeline) {
                    $_query = object_to_array($query);
                    $_query[] = [ '$project' => $projection ];
                    $_query[] = [ '$skip' => (int)$skip ];
                    $_query[] = [ '$limit' => (int)$limit ];
                    $issues = $this->mongo->$db->$project->aggregate($_query);

                    $_query = object_to_array($query);
                    $_query[] = [ '$group' => [ '_id' => null, 'countDocuments' => [ '$sum' => 1 ] ] ];
                    $_query[] = [ '$project' => [ '_id' => 0 ] ];
                    $cursor = $this->mongo->$db->$project->aggregate($_query);
                    foreach ($cursor as $document) {
                        $count = $document["countDocuments"];
                    }
                } else {
                    $_query = object_to_array($query);
                    $issues = $this->mongo->$db->$project->find($_query, $options);
                    $count = $this->mongo->$db->$project->countDocuments($_query);
                }

                $i = [];

                $files = loadBackend("files");

                foreach ($issues as $issue) {
                    $x = object_to_array($issue);
                    $x["id"] = $x["_id"]["oid"];
                    unset($x["_id"]);
                    if ($files && (!$fields || !count($fields) || in_array("attachments", $fields))) {
                        $x["attachments"] = $files->searchFiles([
                            "metadata.issue" => true,
                            "metadata.issueId" => $x["issueId"],
                        ]);
                    }
                    $i[] = $x;
                }

                if ($byPipeline) {
                    $_query = object_to_array($query);
                    $_query[] = [ '$project' => $projection_all ];
                    $issues = $this->mongo->$db->$project->aggregate($_query);
                } else {
                    $issues = $this->mongo->$db->$project->find($_query, $options_all);
                }

                $a = [];
                foreach ($issues as $issue) {
                    $a[] = $issue["issueId"];
                }

                return [
                    "issues" => $i,
                    "projection" => $projection,
                    "sort" => $sort,
                    "skip" => $skip,
                    "limit" => $limit,
                    "count" => $count,
                    "all" => $a,
                ];
            }

            /**
             * @inheritDoc
             */

            public function reCreateIndexes() {
                $db = $this->dbName;

                // fullText
                $p_ = $this->getProjects();
                $c_ = $this->getCustomFields();

                $projects = [];
                $customFields = [];

                $cnt = 0;

                foreach ($c_ as $c) {
                    $customFields[$c["customFieldId"]] = [
                        "name" => "_cf_" . $c["field"],
                        "index" => $c["indx"],
                        "search" => $c["search"],
                    ];
                }

                foreach ($p_ as $p) {
                    $projects[$p["acronym"]] = [
                        "searchSubject" => $p["searchSubject"],
                        "searchDescription" => $p["searchDescription"],
                        "searchComments" => $p["searchComments"],
                        "customFields" => [],
                    ];

                    foreach ($p["customFields"] as $c) {
                        $projects[$p["acronym"]]["customFields"][$customFields[$c]["name"]] = $customFields[$c];
                        unset($projects[$p["acronym"]]["customFields"][$customFields[$c]["name"]]["name"]);
                    }
                }

                foreach ($projects as $acr => $project) {
                    $fullText = [];
                    $fullText["issueId"] = "text";

                    if ($project["searchSubject"]) {
                        $fullText["subject"] = "text";
                    }
                    if ($project["searchDescription"]) {
                        $fullText["description"] = "text";
                    }
                    if ($project["searchComments"]) {
                        $fullText["comments.body"] = "text";
                    }

                    foreach ($project["customFields"] as $c => $p) {
                        if ($p["search"]) {
                            $fullText[$c] = "text";
                        }
                    }

                    $md5 = md5(print_r($fullText, true));

                    if ($this->redis->get("FTS:" . $acr) != $md5) {
                        try {
                            $this->mongo->$db->$acr->dropIndex("fullText");
                            $cnt++;
                        } catch (\Exception $e) {
                            //
                        }
                        try {
                            $this->mongo->$db->$acr->createIndex($fullText, [ "default_language" => @$this->config["language"] ?: "en", "name" => "fullText" ]);
                            $cnt++;
                        } catch (\Exception $e) {
                            //
                        }
                        $this->redis->set("FTS:" . $acr, $md5);
                    }
                }

                foreach ($projects as $acr => $project) {
                    $indexes = [
                        "assigned",
                        "author",
                        "catalog",
                        "created",
                        "description",
                        "issueId",
                        "parent",
                        "project",
                        "resolution",
                        "status",
                        "subject",
                        "updated",
                        "watchers",
                        "workflow",
                    ];

                    foreach ($project["customFields"] as $c => $p) {
                        if ($p["index"]) {
                            $indexes[] = $c;
                        }
                    }

                    $al = array_map(function ($indexInfo) {
                        return [ 'v' => $indexInfo->getVersion(), 'key' => $indexInfo->getKey(), 'name' => $indexInfo->getName() ];
                    }, iterator_to_array($this->mongo->$db->$acr->listIndexes()));

                    $already = [];
                    foreach ($al as $i) {
                        if (strpos($i["name"], "index_") === 0) {
                            $already[] = substr($i["name"], 6);
                        }
                    }

                    foreach ($indexes as $i) {
                        if (!in_array($i, $already)) {
                            try {
                                $this->mongo->$db->$acr->createIndex([ $i => 1 ], [ "name" => "index_" . $i, ]);
                                $cnt++;
                            } catch (\Exception $e) {
                                //
                            }
                        }
                    }

                    foreach ($already as $i) {
                        if (!in_array($i, $indexes)) {
                            try {
                                $this->mongo->$db->$acr->dropIndex("index_" . $i);
                                $cnt++;
                            } catch (\Exception $e) {
                                //
                            }
                        }
                    }
                }

                return $cnt ?: true;
            }

            /**
             * @inheritDoc
             */

            public function addComment($issueId, $comment, $private, $type = false, $silent = false, $markdown = false) {
                $db = $this->dbName;
                $acr = explode("-", $issueId)[0];

                $roles = $this->myRoles();

                if (!@$roles[$acr] || $roles[$acr] < 20) {
                    return false;
                }

                $comment = trim($comment);
                if (!$comment) {
                    return false;
                }

                $this->addJournalRecord($issueId, "addComment", null,
                    $type ?
                    [
                        "commentBody" => $comment,
                        "commentPrivate" => $private,
                        "commentType" => $type,
                        "commentMarkdown" => $markdown,
                    ] : [
                        "commentBody" => $comment,
                        "commentPrivate" => $private,
                        "commentMarkdown" => $markdown,
                    ],
                    false, $silent
                );

                return $this->mongo->$db->$acr->updateOne(
                    [
                        "issueId" => $issueId,
                    ],
                    $type ?
                    [
                        '$push' => [
                            "comments" => [
                                "body" => $comment,
                                "created" => time(),
                                "author" => $this->login,
                                "private" => $private,
                                "type" => $type,
                                "markdown" => $markdown,
                            ],
                        ],
                    ] : [
                        '$push' => [
                            "comments" => [
                                "body" => $comment,
                                "created" => time(),
                                "author" => $this->login,
                                "private" => $private,
                                "markdown" => $markdown,
                            ],
                        ],
                    ]
                ) &&
                $this->mongo->$db->$acr->updateOne(
                    [
                        "issueId" => $issueId,
                    ],
                    [
                        '$set' => [
                            "updated" => time(),
                        ],
                    ]
                );
            }

            /**
             * @inheritDoc
             */

            public function modifyComment($issueId, $commentIndex, $comment, $private, $markdown = false) {
                $db = $this->dbName;
                $acr = explode("-", $issueId)[0];

                if (!checkInt($commentIndex)) {
                    return false;
                }

                $roles = $this->myRoles();

                if (!@$roles[$acr] || $roles[$acr] < 20) {
                    return false;
                }

                $comment = trim($comment);
                if (!$comment) {
                    return false;
                }

                $issue = $this->getIssue($issueId);

                if (!$issue) {
                    return false;
                }

                $this->addJournalRecord($issueId, "modifyComment#$commentIndex", [
                    "commentAuthor" => $issue["comments"][$commentIndex]["author"],
                    "commentBody" => $issue["comments"][$commentIndex]["body"],
                    "commentPrivate" => $issue["comments"][$commentIndex]["private"],
                    "commentMarkdown" => $issue["comments"][$commentIndex]["markdown"],
                ], [
                    "commentAuthor" => $this->login,
                    "commentBody" => $comment,
                    "commentPrivate" => $private,
                    "commentMarkdown" => $markdown,
                ]);

                if ($issue["comments"][$commentIndex]["author"] == $this->login || $roles[$acr] >= 70) {
                    return $this->mongo->$db->$acr->updateOne(
                        [
                            "issueId" => $issueId,
                        ],
                        [
                            '$set' => [
                                "comments.$commentIndex.body" => $comment,
                                "comments.$commentIndex.created" => time(),
                                "comments.$commentIndex.author" => $this->login,
                                "comments.$commentIndex.private" => $private,
                                "comments.$commentIndex.modified" => true,
                            ]
                        ]
                    ) && $this->mongo->$db->$acr->updateOne(
                        [
                            "issueId" => $issueId,
                        ],
                        [
                            '$set' => [
                                "updated" => time(),
                            ],
                        ]
                    );
                }

                return false;
            }

            /**
             * @inheritDoc
             */

            public function deleteComment($issueId, $commentIndex) {
                $db = $this->dbName;
                $acr = explode("-", $issueId)[0];

                if (!checkInt($commentIndex)) {
                    return false;
                }

                $roles = $this->myRoles();

                if (!@$roles[$acr] || $roles[$acr] < 20) {
                    return false;
                }

                $issue = $this->getIssue($issueId);

                if (!$issue) {
                    return false;
                }

                $this->addJournalRecord($issueId, "deleteComment#$commentIndex", [
                    "commentAuthor" => $issue["comments"][$commentIndex]["author"],
                    "commentBody" => $issue["comments"][$commentIndex]["body"],
                    "commentPrivate" => $issue["comments"][$commentIndex]["private"],
                    "commentCreated" => $issue["comments"][$commentIndex]["created"],
                    "commentMarkdown" => $issue["comments"][$commentIndex]["markdown"],
                ], null);

                if ($issue["comments"][$commentIndex]["author"] == $this->login || $roles[$acr] >= 70) {
                    return $this->mongo->$db->$acr->updateOne([ "issueId" => $issueId ], [ '$unset' => [ "comments.$commentIndex" => true ] ]) &&
                        $this->mongo->$db->$acr->updateOne([ "issueId" => $issueId ], [ '$pull' => [ "comments" => null ] ]) &&
                        $this->mongo->$db->$acr->updateOne(
                            [
                                "issueId" => $issueId,
                            ],
                            [
                                '$set' => [
                                    "updated" => time(),
                                ],
                            ]
                        );
                }

                return false;
            }

            /**
             * @inheritDoc
             */

            public function addAttachments($issueId, $attachments) {
                $db = $this->dbName;

                $acr = explode("-", $issueId)[0];

                $projects = $this->getProjects($acr);

                if (!$projects || !$projects[0]) {
                    return false;
                }

                $project = $projects[0];

                $issue = $this->getIssue($issueId);

                if (!$issue) {
                    return false;
                }

                $roles = $this->myRoles();

                if (!@$roles[$acr] || $roles[$acr] < 20) {
                    return false;
                }

                $files = loadBackend("files");

                foreach ($attachments as $i => $attachment) {
                    $list = $files->searchFiles([ "metadata.issue" => true, "metadata.issueId" => $issueId, "filename" => $attachment["name"] ]);

                    if (count($list)) {
                        $f = pathinfo($attachment["name"]);
                        $incr = $this->redis->incr("FILEDUP:$acr");
                        $attachments[$i]["name"] = $f["filename"] . "-dup-" . sprintf("%06d", $incr) . "." . $f["extension"];
                    }

                    if (@$attachment["body"]) {
                        $attachments[$i]["body"] = base64_decode($attachment["body"]);
                        if (strlen(@$attachments[$i]["body"]) <= 0 || strlen(@$attachments[$i]["body"]) > $project["maxFileSize"]) {
                            return false;
                        }
                    } else
                    if (@$attachment["url"]) {
                        $attachments[$i]["body"] = @file_get_contents($attachment["url"]);
                        if (strlen(@$attachments[$i]["body"]) <= 0 || strlen(@$attachments[$i]["body"]) > $project["maxFileSize"]) {
                            return false;
                        }
                    } else
                    if (@$attachment["ud363"]) {
//
                    } else {
                        return false;
                    }
                }

                $checksums = [];

                foreach ($attachments as $attachment) {
                    $meta = [];

                    if (@$attachment["metadata"]) {
                        $meta = $attachment["metadata"];
                    }

                    $meta["date"] = @$attachment["date"] ? round($attachment["date"] / 1000) : time();
                    $meta["added"] = time();
                    $meta["type"] = @$attachment["type"];
                    $meta["comment"] = @$attachment["comment"];
                    $meta["issue"] = true;
                    $meta["project"] = $acr;
                    $meta["issueId"] = $issueId;
                    $meta["attachman"] = $this->login;

                    if ($this->config["backends"]["tt"]["attachments"] === "external") {
                        $meta["external"] = true;
                    }

                    $stream = false;

                    if ($attachment["body"]) {
                        $stream = $files->contentsToStream($attachment["body"]);
                    }

                    if (!(
                        $stream &&
                        $files->addFile($attachment["name"], $stream, $meta) &&
                        $this->mongo->$db->$acr->updateOne(
                            [
                                "issueId" => $issueId,
                            ],
                            [
                                '$set' => [
                                    "updated" => time(),
                                ],
                            ]
                        ) &&
                        $this->addJournalRecord($issueId, "addAttachment", null, [
                            "attachmentFilename" => $attachment["name"],
                        ])
                    )) {
                        return false;
                    } else {
                        $checksums[$attachment["name"]] = md5($attachment["body"]);
                    }
                }

                return $checksums;
            }

            /**
             * @inheritDoc
             */

            public function deleteAttachment($issueId, $filename) {
                $db = $this->dbName;

                $project = explode("-", $issueId)[0];

                $roles = $this->myRoles();

                if (!@$roles[$project] || $roles[$project] < 20) {
                    return false;
                }

                $files = loadBackend("files");

                if ($roles[$project] >= 70) {
                    $list = $files->searchFiles([ "metadata.issue" => true, "metadata.issueId" => $issueId, "filename" => $filename ]);
                } else {
                    $list = $files->searchFiles([ "metadata.issue" => true, "metadata.attachman" => $this->login, "metadata.issueId" => $issueId, "filename" => $filename ]);
                }

                $delete = true;

                if ($list) {
                    foreach ($list as $entry) {
                        $delete = $delete && $files->deleteFile($entry["id"]) &&
                            $this->mongo->$db->$project->updateOne(
                                [
                                    "issueId" => $issueId,
                                ],
                                [
                                    '$set' => [
                                        "updated" => time(),
                                    ],
                                ]
                            ) &&
                            $this->addJournalRecord($issueId, "deleteAttachment", [
                                "attachmentFilename" => $filename,
                            ], null);
                    }
                }

                return $delete;
            }

            /**
             * @inheritDoc
             */

            public function addArrayValue($issueId, $field, $value) {
                $db = $this->dbName;
                $acr = explode("-", $issueId)[0];

                $customFields = $this->getCustomFields();

                $project = false;
                $projects = $this->getProjects();
                foreach ($projects as $p) {
                    if ($p["acronym"] == $acr) {
                        $project = $p;
                        break;
                    }
                }

                if (!$project) {
                    return false;
                }

                $f = false;
                foreach ($customFields as $cf) {
                    if ($field == "_cf_" . $cf["field"]) {
                        if ($cf["type"] == "array" && in_array($cf["customFieldId"], $project["customFields"])) {
                            $f = true;
                        }
                        break;
                    }
                }

                if (!$f) {
                    return false;
                }

                $roles = $this->myRoles();

                if (!@$roles[$acr] || $roles[$acr] < 20) {
                    return false;
                }

                $value = trim($value);
                if (!$value) {
                    return false;
                }

                $issue = $this->getIssue($issueId);

                if (!$issue) {
                    return false;
                }

                if ($issue[$field] && in_array($value, $issue[$field])) {
                    return false;
                }

                $this->addJournalRecord($issueId, "addArrayValue", null, [
                    $field => $value,
                ]);

                return $this->mongo->$db->$acr->updateOne(
                    [
                        "issueId" => $issueId,
                    ],
                    [
                        '$push' => [
                            $field => $value,
                        ],
                    ]
                ) &&
                $this->mongo->$db->$acr->updateOne(
                    [
                        "issueId" => $issueId,
                    ],
                    [
                        '$set' => [
                            "updated" => time(),
                        ],
                    ]
                );
            }

            /**
             * @inheritDoc
             */

            public function deleteArrayValue($issueId, $field, $value) {
                $db = $this->dbName;
                $acr = explode("-", $issueId)[0];

                $customFields = $this->getCustomFields();

                $project = false;
                $projects = $this->getProjects();
                foreach ($projects as $p) {
                    if ($p["acronym"] == $acr) {
                        $project = $p;
                        break;
                    }
                }

                if (!$project) {
                    return false;
                }

                $f = false;
                foreach ($customFields as $cf) {
                    if ($field == "_cf_" . $cf["field"]) {
                        if ($cf["type"] == "array" && in_array($cf["customFieldId"], $project["customFields"])) {
                            $f = true;
                        }
                        break;
                    }
                }

                if (!$f) {
                    return false;
                }

                $db = $this->dbName;
                $acr = explode("-", $issueId)[0];

                $roles = $this->myRoles();

                if (!@$roles[$acr] || $roles[$acr] < 20) {
                    return false;
                }

                $value = trim($value);
                if (!$value) {
                    return false;
                }

                $issue = $this->getIssue($issueId);

                if (!$issue) {
                    return false;
                }

                if (!array_key_exists($field, $issue)) {
                    return false;
                }

                if (!in_array($value, $issue[$field])) {
                    return false;
                }

                $this->addJournalRecord($issueId, "deleteArrayValue", null, [
                    $field => $value,
                ]);

                $result = $this->mongo->$db->$acr->updateOne(
                    [
                        "issueId" => $issueId,
                    ],
                    [
                        '$unset' => [
                            $field . "." . array_search($value, $issue[$field]) => true,
                        ],
                    ]
                ) &&
                $this->mongo->$db->$acr->updateOne(
                    [
                        "issueId" => $issueId,
                    ],
                    [
                        '$pull' => [
                            $field  => null,
                        ],
                    ]
                ) &&
                $this->mongo->$db->$acr->updateOne(
                    [
                        "issueId" => $issueId,
                    ],
                    [
                        '$set' => [
                            "updated" => time(),
                        ],
                    ]
                );

                if ($result) {
                    $issue = $this->getIssue($issueId);
                    if (!count($issue[$field])) {
                        $result = $result && $this->mongo->$db->$acr->updateOne([ "issueId" => $issueId ], [ '$unset' => [ $field => true ] ]);
                    }
                }

                return $result;
            }

            /**
             * @inheritDoc
             */

            public function getSuggestions($project, $field, $query) {
                $me = $this->myRoles();

                $suggestions = [];

                if (@$me[$project] >= 30) { // 30, 'participant.senior' - can create issues
                    $db = $this->dbName;
                    $query = explode(" ", $query);
                    $and = [];

                    foreach ($query as $token) {
                        if (trim($token)) {
                            $and[] = [
                                $field => [
                                    '$regex' => "^.*" . $token . ".*$",
                                    '$options' => "i",
                                ],
                            ];
                        }
                    }

                    $cursor = $this->mongo->$db->$project->aggregate([
                        [
                            '$match' => [
                                '$and' => $and,
                            ],
                        ],
                        [
                            '$group' => [
                                "_id" => "$" . $field,
                            ],
                        ],
                        [
                            '$sort' => [
                                "_id" => 1,
                            ],
                        ],
                        [
                            '$project' => [
                                $field => 1,
                            ],
                        ],
                    ]);

                    foreach ($cursor as $document) {
                        $suggestions[] = $document["_id"];
                    }
                }

                return $suggestions;
            }

            /**
             * @param $part
             * @return bool
             */

            public function cron($part) {
                $success = true;

                if ($part == "5min") {
                    $success = $this->reCreateIndexes();
                }

                try {
                    if ($part == "monthly") {
                        $files = loadBackend("files");

                        if ($files) {
                            $list = $files->searchFiles([ "metadata.issue" => true ]);
                            $found = false;
                            foreach ($list as $file) {
                                $db = $this->dbName;
                                $project = $file["metadata"]["project"];
                                $count = $this->mongo->$db->$project->countDocuments([ "issueId" => $file["metadata"]["issueId"] ]);
                                if (!$count) {
                                    error_log("missing: " . $file["metadata"]["issueId"] . " but file exists: " . $file["id"]);
                                    $found = true;
                                }
                            }
                            if ($found) {
                                error_log("\ntry something like this: ./mongofiles -d rbt delete_id '{\"\$oid\":\"68f260ffcb9bb20613039b42\"}'");
                            }
                        }
                    }
                } catch (\Exception $e) {
                    $success = false;
                    error_log(print_r($e, true));
                }

                return $success && parent::cron($part);
            }

            /**
             * @inheritDoc
             */

            public function journal($issueId, $action, $old, $new, $workflowAction) {

                if (!function_exists('backends\tt\arrayRecursiveDiff')) {
                    function arrayRecursiveDiff($array1, $array2) {
                        $aReturn = [];

                        foreach ($array1 as $index => $value) {
                            if (array_key_exists($index, $array2)) {
                                if (is_array($value) && is_array($array2[$index])) {
                                    $recursiveDiff = arrayRecursiveDiff($value, $array2[$index]);
                                    if ($recursiveDiff !== []) {
                                        $aReturn[$index] = $recursiveDiff;
                                    }
                                } elseif ($value !== $array2[$index]) {
                                    $aReturn[$index] = $value;
                                }
                            } else {
                                $aReturn[$index] = $value;
                            }
                        }

                        return $aReturn;
                    }
                }

                if ($old && $new) {
                    $keys = [];
                    foreach ($old as $key => $field) {
                        $keys[$key] = 1;
                    }
                    foreach ($new as $key => $field) {
                        $keys[$key] = 1;
                    }
                    foreach ($keys as $key => $one) {
                        if (!array_key_exists($key, $new)) {
                            unset($old[$key]);
                        }
                        if (@is_array(@$old[$key]) || @is_array(@$new[$key])) {
                            $o = @$old[$key];
                            $n = @$new[$key];
                            if (!is_array($o)) {
                                if ($o) {
                                    $o = [ $o ];
                                } else {
                                    $o = [];
                                }
                            }
                            if (!is_array($n)) {
                                if ($n) {
                                    $n = [ $n ];
                                } else {
                                    $n = [];
                                }
                            }

                            if (!count(@arrayRecursiveDiff($o, $n)) && !count(@arrayRecursiveDiff($n, $o))) {
                                unset($old[$key]);
                                unset($new[$key]);
                            }
                        } else
                        if (@$old[$key] == @$new[$key]) {
                            unset($old[$key]);
                            unset($new[$key]);
                        }
                    }
                }

                if (!$old && $new) {
                    foreach ($new as $key => $field) {
                        if (!$field) {
                            unset($new[$key]);
                        }
                    }
                }

                if (!$new && $old) {
                    foreach ($old as $key => $field) {
                        if (!$field) {
                            unset($old[$key]);
                        }
                    }
                }

                if ($workflowAction) {
                    $new["workflowAction"] = $workflowAction;
                }

                if ($new || $old) {
                    return $this->clickhouse->insert("ttlog", [ [ "date" => time(), "issue" => $issueId, "login" => $this->login, "action" => $action, "old" => json_encode($old), "new" => json_encode($new) ] ]);
                } else {
                    return true;
                }
            }

            /**
             * @inheritDoc
             */

            public function journalGet($issueId, $limit = false) {
                if ($limit) {
                    $journal = $this->clickhouse->select("select * from default.ttlog where issue='$issueId' order by date limit $limit");
                } else {
                    $journal = $this->clickhouse->select("select * from default.ttlog where issue='$issueId' order by date");
                }

                if ($journal) {
                    foreach ($journal as &$record) {
                        $record["old"] = json_decode($record["old"], true);
                        $record["new"] = json_decode($record["new"], true);
                    }
                    return $journal;
                } else {
                    return [];
                }
            }

            /**
             * @inheritDoc
             */

            public function journalLast($login, $limit = 4096) {
                $limit = (int)$limit;

                return $this->clickhouse->select("select issue from ttlog where login='$login' group by issue order by max(date) desc limit $limit");
            }

            /**
             * @inheritDoc
             */

            public function get($issueId) {
                $db = $this->dbName;
                $project = explode("-", $issueId)[0];

                if ($db && $project) {
                    $issues = $this->mongo->$db->$project->find([ "issueId" => $issueId ]);

                    foreach ($issues as $issue) {
                        return $issue;
                    }
                }

                return false;
            }

            /**
             * @inheritDoc
             */

            public function store($issue) {
                $db = $this->dbName;
                $project = explode("-", $issue["issueId"])[0];

                if ($db && $project) {
                    return $this->mongo->$db->$project->replaceOne([ "issueId" => $issue["issueId"] ], $issue);
                }

                return false;
            }

            /**
             * @inheritDoc
             */

            public function matchFilter($project, $filter, $issueId) {
                $db = $this->dbName;

                $filter = @json_decode($this->getFilter($filter), true);

                if ($filter) {
                    $db = $this->dbName;

                    $query = false;

                    if (isset($filter["pipeline"])) {
                        $query = object_to_array($this->getIssuesQuery($project, @$filter["pipeline"], [ "issueId" ], [], 0, 1, [], [], true));
                    }

                    if (isset($filter["filter"])) {
                        $query = object_to_array($this->getIssuesQuery($project, @$filter["filter"], [ "issueId" ], [], 0, 1));
                    }

                    if ($query) {
                        $pipeline = [
                            [
                                '$match' => $query,
                            ],
                            [
                                '$match' => [
                                    "issueId" => $issueId
                                ],
                            ],
                        ];

                        foreach ($this->mongo->$db->$project->aggregate($pipeline) as $i) {
                            return true;
                        }

                        return false;
                    }
                } else {
                    return false;
                }
            }

            /**
             * @inheritDoc
             */

            public function cliUsage() {
                $usage = parent::cliUsage();

                if (!@$usage["indexes"]) {
                    $usage["indexes"] = [];
                }

                $usage["indexes"]["list-indexes"] = [
                    "params" => [
                        [
                            "project" => [
                                "value" => "string",
                                "placeholder" => "projectAcronym",
                            ],
                        ],
                    ],
                    "description" => "List indexes for TT project",
                ];

                $usage["indexes"]["create-indexes"] = [
                    "description" => "(Re)Create default TT indexes",
                ];

                $usage["indexes"]["drop-indexes"] = [
                    "description" => "Drop default TT indexes",
                ];

                $usage["indexes"]["create-index"] = [
                    "params" => [
                        [
                            "project" => [
                                "value" => "string",
                                "placeholder" => "projectAcronym",
                            ],
                        ],
                    ],
                    "value" => "string",
                    "placeholder" => "field1[,field2...]",
                    "description" => "Manually create index (for searching and filters)",
                ];

                $usage["indexes"]["drop-index"] = [
                    "params" => [
                        [
                            "project" => [
                                "value" => "string",
                                "placeholder" => "projectAcronym",
                            ],
                        ],
                    ],
                    "value" => "string",
                    "placeholder" => "index",
                    "description" => "Drop single index",
                ];

                return $usage;
            }

            /**
             * @inheritDoc
             */

            public function cli($args) {
                if (array_key_exists("--list-indexes", $args)) {
                    $db = $this->dbName;

                    $c = 0;

                    $acr = $args["--project"];

                    $indexes = array_map(function ($indexInfo) {
                        return [ 'v' => $indexInfo->getVersion(), 'key' => $indexInfo->getKey(), 'name' => $indexInfo->getName() ];
                    }, iterator_to_array($this->mongo->$db->$acr->listIndexes()));

                    foreach ($indexes as $i) {
                        echo $i["name"] . "\n";
                        $c++;
                    }

                    echo "$c indexes total\n";

                    exit(0);
                }

                if (array_key_exists("--create-indexes", $args)) {
                    $c = $this->reCreateIndexes();

                    if ($c === true) {
                        $c = 0;
                    }

                    echo "$c indexes [re]created\n";

                    exit(0);
                }

                if (array_key_exists("--drop-indexes", $args)) {
                    $db = $this->dbName;

                    $c = 0;

                    $projects = $this->getProjects();

                    foreach ($projects as $p) {
                        $acr = $p["acronym"];

                        $indexes = array_map(function ($indexInfo) {
                            return [ 'v' => $indexInfo->getVersion(), 'key' => $indexInfo->getKey(), 'name' => $indexInfo->getName() ];
                        }, iterator_to_array($this->mongo->$db->$acr->listIndexes()));

                        foreach ($indexes as $i) {
                            if (strpos($i["name"], "index_") === 0) {
                                try {
                                    $this->mongo->$db->$acr->dropIndex($i["name"]);
                                    $c++;
                                } catch (\Exception $e) {
                                    //
                                }
                            }
                        }
                    }

                    echo "$c indexes dropped\n";

                    exit(0);
                }

                if (array_key_exists("--create-index", $args)) {
                    $db = $this->dbName;

                    $c = 0;

                    $acr = $args["--project"];

                    $fields = explode(",", $args["--create-index"]);

                    $index = [];
                    $indexName = "";

                    foreach ($fields as $f) {
                        $index[$f] = 1;
                        $indexName .= "_" . $f;
                    }


                    try {
                        $this->mongo->$db->$acr->createIndex($index, [ "name" => "manual_index" . $indexName, ]);
                        $c++;
                    } catch (\Exception $e) {
                        //
                    }

                    echo "$c indexes created\n";

                    exit(0);
                }

                if (array_key_exists("--drop-index", $args)) {
                    $db = $this->dbName;

                    $c = 0;

                    $acr = $args["--project"];

                    $indexes = array_map(function ($indexInfo) {
                        return [ 'v' => $indexInfo->getVersion(), 'key' => $indexInfo->getKey(), 'name' => $indexInfo->getName() ];
                    }, iterator_to_array($this->mongo->$db->$acr->listIndexes()));

                    foreach ($indexes as $i) {
                        if ($i["name"] == $args["--drop-index"]) {
                            try {
                                $this->mongo->$db->$acr->dropIndex($i["name"]);
                                $c++;
                            } catch (\Exception $e) {
                                //
                            }
                        }
                    }

                    echo "$c indexes dropped\n";

                    exit(0);
                }

                parent::cli($args);
            }

            // db part

            /**
             * @inheritDoc
             */

            public function allow($params) {
                return true;
            }

            /**
             * @inheritDoc
             */

            public function capabilities() {
                $cap = parent::capabilities();

                if ($cap) {
                    $cap["mode"] = "rw";
                } else {
                    $cap = [
                        "mode" => "rw",
                    ];
                }

                return $cap;
            }

            /**
             * @inheritDoc
             */

            public function getProjects($acronym = false) {
                $key = $acronym ? "PROJECT:$acronym" : "PROJECTS";

                $cache = $this->cacheGet($key);
                if ($cache) {
                    return $cache;
                }

                try {
                    if ($acronym) {
                        $projects = $this->db->get("select project_id, acronym, project, max_file_size, search_subject, search_description, search_comments, assigned, comments from tt_projects where acronym = :acronym", [
                            "acronym" => $acronym,
                        ]);
                    } else {
                        $projects = $this->db->get("select project_id, acronym, project, max_file_size, search_subject, search_description, search_comments, assigned, comments from tt_projects order by acronym");
                    }
                    $_projects = [];

                    foreach ($projects as $project) {
                        $workflows = $this->db->query("select workflow from tt_projects_workflows where project_id = {$project["project_id"]}", \PDO::FETCH_ASSOC)->fetchAll();
                        $w = [];
                        foreach ($workflows as $workflow) {
                            $w[] = $workflow['workflow'];
                        }

                        if ($this->uid) {
                            $gids = $this->myGroups(true);
                            foreach ($gids as &$gid) {
                                $gid = 1000000 + (int)$gid;
                            }
                            $gids = implode(",", $gids);
                            if ($gids) {
                                $filters = $this->db->query("select project_filter_id, filter, coalesce(personal, 0) as personal from tt_projects_filters where project_id = {$project["project_id"]} and (personal is null or personal = {$this->uid} or personal in ($gids)) order by coalesce(personal, 999999999), filter", \PDO::FETCH_ASSOC)->fetchAll();
                            } else {
                                $filters = $this->db->query("select project_filter_id, filter, coalesce(personal, 0) as personal from tt_projects_filters where project_id = {$project["project_id"]} and (personal is null or personal = {$this->uid}) order by coalesce(personal, 999999999), filter", \PDO::FETCH_ASSOC)->fetchAll();
                            }
                        } else {
                            $filters = $this->db->query("select project_filter_id, filter, coalesce(personal, 0) as personal from tt_projects_filters where project_id = {$project["project_id"]} order by coalesce(personal, 999999999), filter", \PDO::FETCH_ASSOC)->fetchAll();
                        }

                        $f = [];
                        foreach ($filters as $filter) {
                            $f[] = [
                                "projectFilterId" => $filter['project_filter_id'],
                                "filter" => $filter['filter'],
                                "personal" => $filter['personal'],
                            ];
                        }

                        $resolutions = $this->db->query("select issue_resolution_id from tt_projects_resolutions where project_id = {$project["project_id"]}", \PDO::FETCH_ASSOC)->fetchAll();
                        $r = [];
                        foreach ($resolutions as $resolution) {
                            $r[] = $resolution["issue_resolution_id"];
                        }

                        $customFields = $this->db->query("select issue_custom_field_id from tt_projects_custom_fields where project_id = {$project["project_id"]}", \PDO::FETCH_ASSOC)->fetchAll();
                        $cf = [];
                        foreach ($customFields as $customField) {
                            $cf[] = $customField["issue_custom_field_id"];
                        }

                        $customFieldsNoJournal = $this->db->query("select issue_custom_field_id from tt_projects_custom_fields_nojournal where project_id = {$project["project_id"]}", \PDO::FETCH_ASSOC)->fetchAll();
                        $cfnj = [];
                        foreach ($customFieldsNoJournal as $customField) {
                            $cfnj[] = $customField["issue_custom_field_id"];
                        }

                        $u = [];
                        $g = [];

                        $usersBackend = loadBackend("users");
                        $groupsBackend = loadBackend("groups");

                        // precache
                        $usersBackend->getUser(-1, false);

                        $groups = $this->db->query("select project_role_id, gid, role_id, level from tt_projects_roles left join tt_roles using (role_id) where project_id = {$project["project_id"]} and gid is not null and gid > 0");

                        foreach ($groups as $group) {
                            $g[] = [
                                "projectRoleId" => $group["project_role_id"],
                                "gid" => $group["gid"],
                                "roleId" => $group["role_id"],
                                "acronym" => $groupsBackend ? $groupsBackend->getGroup($group["gid"])["acronym"] : null,
                            ];

                            if ($groupsBackend) {
                                $users = $groupsBackend->getUsers($group["gid"]);
                            } else {
                                $users = [];
                            }

                            foreach ($users as $user) {
                                $user = $usersBackend->getUser($user, false);
                                if ($user && $user["uid"] > 0) {
                                    $_f = false;
                                    foreach ($u as &$_u) {
                                        if ($_u["uid"] == $user["uid"]) {
                                            if ($_u["roleId"] < $group["role_id"]) {
                                                $_u["projectRoleId"] = $group["project_role_id"];
                                                $_u["roleId"] = $group["role_id"];
                                                $_u["level"] = $group["level"];
                                                $_u["login"] = $user["login"];
                                                $_u["byGroup"] = true;
                                            }
                                            $_f = true;
                                        }
                                    }
                                    if (!$_f) {
                                        $u[] = [
                                            "projectRoleId" => $group["project_role_id"],
                                            "uid" => $user["uid"],
                                            "roleId" => $group["role_id"],
                                            "level" => $group["level"],
                                            "login" => $user["login"],
                                            "byGroup" => true,
                                        ];
                                    }
                                } else {
                                    //
                                }
                            }
                        }

                        $users = $this->db->query("select project_role_id, uid, role_id, level from tt_projects_roles left join tt_roles using (role_id) where project_id = {$project["project_id"]} and uid is not null and uid > 0");
                        foreach ($users as $user) {
                            $_f = false;

                            foreach ($u as &$_u) {
                                if ($_u["uid"] == $user["uid"]) {
                                    $_u["projectRoleId"] = $user["project_role_id"];
                                    $_u["roleId"] = $user["role_id"];
                                    $_u["level"] = $user["level"];
                                    $_u["login"] = $usersBackend->getLoginByUid($user["uid"]);
                                    $_u["byGroup"] = false;
                                    $_f = true;
                                }
                            }

                            if (!$_f) {
                                $u[] = [
                                    "projectRoleId" => $user["project_role_id"],
                                    "uid" => $user["uid"],
                                    "roleId" => $user["role_id"],
                                    "level" => $user["level"],
                                    "login" => $usersBackend->getLoginByUid($user["uid"]),
                                    "byGroup" => false,
                                ];
                            }
                        }

                        $_projects[] = [
                            "projectId" => $project["project_id"],
                            "acronym" => $project["acronym"],
                            "project" => $project["project"],
                            "maxFileSize" => $project["max_file_size"],
                            "searchSubject" => $project["search_subject"],
                            "searchDescription" => $project["search_description"],
                            "searchComments" => $project["search_comments"],
                            "assigned" => $project["assigned"],
                            "comments" => $project["comments"],
                            "workflows" => $w,
                            "filters" => $f,
                            "resolutions" => $r,
                            "customFields" => $cf,
                            "customFieldsNoJournal" => $cfnj,
                            "users" => $u,
                            "groups" => $g,
                            "viewers" => $this->getProjectViewers($project["project_id"]),
                            "tags" => $this->getTags($project["project_id"]),
                        ];
                    }

                    $this->cacheSet($key, $_projects);

                    return $_projects;
                } catch (\Exception $e) {
                    error_log(print_r($e, true));
                    $this->unCache($key);
                    return false;
                }
            }

            /**
             * @inheritDoc
             */

            public function addProject($acronym, $project) {
                $this->clearCache();

                $acronym = trim($acronym);
                $project = trim($project);

                if (!$acronym || !$project) {
                    return false;
                }

                try {
                    $sth = $this->db->prepare("insert into tt_projects (acronym, project) values (:acronym, :project)");
                    if (!$sth->execute([
                        ":acronym" => $acronym,
                        ":project" => $project,
                    ])) {
                        return false;
                    }

                    return $this->db->lastInsertId();
                } catch (\Exception $e) {
                    error_log(print_r($e, true));
                    return false;
                }
            }

            /**
             * @inheritDoc
             */

            public function modifyProject($projectId, $acronym, $project, $maxFileSize, $searchSubject, $searchDescription, $searchComments, $assigned) {
                $this->clearCache();

                if (!checkInt($projectId) || !trim($acronym) || !trim($project) || !checkInt($maxFileSize) || !checkInt($searchSubject) || !checkInt($searchDescription) || !checkInt($searchComments) || !checkInt($assigned)) {
                    return false;
                }

                try {
                    $sth = $this->db->prepare("update tt_projects set acronym = :acronym, project = :project, max_file_size = :max_file_size, search_subject = :search_subject, search_description = :search_description, search_comments = :search_comments, assigned = :assigned where project_id = $projectId");
                    $sth->execute([
                        "acronym" => $acronym,
                        "project" => $project,
                        "max_file_size" => $maxFileSize,
                        "search_subject" => $searchSubject,
                        "search_description" => $searchDescription,
                        "search_comments" => $searchComments,
                        "assigned" => $assigned,
                    ]);
                } catch (\Exception $e) {
                    error_log(print_r($e, true));
                    return false;
                }

                return true;
            }

            /**
             * @inheritDoc
             */

            public function deleteProject($projectId) {
                $this->clearCache();

                if (!checkInt($projectId)) {
                    return false;
                }

                try {
                    $this->db->exec("delete from tt_projects where project_id = $projectId");
                    // TODO: delete all derivatives
                } catch (\Exception $e) {
                    error_log(print_r($e, true));
                    return false;
                }

                return true;
            }

            /**
             * @inheritDoc
             */

            public function setProjectWorkflows($projectId, $workflows) {
                $this->clearCache();

                // TODO: add transaction, commint, rollback

                if (!checkInt($projectId)) {
                    return false;
                }

                try {
                    $sth = $this->db->prepare("insert into tt_projects_workflows (project_id, workflow) values (:project_id, :workflow)");
                } catch (\Exception $e) {
                    error_log(print_r($e, true));
                    return false;
                }

                try {
                    $this->db->exec("delete from tt_projects_workflows where project_id = $projectId");
                } catch (\Exception $e) {
                    error_log(print_r($e, true));
                    return false;
                }

                try {
                    foreach ($workflows as $workflow) {
                        if (!$sth->execute([
                            ":project_id" => $projectId,
                            ":workflow" => $workflow,
                        ])) {
                            return false;
                        }
                    }
                } catch (\Exception $e) {
                    setLastError("invalidWorflow");
                    error_log(print_r($e, true));
                    return false;
                }

                return true;
            }

            /**
             * @inheritDoc
             */

            public function addProjectFilter($projectId, $filter, $personal) {
                $this->clearCache();

                if (!checkInt($projectId) || !checkInt($personal)) {
                    return false;
                }

                if (!$personal) {
                    $already = $this->db->get("select count(*) from tt_projects_filters where project_id = :project_id and filter = :filter and personal is null", [
                        "project_id" => $projectId,
                        "filter" => $filter,
                    ], false, [ "fieldlify" ]);

                    if ($already) {
                        setLastError("filterAlreadyExists");
                        return false;
                    }
                }

                return $this->db->insert("insert into tt_projects_filters (project_id, filter, personal) values (:project_id, :filter, :personal)", [
                    "project_id" => $projectId,
                    "filter" => $filter,
                    "personal" => $personal?$personal:null,
                ], [
                    "silent",
                ]);
            }

            /**
             * @inheritDoc
             */

            public function deleteProjectFilter($projectFilterId) {
                $this->clearCache();

                if (!checkInt($projectFilterId)) {
                    return false;
                }

                return $this->db->modify("delete from tt_projects_filters where project_filter_id = $projectFilterId");
            }

            /**
             * @inheritDoc
             */

            public function deleteWorkflow($workflow) {
                $this->clearCache();

                parent::deleteWorkflow($workflow);

                $this->db->modify("delete from tt_projects_workflows where workflow = :workflow", [
                    "workflow" => $workflow,
                ]);
            }

            /**
             * @inheritDoc
             */

            public function deleteFilter($filter, $owner = false) {
                $this->clearCache();

                parent::deleteFilter($filter, $owner);

                $this->db->modify("delete from tt_projects_filters where filter = :filter", [
                    "filter" => $filter,
                ]);

                return true;
            }

            /**
             * @inheritDoc
             */

            public function getStatuses() {
                $cache = $this->cacheGet("STATUSES");
                if ($cache) {
                    return $cache;
                }

                try {
                    $statuses = $this->db->query("select issue_status_id, status, final from tt_issue_statuses order by status", \PDO::FETCH_ASSOC)->fetchAll();
                    $_statuses = [];

                    foreach ($statuses as $statuse) {
                        $_statuses[] = [
                            "statusId" => $statuse["issue_status_id"],
                            "status" => $statuse["status"],
                            "final" => $statuse["final"],
                        ];
                    }

                    $this->cacheSet("STATUSES", $_statuses);
                    return $_statuses;
                } catch (\Exception $e) {
                    error_log(print_r($e, true));
                    $this->unCache("STATUSES");
                    return false;
                }
            }

            /**
             * @inheritDoc
             */

            public function addStatus($status, $final) {
                $final = (int)$final ? 1 : 0;

                $this->clearCache();

                $status = trim($status);

                if (!$status) {
                    return false;
                }

                return $this->db->insert("insert into tt_issue_statuses (status, final) values (:status, :final)", [ "status" => $status, "final" => $final ]);
            }

            /**
             * @inheritDoc
             */

            public function modifyStatus($statusId, $status, $final) {
                $final = (int)$final ? 1 : 0;

                $this->clearCache();

                $status = trim($status);

                if (!checkInt($statusId) || !$status) {
                    return false;
                }

                try {
                    $sth = $this->db->prepare("update tt_issue_statuses set status = :status, final = :final where issue_status_id = $statusId");
                    $sth->execute([
                        ":status" => $status,
                        ":final" => $final,
                    ]);
                } catch (\Exception $e) {
                    error_log(print_r($e, true));
                    return false;
                }

                return true;
            }

            /**
             * @inheritDoc
             */

            public function deleteStatus($statusId) {
                $this->clearCache();

                if (!checkInt($statusId)) {
                    return false;
                }

                return $this->db->modify("delete from tt_issue_statuses where issue_status_id = $statusId");
            }

            /**
             * @inheritDoc
             */

            public function getResolutions() {
                $cache = $this->cacheGet("RESOLUTIONS");
                if ($cache) {
                    return $cache;
                }

                try {
                    $resolutions = $this->db->query("select issue_resolution_id, resolution from tt_issue_resolutions order by resolution", \PDO::FETCH_ASSOC)->fetchAll();
                    $_resolutions = [];

                    foreach ($resolutions as $resolution) {
                        $_resolutions[] = [
                            "resolutionId" => $resolution["issue_resolution_id"],
                            "resolution" => $resolution["resolution"],
                        ];
                    }

                    $this->cacheSet("RESOLUTIONS", $_resolutions);
                    return $_resolutions;
                } catch (\Exception $e) {
                    error_log(print_r($e, true));
                    $this->unCache("RESOLUTIONS");
                    return false;
                }
            }

            /**
             * @inheritDoc
             */

            public function addResolution($resolution) {
                $this->clearCache();

                $resolution = trim($resolution);

                if (!$resolution) {
                    return false;
                }

                return $this->db->insert("insert into tt_issue_resolutions (resolution) values (:resolution)", [ "resolution" => $resolution, ]);
            }

            /**
             * @inheritDoc
             */

            public function modifyResolution($resolutionId, $resolution) {
                $this->clearCache();

                $resolution = trim($resolution);

                if (!checkInt($resolutionId) || !$resolution) {
                    return false;
                }

                return $this->db->modify("update tt_issue_resolutions set resolution = :resolution where issue_resolution_id = $resolutionId", [ "resolution" => $resolution, ]);
            }

            /**
             * @inheritDoc
             */

            public function deleteResolution($resolutionId) {
                $this->clearCache();

                if (!checkInt($resolutionId)) {
                    return false;
                }

                return $this->db->modify("delete from tt_issue_resolutions where issue_resolution_id = $resolutionId");
            }

            /**
             * @inheritDoc
             */

            public function setProjectResolutions($projectId, $resolutions) {
                $this->clearCache();

                // TODO: add transaction, commint, rollback

                if (!checkInt($projectId)) {
                    return false;
                }

                try {
                    $sth = $this->db->prepare("insert into tt_projects_resolutions (project_id, issue_resolution_id) values (:project_id, :issue_resolution_id)");
                } catch (\Exception $e) {
                    error_log(print_r($e, true));
                    return false;
                }

                try {
                    $this->db->exec("delete from tt_projects_resolutions where project_id = $projectId");
                } catch (\Exception $e) {
                    error_log(print_r($e, true));
                    return false;
                }

                try {
                    foreach ($resolutions as $resolution) {
                        if (!checkInt($resolution)) {
                            return false;
                        }
                        if (!$sth->execute([
                            ":project_id" => $projectId,
                            ":issue_resolution_id" => $resolution,
                        ])) {
                            return false;
                        }
                    }
                } catch (\Exception $e) {
                    error_log(print_r($e, true));
                    return false;
                }

                return true;
            }

            /**
             * @inheritDoc
             */

            public function getCustomFields() {
                $cache = $this->cacheGet("FIELDS");
                if ($cache) {
                    return $cache;
                }

                try {
                    $customFields = $this->db->query("
                        select
                            issue_custom_field_id,
                            catalog,
                            type,
                            field,
                            field_display,
                            field_display_list,
                            field_description,
                            regex,
                            link,
                            format,
                            editor,
                            float,
                            indx,
                            search,
                            required,
                            readonly
                        from
                            tt_issue_custom_fields
                        order by
                            catalog,
                            field
                    ", \PDO::FETCH_ASSOC)->fetchAll();

                    $_customFields = [];

                    foreach ($customFields as $customField) {
                        $options = $this->db->query("select issue_custom_field_option_id, option, option_display from tt_issue_custom_fields_options where issue_custom_field_id = {$customField["issue_custom_field_id"]} order by display_order", \PDO::FETCH_ASSOC)->fetchAll();
                        $_options = [];

                        foreach ($options as $option) {
                            $_options[] = [
                                "customFieldOptionId" => $option["issue_custom_field_option_id"],
                                "option" => $option["option"],
                                "optionDisplay" => $option["option_display"],
                            ];
                        }

                        $_customFields[] = [
                            "customFieldId" => $customField["issue_custom_field_id"],
                            "catalog" => $customField["catalog"],
                            "type" => $customField["type"],
                            "field" => $customField["field"],
                            "fieldDisplay" => $customField["field_display"],
                            "fieldDisplayList" => $customField["field_display_list"],
                            "fieldDescription" => $customField["field_description"],
                            "regex" => $customField["regex"],
                            "link" => $customField["link"],
                            "format" => $customField["format"],
                            "editor" => trim($customField["editor"] ? $customField["editor"] : ""),
                            "float" => $customField["float"] ? $customField["float"] : "0",
                            "indx" => $customField["indx"],
                            "search" => $customField["search"],
                            "required" => $customField["required"],
                            "readonly" => $customField["readonly"],
                            "options" => $_options,
                        ];
                    }

                    $this->cacheSet("FIELDS", $_customFields);
                    return $_customFields;
                } catch (\Exception $e) {
                    error_log(print_r($e, true));
                    $this->unCache("FIELDS");
                    return false;
                }
            }

            /**
             * @inheritDoc
             */

            public function addCustomField($catalog, $type, $field, $fieldDisplay, $fieldDisplayList) {
                $this->clearCache();

                $catalog = trim($catalog);
                $type = trim($type);
                $field = trim($field);
                $fieldDisplay = trim($fieldDisplay);

                if (!$type || !$field || !$fieldDisplay) {
                    return false;
                }

                try {
                    $sth = $this->db->prepare("
                        insert into
                            tt_issue_custom_fields (catalog, type, field, field_display, field_display_list)
                        values (:catalog, :type, :field, :field_display, :field_display_list)
                    ");

                    if (!$sth->execute([
                        ":catalog" => $catalog,
                        ":type" => $type,
                        ":field" => $field,
                        ":field_display" => $fieldDisplay,
                        ":field_display_list" => $fieldDisplayList,
                    ])) {
                        return false;
                    }

                    return $this->db->lastInsertId();
                } catch (\Exception $e) {
                    error_log(print_r($e, true));
                    return false;
                }
            }

            /**
             * @inheritDoc
             */

            public function setProjectCustomFields($projectId, $customFields) {
                $this->clearCache();

                // TODO: add transaction, commint, rollback

                if (!checkInt($projectId)) {
                    return false;
                }

                try {
                    $sth = $this->db->prepare("insert into tt_projects_custom_fields (project_id, issue_custom_field_id) values (:project_id, :issue_custom_field_id)");
                } catch (\Exception $e) {
                    error_log(print_r($e, true));
                    return false;
                }

                try {
                    $this->db->exec("delete from tt_projects_custom_fields where project_id = $projectId");
                } catch (\Exception $e) {
                    error_log(print_r($e, true));
                    return false;
                }

                try {
                    foreach ($customFields as $customField) {
                        if (!checkInt($customField)) {
                            return false;
                        }
                        if (!$sth->execute([
                            ":project_id" => $projectId,
                            ":issue_custom_field_id" => $customField,
                        ])) {
                            return false;
                        }
                    }
                } catch (\Exception $e) {
                    error_log(print_r($e, true));
                    return false;
                }

                return true;
            }

            /**
             * @inheritDoc
             */

            public function setProjectCustomFieldsNoJournal($projectId, $customFields) {
                $this->clearCache();

                // TODO: add transaction, commint, rollback

                if (!checkInt($projectId)) {
                    return false;
                }

                try {
                    $sth = $this->db->prepare("insert into tt_projects_custom_fields_nojournal (project_id, issue_custom_field_id) values (:project_id, :issue_custom_field_id)");
                } catch (\Exception $e) {
                    error_log(print_r($e, true));
                    return false;
                }

                try {
                    $this->db->exec("delete from tt_projects_custom_fields_nojournal where project_id = $projectId");
                } catch (\Exception $e) {
                    error_log(print_r($e, true));
                    return false;
                }

                try {
                    foreach ($customFields as $customField) {
                        if (!checkInt($customField)) {
                            return false;
                        }
                        if (!$sth->execute([
                            ":project_id" => $projectId,
                            ":issue_custom_field_id" => $customField,
                        ])) {
                            return false;
                        }
                    }
                } catch (\Exception $e) {
                    error_log(print_r($e, true));
                    return false;
                }

                return true;
            }

            /**
             * @inheritDoc
             */

            public function addUserRole($projectId, $uid, $roleId) {
                $this->clearCache();

                if (!checkInt($projectId) || !checkInt($uid) || !checkInt($roleId)) {
                    return false;
                }

                return $this->db->insert("insert into tt_projects_roles (project_id, uid, role_id) values ($projectId, $uid, $roleId)");
            }

            /**
             * @inheritDoc
             */

            public function addGroupRole($projectId, $gid, $roleId) {
                $this->clearCache();

                if (!checkInt($projectId) || !checkInt($gid) || !checkInt($roleId)) {
                    return false;
                }

                $positive = $this->db->get("select count(*) from tt_roles where role_id = $roleId and level > 0", false, false, [ "fieldlify" ]);

                if ($positive) {
                    return $this->db->insert("insert into tt_projects_roles (project_id, gid, role_id) values ($projectId, $gid, $roleId)");
                }

                return false;
            }

            /**
             * @inheritDoc
             */

            public function getRoles() {
                $cache = $this->cacheGet("ROLES");
                if ($cache) {
                    return $cache;
                }

                try {
                    $_roles = $this->db->get("select role_id, name, name_display, level from tt_roles order by level", false, [
                        "role_id" => "roleId",
                        "name" => "name",
                        "name_display" => "nameDisplay",
                        "level" => "level"
                    ]);

                    $this->cacheSet("ROLES", $_roles);
                    return $_roles;
                } catch (\Exception $e) {
                    error_log(print_r($e, true));
                    $this->unCache("ROLES");
                    return false;
                }
            }

            /**
             * @inheritDoc
             */

            public function deleteRole($projectRoleId) {
                $this->clearCache();

                if (!checkInt($projectRoleId)) {
                    return false;
                }

                try {
                    $this->db->exec("delete from tt_projects_roles where project_role_id = $projectRoleId");

                    return true;
                } catch (\Exception $e) {
                    error_log(print_r($e, true));
                    return false;
                }
            }

            /**
             * @inheritDoc
             */

            public function setRoleDisplay($roleId, $nameDisplay) {
                $this->clearCache();

                $nameDisplay = trim($nameDisplay);

                if (!checkInt($roleId) || !$nameDisplay) {
                    return false;
                }

                try {
                    $sth = $this->db->prepare("update tt_roles set name_display = :name_display where role_id = $roleId");
                    $sth->execute([
                        ":name_display" => $nameDisplay,
                    ]);
                } catch (\Exception $e) {
                    error_log(print_r($e, true));
                    return false;
                }

                return true;
            }

            /**
             * @inheritDoc
             */

            public function modifyCustomField($customFieldId, $catalog, $fieldDisplay, $fieldDisplayList, $fieldDescription, $regex, $format, $link, $options, $indx, $search, $required, $editor, $float, $readonly) {
                $this->clearCache();

                if (!checkInt($customFieldId)) {
                    return false;
                }

                if (!checkInt($indx)) {
                    return false;
                }

                if (!checkInt($search)) {
                    return false;
                }

                if (!checkInt($required)) {
                    return false;
                }

                if (!checkInt($float)) {
                    return false;
                }

                if (!checkInt($readonly)) {
                    return false;
                }

                $catalog = trim($catalog);

                $cf = $this->db->query("select * from tt_issue_custom_fields where issue_custom_field_id = $customFieldId", \PDO::FETCH_ASSOC)->fetchAll();
                if (count($cf) !== 1) {
                    return false;
                }
                $cf = $cf[0];

                try {
                    $sth = $this->db->prepare("
                        update
                            tt_issue_custom_fields
                        set
                            catalog = :catalog,
                            field_display = :field_display,
                            field_display_list = :field_display_list,
                            field_description = :field_description,
                            regex = :regex,
                            link = :link,
                            format = :format,
                            editor = :editor,
                            float = :float,
                            indx = :indx,
                            search = :search,
                            required = :required,
                            readonly = :readonly
                        where
                            issue_custom_field_id = $customFieldId
                    ");

                    $sth->execute([
                        ":catalog" => $catalog,
                        ":field_display" => $fieldDisplay,
                        ":field_display_list" => $fieldDisplayList,
                        ":field_description" => $fieldDescription,
                        ":regex" => $regex,
                        ":link" => $link,
                        ":format" => $format,
                        ":editor" => $editor,
                        ":float" => $float,
                        ":indx" => $indx,
                        ":search" => $search,
                        ":required" => $required,
                        ":readonly" => $readonly,
                    ]);

                    if ($cf["type"] === "select") {
                        $t = explode("\n", trim($options));
                        $new = [];
                        foreach ($t as $i) {
                            $i = trim($i);
                            if ($i) {
                                $new[] = $i;
                            }
                        }

                        $ins = $this->db->prepare("insert into tt_issue_custom_fields_options (issue_custom_field_id, option, option_display) values ($customFieldId, :option, :option)");
                        $del = $this->db->prepare("delete from tt_issue_custom_fields_options where issue_custom_field_id = $customFieldId and option = :option");
                        $upd = $this->db->prepare("update tt_issue_custom_fields_options set option_display = :option, display_order = :order where issue_custom_field_id = $customFieldId and option = :option");

                        $options = $this->db->query("select option from tt_issue_custom_fields_options where issue_custom_field_id = $customFieldId", \PDO::FETCH_ASSOC)->fetchAll();
                        $old = [];
                        foreach ($options as $option) {
                            $old[] = $option["option"];
                        }

                        foreach ($old as $j) {
                            $f = false;
                            foreach ($new as $i) {
                                if ($i == $j) {
                                    $f = true;
                                    break;
                                }
                            }
                            if (!$f) {
                                $del->execute([
                                    ":option" => $j,
                                ]);
                            }
                        }

                        foreach ($new as $j) {
                            $f = false;
                            foreach ($old as $i) {
                                if ($i == $j) {
                                    $f = true;
                                    break;
                                }
                            }
                            if (!$f) {
                                $ins->execute([
                                    ":option" => $j,
                                ]);
                            }
                        }

                        $n = 1;
                        foreach ($new as $j) {
                            $upd->execute([
                                ":option" => $j,
                                ":order" => $n,
                            ]);
                            $n++;
                        }
                    }
                } catch (\Exception $e) {
                    error_log(print_r($e, true));
                    return false;
                }

                return true;
            }

            /**
             * @inheritDoc
             */

            public function deleteCustomField($customFieldId) {
                $this->clearCache();

                if (!checkInt($customFieldId)) {
                    return false;
                }

                $cf = $this->db->query("select * from tt_issue_custom_fields where issue_custom_field_id = $customFieldId", \PDO::FETCH_ASSOC)->fetchAll();
                if (count($cf) !== 1) {
                    return false;
                }
                $cf = $cf[0];

                try {
                    return $this->db->modify("delete from tt_issue_custom_fields where issue_custom_field_id = $customFieldId") +
                        $this->db->modify("delete from tt_issue_custom_fields_options where issue_custom_field_id = $customFieldId") +
                        $this->db->modify("delete from tt_projects_custom_fields where issue_custom_field_id = $customFieldId") +
                        $this->db->modify("delete from tt_projects_custom_fields_nojournal where issue_custom_field_id = $customFieldId");
                } catch (\Exception $e) {
                    error_log(print_r($e, true));
                    return false;
                }
            }

            /**
             * @inheritDoc
             */

            public function getTags($projectId = false) {
                $key = $projectId?"TAGS:$projectId":"TAGS";

                $cache = $this->cacheGet($key);
                if ($cache) {
                    return $cache;
                }

                if ($projectId !== false) {
                    if (!checkInt($projectId)) {
                        return false;
                    }

                    $_tags = $this->db->get("select * from tt_tags where project_id = $projectId order by tag", false, [
                        "tag_id" => "tagId",
                        "tag" => "tag",
                        "color" => "color",
                        "comments" => "comments",
                    ]);

                    $this->cacheSet($key, $_tags);
                    return $_tags;
                } else {
                    $_tags = $this->db->get("select * from tt_tags order by tag", false, [
                        "tag_id" => "tagId",
                        "project_id" => "projectId",
                        "tag" => "tag",
                        "color" => "color",
                        "comments" => "comments",
                    ]);

                    $this->cacheSet($key, $_tags);
                    return $_tags;
                }
            }

            /**
             * @inheritDoc
             */

            public function addTag($projectId, $tag, $color, $comments) {
                $this->clearCache();

                if (!checkInt($projectId) || !checkStr($tag)) {
                    return false;
                }

                if (!checkStr($comments)) {
                    return false;
                }

                try {
                    $sth = $this->db->prepare("insert into tt_tags (project_id, tag, color, comments) values (:project_id, :tag, :color, :comments)");
                    if (!$sth->execute([
                        "project_id" => $projectId,
                        "tag" => $tag,
                        "color" => $color,
                        "comments" => $comments,
                    ])) {
                        return false;
                    }

                    return $this->db->lastInsertId();
                } catch (\Exception $e) {
                    error_log(print_r($e, true));
                    return false;
                }
            }

            /**
             * @inheritDoc
             */

            public function modifyTag($tagId, $tag, $color, $comments) {
                $this->clearCache();

                if (!checkInt($tagId) || !checkStr($tag)) {
                    return false;
                }

                if (!checkStr($comments)) {
                    return false;
                }

                try {
                    $sth = $this->db->prepare("update tt_tags set tag = :tag, color = :color, comments = :comments where tag_id = $tagId");
                    $sth->execute([
                        "tag" => $tag,
                        "color" => $color,
                        "comments" => $comments,
                    ]);
                } catch (\Exception $e) {
                    error_log(print_r($e, true));
                    return false;
                }

                return true;
            }

            /**
             * @inheritDoc
             */

            public function deleteTag($tagId) {
                $this->clearCache();

                if (!checkInt($tagId)) {
                    return false;
                }

                try {
                    $this->db->exec("delete from tt_tags where tag_id = $tagId");
                } catch (\Exception $e) {
                    error_log(print_r($e, true));
                    return false;
                }

                return true;
            }

            /**
             * @inheritDoc
             */

            public function myRoles($uid = false) {
                $key = ($uid !== false)?"MYROLES:$uid":"MYROLES";

                $cache = $this->cacheGet($key);
                if ($cache) {
                    return $cache;
                }

                if ($uid === false) {
                    $uid = $this->uid;
                }

                $groups = loadBackend("groups");

                if ($groups) {
                    $groups = $groups->getGroups($uid);
                }

                $_projects = [];

                if ($groups) {
                    $g = [];

                    foreach ($groups as $group) {
                        $g[] = $group["gid"];
                    }

                    $g = implode(",", $g);

                    $groups = $this->db->get("select acronym, level from tt_projects_roles left join tt_projects using (project_id) left join tt_roles using (role_id) where gid in ($g) order by level", false, [
                        "level" => "level",
                        "acronym" => "acronym",
                    ]);

                    foreach ($groups as $group) {
                        if (@(int)$projects[$group["acronym"]]) {
                            $_projects[$group["acronym"]] = max(@(int)$projects[$group["acronym"]], (int)$group["level"]);
                        } else {
                            $_projects[$group["acronym"]] = (int)$group["level"];
                        }
                    }
                }

                $levels = $this->db->get("select acronym, level from tt_projects_roles left join tt_projects using (project_id) left join tt_roles using (role_id) where uid = {$uid} and uid > 0 order by level", false, [
                    "level" => "level",
                    "acronym" => "acronym",
                ]);

                foreach ($levels as $level) {
                    if (@(int)$projects[$level["acronym"]]) {
                        if ((int)$level["level"] > 0) {
                            $_projects[$level["acronym"]] = min(@(int)$projects[$level["acronym"]], (int)$level["level"]);
                        } else {
                            unset($projects[$level["acronym"]]);
                        }
                    } else {
                        if ((int)$level["level"] > 0) {
                            $_projects[$level["acronym"]] = (int)$level["level"];
                        }
                    }
                }

                $this->cacheSet($key, $_projects);
                return $_projects;
            }

            /**
             * @inheritDoc
             */

            public function myGroups($returnGids = false) {
                $groups = loadBackend("groups");

                $g = [];

                if ($groups) {
                    $groups = $groups->getGroups($this->uid);

                    if ($returnGids) {
                        foreach ($groups as $group) {
                            $g[] = $group["gid"];
                        }
                    } else {
                        foreach ($groups as $group) {
                            $g[] = $group["acronym"];
                        }
                    }
                }

                return $g;
            }

            /**
             * @inheritDoc
             */

            public function myPrimaryGroup($returnGids = false) {
                $groups = loadBackend("groups");
                $users = loadBackend("users");

                $g = null;

                if ($groups && $users) {
                    $user = $users->getUser($this->uid);

                    if ($user) {
                        if ($returnGids) {
                            $g = (int)$user["primaryGroup"];
                        } else {
                            $g = $user["primaryGroupAcronym"];
                        }
                    }
                }

                return $g;
            }

            /**
             * @inheritDoc
             */

            public function getProjectViewers($projectId) {
                $key = $projectId?"VIEWERS:$projectId":"VIEWERS";

                $cache = $this->cacheGet($key);
                if ($cache) {
                    return $cache;
                }

                if (!checkInt($projectId)) {
                    return false;
                }

                $_viewers = $this->db->get("select field, name from tt_projects_viewers where project_id = $projectId order by name");

                $this->cacheSet($key, $_viewers);
                return $_viewers;
            }

            /**
             * @inheritDoc
             */

            public function setProjectViewers($projectId, $viewers) {
                $this->clearCache();

                if (!checkInt($projectId)) {
                    return false;
                }

                $n = $this->db->modify("delete from tt_projects_viewers where project_id = $projectId");

                foreach ($viewers as $viewer) {
                    $n += $this->db->insert("insert into tt_projects_viewers (project_id, field, name) values (:project_id, :field, :name)", [
                        "project_id" => $projectId,
                        "field" => $viewer["field"],
                        "name" => $viewer["name"],
                    ], [ "silent" ]);
                }

                return $n;
            }

            /**
             * @inheritDoc
             */

            public function setProjectComments($projectId, $comments) {
                $this->clearCache();

                if (!checkInt($projectId)) {
                    return false;
                }

                $t = [];

                $comments = explode("\n", $comments);

                if (function_exists("mb_trim")) {
                    foreach ($comments as $c) {
                        if (mb_trim(preg_replace('~^\s+|\s+$~u', '', $c))) {
                            $t[] = $c;
                        }
                    }
                } else {
                    foreach ($comments as $c) {
                        if (trim(preg_replace('~^\s+|\s+$~u', '', $c))) {
                            $t[] = $c;
                        }
                    }
                }

                $comments = array_unique($t);

                if (extension_loaded('intl') === true) {
                    collator_asort(collator_create('root'), $comments);
                } else {
                    asort($comments);
                }

                $comments = trim(implode("\n", $comments));

                return $this->db->modify("update tt_projects set comments = :comments where project_id = :project_id", [
                    "project_id" => $projectId,
                    "comments" => $comments,
                ]);
            }

            /**
             * @inheritDoc
             */

            public function getFavoriteFilters() {
                return $this->db->get("select filter, project, left_side, icon, color from tt_favorite_filters where login = :login", [
                    "login" => $this->login,
                ], [
                    "filter" => "filter",
                    "project" => "project",
                    "left_side" => "leftSide",
                    "icon" => "icon",
                    "color" => "color",
                ]);
            }

            /**
             * @inheritDoc
             */

            public function addFavoriteFilter($filter, $project, $leftSide, $icon, $color) {
                if (!checkStr($filter)) {
                    return false;
                }

                if (!checkInt($leftSide)) {
                    return false;
                }

                return $this->db->insert("insert into tt_favorite_filters (login, filter, project, left_side, icon, color) values (:login, :filter, :project, :left_side, :icon, :color)", [
                    "login" => $this->login,
                    "filter" => $filter,
                    "project" => $project,
                    "left_side" => $leftSide,
                    "icon" => $icon,
                    "color" => $color,
                ]);
            }

            /**
             * @inheritDoc
             */

            public function deleteFavoriteFilter($filter, $all = false) {
                if (!checkStr($filter)) {
                    return false;
                }

                if ($all) {
                    return $this->db->modify("delete from tt_favorite_filters where filter = :filter", [
                        "filter" => $filter,
                    ]);
                } else {
                    return $this->db->modify("delete from tt_favorite_filters where login = :login and filter = :filter", [
                        "login" => $this->login,
                        "filter" => $filter,
                    ]);
                }
            }

            /**
             * @inheritDoc
             */

            public function getCrontabs() {
                $cache = $this->cacheGet("CRONTABS");
                if ($cache) {
                    return $cache;
                }

                $_crontabs = $this->db->get("select * from tt_crontabs order by crontab, filter, action", false, [
                    "crontab_id" => "crontabId",
                    "crontab" => "crontab",
                    "project_id" => "projectId",
                    "filter" => "filter",
                    "uid" => "uid",
                    "action" => "action",
                ]);

                $this->cacheSet("CRONTABS", $_crontabs);
                return $_crontabs;
            }

            /**
             * @inheritDoc
             */

            public function addCrontab($crontab, $projectId, $filter, $uid, $action) {
                $this->clearCache();

                if (!checkInt($uid)) {
                    return false;
                }

                return $this->db->insert("insert into tt_crontabs (crontab, project_id, filter, uid, action) values (:crontab, :project_id, :filter, :uid, :action)", [
                    "crontab" => $crontab,
                    "project_id" => $projectId,
                    "filter" => $filter,
                    "uid" => $uid,
                    "action" => $action,
                ]);
            }

            /**
             * @inheritDoc
             */

            public function deleteCrontab($crontabId) {
                $this->clearCache();

                if (!checkInt($crontabId)) {
                    return false;
                }

                return $this->db->modify("delete from tt_crontabs where crontab_id = $crontabId");
            }

            /**
             * @inheritDoc
             */

            public function cleanup() {
                $this->db->modify("delete from tt_issue_custom_fields_options where issue_custom_field_id not in (select issue_custom_field_id from tt_issue_custom_fields)");
                $this->db->modify("delete from tt_projects_custom_fields where issue_custom_field_id not in (select issue_custom_field_id from tt_issue_custom_fields)");
                $this->db->modify("delete from tt_projects_custom_fields_nojournal where issue_custom_field_id not in (select issue_custom_field_id from tt_issue_custom_fields)");
                $this->db->modify("delete from tt_projects_viewers where project_id not in (select project_id from tt_projects)");

                return parent::cleanup();
            }
        }
    }
