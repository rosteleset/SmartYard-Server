<?php

/**
 * backends files namespace
 */

namespace backends\files {

    use Exception;
    use MongoDB\BSON\ObjectId;
    use MongoDB\Client;

    /**
     * gridFS storage
     */
    class mongo extends files
    {
        private Client $mongo;
        private string $dbName;

        /**
         * @inheritDoc
         */
        public function __construct($config, $db, $redis, $login = false)
        {
            parent::__construct($config, $db, $redis, $login);

            $this->dbName = @$config["backends"]["files"]["db"] ?: "rbt";

            if (@$config["backends"]["files"]["uri"])
                $this->mongo = new Client($config["backends"]["files"]["uri"]);
            else
                $this->mongo = new Client();
        }

        /**
         * @inheritDoc
         */
        public function addFile($realFileName, $stream, $metadata = [])
        {
            $bucket = $this->mongo->{$this->dbName}->selectGridFSBucket();

            $id = $bucket->uploadFromStream($realFileName, $stream);

            if ($metadata)
                $this->setFileMetadata($id, $metadata);

            return (string)$id;
        }

        /**
         * @inheritDoc
         */
        public function getFile($uuid)
        {
            $bucket = $this->mongo->{$this->dbName}->selectGridFSBucket();

            $fileId = new ObjectId($uuid);

            $stream = $bucket->openDownloadStream($fileId);

            return ["fileInfo" => $bucket->getFileDocumentForStream($stream), "stream" => $stream];
        }

        /**
         * @inheritDoc
         */
        public function getFileBytes($uuid)
        {
            $bucket = $this->mongo->{$this->dbName}->selectGridFSBucket();

            return stream_get_contents($bucket->openDownloadStream(new ObjectId($uuid)));
        }

        /**
         * @inheritDoc
         */
        public function getFileStream($uuid)
        {
            return $this->getFile($uuid)["stream"];
        }

        /**
         * @inheritDoc
         */
        public function getFileInfo($uuid)
        {
            return $this->getFile($uuid)["fileInfo"];
        }

        /**
         * @inheritDoc
         */
        public function setFileMetadata($uuid, $metadata)
        {
            return $this->mongo->{$this->dbName}->{"fs.files"}->updateOne(["_id" => new ObjectId($uuid)], ['$set' => ["metadata" => $metadata]]);
        }

        /**
         * @inheritDoc
         */
        public function getFileMetadata($uuid)
        {
            return $this->getFileInfo($uuid)->metadata;
        }

        /**
         * @inheritDoc
         */
        public function searchFiles($query)
        {
            $cursor = $this->mongo->{$this->dbName}->{"fs.files"}->find($query, ["sort" => ["filename" => 1]]);

            $files = [];

            foreach ($cursor as $document) {
                $document = json_decode(json_encode($document), true);
                $document["id"] = (string)$document["_id"]["\$oid"];

                unset($document["_id"]);

                $files[] = $document;
            }

            return $files;
        }

        /**
         * @inheritDoc
         */
        public function deleteFile($uuid)
        {
            $bucket = $this->mongo->{$this->dbName}->selectGridFSBucket();

            if ($bucket) {
                try {
                    $bucket->delete(new ObjectId($uuid));
                    return true;
                } catch (Exception $e) {
                    last_error($e->getMessage());
                }
            }

            return false;
        }

        /**
         * @inheritDoc
         */
        public function toGUIDv4($uuid)
        {
            $uuid = "10001000" . $uuid;

            $hyphen = chr(45);
            return substr($uuid, 0, 8) . $hyphen . substr($uuid, 8, 4) . $hyphen . substr($uuid, 12, 4) . $hyphen . substr($uuid, 16, 4) . $hyphen . substr($uuid, 20, 12);
        }

        /**
         * @inheritDoc
         */
        public function fromGUIDv4($guidv4)
        {
            return str_replace("-", "", substr($guidv4, 8));
        }

        /**
         * @inheritDoc
         */
        public function cron($part)
        {
            if ($part == '5min') {
                $cursor = $this->mongo->{$this->dbName}->{"fs.files"}->find(["metadata.expire" => ['$lt' => time()]]);

                foreach ($cursor as $document)
                    $this->deleteFile($document->_id);
            }

            return true;
        }
    }
}