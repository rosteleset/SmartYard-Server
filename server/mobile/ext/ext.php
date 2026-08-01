<?php

   /**
    * @api {post} /mobile/ext/ext расширение
    * @apiVersion 1.0.0
    * @apiDescription **нет верстки**
    *
    * @apiGroup Ext
    *
    * @apiHeader {String} authorization токен авторизации
    *
    * @apiBody {String} extId идентификатор расширения
    * @apiBody {Object[]} params параметры передаваемые в расширение
    * @apiBody {String} params.id идентификатор
    * @apiBody {String} params.value значение
    *
    * @apiSuccess {Object} - страничка которую надо отобразить во вьюшке
    * @apiSuccess {String} -.basePath базовый путь (от которго должна была загрузиться страница)
    * @apiSuccess {String} -.code html страница
    * @apiSuccess {Number=1,2} [-.version=1] версия реализации web-расширений (1 - по умолчанию)
    */

    auth();

    $page = null;
    mobileCustomize('ext.ext.page', $page, [
        'postdata' => $postdata,
        'subscriber' => $subscriber,
    ]);

    if ($page !== null) {
        response(200, $page);
    }

    response();
