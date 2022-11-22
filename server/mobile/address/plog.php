<?php

/**
 * @api {post} /address/plog получить журнал событий объекта за день
 * @apiVersion 1.0.0
 * @apiDescription ***в работе***
 *
 * @apiGroup Address
 *
 * @apiHeader {string} authorization токен авторизации
 *
 * @apiParam {string} flatId идентификатор квартиры
 * @apiParam {string="Y-m-d"} day дата (день)
 *
 * @apiSuccess {object[]} - массив объектов
 * @apiSuccess {string="Y-m-d H:i:s"} -.date дата
 * @apiSuccess {UUID} -.uuid UUID события (уникален)
 * @apiSuccess {UUID} [-.image] UUID картинки (может повторяться для "дублирующихся" событий)
 * @apiSuccess {integer} -.objectId идентификатор объекта (домофона)
 * @apiSuccess {integer="0"} -.objectType тип объекта (0 - домофон)
 * @apiSuccess {integer="0","1","2"} -.objectMechanizma идентификатор нагрузки (двери)
 * @apiSuccess {string} -.mechanizmaDescription описание нагрузки (двери)
 * @apiSuccess {integer="1 - не отвечен","2 - отвечен","3 - открытие ключом","4 - открытие приложением","5 - открытие по морде лица","6 - открытие кодом открытия","7 - открытие звонком (гость, калитка)"} -.event тип события
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
 * @apiSuccess {object} [-.detailX.face] координаты распознанного лица
 * @apiSuccess {integer} [-.detailX.face.left] отступ по X
 * @apiSuccess {integer} [-.detailX.face.top] отступ по Y
 * @apiSuccess {integer} [-.detailX.face.width] ширина
 * @apiSuccess {integer} [-.detailX.face.height] высота
 *
 * @apiErrorExample Ошибки
 * 403 требуется авторизация
 * 422 неверный формат данных
 * 404 пользователь не найден
 * 410 авторизация отозвана
 * 424 неверный токен
 */

use backends\plog\plog;

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

$events = loadBackend("plog");

if (!$events) {
    response(403);
}

// TODO сделать проверку на доступность и видимость событий

try {
    $date = date('Ymd', strtotime(@$postdata['day']));
    $result = $events->getDetailEventsByDay($flat_id, $date);
    if ($result) {
        $events_details = [];
        foreach ($result as &$row) {
            $e_details = [];
            $e_details['date'] = $row[plog::COLUMN_DATE];
            $e_details['uuid'] = $row[plog::COLUMN_EVENT_UUID];
            $e_details['image'] = $row[plog::COLUMN_IMAGE_UUID];
            $e_details['previewType'] = $row[plog::COLUMN_PREVIEW];
            $e_details['objectId'] = $row[plog::COLUMN_DOMOPHONE_ID];
            $e_details['objectType'] = 0;
            $e_details['objectMechanizma'] = $row[plog::COLUMN_DOMOPHONE_OUTPUT];
            $e_details['mechanizmaDescription'] = $row[plog::COLUMN_DOMOPHONE_OUTPUT_DESCRIPTION];
            $e_details['event'] = $row[plog::COLUMN_EVENT];
            $face = json_decode($row[plog::COLUMN_FACE]);
            if ($face->width && $face->height) {
                $e_details['detailX']['face'] = [
                    'left' => $face->left,
                    'top' => $face->top,
                    'width' => $face->width,
                    'height' => $face->height
                ];
            }
            if ($face->faceId) {
                $e_details['detailX']['faceId'] = $face->faceId;
            }

            switch ((int)$row[plog::COLUMN_EVENT]) {
                case plog::EVENT_UNANSWERED_CALL:
                case plog::EVENT_ANSWERED_CALL:
                    $e_details['detailX']['opened'] = ($row[plog::COLUMN_OPENED] == 1) ? 't' : 'f';
                    break;

                case plog::EVENT_OPENED_BY_KEY:
                    $e_details['detailX']['key'] = $row[plog::COLUMN_RFID];
                    break;

                case plog::EVENT_OPENED_BY_APP:
                    $e_details['detailX']['phone'] = $row[plog::COLUMN_USER_PHONE];
                    break;

                case plog::EVENT_OPENED_BY_FACE:
                    break;

                case plog::EVENT_OPENED_BY_CODE:
                    $e_details['detailX']['code'] = $row[plog::COLUMN_CODE];
                    break;

                case plog::EVENT_OPENED_GATES_BY_CALL:
                    $e_details['detailX']['phoneFrom'] = $row[plog::COLUMN_USER_PHONE];
                    $e_details['detailX']['phoneTo'] = $row[plog::COLUMN_GATE_PHONE];
                    break;
            }
            if ((int)$row[plog::COLUMN_PREVIEW]) {
                $img = $row[plog::COLUMN_IMAGE_UUID];
                $url =@$config["api"]["mobile"] . "/image/camshot/$img";
                $e_details['preview'] = $url;
            }

            $events_details[] = $e_details;
        }
        response(200, $events_details);
    } else {
        response();
    }
} catch (\Throwable $e)  {
    response(200, $e->getMessage());
    response(500, false, 'Внутренняя ошибка сервера');
}
    
    /*
     * Disclaimer: использование "иерархии" владелец\не владелец считаю в данном случае избыточным и вредоносным,
     * повлиять на это решение возможности нет, ну и хуй с ним
     * (общий список свой\чужой проще в реализации и не так убивает производительность)
     */

