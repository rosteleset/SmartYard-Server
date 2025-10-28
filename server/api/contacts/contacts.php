<?php

    /**
     * @api {get} /api/contacts/contacts get contacts
     *
     * @apiVersion 1.0.0
     *
     * @apiName contacts
     * @apiGroup contacts
     *
     * @apiHeader {String} Authorization authentication token
     *
     * @apiSuccess {Object[]} contacts
     */

    /**
     * contacts api
     */

    namespace api\contacts {

        use api\api;

        /**
         * contacts
         */

        class contacts extends api {

            public static function GET($params) {
                $contacts = loadBackend("contacts");

                $list = false;

                if ($contacts) {
                    $list = $contacts->getContacts();
                }

                return api::ANSWER($list, ($list !== false) ? "contacts" : "notFound");
            }

            public static function index() {
                if (loadBackend("contacts")) {
                    return [
                        "GET",
                    ];
                } else {
                    return false;
                }
            }
        }
    }
