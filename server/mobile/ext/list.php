<?php

/**
 * @api {post} /mobile/ext/list список глобальных расширений (меню)
 * @apiVersion 1.0.0
 * @apiDescription **[нет верстки]**
 *
 * @apiGroup Ext
 *
 * @apiHeader {String} authorization токен авторизации
 *
 * @apiSuccess {Object[]} - массив объектов
 * @apiSuccess {String} -.caption имя расширения (для отображения)
 * @apiSuccess {String} -.icon иконка расширения (svg)
 * @apiSuccess {String} -.extId идентификатор расширения
 * @apiSuccess {Number} -.order порядок следования (вес)
 * @apiSuccess {String="t","f"} [-.highlight="f"] "подсветка" (красная точка)
 */

    auth();
    response();
