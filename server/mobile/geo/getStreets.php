<?php

/**
 * @api {post} /geo/getStreets список улиц
 * @apiVersion 1.0.0
 * @apiDescription **[метод готов]**
 *
 * @apiGroup Geo
 *
 * @apiHeader {String} authorization токен авторизации
 *
 * @apiParam {Number} locationId локация
 *
 * @apiSuccess {Object[]} - массив объектов
 * @apiSuccess {Number} -.streetId идентификатор улицы
 * @apiSuccess {String} -.name наименование улицы
 * @apiSuccess {String} -.type тип улицы
 */

auth();

$location_id = (int)@$postdata['locationId'];

response(200, pg_fetch_all(pg_query("select street_id as \"streetId\", streets.name as name, street_types.name as type from address.streets left join address.street_types using (street_type_id) where location_id=$location_id")));
