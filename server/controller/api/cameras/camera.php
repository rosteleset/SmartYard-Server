<?php

/**
 * cameras api
 */

namespace api\cameras {

    use api\api;
    use Selpol\Validator\Rule;
    use Selpol\Validator\ValidatorMessage;

    /**
     * camera method
     */
    class camera extends api
    {
        public static function GET($params)
        {
            $validate = validate($params, ['_id' => [Rule::required(), Rule::int(), Rule::min(0), Rule::max(), Rule::nonNullable()]]);

            if ($validate instanceof ValidatorMessage)
                return api::ERROR($validate->getMessage());

            $cameras = backend('cameras');

            return api::ANSWER($cameras->getCamera($validate['_id']));
        }

        public static function POST($params)
        {
            $cameras = backend("cameras");

            $cameraId = $cameras->addCamera($params["enabled"], $params["model"], $params["url"], $params["stream"], $params["credentials"], $params["name"], $params["dvrStream"], $params["timezone"], $params["lat"], $params["lon"], $params["direction"], $params["angle"], $params["distance"], $params["frs"], $params["mdLeft"], $params["mdTop"], $params["mdWidth"], $params["mdHeight"], $params["common"], $params["comment"]);

            return api::ANSWER($cameraId, ($cameraId !== false) ? "cameraId" : false);
        }

        public static function PUT($params)
        {
            $cameras = backend("cameras");

            $success = $cameras->modifyCamera($params["_id"], $params["enabled"], $params["model"], $params["url"], $params["stream"], $params["credentials"], $params["name"], $params["dvrStream"], $params["timezone"], $params["lat"], $params["lon"], $params["direction"], $params["angle"], $params["distance"], $params["frs"], $params["mdLeft"], $params["mdTop"], $params["mdWidth"], $params["mdHeight"], $params["common"], $params["comment"]);

            return api::ANSWER($success ?: $params["_id"], $success ? "cameraId" : false);
        }

        public static function DELETE($params)
        {
            $cameras = backend("cameras");

            $success = $cameras->deleteCamera($params["_id"]);

            return api::ANSWER($success);
        }

        public static function index()
        {
            return [
                "GET" => "#same(addresses,house,GET)",
                "PUT" => "#same(addresses,house,PUT)",
                "POST" => "#same(addresses,house,POST)",
                "DELETE" => "#same(addresses,house,DELETE)",
            ];
        }
    }
}