/*
    $flat_id = @(int)$postdata['flatId'];

    if (!in_array($flat_id, all_flats())) {
        response(404);
    }

    $f = pg_fetch_assoc(pg_query("select disable_plog, hidden_plog from domophones.flat_settings where flat_id = $flat_id"));
    $hidden = @$f['hidden_plog'] == 't';
    $disabled = @$f['disable_plog'] == 't';

    if ($disabled) { // типа логирование вообще отключено
        response(403);
    }

    $my_relation_to_this_flat = flat_relation($flat_id, $bearer['id']);
    if ($hidden && $my_relation_to_this_flat != 'owner') { // разрешено только для владельца
        response(403);
    }

    if (!@$postdata['day']) {
        response(404);
    }
    $date = date('Ymd', strtotime(@$postdata['day']));

    $resp = mysqli_fetch_all(clickhouse("select date, uuid, image, object_id objectId, object_type objectType, object_mechanizma objectMechanizma, mechanizma_description mechanizmaDescription, event, detail, preview from plog where not hidden and toYYYYMMDD(date) = '$date' and flat_id = $flat_id and object_type = 0 order by date desc"), MYSQLI_ASSOC);

    $date = date('Y-m-d', strtotime(@$postdata['day']));

    $likes_by_event = [];
    $qr = mysql("select event, owner from dm.likes where flat_id = $flat_id"); // вот тут уже полный пиздец!
    while ($row = mysqli_fetch_assoc($qr)) {
        $likes_by_event[$row['event']][] = $row['owner'];
    }

    $likes_by_external_id = [];
    $qr = mysql("select external_face_id, owner from dm.faceflats where flat_id = $flat_id"); // а это еще больший пиздец!
    while ($row = mysqli_fetch_assoc($qr)) {
        $likes_by_external_id[$row['external_face_id']][] = $row['owner'];
    }

    $face2face = [];
    $qr = mysql("select face_id, external_face_id from dm.face2face where external_face_id in (select external_face_id from dm.faceflats where flat_id = $flat_id)"); // уже немного лучше
    while ($row = mysqli_fetch_assoc($qr)) {
        $face2face[$row['face_id']] = $row['external_face_id'];
    }

    foreach ($resp as &$row) {
        $preview_type = 0;
        if ((int)$row['preview'] && $row['image'] != '00000000-0000-0000-0000-000000000000') {
            $date = explode('-', explode(' ', $row['date'])[0]);
            $url = "https://static.dm.lanta.me/{$date[0]}-{$date[1]}-{$date[2]}/{$row['image'][0]}/{$row['image'][1]}/{$row['image'][2]}/{$row['image'][3]}/{$row['image']}.jpg";
            $preview_type = (int)$row['preview'];
            $row['preview'] = $url;
            $row['previewType'] = $preview_type;
        } else {
            unset($row['preview']);
            unset($row['image']);
            $row['previewType'] = 0;
        }
        $detail = explode(':', $row['detail']);
        $row['detail'] = $detail[0];
        // 1 - не отвечен (detail = 1 - не открыт, detail = 2 - открыт)
        // 2 - отвечен (detail = 1 - не открыт, detail = 2 - открыт)
        // 3 - открытие ключом
        // 4 - открытие приложением
        // 5 - открытие по морде лица
        // 6 - открытие кодом открытия
        // 7 - открытие звонком (гость, калитка)
        switch ((int)$row['event']) {
            case 1:
            case 2:
                if (count($detail) > 1) {
                    $row['detailX'] = [
                        'opened' => ($detail[0] == 1)?'f':'t',
                        'face' => [
                            'left' => $detail[2],
                            'top' => $detail[3],
                            'width' => $detail[4],
                            'height' => $detail[5],
                        ],
                    ];
                } else {
                    $row['detailX'] = [
                        'opened' => ($detail[0] == 1)?'f':'t',
                    ];
                }
                break;
            case 3:
                if (count($detail) > 1) {
                    $row['detailX'] = [
                        'key' => $detail[0],
                        'face' => [
                            'left' => $detail[2],
                            'top' => $detail[3],
                            'width' => $detail[4],
                            'height' => $detail[5],
                        ],
                    ];
                } else {
                    $row['detailX'] = [
                        'key' => $detail[0],
                    ];
                }
                break;
            case 4:
                if (count($detail) > 1) {
                    $row['detailX'] = [
                        'phone' => $detail[0],
                        'face' => [
                            'left' => $detail[2],
                            'top' => $detail[3],
                            'width' => $detail[4],
                            'height' => $detail[5],
                        ],
                    ];
                } else {
                    $row['detailX'] = [
                        'phone' => $detail[0],
                    ];
                }
                break;
            case 5:
                $row['detailX'] = [
                    'faceId' => $detail[2],
                    'face' => [
                        'left' => $detail[3],
                        'top' => $detail[4],
                        'width' => $detail[5],
                        'height' => $detail[6],
                    ],
                ];
                break;
            case 6:
                if (count($detail) > 1) {
                    $row['detailX'] = [
                        'code' => $detail[0],
                        'face' => [
                            'left' => $detail[2],
                            'top' => $detail[3],
                            'width' => $detail[4],
                            'height' => $detail[5],
                        ],
                    ];
                } else {
                    $row['detailX'] = [
                        'code' => $detail[0],
                    ];
                }
                break;
            case 7:
                $row['detailX'] = [
                    'phoneFrom' => $detail[0],
                    'phoneTo' => $detail[1],
                ];
                break;
        }
        if ($preview_type == 2) {
            if ((int)$row['event'] == 5) {
                if (@$likes_by_external_id[$detail[1]]) {
                    if (in_array($bearer['id'], $likes_by_external_id[$detail[1]]?:[]) || $my_relation_to_this_flat == 'owner') {
                        $row['detailX']['flags'][] = 'canDislike';
                    }
                }
                if (in_array($bearer['id'], @$likes_by_event[$row['uuid']]?:[])) {
                    $row['detailX']['flags'][] = 'liked';
                } else {
                    $row['detailX']['flags'][] = 'canLike';
                }
                $row['detail'] = false;
            } else {
                if (@$likes_by_event[$row['uuid']]) {
                    if (in_array($bearer['id'], $likes_by_event[$row['uuid']]?:[]) || $my_relation_to_this_flat == 'owner') {
                        $row['detailX']['flags'][] = 'canDislike';
                        if (@$face2face[$detail[1]]) {
                            $row['detailX']['faceId'] = $face2face[$detail[1]];
                        }
                    }
                    if (in_array($bearer['id'], $likes_by_event[$row['uuid']]?:[])) {
                        $row['detailX']['flags'][] = 'liked';
                    } else {
                        $row['detailX']['flags'][] = 'canLike';
                    }
                } else {
                    $row['detailX']['flags'][] = 'canLike';
                }
            }
        }
        if (!$row['detail']) {
            unset($row['detail']);
        }
        if (!$row['detailX']) {
            unset($row['detailX']);
        }
    }

    if (count($resp)) {
        response(200, $resp);
    } else {
        response();
    }
*/
