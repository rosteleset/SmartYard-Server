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
             * @param string $meta
             * @return string uuid
             */
            abstract public function addFile($realFileName, $fileContent, $meta = []);

            /**
             * add file to storage
             *
             * $meta["expire"] (optional) expire filetime (unix timestamp)
             *
             * @param string $realFileName
             * @param $stream
             * @param array $meta
             * @return string uuid
             */
            abstract public function addFileByStream($realFileName, $stream, $meta = []);

            /**
             * get file from storage
             *
             * @param $uuid
             * @return object file, filename, metadata
             */
            abstract public function getFile($uuid);

            /**
             * get file from storage
             *
             * @param $uuid
             * @return object file, filename, metadata
             */
            abstract public function getFileByStream($uuid);

            /**
             * @param $uuid
             * @return mixed
             */
            abstract public function getContents($uuid);

            /**
             * @param $uuid
             * @return mixed
             */
            abstract public function getMeta($uuid);

            /**
             * @param $uuid
             * @param $meta
             * @return mixed
             */
            abstract public function setMetadata($uuid, $meta);

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
            abstract public function uuidToGUIDv4($uuid);

            /**
             * @param $guidv4
             * @return mixed
             */
            abstract public function GUIDv4ToUuid($guidv4);
        }
    }
