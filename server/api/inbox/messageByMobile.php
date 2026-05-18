<?php

    /**
     * @api {post} /api/inbox/messageByMobile send message by subscriber mobile
     *
     * @apiVersion 1.0.0
     *
     * @apiName sendMessageByMobile
     * @apiGroup inbox
     *
     * @apiHeader {String} Authorization authentication token
     *
     * @apiBody {String} mobile subscriber mobile phone number
     * @apiBody {String} title
     * @apiBody {String} body
     * @apiBody {String="inbox","money"} [action="inbox"]
     *
     * @apiSuccess {Object} sent
     */

    /**
     * inbox api
     */

    namespace api\inbox {

        use api\api;

        /**
         * messageByMobile method
         */

        class messageByMobile extends api {

            public static function POST($params) {
                $mobile = preg_replace('/[\s\-()]/', '', trim((string)@$params["mobile"]));
                $title = @$params["title"];
                $body = @$params["body"];
                $action = @$params["action"];
                $action = (is_string($action) && trim($action)) ? trim($action) : "inbox";

                if (!$mobile || !is_string($title) || !trim($title) || !is_string($body) || !trim($body)) {
                    setLastError("invalidParams");
                    return api::ANSWER(false);
                }

                $households = loadBackend("households");
                $subscribers = $households->getSubscribers("mobile", $mobile, [ "noDetail" ]);

                if ($subscribers === false) {
                    return api::ANSWER(false);
                }

                if (!count($subscribers)) {
                    setLastError("mobileSubscriberNotRegistered");
                    return api::ANSWER(false, "notFound");
                }

                if (count($subscribers) > 1) {
                    setLastError("mobileSubscriberNotUnique");
                    return api::ANSWER(false);
                }

                $subscriber = $subscribers[0];
                $inbox = loadBackend("inbox");

                $success = $inbox->sendMessage($subscriber["subscriberId"], $title, $body, $action);

                if ($success !== false) {
                    $success["subscriberId"] = $subscriber["subscriberId"];
                    $success["mobile"] = $subscriber["mobile"];
                    $success["pushSent"] = (int)$success["count"] > 0;
                }

                return api::ANSWER($success, ($success !== false) ? "sent" : "notAcceptable");
            }

            public static function index() {
                return [
                    "POST" => "#same(addresses,house,PUT)",
                ];
            }
        }
    }
