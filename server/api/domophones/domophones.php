<?php

    /**
     * domophones api
     */

    namespace api\domophones {

        use api\api;

        /**
         * domophones method
         */

        class domophones extends api {

            public static function GET($params) {
                $domophones = loadBackend("domophones");

                $domophones = $domophones?$domophones->getDomophones():$domophones;

                return api::ANSWER($domophones, ($domophones !== false)?"domophones":"badRequest");
            }

            public static function index() {
                return [
                    "GET" => "#common",
                ];
            }
        }
    }
