<?php

/**
 * configs api
 */

namespace api\configs {

    use api\api;

    /**
     * configs method
     */
    class configs extends api
    {
        public static function GET($params)
        {
            $frs = backend("frs");

            $sections = ["FRSServers" => $frs->servers(),];

            return api::ANSWER($sections, "sections");
        }

        public static function index()
        {
            return [
                "GET" => "#common",
            ];
        }
    }
}