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
                $db = $this->dbName;

                $aiid = $this->redis->incr("aiid_" . $acr);
                $issue["issue_id"] = $acr . "-" . $aiid;

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
                                "issue_id" => $issue["issue_id"],
                                "attachman" => $issue["author"],
                            ]);
                        }
                    }

                    return (string)$this->mongo->$db->issues->insertOne($issue)->getInsertedId();
                } catch (\Exception $e) {
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

                $this->mongo->$db->issues->deleteMany([]);
            }

            /**
             * @inheritDoc
             */
            public function getIssues($query, $fields, $sort, $skip, $limit)
            {
                $projects = [];
                $db = $this->dbName;

                $me = $this->whoAmI();

                $allProjects = $this->getProjects();

                foreach ($me as $i => $r) {
                    foreach ($allProjects as $a) {
                        if ($a["projectId"] == $i) {
                            $projects[] = $a["acronym"];
                        }
                    }
                }

                if ($query) {
                    $query = [ '$and' => [ $query, [ "project" => [ '$in' => $projects ] ] ] ];
                } else {
                    $query = [ "project" => [ '$in' => $projects ] ];
                }

                $projection = [];

                foreach ($fields as $field) {
                    $projection[$field] = 1;
                }

                $issues = $this->mongo->$db->issues->find($query, [
                    "projection" => $projection,
                    "skip" => $skip,
                    "limit" => $limit,
                    "sort" => $sort
                ]);

                $i = [];

                foreach ($issues as $issue) {
                    $x = json_decode(json_encode($issue), true);
                    $x["id"] = $x["_id"]['$oid'];
                    unset($x["_id"]);
                    $i[] = $x;
                }

                return [
                    "issues" => $i,
                    "skip" => $skip,
                    "limit" => $limit,
                    "count" => $this->mongo->$db->issues->count($query),
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
                        $this->reCreateIndexes();
                        $this->redis->delete("ttReCreateIndexes");
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
            public function modifyCustomField($customFieldId, $fieldDisplay, $fieldDescription, $regex, $format, $link, $options, $indexes, $required, $editor) {
                $this->dbModifyCustomField($customFieldId, $fieldDisplay, $fieldDescription, $regex, $format, $link, $options, $indexes, $required, $editor);
                $this->redis->set("ttReCreateIndexes", true);
            }

            /**
             * @inheritDoc
             */
            public function getJournal($issue)
            {
                // TODO: Implement getJournal() method.
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
