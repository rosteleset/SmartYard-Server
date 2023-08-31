<?php

/**
 * houses api
 */

namespace api\houses {

    use api\api;

    use Selpol\Task\Tasks\IntercomConfigureTask;
    use Selpol\Validator\Rule;

    /**
     * domophone method
     */
    class domophone extends api
    {
        public static function GET($params)
        {
            $validate = validate($params, ['_id' => [Rule::required(), Rule::int(), Rule::min(0), Rule::max(), Rule::nonNullable()]]);

            if ($validate == false)
                return api::ERROR();

            $households = loadBackend("households");

            return api::ANSWER($households->getDomophone($validate['_id']));
        }

        public static function POST($params)
        {
            $households = loadBackend("households");

            $domophoneId = $households->addDomophone($params["enabled"], $params["model"], $params["server"], $params["url"], $params["credentials"], $params["dtmf"], $params["nat"], $params["comment"]);

            return api::ANSWER($domophoneId, ($domophoneId !== false) ? "domophoneId" : false);
        }

        public static function PUT($params)
        {
            $households = loadBackend("households");

            if (array_key_exists('configure', $params)) {
                $first = array_key_exists('first', $params) ? $params['first'] : false;

                if ($first)
                    $households->modifyDomophone($params["_id"], null, null, null, null, null, null, true, null, null, null);

                return api::ANSWER(task(new IntercomConfigureTask($params['_id'], $first))->high()->dispatch());
            } else {
                $success = $households->modifyDomophone($params["_id"], $params["enabled"], $params["model"], $params["server"], $params["url"], $params["credentials"], $params["dtmf"], $params["firstTime"], $params["nat"], $params["locksAreOpen"], $params["comment"]);

                return api::ANSWER($success);
            }
        }

        public static function DELETE($params)
        {
            $households = loadBackend("households");

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
