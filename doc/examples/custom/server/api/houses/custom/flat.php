<?php

    namespace api\houses\custom {

        require_once __DIR__ . "/../flat.php";

        use api\api;

        /**
         * flat method
         */

        class flat extends \api\houses\flat {

            public static function index() {
                return [
                    "GET",
                    "POST",
                    "PUT",
                    "DELETE",
                ];
            }
        }
    }
