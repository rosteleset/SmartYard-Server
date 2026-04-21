<?php

    /**
     * @api {post} /api/billing/subscriptions synchronize active contracts for auto-blocking
     *
     * @apiVersion 1.0.0
     *
     * @apiName subscriptions
     * @apiGroup subscriptions
     *
     * @apiHeader {String} Authorization authentication token
     *
     * @apiParam {Object[]} subscribers list of subscribers for auto-block synchronization
        * @apiParam {Number|Boolean} [subscribers.isActive] contract state (`1|true` => autoBlock=0, `0|false` => autoBlock=1). Optional for phone-only sync; if omitted, `autoBlock` is left unchanged
        * @apiParam {Number} [subscribers.subscriberID] subscriber ID (contract). Required if `buildingUUID+flatNumber` pair is not provided
        * @apiParam {String} [subscribers.buildingUUID] building UUID. Must be provided together with `flatNumber` if `subscriberID` is omitted
        * @apiParam {String} [subscribers.flatNumber] flat number. Pair field for `buildingUUID`
        * @apiParam {String} [subscribers.agreement] agreement number (optional custom field update, persisted when the flat is resolved by `buildingUUID+flatNumber` or by unique `subscriberID`)
        * @apiParam {String} [subscribers.addressText] address text (optional reference/debug custom field update, persisted when the flat is resolved by `buildingUUID+flatNumber` or by unique `subscriberID`; does not update address classifiers, use `/frontend/billing/addresses` for that)
        * @apiParam {String} [subscribers.login] subscriber login to store in flat
        * @apiParam {String} [subscribers.password] subscriber password to store in flat
        * @apiParam {Object[]} [subscribers.phones] phone numbers to import into RBT for this flat
        * @apiParam {String} subscribers.phones.phone mobile phone number
        * @apiParam {String="owner","regular"} [subscribers.phones.type=regular] desired role for this flat when the subscriber is added to this apartment; existing links in this flat are left unchanged
     * @apiSuccess {Object} subscriptions synchronization result
     * @apiSuccess {Number} subscriptions.processed total processed subscriber items
     * @apiSuccess {Number} subscriptions.updated successfully updated flats
     * @apiSuccess {Number} subscriptions.invalid invalid subscriber items
     * @apiSuccess {Number} subscriptions.notFound subscribers not matched to any flat
     * @apiSuccess {Number} subscriptions.failed internal processing errors count
     * @apiSuccess {String} subscriptions.defaultAction default action for missing contracts (`skipMissing` in current implementation)
     * @apiSuccess {Object} subscriptions.missing result for contracts not present in request
     * @apiSuccess {Number} subscriptions.missing.updated count of missing contracts with updated autoBlock
     * @apiSuccess {Number} subscriptions.missing.unchanged count of missing contracts left unchanged
     * @apiSuccess {Number} subscriptions.missing.failed count of missing contracts failed to update
     * @apiSuccess {Object[]} subscriptions.errors list of validation/runtime errors
     */

    /**
     * billing api
     */

    namespace api\billing {

        use api\api;

        /**
         * subscriptions method
         */

        class subscriptions extends api {

            public static function POST($params) {
                // Method for synchronizing the list of active contracts for auto-blocking
                $response = false;

                if (
                    array_key_exists("subscribers", $params) &&
                    is_array($params['subscribers'])
                ) {
                    $billing = loadBackend("billing");
                    if (!$billing) {
                        return "error";
                    }   

                    $_subscribers = [];

                    foreach ($params['subscribers'] as $index => $subscriber) {
                        if (!is_array($subscriber)) {
                            return "subscriber item must be object at index " . $index;
                        }

                        $item = [];

                        if (array_key_exists("isActive", $subscriber)) {
                            $item["isActive"] = $subscriber["isActive"];
                        }

                        if (array_key_exists("subscriberID", $subscriber)) {
                            $item["subscriberID"] = $subscriber["subscriberID"];
                        }

                        if (array_key_exists("agreement", $subscriber)) {
                            $item["agreement"] = $subscriber["agreement"];
                        }

                        if (array_key_exists("addressText", $subscriber)) {
                            $item["addressText"] = $subscriber["addressText"];
                        }

                        if (array_key_exists("login", $subscriber)) {
                            $item["login"] = $subscriber["login"];
                        }

                        if (array_key_exists("password", $subscriber)) {
                            $item["password"] = $subscriber["password"];
                        }

                        if (array_key_exists("phones", $subscriber)) {
                            $item["phones"] = $subscriber["phones"];
                        }

                        if (array_key_exists("buildingUUID", $subscriber)) {
                            $item["buildingUUID"] = $subscriber["buildingUUID"];
                        }

                        if (array_key_exists("flatNumber", $subscriber)) {
                            $item["flatNumber"] = $subscriber["flatNumber"];
                        }

                        $_subscribers[] = $item;
                    }

                    $response = $billing->syncAutoBlockByContracts($_subscribers, "skipMissing");
                }
                
                return api::ANSWER($response, ($response !== false) ? "subscriptions" : false);
            }
        }
    }
