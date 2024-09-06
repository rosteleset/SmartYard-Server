<?php

/**
 * @api {post} /mobile/pay/prepare подготовка к платежу
 * @apiVersion 1.0.0
 * @apiDescription ***в работе***
 *
 * @apiGroup Payments
 *
 * @apiHeader {String} authorization токен авторизации
 *
 * @apiParam {Number} clientId идентификатор клиента
 * @apiParam {Number} amount сумма платежа
 * @apiParam {String="rbs","dm"} type="dm" тип платежа
 *
 * @apiSuccess {String} - идентификатор платежа
 */

    auth();
    response(200, "");
