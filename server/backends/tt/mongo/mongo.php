<?php

    /**
     * backends tt namespace
     */

    namespace backends\tt
    {

        /**
         * internal.db + mongoDB tt class
         */

        require_once __DIR__ . "/../db/db.php";

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
                                $files->addFile($attachment["name"], $files->contentsToStream(base64_decode($attachment["body"])), [
                                    "date" => round($attachment["date"] / 1000),
                                    "added" => time(),
                                    "type" => $attachment["type"],
                                    "issue" => true,
                                    "project" => $acr,
                                    "issueId" => $issue["issueId"],
                                    "attachman" => $issue["author"],
                                ]);
                            }
                        }

                        if ($this->mongo->$db->$acr->insertOne($issue)->getInsertedId()) {
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
            public function modifyIssue($issue)
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

                if ($comment && !$this->addComment($issue["issueId"], $comment, $commentPrivate)) {
                    return false;
                }

                $issue = $this->checkIssue($issue);

                $issue["updated"] = time();

                if ($issue) {
                    return $this->mongo->$db->$project->updateOne([ "issueId" => $issue["issueId"] ], [ "\$set" => $issue ]);
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

                if ($files) {
                    $issueFiles = $files->searchFiles([
                        "metadata.issue" => true,
                        "metadata.issueId" => $issueId,
                    ]);

                    foreach ($issueFiles as $file) {
                        $files->deleteFile($file["id"]);
                    }
                }

                return $this->mongo->$db->$acr->deleteMany([
                    "issueId" => $issueId,
                ]);
            }

            /**
             * @inheritDoc
             */
            public function getIssues($collection, $query, $fields = [], $sort = [ "created" => 1 ], $skip = 0, $limit = 100)
            {
                global $params;

                $db = $this->dbName;

                $me = $this->myRoles();

                if (!@$me[$collection]) {
                    return [];
                }

                $my = $this->myGroups();
                $my[] = $this->login;

                $preprocess = [
                    "%%me" => $this->login,
                    "%%my" => $my,
                ];

                if ($params && array_key_exists("search", $params) && trim($params["search"])) {
                    $preprocess["%%search"] = trim($params["search"]);
                }

                if ($params && array_key_exists("parent", $params) && trim($params["parent"])) {
                    $preprocess["%%parent"] = trim($params["parent"]);
                }

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
                $this->dbCleanup();
            }

            /**
             * @inheritDoc
             */
            public function reCreateIndexes()
            {
                // TODO: Implement reCreateIndexes() method.
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

                if ($issue["comments"][$commentIndex]["author"] == $this->login || $roles[$acr] >= 70) {
                    return
                        $this->mongo->$db->$acr->updateOne([ "issueId" => $issueId ], [ '$unset' => [ "comments.$commentIndex" => true ] ]) &&
                        $this->mongo->$db->$acr->updateOne([ "issueId" => $issueId ], [ '$pull' => [ "comments" => null ] ]);
                }

                return false;
            }

            /**
             * @inheritDoc
             */
            public function addAttachments($issueId, $attachments)
            {
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
                    $list = $files->searchFiles([ "metadata.issue" => true, "metadata.issueId" => $issueId, "filename" => $attachment["name"] ]);
                    if (count($list)) {
                        return false;
                    }
                    if (strlen(base64_decode($attachment["body"])) > $project["maxFileSize"]) {
                        return false;
                    }
                }

                foreach ($attachments as $attachment) {
                    $files->addFile($attachment["name"], $files->contentsToStream(base64_decode($attachment["body"])), [
                        "date" => round($attachment["date"] / 1000),
                        "added" => time(),
                        "type" => $attachment["type"],
                        "issue" => true,
                        "project" => $acr,
                        "issueId" => $issueId,
                        "attachman" => $this->login,
                    ]);
                }

                return true;
            }

            /**
             * @inheritDoc
             */
            public function deleteAttachment($issueId, $filename)
            {
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

                if ($list && $list[0] && $list[0]["id"]) {
                    return $files->deleteFile($list[0]["id"]);
                }

                return false;
            }

            /**
             * @param $part
             * @return bool
             */
            public function cron($part)
            {
                if ($part == "5min") {
                    if ($this->redis->get("ttReCreateIndexes")) {
                        $this->redis->delete("ttReCreateIndexes");
                        $this->reCreateIndexes();
                    }
                }
                return parent::cron($part);
            }

            /**
             * @inheritDoc
             */
            public function deleteCustomField($customFieldId)
            {
                $this->dbDeleteCustomField($customFieldId);
                $this->redis->set("ttReCreateIndexes", true);
            }

            /**
             * @inheritDoc
             */
            public function modifyCustomField($customFieldId, $fieldDisplay, $fieldDescription, $regex, $format, $link, $options, $indx, $search, $required, $editor)
            {
                $this->dbModifyCustomField($customFieldId, $fieldDisplay, $fieldDescription, $regex, $format, $link, $options, $indx, $search, $required, $editor);
                $this->redis->set("ttReCreateIndexes", true);
            }

            /**
             * @inheritDoc
             */
            public function addJournalRecord($issue, $record)
            {
                // TODO: Implement addJournalRecord() method.
            }
        }
    }
