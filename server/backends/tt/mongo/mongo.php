<?php

/**
 * backends tt namespace
 */

namespace backends\tt {

    use MongoDB\Client;

    /**
     * internal.db + mongoDB tt class
     */
    class mongo extends tt
    {
        protected Client $mongo;
        protected string $dbName;

        /**
         * @inheritDoc
         */
        public function __construct($config, $db, $redis, $login = false)
        {
            parent::__construct($config, $db, $redis, $login);

            $this->dbName = @$config["backends"]["tt"]["db"] ?: "tt";

            if (@$config["backends"]["tt"]["uri"]) {
                $this->mongo = new Client($config["backends"]["tt"]["uri"]);
            } else {
                $this->mongo = new Client();
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
                last_error("invalidIssue");
                return false;
            }

            $me = $this->myRoles();

            if (@$me[$acr] >= 30) { // 30, 'participant.senior' - can create issues
                $db = $this->dbName;

                $aiid = $this->redis->incr("aiid_" . $acr);
                $issue["issueId"] = $acr . "-" . $aiid;

                $attachments = @$issue["attachments"] ?: [];
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
                last_error("permissionDenied");
                return false;
            }
        }

        /**
         * @inheritDoc
         */
        protected function modifyIssue($issue)
        {
            $db = $this->dbName;
            $project = explode("-", $issue["issueId"])[0];

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

            if ($comment && !$this->addComment($issue["issueId"], $comment, $commentPrivate)) {
                return false;
            }

            $issue = $this->checkIssue($issue);

            $issue["updated"] = time();

            if ($issue) {
                $old = $this->getIssue($issue["issueId"]);
                $update = false;
                if ($old) {
                    $update = $this->mongo->$db->$project->updateOne(["issueId" => $issue["issueId"]], ["\$set" => $issue]);
                }
                if ($update) {
                    $this->addJournalRecord($issue["issueId"], "modifyIssue", $old, $issue);
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
                last_error("insufficentRights");
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
                    $delete = $delete && $files->deleteFile($file["id"]) &&
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

            if ($delete) {
                $childrens = $this->getIssues($acr, ["parent" => $issueId], ["issueId"], ["created" => 1], 0, 32768);

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
        public function getIssues($collection, $query, $fields = [], $sort = ["created" => 1], $skip = 0, $limit = 100, $preprocess = [])
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

            $preprocess["%%strToday"] = date("Y-M-d");
            $preprocess["%%strYesterday"] = date("Y-M-d", strtotime("-1 day"));
            $preprocess["%%strTomorrow"] = date("Y-M-d", strtotime("+1 day"));

            $preprocess["%%timestamp"] = time();
            $preprocess["%%timestampToday"] = strtotime(date("Y-M-d"));
            $preprocess["%%timestampYesterday"] = strtotime(date("Y-M-d", strtotime("-1 day")));
            $preprocess["%%timestampTomorrow"] = strtotime(date("Y-M-d", strtotime("+1 day")));
            $preprocess["%%timestamp+2days"] = strtotime(date("Y-M-d", strtotime("+2 day")));
            $preprocess["%%timestamp+3days"] = strtotime(date("Y-M-d", strtotime("+3 day")));
            $preprocess["%%timestamp+7days"] = strtotime(date("Y-M-d", strtotime("+7 day")));
            $preprocess["%%timestamp+1month"] = strtotime(date("Y-M-d", strtotime("+1 month")));
            $preprocess["%%timestamp+1year"] = strtotime(date("Y-M-d", strtotime("+1 year")));
            $preprocess["%%timestamp-2days"] = strtotime(date("Y-M-d", strtotime("-2 day")));
            $preprocess["%%timestamp-3days"] = strtotime(date("Y-M-d", strtotime("-3 day")));
            $preprocess["%%timestamp-7days"] = strtotime(date("Y-M-d", strtotime("-7 day")));
            $preprocess["%%timestamp-1month"] = strtotime(date("Y-M-d", strtotime("-1 month")));
            $preprocess["%%timestamp-1year"] = strtotime(date("Y-M-d", strtotime("-1 year")));
            $preprocess["%%timestamp-2year"] = strtotime(date("Y-M-d", strtotime("-2 year")));
            $preprocess["%%timestamp-3year"] = strtotime(date("Y-M-d", strtotime("-3 year")));

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

                if ($this->redis->get("full_text_search_" . $acr) != $md5) {
                    try {
                        $this->mongo->$db->$acr->dropIndex("fullText");
                    } catch (\Exception $e) {
                        //
                    }
                    $this->mongo->$db->$acr->createIndex($fullText, ["default_language" => @$this->config["language"] ?: "en", "name" => "fullText"]);
                    $this->redis->set("full_text_search_" . $acr, $md5);
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
                        $this->mongo->$db->$acr->createIndex([$i => 1], ["collation" => ["locale" => @$this->config["language"] ?: "en"], "name" => "index_" . $i]);
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
        public function addComment($issueId, $comment, $private)
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
            ]);

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

            if (!check_int($commentIndex)) {
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

            if (!check_int($commentIndex)) {
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
                return $this->mongo->$db->$acr->updateOne(["issueId" => $issueId], ['$unset' => ["comments.$commentIndex" => true]]) &&
                    $this->mongo->$db->$acr->updateOne(["issueId" => $issueId], ['$pull' => ["comments" => null]]) &&
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

            foreach ($attachments as $attachment) {
                $list = $files->searchFiles(["metadata.issue" => true, "metadata.issueId" => $issueId, "filename" => $attachment["name"]]);
                if (count($list)) {
                    return false;
                }
                if (strlen(base64_decode($attachment["body"])) > $project["maxFileSize"]) {
                    return false;
                }
            }

            foreach ($attachments as $attachment) {
                if (!($files->addFile($attachment["name"], $files->contentsToStream(base64_decode($attachment["body"])), [
                        "date" => round($attachment["date"] / 1000),
                        "added" => time(),
                        "type" => $attachment["type"],
                        "issue" => true,
                        "project" => $acr,
                        "issueId" => $issueId,
                        "attachman" => $this->login,
                    ]) &&
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
                    ]))) {
                    return false;
                }
            }

            return true;
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
                $list = $files->searchFiles(["metadata.issue" => true, "metadata.issueId" => $issueId, "filename" => $filename]);
            } else {
                $list = $files->searchFiles(["metadata.issue" => true, "metadata.attachman" => $this->login, "metadata.issueId" => $issueId, "filename" => $filename]);
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

        /**
         * @inheritDoc
         */
        public function allow($params)
        {
            return true;
        }

        /**
         * @inheritDoc
         */
        public function capabilities()
        {
            return [
                "mode" => "rw",
            ];
        }

        /**
         * @inheritDoc
         */
        public function getProjects($acronym = false)
        {
            try {
                if ($acronym) {
                    $projects = $this->db->get("select project_id, acronym, project, max_file_size, search_subject, search_description, search_comments from tt_projects where acronym = :acronym", [
                        "acronym" => $acronym,
                    ]);
                } else {
                    $projects = $this->db->get("select project_id, acronym, project, max_file_size, search_subject, search_description, search_comments from tt_projects order by acronym");
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

                    $u = [];
                    $g = [];

                    $usersBackend = loadBackend("users");
                    $groupsBackend = loadBackend("groups");

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
                            $user = $usersBackend->getUser($user);
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
                                $_u["login"] = $usersBackend->getUser($user["uid"])["login"];
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
                                "login" => $usersBackend->getUser($user["uid"])["login"],
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
                        "workflows" => $w,
                        "filters" => $f,
                        "resolutions" => $r,
                        "customFields" => $cf,
                        "users" => $u,
                        "groups" => $g,
                        "viewers" => $this->getProjectViewers($project["project_id"]),
                        "tags" => $this->getTags($project["project_id"]),
                    ];
                }

                return $_projects;
            } catch (\Exception $e) {
                error_log(print_r($e, true));
                return false;
            }
        }

        /**
         * @inheritDoc
         */
        public function addProject($acronym, $project)
        {
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
        public function modifyProject($projectId, $acronym, $project, $maxFileSize, $searchSubject, $searchDescription, $searchComments)
        {
            if (!check_int($projectId) || !trim($acronym) || !trim($project) || !check_int($maxFileSize) || !check_int($searchSubject) || !check_int($searchDescription) || !check_int($searchComments)) {
                return false;
            }

            try {
                $sth = $this->db->prepare("update tt_projects set acronym = :acronym, project = :project, max_file_size = :max_file_size, search_subject = :search_subject, search_description = :search_description, search_comments = :search_comments where project_id = $projectId");
                $sth->execute([
                    "acronym" => $acronym,
                    "project" => $project,
                    "max_file_size" => $maxFileSize,
                    "search_subject" => $searchSubject,
                    "search_description" => $searchDescription,
                    "search_comments" => $searchComments,
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
        public function deleteProject($projectId)
        {
            if (!check_int($projectId)) {
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
        public function setProjectWorkflows($projectId, $workflows)
        {
            // TODO: add transaction, commint, rollback

            if (!check_int($projectId)) {
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
                last_error("invalidWorflow");
                error_log(print_r($e, true));
                return false;
            }

            return true;
        }

        /**
         * @inheritDoc
         */
        public function addProjectFilter($projectId, $filter, $personal)
        {
            if (!check_int($projectId) || !check_int($personal)) {
                return false;
            }

            if (!$personal) {
                $already = $this->db->get("select count(*) from tt_projects_filters where project_id = :project_id and filter = :filter and personal is null", [
                    "project_id" => $projectId,
                    "filter" => $filter,
                ], false, ["fieldlify"]);

                if ($already) {
                    last_error("filterAlreadyExists");
                    return false;
                }
            }

            return $this->db->insert("insert into tt_projects_filters (project_id, filter, personal) values (:project_id, :filter, :personal)", [
                "project_id" => $projectId,
                "filter" => $filter,
                "personal" => $personal ? $personal : null,
            ]);
        }

        /**
         * @inheritDoc
         */
        public function deleteProjectFilter($projectFilterId)
        {
            if (!check_int($projectFilterId)) {
                return false;
            }

            return $this->db->modify("delete from tt_projects_filters where project_filter_id = $projectFilterId");
        }

        /**
         * @inheritDoc
         */
        public function deleteWorkflow($workflow)
        {
            parent::deleteWorkflow($workflow);

            $this->db->modify("delete from tt_projects_workflows where workflow = :workflow", [
                "workflow" => $workflow,
            ]);
        }

        /**
         * @inheritDoc
         */
        public function deleteFilter($filter, $owner = false)
        {
            parent::deleteFilter($filter);

            $this->db->modify("delete from tt_projects_filters where filter = :filter", [
                "filter" => $filter,
            ]);

            return true;
        }

        /**
         * @inheritDoc
         */
        public function getStatuses()
        {
            try {
                $statuses = $this->db->query("select issue_status_id, status from tt_issue_statuses order by status", \PDO::FETCH_ASSOC)->fetchAll();
                $_statuses = [];

                foreach ($statuses as $statuse) {
                    $_statuses[] = [
                        "statusId" => $statuse["issue_status_id"],
                        "status" => $statuse["status"],
                    ];
                }

                return $_statuses;
            } catch (\Exception $e) {
                error_log(print_r($e, true));
                return false;
            }
        }

        /**
         * @inheritDoc
         */
        public function addStatus($status)
        {
            $status = trim($status);

            if (!$status) {
                return false;
            }

            return $this->db->insert("insert into tt_issue_statuses (status) values (:status)", ["status" => $status,]);
        }

        /**
         * @inheritDoc
         */
        public function modifyStatus($statusId, $status)
        {
            $status = trim($status);

            if (!check_int($statusId) || !$status) {
                return false;
            }

            try {
                $sth = $this->db->prepare("update tt_issue_statuses set status = :status where issue_status_id = $statusId");
                $sth->execute([
                    ":status" => $status,
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
        public function deleteStatus($statusId)
        {
            if (!check_int($statusId)) {
                return false;
            }

            return $this->db->modify("delete from tt_issue_statuses where issue_status_id = $statusId");
        }

        /**
         * @inheritDoc
         */
        public function getResolutions()
        {
            try {
                $resolutions = $this->db->query("select issue_resolution_id, resolution from tt_issue_resolutions order by resolution", \PDO::FETCH_ASSOC)->fetchAll();
                $_resolutions = [];

                foreach ($resolutions as $resolution) {
                    $_resolutions[] = [
                        "resolutionId" => $resolution["issue_resolution_id"],
                        "resolution" => $resolution["resolution"],
                    ];
                }

                return $_resolutions;
            } catch (\Exception $e) {
                error_log(print_r($e, true));
                return false;
            }
        }

        /**
         * @inheritDoc
         */
        public function addResolution($resolution)
        {
            $resolution = trim($resolution);

            if (!$resolution) {
                return false;
            }

            return $this->db->insert("insert into tt_issue_resolutions (resolution) values (:resolution)", ["resolution" => $resolution,]);
        }

        /**
         * @inheritDoc
         */
        public function modifyResolution($resolutionId, $resolution)
        {
            $resolution = trim($resolution);

            if (!check_int($resolutionId) || !$resolution) {
                return false;
            }

            return $this->db->modify("update tt_issue_resolutions set resolution = :resolution where issue_resolution_id = $resolutionId", ["resolution" => $resolution,]);
        }

        /**
         * @inheritDoc
         */
        public function deleteResolution($resolutionId)
        {
            if (!check_int($resolutionId)) {
                return false;
            }

            return $this->db->modify("delete from tt_issue_resolutions where issue_resolution_id = $resolutionId");
        }

        /**
         * @inheritDoc
         */
        public function setProjectResolutions($projectId, $resolutions)
        {
            // TODO: add transaction, commint, rollback

            if (!check_int($projectId)) {
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
                    if (!check_int($resolution)) {
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
        public function getCustomFields()
        {
            try {
                $customFields = $this->db->query("
                        select
                            issue_custom_field_id,
                            catalog,
                            type,
                            field,
                            field_display,
                            field_description,
                            regex,
                            link,
                            format,
                            editor,
                            indx,
                            search,
                            required
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
                        "fieldDescription" => $customField["field_description"],
                        "regex" => $customField["regex"],
                        "link" => $customField["link"],
                        "format" => $customField["format"],
                        "editor" => trim($customField["editor"] ? $customField["editor"] : ""),
                        "indx" => $customField["indx"],
                        "search" => $customField["search"],
                        "required" => $customField["required"],
                        "options" => $_options,
                    ];
                }

                return $_customFields;
            } catch (\Exception $e) {
                error_log(print_r($e, true));
                return false;
            }
        }

        /**
         * @inheritDoc
         */
        public function addCustomField($catalog, $type, $field, $fieldDisplay)
        {
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
                            tt_issue_custom_fields (catalog, type, field, field_display)
                        values (:catalog, :type, :field, :field_display)
                    ");

                if (!$sth->execute([
                    ":catalog" => $catalog,
                    ":type" => $type,
                    ":field" => $field,
                    ":field_display" => $fieldDisplay,
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
        public function setProjectCustomFields($projectId, $customFields)
        {
            // TODO: add transaction, commint, rollback

            if (!check_int($projectId)) {
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
                    if (!check_int($customField)) {
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
        public function addUserRole($projectId, $uid, $roleId)
        {
            if (!check_int($projectId) || !check_int($uid) || !check_int($roleId)) {
                return false;
            }

            return $this->db->insert("insert into tt_projects_roles (project_id, uid, role_id) values ($projectId, $uid, $roleId)");
        }

        /**
         * @inheritDoc
         */
        public function addGroupRole($projectId, $gid, $roleId)
        {
            if (!check_int($projectId) || !check_int($gid) || !check_int($roleId)) {
                return false;
            }

            $positive = $this->db->get("select count(*) from tt_roles where role_id = $roleId and level > 0", false, false, ["fieldlify"]);

            if ($positive) {
                return $this->db->insert("insert into tt_projects_roles (project_id, gid, role_id) values ($projectId, $gid, $roleId)");
            }

            return false;
        }

        /**
         * @inheritDoc
         */
        public function getRoles()
        {
            try {
                return $this->db->get("select role_id, name, name_display, level from tt_roles order by level", false, [
                    "role_id" => "roleId",
                    "name" => "name",
                    "name_display" => "nameDisplay",
                    "level" => "level"
                ]);
            } catch (\Exception $e) {
                error_log(print_r($e, true));
                return false;
            }
        }

        /**
         * @inheritDoc
         */
        public function deleteRole($projectRoleId)
        {
            if (!check_int($projectRoleId)) {
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
        public function setRoleDisplay($roleId, $nameDisplay)
        {
            $nameDisplay = trim($nameDisplay);

            if (!check_int($roleId) || !$nameDisplay) {
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

        public function dbModifyCustomField($customFieldId, $catalog, $fieldDisplay, $fieldDescription, $regex, $format, $link, $options, $indx, $search, $required, $editor)
        {
            if (!check_int($customFieldId)) {
                return false;
            }

            if (!check_int($indx)) {
                return false;
            }

            if (!check_int($search)) {
                return false;
            }

            if (!check_int($required)) {
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
                            field_description = :field_description,
                            regex = :regex,
                            link = :link,
                            format = :format,
                            editor = :editor,
                            indx = :indx,
                            search = :search,
                            required = :required
                        where
                            issue_custom_field_id = $customFieldId
                    ");

                $sth->execute([
                    ":catalog" => $catalog,
                    ":field_display" => $fieldDisplay,
                    ":field_description" => $fieldDescription,
                    ":regex" => $regex,
                    ":link" => $link,
                    ":format" => $format,
                    ":editor" => $editor,
                    ":indx" => $indx,
                    ":search" => $search,
                    ":required" => $required,
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

        public function dbDeleteCustomField($customFieldId)
        {
            if (!check_int($customFieldId)) {
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
                    $this->db->modify("delete from tt_projects_custom_fields where issue_custom_field_id = $customFieldId");
            } catch (\Exception $e) {
                error_log(print_r($e, true));
                return false;
            }
        }

        /**
         * @inheritDoc
         */
        public function getTags($projectId = false)
        {
            if ($projectId !== false) {
                if (!check_int($projectId)) {
                    return false;
                }

                return $this->db->get("select * from tt_tags where project_id = $projectId order by tag", false, [
                    "tag_id" => "tagId",
                    "tag" => "tag",
                    "foreground" => "foreground",
                    "background" => "background",
                ]);
            } else {
                return $this->db->get("select * from tt_tags order by tag", false, [
                    "tag_id" => "tagId",
                    "project_id" => "projectId",
                    "tag" => "tag",
                    "foreground" => "foreground",
                    "background" => "background",
                ]);
            }
        }

        /**
         * @inheritDoc
         */
        public function addTag($projectId, $tag, $foreground, $background)
        {
            if (!check_int($projectId) || !check_string($tag)) {
                return false;
            }

            try {
                $sth = $this->db->prepare("insert into tt_tags (project_id, tag, foreground, background) values (:project_id, :tag, :foreground, :background)");
                if (!$sth->execute([
                    "project_id" => $projectId,
                    "tag" => $tag,
                    "foreground" => $foreground,
                    "background" => $background,
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
        public function modifyTag($tagId, $tag, $foreground, $background)
        {
            if (!check_int($tagId) || !check_string($tag)) {
                return false;
            }

            try {
                $sth = $this->db->prepare("update tt_tags set tag = :tag, foreground = :foreground, background = :background where tag_id = $tagId");
                $sth->execute([
                    "tag" => $tag,
                    "foreground" => $foreground,
                    "background" => $background,
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
        public function deleteTag($tagId)
        {
            if (!check_int($tagId)) {
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
        public function myRoles($uid = false)
        {
            if ($uid === false) {
                $uid = $this->uid;
            }

            $groups = loadBackend("groups");

            if ($groups) {
                $groups = $groups->getGroups($uid);
            }

            $projects = [];

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
                        $projects[$group["acronym"]] = max(@(int)$projects[$group["acronym"]], (int)$group["level"]);
                    } else {
                        $projects[$group["acronym"]] = (int)$group["level"];
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
                        $projects[$level["acronym"]] = min(@(int)$projects[$level["acronym"]], (int)$level["level"]);
                    } else {
                        unset($projects[$level["acronym"]]);
                    }
                } else {
                    if ((int)$level["level"] > 0) {
                        $projects[$level["acronym"]] = (int)$level["level"];
                    }
                }
            }

            return $projects;
        }

        /**
         * @inheritDoc
         */
        public function myGroups($returnGids = false)
        {
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
        public function getProjectViewers($projectId)
        {
            if (!check_int($projectId)) {
                return false;
            }

            return $this->db->get("select field, name from tt_projects_viewers where project_id = $projectId order by name");
        }

        /**
         * @inheritDoc
         */
        public function setProjectViewers($projectId, $viewers)
        {
            if (!check_int($projectId)) {
                return false;
            }

            $n = $this->db->modify("delete from tt_projects_viewers where project_id = $projectId");

            foreach ($viewers as $viewer) {
                $n += $this->db->insert("insert into tt_projects_viewers (project_id, field, name) values (:project_id, :field, :name)", [
                    "project_id" => $projectId,
                    "field" => $viewer["field"],
                    "name" => $viewer["name"],
                ], ["silent"]);
            }

            return $n;
        }

        /**
         * @inheritDoc
         */
        public function getCrontabs()
        {
            return $this->db->get("select * from tt_crontabs order by crontab, filter, action", false, [
                "crontab_id" => "crontabId",
                "crontab" => "crontab",
                "project_id" => "projectId",
                "filter" => "filter",
                "uid" => "uid",
                "action" => "action",
            ]);
        }

        /**
         * @inheritDoc
         */
        public function addCrontab($crontab, $projectId, $filter, $uid, $action)
        {
            if (!check_int($uid)) {
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
        public function deleteCrontab($crontabId)
        {
            if (!check_int($crontabId)) {
                return false;
            }

            return $this->db->modify("delete from tt_crontabs where crontab_id = $crontabId");
        }

        public function dbCleanup()
        {
            $this->db->modify("delete from tt_issue_custom_fields_options where issue_custom_field_id not in (select issue_custom_field_id from tt_issue_custom_fields)");
            $this->db->modify("delete from tt_projects_custom_fields where issue_custom_field_id not in (select issue_custom_field_id from tt_issue_custom_fields)");
            $this->db->modify("delete from tt_projects_viewers where project_id not in (select project_id from tt_projects)");

            return parent::cleanup();
        }
    }
}
