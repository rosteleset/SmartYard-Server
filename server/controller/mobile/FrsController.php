<?php

namespace Selpol\Controller\mobile;

use backends\frs\frs;
use backends\plog\plog;
use Selpol\Controller\Controller;
use Selpol\Http\Response;

class FrsController extends Controller
{
    public function index(): Response
    {
        $user = $this->getSubscriber();

        $flatId = $this->getRoute()->getParamInt('flatId');

        if ($flatId === null)
            return $this->rbtResponse(400, message: 'Квартира не указана');

        $flatIds = array_map(static fn($item) => $item['flatId'], $user['flats']);

        $f = in_array($flatId, $flatIds);

        if (!$f)
            $this->rbtResponse(404, message: 'Квартира не найдена');

        $flatOwner = false;

        foreach ($user['flats'] as $flat)
            if ($flat['flatId'] == $flatId) {
                $flatOwner = ($flat['role'] == 0);

                break;
            }

        $frs = backend('frs');

        $subscriber_id = (int)$user['subscriberId'];
        $faces = $frs->listFaces($flatId, $subscriber_id, $flatOwner);
        $result = [];

        foreach ($faces as $face)
            $result[] = ['faceId' => $face[frs::P_FACE_ID], 'image' => @config()['api']['mobile'] . '/address/plogCamshot/' . $face[frs::P_FACE_IMAGE]];

        return $this->rbtResponse(data: $result);
    }

    public function store(): Response
    {
        $user = $this->getSubscriber();

        $eventId = $this->getRoute()->getParam('eventId');

        if ($eventId === null)
            return $this->rbtResponse(404);

        $plog = backend("plog");
        $frs = backend("frs");

        $eventData = $plog->getEventDetails($eventId);

        if ($eventData === false)
            return $this->rbtResponse(404);

        if ($eventData[plog::COLUMN_PREVIEW] == plog::PREVIEW_NONE)
            $this->rbtResponse(404, message: 'Нет кадра события');

        $flat_ids = array_map(static fn(array $item) => $item['flatId'], $user['flats']);

        $flat_id = (int)$eventData[plog::COLUMN_FLAT_ID];
        $f = in_array($flat_id, $flat_ids);

        if (!$f)
            $this->rbtResponse(404, message: 'Квартира не найдена');

        $households = backend('households');
        $domophone = json_decode($eventData[plog::COLUMN_DOMOPHONE], false);
        $entrances = $households->getEntrances('domophoneId', ['domophoneId' => $domophone->domophone_id, 'output' => $domophone->domophone_output]);

        if ($entrances && $entrances[0]) {
            $cameras = $households->getCameras('id', $entrances[0]['cameraId']);

            if ($cameras && $cameras[0]) {
                $face = json_decode($eventData[plog::COLUMN_FACE], true);
                $result = $frs->registerFace($cameras[0], $eventId, $face['left'] ?? 0, $face['top'] ?? 0, $face['width'] ?? 0, $face['height'] ?? 0);

                if (!isset($result[frs::P_FACE_ID]))
                    return $this->rbtResponse(400, message: $result[frs::P_MESSAGE]);

                $face_id = (int)$result[frs::P_FACE_ID];
                $subscriber_id = (int)$user['subscriberId'];

                $frs->attachFaceId($face_id, $flat_id, $subscriber_id);

                return $this->rbtResponse();
            }
        }

        return $this->rbtResponse();
    }

    public function delete(): Response
    {
        $user = $this->getSubscriber();

        $plog = backend("plog");
        $frs = backend("frs");

        $eventId = $this->request->getQueryParam('eventId');

        $face_id = null;
        $face_id2 = null;

        if ($eventId) {
            $eventData = $plog->getEventDetails($eventId);
            if (!$eventData)
                $this->rbtResponse(404, message: 'Событие не найдено');

            $flat_id = (int)$eventData[plog::COLUMN_FLAT_ID];

            $face = json_decode($eventData[plog::COLUMN_FACE]);
            if (isset($face->faceId) && $face->faceId > 0)
                $face_id = (int)$face->faceId;

            $face_id2 = $frs->getRegisteredFaceId($eventId);

            if ($face_id2 === false)
                $face_id2 = null;
        } else {
            $flat_id = (int)$this->request->getQueryParam('flatId');
            $face_id = (int)$this->request->getQueryParam('faceId');
        }

        if (($face_id === null || $face_id <= 0) && ($face_id2 === null || $face_id2 <= 0))
            return $this->rbtResponse(404);

        $flat_ids = array_map(static fn(array $item) => $item['flatId'], $user['flats']);
        $f = in_array($flat_id, $flat_ids);

        if (!$f)
            return $this->rbtResponse(404);

        $flat_owner = false;

        foreach ($user['flats'] as $flat)
            if ($flat['flatId'] == $flat_id) {
                $flat_owner = ($flat['role'] == 0);

                break;
            }

        if ($flat_owner) {
            if ($face_id > 0) $frs->detachFaceIdFromFlat($face_id, $flat_id);
            if ($face_id2 > 0) $frs->detachFaceIdFromFlat($face_id2, $flat_id);
        } else {
            $subscriber_id = (int)$user['subscriberId'];

            if ($face_id > 0) $frs->detachFaceId($face_id, $subscriber_id);
            if ($face_id2 > 0) $frs->detachFaceId($face_id2, $subscriber_id);
        }

        return $this->rbtResponse();
    }
}