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
             * @param string $fileContent
             * @param array $metadata
             * @return string uuid
             */
            abstract public function addFile($realFileName, $fileContent, $metadata = []);

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
            abstract public function addFileByStream($realFileName, $stream, $metadata = []);

            /**
             * get file from storage
             *
             * @param $uuid
             * @return object contents, fileInfo
             */
            abstract public function getFile($uuid);

            /**
             * get file from storage
             *
             * @param $uuid
             * @return object stream, fileInfo
             */
            abstract public function getFileStream($uuid);

            /**
             * @param $uuid
             * @return mixed
             */
            abstract public function getFileContents($uuid);

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
        }
    }
