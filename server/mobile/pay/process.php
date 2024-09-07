<?php

   /**
    * @api {post} /mobile/pay/process обработка платежа
    * @apiVersion 1.0.0
    * @apiDescription **в работе**
    *
    * @apiGroup Payments
    *
    * @apiHeader {String} authorization токен авторизации
    *
    * @apiBody {String} paymentId идентификатор платежа
    * @apiBody {String} sbId присвоенный сбером идентификатор
    *
    * @apiSuccess {String} - сообщение пользователю
    */

    auth();
    response(200, "");
