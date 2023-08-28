<?php

/**
 * addresses api
 */

namespace api\addresses {

    use api\api;
    use Selpol\Task\Tasks\QrTask;
    use Selpol\Validator\Rule;

    /**
     * qr method
     */
    class qr extends api
    {
        public static function POST($params)
        {
            $validate = validate($params, [
                'houseId' => [Rule::required(), Rule::int(), Rule::min(0), Rule::max(), Rule::nonNullable()],
                'override' => [Rule::required(), Rule::bool(), Rule::nonNullable()]
            ]);

            $uuid = task(new QrTask($validate['houseId'], null, $validate['override']))->sync(null, null, null);

            header('Content-Disposition: attachment; filename="' . $uuid . '.zip"');

            echo loadBackend('files')->getFileBytes($uuid);

            exit(0);
        }

        public static function index()
        {
            return [
                "POST" => "#same(addresses,house,POST)"
            ];
        }
    }
}