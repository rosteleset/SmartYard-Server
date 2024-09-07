<?php

   /**
    * @api {post} /mobile/pay/prepare подготовка к платежу
    * @apiVersion 1.0.0
    * @apiDescription **в работе**
    *
    * @apiGroup Payments
    *
    * @apiHeader {String} authorization токен авторизации
    *
    * @apiBody {Number} clientId идентификатор клиента
    * @apiBody {Number} amount сумма платежа
    * @apiBody {String="rbs","dm"} type="dm" тип платежа
    *
    * @apiSuccess {String} - идентификатор платежа
    */

    auth();
    response(200, "");
