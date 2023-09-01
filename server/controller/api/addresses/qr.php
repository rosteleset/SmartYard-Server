<?php

/**
 * addresses api
 */

namespace api\addresses {

    use api\api;
    use Selpol\Task\Tasks\QrTask;
    use Selpol\Validator\Rule;
    use Selpol\Validator\ValidatorMessage;

    /**
     * qr method
     */
    class qr extends api
    {
        public static function POST($params)
        {
            $validate = validate($params, [
                '_id' => [Rule::required(), Rule::int(), Rule::min(0), Rule::max(), Rule::nonNullable()],
                'override' => [Rule::required(), Rule::bool(), Rule::nonNullable()]
            ]);

            if ($validate instanceof ValidatorMessage)
                return self::ERROR($validate->getMessage());

            $uuid = task(new QrTask($validate['_id'], null, $validate['override']))->sync();

            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="' . $uuid . '.zip"');

            echo backend('files')->getFileBytes($uuid);

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