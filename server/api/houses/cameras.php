<?php

    /**
     * houses api
     */

    namespace api\houses
    {

        use api\api;

        /**
         * entrance method
         */

        class cameras extends api
        {

            public static function GET($params)
            {
                // getCameras
            }

            public static function PUT($params)
            {
                // setCameras
            }

            public static function POST($params)
            {
                // addCamera
            }

            public static function DELETE($params)
            {
                // unlinkCamera
            }

            public static function index()
            {
                return [
                    "GET" => "#same(houses,house,GET)",
                    "POST" => "#same(houses,house,PUT)",
                    "PUT" => "#same(houses,house,PUT)",
                    "DELETE" => "#same(houses,house,PUT)",
                ];
            }
        }
    }
