<?php

    /**
     * backends attachments namespace
     */

    namespace backends\attachments {

        /**
         * gridFS storage
         */

        class mongo extends attachments {

            /**
             * @inheritDoc
             */
            public function addFile($meta, $fileContent)
            {
                return GUIDv4();
            }

            /**
             * @inheritDoc
             */
            public function getFile($uuid)
            {
                return false;
            }

            /**
             * @inheritDoc
             */
            public function deleteFile($uuid)
            {
                return true;
            }
        }
    }
