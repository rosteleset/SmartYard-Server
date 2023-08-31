<?php

/**
 * houses api
 */

namespace api\houses {

    use api\api;

    use Selpol\Task\Tasks\IntercomConfigureTask;
    use Selpol\Validator\Rule;
    use Selpol\Validator\ValidatorMessage;

    /**
     * domophone method
     */
    class domophone extends api
    {
        public static function GET($params)
        {
            $validate = validate($params, ['_id' => [Rule::required(), Rule::int(), Rule::min(0), Rule::max(), Rule::nonNullable()]]);

            if ($validate instanceof ValidatorMessage)
                return api::ERROR($validate->getMessage());

            $households = backend("households");

            return api::ANSWER($households->getDomophone($validate['_id']));
        }

        public static function POST($params)
        {
            $households = backend("households");

            $domophoneId = $households->addDomophone($params["enabled"], $params["model"], $params["server"], $params["url"], $params["credentials"], $params["dtmf"], $params["nat"], $params["comment"]);

            return api::ANSWER($domophoneId, ($domophoneId !== false) ? "domophoneId" : false);
        }

        public static function PUT($params)
        {
            $households = backend("households");

            if (array_key_exists('configure', $params) && $params['configure'])
                task(new IntercomConfigureTask($params['_id'], array_key_exists('first', $params) ? $params["first"] : false))->high()->dispatch();

            $success = $households->modifyDomophone($params["_id"], $params["enabled"], $params["model"], $params["server"], $params["url"], $params["credentials"], $params["dtmf"], $params["firstTime"], $params["nat"], $params["locksAreOpen"], $params["comment"]);

            return api::ANSWER($success);
        }

        public static function DELETE($params)
        {
            $households = backend("households");

            $success = $households->deleteDomophone($params["_id"]);

            return api::ANSWER($success);
        }

        public static function index()
        {
            return [
                "GET" => "#same(addresses,house,GET)",
                "PUT" => "#same(addresses,house,PUT)",
                "POST" => "#same(addresses,house,PUT)",
                "DELETE" => "#same(addresses,house,PUT)",
            ];
        }
    }
}
