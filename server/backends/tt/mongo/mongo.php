<?php

    /**
     * backends tt namespace
     */

    namespace backends\tt {

        /**
         * internal.db + mongoDB tt class
         */

        require_once __DIR__ . "/../.traits/db.php";

        class mongo extends tt {

            use db {
                cleanup as private dbCleanup;
                modifyCustomField as private dbModifyCustomField;
                deleteCustomField as private dbDeleteCustomField;
            }

            protected $mongo, $dbName, $clickhouse;

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

                    $aiid = $this->redis->incr("aiid_" . $acr);
                    $issue["issueId"] = $acr . "-" . $aiid;

                    $attachments = @$issue["attachments"] ? : [];
                    unset($issue["attachments"]);

                    $issue["created"] = time();
                    $issue["updated"] = time();
                    $issue["author"] = $this->login;

                    try {
                        if ($attachments) {
                            $files = loadBackend("files");

                            foreach ($attachments as $attachment) {
                                $add = $files->addFile($attachment["name"], $files->contentsToStream(base64_decode($attachment["body"])), [
                                    "date" => round($attachment["date"] / 1000),
                                    "added" => time(),
                                    "type" => $attachment["type"],
                                    "issue" => true,
                                    "project" => $acr,
                                    "issueId" => $issue["issueId"],
                                    "attachman" => $issue["author"],
                                ]) &&
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
                        $update = $this->mongo->$db->$project->updateOne([ "issueId" => $issue["issueId"] ], [ "\$set" => $issue ]);
                        if (count($unset)) {
                            $update = $update && $this->mongo->$db->$project->updateOne([ "issueId" => $issue["issueId"] ], [ "\$unset" => $unset ]);
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

            private function getIssuesQuery($collection, $query, $fields = [], $sort = [], $skip = 0, $limit = 100, $preprocess = [], $types = [], $byPipeline = false) {
                $me = $this->myRoles();

                if (!@$me[$collection]) {
                    return [];
                }

                $my = $this->myGroups();
                $my[] = $this->login;

                $primaryGroup = $this->myPrimaryGroup();

                $groups = loadBackend("groups");
                $users = loadBackend("users");

                if ($users && $groups) {
                    $gl = $groups->getGroups();

                    $users->getUser(-1);

                    foreach ($gl as $g) {
                        $gu = [];
                        $uids = $groups->getUsers($g["gid"]);
                        if ($uids) {
                            foreach ($uids as $uid) {
                                if ($uid) {
                                    $gu[] = $users->getUser((int)$uid)["login"];
                                }
                            }
                        }
                        $preprocess["%%group::{$g['acronym']}"] = array_values($gu);
                    }
                }

                $preprocess["%%me"] = $this->login;
                $preprocess["%%my"] = $my;
                $preprocess["%%primaryGroup"] = $primaryGroup;

                $preprocess = $this->standartPreprocessValues($preprocess);
                $types = $this->standartPreprocessTypes($types);

                $preprocess["%%last"] = function () {
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

            public function getIssues($collection, $query, $fields = [], $sort = [], $skip = 0, $limit = 100, $preprocess = [], $types = [], $byPipeline = false) {
                $db = $this->dbName;

                $query = $this->getIssuesQuery($collection, $query, $fields, $sort, $skip, $limit, $preprocess, $types, $byPipeline);

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
                    $_query = json_decode(json_encode($query), true);
                    $_query[] = [ '$project' => $projection ];
                    $_query[] = [ '$skip' => (int)$skip ];
                    $_query[] = [ '$limit' => (int)$limit ];
                    $issues = $this->mongo->$db->$collection->aggregate($_query);

                    $_query = json_decode(json_encode($query), true);
                    $_query[] = [ '$group' => [ '_id' => null, 'countDocuments' => [ '$sum' => 1 ] ] ];
                    $_query[] = [ '$project' => [ '_id' => 0 ] ];
                    $cursor = $this->mongo->$db->$collection->aggregate($_query);
                    foreach ($cursor as $document) {
                        $count = $document["countDocuments"];
                    }
                } else {
                    $_query = json_decode(json_encode($query), true);
                    $issues = $this->mongo->$db->$collection->find($query, $options);
                    $count = $this->mongo->$db->$collection->countDocuments($query);
                }

                $i = [];

                $files = loadBackend("files");

                foreach ($issues as $issue) {
                    $x = json_decode(json_encode($issue), true);
                    $x["id"] = $x["_id"]["\$oid"];
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
                    $_query = json_decode(json_encode($query), true);
                    $_query[] = [ '$project' => $projection_all ];
                    $issues = $this->mongo->$db->$collection->aggregate($_query);
                } else {
                    $issues = $this->mongo->$db->$collection->find($query, $options_all);
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
            public function cleanup()
            {
                return $this->dbCleanup();
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
                            $this->mongo->$db->$acr->createIndex($fullText, [ "default_language" => @$this->config["language"] ? : "en", "name" => "fullText" ]);
                            $cnt++;
                        } catch (\Exception $e) {
                            //
                        }
                        $this->redis->set("FTS:" . $acr, $md5);
                    }
                }

                foreach ($projects as $acr => $project) {
                    $indexes = [
                        "issueId",
                        "created",
                        "subject",
                        "description",
                        "status",
                        "catalog",
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
                                $this->mongo->$db->$acr->createIndex([ $i => 1 ], [ "name" => "index_" . $i, "collation" => [ "locale" => @$this->config["language"] ? : "en", "strength" => 3 ] ]);
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

                return $cnt ? : true;
            }

            /**
             * @inheritDoc
             */
            public function addComment($issueId, $comment, $private, $type = false, $silent = false)
            {
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
                    ] : [
                        "commentBody" => $comment,
                        "commentPrivate" => $private,
                    ],
                    false, $silent
                );

                return $this->mongo->$db->$acr->updateOne(
                    [
                        "issueId" => $issueId,
                    ],
                    $type ?
                    [
                        "\$push" => [
                            "comments" => [
                                "body" => $comment,
                                "created" => time(),
                                "author" => $this->login,
                                "private" => $private,
                                "type" => $type,
                            ],
                        ],
                    ] : [
                        "\$push" => [
                            "comments" => [
                                "body" => $comment,
                                "created" => time(),
                                "author" => $this->login,
                                "private" => $private,
                            ],
                        ],
                    ]
                ) &&
                $this->mongo->$db->$acr->updateOne(
                    [
                        "issueId" => $issueId,
                    ],
                    [
                        "\$set" => [
                            "updated" => time(),
                        ],
                    ]
                );
            }

            /**
             * @inheritDoc
             */
            public function modifyComment($issueId, $commentIndex, $comment, $private)
            {
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
                ], [
                    "commentAuthor" => $this->login,
                    "commentBody" => $comment,
                    "commentPrivate" => $private,
                ]);

                if ($issue["comments"][$commentIndex]["author"] == $this->login || $roles[$acr] >= 70) {
                    return $this->mongo->$db->$acr->updateOne(
                        [
                            "issueId" => $issueId,
                        ],
                        [
                            "\$set" => [
                                "comments.$commentIndex.body" => $comment,
                                "comments.$commentIndex.created" => time(),
                                "comments.$commentIndex.author" => $this->login,
                                "comments.$commentIndex.private" => $private,
                            ]
                        ]
                    ) && $this->mongo->$db->$acr->updateOne(
                        [
                            "issueId" => $issueId,
                        ],
                        [
                            "\$set" => [
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
            public function deleteComment($issueId, $commentIndex)
            {
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
                ], null);

                if ($issue["comments"][$commentIndex]["author"] == $this->login || $roles[$acr] >= 70) {
                    return $this->mongo->$db->$acr->updateOne([ "issueId" => $issueId ], [ '$unset' => [ "comments.$commentIndex" => true ] ]) &&
                        $this->mongo->$db->$acr->updateOne([ "issueId" => $issueId ], [ '$pull' => [ "comments" => null ] ]) &&
                        $this->mongo->$db->$acr->updateOne(
                            [
                                "issueId" => $issueId,
                            ],
                            [
                                "\$set" => [
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
                        $incr = $this->redis->incr("filedup");
                        $attachments[$i]["name"] = $f["filename"] . "-dup-" . sprintf("%06d", $incr) . "." . $f["extension"];
                    }

                    if (@$attachment["body"]) {
                        $attachments[$i]["body"] = base64_decode($attachment["body"]);
                    } else
                    if (@$attachment["url"]) {
                        $attachments[$i]["body"] = @file_get_contents($attachment["url"]);
                    }

                    if (strlen(@$attachments[$i]["body"]) <= 0 || strlen(@$attachments[$i]["body"]) > $project["maxFileSize"]) {
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
                    $meta["type"] = $attachment["type"];
                    $meta["issue"] = true;
                    $meta["project"] = $acr;
                    $meta["issueId"] = $issueId;
                    $meta["attachman"] = $this->login;

                    if (!(
                        $files->addFile($attachment["name"], $files->contentsToStream($attachment["body"]), $meta) &&
                        $this->mongo->$db->$acr->updateOne(
                            [
                                "issueId" => $issueId,
                            ],
                            [
                                "\$set" => [
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
            public function deleteAttachment($issueId, $filename)
            {
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
                                    "\$set" => [
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
                        "\$push" => [
                            $field => $value,
                        ],
                    ]
                ) &&
                $this->mongo->$db->$acr->updateOne(
                    [
                        "issueId" => $issueId,
                    ],
                    [
                        "\$set" => [
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
                        "\$unset" => [
                            $field . "." . array_search($value, $issue[$field]) => true,
                        ],
                    ]
                ) &&
                $this->mongo->$db->$acr->updateOne(
                    [
                        "issueId" => $issueId,
                    ],
                    [
                        "\$pull" => [
                            $field  => null,
                        ],
                    ]
                ) &&
                $this->mongo->$db->$acr->updateOne(
                    [
                        "issueId" => $issueId,
                    ],
                    [
                        "\$set" => [
                            "updated" => time(),
                        ],
                    ]
                );

                if ($result) {
                    $issue = $this->getIssue($issueId);
                    if (!count($issue[$field])) {
                        $result = $result && $this->mongo->$db->$acr->updateOne([ "issueId" => $issueId ], [ "\$unset" => [ $field => true ] ]);
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
                                    "\$regex" => "^.*" . $token . ".*$",
                                    "\$options" => "i",
                                ],
                            ];
                        }
                    }

                    $cursor = $this->mongo->$db->$project->aggregate([
                        [
                            "\$match" => [
                                "\$and" => $and,
                            ],
                        ],
                        [
                            "\$group" => [
                                "_id" => "$" . $field,
                            ],
                        ],
                        [
                            "\$sort" => [
                                "_id" => 1,
                            ],
                        ],
                        [
                            "\$project" => [
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

                return $success && parent::cron($part);
            }

            /**
             * @inheritDoc
             */

            public function deleteCustomField($customFieldId) {
                $this->dbDeleteCustomField($customFieldId);
            }

            /**
             * @inheritDoc
             */

            public function modifyCustomField($customFieldId, $catalog, $fieldDisplay, $fieldDisplayList, $fieldDescription, $regex, $format, $link, $options, $indx, $search, $required, $editor, $float, $readonly) {
                $this->dbModifyCustomField($customFieldId, $catalog, $fieldDisplay, $fieldDisplayList, $fieldDescription, $regex, $format, $link, $options, $indx, $search, $required, $editor, $float, $readonly);
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

                foreach ($journal as &$record) {
                    $record["old"] = json_decode($record["old"], true);
                    $record["new"] = json_decode($record["new"], true);
                }

                return $journal;
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
                        $query = json_decode(json_encode($this->getIssuesQuery($project, @$filter["pipeline"], [ "issueId" ], [], 0, 1, [], [], true)));
                    }

                    if (isset($filter["filter"])) {
                        $query = json_decode(json_encode($this->getIssuesQuery($project, @$filter["filter"], [ "issueId" ], [], 0, 1), true));
                    }

                    if ($query) {
                        $pipeline = [
                            [
                                "\$match" => $query,
                            ],
                            [
                                "\$match" => [
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
                        $this->mongo->$db->$acr->createIndex($index, [ "name" => "manual_index" . $indexName, "collation" => [ "locale" => @$this->config["language"] ? : "en" ]  ]);
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
        }
    }
