<?php

    /**
     * backends attachments namespace
     */

    namespace backends\attachments {

        use backends\backend;

        /**
         * local storage attachments class
         */

        abstract class attachments extends backend {

            /**
             * add file to storage
             *
             * @param string $fileName
             * @param string $fileContent
             * @return string uuid
             */

            abstract public function addFile($fileName, $fileContent);

            /**
             * get file from storage
             *
             * @param $uuid
             * @return object file, filename, metadata
             */

            abstract public function getFile($uuid);

            /**
             * delete file
             *
             * @param $uuid
             * @return boolean
             */

            abstract public function deleteFile($uuid);
        }
    }
