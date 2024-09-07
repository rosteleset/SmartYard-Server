<?php

   /**
    * @api {post} /mobile/cctv/youtube получить список роликов на YouTube
    * @apiVersion 1.0.0
    * @apiDescription **почти готов**
    *
    * @apiGroup CCTV
    *
    * @apiBody {Number} [id] id камеры
    *
    * @apiHeader {String} authorization токен авторизации
    *
    * @apiSuccess {Object[]} - массив c роликами
    * @apiSuccess {Number} -.id id камеры
    * @apiSuccess {String="Y-m-d H:i:s"} -.eventTime время события
    * @apiSuccess {integer} [-.timezone] часовой пояс (default - Moscow Time)
    * @apiSuccess {String} -.title заголовок
    * @apiSuccess {String} -.description описание
    * @apiSuccess {String} -.thumbnailsDefault превью
    * @apiSuccess {String} -.thumbnailsMedium превью
    * @apiSuccess {String} -.thumbnailsHigh превью
    * @apiSuccess {String} -.url ссылка на ролик
    * @apiSuccess {String} -.addressLine адрес (?)
    */

    auth();
    response();
