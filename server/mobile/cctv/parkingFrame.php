<?php

    /**
     * @api {get} /mobile/cctv/parkingFrame/:frameId получить обработанный кадр парковочной камеры
     * @apiVersion 1.0.0
     * @apiDescription Кадр по ссылке frameUrl из cctv/parking. Ссылка относится только к одному расчёту: после следующего расчёта
     * (или если камера перестала быть парковочной) метод отвечает 404, и нужно снова запросить cctv/parking за новой ссылкой.
     *
     * @apiGroup CCTV
     *
     * @apiHeader {String} authorization токен авторизации
     *
     * @apiParam {String} frameId идентификатор кадра
     *
     * @apiSuccess {File} - обработанный кадр в формате камеры: JPEG, PNG, BMP или WebP, тип - в Content-Type
     *
     * @apiErrorExample {json} Ошибка
     * 404 кадр больше не доступен
     */

    auth();

    require_once __DIR__ . "/helpers/overviewCamera.php";

    $cameras = loadBackend("cameras");
    $dvr = loadBackend("dvr");
    $parking = loadBackend("parking");
    $files = loadBackend("files");

    $camera_id = $parking ? $parking->parkingFrameCamera($param) : null;

    if ($camera_id === false) {
        response(500, false, i18n("mobile.500"));
    }

    if (!$camera_id || !$files || !mobile_overview_camera_url($cameras->getCamera($camera_id), $dvr)) {
        response(404);
    }

    try {
        $stream = $files->getFileStream($param);
    } catch (\Throwable $error) {
        // the frame was removed after the check
        $stream = false;
    }

    if (!$stream) {
        response(404);
    }

    $frame = stream_get_contents($stream);
    $type = $parking->frameType($frame);

    // the content of a frame never changes, a replaced frame is never served again
    header("Content-Type: " . ($type ? $type["mime"] : "application/octet-stream"));
    header("Cache-Control: private, max-age=86400");
    echo $frame;
    exit;
