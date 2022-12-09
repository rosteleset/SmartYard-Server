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
             * @param string $fileContents
             * @param array $metadata
             * @return string uuid
             */
            abstract public function addFileByContents($realFileName, $fileContents, $metadata = []);

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
             * @param bool $stream
             * @return object contents, fileInfo | stream, fileInfo
             */
            abstract public function getFile($uuid, $stream = false);

            /**
             * @param $uuid
             * @return mixed
             */
            abstract public function getFileContents($uuid);

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
             * @param $metadataField
             * @param $fieldValue
             * @return mixed
             */
            abstract public function searchFilesBy($metadataField, $fieldValue);

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
