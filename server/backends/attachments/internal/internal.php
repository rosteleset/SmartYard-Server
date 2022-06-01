<?php

    /**
     * backends attachments namespace
     */

    namespace backends\attachments {

        /**
         * authenticate by local database
         */

        class internal extends attachments {

            /**
             * add file to storage
             *
             * @param string $fileName
             * @param string $fileContent
             * @return string uuid
             */

            public function addFile($fileName, $fileContent) {
                return GUIDv4();
            }

            /**
             * get file from storage
             *
             * @param $uuid
             * @return boolean|object fileNotFound|file, filename, metadata
             */

            public function getFile($uuid) {
                return false;
            }

            /**
             * delete file
             *
             * @param $uuid
             * @return boolean
             */

            public function deleteFile($uuid) {
                return true;
            }
        }
    }
