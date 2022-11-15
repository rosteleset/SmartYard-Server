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
             * @param string $realFileName
             * @param string $fileContent
             * @param string $meta
             * @return string uuid
             */
            abstract public function addFile($realFileName, $fileContent, $meta = []);

            /**
             * get file from storage
             *
             * @param $uuid
             * @return object file, filename, metadata
             */
            abstract public function getFile($uuid);

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
             * delete file
             *
             * @param $uuid
             * @return boolean
             */
            abstract public function deleteFile($uuid);
        }
    }
