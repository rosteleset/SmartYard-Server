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
            public function createIssue($issue)
            {
                $acr = $issue["project"];

                $me = $this->myRoles();

                if (@$me[$acr] >= 30 || $this->uid === 0) { // 30, 'participant.senior', can create issues or admin
                    $db = $this->dbName;

                    $aiid = $this->redis->incr("aiid_" . $acr);
                    $issue["issue_id"] = $acr . "-" . $aiid;

                    $attachments = @$issue["attachments"] ? : [];
                    unset($issue["attachments"]);

                    $issue["created"] = time();
                    $issue["author"] = $this->login;

                    $issue["assigned"] = array_values($issue["assigned"]);
                    $issue["watchers"] = array_values($issue["watchers"]);
                    $issue["tags"] = array_values($issue["tags"]);

                    try {
                        if ($attachments) {
                            $files = loadBackend("files");

                            foreach ($attachments as $attachment) {
                                $files->addFile($attachment["name"], $files->contentsToStream(base64_decode($attachment["body"])), [
                                    "date" => $attachment["date"],
                                    "type" => $attachment["type"],
                                    "issue" => true,
                                    "project" => $acr,
                                    "issue_id" => $issue["issue_id"],
                                    "attachman" => $issue["author"],
                                ]);
                            }
                        }

                        if ($this->mongo->$db->$acr->insertOne($issue)->getInsertedId()) {
                            return $issue["issue_id"];
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
                // TODO: Implement modifyIssue() method.
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
                        "metadata.issue_id" => $issue,
                    ]);

                    foreach ($issueFiles as $file) {
                        $files->deleteFile($file["id"]);
                    }
                }

                $acr = explode("-", $issue)[0];

                $this->mongo->$db->$acr->deleteMany([
                    "issue_id" => $issue,
                ]);
            }

            /**
             * @inheritDoc
             */
            public function getIssues($collection, $query, $fields = [], $sort = [ "created" => 1 ], $skip = 0, $limit = 100)
            {
                $projects = [];
                $db = $this->dbName;

                $me = $this->myRoles();

                foreach ($me as $i => $r) {
                    $projects[] = $i;
                }

                $my = $this->myGroups();
                $my[] = $this->login;

                if ($query) {
                    $query = $this->preprocessFilter($query, [
                        "%%me" => $this->login,
                        "%%my" => $my,
                    ]);
                    $query = [ '$and' => [ $query, [ "project" => [ '$in' => $projects ] ] ] ];
                } else {
                    $query = [ "project" => [ '$in' => $projects ] ];
                }

                $projection = [];

                if ($fields) {
                    $projection["issue_id"] = 1;
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
                    if ($files) {
                        $x["attachments"] = $files->searchFiles([
                            "metadata.issue" => true,
                            "metadata.issue_id" => $issue["issue_id"],
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
