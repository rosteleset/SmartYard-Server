<?php

    /**
     * @api {post} /mobile/address/modifyKey отредактировать ключ
     * @apiVersion 1.0.0
     * @apiDescription **в работе**
     *
     * @apiGroup Address
     *
     * @apiHeader {String} authorization токен авторизации
     *
     * @apiSuccess {String} - показать alert c текстом
     *
     * @apiBody {Number} flatId идентификатор квартиры
     * @apiBody {Number} keyId идентификатр ключа
     * @apiBody {String} comments комментарии
     * @apiBody {String="t,f"} watch отслеживать ключ
     */

    auth();

    $households = loadBackend("households");

    $flatId = (int)@$postdata["flatId"];
    $keyId = (int)@$postdata["keyId"];
    $comments = @trim($postdata["comments"]);

    foreach ($subscriber['flats'] as $flat) {
        if ($flat['flatId'] && (int)$flat['role'] == 0) {
            $keys = $households->getKeys("keyId", $keyId);
            if ($keys && $keys[0] && $keys[0]["accessType"] == 2 && $keys[0]["accessTo"] == $flatId) {
                $households->modifyKey($keyId, $comments);
            }
            break;
        }
    }

    response();