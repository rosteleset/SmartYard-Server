<?php

    /**
     * @api {get} /api/companies/contact/:contactId get contact
     *
     * @apiVersion 1.0.0
     *
     * @apiName getContact
     * @apiGroup contacts
     *
     * @apiHeader {String} token authentication token
     *
     * @apiParam {Number} contactId
     *
     * @apiSuccess {Object} contact
     */

    /**
     * contacts api
     */

    namespace api\contacts {

        use api\api;

        /**
         * contact
         */

        class contact extends api {

            public static function GET($params) {
                $contact = loadBackend("contact");

                $contact = false;

                if ($contact) {
                    $contact = $contact->getContact(@$params["_id"]);
                }

                return api::ANSWER($contact, ($contact !== false) ? "contact" : "notFound");
            }

            public static function index() {
                if (loadBackend("contacts")) {
                    return [
                        "GET" => "#same(contacts,contacts,GET)",
                        "POST",
                        "PUT",
                        "DELETE",
                    ];
                } else {
                    return false;
                }
            }
        }
    }
