<?php

namespace Selpol\Controller\Internal;

use backends\frs\frs;
use backends\plog\plog;
use Exception;
use Selpol\Controller\Controller;
use Selpol\Http\Response;
use Selpol\Service\CameraService;
use Selpol\Service\DomophoneService;
use Selpol\Service\RedisService;

class FrsController extends Controller
{
    public function callback(): Response
    {
        $frs = backend("frs");
        $households = backend("households");

        $body = $this->request->getParsedBody();

        $camera_id = $this->request->getQueryParam('stream_id');

        $face_id = (int)$body[frs::P_FACE_ID];
        $event_id = (int)$body[frs::P_EVENT_ID];

        if (!isset($camera_id) || $face_id == 0 || $event_id == 0)
            return $this->rbtResponse(204);

        $frs_key = "frs_key_" . $camera_id;

        $redis = container(RedisService::class)->getRedis();

        if ($redis->get($frs_key) != null)
            return $this->rbtResponse(204);

        $entrance = $frs->getEntranceByCameraId($camera_id);

        if (!$entrance)
            return $this->rbtResponse(204);

        $flats = $frs->getFlatsByFaceId($face_id, $entrance["entranceId"]);

        if (!$flats)
            return $this->rbtResponse(204);

        $domophone_id = $entrance["domophoneId"];
        $domophone_output = $entrance["domophoneOutput"];
        $domophone = $households->getDomophone($domophone_id);

        try {
            $model = container(DomophoneService::class)->get($domophone["model"], $domophone["url"], $domophone["credentials"]);
            $model->open_door($domophone_output);
            $redis->set($frs_key, 1, config()["backends"]["frs"]["open_door_timeout"]);

            $plog = backend("plog");

            if ($plog)
                $plog->addDoorOpenDataById(time(), $domophone_id, plog::EVENT_OPENED_BY_FACE, $domophone_output, $face_id . "|" . $event_id);
        } catch (Exception) {
            return $this->rbtResponse(404);
        }

        return $this->rbtResponse(204);
    }

    public function camshot(): Response
    {
        $camera_id = $this->getRoute()->getParam('id');

        if (!isset($camera_id) || $camera_id == 0)
            return $this->rbtResponse(404);

        $cameras = backend("cameras");

        $camera = $cameras->getCamera($camera_id);

        $model = container(CameraService::class)->get($camera['model'], $camera['url'], $camera['credentials']);

        if (!$model)
            return $this->rbtResponse(404);

        return $this->response()->withHeader('Content-Type', 'image/jpeg')->withString($model->camshot());
    }
}