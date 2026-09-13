<?php

namespace api\houses;

use api\api;

/** GET/PUT /api/houses/virtualIntercom/:entranceId. Uses house viewing/editing rights. */
class virtualIntercom extends api
{
    private static function handle(array $params, bool $write): array
    {
        require_once __DIR__ . '/../../virtual-intercom/Service.php';
        if (!preg_match('/^[1-9][0-9]*$/D', (string)($params['_id'] ?? ''))) return api::ERROR('badRequest');
        try {
            $service = \VirtualIntercom\Service::configured();
            $id = (int)$params['_id'];
            $panel = $write ? $service->savePanel($id, $params) : $service->panelSettings($id);
            return api::ANSWER($panel, 'virtualIntercom', 0);
        } catch (\RuntimeException $error) {
            $code = $error->getCode();
            if ($code >= 400 && $code < 500) return [$code => ['error' => $error->getMessage()]];
            throw $error;
        }
    }

    public static function GET($params) { return self::handle($params, false); }
    public static function PUT($params) { return self::handle($params, true); }

    public static function index()
    {
        return ['GET' => '#same(addresses,house,GET)', 'PUT' => '#same(addresses,house,PUT)'];
    }
}
