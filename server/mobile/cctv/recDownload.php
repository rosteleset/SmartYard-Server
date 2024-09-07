<?php

   /**
    * @api {post} /mobile/cctv/recDownload запросить url фрагмента архива
    * @apiVersion 1.0.0
    * @apiDescription **очти готов**
    *
    * @apiGroup CCTV
    *
    * @apiBody {Number} id идентификатор фрагмента
    *
    * @apiHeader {String} authorization токен авторизации
    *
    * @apiSuccess {String} - url
    */

    auth();
    response();
