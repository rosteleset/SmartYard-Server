<?php

    /**
     * @api {get} /api/inbox/broadcast preview address broadcast audience
     * @apiVersion 1.0.0
     * @apiName getAddressBroadcastAudience
     * @apiGroup inbox
     * @apiHeader {String} Authorization authentication token
     * @apiQuery {String="regionId","areaId","cityId","settlementId","streetId","houseId","all"} by
     * @apiQuery {Number} [query] Address ID; omit or use 0 only for by=all
     * @apiSuccess {Object} audience
     * @apiSuccess {Number} audience.count Number of distinct mobile subscribers linked to descendant flats
     */

    /**
     * @api {post} /api/inbox/broadcast queue an address broadcast
     * @apiVersion 1.0.0
     * @apiName queueAddressBroadcast
     * @apiGroup inbox
     * @apiHeader {String} Authorization authentication token
     * @apiBody {String="regionId","areaId","cityId","settlementId","streetId","houseId","all"} by
     * @apiBody {Number} [query] Address ID; omit or use 0 only for by=all
     * @apiBody {String} title
     * @apiBody {String} body
     * @apiBody {String="inbox","money"} [action="inbox"]
     * @apiSuccess {Object} queued
     * @apiSuccess {Number} queued.count Newly queued messages, excluding identical pending messages
     * @apiDescription Recipients are resolved again when submitted. Delivery uses the existing
     * households minutely queue, independently of the browser. Both methods require the same
     * permission as POST /api/inbox/message (addresses/house/PUT).
     */

    namespace api\inbox {

        use api\api;

        class broadcast extends api {

            public static function GET($params) {
                $households = loadBackend("households");
                $count = $households->getAddressBroadcastRecipientCount($params["by"] ?? null, $params["query"] ?? null);

                return $count === false ? api::ANSWER(false) : api::ANSWER([ "count" => $count ], "audience", 0);
            }

            public static function POST($params) {
                $households = loadBackend("households");
                $count = $households->queueAddressBroadcast(
                    $params["by"] ?? null,
                    $params["query"] ?? null,
                    $params["title"] ?? null,
                    $params["body"] ?? null,
                    $params["action"] ?? "inbox"
                );

                return $count === false ? api::ANSWER(false) : api::ANSWER([ "count" => $count ], "queued", 0);
            }

            public static function index() {
                return [
                    "GET" => "#same(addresses,house,PUT)",
                    "POST" => "#same(addresses,house,PUT)",
                ];
            }
        }
    }
