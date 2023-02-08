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
             * @param $issue
             * @return mixed
             */
            public function checkIssue(&$issue) {
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
                        if (strpos($customFieldsByName[$field]["format"], "multiple") !== false) {
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

                error_log(print_r($issue, true));

                return $issue;
            }

            /**
             * @inheritDoc
             */
            protected function createIssue($issue)
            {
                if (!$this->checkIssue($issue)) {
                    setLastError("invalidIssue");
                    return false;
                }

                $me = $this->myRoles();
                $acr = $issue["project"];

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
                                    "date" => $attachment["date"],
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

                $issue["updated"] = time();

                $comment = false;
                if ($issue["comment"]) {
                    $comment = $issue["comment"];
                    unset($issue["comment"]);
                }

                return $this->mongo->$db->$project->updateOne([ "issueId" => $issue["issueId"] ], [ "\$set" => $this->checkIssue($issue) ]);
            }

            /**
             * @inheritDoc
             */
            public function deleteIssue($issue)
            {
                $db = $this->dbName;

                $files = loadBackend("files");

                if ($files) {
                    $issueFiles = $files->searchFiles([
                        "metadata.issue" => true,
                        "metadata.issueId" => $issue,
                    ]);

                    foreach ($issueFiles as $file) {
                        $files->deleteFile($file["id"]);
                    }
                }

                $acr = explode("-", $issue)[0];

                $this->mongo->$db->$acr->deleteMany([
                    "issueId" => $issue,
                ]);
            }

            /**
             * @inheritDoc
             */
            public function getIssues($collection, $query, $fields = [], $sort = [ "created" => 1 ], $skip = 0, $limit = 100)
            {
                $db = $this->dbName;

                $me = $this->myRoles();

                if (!@$me[$collection]) {
                    return [];
                }

                $my = $this->myGroups();
                $my[] = $this->login;

                $query = $this->preprocessFilter($query, [
                    "%%me" => $this->login,
                    "%%my" => $my,
                ]);

                $projection = [];

                if ($fields) {
                    $projection["issueId"] = 1;
                    foreach ($fields as $field) {
                        $projection[$field] = 1;
                    }
                }

                $issues = $this->mongo->$db->$collection->find($query, [
                    "projection" => $projection,
                    "skip" => $skip,
                    "limit" => $limit,
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
                    "skip" => $skip,
                    "limit" => $limit,
                    "count" => $this->mongo->$db->$collection->countDocuments($query),
                ];
            }

            /**
             * @inheritDoc
             */
            public function cleanup() {
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
            public function addComment($issue, $comment)
            {
                // TODO: Implement addComment() method.
            }

            /**
             * @inheritDoc
             */
            public function modifyComment($issue, $comment)
            {
                // TODO: Implement modifyComment() method.
            }

            /**
             * @inheritDoc
             */
            public function deleteComment($issue, $comment)
            {
                // TODO: Implement deleteComment() method.
            }

            /**
             * @inheritDoc
             */
            public function addAttachment($issue, $file)
            {
                // TODO: Implement addAttachment() method.
            }

            /**
             * @inheritDoc
             */
            public function deleteAttachment($issue, $file)
            {
                // TODO: Implement deleteAttachment() method.
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
            public function deleteCustomField($customFieldId) {
                $this->dbDeleteCustomField($customFieldId);
                $this->redis->set("ttReCreateIndexes", true);
            }

            /**
             * @inheritDoc
             */
            public function modifyCustomField($customFieldId, $fieldDisplay, $fieldDescription, $regex, $format, $link, $options, $indx, $search, $required, $editor) {
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
