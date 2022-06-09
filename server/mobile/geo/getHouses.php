<?php

/**
 * @api {post} /geo/getHouses список домов
 * @apiVersion 1.0.0
 * @apiDescription **[метод готов]**
 *
 * @apiGroup Geo
 *
 * @apiHeader {String} authorization токен авторизации
 *
 * @apiParam {Number} streetId улица
 *
 * @apiSuccess {Object[]} - массив объектов
 * @apiSuccess {Number} -.houseId идентификатор дома
 * @apiSuccess {String} -.number номер дома
 */

auth();

$street_id = (int)@$postdata['streetId'];

response(200, pg_fetch_all(pg_query("select house_id as \"houseId\", number from address.houses where street_id=$street_id")));
