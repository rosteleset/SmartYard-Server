<?php

/**
 * @api {post} /geo/getAllLocations список населенных пунктов
 * @apiVersion 1.0.0
 * @apiDescription **[метод готов]**
 *
 * @apiGroup Geo
 *
 * @apiHeader {String} authorization токен авторизации
 *
 * @apiSuccess {Object[]} - массив объектов
 * @apiSuccess {Number} -.locationId идентификатор населенного пункта
 * @apiSuccess {String} [-.areaName] наименование района
 * @apiSuccess {String} -.locationName наименование населенного пункта
 * @apiSuccess {String} -.name наименование населенного пункта
 */

auth();

$area_id = (int)@$postdata['areaId'];

response(200, pg_fetch_all(pg_query("select location_id as \"locationId\", case when without_area then null else areas.name end as \"areaName\", location_name as \"locationName\", case when location_name in (select location_name from (select location_name, count(*) as c from address.locations group by location_name) as t1 where c>1) then location_abbrev || ' ' || location_name || ' (' || areas.name || ')' else location_abbrev || ' ' || location_name end as name from address.locations left join address.areas using (area_id) order by location_name")));
