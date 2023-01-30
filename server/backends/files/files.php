<?php

    /**
     * backends files namespace
     */

    namespace backends\files {

        use backends\backend;

        /**
         * file storage backend
         */

        abstract class files extends backend {

            /**
             * add file to storage
             *
             * $meta["expire"] (optional) expire filetime (unix timestamp)
             *
             * @param string $realFileName
             * @param $stream
             * @param array $metadata
             * @return string uuid
             */
            abstract public function addFile($realFileName, $stream, $metadata = []);

            /**
             * get file from storage
             *
             * @param $uuid
             * @return object stream, fileInfo
             */
            abstract public function getFile($uuid);

            /**
             * @param $uuid
             * @return mixed
             */
            abstract public function getFileStream($uuid);

            /**
             * @param $uuid
             * @return mixed
             */
            abstract public function getFileInfo($uuid);

            /**
             * @param $uuid
             * @param $metadata
             * @return mixed
             */
            abstract public function setFileMetadata($uuid, $metadata);

            /**
             * @param $uuid
             * @return mixed
             */
            abstract public function getFileMetadata($uuid);

            /**
             * @param $query
             * @return mixed
             */
            abstract public function searchFiles($query);

            /**
             * delete file
             *
             * @param $uuid
             * @return boolean
             */
            abstract public function deleteFile($uuid);

            /**
             * @param $uuid
             * @return mixed
             */
            abstract public function toGUIDv4($uuid);

            /**
             * @param $guidv4
             * @return mixed
             */
            abstract public function fromGUIDv4($guidv4);

            /**
             * @param $contents
             * @return false|resource
             */
            public function contentsToStream($contents) {
                $fd = fopen("php://temp", "w+");

                fwrite($fd, $contents, strlen($contents));
                fseek($fd, 0);

                return $fd;
            }

            /**
             * @param $fd
             * @return false|string
             */
            public function streamToContents($fd) {
                fseek($fd, 0);

                return stream_get_contents($fd);
            }
        }
    }
