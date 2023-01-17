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
                global $params;

                $acr = $issue["project"];
                $db = $this->dbName;

                $aiid = $this->redis->incr("aiid_" . $acr);
                $issue["issue_id"] = $acr . "-" . $aiid;

                $attachments = @$issue["attachments"] ? : [];
                $issue["attachments"] = [];

                $issue["created"] = time();
                $issue["author"] = $params["_login"];

                try {
                    if ($attachments) {
                        $files = loadBackend("files");

                        foreach ($attachments as $attachment) {
                            $issue["attachments"][] = $files->addFile($attachment["name"], $files->contentsToStream(base64_decode($attachment["body"])), [
                                "date" => $attachment["date"],
                                "type" => $attachment["type"],
                                "issue" => true,
                                "issue_id" => $issue["issue_id"],
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
                // TODO: Implement deleteIssue() method.
            }

            /**
             * @inheritDoc
             */
            public function getIssues($query, $fields, $start, $limit)
            {
                // TODO: Implement getIssues() method.
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
        }
    }
