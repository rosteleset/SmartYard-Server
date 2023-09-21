<?php

    /**
     * backends tt namespace
     */

    namespace backends\tt
    {

        /**
         * internal.db + mongoDB tt class
         */

        require_once __DIR__ . "/../../../traits/backends/tt/db.php";

        class mongo extends tt
        {

            use db
            {
                cleanup as private dbCleanup;
                modifyCustomField as private dbModifyCustomField;
                deleteCustomField as private dbDeleteCustomField;
            }

            protected $mongo, $dbName;

            /**
             * @inheritDoc
             */
            public function __construct($config, $db, $redis, $login = false)
            {
                parent::__construct($config, $db, $redis, $login);

                require_once __DIR__ . "/../../../mzfc/mongodb/vendor/autoload.php";

                $this->dbName = @$config["backends"]["tt"]["db"]?:"tt";

                if (@$config["backends"]["tt"]["uri"]) {
                    $this->mongo = new \MongoDB\Client($config["backends"]["tt"]["uri"]);
                } else {
                    $this->mongo = new \MongoDB\Client();
                }
            }

            /**
             * @inheritDoc
             */
            protected function createIssue($issue)
            {
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
            protected function modifyIssue($issue, $workflowAction = false, $apUpdated = true)
            {
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
                if (array_key_exists("comment", $issue) && $issue["comment"]) {
                    $comment = trim($issue["comment"]);
                    $commentPrivate = !!$issue["commentPrivate"];
                    unset($issue["comment"]);
                    unset($issue["commentPrivate"]);
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

                if ($comment && !$this->addComment($issue["issueId"], $comment, $commentPrivate, false, true)) {
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
            public function deleteIssue($issueId)
            {
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
                    $childrens = $this->getIssues($acr, [ "parent" => $issueId ], [ "issueId" ], [ "created" => 1 ], 0, 32768);

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

            /**
             * @inheritDoc
             */
            public function getIssues($collection, $query, $fields = [], $sort = [ "created" => 1 ], $skip = 0, $limit = 100, $preprocess = [])
            {
                $db = $this->dbName;

                $me = $this->myRoles();

                if (!@$me[$collection]) {
                    return [];
                }

                $my = $this->myGroups();
                $my[] = $this->login;

                $preprocess["%%me"] = $this->login;
                $preprocess["%%my"] = $my;

                $preprocess["%%strToday"] = date("Y-m-d");
                $preprocess["%%strToday+1day"] = date("Y-m-d", strtotime("+1 day"));
                $preprocess["%%strToday-1day"] = date("Y-m-d", strtotime("-1 day"));

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
                $preprocess["%%timestamp-1year"] = strtotime(date("Y-m-d", strtotime("-1 year")));
                $preprocess["%%timestamp-2years"] = strtotime(date("Y-m-d", strtotime("-2 year")));
                $preprocess["%%timestamp-3years"] = strtotime(date("Y-m-d", strtotime("-3 year")));
                
                $query = $this->preprocessFilter($query, $preprocess);

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

                $issues = $this->mongo->$db->$collection->find($query, [
                    "projection" => $projection,
                    "skip" => (int)$skip,
                    "limit" => (int)$limit,
                    "sort" => $sort
                ]);

                $i = [];

                $files = loadBackend("files");

                foreach ($issues as $issue) {
                    $x = json_decode(json_encode($issue), true);
                    $x["id"] = $x["_id"]["\$oid"];
                    unset($x["_id"]);
                    if ($files && (!$fields || !count($fields) || in_array("attachments", $fields))) {
                        $x["attachments"] = $files->searchFiles([
                            "metadata.issue" => true,
                            "metadata.issueId" => $issue["issueId"],
                        ]);
                    }
                    $i[] = $x;
                }

                return [
                    "issues" => $i,
                    "projection" => $projection,
                    "sort" => $sort,
                    "skip" => $skip,
                    "limit" => $limit,
                    "count" => $this->mongo->$db->$collection->countDocuments($query),
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
            public function reCreateIndexes()
            {
                $db = $this->dbName;

                // fullText
                $p_ = $this->getProjects();
                $c_ = $this->getCustomFields();

                $projects = [];
                $customFields = [];

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
                        } catch (\Exception $e) {
                            //
                        }
                        $this->mongo->$db->$acr->createIndex($fullText, [ "default_language" => @$this->config["language"] ? : "en", "name" => "fullText" ]);
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
                    ];
    
                    foreach ($project["customFields"] as $c => $p) {
                        if ($p["index"]) {
                            $indexes[] = $c;
                        }
                    }

                    $al = array_map(function ($indexInfo) {
                        return ['v' => $indexInfo->getVersion(), 'key' => $indexInfo->getKey(), 'name' => $indexInfo->getName(), 'ns' => $indexInfo->getNamespace()];
                    }, iterator_to_array($this->mongo->$db->$acr->listIndexes()));

                    $already = [];
                    foreach ($al as $i) {
                        if (strpos($i["name"], "index_") === 0) {
                            $already[] = substr($i["name"], 6);
                        }
                    }

                    foreach ($indexes as $i) {
                        if (!in_array($i, $already)) {
                            $this->mongo->$db->$acr->createIndex([ $i => 1 ], [ "collation" => [ "locale" => @$this->config["language"] ? : "en" ], "name" => "index_" . $i ]);
                        }
                    }

                    foreach ($already as $i) {
                        if (!in_array($i, $indexes)) {
                            $this->mongo->$db->$acr->dropIndex("index_" . $i);
                        }
                    }
                }

                return true;
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

                $this->addJournalRecord($issueId, "addComment", null, [
                    "commentBody" => $comment,
                    "commentPrivate" => $private,
                    "commentType" => $type,
                ], false, $silent);

                return $this->mongo->$db->$acr->updateOne(
                    [
                        "issueId" => $issueId,
                    ],
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
            public function addAttachments($issueId, $attachments)
            {
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

                foreach ($attachments as &$attachment) {
                    $list = $files->searchFiles([ "metadata.issue" => true, "metadata.issueId" => $issueId, "filename" => $attachment["name"] ]);
                    if (count($list)) {
                        return false;
                    }
                    if ($attachment["body"]) {
                        $attachment["body"] = base64_decode($attachment["body"]);
                    } else
                    if ($attachment["url"]) {
                        $attachment["body"] = @file_get_contents($attachment["url"]);
                    }
                    if (strlen($attachment["body"]) <= 0 || strlen($attachment["body"]) > $project["maxFileSize"]) {
                        return false;
                    }
                }

                $checksums = [];

                foreach ($attachments as $attachment) {
                    $meta = [];

                    if (@$attachment["metadata"]) {
                        $meta = $attachment["metadata"];
                    }

                    $meta["date"] = $attachment["date"]?round($attachment["date"] / 1000):time();
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
             * @param $part
             * @return bool
             */
            public function cron($part)
            {
                $success = true;
                if ($part == "5min") {
                    $success = $this->reCreateIndexes();
                }
                return $success && parent::cron($part);
            }

            /**
             * @inheritDoc
             */
            public function deleteCustomField($customFieldId)
            {
                $this->dbDeleteCustomField($customFieldId);
            }

            /**
             * @inheritDoc
             */
            public function modifyCustomField($customFieldId, $catalog, $fieldDisplay, $fieldDescription, $regex, $format, $link, $options, $indx, $search, $required, $editor)
            {
                $this->dbModifyCustomField($customFieldId, $catalog, $fieldDisplay, $fieldDescription, $regex, $format, $link, $options, $indx, $search, $required, $editor);
            }
        }
    }
