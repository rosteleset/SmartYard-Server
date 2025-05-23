<?php

    /**
     * @api {post} /mobile/address/plog получить журнал событий объекта за день
     * @apiVersion 1.0.0
     * @apiDescription **в работе**
     *
     * @apiGroup Address
     *
     * @apiHeader {string} authorization токен авторизации
     *
     * @apiBody {string} flatId идентификатор квартиры
     * @apiBody {string="Y-m-d"} day дата (день)
     *
     * @apiSuccess {object[]} - массив объектов
     * @apiSuccess {string="Y-m-d H:i:s"} -.date дата
     * @apiSuccess {integer} [-.timezone] часовой пояс (default - Moscow Time)
     * @apiSuccess {UUID} -.uuid UUID события (уникален)
     * @apiSuccess {integer} -.objectId идентификатор объекта (домофона)
     * @apiSuccess {integer="0"} -.objectType тип объекта (0 - домофон)
     * @apiSuccess {integer="0","1","2"} -.objectMechanizma идентификатор нагрузки (двери)
     * @apiSuccess {string} -.mechanizmaDescription описание нагрузки (двери)
     * @apiSuccess {integer} [-.houseId] идентификатор дома
     * @apiSuccess {integer} [-.entranceId] идентификатор входа
     * @apiSuccess {integer} [-.cameraId] идентификатор камеры
     * @apiSuccess {string="1 - не отвечен","2 - отвечен","3 - открытие ключом","4 - открытие приложением","5 - открытие по морде лица","6 - открытие кодом открытия","7 - открытие звонком (гость, калитка)","9 - открытие по номеру машины"} -.event тип события
     * @apiSuccess {string} [-.preview] url картинки
     * @apiSuccess {integer="0","1","2"} -.previewType тип каринки (0 - нет, 1 - DVR, 2 - FRS)
     * @apiSuccess {string} [-.detail] непонятная фигня
     * @apiSuccess {object} [-.detailX] детализация события
     * @apiSuccess {string="t","f"} [-.detailX.opened] открыли или нет (1, 2)
     * @apiSuccess {string} [-.detailX.key] ключ (3)
     * @apiSuccess {string} [-.detailX.phone] телефон (4)
     * @apiSuccess {string} [-.detailX.faceId] идентификатор лица (5+)
     * @apiSuccess {string} [-.detailX.code] код открытия (6)
     * @apiSuccess {string} [-.detailX.phoneFrom] телефон (7)
     * @apiSuccess {string} [-.detailX.phoneTo] телефон (7)
     * @apiSuccess {object} [-.detailX.flags] доп. флаги
     * @apiSuccess {void} [-.detailX.flags.canLike] можно "лайкать"
     * @apiSuccess {void} [-.detailX.flags.canDislike] можно "дизлайкать"
     * @apiSuccess {void} [-.detailX.flags.liked] уже "лайкнуто"
     * @apiSuccess {object} [-.detailX.face] дополнительная информация по распознанному лицу
     * @apiSuccess {integer} [-.detailX.face.left] отступ по X
     * @apiSuccess {integer} [-.detailX.face.top] отступ по Y
     * @apiSuccess {integer} [-.detailX.face.width] ширина
     * @apiSuccess {integer} [-.detailX.face.height] высота
     * @apiSuccess {object} [-.detailX.vehicle] информация по распознанному автомобильному номеру
     * @apiSuccess {integer[]} [-.detailX.vehicle.vehicleBox] координаты левого верхнего и правого нижнего угла прямоугольной области, определяющей положение автомобиля
     * @apiSuccess {integer[]} [-.detailX.vehicle.plateKeyPoints] координаты четырёхугольника, определяющие положение автомобильного номера
     * @apiSuccess {string} [-.detailX.vehicle.plateNumber] автомобильный номер
     *
     * @apiErrorExample Ошибки
     * 402 требуется оплата
     * 403 требуется авторизация
     * 422 неверный формат данных
     * 404 пользователь не найден
     * 410 авторизация отозвана
     * 424 неверный токен
     */

    use backends\plog\plog;
    use backends\frs\frs;

    auth();
    $households = loadBackend("households");
    $flat_id = (int)@$postdata['flatId'];

    if (!$flat_id) {
        response(422);
    }

    $flatIds = array_map( function($item) { return $item['flatId']; }, $subscriber['flats']);
    $f = in_array($flat_id, $flatIds);
    if (!$f) {
        response(404);
    }

    if (!@$postdata['day']) {
        response(404);
    }

    $plog = loadBackend("plog");
    if (!$plog) {
        response(403);
    }

    // Checking for event availability
    $flat_owner = false;
    foreach ($subscriber['flats'] as $flat) {
        if ($flat['flatId'] == $flat_id) {
            $flat_owner = ($flat['role'] == 0);
            break;
        }
    }

    $flat_details = $households->getFlat($flat_id);

    // Checking account balance. Possible use redirect to payment screen on mobile app by response code 402
    if ($flat_details['autoBlock']){
        response(402);
    } elseif ($flat_details['adminBlock'] || $flat_details['manualBlock']) {
        response(403);
    }

    $plog_access = $flat_details['plog'];
    if ($plog_access == $plog::ACCESS_DENIED || $plog_access == $plog::ACCESS_RESTRICTED_BY_ADMIN
        || $plog_access == $plog::ACCESS_OWNER_ONLY && !$flat_owner) {
        response(403);
    }

    try {
        $date = date('Ymd', strtotime(@$postdata['day']));
        $result = $plog->getDetailEventsByDay($flat_id, $date);
        if ($result) {
            $events_details = [];
            foreach ($result as &$row) {
                $e_details = [];
                $e_details['date'] = date('Y-m-d H:i:s', $row[plog::COLUMN_DATE]);
                $e_details['uuid'] = $row[plog::COLUMN_EVENT_UUID];
                $e_details['previewType'] = $row[plog::COLUMN_PREVIEW];

                $domophone = json_decode($row[plog::COLUMN_DOMOPHONE]);
                if (isset($domophone->domophone_id) && isset($domophone->domophone_output)) {
                    $e_details['objectId'] = strval($domophone->domophone_id);
                    $e_details['objectType'] = "0";
                    $e_details['objectMechanizma'] = strval($domophone->domophone_output);
                    if (isset($domophone->domophone_description) && $domophone->domophone_description !== false) {
                        $e_details['mechanizmaDescription'] = $domophone->domophone_description;
                    } else {
                        $e_details['mechanizmaDescription'] = '';
                    }
                    if (isset($domophone->house_id) && $domophone->house_id > 0) {
                        $e_details['houseId'] = $domophone->house_id;
                    }
                    if (isset($domophone->entrance_id) && $domophone->entrance_id > 0) {
                        $e_details['entranceId'] = $domophone->entrance_id;
                    }
                    if (isset($domophone->camera_id) && $domophone->camera_id > 0) {
                        $e_details['cameraId'] = $domophone->camera_id;
                    }
                } else {
                    continue;
                }

                $event_type = (int)$row[plog::COLUMN_EVENT];
                $e_details['event'] = strval($event_type);
                $face = json_decode($row[plog::COLUMN_FACE], false);
                if (isset($face->width) && $face->width > 0 && isset($face->height) && $face->height > 0) {
                    $e_details['detailX']['face'] = [
                        'left' => strval($face->left),
                        'top' => strval($face->top),
                        'width' => strval($face->width),
                        'height' => strval($face->height)
                    ];
                    $frs = loadBackend("frs");
                    if ($frs) {
                        $e_details['detailX']['flags'] = [frs::FLAG_CAN_LIKE];
                        $face_id = null;
                        if (isset($face->faceId) && $face->faceId > 0) {
                            $face_id = $face->faceId;
                        }
                        $subscriber_id = (int)$subscriber['subscriberId'];
                        if ($frs->isLikedFlagFrs($flat_id, $subscriber_id, $face_id, $row[plog::COLUMN_EVENT_UUID], $flat_owner)) {
                            $e_details['detailX']['flags'][] = frs::FLAG_LIKED;
                            $e_details['detailX']['flags'][] = frs::FLAG_CAN_DISLIKE;
                        }
                    }
                }
                if (isset($face->faceId) && $face->faceId > 0) {
                    $e_details['detailX']['faceId'] = strval($face->faceId);
                }

                $phones = json_decode($row[plog::COLUMN_PHONES]);

                switch ($event_type) {
                    case plog::EVENT_UNANSWERED_CALL:
                    case plog::EVENT_ANSWERED_CALL:
                        $e_details['detailX']['opened'] = ($row[plog::COLUMN_OPENED] == 1) ? 't' : 'f';
                        break;

                    case plog::EVENT_OPENED_BY_KEY:
                        $e_details['detailX']['key'] = strval($row[plog::COLUMN_RFID]);
                        break;

                    case plog::EVENT_OPENED_BY_APP:
                        if ($phones->user_phone) {
                            $e_details['detailX']['phone'] = strval($phones->user_phone);
                        }
                        break;

                    case plog::EVENT_OPENED_BY_FACE:
                        break;

                    case plog::EVENT_OPENED_BY_CODE:
                        $e_details['detailX']['code'] = strval($row[plog::COLUMN_CODE]);
                        break;

                    case plog::EVENT_OPENED_GATES_BY_CALL:
                        if ($phones->user_phone) {
                            $e_details['detailX']['phoneFrom'] = strval($phones->user_phone);
                        }
                        if ($phones->gate_phone) {
                            $e_details['detailX']['phoneTo'] = strval($phones->gate_phone);
                        }
                        break;

                    case plog::EVENT_OPENED_BY_VEHICLE:
                        $e_details['detailX']['vehicle'] = json_decode($row[plog::COLUMN_VEHICLE]);
                        break;
                }
                if ((int)$row[plog::COLUMN_PREVIEW]) {
                    $img_uuid = $row[plog::COLUMN_IMAGE_UUID];
                    $url = @$config["api"]["mobile"] . "/address/plogCamshot/$img_uuid";
                    $e_details['preview'] = $url;
                }

                $events_details[] = $e_details;
            }
            response(200, $events_details);
        } else {
            response();
        }
    } catch (\Throwable $e)  {
        response(500, false, i18n("mobile.500"));
    }
