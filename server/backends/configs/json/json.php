<?php

    /**
     * backends configs namespace
     */

    namespace backends\configs {

        /**
         * config.json configs class
         */

        class json extends configs {

            /**
             * @inheritDoc
             */
            public function getDomophonesModels()
            {
                $files = scandir(__DIR__ . "/../../../hw/ip/domophone/models");

                $models = [];

                foreach ($files as $file) {
                    if (substr($file, -5) === ".json") {
                        $models[$file] = json_decode(file_get_contents(__DIR__ . "/../../../hw/ip/domophone/models/" . $file), true);
                    }
                }

                return $models;
            }

            /**
             * @inheritDoc
             */
            public function getCamerasModels()
            {
                $files = scandir(__DIR__ . "/../../../hw/ip/camera/models");

                $models = [];

                foreach ($files as $file) {
                    if (substr($file, -5) === ".json") {
                        $models[$file] = json_decode(file_get_contents(__DIR__ . "/../../../hw/ip/camera/models/" . $file), true);
                    }
                }

                return $models;
            }

            /**
             * @inheritDoc
             */
            public function getCMSes()
            {
                $files = scandir(__DIR__ . "/../../../hw/ip/domophone/cmses");

                $cmses = [];

                foreach ($files as $file) {
                    if (substr($file, -5) === ".json") {
                        $cmses[$file] = json_decode(file_get_contents(__DIR__ . "/../../../hw//ip/domophone/cmses/" . $file), true);
                    }
                }

                return $cmses;
            }
        }
    }
