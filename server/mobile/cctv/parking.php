<?php

    /**
     * @api {post} /mobile/cctv/parking получить свободные места парковочной камеры
     * @apiVersion 1.0.0
     * @apiDescription Данные для показа обработанного кадра со свободными местами. Все координаты - в пикселях обработанного кадра.
     * Камера должна быть в списке cctv/overview и быть парковочной (иметь объект parking).
     *
     * @apiGroup CCTV
     *
     * @apiHeader {String} authorization токен авторизации
     *
     * @apiBody {Number} cameraId идентификатор камеры
     *
     * @apiSuccess {String="Y-m-d H:i:s"} processedAt время последнего расчёта
     * @apiSuccess {Number} frameWidth ширина обработанного кадра
     * @apiSuccess {Number} frameHeight высота обработанного кадра
     * @apiSuccess {String} frameUrl url обработанного кадра (JPEG), требует токен авторизации; ссылка относится только к этому расчёту
     * и после следующего расчёта отвечает 404 - тогда нужно снова запросить cctv/parking
     * @apiSuccess {Number} capacity общее количество мест
     * @apiSuccess {Number} free количество свободных мест
     * @apiSuccess {Object[]} areas площадки парковки
     * @apiSuccess {String} areas.name название площадки
     * @apiSuccess {Number} areas.capacity количество мест на площадке
     * @apiSuccess {Object[]} areas.points контур площадки, 4 точки { x, y }
     * @apiSuccess {Object[][]} areas.freeSpaces свободные места площадки, каждое - 4 точки { x, y }
     *
     * @apiErrorExample {json} Ошибка
     * 404 камера не найдена или не является парковочной
     */

    auth();

    require_once __DIR__ . "/helpers/overviewCamera.php";

    $cameras = loadBackend("cameras");
    $dvr = loadBackend("dvr");
    $parking = loadBackend("parking");

    $camera_id = (int)@$postdata['cameraId'];

    if (!$parking || !mobile_overview_camera_url($cameras->getCamera($camera_id), $dvr)) {
        response(404);
    }

    $result = $parking->parkingCamera($camera_id);

    if ($result === false) {
        response(500, false, i18n("mobile.500"));
    }

    if (!$result) {
        response(404);
    }

    response(200, [
        "processedAt" => $result["processedAt"],
        "frameWidth" => $result["frameWidth"],
        "frameHeight" => $result["frameHeight"],
        "frameUrl" => @$config["api"]["mobile"] . "/cctv/parkingFrame/" . $result["frameId"],
        "capacity" => $result["capacity"],
        "free" => $result["free"],
        "areas" => $result["areas"],
    ]);
