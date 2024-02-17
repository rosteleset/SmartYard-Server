<?php

    /**
     * addresses api (custom extension)
     */

    namespace api\addresses\custom {

        use api\api;

        /**
         * house method (custom extension)
         * 
         * server/api/addresses/custom
         */

        require_once __DIR__ . "/../house.php";

        class house extends \api\addresses\house {

            public static function PUT($params) {
                error_log("YOUR HOOK CODE HERE");

                return parent::PUT($params);
            }
        }
    }